<?php
include "../connect.php";
include "../session.php";
if($_SESSION["role"] != "customer"){
    header("Location: ../login.php");
}
$cid = $_SESSION["id"];
if(isset($_POST["cancel_order"])){
    $oid = $_POST["order_id"];
    mysqli_query($conn,"UPDATE orders SET status='canceled' WHERE id='$oid' AND customer_id='$cid'");
    header("Location: cust_orders.php");
}
if(isset($_POST["rate_shop"])){
    $shop_id = intval($_POST["shop_id"]);
    $rating  = floatval($_POST["rating"]);
    if($rating >= 1 && $rating <= 5){
        mysqli_query($conn,"UPDATE shops SET rating='$rating' WHERE id='$shop_id'");
    }
    header("Location: cust_orders.php");
}
$orders = [];
$sql = "SELECT o.id, o.status, o.total, o.tax
        FROM orders o
        WHERE o.customer_id = '$cid'
        ORDER BY o.id DESC";
$res = mysqli_query($conn, $sql);
while($o = mysqli_fetch_assoc($res)){
    $items = [];
    $sql2 = "SELECT ol.amount, ol.total_price, p.name as pname, s.name as shop_name, s.id as shop_id, s.rating as shop_rating
             FROM order_line ol
             JOIN products p ON ol.product_id = p.id
             JOIN shops s    ON p.shop_id = s.id
             WHERE ol.order_id = '{$o['id']}'";
    $ir = mysqli_query($conn, $sql2);
    while($i = mysqli_fetch_assoc($ir)) $items[] = $i;
    $o["items"] = $items;
    $orders[]   = $o;
}
$activePage = 'orders';
function statusClass($s){
    $map = [
        'pending'       =>'s-pending',
        'accepted'      =>'s-accepted',
        'being_prepared'=>'s-prepared',
        'shipped'       =>'s-shipped',
        'delivered'     =>'s-delivered',
        'canceled'      =>'s-canceled'
    ];
    if(isset($map[strtolower($s)])){
        return $map[strtolower($s)];
    } else {
        return 's-pending';
    }
}

function statusLabel($s){
    $map = [
        'pending'       =>'Pending',
        'accepted'      =>'Accepted',
        'being_prepared'=>'Being Prepared',
        'shipped'       =>'Shipped',
        'delivered'     =>'Delivered',
        'canceled'      =>'Canceled'
    ];
    if(isset($map[strtolower($s)])){
        return $map[strtolower($s)];
    } else {
        return $s;
    }
}

