<?php
require_once __DIR__ . '/cloudflareapi.php';
require_once __DIR__ . '/dnsmanager_lib.php';

use WHMCS\Database\Capsule;
use DnsManager\CloudflareAPI;

function dnsmanager_clientarea($vars) {
    $authType = isset($vars['auth_type']) ? $vars['auth_type'] : 'token';
    $apiEmail = isset($vars['api_email']) ? $vars['api_email'] : '';
    $apiSecret = isset($vars['api_key']) ? $vars['api_key'] : '';
    $accountId = isset($vars['account_id']) ? $vars['account_id'] : '';
    $token = isset($vars['token']) ? $vars['token'] : (isset($_SESSION['token']) ? $_SESSION['token'] : '');

    $currentUserId = 0;
    if (!empty($_SESSION['uid'])) {
        $currentUserId = (int) $_SESSION['uid'];
    } elseif (!empty($vars['client']['id'])) {
        $currentUserId = (int) $vars['client']['id'];
    } elseif (!empty($vars['clientsdetails']['userid'])) {
        $currentUserId = (int) $vars['clientsdetails']['userid'];
    }

    $message = '';
    $error = '';
    $view = 'domains';

    $action = isset($_GET['action']) ? $_GET['action'] : 'domains';
    $domainId = isset($_GET['domain_id']) ? (int) $_GET['domain_id'] : 0;
    // pagination & filters
    $dpage = isset($_GET['dpage']) ? max(1, (int) $_GET['dpage']) : 1;
    $dlimit = isset($_GET['dlimit']) ? max(5, (int) $_GET['dlimit']) : 10;
    $rpage = isset($_GET['rpage']) ? max(1, (int) $_GET['rpage']) : 1;
    $rlimit = isset($_GET['rlimit']) ? max(5, (int) $_GET['rlimit']) : 25;
    $rsearch = isset($_GET['rsearch']) ? trim($_GET['rsearch']) : '';

    $selectedDomain = null;
    if ($domainId > 0) {
        try {
            $selectedDomain = Capsule::table('mod_dnsmanager_domains')
                ->where('id', $domainId)
                ->where('userid', $currentUserId)
                ->first();
            if (!$selectedDomain) {
                $error = '无权访问该域名或域名不存在。';
                $domainId = 0;
            }
        } catch (\Exception $e) {
            $error = '数据库未初始化，请联系管理员在后台激活模块。';
            $domainId = 0;
        }
    }

    $buildApi = function($defaultZoneId = null) use ($authType, $apiEmail, $apiSecret, $accountId) {
        return new CloudflareAPI($authType, $apiEmail, $apiSecret, $accountId, $defaultZoneId);
    };

    $logAction = function($action, $type = '', $name = '', $content = '') use ($currentUserId) {
        if (!function_exists('dnsmanager_log')) {
            return;
        }
        dnsmanager_log($currentUserId, $action, $type, $name, $content);
    };

    $extractCfError = function($resp) {
        if (isset($resp['errors']) && is_array($resp['errors']) && isset($resp['errors'][0]['message'])) {
            return $resp['errors'][0]['message'];
        }
        if (isset($resp['messages']) && is_array($resp['messages']) && isset($resp['messages'][0])) {
            return is_array($resp['messages'][0]) && isset($resp['messages'][0]['message']) ? $resp['messages'][0]['message'] : $resp['messages'][0];
        }
        return '未知错误';
    };

    // Handle POST actions first
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // CSRF check (only enforce when token is present)
        $postedToken = isset($_POST['token']) ? $_POST['token'] : '';
        if ($token !== '' && $postedToken !== $token) {
            $error = '安全校验失败，请刷新页面重试';
        }
        if ($action === 'importwhmcs' && $error === '') {
            $imported = 0; $skipped = 0; $failed = 0;
            try {
                $whmcsDomains = Capsule::table('tbldomains')
                    ->where('userid', $currentUserId)
                    ->whereIn('status', ['Active','Pending Transfer'])
                    ->get();
                foreach ($whmcsDomains as $d) {
                    $exists = Capsule::table('mod_dnsmanager_domains')
                        ->where('userid', $currentUserId)
                        ->where('domain', $d->domain)
                        ->first();
                    if ($exists) { $skipped++; continue; }
                    $res = dnsmanager_add_domain_for_user($currentUserId, $d->domain, ['create' => true]);
                    if (isset($res['success']) && $res['success']) { $imported++; }
                    else { $failed++; }
                }
                $message = '导入完成：新增 ' . $imported . '，跳过 ' . $skipped . '，失败 ' . $failed;
                $logAction('import-whmcs', '', '', 'imported=' . $imported . ', skipped=' . $skipped . ', failed=' . $failed);
            } catch (\Exception $e) {
                $error = '读取 WHMCS 域名失败：' . $e->getMessage();
            }
            $action = 'domains';
        }
        if ($action === 'adddomain') {
            $domainName = trim(isset($_POST['domain']) ? $_POST['domain'] : '');
            if ($domainName === '') {
                $error = '域名不能为空';
            } else {
                try {
                    $existing = Capsule::table('mod_dnsmanager_domains')
                        ->where('userid', $currentUserId)
                        ->where('domain', $domainName)
                        ->first();
                } catch (\Exception $e) {
                    $existing = null;
                    $error = '数据库未初始化，请联系管理员在后台激活模块。';
                }
                if ($existing) {
                    $error = '该域名已添加';
                } else {
                    $api = $buildApi();
                    $createResp = $api->createZone($domainName, false, 'full');
                    if (isset($createResp['success']) && $createResp['success']) {
                        $zone = $createResp['result'];
                        $zoneId = $zone['id'];
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
                                'userid' => $currentUserId,
                                'domain' => $domainName,
                                'zone_id' => $zoneId,
                                'ns1' => $ns1,
                                'ns2' => $ns2,
                                'ns_status' => 'pending',
                            ]);
                        } catch (\Exception $e) {
                            $error = '保存域名到数据库失败，请联系管理员。';
                        }
                        $message = '域名已添加，请将域名的 NS 修改为 Cloudflare 分配的 NS 后等待生效。';
                        $logAction('add-domain', '', $domainName, $zoneId);
                    } else {
                        $error = '创建 Cloudflare Zone 失败：' . $extractCfError($createResp);
                    }
                }
            }
            $action = 'domains';
        }

        if ($action === 'deletedomain' && isset($_POST['domain_id'])) {
            $delId = (int) $_POST['domain_id'];
            try {
                $domainRow = Capsule::table('mod_dnsmanager_domains')
                    ->where('id', $delId)
                    ->where('userid', $currentUserId)
                    ->first();
                if (!$domainRow) {
                    $error = '域名不存在或无权限';
                } else {
                    $mode = isset($_POST['mode']) ? $_POST['mode'] : 'unbind';
                    if ($mode === 'delete_zone') {
                        $api = $buildApi($domainRow->zone_id);
                        $resp = $api->deleteZone($domainRow->zone_id);
                        if (!(isset($resp['success']) && $resp['success'])) {
                            $error = '删除 Cloudflare Zone 失败';
                        }
                    }
                    if ($error === '') {
                        Capsule::table('mod_dnsmanager_domains')->where('id', $delId)->delete();
                        // cleanup desired
                        try { Capsule::table('mod_dnsmanager_desired_records')->where('domain_id', $delId)->delete(); } catch (\Exception $e) {}
                        $message = '域名已移除';
                        $logAction('delete-domain', '', $domainRow->domain, $domainRow->zone_id);
                    }
                }
            } catch (\Exception $e) {
                $error = '删除失败：' . $e->getMessage();
            }
            $action = 'domains';
        }

        if (($action === 'adddns' || $action === 'editdns') && $selectedDomain && $error === '') {
            $zoneId = $selectedDomain->zone_id;
            $api = $buildApi($zoneId);

            $type = isset($_POST['type']) ? strtoupper(trim($_POST['type'])) : '';
            $name = isset($_POST['name']) ? trim($_POST['name']) : '';
            $content = isset($_POST['content']) ? trim($_POST['content']) : '';
            $ttl = isset($_POST['ttl']) ? (int) $_POST['ttl'] : 1;
            if ($ttl <= 0) { $ttl = 1; }
            $proxied = isset($_POST['proxied']);

            // Validate type and enforce proxy support for allowed types only
            $allowedTypes = ['A','AAAA','CNAME','MX','TXT','SRV','NS','CAA','PTR'];
            if (!in_array($type, $allowedTypes, true)) {
                $error = '不支持的记录类型';
            }
            $supportsProxy = in_array($type, ['A', 'AAAA', 'CNAME'], true);
            if (!$supportsProxy) { $proxied = false; }

            // Handle special types
            $priority = null;
            $data = null;
            if ($type === 'MX') {
                $priority = isset($_POST['priority']) ? (int) $_POST['priority'] : 0;
            } elseif ($type === 'SRV') {
                $data = [
                    'service' => isset($_POST['srv_service']) ? trim($_POST['srv_service']) : '',
                    'proto' => isset($_POST['srv_proto']) ? trim($_POST['srv_proto']) : '',
                    'name' => $name,
                    'target' => isset($_POST['srv_target']) ? trim($_POST['srv_target']) : '',
                    'priority' => isset($_POST['srv_priority']) ? (int) $_POST['srv_priority'] : 0,
                    'weight' => isset($_POST['srv_weight']) ? (int) $_POST['srv_weight'] : 0,
                    'port' => isset($_POST['srv_port']) ? (int) $_POST['srv_port'] : 0,
                ];
            } elseif ($type === 'CAA') {
                $data = [
                    'flags' => isset($_POST['caa_flags']) ? (int) $_POST['caa_flags'] : 0,
                    'tag' => isset($_POST['caa_tag']) ? trim($_POST['caa_tag']) : 'issue',
                    'value' => isset($_POST['caa_value']) ? trim($_POST['caa_value']) : '',
                ];
            }

            // 冲突预检：同名 CNAME 与其它类型互斥
            if ($error === '') {
                $zoneDomain = isset($selectedDomain->domain) ? strtolower($selectedDomain->domain) : '';
                $inputName = trim($name);
                $fqdn = $zoneDomain;
                if ($inputName !== '' && $inputName !== '@') {
                    $lower = strtolower($inputName);
                    if ($lower === $zoneDomain || (strlen($zoneDomain) > 0 && substr($lower, -1 - strlen($zoneDomain)) === '.' . $zoneDomain)) {
                        $fqdn = $lower;
                    } else {
                        $fqdn = $inputName . '.' . $zoneDomain;
                    }
                }
                $params = ['per_page' => 100, 'name' => $fqdn];
                $existing = $api->listRecords($zoneId, $params);
                $existingByType = [];
                $existingCnameCount = 0;
                if (isset($existing['result']) && is_array($existing['result'])) {
                    $editId = ($action === 'editdns' && isset($_POST['id'])) ? trim($_POST['id']) : '';
                    foreach ($existing['result'] as $rec) {
                        if (isset($rec['id']) && $editId !== '' && $rec['id'] === $editId) { continue; }
                        $rtype = isset($rec['type']) ? strtoupper($rec['type']) : '';
                        $existingByType[$rtype] = true;
                        if ($rtype === 'CNAME') { $existingCnameCount++; }
                    }
                }
                if ($type === 'CNAME') {
                    $hasNonCname = false;
                    foreach ($existingByType as $rtype => $_v) { if ($rtype !== 'CNAME') { $hasNonCname = true; break; } }
                    if ($hasNonCname) {
                        $error = '存在同名的其它记录类型，无法添加/更新 CNAME。请先删除该名称下的其它记录。';
                    }
                    if ($error === '' && $existingCnameCount > 0) {
                        $error = '该名称已存在 CNAME 记录且每个名称仅允许一个 CNAME。';
                    }
                } else {
                    if ($existingCnameCount > 0) {
                        $error = '存在同名 CNAME 记录，无法添加/更新其它类型。请先删除该名称下的 CNAME。';
                    }
                }
            }

            if ($error === '' && $action === 'adddns') {
                $resp = $api->addRecord($zoneId, $type, $name, $content, $ttl, $proxied, $priority, $data);
                if (isset($resp['success']) && $resp['success']) {
                    $message = 'DNS 记录已添加';
                    $logAction('add-record', $type, $name, $content);
                    // save desired
                    try {
                        $fqdn = function_exists('dnsmanager_build_fqdn') ? dnsmanager_build_fqdn($selectedDomain->domain, $name) : $name;
                        dnsmanager_save_desired_record($selectedDomain->id, $selectedDomain->zone_id, $type, $fqdn, $content, $ttl, $proxied, $priority, $data);
                    } catch (\Exception $e) {}
                } else {
                    $error = '添加 DNS 记录失败：' . $extractCfError($resp);
                }
            } elseif ($error === '') {
                $recordId = isset($_POST['id']) ? trim($_POST['id']) : '';
                $resp = $api->updateRecord($zoneId, $recordId, $type, $name, $content, $ttl, $proxied, $priority, $data);
                if (isset($resp['success']) && $resp['success']) {
                    $message = 'DNS 记录已更新';
                    $logAction('edit-record', $type, $name, $content);
                    try {
                        $fqdn = function_exists('dnsmanager_build_fqdn') ? dnsmanager_build_fqdn($selectedDomain->domain, $name) : $name;
                        dnsmanager_save_desired_record($selectedDomain->id, $selectedDomain->zone_id, $type, $fqdn, $content, $ttl, $proxied, $priority, $data, $recordId);
                    } catch (\Exception $e) {}
                } else {
                    $error = '更新 DNS 记录失败：' . $extractCfError($resp);
                }
            }
            $action = 'manage';
        }

        if ($action === 'deletedns' && $selectedDomain && isset($_POST['id']) && $error === '') {
        // 从 CSV 导入记录
        if ($action === 'importcsv' && $selectedDomain && $error === '') {
            if (!isset($_FILES['csvfile']) || !is_uploaded_file($_FILES['csvfile']['tmp_name'])) {
                $error = '未选择 CSV 文件';
            } else {
                $zoneId = $selectedDomain->zone_id;
                $api = $buildApi($zoneId);
                $fh = fopen($_FILES['csvfile']['tmp_name'], 'r');
                $rowNum = 0; $ok = 0; $fail = 0;
                if ($fh) {
                    while (($cols = fgetcsv($fh)) !== false) {
                        $rowNum++;
                        if ($rowNum === 1) {
                            // 跳过表头（如果检测到非类型字段）
                            if (isset($cols[0]) && !in_array(strtoupper(trim($cols[0])), ['A','AAAA','CNAME','MX','TXT','SRV','NS','CAA','PTR'], true)) {
                                continue;
                            }
                        }
                        $type = isset($cols[0]) ? strtoupper(trim($cols[0])) : '';
                        $name = isset($cols[1]) ? trim($cols[1]) : '';
                        $content = isset($cols[2]) ? trim($cols[2]) : '';
                        $ttl = isset($cols[3]) ? (int)$cols[3] : 1;
                        $proxied = isset($cols[4]) ? ((int)$cols[4] === 1) : false;
                        $priority = isset($cols[5]) && $cols[5] !== '' ? (int)$cols[5] : null;
                        $data = null;
                        if ($type === 'SRV' || $type === 'CAA') {
                            $data = isset($cols[6]) && $cols[6] !== '' ? json_decode($cols[6], true) : null;
                            if ($data === null) { $data = []; }
                        }
                        if ($type === '' || $name === '') { $fail++; continue; }
                        $resp = $api->addRecord($zoneId, $type, $name, $content, $ttl>0?$ttl:1, $proxied, $priority, $data);
                        if (isset($resp['success']) && $resp['success']) {
                            $ok++;
                            try {
                                $fqdn = function_exists('dnsmanager_build_fqdn') ? dnsmanager_build_fqdn($selectedDomain->domain, $name) : $name;
                                dnsmanager_save_desired_record($selectedDomain->id, $selectedDomain->zone_id, $type, $fqdn, $content, $ttl>0?$ttl:1, $proxied, $priority, $data);
                            } catch (\Exception $e) {}
                        } else { $fail++; }
                    }
                    fclose($fh);
                }
                $message = '导入完成：成功 ' . $ok . '，失败 ' . $fail;
            }
            $action = 'manage';
        }
            $zoneId = $selectedDomain->zone_id;
            $api = $buildApi($zoneId);
            $resp = $api->deleteRecord($zoneId, $_POST['id']);
            if (isset($resp['success']) && $resp['success']) {
                $message = 'DNS 记录已删除';
                $logAction('delete-record', '', '', $_POST['id']);
                try {
                    // best-effort cleanup by name/type if posted
                    $dtype = isset($_POST['type']) ? strtoupper(trim($_POST['type'])) : '';
                    $dname = isset($_POST['name']) ? trim($_POST['name']) : '';
                    if ($dtype !== '' && $dname !== '') {
                        $fqdn = function_exists('dnsmanager_build_fqdn') ? dnsmanager_build_fqdn($selectedDomain->domain, $dname) : $dname;
                        dnsmanager_delete_desired_record($selectedDomain->id, $dtype, $fqdn);
                    }
                } catch (\Exception $e) {}
            } else {
                $error = '删除 DNS 记录失败：' . $extractCfError($resp);
            }
            $action = 'manage';
        }

        // 批量操作（删除 / TTL / 代理）
        if ($action === 'bulkrecords' && $selectedDomain && $error === '') {
            $zoneId = $selectedDomain->zone_id;
            $api = $buildApi($zoneId);
            $ids = isset($_POST['ids']) ? (array)$_POST['ids'] : [];
            $bulk = isset($_POST['bulk_action']) ? $_POST['bulk_action'] : '';
            $bulkTtl = isset($_POST['bulk_ttl']) ? (int)$_POST['bulk_ttl'] : 1;
            $ok = 0; $fail = 0;
            foreach ($ids as $rid) {
                $rid = trim($rid);
                if ($rid === '') { continue; }
                $detail = $api->getRecord($zoneId, $rid);
                if (!(isset($detail['success']) && $detail['success'] && isset($detail['result']))) { $fail++; continue; }
                $r = $detail['result'];
                if ($bulk === 'delete') {
                    $resp = $api->deleteRecord($zoneId, $rid);
                    $ok += (isset($resp['success']) && $resp['success']) ? 1 : 0;
                    $fail += (isset($resp['success']) && $resp['success']) ? 0 : 1;
                } elseif ($bulk === 'ttl') {
                    $resp = $api->updateRecord($zoneId, $rid, $r['type'], $r['name'], isset($r['content'])?$r['content']:'', $bulkTtl>0?$bulkTtl:1, isset($r['proxied'])?$r['proxied']:false, isset($r['priority'])?$r['priority']:null, isset($r['data'])?$r['data']:null);
                    $ok += (isset($resp['success']) && $resp['success']) ? 1 : 0;
                    $fail += (isset($resp['success']) && $resp['success']) ? 0 : 1;
                } elseif ($bulk === 'proxy_on' || $bulk === 'proxy_off') {
                    $enable = $bulk === 'proxy_on';
                    // 仅对 A/AAAA/CNAME 生效
                    $t = isset($r['type']) ? strtoupper($r['type']) : '';
                    if (in_array($t, ['A','AAAA','CNAME'], true)) {
                        $resp = $api->updateRecord($zoneId, $rid, $t, $r['name'], isset($r['content'])?$r['content']:'', isset($r['ttl'])?$r['ttl']:1, $enable, isset($r['priority'])?$r['priority']:null, isset($r['data'])?$r['data']:null);
                        $ok += (isset($resp['success']) && $resp['success']) ? 1 : 0;
                        $fail += (isset($resp['success']) && $resp['success']) ? 0 : 1;
                    }
                }
            }
            $message = '批量操作完成：成功 ' . $ok . '，失败 ' . $fail;
            $action = 'manage';
        }
    }

    // Handle GET actions
    // 删除操作改为 POST，拒绝 GET

    if ($action === 'togglecdn' && $selectedDomain && isset($_GET['id'])) {
        $zoneId = $selectedDomain->zone_id;
        $api = $buildApi($zoneId);
        $recId = trim($_GET['id']);
        $enable = isset($_GET['enable']) && $_GET['enable'] === '1';
        $detail = $api->getRecord($zoneId, $recId);
        $ok = false; $msg = '';
        if (isset($detail['success']) && $detail['success'] && isset($detail['result'])) {
            $r = $detail['result'];
            $type = isset($r['type']) ? strtoupper($r['type']) : '';
            if (in_array($type, ['A','AAAA','CNAME'], true)) {
                $resp = $api->updateRecord($zoneId, $recId, $type, $r['name'], isset($r['content'])?$r['content']:'', isset($r['ttl'])?$r['ttl']:1, $enable, isset($r['priority'])?$r['priority']:null, isset($r['data'])?$r['data']:null);
                $ok = isset($resp['success']) && $resp['success'];
                if ($ok) {
                    // 更新 desired 记录
                    try {
                        $fqdn = function_exists('dnsmanager_build_fqdn') ? dnsmanager_build_fqdn($selectedDomain->domain, $r['name']) : $r['name'];
                        dnsmanager_save_desired_record($selectedDomain->id, $selectedDomain->zone_id, $type, $fqdn, isset($r['content'])?$r['content']:'', isset($r['ttl'])?$r['ttl']:1, $enable, isset($r['priority'])?$r['priority']:null, isset($r['data'])?$r['data']:null);
                    } catch (\Exception $e) {}
                    $logAction('toggle-cdn', $type, $r['name'], $enable ? 'enable=1' : 'enable=0');
                }
            } else {
                $msg = '不支持的类型';
            }
        } else {
            $msg = '读取记录失败';
        }
        header('Content-Type: application/json');
        echo json_encode(['success'=>$ok,'message'=>$msg,'enabled'=>$enable?1:0]);
        exit;
    }

    // 快速设置 TTL（AJAX）
    if ($action === 'setttl' && $selectedDomain && isset($_GET['id']) && isset($_GET['ttl'])) {
        $zoneId = $selectedDomain->zone_id;
        $api = $buildApi($zoneId);
        $recId = trim($_GET['id']);
        $newTtl = (int) $_GET['ttl'];
        $ok = false; $msg = '';
        $detail = $api->getRecord($zoneId, $recId);
        if (isset($detail['success']) && $detail['success'] && isset($detail['result'])) {
            $r = $detail['result'];
            $type = isset($r['type']) ? strtoupper($r['type']) : '';
            $nameVal = isset($r['name']) ? $r['name'] : '';
            $contentVal = isset($r['content']) ? $r['content'] : '';
            $ttlVal = $newTtl > 0 ? $newTtl : 1;
            $proxiedVal = isset($r['proxied']) ? (bool)$r['proxied'] : false;
            $priorityVal = isset($r['priority']) ? $r['priority'] : null;
            $dataVal = isset($r['data']) ? $r['data'] : null;
            $resp = $api->updateRecord($zoneId, $recId, $type, $nameVal, $contentVal, $ttlVal, $proxiedVal, $priorityVal, $dataVal);
            $ok = isset($resp['success']) && $resp['success'];
            if ($ok) {
                $message = 'TTL 已更新';
                $logAction('edit-record', $type, $nameVal, 'ttl=' . $ttlVal);
                try {
                    $fqdn = function_exists('dnsmanager_build_fqdn') ? dnsmanager_build_fqdn($selectedDomain->domain, $nameVal) : $nameVal;
                    dnsmanager_save_desired_record($selectedDomain->id, $selectedDomain->zone_id, $type, $fqdn, $contentVal, $ttlVal, $proxiedVal, $priorityVal, $dataVal);
                } catch (\Exception $e) {}
            } else { $msg = '更新失败'; }
        } else { $msg = '读取记录失败'; }
        header('Content-Type: application/json');
        echo json_encode(['success'=>$ok,'message'=>$msg,'ttl'=>$newTtl>0?$newTtl:1]);
        exit;
    }

    // 导出 CSV（当前筛选）
    if ($action === 'exportcsv' && $selectedDomain) {
        $zoneId = $selectedDomain->zone_id;
        $api = $buildApi($zoneId);
        $params = ['per_page' => 500];
        if ($rsearch !== '') { $params['name'] = $rsearch; }
        if (isset($_GET['rtype']) && $_GET['rtype'] !== '') { $params['type'] = strtoupper(trim($_GET['rtype'])); }
        $recordsData = $api->listRecords($zoneId, $params);
        $rows = isset($recordsData['result']) && is_array($recordsData['result']) ? $recordsData['result'] : [];
        $filename = 'dns_records_' . preg_replace('/[^A-Za-z0-9_.-]/','_', $selectedDomain->domain) . '.csv';
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        $out = fopen('php://output', 'w');
        if ($out) {
            fputcsv($out, ['type','name','content','ttl','proxied','priority','data']);
            foreach ($rows as $r) {
                $type = isset($r['type']) ? $r['type'] : '';
                $name = isset($r['name']) ? $r['name'] : '';
                $content = isset($r['content']) ? $r['content'] : '';
                $ttl = isset($r['ttl']) ? $r['ttl'] : 1;
                $proxied = isset($r['proxied']) && $r['proxied'] ? 1 : 0;
                $priority = isset($r['priority']) ? $r['priority'] : '';
                $data = isset($r['data']) ? json_encode($r['data']) : '';
                fputcsv($out, [$type,$name,$content,$ttl,$proxied,$priority,$data]);
            }
            fclose($out);
        }
        exit;
    }

    if ($action === 'refreshns' && $selectedDomain) {
        $api = $buildApi($selectedDomain->zone_id);
        $detail = $api->getZoneDetails($selectedDomain->zone_id);
        if (isset($detail['success']) && $detail['success']) {
            $result = $detail['result'];
            $status = isset($result['status']) ? $result['status'] : 'unknown';
            $ns1 = isset($result['name_servers'][0]) ? $result['name_servers'][0] : $selectedDomain->ns1;
            $ns2 = isset($result['name_servers'][1]) ? $result['name_servers'][1] : $selectedDomain->ns2;

            try {
                Capsule::table('mod_dnsmanager_domains')
                    ->where('id', $selectedDomain->id)
                    ->update([
                        'ns_status' => $status,
                        'ns1' => $ns1,
                        'ns2' => $ns2,
                        'ns_checked_at' => date('Y-m-d H:i:s'),
                    ]);
            } catch (\Exception $e) {
                // ignore
            }
            $message = '已检查 NS 状态';
        } else {
            $error = '检查 NS 状态失败';
        }
        $action = 'manage';
    }

    // Build data for template
    $templateVars = [
        'message' => $message,
        'error' => $error,
        'view' => 'domains',
        'domains' => [],
        'selectedDomain' => null,
        'dnsRecords' => [],
        'standalone' => isset($_GET['standalone']) ? 1 : 0,
        'dmStats' => ['total' => 0, 'active' => 0, 'pending' => 0],
        'search' => isset($_GET['search']) ? trim($_GET['search']) : '',
        'sort' => isset($_GET['sort']) ? trim($_GET['sort']) : 'created_at',
        'order' => isset($_GET['order']) ? trim($_GET['order']) : 'desc',
        'token' => $token,
        'dpage' => $dpage,
        'dlimit' => $dlimit,
        'domainsHasNext' => false,
        'rpage' => $rpage,
        'rlimit' => $rlimit,
        'rsearch' => $rsearch,
        'rtype' => isset($_GET['rtype']) ? strtoupper(trim($_GET['rtype'])) : '',
        'recordsHasNext' => false,
        'locked' => 0,
        'recentLogs' => [],
    ];

    // Domains list
    try {
        $allowedSort = ['domain', 'ns_status', 'created_at'];
        $sort = in_array($templateVars['sort'], $allowedSort, true) ? $templateVars['sort'] : 'created_at';
        $order = strtolower($templateVars['order']) === 'asc' ? 'asc' : 'desc';

        $domainsCountQuery = Capsule::table('mod_dnsmanager_domains')->where('userid', $currentUserId);
        $domainsQuery = Capsule::table('mod_dnsmanager_domains')->where('userid', $currentUserId);
        if ($templateVars['search'] !== '') {
            $domainsCountQuery->where('domain', 'like', '%' . $templateVars['search'] . '%');
            $domainsQuery->where('domain', 'like', '%' . $templateVars['search'] . '%');
        }
        $totalDomains = $domainsCountQuery->count();
        $offset = ($dpage - 1) * $dlimit;
        $domains = $domainsQuery->orderBy($sort, $order)->skip($offset)->take($dlimit)->get();
        $templateVars['domainsHasNext'] = ($offset + count($domains)) < $totalDomains;
    } catch (\Exception $e) {
        $domains = [];
        if ($error === '') {
            $error = '数据库未初始化，请联系管理员在后台激活模块。';
        }
    }

    // Cast domain rows to arrays for Smarty to avoid object-as-array errors
    $domainsArr = [];
    foreach ($domains as $d) { $domainsArr[] = (array)$d; }
    $templateVars['domains'] = $domainsArr;

    // Stats for domains
    try {
        $statsRow = Capsule::table('mod_dnsmanager_domains')
            ->where('userid', $currentUserId)
            ->selectRaw('COUNT(*) as total, SUM(CASE WHEN ns_status = "active" THEN 1 ELSE 0 END) as active')
            ->first();
        if ($statsRow) {
            $totalAll = (int)$statsRow->total;
            $activeAll = (int)$statsRow->active;
            $templateVars['dmStats'] = [
                'total' => $totalAll,
                'active' => $activeAll,
                'pending' => max(0, $totalAll - $activeAll),
            ];
        }
    } catch (\Exception $e) {
        // ignore
    }

    // Manage view
    if ($action === 'manage' && $selectedDomain) {
        $templateVars['view'] = 'records';
        // Pass array to Smarty to avoid stdClass access issues
        $templateVars['selectedDomain'] = (array)$selectedDomain;
        if (isset($selectedDomain->locked)) {
            $templateVars['locked'] = (int) $selectedDomain->locked;
        }
        // NS progress estimation
        $nsStatus = isset($selectedDomain->ns_status) ? $selectedDomain->ns_status : 'unknown';
        $createdAt = isset($selectedDomain->created_at) ? strtotime($selectedDomain->created_at) : time();
        $checkedAt = isset($selectedDomain->ns_checked_at) && $selectedDomain->ns_checked_at ? strtotime($selectedDomain->ns_checked_at) : null;
        $elapsedMin = max(0, floor((time() - $createdAt) / 60));
        $etaMin = 0; $progress = 0; $hint = '';
        if ($nsStatus === 'active') { $progress = 100; }
        else {
            // rough ETA model: typical 10-30min, worst 24h
            if ($elapsedMin <= 10) { $progress = 20; $etaMin = 10 - $elapsedMin; }
            elseif ($elapsedMin <= 30) { $progress = 20 + (int)(($elapsedMin-10) * 2); $etaMin = 30 - $elapsedMin; }
            elseif ($elapsedMin <= 120) { $progress = 60 + (int)(($elapsedMin-30) * 0.5); $etaMin = 120 - $elapsedMin; }
            else { $progress = min(95, 70 + (int)(($elapsedMin-120) * 0.1)); $etaMin = max(0, 1440 - $elapsedMin); }
            if ($progress < 5) { $progress = 5; }
            if ($progress > 99) { $progress = 99; }
        }
        if ($etaMin > 0) {
            if ($etaMin >= 60) { $hours = floor($etaMin/60); $mins = $etaMin % 60; $hint = $hours . ' 小时' . ($mins>0?(' ' . $mins . ' 分钟'):''); }
            else { $hint = $etaMin . ' 分钟'; }
        }
        // Status badge mapping for UI
        $nsBadgeClass = 'badge badge-danger';
        $nsBadgeText = '未修改 NS';
        if ($nsStatus === 'active') { $nsBadgeClass = 'badge badge-success'; $nsBadgeText = '已生效'; }
        elseif ($nsStatus === 'pending') { $nsBadgeClass = 'badge badge-warning'; $nsBadgeText = '待生效'; }
        $templateVars['nsBadgeClass'] = $nsBadgeClass;
        $templateVars['nsBadgeText'] = $nsBadgeText;
        $templateVars['nsProgress'] = $progress;
        $templateVars['nsEtaText'] = $hint;
        $templateVars['nsCheckedAt'] = $checkedAt ? date('Y-m-d H:i', $checkedAt) : '';
        $api = $buildApi($selectedDomain->zone_id);
        $params = ['per_page' => $rlimit, 'page' => $rpage];
        if ($rsearch !== '') { $params['name'] = $rsearch; }
        if ($templateVars['rtype'] !== '') { $params['type'] = $templateVars['rtype']; }
        $recordsData = $api->listRecords($selectedDomain->zone_id, $params);
        $dnsRecords = [];
        if (isset($recordsData['result']) && is_array($recordsData['result'])) {
            $dnsRecords = $recordsData['result'];
        }
        // 强制将记录及其嵌套 data 转为数组，避免 Smarty 报 stdClass 作为数组使用错误
        $normalized = [];
        foreach ($dnsRecords as $rec) {
            // 深度转换为数组
            $arr = json_decode(json_encode($rec), true);
            $normalized[] = $arr;
        }
        $dnsRecords = $normalized;
        if (isset($recordsData['result_info'])) {
            $info = $recordsData['result_info'];
            if (isset($info['page']) && isset($info['total_pages'])) {
                $templateVars['recordsHasNext'] = ((int)$info['page']) < ((int)$info['total_pages']);
            } elseif (isset($info['count'])) {
                $templateVars['recordsHasNext'] = ((int)$info['count']) === $rlimit;
            }
        } else {
            $templateVars['recordsHasNext'] = count($dnsRecords) === $rlimit;
        }
        $templateVars['dnsRecords'] = $dnsRecords;

        // 最近操作日志（前 10 条），转换为纯数组供 Smarty 使用
        try {
            $logsQ = Capsule::table('mod_dnsmanager_logs')
                ->where('userid', $currentUserId)
                ->orderBy('id', 'desc')
                ->limit(10);
            $logs = $logsQ->get();
            $logsArr = [];
            foreach ($logs as $lg) { $logsArr[] = (array) $lg; }
            $templateVars['recentLogs'] = $logsArr;
        } catch (\Exception $e) {
            $templateVars['recentLogs'] = [];
        }

        // Record type counts for UI filters
        $typeCounts = ['A'=>0,'AAAA'=>0,'CNAME'=>0,'MX'=>0,'TXT'=>0,'SRV'=>0,'NS'=>0];
        foreach ($dnsRecords as $rec) {
            if (isset($rec['type']) && isset($typeCounts[$rec['type']])) { $typeCounts[$rec['type']]++; }
        }
        $templateVars['recordTypeCounts'] = $typeCounts;
        $templateVars['recordTotal'] = count($dnsRecords);
    }

    return [
        'pagetitle' => 'DNS 管理',
        'breadcrumb' => ['index.php?m=dnsmanager' => 'DNS Manager'],
        'templatefile' => 'clientarea',
        'requirelogin' => true,
        'vars' => $templateVars,
    ];
}
