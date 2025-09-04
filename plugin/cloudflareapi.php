<?php
namespace DnsManager;

class CloudflareAPI {
    private $authType; // 'key' or 'token'
    private $email;    // required for 'key'
    private $secret;   // api key or token
    private $accountId;
    private $defaultZoneId;
    private $apiUrl = "https://api.cloudflare.com/client/v4";

    public function __construct($authType, $email, $secret, $accountId, $defaultZoneId = null) {
        $this->authType = $authType === 'token' ? 'token' : 'key';
        $this->email = $email;
        $this->secret = $secret;
        $this->accountId = $accountId;
        $this->defaultZoneId = $defaultZoneId;
    }

    private function buildHeaders() {
        $headers = ["Content-Type: application/json"];
        if ($this->authType === 'token') {
            $headers[] = "Authorization: Bearer {$this->secret}";
        } else {
            $headers[] = "X-Auth-Email: {$this->email}";
            $headers[] = "X-Auth-Key: {$this->secret}";
        }
        return $headers;
    }

    private function request($method, $endpoint, $data = []) {
        $ch = curl_init();
        $headers = $this->buildHeaders();

        $url = $this->apiUrl . $endpoint;

        if ($method === "GET" && !empty($data)) {
            $url .= "?" . http_build_query($data);
        }

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers,
        ]);

        if ($method !== "GET" && !empty($data)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $response = curl_exec($ch);
        if ($response === false) {
            $error = curl_error($ch);
            curl_close($ch);
            return [
                'success' => false,
                'errors' => [['message' => 'cURL error: ' . $error]],
            ];
        }
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $responseHeaders = []; // not collected via curl options here
        curl_close($ch);
        $decoded = json_decode($response, true);
        if (is_array($decoded)) {
            $decoded['_http'] = ['code' => $httpCode];
        }
        return $decoded;
    }

    private function resolveZoneId($zoneId) {
        return $zoneId ?: $this->defaultZoneId;
    }

    public function findZoneByName($name) {
        $resp = $this->request("GET", "/zones", [
            'name' => $name,
            'per_page' => 1
        ]);
        if (isset($resp['success']) && $resp['success'] && !empty($resp['result'])) {
            return $resp['result'][0];
        }
        return null;
    }

    public function createZone($name, $jumpStart = false, $type = 'full') {
        // Try to resolve account id if not configured and token has exactly one account
        $accountId = $this->accountId;
        if (!$accountId) {
            $acc = $this->request('GET', '/accounts', ['per_page' => 2]);
            if (isset($acc['success']) && $acc['success'] && isset($acc['result']) && count($acc['result']) === 1) {
                $accountId = $acc['result'][0]['id'];
            }
        }
        $payload = [
            'name' => $name,
            'account' => ['id' => $accountId],
            'jump_start' => (bool)$jumpStart,
            'type' => $type,
        ];
        return $this->request('POST', '/zones', $payload);
    }

    public function getZoneDetails($zoneId) {
        $zoneId = $this->resolveZoneId($zoneId);
        return $this->request('GET', "/zones/{$zoneId}");
    }

    public function deleteZone($zoneId) {
        $zoneId = $this->resolveZoneId($zoneId);
        return $this->request('DELETE', "/zones/{$zoneId}");
    }

    public function listRecords($zoneId = null, $params = []) {
        $zoneId = $this->resolveZoneId($zoneId);
        return $this->request("GET", "/zones/{$zoneId}/dns_records", $params);
    }

    public function getRecord($zoneId, $recordId) {
        $zoneId = $this->resolveZoneId($zoneId);
        return $this->request("GET", "/zones/{$zoneId}/dns_records/{$recordId}");
    }

    private function buildRecordPayload($type, $name, $content, $ttl, $proxied, $priority = null, $data = null) {
        $payload = [
            'type' => strtoupper($type),
            'name' => $name,
            'ttl' => (int)$ttl,
        ];

        // Only A/AAAA/CNAME support proxied
        $supportsProxy = in_array(strtoupper($type), ['A', 'AAAA', 'CNAME'], true);
        if ($supportsProxy) {
            $payload['proxied'] = (bool)$proxied;
        }

        if (strtoupper($type) === 'MX') {
            $payload['content'] = $content;
            $payload['priority'] = $priority !== null ? (int)$priority : 0;
        } elseif (strtoupper($type) === 'SRV') {
            // SRV requires data object
            $payload['data'] = is_array($data) ? $data : [];
        } elseif (strtoupper($type) === 'CAA') {
            // CAA requires data object: flags, tag, value
            $payload['data'] = is_array($data) ? $data : [];
        } else {
            $payload['content'] = $content;
        }

        return $payload;
    }

    public function addRecord($zoneId, $type, $name, $content, $ttl = 1, $proxied = false, $priority = null, $data = null) {
        $zoneId = $this->resolveZoneId($zoneId);
        $payload = $this->buildRecordPayload($type, $name, $content, $ttl, $proxied, $priority, $data);
        return $this->request("POST", "/zones/{$zoneId}/dns_records", $payload);
    }

    public function updateRecord($zoneId, $recordId, $type, $name, $content, $ttl = 1, $proxied = false, $priority = null, $data = null) {
        $zoneId = $this->resolveZoneId($zoneId);
        $payload = $this->buildRecordPayload($type, $name, $content, $ttl, $proxied, $priority, $data);
        return $this->request("PUT", "/zones/{$zoneId}/dns_records/{$recordId}", $payload);
    }

    public function deleteRecord($zoneId, $recordId) {
        $zoneId = $this->resolveZoneId($zoneId);
        return $this->request("DELETE", "/zones/{$zoneId}/dns_records/{$recordId}");
    }

    // Health methods
    public function verifyAuth() {
        // A simple request that needs auth; use user endpoint for token or zones list
        $resp = $this->request('GET', '/user');
        if (!isset($resp['success']) || !$resp['success']) {
            return $resp;
        }
        return $resp;
    }

    public function rateLimitStatus() {
        // Cloudflare v4 doesn't expose quota easily; infer from http code or dummy req
        $resp = $this->request('GET', '/zones', ['per_page' => 1]);
        return $resp;
    }

    public function listAccounts($params = []) {
        return $this->request('GET', '/accounts', $params);
    }
}
