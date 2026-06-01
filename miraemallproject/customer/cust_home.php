<?php
include "../connect.php";
include "../session.php";
if($_SESSION["role"] != "customer"){ header("Location: ../login.php"); exit(); }

// Add to cart
if(isset($_POST["add_to_cart"])){
    $pid = $_POST["product_id"];
    $amt = $_POST["amount"];
    $cid = $_SESSION["id"];
    $r   = mysqli_fetch_assoc(mysqli_query($conn,"SELECT * FROM products WHERE id='$pid'"));
    $total = $r["price"] * $amt;
    $tax   = $total * 0.18;
    $grand = $total + $tax;
    mysqli_query($conn,"INSERT INTO orders(status,customer_id,total,tax) VALUES('pending','$cid','$grand','$tax')");
    $oid = mysqli_insert_id($conn);
    mysqli_query($conn,"INSERT INTO order_line(product_id,order_id,amount,total_price) VALUES('$pid','$oid','$amt','$total')");
    $olid = mysqli_insert_id($conn);
    mysqli_query($conn,"INSERT INTO cart(order_id,order_line_id,customer_id,payment_method) VALUES('$oid','$olid','$cid','cash')");
    $added = true;
}

// Load shops + products
$shops = [];
$res   = mysqli_query($conn,"SELECT * FROM shops ORDER BY name");
while($s = mysqli_fetch_assoc($res)){
    $prods = [];
    $pr = mysqli_query($conn,"SELECT * FROM products WHERE shop_id='{$s['id']}'");
    while($p = mysqli_fetch_assoc($pr)){
        $picr = mysqli_query($conn,"SELECT path FROM pictures WHERE product_id='{$p['id']}'");
        $p["pics"] = [];
        while($pic = mysqli_fetch_assoc($picr)) $p["pics"][] = $pic["path"];
        $prods[] = $p;
    }
    $s["products"] = $prods;
    $shops[] = $s;
}

function sico($type){
    $t = strtolower($type);
    if(strpos($t,'clothing')!==false||strpos($t,'fashion')!==false||strpos($t,'men')!==false||strpos($t,'women')!==false) return 'clothing';
    if(strpos($t,'restaurant')!==false||strpos($t,'food')!==false||strpos($t,'cafe')!==false) return 'food';
    if(strpos($t,'electronics')!==false||strpos($t,'tech')!==false||strpos($t,'phone')!==false) return 'electronics';
    if(strpos($t,'coffee')!==false||strpos($t,'drink')!==false) return 'coffee';
    if(strpos($t,'sport')!==false||strpos($t,'gym')!==false) return 'sports';
    if(strpos($t,'book')!==false) return 'books';
    if(strpos($t,'beauty')!==false||strpos($t,'cosmetic')!==false) return 'beauty';
    return 'general';
}
function shopBannerGradient($cat){
    $map = ['clothing'=>'linear-gradient(135deg,#7C3AED,#A855F7)','food'=>'linear-gradient(135deg,#EA580C,#F97316)','electronics'=>'linear-gradient(135deg,#1D4ED8,#3B82F6)','coffee'=>'linear-gradient(135deg,#92400E,#B45309)','sports'=>'linear-gradient(135deg,#065F46,#10B981)','books'=>'linear-gradient(135deg,#1E3A5F,#2563EB)','beauty'=>'linear-gradient(135deg,#BE185D,#EC4899)','general'=>'linear-gradient(135deg,#374151,#6B7280)'];
    return $map[$cat] ?? $map['general'];
}
function shopBannerLabel($cat){
    $map = ['clothing'=>'Fashion','food'=>'Food & Dining','electronics'=>'Electronics','coffee'=>'Coffee & Drinks','sports'=>'Sports','books'=>'Books','beauty'=>'Beauty','general'=>'Shop'];
    return $map[$cat] ?? 'Shop';
}
function shopBannerEmoji($cat){
    $map = ['clothing'=>'👗','food'=>'🍽️','electronics'=>'📱','coffee'=>'☕','sports'=>'⚽','books'=>'📚','beauty'=>'💄','general'=>'🏪'];
    return $map[$cat] ?? '🏪';
}

