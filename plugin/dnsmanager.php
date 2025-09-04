<?php
if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

use WHMCS\Database\Capsule;
require_once __DIR__ . '/cloudflareapi.php';
require_once __DIR__ . '/clientarea.php';

function dnsmanager_config() {
    return [
        "name" => "DNS Manager",
        "description" => "让用户在 WHMCS 前台直接管理 Cloudflare DNS 记录",
        "version" => "1.3.1",
        "author" => "YourName",
        "fields" => [
            "auth_type" => [
                "FriendlyName" => "认证方式",
                "Type" => "dropdown",
                "Options" => "key=全局 API Key",
                "Description" => "固定使用邮箱 + Global API Key 认证",
                "Default" => "key",
            ],
            "api_email" => [
                "FriendlyName" => "Cloudflare Email",
                "Type" => "text",
                "Size" => "50",
                "Description" => "Cloudflare 登录邮箱（与 Global API Key 配对）",
            ],
            "api_key" => [
                "FriendlyName" => "Cloudflare API Key",
                "Type" => "password",
                "Size" => "50",
                "Description" => "Global API Key",
            ],
            "account_id" => [
                "FriendlyName" => "Cloudflare Account ID",
                "Type" => "text",
                "Size" => "50",
                "Description" => "可在 Cloudflare 后台获取；留空时系统尝试自动解析",
            ],
        ]
    ];
}

function dnsmanager_activate() {
    try {
        $sql = file_get_contents(__DIR__ . "/sql/install.sql");
        $statements = array_filter(array_map('trim', explode(';', $sql)));
        foreach ($statements as $statement) {
            if ($statement !== '') {
                Capsule::statement($statement);
            }
        }
        return ["status" => "success", "description" => "DNS Manager 插件已启用并创建数据表"];
    } catch (\Exception $e) {
        return ["status" => "error", "description" => "数据库错误: " . $e->getMessage()];
    }
}

function dnsmanager_deactivate() {
    try {
        $sql = file_get_contents(__DIR__ . "/sql/uninstall.sql");
        $statements = array_filter(array_map('trim', explode(';', $sql)));
        foreach ($statements as $statement) {
            if ($statement !== '') {
                Capsule::statement($statement);
            }
        }
        return ["status" => "success", "description" => "DNS Manager 插件已禁用并删除数据表"];
    } catch (\Exception $e) {
        return ["status" => "error", "description" => "数据库错误: " . $e->getMessage()];
    }
}

