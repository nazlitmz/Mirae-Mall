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

if(isset($_POST["update_status"])){
    $oid = intval($_POST["order_id"]);
    $new_status = $_POST["new_status"];
    if($new_status == "pending" || $new_status == "accepted" || $new_status == "being_prepared" || $new_status == "shipped" || $new_status == "delivered" || $new_status == "canceled"){
        mysqli_query($conn,"UPDATE orders SET status='$new_status' WHERE id='$oid'");
    }
    header("Location: mgr_orders.php");
}

$orders = [];
if($shop_id != 0){
    $sql = "SELECT DISTINCT o.id, o.status, o.total, o.tax, u.name as cname
            FROM orders o
            JOIN order_line ol ON ol.order_id = o.id
            JOIN products p ON ol.product_id = p.id
            JOIN users u ON u.id = o.customer_id
            WHERE p.shop_id='$shop_id' AND o.status != 'delivered'
            ORDER BY o.id DESC";
    $res = mysqli_query($conn, $sql);
    while($row = mysqli_fetch_assoc($res)){
        $sql2 = "SELECT p.name, ol.amount FROM order_line ol JOIN products p ON ol.product_id=p.id
                 WHERE ol.order_id='{$row['id']}' AND p.shop_id='$shop_id'";
        $ir = mysqli_query($conn, $sql2);
        $items = [];
        while($i = mysqli_fetch_assoc($ir)) $items[] = htmlspecialchars($i["name"])." ×".$i["amount"];
        $row["items"] = implode(", ", $items);
        $orders[] = $row;
    }
}

