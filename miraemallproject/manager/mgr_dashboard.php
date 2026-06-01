<?php
include "../connect.php";
include "../session.php";

if($_SESSION["role"] != "manager"){
    header("Location: ../login.php");
}

$mid = $_SESSION["id"];

$sql = "SELECT * FROM shops WHERE manager_id='$mid' LIMIT 1";
$result = mysqli_query($conn, $sql);
$shop_row = mysqli_fetch_assoc($result);

if($shop_row){
    $shop_id   = $shop_row["id"];
    $shop_name = htmlspecialchars($shop_row["name"]);
} else {
    $shop_id   = 0;
    $shop_name = "Your Shop";
}

$prod_count = 0;
if($shop_id != 0){
    $sql = "SELECT COUNT(*) as c FROM products WHERE shop_id='$shop_id'";
    $result = mysqli_query($conn, $sql);
    $r = mysqli_fetch_assoc($result);
    $prod_count = $r["c"];
}

$order_count = 0;
$total_rev   = 0;
if($shop_id != 0){
    $sql = "SELECT COUNT(DISTINCT o.id) as cnt, SUM(ol.total_price) as rev
            FROM orders o
            JOIN order_line ol ON ol.order_id = o.id
            JOIN products p ON ol.product_id = p.id
            WHERE p.shop_id='$shop_id' AND o.status != 'canceled'";
    $result = mysqli_query($conn, $sql);
    $r = mysqli_fetch_assoc($result);
    if($r["cnt"] != ""){
        $order_count = $r["cnt"];
    } else {
        $order_count = 0;
    }
    if($r["rev"] != ""){
        $total_rev = $r["rev"];
    } else {
        $total_rev = 0;
    }
}

if($shop_row){
    $rating = number_format($shop_row["rating"],1);
} else {
    $rating = "—";
}

$recent_orders = [];
if($shop_id != 0){
    $sql = "SELECT DISTINCT o.id, o.status, o.total, u.name as cname
            FROM orders o
            JOIN order_line ol ON ol.order_id = o.id
            JOIN products p ON ol.product_id = p.id
            JOIN users u ON u.id = o.customer_id
            WHERE p.shop_id='$shop_id'
            ORDER BY o.id DESC LIMIT 5";
    $res = mysqli_query($conn, $sql);
    while($row = mysqli_fetch_assoc($res)){
        $sql2 = "SELECT p.name, ol.amount FROM order_line ol JOIN products p ON ol.product_id=p.id
                 WHERE ol.order_id='{$row['id']}' AND p.shop_id='$shop_id'";
        $ir = mysqli_query($conn, $sql2);
        $items = [];
        while($i = mysqli_fetch_assoc($ir)) $items[] = htmlspecialchars($i["name"])." ×".$i["amount"];
        $row["items"] = implode(", ", $items);
        $recent_orders[] = $row;
    }
}

