<div class="container{if $standalone} container-fluid{/if} mt-0"{if $standalone} style="padding:0"{/if}>

    {literal}
    <style>
    /* Bootstrap 3 compatibility polyfills */
    .d-flex{display:flex;}
    .justify-content-between{justify-content:space-between;}
    .align-items-center{align-items:center;}
    .mb-0{margin-bottom:0!important;}
    .mb-2{margin-bottom:.5rem!important;}
    .mb-3{margin-bottom:1rem!important;}
    .mt-3{margin-top:1rem!important;}
    .mr-2{margin-right:.5rem!important;}
    .mr-3{margin-right:1rem!important;}
    .shadow-sm{box-shadow:0 .125rem .25rem rgba(0,0,0,.075)!important;}
    /* use bootstrap defaults; avoid forcing text colors */
    .btn-light{background-color:#f8f9fa;border:1px solid #ced4da;color:#212529;}
    .alert-secondary{background-color:#f8f9fa;border-color:#ddd;color:#495057;}
    .d-none{display:none!important;}
    .table-responsive{overflow-x:auto;}

    /* Cards */
    .card{background:#fff;border:1px solid #e5e5e5;border-radius:.25rem;}
    .card-header{padding:.5rem .75rem;border-bottom:1px solid #e5e5e5;}
    .card-body{padding:.75rem;}

    /* Keep badges minimal by using Bootstrap context or light border */
    .badge{display:inline-block;padding:.25em .5em;font-size:85%;font-weight:600;line-height:1;border-radius:.25rem;}
    .badge-light{background-color:#f8f9fa;color:#212529;border:1px solid #dee2e6;}

    /* Table head light */
    .thead-light th{background-color:#f8f9fa;border-color:#dee2e6;}

    /* Modal center fallback */
    .modal-dialog-centered{display:flex;align-items:center;min-height:calc(100% - 1rem);} 
    .modal-dialog-centered .modal-content{margin:auto;}

    /* Hero banner simplified */
    .hero-banner{background:#f8f9fa;border-radius:.5rem;color:#212529;padding:12px 14px;margin-bottom:12px;position:relative;overflow:hidden;}
    .hero-banner .title{font-size:16px;font-weight:600;margin:0;}
    .hero-banner .subtitle{opacity:.9;margin:2px 0 0 0;}

    /* Chips minimal */
    .chip{display:inline-flex;align-items:center;padding:.15rem .4rem;border-radius:999px;background:#f1f3f5;border:1px solid #dee2e6;color:#333;font-weight:600;}
    .btn-chip{margin-left:6px;padding:2px 6px;font-size:12px;border:1px solid #ced4da;background:#fff;color:#333;border-radius:999px;}

    /* Sticky toolbar */
    .sticky-toolbar{position:sticky;top:10px;z-index:2;background:#fff;padding:12px;border:1px solid #eee;border-radius:.25rem;margin-top:14px;margin-bottom:14px;}
    .sticky-toolbar .form-control{font-size:13px}
    .sticky-toolbar .form-group{margin-right:10px;margin-bottom:8px}

    /* Table hover */
    .table-hover tbody tr:hover{background-color:#f9fbff;}
    .table td,.table th{vertical-align:middle;}
    /* lightweight toast */
    .dm-toast{position:fixed;top:12px;right:12px;z-index:9999}
    .dm-toast .item{background:#343a40;color:#fff;padding:8px 12px;border-radius:4px;margin-top:6px;box-shadow:0 2px 6px rgba(0,0,0,.15)}
    .dm-toast .item.success{background:#28a745}
    .dm-toast .item.error{background:#dc3545}
    /* Floating label inputs */
    .fl-group{position:relative;display:inline-block;vertical-align:top}
    .fl-group .form-control{padding-top:18px}
    .fl-group .form-control::placeholder{color:#adb5bd;opacity:1}
    .fl-label{position:absolute;left:10px;top:10px;color:#6c757d;font-size:11px;opacity:.85;pointer-events:none;transition:all .15s ease}
    .fl-group.is-focused .fl-label,.fl-group.has-value .fl-label{top:-9px;left:8px;background:#fff;padding:0 4px;font-size:10px;opacity:.95}
    /* Records table spacing & content width */
    .records-table td,.records-table th{padding:12px 14px}
    .records-table .content-col{word-break:break-all}
    @media (min-width:992px){.records-table .content-col{width:50%}}
    /* Wider content input in inline add */
    .inline-content-wrap .form-control{width:360px}
    @media (min-width:1200px){.inline-content-wrap .form-control{width:460px}}
    @media (max-width:768px){.inline-content-wrap .form-control{width:100%}}
    /* Domain card accents */
    .domain-card{border-left:3px solid #e5e5e5}
    .domain-card.status-active{border-left-color:#28a745}
    .domain-card.status-pending{border-left-color:#ffc107}
    .domain-card.status-unknown{border-left-color:#dee2e6}
    .section-title{display:flex;align-items:center;gap:6px}
    .section-title .fa{color:#6c757d}
    /* Inline form responsiveness */
    .form-inline .form-group{margin-bottom:.75rem}
    @media (max-width:768px){.form-inline .form-group{display:block;width:100%}}
    /* Toggle switch (Cloudflare-like) */
    .dm-switch{display:inline-block;position:relative;width:52px;height:28px;border:1px solid #dee2e6;border-radius:16px;background:#e9ecef;cursor:pointer;vertical-align:middle;outline:none;transition:background .2s,border-color .2s,box-shadow .2s}
    .dm-switch .knob{position:absolute;top:2px;left:2px;width:24px;height:24px;border-radius:50%;background:#fff;box-shadow:0 1px 3px rgba(0,0,0,.2);transition:left .2s}
    .dm-switch.on{background:#f5a623;border-color:#f5a623}
    .dm-switch.on .knob{left:26px}
    </style>
    {/literal}

    {if $standalone}
    <div class="navbar" style="background:#fff;border-bottom:1px solid #e9ecef;padding:10px 14px;{if $standalone}margin-bottom:0{/if}">
      <div class="section-title">
        <i class="fa fa-shield"></i>
        <strong>DNS 管理</strong>
      </div>
      <div class="pull-right">
        <a href="index.php?m=dnsmanager" class="btn btn-default btn-sm">域名列表</a>
        {if $view != 'domains'}<a href="index.php?m=dnsmanager&action=manage&domain_id={$selectedDomain.id}&standalone=1" class="btn btn-default btn-sm">当前域名</a>{/if}
      </div>
      <div style="clear:both"></div>
      <ul class="nav nav-tabs" style="margin-top:10px;">
        <li class="{if $view=='domains'}active{/if}"><a href="index.php?m=dnsmanager&standalone=1">我的域名</a></li>
        {if $view!='domains'}
        <li class="active"><a href="index.php?m=dnsmanager&action=manage&domain_id={$selectedDomain.id}&standalone=1">解析记录</a></li>
        {/if}
      </ul>
    </div>
    {/if}

    {if $message}
        <div class="alert alert-success alert-dismissible" role="alert">
            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            {$message}
        </div>
    {/if}
    {if $error}
        <div class="alert alert-danger alert-dismissible" role="alert">
            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            {$error}
        </div>
    {/if}

    <div class="hero-banner">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <p class="title">🌐 Cloudflare DNS Manager</p>
                <p class="subtitle">快速管理域名与解析记录，支持状态自动检测与模板快捷添加</p>
            </div>
            <div class="hidden-xs">
                <span class="badge badge-light" style="color:#212529;">更安全</span>
                <span class="badge badge-light" style="color:#212529;">更高效</span>
                <span class="badge badge-light" style="color:#212529;">更易用</span>
            </div>
        </div>
    </div>

    

    <div class="card shadow-sm mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Cloudflare DNS 管理</h5>
            {if $view == 'domains'}
                <div>
                    <button class="btn btn-default btn-sm" data-toggle="modal" data-target="#addDomainModal"><i class="fa fa-plus"></i> 添加域名</button>
                    
                </div>
            {else}
                <div>
                    <a href="index.php?m=dnsmanager" class="btn btn-light btn-sm">返回域名列表</a>
                    <div class="btn-group">
                        <button class="btn btn-default btn-sm" data-toggle="modal" data-target="#addDnsModal" {if $locked}disabled title="该域名已锁定，前台只读"{/if}><i class="fa fa-plus"></i> 添加记录</button>
                        <button class="btn btn-light btn-sm dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">快速模板</button>
                        <ul class="dropdown-menu dropdown-menu-right">
                            <li><a href="#" class="dropdown-item quick-template" data-type="A" data-name="@" data-content="" data-ttl="1" data-proxied="0">A @</a></li>
                            <li><a href="#" class="dropdown-item quick-template" data-type="CNAME" data-name="www" data-content="@" data-ttl="1" data-proxied="0">CNAME www → @</a></li>
                            <li><a href="#" class="dropdown-item quick-template" data-type="MX" data-name="@" data-content="mail.example.com" data-priority="10" data-ttl="1">MX @</a></li>
                            <li><a href="#" class="dropdown-item quick-template" data-type="TXT" data-name="@" data-content="v=spf1 ~all" data-ttl="1">TXT SPF</a></li>
                        </ul>
                    </div>
                </div>
            {/if}
        </div>

        <div class="card-body">
            {if $view == 'domains'}
                <form class="form-inline mb-3" method="get" action="index.php">
                    <input type="hidden" name="m" value="dnsmanager">
                    <div class="form-group mr-2">
                        <input type="text" class="form-control" name="search" placeholder="搜索域名" value="{$search}">
                    </div>
                    <div class="form-group mr-2">
                        <select class="form-control" name="sort">
                            <option value="created_at" {if $sort=='created_at'}selected{/if}>添加时间</option>
                            <option value="domain" {if $sort=='domain'}selected{/if}>域名</option>
                            <option value="ns_status" {if $sort=='ns_status'}selected{/if}>状态</option>
                        </select>
                    </div>
                    <div class="form-group mr-2">
                        <select class="form-control" name="order">
                            <option value="desc" {if $order=='desc'}selected{/if}>降序</option>
                            <option value="asc" {if $order=='asc'}selected{/if}>升序</option>
                        </select>
                    </div>
                    <div class="form-group mr-2">
                        <select class="form-control" name="dlimit">
                            <option value="10" {if $dlimit==10}selected{/if}>每页 10</option>
                            <option value="20" {if $dlimit==20}selected{/if}>每页 20</option>
                            <option value="50" {if $dlimit==50}selected{/if}>每页 50</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">筛选</button>
                </form>

                {if $domains|@count > 0}
<div class="row">
{foreach from=$domains item=domain}
<div class="col-sm-6 col-md-4">
	<div class="card shadow-sm mb-3 domain-card {if $domain.ns_status == 'active'}status-active{elseif $domain.ns_status == 'pending'}status-pending{else}status-unknown{/if}">
		<div class="card-header d-flex justify-content-between align-items-center">
			<strong>{$domain.domain}</strong>
			<span>
				{if $domain.ns_status == 'active'}
					<span class="badge badge-success">已生效</span>
				{elseif $domain.ns_status == 'pending'}
					<span class="badge badge-warning">未生效</span>
				{else}
					<span class="badge badge-secondary">{$domain.ns_status}</span>
				{/if}
			</span>
		</div>
		<div class="card-body">
			<div class="mb-2"><small class="text-muted">NS1</small> <span class="badge badge-light">{$domain.ns1}</span> <button type="button" class="btn btn-light btn-xs copy-btn" data-copy="{$domain.ns1}">复制</button></div>
			<div class="mb-2"><small class="text-muted">NS2</small> <span class="badge badge-light">{$domain.ns2}</span> <button type="button" class="btn btn-light btn-xs copy-btn" data-copy="{$domain.ns2}">复制</button></div>
			<div class="text-muted"><small>添加于 {$domain.created_at}</small></div>
		</div>
		<div class="card-body d-flex justify-content-between">
			<button type="button" class="btn btn-sm btn-outline-danger" data-toggle="modal" data-target="#deleteDomainModal{$domain.id}" {if $domain.locked}disabled title="该域名已锁定，前台只读"{/if}>删除/解绑</button>
			<a class="btn btn-sm btn-outline-primary" href="index.php?m=dnsmanager&action=manage&domain_id={$domain.id}">管理</a>
		</div>
	</div>
</div>
<div class="modal fade" id="deleteDomainModal{$domain.id}" tabindex="-1" role="dialog" aria-hidden="true">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title">删除/解绑域名</h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
			</div>
			<form method="post" action="index.php?action=deletedomain&m=dnsmanager" onsubmit="return confirm('确认继续？该操作不可撤销');">
				<div class="modal-body">
					<input type="hidden" name="token" value="{$token}">
					<input type="hidden" name="domain_id" value="{$domain.id}">
					<div class="alert alert-warning">请选择删除方式：</div>
					<div class="radio">
						<label><input type="radio" name="mode" value="unbind" checked> 仅从本系统解绑（不删除 Cloudflare Zone）</label>
					</div>
					<div class="radio">
						<label><input type="radio" name="mode" value="delete_zone"> 同时删除 Cloudflare Zone（危险操作）</label>
					</div>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-default" data-dismiss="modal">取消</button>
					<button type="submit" class="btn btn-danger js-loading-btn" data-loading-text="处理中...">确认</button>
				</div>
			</form>
		</div>
	</div>
</div>
{/foreach}
</div>
{else}
<div class="alert alert-info">暂无域名，请先添加。<button class="btn btn-sm btn-primary ml-2" data-toggle="modal" data-target="#addDomainModal">立即添加</button></div>
{/if}

                <div class="alert alert-secondary mt-3">
                    <strong>提示：</strong> 添加域名后，我们会返回 Cloudflare 分配的 NS。请到域名注册商处将域名的 NS 修改为这里显示的 NS。通常需要 10-30 分钟生效，有时可能更久。
                </div>
            {else}
                <div class="mb-3">
                    <h5 class="mb-2">管理域名：<span class="text-primary">{$selectedDomain.domain}</span></h5>
                    <div>
                        <span class="mr-3">NS1：<span class="badge badge-light">{$selectedDomain.ns1}</span> <button type="button" class="btn btn-light btn-xs copy-btn" data-copy="{$selectedDomain.ns1}">复制</button></span>
                        <span class="mr-3">NS2：<span class="badge badge-light">{$selectedDomain.ns2}</span> <button type="button" class="btn btn-light btn-xs copy-btn" data-copy="{$selectedDomain.ns2}">复制</button></span>
                        <span class="mr-3">状态：
                            {if $selectedDomain.ns_status == 'active'}
                                <span class="badge badge-success">已生效</span>
                            {elseif $selectedDomain.ns_status == 'pending'}
                                <span id="ns-status-badge" class="badge badge-warning">未生效</span>
                            {else}
                                <span id="ns-status-badge" class="badge badge-secondary">{$selectedDomain.ns_status}</span>
                            {/if}
                        </span>
                        <a class="btn btn-sm btn-outline-secondary" href="index.php?m=dnsmanager&action=refreshns&domain_id={$selectedDomain.id}">刷新 NS 状态</a>
                    </div>
                    {if $selectedDomain.ns_status != 'active'}
                    <div class="mt-3">
                        <div class="progress" style="height:10px;">
                          <div class="progress-bar progress-bar-striped active" role="progressbar" aria-valuenow="{$nsProgress|default:5}" aria-valuemin="0" aria-valuemax="100" style="width: {$nsProgress|default:5}%">
                          </div>
                        </div>
                        <div class="text-muted mt-1">
                          预计生效时间：{if $nsEtaText}{$nsEtaText}{else}稍后{/if}
                          {if $nsCheckedAt}（上次检查：{$nsCheckedAt}）{/if}
                        </div>
                        <details class="mt-1">
                          <summary>常见问题</summary>
                          <ul>
                            <li>请到域名注册商修改 NS 为上方 Cloudflare 分配的 NS。</li>
                            <li>DNS 传播通常 10-30 分钟，少数情况需要数小时。</li>
                            <li>若注册商缓存较久，可尝试先切回默认 NS 再切回 Cloudflare。</li>
                          </ul>
                        </details>
                    </div>
                    {/if}
                </div>

                <!-- 记录筛选（移动到添加记录上方） -->
                <form class="form-inline mb-3" method="get" action="index.php">
                    <input type="hidden" name="m" value="dnsmanager">
                    <input type="hidden" name="action" value="manage">
                    <input type="hidden" name="domain_id" value="{$selectedDomain.id}">
                    <div class="form-group mr-2">
                        <input type="text" class="form-control" name="rsearch" placeholder="筛选记录（按名称）" value="{$rsearch}">
                    </div>
                    <div class="form-group mr-2">
                        <select class="form-control" name="rtype">
                            <option value="">全部类型 ({$recordTotal|default:0})</option>
                            <option value="A">A ({$recordTypeCounts.A|default:0})</option>
                            <option value="AAAA">AAAA ({$recordTypeCounts.AAAA|default:0})</option>
                            <option value="CNAME">CNAME ({$recordTypeCounts.CNAME|default:0})</option>
                            <option value="MX">MX ({$recordTypeCounts.MX|default:0})</option>
                            <option value="TXT">TXT ({$recordTypeCounts.TXT|default:0})</option>
                            <option value="SRV">SRV ({$recordTypeCounts.SRV|default:0})</option>
                            <option value="NS">NS ({$recordTypeCounts.NS|default:0})</option>
                        </select>
                    </div>
                    <div class="form-group mr-2">
                        <select class="form-control" name="rlimit">
                            <option value="25" {if $rlimit==25}selected{/if}>每页 25</option>
                            <option value="50" {if $rlimit==50}selected{/if}>每页 50</option>
                            <option value="100" {if $rlimit==100}selected{/if}>每页 100</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">筛选</button>
                </form>

                <!-- 横条式内联添加记录（Cloudflare 风格） -->
                <div class="sticky-toolbar mt-3">
                  <form class="form-inline" method="post" action="index.php?action=adddns&m=dnsmanager&domain_id={$selectedDomain.id}" id="inline-add-form">
                    <input type="hidden" name="token" value="{$token}">
                    <div class="form-group mr-2 fl-group">
                      <label class="fl-label" for="inline-type">类型</label>
                      <select class="form-control" name="type" id="inline-type">
                        <option value="A">A</option>
                        <option value="AAAA">AAAA</option>
                        <option value="CNAME">CNAME</option>
                        <option value="MX">MX</option>
                        <option value="TXT">TXT</option>
                        <option value="SRV">SRV</option>
                        <option value="NS">NS</option>
                        <option value="CAA">CAA</option>
                        <option value="PTR">PTR</option>
                      </select>
                    </div>
                    <div class="form-group mr-2 fl-group">
                      <label class="fl-label">名称（如 @、www）</label>
                      <input type="text" class="form-control" name="name" placeholder="www">
                    </div>
                    <div class="form-group mr-2 inline-content-wrap fl-group">
                      <label class="fl-label">内容 / 目标（如 1.2.3.4 或 example.com）</label>
                      <input type="text" class="form-control" name="content" placeholder="1.2.3.4">
                    </div>
                    <div class="form-group mr-2 inline-mx-wrap d-none fl-group">
                      <label class="fl-label">MX 优先级（数值越小优先级越高）</label>
                      <input type="number" class="form-control" name="priority" value="10" placeholder="10">
                    </div>
                    <div class="form-group mr-2 inline-srv-wrap d-none">
                      <div class="mr-1 fl-group">
                        <label class="fl-label">Service（_service）</label>
                        <input type="text" class="form-control" name="srv_service" placeholder="_sip">
                      </div>
                      <div class="mr-1 fl-group">
                        <label class="fl-label">Proto（_tcp/_udp）</label>
                        <input type="text" class="form-control" name="srv_proto" placeholder="_tcp">
                      </div>
                      <div class="mr-1 fl-group">
                        <label class="fl-label">Target</label>
                        <input type="text" class="form-control" name="srv_target" placeholder="target.example.com">
                      </div>
                      <div class="mr-1 fl-group">
                        <label class="fl-label">Priority</label>
                        <input type="number" class="form-control" name="srv_priority" value="0" placeholder="0">
                      </div>
                      <div class="mr-1 fl-group">
                        <label class="fl-label">Weight</label>
                        <input type="number" class="form-control" name="srv_weight" value="0" placeholder="0">
                      </div>
                      <div class="fl-group">
                        <label class="fl-label">Port</label>
                        <input type="number" class="form-control" name="srv_port" value="0" placeholder="5060">
                      </div>
                    </div>
                    <div class="form-group mr-2 inline-caa-wrap d-none">
                      <div class="mr-1 fl-group">
                        <label class="fl-label">Flags</label>
                        <input type="number" class="form-control" name="caa_flags" value="0" placeholder="0">
                      </div>
                      <div class="mr-1 fl-group">
                        <label class="fl-label">Tag</label>
                        <select class="form-control" name="caa_tag"><option value="issue">issue</option><option value="issuewild">issuewild</option><option value="iodef">iodef</option></select>
                      </div>
                      <div class="fl-group">
                        <label class="fl-label">Value（授权 CA 或 URL）</label>
                        <input type="text" class="form-control" name="caa_value" placeholder="digicert.com">
                      </div>
                    </div>
                    <div class="form-group mr-2 fl-group">
                      <label class="fl-label">TTL</label>
                      <select class="form-control" name="ttl">
                        <option value="1" selected>自动</option>
                        <option value="60">1 分钟</option>
                        <option value="120">2 分钟</option>
                        <option value="300">5 分钟</option>
                        <option value="600">10 分钟</option>
                        <option value="900">15 分钟</option>
                        <option value="1800">30 分钟</option>
                        <option value="3600">1 小时</option>
                      </select>
                    </div>
                    <div class="form-group mr-2 inline-proxy-wrap">
                      <input type="hidden" name="proxied" value="0">
                      <button type="button" class="btn btn-warning btn-sm" id="inline-proxy-toggle" title="CDN 代理 (橙色=开, 灰色=关)">
                        <i class="fa fa-cloud" style="color:#fff"></i>
                      </button>
                    </div>
                    <button type="submit" class="btn btn-success btn-sm js-loading-btn" data-loading-text="添加中..." {if $locked}disabled title="该域名已锁定，前台只读"{/if}>添加</button>
                  </form>
                  <div class="text-muted" style="margin-top:6px;">
                    <small id="inline-help">
                      类型说明：A/AAAA(IP)、CNAME(别名, 不能指向IP)、MX(邮件, 需优先级)、TXT(文本)、NS(子域委派)、SRV(服务定位)、CAA(证书颁发授权)、PTR(反向记录)。
                      A/AAAA/CNAME 可开启 CDN 代理（橙色小云）。
                    </small>
                  </div>
                </div>

                <form class="form-inline mb-3" method="get" action="index.php">
                    <input type="hidden" name="m" value="dnsmanager">
                    <input type="hidden" name="action" value="manage">
                    <input type="hidden" name="domain_id" value="{$selectedDomain.id}">
                    <div class="form-group mr-2">
                        <input type="text" class="form-control" name="rsearch" placeholder="筛选记录（按名称）" value="{$rsearch}">
                    </div>
                    <div class="form-group mr-2">
                        <select class="form-control" name="rtype">
                            <option value="">全部类型 ({$recordTotal|default:0})</option>
                            <option value="A">A ({$recordTypeCounts.A|default:0})</option>
                            <option value="AAAA">AAAA ({$recordTypeCounts.AAAA|default:0})</option>
                            <option value="CNAME">CNAME ({$recordTypeCounts.CNAME|default:0})</option>
                            <option value="MX">MX ({$recordTypeCounts.MX|default:0})</option>
                            <option value="TXT">TXT ({$recordTypeCounts.TXT|default:0})</option>
                            <option value="SRV">SRV ({$recordTypeCounts.SRV|default:0})</option>
                            <option value="NS">NS ({$recordTypeCounts.NS|default:0})</option>
                        </select>
                    </div>
                    <div class="form-group mr-2">
                        <select class="form-control" name="rlimit">
                            <option value="25" {if $rlimit==25}selected{/if}>每页 25</option>
                            <option value="50" {if $rlimit==50}selected{/if}>每页 50</option>
                            <option value="100" {if $rlimit==100}selected{/if}>每页 100</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">筛选</button>
                </form>
                {if $dnsRecords|@count > 0}
                    <div class="table-responsive">
                    <table class="table table-striped table-hover records-table">
                        <thead class="thead-light">
                            <tr>
                                <th>类型</th>
                                <th>名称</th>
                                <th class="content-col">内容/数据</th>
                                <th>TTL</th>
                                <th>代理</th>
                                <th class="text-right">操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            {foreach from=$dnsRecords item=record}
                            <tr>
                                <td><span class="badge badge-info">{$record.type}</span></td>
                                <td>{$record.name}</td>
                                <td>
                                    {if $record.type == 'SRV'}
                                        {assign var=dat value=$record.data}
                                        服务：{$dat.service} 协议：{$dat.proto} 目标：{$dat.target} 端口：{$dat.port} 优先级：{$dat.priority} 权重：{$dat.weight}
                                    {else}
                                        {$record.content}
                                    {/if}
                                </td>
                                <td>{if $record.ttl == 1}自动{else}{$record.ttl} 秒{/if}</td>
                                <td>
                                    {if $record.type == 'A' || $record.type == 'AAAA' || $record.type == 'CNAME'}
                                        <button type="button" class="dm-switch js-toggle-cdn {if $record.proxied}on{/if}" data-id="{$record.id}" data-enabled="{if $record.proxied}1{else}0{/if}" title="点击切换 CDN">
                                          <span class="knob"></span>
                                        </button>
                                    {else}
                                        <span class="badge badge-light">不支持</span>
                                    {/if}
                                </td>
                                <td class="text-right">
                                    
                                    <button class="btn btn-sm btn-outline-success copy-record-btn"
                                        data-type="{$record.type}"
                                        data-name="{$record.name}"
                                        data-content="{$record.content}"
                                        data-ttl="{$record.ttl}"
                                        data-proxied="{if ($record.type == 'A' || $record.type == 'AAAA' || $record.type == 'CNAME') && $record.proxied}1{else}0{/if}"
                                        data-priority="{$record.priority|default:0}"
                                        {if $record.type == 'SRV'}
                                            {assign var=dat value=$record.data}
                                            data-srv-service="{$dat.service}"
                                            data-srv-proto="{$dat.proto}"
                                            data-srv-target="{$dat.target}"
                                            data-srv-priority="{$dat.priority}"
                                            data-srv-weight="{$dat.weight}"
                                            data-srv-port="{$dat.port}"
                                        {/if}
                                    >复制</button>
                                    <form method="post" action="index.php?action=deletedns&m=dnsmanager&domain_id={$selectedDomain.id}" style="display:inline" onsubmit="return confirm('确定删除该记录吗？');">
                                        <input type="hidden" name="token" value="{$token}">
                                        <input type="hidden" name="id" value="{$record.id}">
                                        <button type="submit" class="btn btn-sm btn-outline-danger js-loading-btn" data-loading-text="删除中..." {if $locked}disabled title="该域名已锁定，前台只读"{/if}>删除</button>
                                    </form>
                                </td>
                            </tr>

                            <!-- 编辑模态框 -->
                            <div class="modal fade" id="editDnsModal{$record.id}" tabindex="-1" role="dialog" aria-hidden="true">
                              <div class="modal-dialog modal-dialog-centered" role="document">
                                <div class="modal-content">
                                  <div class="modal-header">
                                    <h5 class="modal-title">编辑 DNS 记录</h5>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="关闭">
                                      <span aria-hidden="true">&times;</span>
                                    </button>
                                  </div>
                                  <form method="post" action="index.php?action=editdns&m=dnsmanager&domain_id={$selectedDomain.id}">
                                    <input type="hidden" name="token" value="{$token}">
                                    <div class="modal-body">
                                        <input type="hidden" name="id" value="{$record.id}">
                                        <div class="form-group">
                                            <label>类型</label>
                                            <input type="text" class="form-control" name="type" value="{$record.type}" readonly>
                                        </div>
                                        <div class="form-group">
                                            <label>名称</label>
                                            <input type="text" class="form-control" name="name" value="{$record.name}">
                                        </div>
                                        {if $record.type == 'SRV'}
                                            {assign var=dat value=$record.data}
                                            <div class="form-row">
                                                <div class="form-group col-md-6">
                                                    <label>Service</label>
                                                    <input type="text" class="form-control" name="srv_service" value="{$dat.service}">
                                                </div>
                                                <div class="form-group col-md-6">
                                                    <label>Proto</label>
                                                    <input type="text" class="form-control" name="srv_proto" value="{$dat.proto}">
                                                </div>
                                            </div>
                                            <div class="form-row">
                                                <div class="form-group col-md-6">
                                                    <label>Target</label>
                                                    <input type="text" class="form-control" name="srv_target" value="{$dat.target}">
                                                </div>
                                                <div class="form-group col-md-2">
                                                    <label>Priority</label>
                                                    <input type="number" class="form-control" name="srv_priority" value="{$dat.priority}">
                                                </div>
                                                <div class="form-group col-md-2">
                                                    <label>Weight</label>
                                                    <input type="number" class="form-control" name="srv_weight" value="{$dat.weight}">
                                                </div>
                                                <div class="form-group col-md-2">
                                                    <label>Port</label>
                                                    <input type="number" class="form-control" name="srv_port" value="{$dat.port}">
                                                </div>
                                            </div>
                                        {else}
                                            <div class="form-group">
                                                <label>内容</label>
                                                <input type="text" class="form-control" name="content" value="{$record.content}">
                                            </div>
                                        {/if}
                                        {if $record.type == 'MX'}
                                            <div class="form-group">
                                                <label>优先级（Priority）</label>
                                                <input type="number" class="form-control" name="priority" value="{$record.priority}">
                                            </div>
                                        {/if}
                                        <div class="form-group">
                                            <label>TTL</label>
                                            <input type="number" class="form-control" name="ttl" value="{$record.ttl}">
                                            <small class="form-text text-muted">设置为 1 表示自动</small>
                                        </div>
                                        {if $record.type == 'A' || $record.type == 'AAAA' || $record.type == 'CNAME'}
                                            <div class="form-group form-check">
                                                <input type="checkbox" class="form-check-input" name="proxied" {if $record.proxied}checked{/if}>
                                                <label class="form-check-label">启用代理 (橙色小云)</label>
                                            </div>
                                        {/if}
                                    </div>
                                    <div class="modal-footer">
                                      <button type="button" class="btn btn-secondary" data-dismiss="modal">取消</button>
                                      <button type="submit" class="btn btn-primary js-loading-btn" data-loading-text="保存中...">保存修改</button>
                                    </div>
                                  </form>
                                </div>
                              </div>
                            </div>
                            {/foreach}
                        </tbody>
                    </table>
                    </div>
                    <div class="d-flex justify-content-between mt-3">
                        <div>
                            <a class="btn btn-default {if $rpage<=1}disabled{/if}" href="index.php?m=dnsmanager&action=manage&domain_id={$selectedDomain.id}&rsearch={$rsearch}&rlimit={$rlimit}&rpage={if $rpage>1}{$rpage-1}{else}1{/if}">上一页</a>
                        </div>
                        <div>
                            <a class="btn btn-default {if !$recordsHasNext}disabled{/if}" href="index.php?m=dnsmanager&action=manage&domain_id={$selectedDomain.id}&rsearch={$rsearch}&rlimit={$rlimit}&rpage={$rpage+1}">下一页</a>
                        </div>
                    </div>
                {else}
                    <div class="alert alert-info">暂无 DNS 记录 <button class="btn btn-sm btn-success ml-2" data-toggle="modal" data-target="#addDnsModal" {if $locked}disabled title="该域名已锁定，前台只读"{/if}>立即添加</button></div>
                {/if}
            {/if}
        </div>
    </div>

</div>

<!-- 添加域名模态框 -->
<div class="modal fade" id="addDomainModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">添加域名</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="关闭">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <form method="post" action="index.php?action=adddomain&m=dnsmanager">
        <input type="hidden" name="token" value="{$token}">
        <div class="modal-body">
            <div class="form-group">
                <label>域名</label>
                <input type="text" class="form-control" name="domain" placeholder="例如：example.com">
            </div>
            <div class="alert alert-secondary">
                添加后会自动在 Cloudflare 创建 Zone，并返回分配的 NS。请在域名注册商处把 NS 修改为这里显示的 NS。
            </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">取消</button>
          <button type="submit" class="btn btn-success js-loading-btn" data-loading-text="添加中...">添加</button>
        </div>
      </form>
    </div>
  </div>
  </div>

<!-- 添加 DNS 记录模态框（管理页） -->
<div class="modal fade" id="addDnsModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">添加 DNS 记录</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="关闭">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <form method="post" action="index.php?action=adddns&m=dnsmanager&domain_id={$selectedDomain.id}">
        <input type="hidden" name="token" value="{$token}">
        <div class="modal-body">
            <div class="form-group">
                <label>类型</label>
                <select class="form-control" name="type" id="add-type">
                    <option value="A">A</option>
                    <option value="AAAA">AAAA</option>
                    <option value="CNAME">CNAME</option>
                    <option value="MX">MX</option>
                    <option value="TXT">TXT</option>
                    <option value="SRV">SRV</option>
                    <option value="NS">NS</option>
                    <option value="CAA">CAA</option>
                    <option value="PTR">PTR</option>
                </select>
            </div>
            <div class="form-group">
                <label>名称</label>
                <input type="text" class="form-control" name="name" id="add-name">
                <small class="form-text text-muted" id="hint-name"></small>
            </div>
            <div class="form-group add-content-group">
                <label>内容</label>
                <input type="text" class="form-control" name="content" id="add-content">
                <small class="form-text text-muted" id="hint-content"></small>
            </div>
            <div class="form-row add-mx-group d-none">
                <div class="form-group col-md-6">
                    <label>优先级（Priority）</label>
                    <input type="number" class="form-control" name="priority" value="0">
                </div>
            </div>
            <div class="add-srv-group d-none">
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label>Service</label>
                        <input type="text" class="form-control" name="srv_service" placeholder="_service">
                    </div>
                    <div class="form-group col-md-6">
                        <label>Proto</label>
                        <input type="text" class="form-control" name="srv_proto" placeholder="_tcp 或 _udp">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label>Target</label>
                        <input type="text" class="form-control" name="srv_target" placeholder="目标主机名">
                    </div>
                    <div class="form-group col-md-2">
                        <label>Priority</label>
                        <input type="number" class="form-control" name="srv_priority" value="0">
                    </div>
                    <div class="form-group col-md-2">
                        <label>Weight</label>
                        <input type="number" class="form-control" name="srv_weight" value="0">
                    </div>
                    <div class="form-group col-md-2">
                        <label>Port</label>
                        <input type="number" class="form-control" name="srv_port" value="0">
                    </div>
                </div>
            </div>
            <div class="add-caa-group d-none">
                <div class="form-row">
                    <div class="form-group col-md-2">
                        <label>Flags</label>
                        <input type="number" class="form-control" name="caa_flags" value="0">
                    </div>
                    <div class="form-group col-md-4">
                        <label>Tag</label>
                        <select class="form-control" name="caa_tag">
                            <option value="issue">issue</option>
                            <option value="issuewild">issuewild</option>
                            <option value="iodef">iodef</option>
                        </select>
                    </div>
                    <div class="form-group col-md-6">
                        <label>Value</label>
                        <input type="text" class="form-control" name="caa_value" placeholder="例如 digicert.com">
                    </div>
                </div>
            </div>
            <div class="form-group">
                <label>TTL</label>
                <input type="number" class="form-control" name="ttl" value="1">
                <small class="form-text text-muted">设置为 1 表示自动</small>
            </div>
            <div class="form-group form-check add-proxy-group">
                <input type="checkbox" class="form-check-input" name="proxied">
                <label class="form-check-label">启用代理 (橙色小云，仅 A/AAAA/CNAME)</label>
            </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">取消</button>
          <button type="submit" class="btn btn-success js-loading-btn" data-loading-text="添加中..." id="add-submit">添加</button>
        </div>
      </form>
    </div>
  </div>
</div>

 

<script>
(function(){
  function toggleByType(selectEl, prefix){
    var type = selectEl.value.toUpperCase();
    var addContent = document.querySelector('.' + prefix + 'content-group');
    var addMX = document.querySelector('.' + prefix + 'mx-group');
    var addSRV = document.querySelector('.' + prefix + 'srv-group');
    var addCAA = document.querySelector('.' + prefix + 'caa-group');
    var addProxy = document.querySelector('.' + prefix + 'proxy-group');
    if(addContent){ addContent.classList.remove('d-none'); }
    if(addMX){ addMX.classList.add('d-none'); }
    if(addSRV){ addSRV.classList.add('d-none'); }
    if(addCAA){ addCAA.classList.add('d-none'); }
    if(addProxy){ addProxy.classList.remove('d-none'); }
    if(type === 'MX'){
      if(addMX){ addMX.classList.remove('d-none'); }
    }
    if(type === 'SRV'){
      if(addSRV){ addSRV.classList.remove('d-none'); }
      if(addContent){ addContent.classList.add('d-none'); }
    }
    if(type === 'CAA'){
      if(addCAA){ addCAA.classList.remove('d-none'); }
      if(addContent){ addContent.classList.add('d-none'); }
    }
    if(['A','AAAA','CNAME'].indexOf(type) === -1){
      if(addProxy){ addProxy.classList.add('d-none'); }
    }
  }
  var addType = document.getElementById('add-type');
  if(addType){
    addType.addEventListener('change', function(){ toggleByType(addType, 'add-'); });
    toggleByType(addType, 'add-');
  }
  // 复制记录到添加模态框
  document.addEventListener('click', function(e){
    if(e.target && e.target.classList.contains('copy-record-btn')){
      var btn = e.target;
      var type = btn.getAttribute('data-type') || 'A';
      var name = btn.getAttribute('data-name') || '';
      var content = btn.getAttribute('data-content') || '';
      var ttl = btn.getAttribute('data-ttl') || '1';
      var proxied = btn.getAttribute('data-proxied') === '1';
      var priority = btn.getAttribute('data-priority') || '0';
      var srvService = btn.getAttribute('data-srv-service') || '';
      var srvProto = btn.getAttribute('data-srv-proto') || '';
      var srvTarget = btn.getAttribute('data-srv-target') || '';
      var srvPriority = btn.getAttribute('data-srv-priority') || '0';
      var srvWeight = btn.getAttribute('data-srv-weight') || '0';
      var srvPort = btn.getAttribute('data-srv-port') || '0';

      var modal = document.getElementById('addDnsModal');
      if(!modal) return;
      // Set values
      var typeEl = modal.querySelector('select[name="type"]');
      var nameEl = modal.querySelector('input[name="name"]');
      var contentEl = modal.querySelector('input[name="content"]');
      var ttlEl = modal.querySelector('input[name="ttl"]');
      var proxiedEl = modal.querySelector('input[name="proxied"]');
      var priEl = modal.querySelector('input[name="priority"]');
      var sServiceEl = modal.querySelector('input[name="srv_service"]');
      var sProtoEl = modal.querySelector('input[name="srv_proto"]');
      var sTargetEl = modal.querySelector('input[name="srv_target"]');
      var sPriEl = modal.querySelector('input[name="srv_priority"]');
      var sWeightEl = modal.querySelector('input[name="srv_weight"]');
      var sPortEl = modal.querySelector('input[name="srv_port"]');

      if(typeEl){ typeEl.value = type; typeEl.dispatchEvent(new Event('change')); }
      if(nameEl){ nameEl.value = name; }
      if(contentEl){ contentEl.value = content; }
      if(ttlEl){ ttlEl.value = ttl; }
      if(proxiedEl){ proxiedEl.checked = proxied; }
      if(priEl){ priEl.value = priority; }
      if(sServiceEl){ sServiceEl.value = srvService; }
      if(sProtoEl){ sProtoEl.value = srvProto; }
      if(sTargetEl){ sTargetEl.value = srvTarget; }
      if(sPriEl){ sPriEl.value = srvPriority; }
      if(sWeightEl){ sWeightEl.value = srvWeight; }
      if(sPortEl){ sPortEl.value = srvPort; }

      // Open modal
      try {
        $('#addDnsModal').modal('show');
      } catch(err) {
        modal.classList.add('show');
      }
    }
  });
  // 自动刷新 NS 状态（仅在未生效时）
  var nsBadge = document.getElementById('ns-status-badge');
  var selectedDomainId = '{if $selectedDomain}{$selectedDomain.id}{/if}';
  if(nsBadge && selectedDomainId){
    setInterval(function(){
      if(nsBadge.classList.contains('badge-warning')){
        var url = 'index.php?m=dnsmanager&action=refreshns&domain_id=' + selectedDomainId;
        if (window.jQuery && $.get) {
          $.get(url).always(function(){ location.reload(); });
        } else {
          var xhr = new XMLHttpRequest();
          xhr.open('GET', url, true);
          xhr.withCredentials = true;
          xhr.onreadystatechange = function(){
            if (xhr.readyState === 4) { location.reload(); }
          };
          xhr.send(null);
        }
      }
    }, 60000);
  }
  // 复制到剪贴板（NS/内容）
  document.addEventListener('click', function(e){
    if(e.target && e.target.classList.contains('copy-btn')){
      var text = e.target.getAttribute('data-copy') || '';
      var ta = document.createElement('textarea');
      ta.value = text; document.body.appendChild(ta); ta.select();
      try { document.execCommand('copy'); } catch(err) {}
      document.body.removeChild(ta);
      // feedback: switch button text briefly (no object literals to avoid Smarty parsing)
      var btnEl = e.target;
      var oldHtml = btnEl.innerHTML;
      btnEl.innerHTML = '已复制';
      btnEl.disabled = true;
      setTimeout(function(){ btnEl.innerHTML = oldHtml; btnEl.disabled = false; }, 800);
    }
  });
  // 快速模板填充
  document.addEventListener('click', function(e){
    if(e.target && e.target.classList.contains('quick-template')){
      e.preventDefault();
      var btn = e.target;
      var type = btn.getAttribute('data-type') || 'A';
      var name = btn.getAttribute('data-name') || '';
      var content = btn.getAttribute('data-content') || '';
      var ttl = btn.getAttribute('data-ttl') || '1';
      var proxied = btn.getAttribute('data-proxied') === '1';
      var priority = btn.getAttribute('data-priority') || '';
      var modal = document.getElementById('addDnsModal');
      if(!modal) return;
      var typeEl = modal.querySelector('select[name="type"]');
      var nameEl = modal.querySelector('input[name="name"]');
      var contentEl = modal.querySelector('input[name="content"]');
      var ttlEl = modal.querySelector('input[name="ttl"]');
      var proxiedEl = modal.querySelector('input[name="proxied"]');
      var priEl = modal.querySelector('input[name="priority"]');
      if(typeEl){ typeEl.value = type; typeEl.dispatchEvent(new Event('change')); }
      if(nameEl){ nameEl.value = name; }
      if(contentEl){ contentEl.value = content; }
      if(ttlEl){ ttlEl.value = ttl; }
      if(proxiedEl){ proxiedEl.checked = proxied; }
      if(priEl && priority !== ''){ priEl.value = priority; }
      try { $('#addDnsModal').modal('show'); } catch(err) { modal.classList.add('show'); }
    }
  });
  // 自动关闭提示
  setTimeout(function(){
    try { $('.alert.alert-success, .alert.alert-danger').alert('close'); } catch(err) {}
  }, 4000);
  // 按钮 loading/禁用态
  document.addEventListener('submit', function(e){
    var btns = e.target.querySelectorAll('.js-loading-btn');
    for(var i=0;i<btns.length;i++){
      var btn = btns[i];
      var text = btn.getAttribute('data-loading-text') || '处理中...';
      btn.setAttribute('data-old-text', btn.innerHTML);
      btn.innerHTML = text;
      btn.disabled = true;
    }
  }, true);

  // 记录创建向导：字段校验与智能提示
  function isIPv4(str){
    var parts = (str||'').split('.');
    if(parts.length!==4) return false;
    for(var i=0;i<4;i++){ var n = +parts[i]; if(isNaN(n)||n<0||n>255||(/^0\d+$/).test(parts[i])) return false; }
    return true;
  }
  function isIPv6(str){ return /:/.test(str||''); }
  function isHostname(str){ return /^[A-Za-z0-9_.-]+$/.test(str||''); }
  function isFQDN(str){ return isHostname(str) && (str.indexOf('.')>0); }

  function setHint(id, text){ var el=document.getElementById(id); if(el){ el.textContent = text || ''; } }
  function addError(input, msg){ input.classList.add('has-error'); input.setAttribute('data-error', msg); }
  function clearError(input){ input.classList.remove('has-error'); input.removeAttribute('data-error'); }

  var typeSel = document.getElementById('add-type');
  var nameInput = document.getElementById('add-name');
  var contentInput = document.getElementById('add-content');
  var submitBtn = document.getElementById('add-submit');

  function updateSmartHints(){
    if(!typeSel) return;
    var t = (typeSel.value||'').toUpperCase();
    setHint('hint-name',''); setHint('hint-content','');
    if(t==='A'){ setHint('hint-content','请输入 IPv4 地址，例如 1.2.3.4'); }
    if(t==='AAAA'){ setHint('hint-content','请输入 IPv6 地址'); }
    if(t==='CNAME'){ setHint('hint-content','请输入指向的主机名（不能指向 IP）'); }
    if(t==='MX'){ setHint('hint-content','请输入邮件主机名，优先级可在下方设置'); }
    if(t==='TXT'){ setHint('hint-content','请输入完整的 TXT 内容'); }
    if(t==='NS'){ setHint('hint-content','请输入权威 NS 主机名'); }
    if(t==='SRV'){ setHint('hint-name','SRV 名称通常留空或仅主机部分，Service/Proto 在下方填写'); }
  }
  if(typeSel){ typeSel.addEventListener('change', updateSmartHints); updateSmartHints(); }

  function validateForm(){
    if(!typeSel) return true;
    var t = (typeSel.value||'').toUpperCase();
    var nameVal = nameInput? (nameInput.value||'').trim():'';
    var contentVal = contentInput? (contentInput.value||'').trim():'';
    var ok = true;
    if(nameInput){ clearError(nameInput); }
    if(contentInput){ clearError(contentInput); }

    // CNAME 互斥检测：不允许 CNAME 与同名 A/AAAA 并存（前端只能做提示）
    if(t==='CNAME'){
      if(/^\d+\.\d+\.\d+\.\d+$/.test(contentVal) || isIPv6(contentVal)){
        addError(contentInput, 'CNAME 不能指向 IP 地址'); ok=false;
      }
    }
    if(t==='A' && !isIPv4(contentVal)){ addError(contentInput,'必须为合法 IPv4 地址'); ok=false; }
    if(t==='AAAA' && !isIPv6(contentVal)){ addError(contentInput,'必须为合法 IPv6 地址'); ok=false; }
    if((t==='CNAME'||t==='MX'||t==='NS') && !isHostname(contentVal)){ addError(contentInput,'必须为合法主机名'); ok=false; }
    // TXT 不做格式限制
    // SRV 由下方字段组成，这里只提示 name 可留空

    // 名称可为空代表根域，若填写需是主机名片段
    if(nameVal && !isHostname(nameVal)){ addError(nameInput,'名称应为主机名片段，如 www 或 @'); ok=false; }

    return ok;
  }

  if(submitBtn){
    submitBtn.addEventListener('click', function(e){
      var ok = validateForm();
      if(!ok){ e.preventDefault(); e.stopPropagation();
        // 显示第一个错误的提示
        var err = document.querySelector('.has-error');
        if(err && err.scrollIntoView){ err.scrollIntoView(true); }
      }
    });
  }

  // 内联添加条：根据类型切换字段显示
  function toggleInline(){
    var tSel = document.getElementById('inline-type'); if(!tSel) return;
    var t = (tSel.value||'').toUpperCase();
    var wrapMX = document.querySelector('.inline-mx-wrap');
    var wrapSRV = document.querySelector('.inline-srv-wrap');
    var wrapCAA = document.querySelector('.inline-caa-wrap');
    var wrapProxy = document.querySelector('.inline-proxy-wrap');
    var wrapContent = document.querySelector('.inline-content-wrap');
    function hide(el){ if(el){ el.classList.add('d-none'); } }
    function show(el){ if(el){ el.classList.remove('d-none'); } }
    hide(wrapMX); hide(wrapSRV); hide(wrapCAA);
    show(wrapContent);
    if(['A','AAAA','CNAME'].indexOf(t)===-1){ if(wrapProxy){ wrapProxy.classList.add('d-none'); } } else { if(wrapProxy){ wrapProxy.classList.remove('d-none'); } }
    if(t==='MX'){ show(wrapMX); }
    if(t==='SRV'){ show(wrapSRV); hide(wrapContent); }
    if(t==='CAA'){ show(wrapCAA); hide(wrapContent); }
  }
  var inlineType = document.getElementById('inline-type');
  if(inlineType){ inlineType.addEventListener('change', toggleInline); toggleInline(); }

  // 内联 CDN 图标开关
  var inlineProxyBtn = document.getElementById('inline-proxy-toggle');
  if(inlineProxyBtn){
    inlineProxyBtn.addEventListener('click', function(){
      var form = document.getElementById('inline-add-form'); if(!form) return;
      var hidden = form.querySelector('input[name="proxied"]'); if(!hidden) return;
      var icon = inlineProxyBtn.querySelector('i');
      var enabled = hidden.value === '1';
      hidden.value = enabled ? '0' : '1';
      if(icon){ icon.style.color = enabled ? '#ccc' : '#f5a623'; }
    });
  }

  // 列表 CDN 点击切换（AJAX）
  document.addEventListener('click', function(e){
    if(e.target && (e.target.classList.contains('js-toggle-cdn') || (e.target.parentElement && e.target.parentElement.classList && e.target.parentElement.classList.contains('js-toggle-cdn')))){
      var btn = e.target.classList.contains('js-toggle-cdn') ? e.target : e.target.parentElement;
      var id = btn.getAttribute('data-id');
      var enabled = btn.getAttribute('data-enabled') === '1';
      var domainId = '{if $selectedDomain}{$selectedDomain.id}{/if}';
      if(!id || !domainId){ return; }
      var url = 'index.php?m=dnsmanager&action=togglecdn&domain_id=' + domainId + '&id=' + encodeURIComponent(id) + '&enable=' + (enabled? '0':'1');
      var apply = function(on){
        btn.setAttribute('data-enabled', on? '1':'0');
        if(btn.classList.contains('dm-switch')){ if(on){ btn.classList.add('on'); } else { btn.classList.remove('on'); } }
        var icon = btn.querySelector('i'); if(icon){ icon.style.color = on? '#f5a623':'#ccc'; }
      };
      function toast(msg, ok){
        var box = document.getElementById('dm-toast');
        if(!box){ box = document.createElement('div'); box.id='dm-toast'; box.className='dm-toast'; document.body.appendChild(box); }
        var it = document.createElement('div'); it.className='item ' + (ok?'success':'error'); it.textContent = msg;
        box.appendChild(it);
        setTimeout(function(){ try{ box.removeChild(it); }catch(e){} }, 2000);
      }
      if (window.jQuery && $.get) { $.get(url).done(function(res){ try{ var r = (typeof res === 'string')? JSON.parse(res):res; if(r && r.success){ apply(r.enabled===1); toast(r.enabled===1?'已开启 CDN':'已关闭 CDN', true); } else { toast((r&&r.message)?r.message:'切换失败', false); } }catch(err){ toast('切换失败', false); } }); }
      else {
        var xhr = new XMLHttpRequest(); xhr.open('GET', url, true); xhr.withCredentials = true; xhr.onreadystatechange = function(){ if(xhr.readyState===4){ try{ var r = JSON.parse(xhr.responseText||'{}'); if(r && r.success){ apply(r.enabled===1); toast(r.enabled===1?'已开启 CDN':'已关闭 CDN', true); } else { toast((r&&r.message)?r.message:'切换失败', false); } }catch(err){ toast('切换失败', false); } } }; xhr.send(null);
      }
    }
  });

  // Floating label behavior for inline inputs
  function flInit(){
    var groups = document.querySelectorAll('.fl-group');
    for(var i=0;i<groups.length;i++){
      (function(g){
        var input = g.querySelector('.form-control'); if(!input) return;
        function update(){ if((input.value||'').length>0){ g.classList.add('has-value'); } else { g.classList.remove('has-value'); } }
        input.addEventListener('focus', function(){ g.classList.add('is-focused'); });
        input.addEventListener('blur', function(){ g.classList.remove('is-focused'); update(); });
        input.addEventListener('input', update);
        update();
      })(groups[i]);
    }
  }
  flInit();
})();
</script>