function sBadge($s){
    $map = [
        "pending"       =>"b-amber",
        "accepted"      =>"b-blue",
        "being_prepared"=>"b-teal",
        "shipped"       =>"b-purple",
        "delivered"     =>"b-green",
        "canceled"      =>"b-red"
    ];
    $labels = [
        "pending"       =>"Pending",
        "accepted"      =>"Accepted",
        "being_prepared"=>"Being Prepared",
        "shipped"       =>"Shipped",
        "delivered"     =>"Delivered",
        "canceled"      =>"Canceled"
    ];
    if(isset($map[$s])){
        $cls = $map[$s];
    } else {
        $cls = "b-teal";
    }
    if(isset($labels[$s])){
        $lbl = $labels[$s];
    } else {
        $lbl = $s;
    }
    return "<span class='badge $cls'>● $lbl</span>";
}
?>
<!DOCTYPE html><html lang="en"><head>
<meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1.0"/>
<title>Mirae Manager — Orders</title>
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Space+Mono&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{margin:0;padding:0;box-sizing:border-box;}
:root{--bg:#060810;--surface:rgba(255,255,255,.03);--surface2:rgba(255,255,255,.06);--border:rgba(255,255,255,.07);--border2:rgba(255,255,255,.13);--text:#E8EAF6;--muted:rgba(232,234,246,.45);--muted2:rgba(232,234,246,.22);--grad:linear-gradient(135deg,#0D9488,#0EA5E9,#6366F1);--grad-text:linear-gradient(90deg,#2DD4BF,#38BDF8);--nav-w:240px;--topbar-h:64px;}
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
.breadcrumb strong{color:var(--text);}
.page-badge{display:inline-flex;align-items:center;gap:5px;padding:3px 11px;border-radius:100px;font-size:.65rem;font-weight:600;letter-spacing:.12em;text-transform:uppercase;background:rgba(13,148,136,.12);border:1px solid rgba(13,148,136,.3);color:#2DD4BF;}
.mgr-chip{display:flex;align-items:center;gap:8px;padding:5px 13px;border-radius:100px;background:var(--surface2);border:1px solid var(--border2);font-size:.8rem;font-weight:500;}
.mgr-av{width:26px;height:26px;border-radius:50%;background:var(--grad);display:flex;align-items:center;justify-content:center;font-size:.65rem;font-weight:700;color:#fff;}
.content{flex:1;padding:30px;}
.page-title{font-size:1.5rem;font-weight:800;margin-bottom:20px;}
.page-title span{background:var(--grad-text);-webkit-background-clip:text;-webkit-text-fill-color:transparent;}
.table-card{background:var(--surface);border:1px solid var(--border);border-radius:15px;overflow:hidden;}
.data-table{width:100%;border-collapse:collapse;}
.data-table th{font-size:.65rem;font-weight:600;letter-spacing:.12em;text-transform:uppercase;color:var(--muted2);padding:13px 18px;text-align:left;border-bottom:1px solid var(--border);}
.data-table td{padding:12px 18px;border-bottom:1px solid var(--border);vertical-align:middle;font-size:.845rem;}
.data-table tbody tr:last-child td{border-bottom:none;}
.data-table tbody tr:hover{background:rgba(255,255,255,.02);}
.badge{display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:100px;font-size:.68rem;font-weight:500;}
.b-green  {background:rgba(16,185,129,.1);border:1px solid rgba(16,185,129,.25);color:#34D399;}
.b-amber  {background:rgba(245,158,11,.1);border:1px solid rgba(245,158,11,.25);color:#FCD34D;}
.b-red    {background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.25);color:#FCA5A5;}
.b-blue   {background:rgba(59,130,246,.1);border:1px solid rgba(59,130,246,.25);color:#93C5FD;}
.b-teal   {background:rgba(13,148,136,.1);border:1px solid rgba(13,148,136,.25);color:#2DD4BF;}
.b-purple {background:rgba(139,92,246,.1);border:1px solid rgba(139,92,246,.25);color:#c4b5fd;}
.status-sel{background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.12);color:var(--text);border-radius:7px;padding:5px 8px;font-family:'Outfit',sans-serif;font-size:.78rem;cursor:pointer;}
.status-sel option{background:#0b0f1e;}
.upd-btn{background:var(--grad);border:none;color:#fff;border-radius:7px;padding:6px 13px;font-family:'Outfit',sans-serif;font-size:.78rem;font-weight:600;cursor:pointer;transition:opacity .2s;}
.upd-btn:hover{opacity:.85;}
</style>
</head><body>
<div class="bg-canvas"><div class="bg-blob bg-blob-1"></div><div class="bg-blob bg-blob-2"></div><div class="grid-lines"></div></div>
<div class="shell">
  <aside class="sidebar">
    <a href="mgr_dashboard.php" class="sidebar-brand"><div><div class="sidebar-brand-name">Mirae</div><div class="sidebar-brand-role">◈ Manager Portal</div></div></a>
    <nav class="sidebar-nav">
      <div class="nav-sec">Overview</div>
      <a href="mgr_dashboard.php" class="nav-link"><svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>Home</a>
      <div class="nav-sec">Shop</div>
      <a href="mgr_products.php" class="nav-link"><svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10"/></svg>Products</a>
      <a href="mgr_orders.php" class="nav-link active"><svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>Orders</a>
      <a href="mgr_deliveries.php" class="nav-link"><svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>Deliveries</a>
      <div style="flex:1;"></div>
      <a href="logout.php" class="nav-link logout"><svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>Log Out</a>
    </nav>
    <div class="sidebar-footer"><span>// MANAGER SESSION</span><br><?= $shop_name ?> · Active</div>
  </aside>
  <main class="main">
    <div class="topbar">
      <div style="display:flex;align-items:center;gap:14px;">
        <div class="breadcrumb">Manager / <strong>Orders</strong></div>
        <div class="page-badge">◈ Orders</div>
      </div>
      <div class="mgr-chip"><div class="mgr-av"><?= strtoupper(substr($_SESSION["name"],0,1)) ?></div><?= htmlspecialchars(explode(" ",$_SESSION["name"])[0]) ?></div>
    </div>
    <div class="content">
      <h1 class="page-title">Shop <span>Orders</span></h1>
      <?php if(empty($orders)): ?>
        <p style="color:var(--muted);">No active orders for your shop.</p>
      <?php else: ?>
      <div class="table-card">
        <table class="data-table">
          <thead><tr><th>#</th><th>Customer</th><th>Items</th><th>Total</th><th>Status</th><th>Update</th></tr></thead>
          <tbody>
          <?php foreach($orders as $o): ?>
          <tr>
            <td style="font-family:'Space Mono',monospace;font-size:.75rem;color:var(--muted)">#<?= $o["id"] ?></td>
            <td style="font-weight:500"><?= htmlspecialchars($o["cname"]) ?></td>
            <td style="color:var(--muted);font-size:.8rem"><?= $o["items"] ?></td>
            <td style="font-weight:700">₺<?= number_format($o["total"],2) ?></td>
            <td><?= sBadge($o["status"]) ?></td>
            <td>
              <?php if($o["status"] !== "canceled"): ?>
              <form method="post" style="display:flex;gap:6px;align-items:center;">
                <input type="hidden" name="order_id" value="<?= $o["id"] ?>">
                <select name="new_status" class="status-sel">
                  <option value="pending"        <?= $o["status"]=="pending"        ?"selected":"" ?>>Pending</option>
                  <option value="accepted"       <?= $o["status"]=="accepted"       ?"selected":"" ?>>Accepted</option>
                  <option value="being_prepared" <?= $o["status"]=="being_prepared" ?"selected":"" ?>>Being Prepared</option>
                  <option value="shipped"        <?= $o["status"]=="shipped"        ?"selected":"" ?>>Shipped</option>
                  <option value="delivered"      <?= $o["status"]=="delivered"      ?"selected":"" ?>>Delivered</option>
                  <option value="canceled"       <?= $o["status"]=="canceled"       ?"selected":"" ?>>Canceled</option>
                </select>
                <button type="submit" name="update_status" class="upd-btn">Save</button>
              </form>
              <?php else: ?>
                <span style="color:var(--muted2);font-size:.75rem;">Canceled</span>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>
    </div>
  </main>
</div>
</body></html>