function canCancel($s){
    if(strtolower($s) == "pending"){
        return true;
    } else {
        return false;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1.0"/>
  <title>Mirae — Orders</title>
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="mirae.css"/>
<style>
.s-prepared{background:rgba(13,148,136,.12);border:1px solid rgba(13,148,136,.3);color:#2DD4BF;}
.s-shipped {background:rgba(139,92,246,.12);border:1px solid rgba(139,92,246,.3);color:#c4b5fd;}
</style>
</head>
<body>
<?php include "nav.php"; ?>

<div class="pg">
  <div class="ptitle">My <span>Orders</span></div>
  <div class="psub"><?= count($orders) ?> order<?= count($orders)!=1?'s':'' ?> placed</div>

  <?php if(empty($orders)): ?>
  <div class="empty">
    <div class="empty-icon">📋</div>
    <div class="empty-msg">No orders yet</div>
    <div class="empty-sub">Start shopping at <a href="cust_home.php">our stores</a>.</div>
  </div>
  <?php else: ?>

  <div class="table-card">
    <table>
      <thead>
        <tr>
          <th>#</th>
          <th>Items</th>
          <th>Subtotal</th>
          <th>Tax</th>
          <th>Total</th>
          <th>Status</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach($orders as $o): ?>
        <tr>
          <td style="font-weight:600;color:var(--mut);">#<?= $o["id"] ?></td>
          <td>
            <?php foreach($o["items"] as $it): ?>
            <div style="font-size:.8rem;margin-bottom:2px;">
              <span style="font-weight:600;"><?= htmlspecialchars($it["pname"]) ?></span>
              <span style="color:var(--mut);"> &times;<?= $it["amount"] ?> — <?= htmlspecialchars($it["shop_name"]) ?></span>
            </div>
            <?php endforeach; ?>
            <?php if(empty($o["items"])): ?><span style="color:var(--mut);font-size:.78rem;">—</span><?php endif; ?>
          </td>
          <td>&#8378;<?= number_format($o["total"] - $o["tax"], 2) ?></td>
          <td style="color:var(--mut);">&#8378;<?= number_format($o["tax"],2) ?></td>
          <td style="font-weight:700;">&#8378;<?= number_format($o["total"],2) ?></td>
          <td>
            <span class="status <?= statusClass($o["status"]) ?>">
              <?= statusLabel($o["status"]) ?>
            </span>
          </td>
          <td>
            <div style="display:flex;gap:6px;align-items:center;flex-wrap:wrap;">
              <form method="post" action="cust_orders.php" style="display:inline;">
                <input type="hidden" name="order_id" value="<?= $o["id"] ?>">
                <button
                  type="submit"
                  name="cancel_order"
                  class="cancel-btn"
                  <?= canCancel($o["status"]) ? '' : 'disabled' ?>
                  <?= canCancel($o["status"]) ? 'onclick="return confirm(\'Cancel this order?\')"' : '' ?>
                >Cancel</button>
              </form>
              <?php if(strtolower($o["status"]) === 'delivered' && !empty($o["items"])): ?>
              <button type="button"
                onclick="openRateModal(<?= $o['items'][0]['shop_id'] ?>, '<?= htmlspecialchars(addslashes($o['items'][0]['shop_name'])) ?>', <?= $o['items'][0]['shop_rating'] ?>)"
                style="padding:5px 12px;border-radius:8px;border:1px solid rgba(245,158,11,.35);background:rgba(245,158,11,.1);color:#FCD34D;font-family:'Outfit',sans-serif;font-size:.75rem;font-weight:600;cursor:pointer;">
                ⭐ Rate Shop
              </button>
              <?php endif; ?>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <?php endif; ?>
</div>

<!-- RATE SHOP MODAL -->
<div id="rateModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.7);backdrop-filter:blur(8px);z-index:300;align-items:center;justify-content:center;">
  <div style="background:#0d1020;border:1px solid rgba(255,255,255,.12);border-radius:20px;padding:32px;width:100%;max-width:380px;text-align:center;">
    <div style="font-size:1.1rem;font-weight:700;color:#E8EAF6;margin-bottom:6px;">Rate Shop</div>
    <div id="rateShopName" style="font-size:.85rem;color:rgba(232,234,246,.5);margin-bottom:22px;"></div>
    <!-- Stars -->
    <div id="starRow" style="display:flex;justify-content:center;gap:10px;margin-bottom:20px;font-size:2rem;cursor:pointer;">
      <span class="star" data-v="1">☆</span>
      <span class="star" data-v="2">☆</span>
      <span class="star" data-v="3">☆</span>
      <span class="star" data-v="4">☆</span>
      <span class="star" data-v="5">☆</span>
    </div>
    <form method="post" id="rateForm">
      <input type="hidden" name="shop_id" id="rateShopId">
      <input type="hidden" name="rating"  id="rateValue" value="0">
      <div style="display:flex;gap:10px;justify-content:center;">
        <button type="button" onclick="closeRateModal()" style="padding:10px 20px;border-radius:10px;border:1px solid rgba(255,255,255,.12);background:rgba(255,255,255,.05);color:rgba(232,234,246,.6);font-family:'Outfit',sans-serif;font-size:.85rem;cursor:pointer;">Cancel</button>
        <button type="submit" name="rate_shop" id="rateSubmit" disabled style="padding:10px 20px;border-radius:10px;border:none;background:linear-gradient(135deg,#F59E0B,#FBBF24);color:#000;font-family:'Outfit',sans-serif;font-size:.85rem;font-weight:700;cursor:pointer;opacity:.5;transition:opacity .2s;">Submit Rating</button>
      </div>
    </form>
  </div>
</div>

<script>
function applyTheme(t){document.body.classList.toggle('light',t==='light');document.getElementById('tbtn').textContent=t==='light'?'☀️':'🌙';localStorage.setItem('mt',t);}
function toggleTheme(){applyTheme(document.body.classList.contains('light')?'dark':'light');}
applyTheme(localStorage.getItem('mt')||'dark');

function openRateModal(shopId, shopName, currentRating){
    document.getElementById('rateShopId').value = shopId;
    document.getElementById('rateShopName').textContent = shopName;
    setStars(currentRating || 0);
    document.getElementById('rateModal').style.display = 'flex';
}
function closeRateModal(){
    document.getElementById('rateModal').style.display = 'none';
}
function setStars(val){
    document.getElementById('rateValue').value = val;
    document.querySelectorAll('.star').forEach(function(s){
        s.textContent = parseFloat(s.dataset.v) <= val ? '★' : '☆';
        s.style.color = parseFloat(s.dataset.v) <= val ? '#FBBF24' : 'rgba(232,234,246,.3)';
    });
    var btn = document.getElementById('rateSubmit');
    btn.disabled = val < 1;
    btn.style.opacity = val < 1 ? '.5' : '1';
    btn.style.cursor = val < 1 ? 'not-allowed' : 'pointer';
}
document.querySelectorAll('.star').forEach(function(s){
    s.addEventListener('click', function(){ setStars(parseFloat(this.dataset.v)); });
    s.addEventListener('mouseover', function(){
        document.querySelectorAll('.star').forEach(function(x){
            x.textContent = parseFloat(x.dataset.v) <= parseFloat(s.dataset.v) ? '★' : '☆';
            x.style.color = parseFloat(x.dataset.v) <= parseFloat(s.dataset.v) ? '#FBBF24' : 'rgba(232,234,246,.3)';
        });
    });
    s.addEventListener('mouseout', function(){
        setStars(parseFloat(document.getElementById('rateValue').value));
    });
});
document.addEventListener('keydown', function(e){ if(e.key==='Escape') closeRateModal(); });
</script>
</body>
</html>