$activePage = 'home';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1.0"/>
  <title>Mirae — Home</title>
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <!-- Bootstrap ÖNCE -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- mirae.css SONRA — Bootstrap'ı override eder -->
  <link rel="stylesheet" href="mirae.css"/>
  <style>
    /* ── BOOTSTRAP RESET — dark mode zorla ── */
    body:not(.light) {
      background-color: #0d0f1a !important;
      color: #E8EAF6 !important;
    }
    body:not(.light) .pg,
    body:not(.light) #shopsView,
    body:not(.light) #storeView {
      background-color: transparent !important;
      color: #E8EAF6 !important;
    }

    /* ── SCARD DARK MODE ── */
    body:not(.light) .scard {
      background: #161929 !important;
      border-color: rgba(255,255,255,.08) !important;
    }
    body:not(.light) .scard-body {
      background: #161929 !important;
    }
    body:not(.light) .scard-name {
      color: #E8EAF6 !important;
    }
    body:not(.light) .scard-badge {
      background: rgba(99,102,241,.15) !important;
      border: 1px solid rgba(99,102,241,.3) !important;
      color: #c4b5fd !important;
    }
    body:not(.light) .scard-info,
    body:not(.light) .scard-info-row,
    body:not(.light) .scard-stock {
      color: rgba(232,234,246,.5) !important;
    }
    body:not(.light) .scard-btn {
      color: #E8EAF6 !important;
      border-color: rgba(255,255,255,.12) !important;
      background: transparent !important;
    }
    body:not(.light) .scard-btn:hover {
      background: rgba(99,102,241,.12) !important;
      border-color: rgba(99,102,241,.5) !important;
    }
    body:not(.light) .ptitle,
    body:not(.light) .psub {
      color: #E8EAF6 !important;
    }

    /* banner label her zaman beyaz */
    .scard-banner-lbl { color: rgba(255,255,255,.9) !important; }

    /* ── FILTER BAR ── */
    .filter-bar {
      display: flex;
      flex-wrap: wrap;
      gap: 8px;
      margin: 10px 0 24px;
    }
    .flt-btn {
      padding: 7px 18px;
      border-radius: 999px;
      border: 1px solid rgba(255,255,255,.12);
      background: rgba(255,255,255,.05);
      color: rgba(232,234,246,.55);
      font-family: 'Outfit', sans-serif;
      font-size: .82rem;
      font-weight: 500;
      cursor: pointer;
      transition: all .2s;
      white-space: nowrap;
    }
    .flt-btn:hover {
      border-color: rgba(99,102,241,.5);
      color: #E8EAF6;
      background: rgba(99,102,241,.1);
    }
    .flt-btn.active {
      background: #6366f1;
      border-color: #6366f1;
      color: #fff;
      box-shadow: 0 2px 14px rgba(99,102,241,.4);
    }
    body.light .flt-btn {
      border-color: rgba(0,0,0,.12);
      background: rgba(0,0,0,.04);
      color: #555;
    }
    body.light .flt-btn:hover {
      border-color: #6366f1;
      color: #6366f1;
      background: rgba(99,102,241,.08);
    }
    body.light .flt-btn.active {
      background: #6366f1;
      border-color: #6366f1;
      color: #fff;
    }
  </style>
</head>
<body>
<?php include "nav.php"; ?>

