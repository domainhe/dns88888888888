<?php
use WHMCS\Database\Capsule;

require_once __DIR__ . '/cloudflareapi.php';
use DnsManager\CloudflareAPI;

if (!function_exists('dnsmanager_get_config')) {
    function dnsmanager_get_config() {
        $settings = [];
        try {
            $rows = Capsule::table('tbladdonmodules')
                ->where('module', 'dnsmanager')
                ->get();
            foreach ($rows as $row) {
                $settings[$row->setting] = $row->value;
            }
        } catch (\Exception $e) {
            // ignore and return empty which will fail later gracefully
        }

        $authType = isset($settings['auth_type']) ? $settings['auth_type'] : 'token';
        $apiEmail = isset($settings['api_email']) ? $settings['api_email'] : '';
        $apiKey = isset($settings['api_key']) ? $settings['api_key'] : '';
        $accountId = isset($settings['account_id']) ? $settings['account_id'] : '';

        return [
            'auth_type' => $authType,
            'api_email' => $apiEmail,
            'api_key' => $apiKey,
            'account_id' => $accountId,
        ];
    }
}

if (!function_exists('dnsmanager_build_api_from_config')) {
    function dnsmanager_build_api_from_config($defaultZoneId = null) {
        $cfg = dnsmanager_get_config();
        return new CloudflareAPI(
            isset($cfg['auth_type']) ? $cfg['auth_type'] : 'token',
            isset($cfg['api_email']) ? $cfg['api_email'] : '',
            isset($cfg['api_key']) ? $cfg['api_key'] : '',
            isset($cfg['account_id']) ? $cfg['account_id'] : '',
            $defaultZoneId
        );
    }
}

if (!function_exists('dnsmanager_add_domain_for_user')) {
    function dnsmanager_add_domain_for_user($userId, $domainName, $options = []) {
        $result = ['success' => false, 'message' => ''];
        $userId = (int) $userId;
        $domainName = strtolower(trim($domainName));
        if ($userId <= 0 || $domainName === '') {
            $result['message'] = 'Invalid user or domain';
            return $result;
        }

        try {
            $existing = Capsule::table('mod_dnsmanager_domains')
                ->where('userid', $userId)
                ->where('domain', $domainName)
                ->first();
            if ($existing) {
                $result['success'] = true;
                $result['message'] = 'Domain already exists';
                return $result;
            }
        } catch (\Exception $e) {
            $result['message'] = 'Database not initialized';
            return $result;
        }

        $api = dnsmanager_build_api_from_config();

        $zone = $api->findZoneByName($domainName);
        if (!$zone) {
            $createResp = $api->createZone($domainName, false, 'full');
            if (!(isset($createResp['success']) && $createResp['success'])) {
                $msg = 'Failed to create zone';
                if (isset($createResp['errors'][0]['message'])) {
                    $msg .= ': ' . $createResp['errors'][0]['message'];
                }
                $result['message'] = $msg;
                return $result;
            }
            $zone = $createResp['result'];
        }

        $zoneId = isset($zone['id']) ? $zone['id'] : '';
        $ns1 = '';
        $ns2 = '';
        if (!empty($zone['name_servers']) && is_array($zone['name_servers'])) {
            $ns1 = isset($zone['name_servers'][0]) ? $zone['name_servers'][0] : '';
            $ns2 = isset($zone['name_servers'][1]) ? $zone['name_servers'][1] : '';
        } else {
            $detail = $api->getZoneDetails($zoneId);
            if (isset($detail['success']) && $detail['success'] && !empty($detail['result']['name_servers'])) {
                $ns1 = isset($detail['result']['name_servers'][0]) ? $detail['result']['name_servers'][0] : '';
                $ns2 = isset($detail['result']['name_servers'][1]) ? $detail['result']['name_servers'][1] : '';
            }
        }

        try {
            Capsule::table('mod_dnsmanager_domains')->insert([
                'userid' => $userId,
                'domain' => $domainName,
                'zone_id' => $zoneId,
                'ns1' => $ns1,
                'ns2' => $ns2,
                'ns_status' => 'pending',
            ]);
        } catch (\Exception $e) {
            $result['message'] = 'Failed to save domain: ' . $e->getMessage();
            return $result;
        }

        if (function_exists('dnsmanager_log')) {
            dnsmanager_log($userId, 'auto-add-domain', '', $domainName, $zoneId);
        }

        $result['success'] = true;
        $result['message'] = 'Domain added';
        $result['zone_id'] = $zoneId;
        return $result;
    }
}

