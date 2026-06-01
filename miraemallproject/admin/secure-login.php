<?php
session_start();
include "../connect.php";

$msg = "";

if(isset($_POST["submit"])){
    $email    = mysqli_real_escape_string($conn, $_POST["email"]);
    $password = $_POST["password"];

    $result = mysqli_query($conn,"SELECT * FROM users WHERE email='$email' AND role='admin'");
    if($result && mysqli_num_rows($result) > 0){
        $user = mysqli_fetch_assoc($result);
        if(password_verify($password, $user["password"])){
            $_SESSION["id"]       = $user["id"];
            $_SESSION["name"]     = $user["name"];
            $_SESSION["email"]    = $user["email"];
            $_SESSION["role"]     = "admin";
            $_SESSION["loggedin"] = true;
            header("Location: admin_dashboard.php");
        } else {
            $msg = "Incorrect password.";
        }
    } else {
        $msg = "No admin account found with this email.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Mirae — Admin Access</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">
  <style>
    *,*::before,*::after{margin:0;padding:0;box-sizing:border-box;}
    :root{
      --bg:#03050a;--surface:rgba(255,255,255,.03);--border:rgba(255,255,255,.07);
      --text:#D4D8F0;--muted:rgba(212,216,240,.4);--input-bg:rgba(255,255,255,.04);
      --accent:#5B21B6;--accent2:#7C3AED;
    }
    html,body{height:100%;font-family:'Outfit',sans-serif;background:var(--bg);color:var(--text);overflow:hidden;}
    .bg{position:fixed;inset:0;z-index:0;}
    .bg::before{content:'';position:absolute;inset:0;background:radial-gradient(ellipse 60% 50% at 50% 50%, rgba(91,33,182,.08) 0%,transparent 70%);}
    .scanlines{position:absolute;inset:0;background-image:repeating-linear-gradient(0deg,transparent,transparent 2px,rgba(0,0,0,.15) 2px,rgba(0,0,0,.15) 4px);pointer-events:none;opacity:.4;}
    .page{position:relative;z-index:1;height:100vh;display:flex;flex-direction:column;align-items:center;justify-content:center;padding:24px;}
    .card{width:100%;max-width:400px;background:rgba(255,255,255,.025);border:1px solid var(--border);border-radius:20px;padding:44px 40px;backdrop-filter:blur(24px);box-shadow:0 0 0 1px rgba(91,33,182,.1) inset, 0 40px 80px rgba(0,0,0,.7);animation:up .5s cubic-bezier(.22,1,.36,1) both;}
    @keyframes up{from{opacity:0;transform:translateY(20px)}to{opacity:1;transform:translateY(0)}}
    .header{text-align:center;margin-bottom:32px;}
    .shield{width:56px;height:56px;border-radius:14px;background:linear-gradient(135deg,rgba(91,33,182,.4),rgba(124,58,237,.2));border:1px solid rgba(91,33,182,.4);display:flex;align-items:center;justify-content:center;margin:0 auto 16px;font-size:1.5rem;}
    .admin-title{font-size:1.4rem;font-weight:700;letter-spacing:-.02em;margin-bottom:4px;}
    .admin-sub{font-size:.8rem;color:var(--muted);font-family:'Space Mono',monospace;}
    .field{margin-bottom:16px;}
    .field label{display:block;font-size:.73rem;color:var(--muted);font-weight:500;letter-spacing:.08em;text-transform:uppercase;margin-bottom:7px;font-family:'Space Mono',monospace;}
    .iw{position:relative;}
    .iw svg{position:absolute;left:14px;top:50%;transform:translateY(-50%);opacity:.35;pointer-events:none;}
    .iw input{width:100%;padding:12px 14px 12px 42px;background:var(--input-bg);border:1px solid var(--border);border-radius:10px;color:var(--text);font-family:'Space Mono',monospace;font-size:.85rem;outline:none;transition:border-color .25s,box-shadow .25s;letter-spacing:.04em;}
    .iw input::placeholder{color:rgba(212,216,240,.2);}
    .iw input:focus{border-color:rgba(91,33,182,.6);background:rgba(91,33,182,.06);box-shadow:0 0 0 3px rgba(91,33,182,.12);}
    .eye{position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--muted);opacity:.4;transition:opacity .2s;}
    .eye:hover{opacity:.8;}
    .btn{width:100%;padding:13px;border:none;border-radius:10px;background:linear-gradient(135deg,#5B21B6,#7C3AED);color:#fff;font-family:'Outfit',sans-serif;font-size:.95rem;font-weight:600;cursor:pointer;position:relative;overflow:hidden;transition:opacity .2s,transform .15s,box-shadow .25s;box-shadow:0 4px 20px rgba(91,33,182,.4);margin-top:4px;}
    .btn::before{content:'';position:absolute;inset:0;background:linear-gradient(135deg,rgba(255,255,255,.1) 0%,transparent 60%);pointer-events:none;}
    .btn:hover{opacity:.9;transform:translateY(-1px);box-shadow:0 8px 28px rgba(91,33,182,.5);}
  </style>
</head>
<body>
<div class="bg"><div class="scanlines"></div></div>

<div class="page">
  <div class="card">
    <div class="header">
      <div class="shield"><img src="../customer/logo.png" alt="Mirae" style="width:34px;height:34px;object-fit:contain;display:block;"/></div>
      <h1 class="admin-title">Admin Portal</h1>
      <p class="admin-sub">// MIRAE SYSTEM ACCESS</p>
    </div>

    <?php if(isset($msg) && $msg != ""): ?>
    <div style="background:rgba(220,38,38,.08);border:1px solid rgba(220,38,38,.25);border-radius:10px;padding:10px 14px;font-size:.78rem;color:#FCA5A5;margin-bottom:16px;font-family:'Space Mono',monospace;">
      ⚠ <?php echo $msg; ?>
    </div>
    <?php endif; ?>

    <form method="post" id="adminForm">
      <div class="field">
        <label>Admin ID / Email</label>
        <div class="iw">
          <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zM20 21a8 8 0 10-16 0"/></svg>
          <?php if(isset($_POST["email"])): ?>
          <input type="email" name="email" placeholder="admin@mirae.com" autocomplete="off" value="<?php echo $_POST['email']; ?>"/>
          <?php else: ?>
          <input type="email" name="email" placeholder="admin@mirae.com" autocomplete="off"/>
          <?php endif; ?>
        </div>
      </div>
      <div class="field">
        <label>Password</label>
        <div class="iw">
          <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><rect x="3" y="11" width="18" height="11" rx="2"/><path stroke-linecap="round" d="M7 11V7a5 5 0 0110 0v4"/></svg>
          <input type="password" name="password" id="admin-pw" placeholder="••••••••••••" autocomplete="current-password"/>
          <button type="button" class="eye" onclick="tog('admin-pw')">
            <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
          </button>
        </div>
      </div>
      <button type="submit" name="submit" class="btn">Access Admin Panel →</button>
    </form>
  </div>
</div>

<script>
function tog(id){
  const i = document.getElementById(id);
  i.type = i.type === 'password' ? 'text' : 'password';
}
</script>
</body>
</html>