function dnsmanager_upgrade($vars) {
    $table = 'mod_dnsmanager_domains';
    try {
        $columns = [
            'ns1' => "VARCHAR(64) NOT NULL DEFAULT ''",
            'ns2' => "VARCHAR(64) NOT NULL DEFAULT ''",
            'ns_status' => "VARCHAR(20) NOT NULL DEFAULT 'pending'",
            'ns_checked_at' => "DATETIME NULL DEFAULT NULL",
            'locked' => "TINYINT(1) NOT NULL DEFAULT 0",
        ];

        foreach ($columns as $col => $ddl) {
            $exists = Capsule::select("SHOW COLUMNS FROM `{$table}` LIKE ?", [$col]);
            if (empty($exists)) {
                Capsule::statement("ALTER TABLE `{$table}` ADD `{$col}` {$ddl}");
            }
        }

        // ensure cron run table exists
        $existsCron = Capsule::select("SHOW TABLES LIKE 'mod_dnsmanager_cron_runs'");
        if (empty($existsCron)) {
            Capsule::statement("CREATE TABLE IF NOT EXISTS `mod_dnsmanager_cron_runs` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `ran_at` DATETIME NOT NULL,
                `job` VARCHAR(64) NOT NULL,
                `duration_ms` INT NOT NULL DEFAULT 0,
                `success_count` INT NOT NULL DEFAULT 0,
                `fail_count` INT NOT NULL DEFAULT 0,
                `queue_depth` INT NOT NULL DEFAULT 0,
                `message` VARCHAR(255) NOT NULL DEFAULT ''
            ) ENGINE=InnoDB");
        }

        // ensure desired records table exists
        $existsDesired = Capsule::select("SHOW TABLES LIKE 'mod_dnsmanager_desired_records'");
        if (empty($existsDesired)) {
            Capsule::statement("CREATE TABLE IF NOT EXISTS `mod_dnsmanager_desired_records` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `domain_id` INT NOT NULL,
                `zone_id` VARCHAR(64) NOT NULL,
                `type` VARCHAR(10) NOT NULL,
                `name` VARCHAR(255) NOT NULL,
                `content` TEXT NULL,
                `ttl` INT NOT NULL DEFAULT 1,
                `proxied` TINYINT(1) NOT NULL DEFAULT 0,
                `priority` INT NULL,
                `data` TEXT NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB");
        }

        return ["status" => "success", "description" => "已升级数据库结构，新增 NS/状态列、定时任务日志表、desired records 表"];
    } catch (\Exception $e) {
        return ["status" => "error", "description" => "数据库升级失败: " . $e->getMessage()];
    }
}

function dnsmanager_output($vars) {
    echo "<h2>DNS Manager 后台管理</h2>";

    // Health panel actions
    $action = isset($_REQUEST['dm_action']) ? $_REQUEST['dm_action'] : '';
    $message = '';
    if ($action === 'api_test') {
        require_once __DIR__ . '/dnsmanager_lib.php';
        $api = dnsmanager_build_api_from_config();
        $resp = $api->verifyAuth();
        $ok = isset($resp['success']) && $resp['success'];
        $message = $ok ? 'API 认证正常' : ('API 测试失败：' . (isset($resp['errors'][0]['message']) ? $resp['errors'][0]['message'] : '未知'));
    }
    if ($action === 'cron_run_pending') {
        $start = microtime(true);
        $success = 0; $fail = 0; $depth = 0; $msg = '';
        try {
            $pending = Capsule::table('mod_dnsmanager_domains')
                ->whereIn('ns_status', ['pending','unknown',''])
                ->orderBy('id', 'asc')
                ->limit(200)
                ->get();
            $depth = count($pending);
            require_once __DIR__ . '/dnsmanager_lib.php';
            foreach ($pending as $row) {
                $r = dnsmanager_refresh_ns_status($row);
                if ($r['success']) { $success++; } else { $fail++; }
            }
            $message = "已刷新 pending：成功 {$success}，失败 {$fail}";
        } catch (\Exception $e) { $msg = $e->getMessage(); $message = '执行失败：' . $msg; }
        try {
            Capsule::table('mod_dnsmanager_cron_runs')->insert([
                'ran_at' => date('Y-m-d H:i:s'),
                'job' => 'manual-pending-refresh',
                'duration_ms' => (int) round((microtime(true) - $start) * 1000),
                'success_count' => $success,
                'fail_count' => $fail,
                'queue_depth' => $depth,
                'message' => $msg,
            ]);
        } catch (\Exception $e) {}
    }

    if ($message !== '') {
        echo "<div class='alert alert-info'>{$message}</div>";
    }

    // Health panel
    echo "<h4>健康面板</h4>";
    echo "<form method='post'><input type='hidden' name='module' value='dnsmanager'>
        <button class='btn btn-sm btn-primary' name='dm_action' value='api_test'>API 自检</button>
        <button class='btn btn-sm btn-default' name='dm_action' value='cron_run_pending'>立即刷新 Pending NS</button>
    </form>";

    $runs = [];
    try {
        $runs = Capsule::table('mod_dnsmanager_cron_runs')->orderBy('id', 'desc')->limit(20)->get();
    } catch (\Exception $e) {}
    echo "<div class='table-responsive'><table class='table table-bordered'>";
    echo "<tr><th>时间</th><th>任务</th><th>耗时(ms)</th><th>成功</th><th>失败</th><th>队列深度</th><th>备注</th></tr>";
    foreach ($runs as $r) {
        echo "<tr>
            <td>{$r->ran_at}</td>
            <td>{$r->job}</td>
            <td>{$r->duration_ms}</td>
            <td>{$r->success_count}</td>
            <td>{$r->fail_count}</td>
            <td>{$r->queue_depth}</td>
            <td>{$r->message}</td>
        </tr>";
    }
    echo "</table></div>";

    echo "<h4>用户域名列表与操作</h4>";
    // Filters
    $f_ns = isset($_GET['f_ns']) ? $_GET['f_ns'] : '';
    $f_lock = isset($_GET['f_lock']) ? (int)$_GET['f_lock'] : -1;
    $f_uid = isset($_GET['f_uid']) ? (int)$_GET['f_uid'] : 0;
    echo "<form class='form-inline' method='get'>
        <input type='hidden' name='module' value='dnsmanager'>
        <select name='f_ns' class='form-control'>
            <option value=''>NS 状态</option>
            <option value='pending'" . ($f_ns==='pending'?' selected':'') . ">pending</option>
            <option value='active'" . ($f_ns==='active'?' selected':'') . ">active</option>
            <option value='unknown'" . ($f_ns==='unknown'?' selected':'') . ">unknown</option>
        </select>
        <select name='f_lock' class='form-control'>
            <option value='-1'" . ($f_lock===-1?' selected':'') . ">锁定(全部)</option>
            <option value='0'" . ($f_lock===0?' selected':'') . ">未锁定</option>
            <option value='1'" . ($f_lock===1?' selected':'') . ">已锁定</option>
        </select>
        <input type='number' class='form-control' name='f_uid' placeholder='用户ID' value='" . ($f_uid>0?$f_uid:'') . "'>
        <button class='btn btn-default'>筛选</button>
    </form>";

    $q = Capsule::table("mod_dnsmanager_domains")
        ->join("tblclients", "mod_dnsmanager_domains.userid", "=", "tblclients.id")
        ->select("mod_dnsmanager_domains.*", "tblclients.firstname", "tblclients.lastname", "tblclients.email");
    if ($f_ns !== '') { $q->where('ns_status', $f_ns); }
    if ($f_lock !== -1) { $q->where('locked', $f_lock); }
    if ($f_uid > 0) { $q->where('userid', $f_uid); }
    $domains = $q->orderBy('mod_dnsmanager_domains.id','desc')->limit(200)->get();

    // bulk actions
    echo "<form method='post' onsubmit=\"return confirm('确认执行所选批量操作？');\">";
    echo "<input type='hidden' name='module' value='dnsmanager'>";
    echo "<div class='form-inline'>
        <select name='dm_action' class='form-control'>
            <option value=''>批量操作</option>
            <option value='bulk_refresh_ns'>批量刷新 NS</option>
            <option value='bulk_lock'>批量锁定</option>
            <option value='bulk_unlock'>批量解锁</option>
            <option value='bulk_unbind'>批量解绑(不删 Zone)</option>
            <option value='bulk_delete_zone'>批量删除 Zone(危险)</option>
        </select>
        <button class='btn btn-danger btn-sm'>执行</button>
    </div>";

    echo "<div class='table-responsive'><table class='table table-bordered'>";
    echo "<tr><th><input type='checkbox' onclick=\"var c=this.checked; var boxes=document.querySelectorAll('[name=\'domids[]\']'); for(var i=0;i<boxes.length;i++){boxes[i].checked=c;}\"></th><th>用户</th><th>域名</th><th>Zone ID</th><th>NS 状态</th><th>锁定</th><th>添加时间</th></tr>";
    foreach ($domains as $d) {
        echo "<tr>
            <td><input type='checkbox' name='domids[]' value='{$d->id}'></td>
            <td>{$d->firstname} {$d->lastname} ({$d->email})</td>
            <td>{$d->domain}</td>
            <td>{$d->zone_id}</td>
            <td>{$d->ns_status}</td>
            <td>" . ($d->locked? '是':'否') . "</td>
            <td>{$d->created_at}</td>
        </tr>";
    }
    echo "</table></div>";

    // handle bulk
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['dm_action']) && isset($_POST['domids'])) {
        $act = $_POST['dm_action'];
        $ids = array_map('intval', (array)$_POST['domids']);
        $api = dnsmanager_build_api_from_config();
        $ok = 0; $fail = 0;
        foreach ($ids as $id) {
            $row = Capsule::table('mod_dnsmanager_domains')->where('id', $id)->first();
            if (!$row) { $fail++; continue; }
            if ($act === 'bulk_refresh_ns') {
                $r = dnsmanager_refresh_ns_status($row); $ok += $r['success']?1:0; $fail += $r['success']?0:1;
            } elseif ($act === 'bulk_lock') {
                Capsule::table('mod_dnsmanager_domains')->where('id', $id)->update(['locked'=>1]); $ok++;
            } elseif ($act === 'bulk_unlock') {
                Capsule::table('mod_dnsmanager_domains')->where('id', $id)->update(['locked'=>0]); $ok++;
            } elseif ($act === 'bulk_unbind') {
                Capsule::table('mod_dnsmanager_domains')->where('id', $id)->delete(); $ok++;
            } elseif ($act === 'bulk_delete_zone') {
                if (!empty($row->zone_id)) { $api->deleteZone($row->zone_id); }
                Capsule::table('mod_dnsmanager_domains')->where('id', $id)->delete(); $ok++;
            }
        }
        echo "<div class='alert alert-success'>批量操作完成：成功 {$ok}，失败 {$fail}</div>";
    }

    // conflict detection
    echo "<h4>冲突检测</h4>";
    $dupes = Capsule::table('mod_dnsmanager_domains')
        ->select('domain', Capsule::raw('COUNT(*) as cnt'))
        ->groupBy('domain')
        ->having(Capsule::raw('COUNT(*)'), '>', 1)
        ->get();
    if (count($dupes) > 0) {
        echo "<div class='alert alert-warning'>发现同名域名多记录：" . count($dupes) . " 个。建议手动检查并保留一条。</div>";
    } else {
        echo "<div class='alert alert-info'>未发现同名域名冲突。</div>";
    }
}
