<?php
if(!isset($activePage)) $activePage = '';

$sql = "SELECT SUM(ol.amount) as total FROM cart c JOIN order_line ol ON c.order_line_id = ol.id WHERE c.customer_id = '{$_SESSION['id']}'";
$result = mysqli_query($conn, $sql);
$cc_row = mysqli_fetch_assoc($result);
if($cc_row["total"] != ""){
    $nav_cart_count = intval($cc_row["total"]);
} else {
    $nav_cart_count = 0;
}

$nav_items = [
  ['href'=>'cust_home.php',   'key'=>'home',    'label'=>'Home'],
  ['href'=>'cust_cart.php',   'key'=>'cart',    'label'=>'Cart'],
  ['href'=>'cust_orders.php', 'key'=>'orders',  'label'=>'Orders'],
  ['href'=>'cust_account.php','key'=>'account', 'label'=>'Account'],
];
$svgs = [
  'home'    => '<path stroke-linecap="round" d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><polyline stroke-linecap="round" points="9 22 9 12 15 12 15 22"/>',
  'cart'    => '<path stroke-linecap="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>',
  'orders'  => '<path stroke-linecap="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>',
  'account' => '<path stroke-linecap="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>',
];
?>
<nav class="topnav">
  <a href="cust_home.php" class="brand">
    <svg width="28" height="28" viewBox="0 0 28 28" fill="none" xmlns="http://www.w3.org/2000/svg" style="flex-shrink:0;display:block;border-radius:6px;">
      <rect width="28" height="28" rx="7" fill="url(#mg)"/>
      <text x="14" y="19" text-anchor="middle" font-family="Outfit,sans-serif" font-weight="800" font-size="14" fill="white">M</text>
      <defs><linearGradient id="mg" x1="0" y1="0" x2="28" y2="28" gradientUnits="userSpaceOnUse"><stop stop-color="#6366f1"/><stop offset="1" stop-color="#8b5cf6"/></linearGradient></defs>
    </svg>
    <span class="brand-text">Mirae</span>
  </a>

  <div class="nav-links">
    <?php foreach($nav_items as $item): ?>
    <?php $active = ($activePage === $item['key']); ?>
    <a href="<?= $item['href'] ?>" class="nl<?= $active ? ' on' : '' ?>">
      <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><?= $svgs[$item['key']] ?></svg>
      <?= $item['label'] ?><?php if($item['key']==='cart' && $nav_cart_count>0): ?>&nbsp;<span class="cbadge"><?= $nav_cart_count ?></span><?php endif; ?>
    </a>
    <?php endforeach; ?>

    <a href="logout.php" class="nl logout">
      <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
      Log Out
    </a>
  </div>

  <div class="nav-right">
    <button class="tbtn" onclick="toggleTheme()" id="tbtn" aria-label="Toggle theme">🌙</button>
    <div class="uchip">
      <div class="uav"><?= strtoupper(substr($_SESSION["name"],0,1)) ?></div>
      <?= htmlspecialchars(explode(" ",$_SESSION["name"])[0]) ?>
    </div>
  </div>
</nav>