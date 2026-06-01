<?php
include "../connect.php";
include "../session.php";
if($_SESSION["role"] != "customer"){
    header("Location: ../login.php");
}
$cid = $_SESSION["id"];
if(isset($_POST["confirm_order"])){
    if(isset($_POST["payment"])){ $payment = $_POST["payment"]; } else { $payment = "cash"; }
    mysqli_query($conn,"UPDATE cart SET payment_method='".mysqli_real_escape_string($conn,$payment)."' WHERE customer_id='$cid'");
    mysqli_query($conn,"DELETE FROM cart WHERE customer_id='$cid'");
    header("Location: cust_orders.php");
}
if(isset($_POST["remove_item"])){
    $cart_id = $_POST["cart_id"];
    $sql = "SELECT * FROM cart WHERE id='$cart_id' AND customer_id='$cid'";
    $result = mysqli_query($conn, $sql);
    $res = mysqli_fetch_assoc($result);
    if($res){
        mysqli_query($conn,"DELETE FROM order_line WHERE id='{$res['order_line_id']}'");
        mysqli_query($conn,"DELETE FROM orders WHERE id='{$res['order_id']}'");
        mysqli_query($conn,"DELETE FROM cart WHERE id='$cart_id'");
    }
    header("Location: cust_cart.php");
}
$cart_items = [];
$sql = "SELECT c.id as cart_id, c.payment_method,
               ol.amount, ol.total_price,
               p.name as pname, p.price as unit_price, p.description,
               s.name as shop_name, s.type as shop_type
        FROM cart c
        JOIN order_line ol ON c.order_line_id = ol.id
        JOIN products p    ON ol.product_id = p.id
        JOIN shops s       ON p.shop_id = s.id
        WHERE c.customer_id = '$cid'";
$res = mysqli_query($conn, $sql);
while($row = mysqli_fetch_assoc($res)) $cart_items[] = $row;
$subtotal = array_sum(array_column($cart_items,'total_price'));
$tax      = $subtotal * 0.18;
$grand    = $subtotal + $tax;
function shopIco($type){
    $t = strtolower($type);
    if(strpos($t,'clothing')!==false) return '👗';
    if(strpos($t,'restaurant')!==false||strpos($t,'food')!==false) return '🍽️';
    if(strpos($t,'electronics')!==false) return '📱';
    if(strpos($t,'coffee')!==false) return '☕';
    return '🏪';
}
$activePage = 'cart';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1.0"/>
  <title>Mirae — Cart</title>
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="mirae.css"/>
</head>
<body>
<?php include "nav.php"; ?>
<div class="pg">
  <div class="ptitle">Your <span>Cart</span></div>
  <div class="psub"><?= count($cart_items) ?> item<?= count($cart_items)!=1?'s':'' ?></div>

  <?php if(isset($confirmed)): ?>
  <div class="alert alert-ok">&#10003; Order confirmed! We'll prepare your items shortly.</div>
  <?php endif; ?>

  <?php if(empty($cart_items)): ?>
  <div class="empty">
    <div class="empty-icon">🛒</div>
    <div class="empty-msg">Your cart is empty</div>
    <div class="empty-sub">Browse <a href="cust_home.php">available shops</a> and add something!</div>
  </div>
  <?php else: ?>

  <form method="post" action="cust_cart.php">
  <div class="cart-wrap">

   
    <div class="cart-card">
      <div class="cart-hdr">Cart Items</div>
      <?php foreach($cart_items as $item): ?>
      <div class="cart-row">
        <div class="cr-icon"><?= shopIco($item["shop_type"]) ?></div>
        <div class="cr-info">
          <div class="cr-name"><?= htmlspecialchars($item["pname"]) ?></div>
          <div class="cr-shop"><?= htmlspecialchars($item["shop_name"]) ?></div>
        </div>
        <div class="cr-qty">
          <div class="qval">x<?= $item["amount"] ?></div>
        </div>
        <div class="cr-price">&#8378;<?= number_format($item["total_price"],2) ?></div>
       
        <form method="post" action="cust_cart.php" style="margin-left:8px;">
          <input type="hidden" name="cart_id" value="<?= $item["cart_id"] ?>">
          <button type="submit" name="remove_item" style="width:26px;height:26px;border-radius:7px;border:1px solid rgba(239,68,68,.3);background:rgba(239,68,68,.08);cursor:pointer;color:#f87171;font-size:.85rem;display:flex;align-items:center;justify-content:center;transition:all .17s;" title="Remove">&times;</button>
        </form>
      </div>
      <?php endforeach; ?>
    </div>

    <div class="summary-card">
      <div class="sum-title">Order Summary</div>
      <div class="sum-row"><span>Subtotal</span><span>&#8378;<?= number_format($subtotal,2) ?></span></div>
      <div class="sum-row"><span>Tax (18%)</span><span>&#8378;<?= number_format($tax,2) ?></span></div>
      <div class="sum-row total"><span>Total</span><span>&#8378;<?= number_format($grand,2) ?></span></div>
      <div class="sum-note">Tax is calculated at 18% on the subtotal. Final amounts are shown inclusive of tax.</div>
      <select class="pay-sel" name="payment">
        <option value="cash">Cash on Delivery</option>
        <option value="card">Credit / Debit Card</option>
        <option value="online">Online Payment</option>
      </select>
      <button type="submit" name="confirm_order" class="confirm-btn">Confirm Order &#10003;</button>
    </div>

  </div>
  </form>

  <?php endif; ?>
</div>

<script>
function applyTheme(t){document.body.classList.toggle('light',t==='light');document.getElementById('tbtn').textContent=t==='light'?'☀️':'🌙';localStorage.setItem('mt',t);}
function toggleTheme(){applyTheme(document.body.classList.contains('light')?'dark':'light');}
applyTheme(localStorage.getItem('mt')||'dark');
</script>
</body>
</html>