function statusBadge($s){
    $map = ["pending"=>"b-amber","accepted"=>"b-blue","delivered"=>"b-green","canceled"=>"b-red"];
    if(isset($map[$s])){
        $cls = $map[$s];
    } else {
        $cls = "b-teal";
    }
    return "<span class='badge $cls'>● ".htmlspecialchars($s)."</span>";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1.0"/>
  <title>Mirae Manager — Dashboard</title>
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Plus+Jakarta+Sans:wght@400;600;700;800&family=Space+Mono&display=swap" rel="stylesheet">
  <style>
*,*::before,*::after{margin:0;padding:0;box-sizing:border-box;}
:root{
  --bg:#060810;--surface:rgba(255,255,255,.03);--surface2:rgba(255,255,255,.06);
  --border:rgba(255,255,255,.07);--border2:rgba(255,255,255,.13);
  --text:#E8EAF6;--muted:rgba(232,234,246,.45);--muted2:rgba(232,234,246,.22);
  --teal:#0D9488;--grad:linear-gradient(135deg,#0D9488,#0EA5E9,#6366F1);
  --grad-text:linear-gradient(90deg,#2DD4BF,#38BDF8);
  --nav-w:240px;--topbar-h:64px;
}
html,body{height:100%;font-family:'Outfit',sans-serif;background:var(--bg);color:var(--text);}
.bg-canvas{position:fixed;inset:0;z-index:0;pointer-events:none;}
.bg-blob{position:absolute;border-radius:50%;filter:blur(90px);opacity:.45;}
.bg-blob-1{width:550px;height:550px;background:radial-gradient(circle,rgba(13,148,136,.16),transparent 70%);top:-120px;left:-80px;}
.bg-blob-2{width:400px;height:400px;background:radial-gradient(circle,rgba(99,102,241,.12),transparent 70%);bottom:0;right:80px;}
.grid-lines{position:absolute;inset:0;background-image:linear-gradient(rgba(13,148,136,.04) 1px,transparent 1px),linear-gradient(90deg,rgba(13,148,136,.04) 1px,transparent 1px);background-size:56px 56px;}
.shell{position:relative;z-index:1;display:flex;min-height:100vh;}
.sidebar{position:fixed;top:0;left:0;width:var(--nav-w);height:100vh;background:rgba(6,8,16,.88);border-right:1px solid var(--border);backdrop-filter:blur(24px);display:flex;flex-direction:column;z-index:100;}
.sidebar-brand{display:flex;align-items:center;gap:10px;padding:18px 22px;border-bottom:1px solid var(--border);text-decoration:none;min-height:var(--topbar-h);}
.sidebar-brand-name{font-size:1rem;font-weight:700;letter-spacing:.04em;background:var(--grad-text);-webkit-background-clip:text;-webkit-text-fill-color:transparent;}
.sidebar-brand-role{font-size:.6rem;font-weight:600;letter-spacing:.14em;text-transform:uppercase;color:rgba(13,148,136,.8);}
.sidebar-nav{flex:1;padding:14px 10px;display:flex;flex-direction:column;gap:3px;}
.nav-sec{font-size:.58rem;font-weight:600;letter-spacing:.18em;text-transform:uppercase;color:var(--muted2);padding:12px 12px 5px;}
.nav-link{display:flex;align-items:center;gap:11px;padding:10px 13px;border-radius:11px;text-decoration:none;color:var(--muted);font-size:.875rem;font-weight:500;transition:all .2s;border:1px solid transparent;}
.nav-link:hover{color:var(--text);background:var(--surface2);border-color:var(--border);}
.nav-link.active{color:var(--text);background:rgba(13,148,136,.15);border-color:rgba(13,148,136,.35);}
.nav-icon{width:17px;height:17px;opacity:.65;flex-shrink:0;}
.nav-link.active .nav-icon,.nav-link:hover .nav-icon{opacity:1;}
.nav-link.logout{color:rgba(239,68,68,.55);}
.nav-link.logout:hover{color:#F87171;background:rgba(239,68,68,.08);border-color:rgba(239,68,68,.2);}
.sidebar-footer{padding:14px;border-top:1px solid var(--border);font-size:.68rem;color:var(--muted2);font-family:'Space Mono',monospace;line-height:1.6;}
.sidebar-footer span{color:rgba(13,148,136,.6);}
.main{margin-left:var(--nav-w);flex:1;display:flex;flex-direction:column;}
.topbar{height:var(--topbar-h);display:flex;align-items:center;justify-content:space-between;padding:0 30px;border-bottom:1px solid var(--border);background:rgba(6,8,16,.65);backdrop-filter:blur(20px);position:sticky;top:0;z-index:50;}
.breadcrumb{font-size:.78rem;color:var(--muted);}
.breadcrumb strong{color:var(--text);font-weight:600;}
.page-badge{display:inline-flex;align-items:center;gap:5px;padding:3px 11px;border-radius:100px;font-size:.65rem;font-weight:600;letter-spacing:.12em;text-transform:uppercase;background:rgba(13,148,136,.12);border:1px solid rgba(13,148,136,.3);color:#2DD4BF;}
.mgr-chip{display:flex;align-items:center;gap:8px;padding:5px 13px;border-radius:100px;background:var(--surface2);border:1px solid var(--border2);font-size:.8rem;font-weight:500;}
.mgr-av{width:26px;height:26px;border-radius:50%;background:var(--grad);display:flex;align-items:center;justify-content:center;font-size:.65rem;font-weight:700;color:#fff;}
.status-dot{width:7px;height:7px;border-radius:50%;background:#10B981;box-shadow:0 0 6px rgba(16,185,129,.6);}
.content{flex:1;padding:30px;}
.page-title{font-size:1.75rem;font-weight:800;letter-spacing:-.025em;font-family:'Plus Jakarta Sans',sans-serif;margin-bottom:4px;}
.page-title span{background:var(--grad-text);-webkit-background-clip:text;-webkit-text-fill-color:transparent;}
.page-subtitle{font-size:.85rem;color:var(--muted);font-weight:300;margin-bottom:26px;}
.stats-row{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:24px;}
.stat-card{background:var(--surface);border:1px solid var(--border);border-radius:15px;padding:22px;position:relative;overflow:hidden;}
.stat-icon{width:38px;height:38px;border-radius:9px;display:flex;align-items:center;justify-content:center;font-size:1rem;margin-bottom:12px;}
.si-teal{background:rgba(13,148,136,.18);border:1px solid rgba(13,148,136,.3);}
.si-green{background:rgba(16,185,129,.18);border:1px solid rgba(16,185,129,.3);}
.si-amber{background:rgba(245,158,11,.18);border:1px solid rgba(245,158,11,.3);}
.si-purple{background:rgba(139,92,246,.18);border:1px solid rgba(139,92,246,.3);}
.stat-val{font-size:1.8rem;font-weight:800;letter-spacing:-.03em;font-family:'Plus Jakarta Sans',sans-serif;margin-bottom:3px;}
.stat-lbl{font-size:.75rem;color:var(--muted);}
.data-table{width:100%;border-collapse:collapse;}
.data-table th{font-size:.65rem;font-weight:600;letter-spacing:.12em;text-transform:uppercase;color:var(--muted2);padding:13px 18px;text-align:left;border-bottom:1px solid var(--border);}
.data-table td{padding:13px 18px;border-bottom:1px solid var(--border);vertical-align:middle;font-size:.845rem;}
.data-table tbody tr:last-child td{border-bottom:none;}
.data-table tbody tr:hover{background:rgba(255,255,255,.02);}
.badge{display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:100px;font-size:.68rem;font-weight:500;}
.b-green{background:rgba(16,185,129,.1);border:1px solid rgba(16,185,129,.25);color:#34D399;}
.b-amber{background:rgba(245,158,11,.1);border:1px solid rgba(245,158,11,.25);color:#FCD34D;}
.b-red{background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.25);color:#FCA5A5;}
.b-blue{background:rgba(59,130,246,.1);border:1px solid rgba(59,130,246,.25);color:#93C5FD;}
.b-teal{background:rgba(13,148,136,.1);border:1px solid rgba(13,148,136,.25);color:#2DD4BF;}
.recent-orders{background:var(--surface);border:1px solid var(--border);border-radius:15px;padding:22px;}
.ro-hdr{display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;}
.ro-title{font-size:.95rem;font-weight:600;}
  </style>
</head>
<body>
<div class="bg-canvas"><div class="bg-blob bg-blob-1"></div><div class="bg-blob bg-blob-2"></div><div class="grid-lines"></div></div>
<div class="shell">
  <aside class="sidebar">
    <a href="mgr_dashboard.php" class="sidebar-brand">
      <img src="../customer/logo.png" alt="Mirae" style="width:30px;height:30px;object-fit:contain;border-radius:7px;flex-shrink:0;display:block;"/>
      <div><div class="sidebar-brand-name">Mirae</div></div>
    </a>
    <nav class="sidebar-nav">
      <div class="nav-sec">Overview</div>
      <a href="mgr_dashboard.php" class="nav-link active">
        <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
        Home
      </a>
      <div class="nav-sec">Shop</div>
      <a href="mgr_products.php" class="nav-link">
        <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10"/></svg>
        Products
      </a>
      <a href="mgr_orders.php" class="nav-link">
        <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
        Orders
      </a>
      <a href="mgr_deliveries.php" class="nav-link">
        <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
        Deliveries
      </a>
      <div style="flex:1;"></div>
      <a href="logout.php" class="nav-link logout" style="font-size:.95rem;font-weight:600;padding:12px 13px;">
        <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
        Log Out
      </a>
    </nav>

  </aside>
  <main class="main">
    <div class="topbar">
      <div style="display:flex;align-items:center;gap:14px;">
        <div class="breadcrumb">Manager / <strong>Dashboard</strong></div>

      </div>
      <div class="mgr-chip">
        <div class="mgr-av"><?= strtoupper(substr($_SESSION["name"],0,1)) ?></div>
        <?= htmlspecialchars(explode(" ",$_SESSION["name"])[0]) ?>
      </div>
    </div>
    <div class="content">
      <h1 class="page-title">Welcome back, <span><?= htmlspecialchars(explode(" ",$_SESSION["name"])[0]) ?></span> 👋</h1>
      <p class="page-subtitle"><?= $shop_name ?> · Here's your shop performance today.</p>

      <div class="stats-row">
        <div class="stat-card">
          <div class="stat-icon si-teal">💰</div>
          <div class="stat-val">₺<?= number_format($total_rev,0) ?></div>
          <div class="stat-lbl">Total Revenue</div>
        </div>
        <div class="stat-card">
          <div class="stat-icon si-green">🛒</div>
          <div class="stat-val"><?= $order_count ?></div>
          <div class="stat-lbl">Total Orders</div>
        </div>
        <div class="stat-card">
          <div class="stat-icon si-amber">📦</div>
          <div class="stat-val"><?= $prod_count ?></div>
          <div class="stat-lbl">Products Listed</div>
        </div>
        <div class="stat-card">
          <div class="stat-icon si-purple">⭐</div>
          <div class="stat-val"><?= $rating ?></div>
          <div class="stat-lbl">Shop Rating</div>
        </div>
      </div>

      <div class="recent-orders">
        <div class="ro-hdr">
          <div class="ro-title">Recent Orders</div>
          <a href="mgr_orders.php" style="font-size:.78rem;color:#2DD4BF;text-decoration:none;">View all →</a>
        </div>
        <?php if(empty($recent_orders)): ?>
          <p style="color:var(--muted);font-size:.85rem;padding:20px 0;">No orders yet.</p>
        <?php else: ?>
        <table class="data-table">
          <thead><tr><th>#</th><th>Customer</th><th>Items</th><th>Total</th><th>Status</th></tr></thead>
          <tbody>
            <?php foreach($recent_orders as $o): ?>
            <tr>
              <td style="font-family:'Space Mono',monospace;font-size:.75rem;color:var(--muted)">#<?= $o["id"] ?></td>
              <td style="font-weight:500"><?= htmlspecialchars($o["cname"]) ?></td>
              <td style="color:var(--muted)"><?= $o["items"] ?></td>
              <td style="font-weight:700">₺<?= number_format($o["total"],2) ?></td>
              <td><?= statusBadge($o["status"]) ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        <?php endif; ?>
      </div>
    </div>
  </main>
</div>
</body>
</html>