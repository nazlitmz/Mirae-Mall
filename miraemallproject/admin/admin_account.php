<?php
session_start();
include "../connect.php";

if(!isset($_SESSION["loggedin"]) || $_SESSION["role"] != "admin"){
    header("Location: secure_login.php");
}

$uid = $_SESSION["id"];
$msg = "";

if(isset($_POST["change_password"])){

    if(isset($_POST["current_password"])){ $current = $_POST["current_password"]; } else { $current = ""; }
    if(isset($_POST["new_password"])){ $new_pw = $_POST["new_password"]; } else { $new_pw = ""; }
    if(isset($_POST["confirm_password"])){ $confirm = $_POST["confirm_password"]; } else { $confirm = ""; }

    $sql = "SELECT * FROM users WHERE id='$uid'";
    $result = mysqli_query($conn, $sql);
    $user = mysqli_fetch_assoc($result);

    if(!password_verify($current, $user["password"])){
        $msg = "Current password is incorrect.";
    } elseif(strlen($new_pw) < 8){
        $msg = "New password must be at least 8 characters.";
    } elseif($new_pw != $confirm){
        $msg = "New passwords do not match.";
    } else {
        $hash = password_hash($new_pw, PASSWORD_DEFAULT);
        mysqli_query($conn,"UPDATE users SET `password`='$hash' WHERE id='$uid'");
        $msg = "Password updated successfully.";
    }
}

if(isset($_POST["update_profile"])){
    $name  = mysqli_real_escape_string($conn, $_POST["name"]);
    $email = mysqli_real_escape_string($conn, $_POST["email"]);
    if($name == "" || $email == ""){
        $msg = "Name and email are required.";
    } else {
        mysqli_query($conn,"UPDATE users SET name='$name', email='$email' WHERE id='$uid'");
        $_SESSION["name"]  = $name;
        $_SESSION["email"] = $email;
        $msg = "Profile updated successfully.";
    }
}

$sql = "SELECT * FROM users WHERE id='$uid'";
$result = mysqli_query($conn, $sql);
$user = mysqli_fetch_assoc($result);
?>

