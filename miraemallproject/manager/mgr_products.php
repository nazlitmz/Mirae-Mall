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

if(isset($_POST["add_product"]) && $shop_id != 0){
    $name  = mysqli_real_escape_string($conn,$_POST["pname"]);
    $desc  = mysqli_real_escape_string($conn,$_POST["pdesc"]);
    $price = floatval($_POST["pprice"]);
    $qty   = intval($_POST["pqty"]);

    mysqli_query($conn,"INSERT INTO products (name,description,price,quantity,shop_id) VALUES ('$name','$desc','$price','$qty','$shop_id')");
    $new_pid = mysqli_insert_id($conn);

    if(isset($_FILES["images"]["name"][0]) && $_FILES["images"]["name"][0] != "" && $new_pid){
        $pics = $_FILES["images"];
        for($i = 0; $i < count($pics["name"]); $i++){
            $file_name = $pics["name"][$i];
            $destination = "./uploads/".$file_name;
            $db_path = "../manager/uploads/".$file_name;
            if(move_uploaded_file($pics["tmp_name"][$i], $destination)){
                $path_safe = mysqli_real_escape_string($conn, $db_path);
                mysqli_query($conn,"INSERT INTO pictures (product_id, path) VALUES ('$new_pid','$path_safe')");
            }
        }
    }
    header("Location: mgr_products.php");
}

if(isset($_POST["delete_pic_action"]) && $shop_id != 0){
    $dpid = intval($_POST["delete_pic"]);
    $sql = "SELECT path FROM pictures WHERE id='$dpid'";
    $result = mysqli_query($conn, $sql);
    $pr = mysqli_fetch_assoc($result);
    if($pr && file_exists($pr["path"])) unlink($pr["path"]);
    mysqli_query($conn,"DELETE FROM pictures WHERE id='$dpid'");
    header("Location: mgr_products.php");
}

if(isset($_POST["edit_product"]) && $shop_id != 0){
    $pid = intval($_POST["edit_pid"]);

    if(isset($_POST["ename"])){ $name = mysqli_real_escape_string($conn,$_POST["ename"]); } else { $name = ""; }
    if(isset($_POST["edesc"])){ $desc = mysqli_real_escape_string($conn,$_POST["edesc"]); } else { $desc = ""; }
    if(isset($_POST["eprice"])){ $price = floatval($_POST["eprice"]); } else { $price = 0; }
    if(isset($_POST["eqty"])){ $qty = intval($_POST["eqty"]); } else { $qty = 0; }

    if($name != ""){
        mysqli_query($conn,"UPDATE products SET name='$name',description='$desc',price='$price',quantity='$qty' WHERE id='$pid' AND shop_id='$shop_id'");
    }

    if(isset($_FILES["eimages"]["name"][0]) && $_FILES["eimages"]["name"][0] != ""){
        $pics = $_FILES["eimages"];
        for($i = 0; $i < count($pics["name"]); $i++){
            $file_name = $pics["name"][$i];
            $destination = "./uploads/".$file_name;
            $db_path = "../manager/uploads/".$file_name;
            if(move_uploaded_file($pics["tmp_name"][$i], $destination)){
                $ps = mysqli_real_escape_string($conn, $db_path);
                mysqli_query($conn,"INSERT INTO pictures (product_id, path) VALUES ('$pid','$ps')");
            }
        }
    }
    header("Location: mgr_products.php");
}

if(isset($_POST["delete_product"])){
    $pid = intval($_POST["product_id"]);
    $sql = "SELECT path FROM pictures WHERE product_id='$pid'";
    $pic_res = mysqli_query($conn, $sql);
    while($pr = mysqli_fetch_assoc($pic_res)){
        if(file_exists($pr["path"])) unlink($pr["path"]);
    }
    mysqli_query($conn,"DELETE FROM pictures WHERE product_id='$pid'");
    mysqli_query($conn,"DELETE FROM products WHERE id='$pid' AND shop_id='$shop_id'");
    header("Location: mgr_products.php");
}

