<?php
session_start();
include "../connect.php";
if(!isset($_SESSION["loggedin"]) || $_SESSION["role"] !== "admin"){
    header("Location: secure_login.php"); exit();
}


$total_users  = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) as c FROM users"))["c"];
$total_shops  = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) as c FROM shops"))["c"];
$total_orders = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) as c FROM orders"))["c"];
$total_rev    = mysqli_fetch_assoc(mysqli_query($conn,"SELECT SUM(total) as s FROM orders WHERE status!='canceled'"))["s"] ?? 0;


$recent_orders = [];
$ro = mysqli_query($conn,"SELECT o.id,o.status,o.total,u.name as uname FROM orders o JOIN users u ON u.id=o.customer_id ORDER BY o.id DESC LIMIT 5");
while($r=mysqli_fetch_assoc($ro)) $recent_orders[]=$r;


$recent_users = [];
$ru = mysqli_query($conn,"SELECT id,name,email,role FROM users ORDER BY id DESC LIMIT 5");
while($r=mysqli_fetch_assoc($ru)) $recent_users[]=$r;


$recent_orders_admin = [];
$ro2 = mysqli_query($conn,
    "SELECT o.id, o.status, o.total, u.name as uname
     FROM orders o JOIN users u ON u.id=o.customer_id
     ORDER BY o.id DESC LIMIT 5");
while($r=mysqli_fetch_assoc($ro2)) $recent_orders_admin[]=$r;


$shop_types = [];
$st = mysqli_query($conn,"SELECT type, COUNT(*) as cnt FROM shops GROUP BY type ORDER BY cnt DESC");
while($r=mysqli_fetch_assoc($st)) $shop_types[]=$r;

$chart_labels = json_encode(array_column($shop_types,'type'));
$chart_data   = json_encode(array_column($shop_types,'cnt'));


$months_labels = [];
$months_orders = [];
$months_rev    = [];

for($m = 6; $m >= 0; $m--){
    $months_labels[] = date("M Y", strtotime("-$m months"));
    $months_orders[] = 0;
    $months_rev[]    = 0;
}


$sql_chart = "SELECT 
    DATE_FORMAT(order_date, '%b %Y') as month_label,
    COUNT(*) as cnt,
    SUM(total) as rev
FROM orders 
WHERE status != 'canceled'
  AND order_date >= DATE_SUB(NOW(), INTERVAL 7 MONTH)
GROUP BY DATE_FORMAT(order_date, '%Y-%m')
ORDER BY MIN(order_date) ASC";

$chart_res = mysqli_query($conn, $sql_chart);
$db_months = [];
while($r = mysqli_fetch_assoc($chart_res)){
    $db_months[$r['month_label']] = ['cnt' => $r['cnt'], 'rev' => round($r['rev'] / 1000, 1)];
}


foreach($months_labels as $i => $lbl){
    if(isset($db_months[$lbl])){
        $months_orders[$i] = (int)$db_months[$lbl]['cnt'];
        $months_rev[$i]    = $db_months[$lbl]['rev'];
    }
}


$months_labels_short = [];
foreach($months_labels as $lbl){
    $months_labels_short[] = date("M", strtotime($lbl));
}

$line_labels = json_encode($months_labels_short);
$line_orders = json_encode($months_orders);
$line_rev    = json_encode($months_rev);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Mirae Admin — Dashboard</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
  <style>
    *,*::before,*::after{margin:0;padding:0;box-sizing:border-box;}
    :root{
      --bg:#060810;
      --surface:rgba(255,255,255,.03);
      --surface2:rgba(255,255,255,.055);
      --border:rgba(255,255,255,.07);
      --border2:rgba(255,255,255,.12);
      --text:#E8EAF6;
      --muted:rgba(232,234,246,.45);
      --muted2:rgba(232,234,246,.25);
      --purple:#6B3FEC;
      --blue:#2563EB;
      --cyan:#06B6D4;
      --green:#10B981;
      --amber:#F59E0B;
      --red:#EF4444;
      --grad:linear-gradient(135deg,#6B3FEC,#2563EB,#06B6D4);
      --grad-text:linear-gradient(90deg,#818CF8,#38BDF8);
      --nav-w:240px;
      --topbar-h:64px;
    }
    html,body{height:100%;font-family:'Outfit',sans-serif;background:var(--bg);color:var(--text);overflow-x:hidden;}

    /* ═══════════════════════════════
       BACKGROUND
    ═══════════════════════════════ */
    .bg-canvas{
      position:fixed;inset:0;z-index:0;pointer-events:none;
    }
    .bg-blob{
      position:absolute;border-radius:50%;filter:blur(80px);opacity:.5;
    }
    .bg-blob-1{width:500px;height:500px;background:radial-gradient(circle,rgba(107,63,236,.15),transparent 70%);top:-100px;left:-100px;animation:blobDrift1 18s ease-in-out infinite alternate;}
    .bg-blob-2{width:400px;height:400px;background:radial-gradient(circle,rgba(6,182,212,.10),transparent 70%);bottom:0;right:200px;animation:blobDrift2 22s ease-in-out infinite alternate;}
    .grid-lines{position:absolute;inset:0;background-image:linear-gradient(rgba(107,63,236,.035) 1px,transparent 1px),linear-gradient(90deg,rgba(107,63,236,.035) 1px,transparent 1px);background-size:56px 56px;}
    @keyframes blobDrift1{from{transform:translate(0,0) scale(1)}to{transform:translate(40px,30px) scale(1.1)}}
    @keyframes blobDrift2{from{transform:translate(0,0) scale(1)}to{transform:translate(-30px,-40px) scale(1.05)}}

    /* ═══════════════════════════════
       LAYOUT SHELL
    ═══════════════════════════════ */
    .shell{position:relative;z-index:1;display:flex;min-height:100vh;}

    /* ═══════════════════════════════
       SIDEBAR NAV
    ═══════════════════════════════ */
    .sidebar{
      position:fixed;top:0;left:0;
      width:var(--nav-w);height:100vh;
      background:rgba(6,8,16,.85);
      border-right:1px solid var(--border);
      backdrop-filter:blur(24px);
      display:flex;flex-direction:column;
      z-index:100;padding:0;
    }
    .sidebar-brand{
      display:flex;align-items:center;gap:10px;
      padding:20px 24px 20px;
      border-bottom:1px solid var(--border);
      text-decoration:none;
      min-height:var(--topbar-h);
    }
    .sidebar-brand img{width:32px;height:32px;object-fit:contain;}
    .sidebar-brand-text{
      display:flex;flex-direction:column;gap:1px;
    }
    .sidebar-brand-name{
      font-size:1.05rem;font-weight:700;letter-spacing:.04em;
      background:var(--grad-text);-webkit-background-clip:text;-webkit-text-fill-color:transparent;
    }
    .sidebar-brand-role{
      font-size:.62rem;font-weight:500;letter-spacing:.14em;text-transform:uppercase;
      color:rgba(239,68,68,.7);
    }
    .sidebar-nav{flex:1;padding:16px 12px;display:flex;flex-direction:column;gap:4px;}
    .nav-section-label{
      font-size:.6rem;font-weight:600;letter-spacing:.18em;text-transform:uppercase;
      color:var(--muted2);padding:12px 12px 6px;
    }
    .nav-link{
      display:flex;align-items:center;gap:12px;
      padding:11px 14px;border-radius:12px;
      text-decoration:none;color:var(--muted);
      font-size:.875rem;font-weight:500;
      transition:all .22s;position:relative;
      border:1px solid transparent;
    }
    .nav-link:hover{color:var(--text);background:var(--surface2);border-color:var(--border);}
    .nav-link.active{
      color:var(--text);
      background:rgba(107,63,236,.15);
      border-color:rgba(107,63,236,.3);
    }
    .nav-link.active::before{
      content:'';position:absolute;left:-12px;top:50%;transform:translateY(-50%);
      width:3px;height:60%;border-radius:2px;
      background:linear-gradient(180deg,#818CF8,#38BDF8);
    }
    .nav-icon{width:18px;height:18px;opacity:.7;flex-shrink:0;}
    .nav-link.active .nav-icon,.nav-link:hover .nav-icon{opacity:1;}
    .nav-link.logout{color:rgba(239,68,68,.6);margin-top:auto;}
    .nav-link.logout:hover{color:#F87171;background:rgba(239,68,68,.08);border-color:rgba(239,68,68,.2);}
    .sidebar-footer{
      padding:16px;border-top:1px solid var(--border);
      font-size:.72rem;color:var(--muted2);font-family:'Space Mono',monospace;
      line-height:1.6;
    }
    .sidebar-footer span{color:rgba(107,63,236,.6);}

    /* ═══════════════════════════════
       MAIN CONTENT AREA
    ═══════════════════════════════ */
    .main{
      margin-left:var(--nav-w);
      flex:1;display:flex;flex-direction:column;
      min-height:100vh;
    }

    /* TOP BAR */
    .topbar{
      height:var(--topbar-h);
      display:flex;align-items:center;justify-content:space-between;
      padding:0 32px;
      border-bottom:1px solid var(--border);
      background:rgba(6,8,16,.6);
      backdrop-filter:blur(20px);
      position:sticky;top:0;z-index:50;
    }
    .topbar-left{display:flex;align-items:center;gap:16px;}
    .breadcrumb{font-size:.8rem;color:var(--muted);}
    .breadcrumb strong{color:var(--text);font-weight:600;}
    .page-badge{
      display:inline-flex;align-items:center;gap:6px;
      padding:4px 12px;border-radius:100px;
      background:rgba(107,63,236,.12);border:1px solid rgba(107,63,236,.25);
      font-size:.68rem;font-weight:600;letter-spacing:.12em;text-transform:uppercase;
      color:#a78bfa;
    }
    .topbar-right{display:flex;align-items:center;gap:16px;}
    .admin-chip{
      display:flex;align-items:center;gap:8px;
      padding:6px 14px;border-radius:100px;
      background:var(--surface2);border:1px solid var(--border2);
      font-size:.8rem;font-weight:500;
    }
    .admin-avatar{
      width:26px;height:26px;border-radius:50%;
      background:var(--grad);
      display:flex;align-items:center;justify-content:center;
      font-size:.65rem;font-weight:700;color:#fff;
    }
    .status-dot{width:7px;height:7px;border-radius:50%;background:#10B981;box-shadow:0 0 6px rgba(16,185,129,.6);}

    /* ═══════════════════════════════
       PAGE CONTENT
    ═══════════════════════════════ */
    .content{flex:1;padding:32px;overflow-y:auto;}

    /* PAGE HEADER */
    .page-header{margin-bottom:32px;}
    .page-title{
      font-size:1.8rem;font-weight:800;letter-spacing:-.025em;
      font-family:'Plus Jakarta Sans',sans-serif;
      margin-bottom:6px;
    }
    .page-title span{
      background:var(--grad-text);
      -webkit-background-clip:text;-webkit-text-fill-color:transparent;
    }
    .page-subtitle{font-size:.875rem;color:var(--muted);font-weight:300;line-height:1.6;}

    /* ═══════════════════════════════
       STAT CARDS ROW
    ═══════════════════════════════ */
    .stats-row{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:28px;}
    .stat-card{
      background:var(--surface);
      border:1px solid var(--border);
      border-radius:16px;padding:22px;
      position:relative;overflow:hidden;
      transition:border-color .25s,transform .25s,box-shadow .25s;
    }
    .stat-card:hover{border-color:var(--border2);transform:translateY(-2px);box-shadow:0 12px 40px rgba(0,0,0,.4);}
    .stat-card::before{
      content:'';position:absolute;inset:0;
      background:linear-gradient(135deg,rgba(107,63,236,.06) 0%,transparent 60%);
      pointer-events:none;
    }
    .stat-card-icon{
      width:40px;height:40px;border-radius:10px;
      display:flex;align-items:center;justify-content:center;
      font-size:1.1rem;margin-bottom:14px;
    }
    .stat-card-icon.purple{background:rgba(107,63,236,.2);border:1px solid rgba(107,63,236,.3);}
    .stat-card-icon.blue{background:rgba(37,99,235,.2);border:1px solid rgba(37,99,235,.3);}
    .stat-card-icon.cyan{background:rgba(6,182,212,.2);border:1px solid rgba(6,182,212,.3);}
    .stat-card-icon.green{background:rgba(16,185,129,.2);border:1px solid rgba(16,185,129,.3);}
    .stat-card-icon.amber{background:rgba(245,158,11,.2);border:1px solid rgba(245,158,11,.3);}
    .stat-val{font-size:1.9rem;font-weight:800;letter-spacing:-.03em;font-family:'Plus Jakarta Sans',sans-serif;margin-bottom:4px;}
    .stat-label{font-size:.78rem;color:var(--muted);font-weight:400;}
    .stat-change{
      font-size:.72rem;font-weight:500;margin-top:10px;
      display:inline-flex;align-items:center;gap:4px;
    }
    .stat-change.up{color:#10B981;}
    .stat-change.down{color:#EF4444;}

    /* ═══════════════════════════════
       CHART SECTION
    ═══════════════════════════════ */
    .charts-row{display:grid;grid-template-columns:2fr 1fr;gap:16px;margin-bottom:28px;}
    .chart-card{
      background:var(--surface);border:1px solid var(--border);
      border-radius:16px;padding:24px;
    }
    .chart-card-header{
      display:flex;align-items:center;justify-content:space-between;
      margin-bottom:24px;
    }
    .chart-card-title{font-size:1rem;font-weight:600;letter-spacing:-.01em;}
    .chart-card-sub{font-size:.75rem;color:var(--muted);margin-top:2px;}
    .chart-legend{display:flex;gap:16px;}
    .legend-item{display:flex;align-items:center;gap:6px;font-size:.75rem;color:var(--muted);}
    .legend-dot{width:8px;height:8px;border-radius:50%;}
    canvas{max-height:260px;}

    /* Donut chart wrapper */
    .donut-wrap{position:relative;max-width:220px;margin:0 auto;}
    .donut-center{
      position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);
      text-align:center;
    }
    .donut-center-val{font-size:1.6rem;font-weight:800;font-family:'Plus Jakarta Sans',sans-serif;}
    .donut-center-lbl{font-size:.68rem;color:var(--muted);}

    /* Shop type legend */
    .type-legend{margin-top:20px;display:flex;flex-direction:column;gap:8px;}
    .type-row{display:flex;align-items:center;justify-content:space-between;font-size:.8rem;}
    .type-name{display:flex;align-items:center;gap:8px;color:var(--muted);}
    .type-bar-wrap{flex:1;height:3px;background:rgba(255,255,255,.07);border-radius:2px;margin:0 12px;}
    .type-bar{height:100%;border-radius:2px;}
    .type-count{font-weight:600;color:var(--text);}

    /* ═══════════════════════════════
       RECENT ACTIVITY + ORDERS TABLE
    ═══════════════════════════════ */
    .bottom-row{display:grid;grid-template-columns:1fr 1fr;gap:16px;}
    .info-card{background:var(--surface);border:1px solid var(--border);border-radius:16px;padding:24px;}
    .info-card-header{
      display:flex;align-items:center;justify-content:space-between;
      margin-bottom:20px;
    }
    .info-card-title{font-size:.95rem;font-weight:600;}
    .view-all{font-size:.75rem;color:#a78bfa;text-decoration:none;transition:color .2s;}
    .view-all:hover{color:#c4b5fd;}

    /* Activity feed */
    .activity-list{display:flex;flex-direction:column;gap:0;}
    .activity-item{
      display:flex;align-items:flex-start;gap:12px;
      padding:12px 0;border-bottom:1px solid var(--border);
    }
    .activity-item:last-child{border-bottom:none;padding-bottom:0;}
    .activity-icon{
      width:34px;height:34px;border-radius:10px;flex-shrink:0;
      display:flex;align-items:center;justify-content:center;font-size:.9rem;
    }
    .ai-purple{background:rgba(107,63,236,.15);border:1px solid rgba(107,63,236,.25);}
    .ai-blue{background:rgba(37,99,235,.15);border:1px solid rgba(37,99,235,.25);}
    .ai-green{background:rgba(16,185,129,.15);border:1px solid rgba(16,185,129,.25);}
    .ai-amber{background:rgba(245,158,11,.15);border:1px solid rgba(245,158,11,.25);}
    .ai-red{background:rgba(239,68,68,.15);border:1px solid rgba(239,68,68,.25);}
    .activity-text{font-size:.82rem;color:var(--muted);line-height:1.5;}
    .activity-text strong{color:var(--text);font-weight:600;}
    .activity-time{font-size:.68rem;color:var(--muted2);margin-top:2px;font-family:'Space Mono',monospace;}

    /* Mini table */
    .mini-table{width:100%;border-collapse:collapse;}
    .mini-table th{font-size:.68rem;font-weight:600;letter-spacing:.1em;text-transform:uppercase;color:var(--muted2);padding:8px 0 12px;text-align:left;border-bottom:1px solid var(--border);}
    .mini-table td{font-size:.82rem;padding:11px 0;border-bottom:1px solid var(--border);color:var(--muted);vertical-align:middle;}
    .mini-table tr:last-child td{border-bottom:none;}
    .mini-table td:first-child{color:var(--text);font-weight:500;}
    .status-badge{
      display:inline-flex;align-items:center;gap:5px;
      padding:3px 10px;border-radius:100px;font-size:.68rem;font-weight:500;
    }
    .sb-green{background:rgba(16,185,129,.12);border:1px solid rgba(16,185,129,.25);color:#34D399;}
    .sb-amber{background:rgba(245,158,11,.12);border:1px solid rgba(245,158,11,.25);color:#FCD34D;}
    .sb-red{background:rgba(239,68,68,.12);border:1px solid rgba(239,68,68,.25);color:#FCA5A5;}
    .sb-blue{background:rgba(37,99,235,.12);border:1px solid rgba(37,99,235,.25);color:#93C5FD;}
  </style>
</head>
<body>
<div class="bg-canvas">
  <div class="bg-blob bg-blob-1"></div>
  <div class="bg-blob bg-blob-2"></div>
  <div class="grid-lines"></div>
</div>

<div class="shell">
  <!-- ── SIDEBAR ── -->
  <aside class="sidebar">
    <a href="admin_dashboard.php" class="sidebar-brand">
      <img src="../customer/logo.png" alt="Mirae" style="width:32px;height:32px;object-fit:contain;border-radius:7px;"/>
      <div class="sidebar-brand-text">
        <div class="sidebar-brand-name">Mirae</div>
        <div class="sidebar-brand-role">⬡ Admin Portal</div>
      </div>
    </a>

    <nav class="sidebar-nav">
      <div class="nav-section-label">Main</div>
      <a href="admin_dashboard.php" class="nav-link active">
        <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
        Dashboard
      </a>

      <div class="nav-section-label">Management</div>
      <a href="admin_users.php" class="nav-link">
        <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path stroke-linecap="round" d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/></svg>
        Manage Users
      </a>
      <a href="admin_shops.php" class="nav-link">
        <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><polyline stroke-linecap="round" points="9 22 9 12 15 12 15 22"/></svg>
        Manage Shops
      </a>

      <div style="flex:1;"></div>
      <a href="admin_account.php" class="nav-link">
        <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
        Account
      </a>
      <a href="admin_logout.php" class="nav-link logout">
        <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
        Log Out
      </a>
    </nav>

    <div class="sidebar-footer">
      <span>// RESTRICTED ACCESS</span><br>
      Session active · Logs enabled
    </div>
  </aside>

  <!-- ── MAIN ── -->
  <main class="main">
    <div class="topbar">
      <div class="topbar-left">
        <div class="breadcrumb">Mirae Admin / <strong>Dashboard</strong></div>
      </div>
      <div class="topbar-right">
        <div class="admin-chip">
          <div class="admin-avatar"><?= strtoupper(substr($_SESSION['name'],0,1)) ?></div>
          <?= htmlspecialchars(explode(' ',$_SESSION['name'])[0]) ?>
        </div>
      </div>
    </div>

    <div class="content">
      <div class="page-header">
        <h1 class="page-title">Welcome back, <span><?= htmlspecialchars(explode(' ', $_SESSION['name'])[0]) ?></span></h1>
        <p class="page-subtitle">Here's what's happening across the Mirae digital mall today.</p>
      </div>

      <!-- STAT CARDS -->
      <div class="stats-row">
        <div class="stat-card">
          <div class="stat-card-icon purple">💰</div>
          <div class="stat-val">₺<?= number_format($total_rev, 0) ?></div>
          <div class="stat-label">Total Revenue</div>
        </div>
        <div class="stat-card">
          <div class="stat-card-icon blue">🛒</div>
          <div class="stat-val"><?= number_format($total_orders) ?></div>
          <div class="stat-label">Total Orders</div>
        </div>
        <div class="stat-card">
          <div class="stat-card-icon cyan">👥</div>
          <div class="stat-val"><?= number_format($total_users) ?></div>
          <div class="stat-label">Registered Users</div>
        </div>
        <div class="stat-card">
          <div class="stat-card-icon green">🏪</div>
          <div class="stat-val"><?= $total_shops ?></div>
          <div class="stat-label">Active Shops</div>
        </div>
      </div>

      <!-- CHARTS -->
      <div class="charts-row">
        <!-- Revenue Line Chart -->
        <div class="chart-card">
          <div class="chart-card-header">
            <div>
              <div class="chart-card-title">Revenue & Orders</div>
              <div class="chart-card-sub">Last 7 months performance</div>
            </div>
            <div class="chart-legend">
              <div class="legend-item"><div class="legend-dot" style="background:#818CF8"></div>Revenue</div>
              <div class="legend-item"><div class="legend-dot" style="background:#38BDF8"></div>Orders</div>
            </div>
          </div>
          <canvas id="revenueChart"></canvas>
        </div>

        <!-- Shops by Type Donut -->
        <div class="chart-card">
          <div class="chart-card-header">
            <div>
              <div class="chart-card-title">Shops by Type</div>
              <div class="chart-card-sub">Current distribution</div>
            </div>
          </div>
          <div class="donut-wrap">
            <canvas id="shopTypeChart"></canvas>
            <div class="donut-center">
              <div class="donut-center-val"><?= $total_shops ?></div>
              <div class="donut-center-lbl">Total Shops</div>
            </div>
          </div>
          <div class="type-legend">
            <?php
            $leg_colors = ['#818CF8','#38BDF8','#34D399','#FCD34D','#EF4444','#10B981'];
            $max_cnt = !empty($shop_types) ? max(array_column($shop_types,'cnt')) : 1;
            foreach($shop_types as $i => $st):
                $col = $leg_colors[$i % count($leg_colors)];
                $pct = round(($st['cnt'] / $max_cnt) * 100);
            ?>
            <div class="type-row">
              <div class="type-name"><div class="legend-dot" style="background:<?= $col ?>"></div><?= htmlspecialchars($st['type']) ?></div>
              <div class="type-bar-wrap"><div class="type-bar" style="width:<?= $pct ?>%;background:<?= $col ?>"></div></div>
              <div class="type-count"><?= $st['cnt'] ?></div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

      <!-- BOTTOM: ACTIVITY + RECENT ORDERS -->
      <div class="bottom-row">
        <!-- Activity Feed -->
        <div class="info-card">
          <div class="info-card-header">
            <div class="info-card-title">Recent Activity</div>
            <a href="#" class="view-all">View all →</a>
          </div>
          <div class="activity-list">
            <?php foreach($recent_users as $u): ?>
            <div class="activity-item">
              <div class="activity-icon ai-purple">👤</div>
              <div>
                <div class="activity-text"><strong><?= htmlspecialchars($u['name']) ?></strong> — <?= ucfirst($u['role']) ?> · <?= htmlspecialchars($u['email']) ?></div>
                <div class="activity-time">#<?= $u['id'] ?></div>
              </div>
            </div>
            <?php endforeach; ?>
            <?php if(empty($recent_users)): ?>
            <div style="color:var(--muted);font-size:.82rem;padding:12px 0;">No users yet.</div>
            <?php endif; ?>
          </div>
        </div>

        <!-- Recent Orders Table -->
        <div class="info-card">
          <div class="info-card-header">
            <div class="info-card-title">Recent Orders</div>
            <a href="#" class="view-all">View all →</a>
          </div>
          <table class="mini-table">
            <thead>
              <tr>
                <th>Order</th>
                <th>Customer</th>
                <th>Amount</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <?php if(empty($recent_orders_admin)): ?>
              <tr><td colspan="4" style="text-align:center;color:var(--muted);padding:20px;">No orders yet.</td></tr>
              <?php else: foreach($recent_orders_admin as $o):
                $sc = ["pending"=>"sb-amber","accepted"=>"sb-blue","delivered"=>"sb-green","canceled"=>"sb-red"];
                $cls = $sc[$o["status"]] ?? "sb-amber";
              ?>
              <tr>
                <td style="font-family:'Space Mono',monospace;font-size:.78rem;color:var(--muted2)">#<?= $o["id"] ?></td>
                <td><?= htmlspecialchars($o["uname"]) ?></td>
                <td>₺<?= number_format($o["total"],2) ?></td>
                <td><span class="status-badge <?= $cls ?>">● <?= ucfirst($o["status"]) ?></span></td>
              </tr>
              <?php endforeach; endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div><!-- /content -->
  </main>
</div><!-- /shell -->

<script>
// ── Revenue & Orders Line Chart ──
const rCtx = document.getElementById('revenueChart').getContext('2d');
new Chart(rCtx, {
  type:'line',
  data:{
    labels: <?= $line_labels ?>,
    datasets:[
      {
        label:'Revenue (₺K)',
        data: <?= $line_rev ?>,
        borderColor:'#818CF8',
        backgroundColor:'rgba(129,140,248,.08)',
        borderWidth:2.5,tension:.4,fill:true,
        pointBackgroundColor:'#818CF8',pointRadius:4,pointHoverRadius:6,
        yAxisID:'y'
      },
      {
        label:'Orders',
        data: <?= $line_orders ?>,
        borderColor:'#38BDF8',
        backgroundColor:'rgba(56,189,248,.06)',
        borderWidth:2.5,tension:.4,fill:true,
        pointBackgroundColor:'#38BDF8',pointRadius:4,pointHoverRadius:6,
        yAxisID:'y2'
      }
    ]
  },
  options:{
    responsive:true,maintainAspectRatio:true,
    interaction:{intersect:false,mode:'index'},
    plugins:{
      legend:{display:false},
      tooltip:{
        backgroundColor:'rgba(6,8,16,.92)',
        borderColor:'rgba(255,255,255,.1)',borderWidth:1,
        titleColor:'#E8EAF6',bodyColor:'rgba(232,234,246,.6)',
        padding:12,cornerRadius:10
      }
    },
    scales:{
      x:{
        grid:{color:'rgba(255,255,255,.05)'},
        ticks:{color:'rgba(232,234,246,.4)',font:{size:11}}
      },
      y:{
        position:'left',
        min:0,
        grid:{color:'rgba(255,255,255,.05)'},
        ticks:{color:'rgba(232,234,246,.4)',font:{size:11},callback:v=>'₺'+v+'K'}
      },
      y2:{
        position:'right',
        min:0,
        grid:{display:false},
        ticks:{
          color:'rgba(56,189,248,.6)',
          font:{size:11},
          stepSize:1,
          callback:v => Number.isInteger(v) ? v : ''
        }
      }
    }
  }
});

// ── Shop Type Donut Chart (DB) ──
const sCtx = document.getElementById('shopTypeChart').getContext('2d');
const shopLabels = <?= $chart_labels ?>;
const shopData   = <?= $chart_data ?>;
const bgColors = ['rgba(129,140,248,.85)','rgba(56,189,248,.85)','rgba(52,211,153,.85)','rgba(252,211,77,.85)','rgba(239,68,68,.85)','rgba(16,185,129,.85)'];
new Chart(sCtx, {
  type:'doughnut',
  data:{
    labels: shopLabels,
    datasets:[{
      data: shopData,
      backgroundColor: bgColors.slice(0, shopLabels.length),
      borderColor:'rgba(6,8,16,.8)',borderWidth:3,hoverOffset:6
    }]
  },
  options:{
    responsive:true,cutout:'72%',
    plugins:{legend:{display:false},tooltip:{
      backgroundColor:'rgba(6,8,16,.92)',
      borderColor:'rgba(255,255,255,.1)',borderWidth:1,
      titleColor:'#E8EAF6',bodyColor:'rgba(232,234,246,.6)',
      padding:10,cornerRadius:10
    }}
  }
});
</script>
</body>
</html>