<!DOCTYPE html><html lang="en"><head>
<meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1.0"/>
<title>Mirae Admin — Account</title>
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Plus+Jakarta+Sans:wght@700;800&family=Space+Mono&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{margin:0;padding:0;box-sizing:border-box;}
:root{--bg:#060810;--surface:rgba(255,255,255,.03);--surface2:rgba(255,255,255,.055);--border:rgba(255,255,255,.07);--border2:rgba(255,255,255,.12);--text:#E8EAF6;--muted:rgba(232,234,246,.45);--muted2:rgba(232,234,246,.25);--grad:linear-gradient(135deg,#6B3FEC,#2563EB,#06B6D4);--grad-text:linear-gradient(90deg,#818CF8,#38BDF8);--nav-w:240px;--topbar-h:64px;}
html,body{height:100%;font-family:'Outfit',sans-serif;background:var(--bg);color:var(--text);}
.bg-canvas{position:fixed;inset:0;z-index:0;pointer-events:none;}
.bg-blob{position:absolute;border-radius:50%;filter:blur(80px);opacity:.4;}
.bg-blob-1{width:500px;height:500px;background:radial-gradient(circle,rgba(107,63,236,.15),transparent 70%);top:-100px;left:-100px;}
.bg-blob-2{width:350px;height:350px;background:radial-gradient(circle,rgba(6,182,212,.10),transparent 70%);bottom:100px;right:100px;}
.grid-lines{position:absolute;inset:0;background-image:linear-gradient(rgba(107,63,236,.03) 1px,transparent 1px),linear-gradient(90deg,rgba(107,63,236,.03) 1px,transparent 1px);background-size:56px 56px;}
.shell{position:relative;z-index:1;display:flex;min-height:100vh;}
.sidebar{position:fixed;top:0;left:0;width:var(--nav-w);height:100vh;background:rgba(6,8,16,.85);border-right:1px solid var(--border);backdrop-filter:blur(24px);display:flex;flex-direction:column;z-index:100;}
.sidebar-brand{display:flex;align-items:center;gap:10px;padding:20px 24px;border-bottom:1px solid var(--border);text-decoration:none;min-height:var(--topbar-h);}
.sidebar-brand-name{font-size:1.05rem;font-weight:700;letter-spacing:.04em;background:var(--grad-text);-webkit-background-clip:text;-webkit-text-fill-color:transparent;}
.sidebar-brand-role{font-size:.62rem;font-weight:500;letter-spacing:.14em;text-transform:uppercase;color:rgba(239,68,68,.7);}
.sidebar-nav{flex:1;padding:16px 12px;display:flex;flex-direction:column;gap:4px;}
.nav-section-label{font-size:.6rem;font-weight:600;letter-spacing:.18em;text-transform:uppercase;color:var(--muted2);padding:12px 12px 6px;}
.nav-link{display:flex;align-items:center;gap:12px;padding:11px 14px;border-radius:12px;text-decoration:none;color:var(--muted);font-size:.875rem;font-weight:500;transition:all .22s;border:1px solid transparent;}
.nav-link:hover{color:var(--text);background:var(--surface2);border-color:var(--border);}
.nav-link.active{color:var(--text);background:rgba(107,63,236,.15);border-color:rgba(107,63,236,.3);}
.nav-link.logout{color:rgba(239,68,68,.6);}
.nav-link.logout:hover{color:#F87171;background:rgba(239,68,68,.08);border-color:rgba(239,68,68,.2);}
.nav-icon{width:18px;height:18px;opacity:.7;flex-shrink:0;}
.nav-link.active .nav-icon,.nav-link:hover .nav-icon{opacity:1;}
.sidebar-footer{padding:16px;border-top:1px solid var(--border);font-size:.72rem;color:var(--muted2);font-family:'Space Mono',monospace;line-height:1.6;}
.sidebar-footer span{color:rgba(107,63,236,.6);}
.main{margin-left:var(--nav-w);flex:1;display:flex;flex-direction:column;}
.topbar{height:var(--topbar-h);display:flex;align-items:center;justify-content:space-between;padding:0 32px;border-bottom:1px solid var(--border);background:rgba(6,8,16,.6);backdrop-filter:blur(20px);position:sticky;top:0;z-index:50;}
.breadcrumb{font-size:.8rem;color:var(--muted);}
.breadcrumb strong{color:var(--text);font-weight:600;}
.admin-chip{display:flex;align-items:center;gap:8px;padding:6px 14px;border-radius:100px;background:var(--surface2);border:1px solid var(--border2);font-size:.8rem;font-weight:500;}
.admin-avatar{width:26px;height:26px;border-radius:50%;background:var(--grad);display:flex;align-items:center;justify-content:center;font-size:.65rem;font-weight:700;color:#fff;}
.status-dot{width:7px;height:7px;border-radius:50%;background:#10B981;box-shadow:0 0 6px rgba(16,185,129,.6);}
.content{flex:1;padding:32px;max-width:720px;}
.page-title{font-size:1.8rem;font-weight:800;letter-spacing:-.025em;font-family:'Plus Jakarta Sans',sans-serif;margin-bottom:6px;}
.page-title span{background:var(--grad-text);-webkit-background-clip:text;-webkit-text-fill-color:transparent;}
.page-sub{font-size:.875rem;color:var(--muted);margin-bottom:28px;}
.section-card{background:var(--surface);border:1px solid var(--border);border-radius:16px;padding:28px;margin-bottom:20px;}
.section-title{font-size:.72rem;font-weight:600;letter-spacing:.14em;text-transform:uppercase;color:var(--muted2);margin-bottom:20px;display:flex;align-items:center;gap:8px;}
.section-title::after{content:'';flex:1;height:1px;background:rgba(255,255,255,.05);}
.field{margin-bottom:16px;}
.field label{display:block;font-size:.73rem;font-weight:500;letter-spacing:.07em;text-transform:uppercase;color:var(--muted);margin-bottom:7px;}
.field input{width:100%;padding:11px 14px;background:rgba(255,255,255,.05);border:1px solid var(--border);border-radius:10px;color:var(--text);font-family:'Outfit',sans-serif;font-size:.9rem;outline:none;transition:border-color .25s,box-shadow .25s;}
.field input:focus{border-color:rgba(107,63,236,.6);box-shadow:0 0 0 3px rgba(107,63,236,.12);}
.field input[readonly]{opacity:.6;cursor:not-allowed;}
.btn-save{padding:12px 24px;border-radius:10px;border:none;background:var(--grad);color:#fff;font-family:'Outfit',sans-serif;font-size:.9rem;font-weight:600;cursor:pointer;transition:opacity .2s,transform .15s;}
.btn-save:hover{opacity:.9;transform:translateY(-1px);}
.msg{padding:12px 16px;border-radius:10px;font-size:.85rem;margin-bottom:20px;}
.msg.success{background:rgba(16,185,129,.1);border:1px solid rgba(16,185,129,.25);color:#34D399;}
.msg.error{background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.25);color:#FCA5A5;}
.profile-header{display:flex;align-items:center;gap:18px;margin-bottom:24px;}
.profile-av{width:64px;height:64px;border-radius:50%;background:var(--grad);display:flex;align-items:center;justify-content:center;font-size:1.6rem;font-weight:800;color:#fff;flex-shrink:0;}
.profile-name{font-size:1.1rem;font-weight:700;}
.profile-role{font-size:.75rem;color:var(--muted);margin-top:3px;}
</style>
</head><body>
<div class="bg-canvas"><div class="bg-blob bg-blob-1"></div><div class="bg-blob bg-blob-2"></div><div class="grid-lines"></div></div>
<div class="shell">
  <aside class="sidebar">
    <a href="admin_dashboard.php" class="sidebar-brand">
      <div><div class="sidebar-brand-name">Mirae</div><div class="sidebar-brand-role">⬡ Admin Portal</div></div>
    </a>
    <nav class="sidebar-nav">
      <div class="nav-section-label">Main</div>
      <a href="admin_dashboard.php" class="nav-link">
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
      <a href="admin_account.php" class="nav-link active">
        <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
        Account
      </a>
      <a href="admin_logout.php" class="nav-link logout">
        <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
        Log Out
      </a>
    </nav>
    <div class="sidebar-footer"><span>// RESTRICTED ACCESS</span><br>Session active · Logs enabled</div>
  </aside>
  <main class="main">
    <div class="topbar">
      <div style="display:flex;align-items:center;gap:16px;">
        <div class="breadcrumb">Mirae Admin / <strong>Account</strong></div>
      </div>
      <div class="admin-chip">
        <div class="admin-avatar"><?= strtoupper(substr($user['name'],0,1)) ?></div>
        <?= htmlspecialchars(explode(' ',$user['name'])[0]) ?>
        <div class="status-dot"></div>
      </div>
    </div>
    <div class="content">
      <h1 class="page-title">My <span>Account</span></h1>
      <p class="page-sub">Manage your admin profile and security settings.</p>

      <?php if(!empty($msg)): ?>
      <div class="msg <?= $msg_type ?>"><?= htmlspecialchars($msg) ?></div>
      <?php endif; ?>

    
      <div class="profile-header">
        <div class="profile-av"><?= strtoupper(substr($user['name'],0,1)) ?></div>
        <div>
          <div class="profile-name"><?= htmlspecialchars($user['name']) ?></div>
          <div class="profile-role">Administrator · <?= htmlspecialchars($user['email']) ?></div>
        </div>
      </div>

      <div class="section-card">
        <div class="section-title">Profile Information</div>
        <form method="post">
          <div class="field">
            <label>Full Name</label>
            <input type="text" name="name" value="<?= htmlspecialchars($user['name']) ?>" required/>
          </div>
          <div class="field">
            <label>Email Address</label>
            <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required/>
          </div>
          <div class="field">
            <label>Role</label>
            <input type="text" value="Administrator" readonly/>
          </div>
          <button type="submit" name="update_profile" class="btn-save">Save Changes</button>
        </form>
      </div>

      <div class="section-card">
        <div class="section-title">Change Password</div>
        <form method="post">
          <div class="field">
            <label>Current Password</label>
            <input type="password" name="current_password" placeholder="••••••••" required/>
          </div>
          <div class="field">
            <label>New Password</label>
            <input type="password" name="new_password" placeholder="Min. 8 characters" minlength="8" required/>
          </div>
          <div class="field">
            <label>Confirm New Password</label>
            <input type="password" name="confirm_password" placeholder="••••••••" required/>
          </div>
          <button type="submit" name="change_password" class="btn-save">Update Password</button>
        </form>
      </div>

    </div>
  </main>
</div>
</body></html>