if (!function_exists('dnsmanager_refresh_ns_status')) {
    function dnsmanager_refresh_ns_status($domainRow) {
        $result = ['success' => false, 'status' => null];
        if (!$domainRow || empty($domainRow->zone_id)) { return $result; }
        $api = dnsmanager_build_api_from_config($domainRow->zone_id);
        $detail = $api->getZoneDetails($domainRow->zone_id);
        if (isset($detail['success']) && $detail['success']) {
            $res = $detail['result'];
            $status = isset($res['status']) ? $res['status'] : 'unknown';
            $ns1 = isset($res['name_servers'][0]) ? $res['name_servers'][0] : $domainRow->ns1;
            $ns2 = isset($res['name_servers'][1]) ? $res['name_servers'][1] : $domainRow->ns2;
            try {
                Capsule::table('mod_dnsmanager_domains')
                    ->where('id', $domainRow->id)
                    ->update([
                        'ns_status' => $status,
                        'ns1' => $ns1,
                        'ns2' => $ns2,
                        'ns_checked_at' => date('Y-m-d H:i:s'),
                    ]);
            } catch (\Exception $e) {}
            $result['success'] = true;
            $result['status'] = $status;
        }
        return $result;
    }
}

// Build FQDN from zone domain and input name
if (!function_exists('dnsmanager_build_fqdn')) {
    function dnsmanager_build_fqdn($zoneDomain, $inputName) {
        $zoneDomain = strtolower(trim($zoneDomain));
        $inputName = trim($inputName);
        if ($inputName === '' || $inputName === '@') { return $zoneDomain; }
        $lower = strtolower($inputName);
        if ($lower === $zoneDomain || (strlen($zoneDomain) > 0 && substr($lower, -1 - strlen($zoneDomain)) === '.' . $zoneDomain)) {
            return $lower;
        }
        return $inputName . '.' . $zoneDomain;
    }
}

if (!function_exists('dnsmanager_save_desired_record')) {
    function dnsmanager_save_desired_record($domainId, $zoneId, $type, $name, $content, $ttl, $proxied, $priority = null, $data = null, $recordId = null) {
        try {
            $payload = [
                'domain_id' => (int)$domainId,
                'zone_id' => $zoneId,
                'type' => strtoupper($type),
                'name' => $name,
                'content' => $content,
                'ttl' => (int)$ttl,
                'proxied' => $proxied ? 1 : 0,
                'priority' => $priority,
                'data' => $data ? json_encode($data) : null,
            ];
            // upsert by (domain_id, type, name) — simplified
            $exists = Capsule::table('mod_dnsmanager_desired_records')
                ->where('domain_id', (int)$domainId)
                ->where('type', strtoupper($type))
                ->where('name', $name)
                ->first();
            if ($exists) {
                Capsule::table('mod_dnsmanager_desired_records')->where('id', $exists->id)->update($payload);
            } else {
                Capsule::table('mod_dnsmanager_desired_records')->insert($payload);
            }
        } catch (\Exception $e) {
            // ignore
        }
    }
}

if (!function_exists('dnsmanager_delete_desired_record')) {
    function dnsmanager_delete_desired_record($domainId, $type, $name) {
        try {
            Capsule::table('mod_dnsmanager_desired_records')
                ->where('domain_id', (int)$domainId)
                ->where('type', strtoupper($type))
                ->where('name', $name)
                ->delete();
        } catch (\Exception $e) {}
    }
}