$products = [];
if($shop_id != 0){
    $sql = "SELECT * FROM products WHERE shop_id='$shop_id' ORDER BY id DESC";
    $res = mysqli_query($conn, $sql);
    while($r = mysqli_fetch_assoc($res)){
        $sql2 = "SELECT id, path FROM pictures WHERE product_id='{$r['id']}'";
        $pic_res = mysqli_query($conn, $sql2);
        $r["pics"] = [];
        while($pr = mysqli_fetch_assoc($pic_res)) $r["pics"][] = ["id"=>$pr["id"], "path"=>$pr["path"]];
        $products[] = $r;
    }
}
?>

<!DOCTYPE html><html lang="en"><head>
<meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1.0"/>
<title>Mirae Manager — Products</title>
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Space+Mono&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
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
.sidebar-brand-name{font-size:1rem;font-weight:700;background:var(--grad-text);-webkit-background-clip:text;-webkit-text-fill-color:transparent;}
.sidebar-brand-role{font-size:.6rem;font-weight:600;letter-spacing:.14em;text-transform:uppercase;color:rgba(13,148,136,.8);}
.sidebar-nav{flex:1;padding:14px 10px;display:flex;flex-direction:column;gap:3px;}
.nav-sec{font-size:.58rem;font-weight:600;letter-spacing:.18em;text-transform:uppercase;color:var(--muted2);padding:12px 12px 5px;}
.nav-link{display:flex;align-items:center;gap:11px;padding:10px 13px;border-radius:11px;text-decoration:none;color:var(--muted);font-size:.875rem;font-weight:500;transition:all .2s;border:1px solid transparent;}
.nav-link:hover{color:var(--text);background:var(--surface2);border-color:var(--border);}
.nav-link.active{color:var(--text);background:rgba(13,148,136,.15);border-color:rgba(13,148,136,.35);}
.nav-icon{width:17px;height:17px;opacity:.65;flex-shrink:0;}
.nav-link.active .nav-icon,.nav-link:hover .nav-icon{opacity:1;}
.nav-link.logout{color:rgba(239,68,68,.55);}
.nav-link.logout:hover{color:#F87171;background:rgba(239,68,68,.08);}
.sidebar-footer{padding:14px;border-top:1px solid var(--border);font-size:.68rem;color:var(--muted2);font-family:'Space Mono',monospace;line-height:1.6;}
.sidebar-footer span{color:rgba(13,148,136,.6);}
.main{margin-left:var(--nav-w);flex:1;display:flex;flex-direction:column;}
.topbar{height:var(--topbar-h);display:flex;align-items:center;justify-content:space-between;padding:0 30px;border-bottom:1px solid var(--border);background:rgba(6,8,16,.65);backdrop-filter:blur(20px);position:sticky;top:0;z-index:50;}
.breadcrumb{font-size:.78rem;color:var(--muted);}
.breadcrumb strong{color:var(--text);}
.page-badge{display:inline-flex;align-items:center;gap:5px;padding:3px 11px;border-radius:100px;font-size:.65rem;font-weight:600;text-transform:uppercase;background:rgba(13,148,136,.12);border:1px solid rgba(13,148,136,.3);color:#2DD4BF;}
.mgr-chip{display:flex;align-items:center;gap:8px;padding:5px 13px;border-radius:100px;background:var(--surface2);border:1px solid var(--border2);font-size:.8rem;font-weight:500;}
.mgr-av{width:26px;height:26px;border-radius:50%;background:var(--grad);display:flex;align-items:center;justify-content:center;font-size:.65rem;font-weight:700;color:#fff;}
.content{flex:1;padding:30px;}
.page-hdr{display:flex;align-items:center;justify-content:space-between;margin-bottom:22px;}
.page-title{font-size:1.5rem;font-weight:800;}
.page-title span{background:var(--grad-text);-webkit-background-clip:text;-webkit-text-fill-color:transparent;}
.btn-add{display:inline-flex;align-items:center;gap:7px;padding:10px 18px;border-radius:11px;border:none;background:var(--grad);color:#fff;font-family:'Outfit',sans-serif;font-size:.855rem;font-weight:600;cursor:pointer;transition:opacity .2s;}
.btn-add:hover{opacity:.88;}


.pgrid{display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:18px;}
.pcard{background:var(--surface);border:1px solid var(--border);border-radius:14px;overflow:hidden;display:flex;flex-direction:column;transition:all .22s;}
.pcard:hover{border-color:rgba(13,148,136,.35);transform:translateY(-3px);box-shadow:0 12px 38px rgba(0,0,0,.45);}


.carousel-wrap{position:relative;height:180px;background:linear-gradient(135deg,rgba(13,148,136,.1),rgba(99,102,241,.08));overflow:hidden;}
.carousel-wrap img{width:100%;height:180px;object-fit:cover;}
.carousel-no-img{width:100%;height:180px;display:flex;align-items:center;justify-content:center;font-size:3rem;color:rgba(255,255,255,.15);}
.car-btn{position:absolute;top:50%;transform:translateY(-50%);background:rgba(0,0,0,.45);border:none;color:#fff;width:28px;height:28px;border-radius:50%;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:.75rem;z-index:5;transition:background .2s;}
.car-btn:hover{background:rgba(0,0,0,.7);}
.car-prev{left:8px;}
.car-next{right:8px;}
.car-dots{position:absolute;bottom:7px;left:50%;transform:translateX(-50%);display:flex;gap:4px;}
.car-dot{width:5px;height:5px;border-radius:50%;background:rgba(255,255,255,.4);cursor:pointer;transition:background .2s;}
.car-dot.active{background:#2DD4BF;}


.pcard-body{padding:14px;flex:1;display:flex;flex-direction:column;}
.pcard-name{font-size:.95rem;font-weight:700;margin-bottom:3px;}
.pcard-desc{font-size:.75rem;color:var(--muted);line-height:1.5;margin-bottom:10px;flex:1;}
.pcard-meta{display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;}
.pcard-price{font-size:1.05rem;font-weight:800;background:var(--grad-text);-webkit-background-clip:text;-webkit-text-fill-color:transparent;}
.pcard-qty{font-size:.72rem;color:var(--muted);}
.del-btn{width:100%;padding:8px;background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.22);color:#FCA5A5;border-radius:8px;font-family:'Outfit',sans-serif;font-size:.8rem;cursor:pointer;transition:all .2s;}
.del-btn:hover{background:rgba(239,68,68,.2);}
.edit-btn{flex:1;padding:8px;background:rgba(13,148,136,.1);border:1px solid rgba(13,148,136,.25);color:#2DD4BF;border-radius:8px;font-family:'Outfit',sans-serif;font-size:.8rem;cursor:pointer;transition:all .2s;white-space:nowrap;}
.edit-btn:hover{background:rgba(13,148,136,.2);}


.mgr-overlay{position:fixed!important;inset:0!important;background:rgba(0,0,0,.82)!important;backdrop-filter:blur(7px);z-index:9999!important;display:flex!important;align-items:center;justify-content:center;opacity:0;pointer-events:none;transition:opacity .25s;}
.mgr-overlay.open{opacity:1!important;pointer-events:all!important;}
.mgr-modal{width:100%!important;max-width:480px!important;background:#0b0f1e!important;border:1px solid rgba(255,255,255,.15)!important;border-radius:18px!important;padding:28px!important;transform:translateY(18px);transition:transform .25s;max-height:90vh;overflow-y:auto;color:#E8EAF6!important;}
.mgr-overlay.open .mgr-modal{transform:translateY(0)!important;}
.mgr-modal-title{font-size:1.1rem;font-weight:700;margin-bottom:20px;}
.form-lbl{display:block;font-size:.7rem;font-weight:500;letter-spacing:.07em;text-transform:uppercase;color:var(--muted);margin-bottom:6px;}
.form-inp,.form-ta{width:100%;padding:10px 13px;background:rgba(255,255,255,.05);border:1px solid var(--border);border-radius:9px;color:var(--text);font-family:'Outfit',sans-serif;font-size:.875rem;outline:none;margin-bottom:14px;}
.form-inp:focus,.form-ta:focus{border-color:rgba(13,148,136,.6);}
.form-ta{resize:vertical;min-height:70px;}
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:12px;}
.file-zone{width:100%;padding:14px;background:rgba(255,255,255,.04);border:1.5px dashed rgba(255,255,255,.15);border-radius:9px;text-align:center;font-size:.8rem;color:var(--muted);cursor:pointer;transition:border-color .2s;margin-bottom:14px;}
.file-zone:hover{border-color:rgba(13,148,136,.5);color:var(--text);}
.file-zone input{display:none;}
.file-hint{font-size:.7rem;color:var(--muted2);margin-top:4px;}
.mgr-modal-foot{display:flex;gap:9px;margin-top:6px;}
.btn-mcancel{flex:1;padding:11px;border-radius:9px;border:1px solid var(--border);background:transparent;color:var(--muted);font-family:'Outfit',sans-serif;font-size:.875rem;cursor:pointer;}
.btn-msave{flex:2;padding:11px;border-radius:9px;border:none;background:var(--grad);color:#fff;font-family:'Outfit',sans-serif;font-size:.875rem;font-weight:600;cursor:pointer;}
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
      <a href="mgr_products.php" class="nav-link active"><svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10"/></svg>Products</a>
      <a href="mgr_orders.php" class="nav-link"><svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>Orders</a>
      <a href="mgr_deliveries.php" class="nav-link"><svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>Deliveries</a>
      <div style="flex:1;"></div>
      <a href="logout.php" class="nav-link logout"><svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>Log Out</a>
    </nav>
    <div class="sidebar-footer"><span>// MANAGER SESSION</span><br><?= $shop_name ?> · Active</div>
  </aside>

  <main class="main">
    <div class="topbar">
      <div style="display:flex;align-items:center;gap:14px;">
        <div class="breadcrumb">Manager / <strong>Products</strong></div>
        <div class="page-badge">◈ Products</div>
      </div>
      <div class="mgr-chip"><div class="mgr-av"><?= strtoupper(substr($_SESSION["name"],0,1)) ?></div><?= htmlspecialchars(explode(" ",$_SESSION["name"])[0]) ?></div>
    </div>

    <div class="content">
      <div class="page-hdr">
        <h1 class="page-title">My <span>Products</span></h1>
        <button class="btn-add" onclick="document.getElementById('addModal').classList.add('open')">+ Add Product</button>
      </div>

      <?php if(empty($products)): ?>
        <p style="color:var(--muted);">No products yet. Add your first product!</p>
      <?php else: ?>
      <div class="pgrid">
        <?php foreach($products as $p): ?>
        <div class="pcard">

          
          <?php if(!empty($p["pics"])): ?>
          <div id="carousel-<?= $p['id'] ?>" class="carousel slide" data-bs-ride="carousel" data-bs-interval="2000">
            <div class="carousel-inner">
              <?php $first_pic = $p["pics"][0]; $first_path = str_replace('../manager/', './', $first_pic['path']); ?>
              <div class="carousel-item active">
                <img src="<?= htmlspecialchars($first_path) ?>" class="d-block w-100" style="height:180px;object-fit:cover;" alt="<?= htmlspecialchars($p['name']) ?>">
              </div>
              <?php for($pi = 1; $pi < count($p["pics"]); $pi++):
                    $pic = $p["pics"][$pi];
                    $display_path = str_replace('../manager/', './', $pic['path']); ?>
              <div class="carousel-item">
                <img src="<?= htmlspecialchars($display_path) ?>" class="d-block w-100" style="height:180px;object-fit:cover;" alt="<?= htmlspecialchars($p['name']) ?>">
              </div>
              <?php endfor; ?>
            </div>
            <?php if(count($p["pics"]) > 1): ?>
            <button class="carousel-control-prev" type="button" data-bs-target="#carousel-<?= $p['id'] ?>" data-bs-slide="prev">
              <span class="carousel-control-prev-icon" aria-hidden="true"></span>
              <span class="visually-hidden">Previous</span>
            </button>
            <button class="carousel-control-next" type="button" data-bs-target="#carousel-<?= $p['id'] ?>" data-bs-slide="next">
              <span class="carousel-control-next-icon" aria-hidden="true"></span>
              <span class="visually-hidden">Next</span>
            </button>
            <?php endif; ?>
          </div>
          <?php else: ?>
          <div class="carousel-no-img">📦</div>
          <?php endif; ?>

          <div class="pcard-body">
            <div class="pcard-name"><?= htmlspecialchars($p["name"]) ?></div>
            <div class="pcard-desc"><?= htmlspecialchars($p["description"]) ?></div>
            <div class="pcard-meta">
              <div class="pcard-price">₺<?= number_format($p["price"],2) ?></div>
              <div class="pcard-qty">Stock: <?= $p["quantity"] ?></div>
            </div>
            <div style="display:flex;gap:8px;">
              <button class="edit-btn" onclick="openEdit(<?= htmlspecialchars(json_encode($p)) ?>)">✏️ Edit</button>
              <form method="post" onsubmit="return confirm('Delete this product and all its images?')" style="flex:1;">
                <input type="hidden" name="product_id" value="<?= $p["id"] ?>">
                <button type="submit" name="delete_product" class="del-btn" style="width:100%;">🗑 Delete</button>
              </form>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>
  </main>
</div>


<div class="mgr-overlay" id="addModal">
  <div class="mgr-modal">
    <div class="mgr-modal-title">Add New Product</div>
    <form method="post" enctype="multipart/form-data">
      <label class="form-lbl">Product Name</label>
      <input type="text" name="pname" class="form-inp" placeholder="e.g. Cotton T-Shirt" required>

      <label class="form-lbl">Description</label>
      <textarea name="pdesc" class="form-ta" placeholder="Brief description..."></textarea>

      <div class="form-row">
        <div>
          <label class="form-lbl">Price (₺)</label>
          <input type="number" name="pprice" class="form-inp" placeholder="0.00" step="0.01" min="0" required>
        </div>
        <div>
          <label class="form-lbl">Stock Quantity</label>
          <input type="number" name="pqty" class="form-inp" placeholder="0" min="0" required>
        </div>
      </div>

      <label class="form-lbl">Product Images</label>
      <label class="file-zone" id="fileZone">
        <input type="file" name="images[]" id="fileInput" multiple accept="image/*" onchange="showFiles(this)">
        <div id="fileLabel">📷 Click to upload images (multiple allowed)</div>
        <div class="file-hint">PNG, JPG, WEBP supported</div>
      </label>

      <div class="mgr-modal-foot">
        <button type="button" class="btn-mcancel" onclick="document.getElementById('addModal').classList.remove('open')">Cancel</button>
        <button type="submit" name="add_product" class="btn-msave">Add Product</button>
      </div>
    </form>
  </div>
</div>

<script>

document.getElementById('addModal').addEventListener('click',function(e){
  if(e.target===this) this.classList.remove('open');
});


function showFiles(input){
  const label = document.getElementById('fileLabel');
  if(input.files.length === 0){
    label.textContent = '📷 Click to upload images (multiple allowed)';
  } else if(input.files.length === 1){
    label.textContent = '✅ ' + input.files[0].name;
  } else {
    label.textContent = '✅ ' + input.files.length + ' images selected';
  }
}


</script>


<div class="mgr-overlay" id="editModal">
  <div class="mgr-modal">
    <div class="mgr-modal-title">Edit Product</div>
    <form method="post" enctype="multipart/form-data" id="editForm">
      <input type="hidden" name="edit_pid" id="edit_pid">

      <label class="form-lbl">Product Name</label>
      <input type="text" name="ename" id="ename" class="form-inp" required>

      <label class="form-lbl">Description</label>
      <textarea name="edesc" id="edesc" class="form-ta"></textarea>

      <div class="form-row">
        <div>
          <label class="form-lbl">Price (₺)</label>
          <input type="number" name="eprice" id="eprice" class="form-inp" step="0.01" min="0" required>
        </div>
        <div>
          <label class="form-lbl">Stock Quantity</label>
          <input type="number" name="eqty" id="eqty" class="form-inp" min="0" required>
        </div>
      </div>

      <label class="form-lbl">Current Images</label>
      <div id="editPicsWrap" style="display:flex;flex-wrap:wrap;gap:8px;margin-bottom:14px;"></div>

     
      <label class="form-lbl">Add New Images</label>
      <label class="file-zone" id="editFileZone">
        <input type="file" name="eimages[]" id="editFileInput" multiple accept="image/*" onchange="showEditFiles(this)">
        <div id="editFileLabel">📷 Click to upload more images</div>
        <div class="file-hint">Existing images will be kept</div>
      </label>

      <div class="mgr-modal-foot">
        <button type="button" class="btn-mcancel" onclick="document.getElementById('editModal').classList.remove('open')">Cancel</button>
        <button type="submit" name="edit_product" class="btn-msave">Save Changes</button>
      </div>
    </form>
  </div>
</div>

<script>
function openEdit(p){
  document.getElementById('edit_pid').value  = p.id;
  document.getElementById('ename').value     = p.name;
  document.getElementById('edesc').value     = p.description || '';
  document.getElementById('eprice').value    = p.price;
  document.getElementById('eqty').value      = p.quantity;
  document.getElementById('editFileLabel').textContent = '📷 Click to upload more images';


  var wrap = document.getElementById('editPicsWrap');
  wrap.innerHTML = '';
  if(p.pics && p.pics.length > 0){
    p.pics.forEach(function(pic){
      var div = document.createElement('div');
      div.style.cssText = 'position:relative;width:70px;height:70px;border-radius:8px;overflow:hidden;border:1px solid rgba(255,255,255,.1);';
      var thumbPath = pic.path.replace('../manager/', './');
      div.innerHTML =
        '<img src="'+thumbPath+'" style="width:100%;height:100%;object-fit:cover;">'
        +'<form method="post" style="position:absolute;top:2px;right:2px;">'
        +'<input type="hidden" name="delete_pic" value="'+pic.id+'">'
        +'<button type="submit" name="delete_pic_action" title="Delete image" '
        +'style="background:rgba(239,68,68,.8);border:none;color:#fff;width:18px;height:18px;border-radius:50%;cursor:pointer;font-size:.6rem;display:flex;align-items:center;justify-content:center;line-height:1;">✕</button>'
        +'</form>';
      wrap.appendChild(div);
    });
  } else {
    wrap.innerHTML = '<span style="font-size:.75rem;color:var(--muted);">No images yet</span>';
  }

  document.getElementById('editModal').classList.add('open');
}

function showEditFiles(input){
  var label = document.getElementById('editFileLabel');
  if(input.files.length === 0) label.textContent = '📷 Click to upload more images';
  else if(input.files.length === 1) label.textContent = '✅ ' + input.files[0].name;
  else label.textContent = '✅ ' + input.files.length + ' images selected';
}

document.getElementById('editModal').addEventListener('click',function(e){
  if(e.target===this) this.classList.remove('open');
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body></html>