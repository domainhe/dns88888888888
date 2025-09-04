<?php
use WHMCS\Database\Capsule;
use WHMCS\View\Menu\Item as MenuItem;
require_once __DIR__ . '/dnsmanager_lib.php';
require_once __DIR__ . '/cloudflareapi.php';
use DnsManager\CloudflareAPI;

add_hook("ClientAreaPage", 1, function($vars) {
    // 预留：可在此注入模板变量
});

// 在主导航中添加 DNS 管理入口（WHMCS Six 主题 / WHMCS 7 兼容）
add_hook('ClientAreaPrimaryNavbar', 1, function (MenuItem $primaryNavbar) {
    try {
        $label = 'DNS 管理';
        $uri = 'index.php?m=dnsmanager';
        // 优先挂到“服务”下，如果没有则作为顶级
        $services = $primaryNavbar->getChild('Services');
        if ($services instanceof MenuItem) {
            if (!$services->getChild($label)) {
                $services->addChild($label)
                    ->setUri($uri)
                    ->setOrder(80)
                    ->setIcon('fa-globe');
            }
        } else {
            if (!$primaryNavbar->getChild($label)) {
                $primaryNavbar->addChild($label)
                    ->setUri($uri)
                    ->setOrder(80)
                    ->setIcon('fa-globe');
            }
        }
    } catch (\Throwable $e) {
        // 忽略导航注入异常，避免影响前台
    }
});

// 记录日志方法
function dnsmanager_log($userid, $action, $type, $name, $content) {
    Capsule::table("mod_dnsmanager_logs")->insert([
        "userid" => $userid,
        "action" => $action,
        "record_type" => $type,
        "record_name" => $name,
        "record_content" => $content,
    ]);
}

// 域名成功注册后自动添加到 DNS Manager
add_hook('AfterRegistrarRegistration', 1, function ($vars) {
    $userid = 0;
    if (isset($vars['params']['userid'])) { $userid = (int)$vars['params']['userid']; }
    elseif (isset($vars['userid'])) { $userid = (int)$vars['userid']; }
    if ($userid <= 0 && isset($vars['domainid'])) {
        try {
            $uid = Capsule::table('tbldomains')->where('id', (int)$vars['domainid'])->value('userid');
            if ($uid) { $userid = (int)$uid; }
        } catch (\Exception $e) {}
    }

    $domain = '';
    if (isset($vars['params']['domain']) && $vars['params']['domain'] !== '') { $domain = $vars['params']['domain']; }
    elseif (isset($vars['domain']) && $vars['domain'] !== '') { $domain = $vars['domain']; }
    else {
        $sld = isset($vars['params']['sld']) ? $vars['params']['sld'] : (isset($vars['sld']) ? $vars['sld'] : '');
        $tld = isset($vars['params']['tld']) ? $vars['params']['tld'] : (isset($vars['tld']) ? $vars['tld'] : '');
        if ($sld && $tld) { $domain = $sld . '.' . ltrim($tld, '.'); }
    }

    $domain = strtolower(trim($domain));
    if ($userid > 0 && $domain !== '') {
        try { dnsmanager_add_domain_for_user($userid, $domain, ['create' => true]); } catch (\Throwable $e) {}
    }
});

// 域名转入成功后自动添加到 DNS Manager
add_hook('AfterRegistrarTransfer', 1, function ($vars) {
    $userid = 0;
    if (isset($vars['params']['userid'])) { $userid = (int)$vars['params']['userid']; }
    elseif (isset($vars['userid'])) { $userid = (int)$vars['userid']; }
    if ($userid <= 0 && isset($vars['domainid'])) {
        try {
            $uid = Capsule::table('tbldomains')->where('id', (int)$vars['domainid'])->value('userid');
            if ($uid) { $userid = (int)$uid; }
        } catch (\Exception $e) {}
    }

    $domain = '';
    if (isset($vars['params']['domain']) && $vars['params']['domain'] !== '') { $domain = $vars['params']['domain']; }
    elseif (isset($vars['domain']) && $vars['domain'] !== '') { $domain = $vars['domain']; }
    else {
        $sld = isset($vars['params']['sld']) ? $vars['params']['sld'] : (isset($vars['sld']) ? $vars['sld'] : '');
        $tld = isset($vars['params']['tld']) ? $vars['params']['tld'] : (isset($vars['tld']) ? $vars['tld'] : '');
        if ($sld && $tld) { $domain = $sld . '.' . ltrim($tld, '.'); }
    }

    $domain = strtolower(trim($domain));
    if ($userid > 0 && $domain !== '') {
        try { dnsmanager_add_domain_for_user($userid, $domain, ['create' => true]); } catch (\Throwable $e) {}
    }
});

// CRON：定时检查 pending 域名的 NS 状态
// 通过内置 Daily Cron 执行（每日一次）
add_hook('DailyCronJob', 1, function($vars) {
    try {
        $pending = Capsule::table('mod_dnsmanager_domains')
            ->whereIn('ns_status', ['pending','unknown',''])
            ->orderBy('id', 'asc')
            ->limit(200)
            ->get();
        foreach ($pending as $row) {
            dnsmanager_refresh_ns_status($row);
        }
    } catch (\Exception $e) {}
});

// 在每小时的 Cron 中也跑一次，尽快同步状态
add_hook('AfterCronJob', 1, function($vars) {
    if (!isset($vars['jobname'])) { return; }
    // 仅在域名相关/计费任务后尝试刷新，避免过于频繁
    $name = strtolower((string)$vars['jobname']);
    if (strpos($name, 'domain') !== false || strpos($name, 'invoice') !== false || strpos($name, 'automation') !== false) {
        try {
            $pending = Capsule::table('mod_dnsmanager_domains')
                ->whereIn('ns_status', ['pending','unknown',''])
                ->orderBy('id', 'asc')
                ->limit(100)
                ->get();
            foreach ($pending as $row) {
                dnsmanager_refresh_ns_status($row);
            }
        } catch (\Exception $e) {}
    }
});

// CRON：对账 desired 与 Cloudflare 实际记录，按用户前台为准进行调整
add_hook('DailyCronJob', 2, function($vars) {
    $start = microtime(true);
    $ok = 0; $err = 0; $depth = 0; $msg = '';
    try {
        $domains = Capsule::table('mod_dnsmanager_domains')->orderBy('id','asc')->limit(200)->get();
        $depth = count($domains);
        foreach ($domains as $d) {
            $r = dnsmanager_reconcile_zone_to_desired($d);
            if (isset($r['success']) && $r['success']) { $ok++; } else { $err++; }
        }
    } catch (\Exception $e) { $msg = $e->getMessage(); }
    try {
        Capsule::table('mod_dnsmanager_cron_runs')->insert([
            'ran_at' => date('Y-m-d H:i:s'),
            'job' => 'reconcile-desired',
            'duration_ms' => (int) round((microtime(true) - $start) * 1000),
            'success_count' => $ok,
            'fail_count' => $err,
            'queue_depth' => $depth,
            'message' => $msg,
        ]);
    } catch (\Exception $e) {}
});