<div class="pg">

  <!-- ── SHOPS VIEW ── -->
  <div id="shopsView">
    <div class="ptitle">Available <span>Shops</span></div>
    <div class="psub" id="shopCount"><?= count($shops) ?> store<?= count($shops)!=1?'s':'' ?> open today</div>

    <!-- FILTER BAR -->
    <div class="filter-bar">
      <button class="flt-btn active" data-filter="all" onclick="filterShops('all',this)">All</button>
      <button class="flt-btn" data-filter="clothing" onclick="filterShops('clothing',this)">Clothing</button>
      <button class="flt-btn" data-filter="electronics" onclick="filterShops('electronics',this)">Electronics</button>
      <button class="flt-btn" data-filter="coffee" onclick="filterShops('coffee',this)">Coffeeshop</button>
      <button class="flt-btn" data-filter="food" onclick="filterShops('food',this)">Restaurant</button>
    </div>

    <?php if(isset($added)): ?>
    <div class="alert alert-ok">&#10003; Product added to cart!</div>
    <?php endif; ?>

    <?php if(empty($shops)): ?>
    <div class="empty">
      <div class="empty-icon">🏪</div>
      <div class="empty-msg">No shops available yet</div>
      <div class="empty-sub">Check back soon.</div>
    </div>
    <?php else: ?>
    <div class="sgrid" id="shopGrid">
      <?php foreach($shops as $s):
        $cat  = sico($s["type"]);
        $grad = shopBannerGradient($cat);
        $blbl = shopBannerLabel($cat);
        $bico = shopBannerEmoji($cat);
        $pc   = count($s["products"]);
        $stockClass = 'scard-stock-dot';
        if($pc === 0) $stockClass .= ' none';
        elseif($pc <= 2) $stockClass .= ' low';
      ?>
      <div class="scard" data-cat="<?= $cat ?>">
        <div class="scard-banner" style="background:<?= $grad ?>">
          <div class="scard-banner-icon"><?= $bico ?></div>
          <div class="scard-banner-lbl"><?= $blbl ?></div>
        </div>
        <div class="scard-body">
          <div class="scard-name"><?= htmlspecialchars($s["name"]) ?></div>
          <span class="scard-badge"><?= htmlspecialchars($s["type"]) ?></span>
          <div class="scard-info">
            <?php if(!empty($s["location"])): ?>
            <div class="scard-info-row">
              <span style="font-size:0.72rem;opacity:0.7;flex-shrink:0;">📍</span>
              <?= htmlspecialchars($s["location"]) ?>
            </div>
            <?php endif; ?>
          </div>
          <div class="scard-stock">
            <div class="<?= $stockClass ?>"></div>
            <?= $pc ?> product<?= $pc!=1?'s':'' ?> available
          </div>
          <button class="scard-btn" onclick="viewStore(<?= $s['id'] ?>)">
            View Store &rarr;
          </button>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <div class="empty" id="noResults" style="display:none;">
      <div class="empty-icon">🔍</div>
      <div class="empty-msg">No shops in this category</div>
      <div class="empty-sub">Try a different filter.</div>
    </div>
    <?php endif; ?>
  </div>

  <!-- ── STORE / PRODUCTS VIEW ── -->
  <div id="storeView" style="display:none;">
    <button class="bbtn" onclick="backToShops()">&#8592; Back to Shops</button>
    <div class="shdr">
      <div class="shdr-icon" id="si"></div>
      <div>
        <div class="shdr-name" id="sn"></div>
        <div class="shdr-meta" id="sm"></div>
      </div>
    </div>
    <div class="pgrid" id="prodGrid"></div>
  </div>

</div>

<?php if(isset($added)): ?>
<div class="toast" id="toast" style="opacity:1;transform:translateY(0);">&#10003; Added to cart!</div>
<script>setTimeout(function(){var t=document.getElementById('toast');if(t){t.style.opacity='0';t.style.transform='translateY(8px)';}},2200);</script>
<?php endif; ?>

<script>
const SHOPS = <?= json_encode($shops) ?>;

function filterShops(cat, btn){
    // Active button
    document.querySelectorAll('.flt-btn').forEach(function(b){ b.classList.remove('active'); });
    btn.classList.add('active');

    var cards = document.querySelectorAll('#shopGrid .scard');
    var visible = 0;
    cards.forEach(function(c){
        var show = (cat === 'all' || c.dataset.cat === cat);
        c.style.display = show ? '' : 'none';
        if(show) visible++;
    });

    // Update count text
    var countEl = document.getElementById('shopCount');
    if(countEl) countEl.textContent = visible + ' store' + (visible !== 1 ? 's' : '') + ' open today';

    // Show/hide empty state
    var noRes = document.getElementById('noResults');
    if(noRes) noRes.style.display = visible === 0 ? 'block' : 'none';
}

function applyTheme(t){
    document.body.classList.toggle('light', t==='light');
    document.getElementById('tbtn').textContent = t==='light' ? '☀️' : '🌙';
    localStorage.setItem('mt', t);
}
function toggleTheme(){ applyTheme(document.body.classList.contains('light') ? 'dark' : 'light'); }
applyTheme(localStorage.getItem('mt') || 'dark');