if (!function_exists('dnsmanager_reconcile_zone_to_desired')) {
    function dnsmanager_reconcile_zone_to_desired($domainRow) {
        $result = ['success'=>false,'created'=>0,'updated'=>0,'deleted'=>0,'errors'=>0];
        if (!$domainRow) { return $result; }
        $zoneId = $domainRow->zone_id;
        $api = dnsmanager_build_api_from_config($zoneId);
        $current = $api->listRecords($zoneId, ['per_page'=>500]);
        $currentList = isset($current['result']) && is_array($current['result']) ? $current['result'] : [];
        $desired = Capsule::table('mod_dnsmanager_desired_records')->where('domain_id', $domainRow->id)->get();

        // Index current by (type+name)
        $curMap = [];
        foreach ($currentList as $rec) {
            $key = strtoupper($rec['type']) . '|' . strtolower($rec['name']);
            $curMap[$key] = $rec;
        }
        // Sync desired -> current
        foreach ($desired as $d) {
            $key = strtoupper($d->type) . '|' . strtolower($d->name);
            if (!isset($curMap[$key])) {
                // create
                $data = $d->data ? json_decode($d->data, true) : null;
                $resp = $api->addRecord($zoneId, $d->type, $d->name, (string)$d->content, (int)$d->ttl, (bool)$d->proxied, $d->priority, $data);
                if (isset($resp['success']) && $resp['success']) { $result['created']++; } else { $result['errors']++; }
            } else {
                $rec = $curMap[$key];
                $needUpdate = false;
                // Compare essential fields
                if (strtoupper($d->type) === 'SRV') {
                    $curData = isset($rec['data']) ? $rec['data'] : [];
                    $desData = $d->data ? json_decode($d->data, true) : [];
                    if (json_encode($curData) !== json_encode($desData)) { $needUpdate = true; }
                } else {
                    $curContent = isset($rec['content']) ? (string)$rec['content'] : '';
                    if ($curContent !== (string)$d->content) { $needUpdate = true; }
                }
                $curTtl = isset($rec['ttl']) ? (int)$rec['ttl'] : 1;
                if ($curTtl !== (int)$d->ttl) { $needUpdate = true; }
                if (in_array(strtoupper($d->type), ['A','AAAA','CNAME'], true)) {
                    $curProxied = isset($rec['proxied']) ? (bool)$rec['proxied'] : false;
                    if ($curProxied !== ((int)$d->proxied === 1)) { $needUpdate = true; }
                }
                if (strtoupper($d->type) === 'MX') {
                    $curPri = isset($rec['priority']) ? (int)$rec['priority'] : 0;
                    $desPri = $d->priority !== null ? (int)$d->priority : 0;
                    if ($curPri !== $desPri) { $needUpdate = true; }
                }
                if ($needUpdate && isset($rec['id'])) {
                    $data = $d->data ? json_decode($d->data, true) : null;
                    $resp = $api->updateRecord($zoneId, $rec['id'], $d->type, $d->name, (string)$d->content, (int)$d->ttl, (bool)$d->proxied, $d->priority, $data);
                    if (isset($resp['success']) && $resp['success']) { $result['updated']++; } else { $result['errors']++; }
                }
                unset($curMap[$key]);
            }
        }
        // Optionally delete extra records not in desired (skip NS root records and CF system records)
        foreach ($curMap as $rec) {
            $type = isset($rec['type']) ? strtoupper($rec['type']) : '';
            if ($type === 'NS') { continue; }
            if (!isset($rec['id'])) { continue; }
            $resp = $api->deleteRecord($zoneId, $rec['id']);
            if (isset($resp['success']) && $resp['success']) { $result['deleted']++; } else { $result['errors']++; }
        }

        $result['success'] = true;
        return $result;
    }
}

