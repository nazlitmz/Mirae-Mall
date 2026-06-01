<?php 
include "connect.php";

if(isset($_POST["submit"])){
    $email    = $_POST["email"];
    $password = $_POST["password"];

    $sql = "SELECT * from users where email = '$email'";

    $result = mysqli_query($conn, $sql);
    if($result && mysqli_num_rows($result) > 0){
        $user = mysqli_fetch_assoc($result);
        if(password_verify($password, $user["password"])){
            session_start();
            $_SESSION["id"]       = $user["id"];
            $_SESSION["name"]     = $user["name"];
            $_SESSION["email"]    = $user["email"];
            $_SESSION["role"]     = $user["role"];
            $_SESSION["loggedin"] = true;

            if($user["role"] == "admin"){
                header("Location: admin/admin_dashboard.php");
            }
            elseif($user["role"] == "manager"){
                header("Location: manager/mgr_dashboard.php");
            }
            else{
                header("Location: customer/cust_home.php");
            }
            exit();
        }
        else{
            $msg = "Incorrect password.";
        }
    }
    else{
        $msg = "No account found with this email.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Mirae — Sign In</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    *, *::before, *::after { margin:0; padding:0; box-sizing:border-box; }
    :root {
      --purple:#6B3FEC; --blue:#2563EB; --cyan:#06B6D4;
      --grad:linear-gradient(135deg,#6B3FEC 0%,#2563EB 50%,#06B6D4 100%);
      --grad-text:linear-gradient(90deg,#7C5CFC,#38BDF8);
      --bg:#060810; --border:rgba(255,255,255,0.08);
      --text:#E8EAF6; --muted:rgba(232,234,246,0.45);
      --input-bg:rgba(255,255,255,0.05);
    }
    html,body { height:100%; font-family:'Outfit',sans-serif; background:var(--bg); color:var(--text); overflow:hidden; }

    .bg { position:fixed; inset:0; z-index:0; overflow:hidden; }
    .bg::before { content:''; position:absolute; width:800px; height:800px; border-radius:50%; background:radial-gradient(circle,rgba(107,63,236,.18) 0%,transparent 70%); top:-200px; left:-200px; animation:dA 12s ease-in-out infinite alternate; }
    .bg::after  { content:''; position:absolute; width:600px; height:600px; border-radius:50%; background:radial-gradient(circle,rgba(6,182,212,.14) 0%,transparent 70%); bottom:-150px; right:-100px; animation:dB 15s ease-in-out infinite alternate; }
    .grid { position:absolute; inset:0; background-image:linear-gradient(rgba(107,63,236,.04) 1px,transparent 1px),linear-gradient(90deg,rgba(107,63,236,.04) 1px,transparent 1px); background-size:60px 60px; }
    @keyframes dA { from{transform:translate(0,0)} to{transform:translate(40px,60px)} }
    @keyframes dB { from{transform:translate(0,0)} to{transform:translate(-50px,-40px)} }

    .page { position:relative; z-index:1; min-height:100vh; display:flex; }

    /* ── PNG LOGO (shared) ── */
    .logo-img {
      width:36px; height:36px; object-fit:contain; flex-shrink:0;
    }
    .logo-text {
      font-size:1.2rem; font-weight:700; letter-spacing:.04em;
      background:var(--grad-text);
      -webkit-background-clip:text; -webkit-text-fill-color:transparent;
      background-clip:text;
    }

    /* LEFT PANEL */
    .left {
      display:none; flex:0 0 52%; flex-direction:column;
      justify-content:center; align-items:flex-start;
      padding:0 72px; position:relative; overflow:hidden;
    }
    @media(min-width:900px){ .left{ display:flex; } }

    .left::before {
      content:''; position:absolute;
      width:600px; height:600px; border-radius:50%;
      background:radial-gradient(circle, rgba(107,63,236,.22) 0%, transparent 65%);
      top:-120px; left:-140px; pointer-events:none;
      animation:glowPulse 6s ease-in-out infinite alternate;
    }
    .left::after {
      content:''; position:absolute;
      width:400px; height:400px; border-radius:50%;
      background:radial-gradient(circle, rgba(6,182,212,.14) 0%, transparent 65%);
      bottom:60px; left:200px; pointer-events:none;
      animation:glowPulse 8s ease-in-out infinite alternate-reverse;
    }
    @keyframes glowPulse { from{opacity:.6;transform:scale(1)} to{opacity:1;transform:scale(1.08)} }

    /* topbar brand (left panel) */
    .brand {
      position:absolute; top:36px; left:72px;
      display:flex; align-items:center; gap:10px;
      text-decoration:none; z-index:2;
    }

    .eyebrow {
      display:inline-flex; align-items:center; gap:8px;
      font-size:.68rem; font-weight:600; letter-spacing:.18em; text-transform:uppercase;
      color:rgba(167,139,250,.7);
      margin-bottom:28px; position:relative; z-index:2;
    }
    .eyebrow-line { width:28px; height:1px; background:linear-gradient(90deg, rgba(107,63,236,.8), rgba(6,182,212,.4)); }

    .hero-h {
      font-size:clamp(2.8rem,4.2vw,4.4rem); font-weight:800;
      line-height:1.05; letter-spacing:-.035em;
      margin-bottom:28px; position:relative; z-index:2; max-width:520px;
    }
    .hero-h .plain { color:var(--text); display:block; }
    .hero-h .grad {
      display:block;
      background:linear-gradient(95deg, #818CF8 0%, #38BDF8 55%, #06B6D4 100%);
      -webkit-background-clip:text; -webkit-text-fill-color:transparent; background-clip:text;
    }

    .hero-p {
      font-size:1.05rem; color:rgba(232,234,246,.5);
      font-weight:300; line-height:1.75;
      max-width:400px; margin-bottom:52px;
      position:relative; z-index:2; letter-spacing:.01em;
    }

    .hero-rule { width:48px; height:2px; background:linear-gradient(90deg,#6B3FEC,#06B6D4); border-radius:2px; margin-bottom:36px; position:relative; z-index:2; opacity:.5; }

    .ftag-row { display:flex; gap:10px; flex-wrap:wrap; position:relative; z-index:2; }
    .ftag {
      display:inline-flex; align-items:center; gap:7px;
      padding:8px 14px; border-radius:10px;
      border:1px solid rgba(255,255,255,.07);
      background:rgba(255,255,255,.04); backdrop-filter:blur(8px);
      font-size:.75rem; color:rgba(232,234,246,.5);
      font-weight:500; letter-spacing:.04em;
      transition:border-color .3s, color .3s, background .3s;
    }
    .ftag:hover { border-color:rgba(107,63,236,.4); color:var(--text); background:rgba(107,63,236,.08); }
    .ftag-icon { font-size:.85rem; opacity:.7; }

    /* RIGHT / FORM */
    .right { flex:1; display:flex; flex-direction:column; justify-content:center; align-items:center; padding:40px 24px; }
    .card {
      width:100%; max-width:420px;
      background:rgba(255,255,255,.04); border:1px solid var(--border);
      border-radius:24px; padding:44px 40px;
      backdrop-filter:blur(20px);
      box-shadow:0 30px 80px rgba(0,0,0,.5),0 0 0 1px rgba(255,255,255,.04) inset;
      animation:up .6s cubic-bezier(.22,1,.36,1) both;
    }
    @keyframes up { from{opacity:0;transform:translateY(30px)} to{opacity:1;transform:translateY(0)} }

    /* Mobile logo (shown only on small screens) */
    .mob-logo {
      display:flex; align-items:center; gap:10px;
      justify-content:center; margin-bottom:32px;
    }
    @media(min-width:900px){ .mob-logo{ display:none; } }

    .form-title { font-size:1.7rem; font-weight:700; margin-bottom:6px; letter-spacing:-.02em; }
    .form-sub { font-size:.875rem; color:var(--muted); margin-bottom:28px; }
    .form-sub a { color:#a78bfa; text-decoration:none; font-weight:500; }
    .form-sub a:hover { color:#c4b5fd; }



    .field { margin-bottom:18px; }
    .field label { display:block; font-size:.78rem; color:var(--muted); font-weight:500; letter-spacing:.06em; text-transform:uppercase; margin-bottom:8px; }
    .iw { position:relative; }
    .iw>svg:first-of-type { position:absolute; left:14px; top:50%; transform:translateY(-50%); opacity:.4; pointer-events:none; }
    .iw input { width:100%; padding:13px 44px 13px 42px; background:var(--input-bg); border:1px solid var(--border); border-radius:12px; color:var(--text); font-family:'Outfit',sans-serif; font-size:.925rem; outline:none; transition:border-color .25s,box-shadow .25s; }
    .iw input::placeholder { color:rgba(232,234,246,.25); }
    .iw input:focus { border-color:rgba(107,63,236,.6); background:rgba(107,63,236,.06); box-shadow:0 0 0 3px rgba(107,63,236,.12); }
    .eye { position:absolute; right:10px; top:50%; transform:translateY(-50%); background:none; border:none; cursor:pointer; color:var(--muted); opacity:.55; transition:opacity .2s; z-index:5; pointer-events:all; padding:5px; line-height:0; }
    .eye svg { display:block; width:16px; height:16px; }
    .eye:hover { opacity:1; }
    .btn { display:block; width:100%; padding:14px; border:none; border-radius:12px; background:var(--grad); color:#fff; font-family:'Outfit',sans-serif; font-size:.975rem; font-weight:600; cursor:pointer; position:relative; overflow:hidden; transition:opacity .2s,transform .15s,box-shadow .25s; box-shadow:0 4px 24px rgba(107,63,236,.35); }
    .btn::before { content:''; position:absolute; inset:0; background:linear-gradient(135deg,rgba(255,255,255,.15) 0%,transparent 60%); }
    .btn:hover { opacity:.9; transform:translateY(-1px); box-shadow:0 8px 32px rgba(107,63,236,.45); }
  </style>
</head>
<body>
<div class="bg"><div class="grid"></div></div>
<div class="page">

  <!-- LEFT PANEL -->
  <div class="left">
    <a href="#" class="brand">
      <img src="customer/logo.png" alt="Mirae" class="logo-img"/>
      <span class="logo-text">Mirae</span>
    </a>

    <div class="eyebrow">
      <div class="eyebrow-line"></div>
      MIRAE MALL
      <div class="eyebrow-line"></div>
    </div>

    <h1 class="hero-h">
      <span class="plain">Beyond Shopping.</span>
      <span class="grad">Into the Future.</span>
    </h1>

    <div class="hero-rule"></div>
    <p class="hero-p">Curated digital experiences for modern lifestyle and commerce.</p>

    <div class="ftag-row">
      <div class="ftag"><span class="ftag-icon">🏪</span>Multi-Store Mall</div>
      <div class="ftag"><span class="ftag-icon">📦</span>Fast Delivery</div>
      <div class="ftag"><span class="ftag-icon">🔒</span>Secure Shopping</div>
      <div class="ftag"><span class="ftag-icon">⭐</span>Top-Rated Shops</div>
    </div>
  </div>

  <!-- RIGHT / FORM -->
  <div class="right">
    <div class="card">

      <!-- Mobile logo (only visible on small screens) -->
      <div class="mob-logo">
        <img src="customer/logo.png" alt="Mirae" class="logo-img"/>
        <span class="logo-text" style="font-size:1.5rem;">Mirae</span>
      </div>

      <h2 class="form-title">Welcome back</h2>
      <p class="form-sub">New here? <a href="signup.php">Create an account →</a></p>

      <?php if (isset($msg)): ?>
      <div style="background:rgba(239,68,68,.12);border:1px solid rgba(239,68,68,.3);border-radius:10px;padding:10px 14px;font-size:.82rem;color:#FCA5A5;margin-bottom:16px;">
        ⚠ <?php echo $msg; ?>
      </div>
      <?php endif; ?>



      <form action="login.php" method="post">
        <div class="field">
          <label>Email Address</label>
          <div class="iw">
            <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
            <input type="email" name="email" placeholder="you@example.com" autocomplete="email"/>
          </div>
        </div>
        <div class="field">
          <label>Password</label>
          <div class="iw">
            <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><rect x="3" y="11" width="18" height="11" rx="2"/><path stroke-linecap="round" d="M7 11V7a5 5 0 0110 0v4"/></svg>
            <input type="password" id="pw" name="password" placeholder="••••••••" autocomplete="current-password"/>
            <button type="button" class="eye" onclick="tog('pw',this)">
              <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
            </button>
          </div>
        </div>
        <button type="submit" name="submit" class="btn">Sign In to Mirae</button>
      </form>
    </div>
  </div>
</div>

<script>
function tog(id, btn) {
  var inp = document.getElementById(id);
  if (!inp) return;
  var show = (inp.type === 'text');
  inp.type = show ? 'password' : 'text';
  btn.innerHTML = show
    ? '<svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>'
    : '<svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19m-6.72-1.07a3 3 0 11-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23" stroke-linecap="round"/></svg>';
  btn.style.opacity = show ? '0.55' : '0.9';
}
</script>
</body>
</html>