function ico(type){
    var t = (type||'').toLowerCase();
    if(t.indexOf('clothing')>=0||t.indexOf('men')>=0||t.indexOf('women')>=0) return '👗';
    if(t.indexOf('restaurant')>=0||t.indexOf('food')>=0) return '🍽️';
    if(t.indexOf('electronics')>=0||t.indexOf('phone')>=0) return '📱';
    if(t.indexOf('coffee')>=0) return '☕';
    if(t.indexOf('sport')>=0) return '⚽';
    if(t.indexOf('book')>=0) return '📚';
    if(t.indexOf('beauty')>=0) return '💄';
    return '🏪';
}

function viewStore(id){
    var s = SHOPS.find(function(x){ return x.id == id; });
    if(!s) return;
    document.getElementById('shopsView').style.display = 'none';
    document.getElementById('storeView').style.display = 'block';
    document.getElementById('si').textContent = ico(s.type);
    document.getElementById('sn').textContent = s.name;
    document.getElementById('sm').textContent = s.type + (s.location ? ' · '+s.location : '') + ' · ' + s.products.length + ' products';
    var g = document.getElementById('prodGrid');
    g.innerHTML = '';
    if(!s.products || !s.products.length){
        g.innerHTML = '<div class="empty" style="grid-column:1/-1"><div class="empty-icon">📦</div><div class="empty-msg">No products yet</div></div>';
        return;
    }
    s.products.forEach(function(p){
        var inStock = parseInt(p.quantity) > 0;
        var pr = parseFloat(p.price).toLocaleString('tr-TR',{minimumFractionDigits:2});
        var btn = inStock
            ? '<form action="cust_home.php" method="post" class="pbtnw"><input type="hidden" name="product_id" value="'+p.id+'"><input type="hidden" name="amount" value="1"><button type="submit" name="add_to_cart" class="abtn">🛒 Add to Cart</button></form>'
            : '<div class="pbtnw"><button class="abtn" disabled>Out of Stock</button></div>';
        var carId = 'pcar-'+p.id;
        var imgHtml = '';
        if(p.pics && p.pics.length > 0){
            imgHtml += '<div id="'+carId+'" class="carousel slide" data-bs-ride="carousel" data-bs-interval="2000">';
            imgHtml += '<div class="carousel-inner">';
            p.pics.forEach(function(pic, i){
                var path = (pic && pic.path) ? pic.path : pic;
                imgHtml += '<div class="carousel-item'+(i===0?' active':'')+'"><img src="'+path+'" class="d-block w-100" style="height:160px;object-fit:cover;" alt="'+esc(p.name)+'"></div>';
            });
            imgHtml += '</div>';
            if(p.pics.length > 1){
                imgHtml += '<button class="carousel-control-prev" type="button" data-bs-target="#'+carId+'" data-bs-slide="prev"><span class="carousel-control-prev-icon" aria-hidden="true"></span></button>';
                imgHtml += '<button class="carousel-control-next" type="button" data-bs-target="#'+carId+'" data-bs-slide="next"><span class="carousel-control-next-icon" aria-hidden="true"></span></button>';
            }
            imgHtml += '</div>';
        } else {
            imgHtml = '<div class="pimg">'+ico(s.type)+'</div>';
        }
        g.innerHTML +=
            '<div class="pcard">'
            + imgHtml
            + '<div class="pbody">'
                + '<div class="pname">'+esc(p.name)+'</div>'
                + '<div class="pdesc">'+esc(p.description||'')+'</div>'
                + '<div class="pprice">₺'+pr+'</div>'
                + '<div class="pstock">Stock: '+p.quantity+'</div>'
            + '</div>'
            + btn
            + '</div>';
    });
    window.scrollTo({top:0, behavior:'smooth'});
}

function backToShops(){
    document.getElementById('storeView').style.display = 'none';
    document.getElementById('shopsView').style.display = 'block';
}
function esc(s){ var d=document.createElement('div'); d.appendChild(document.createTextNode(s)); return d.innerHTML; }
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>