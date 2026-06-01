<?php 
include "connect.php";
$msg = "";
if(isset($_POST["submit"])){

    if(isset($_POST["role"])){
        $role = $_POST["role"];
    } else {
        $role = "customer";
    }

    if($role == "manager"){
        if(isset($_POST["m_fname"])){ $fname = $_POST["m_fname"]; } else { $fname = ""; }
        if(isset($_POST["m_lname"])){ $lname = $_POST["m_lname"]; } else { $lname = ""; }
        if(isset($_POST["m_email"])){ $email = $_POST["m_email"]; } else { $email = ""; }
        if(isset($_POST["m_password"])){ $password = $_POST["m_password"]; } else { $password = ""; }
        if(isset($_POST["m_confirm"])){ $confirm = $_POST["m_confirm"]; } else { $confirm = ""; }
        if(isset($_POST["m_address"])){ $address = $_POST["m_address"]; } else { $address = ""; }
    } else {
        if(isset($_POST["c_fname"])){ $fname = $_POST["c_fname"]; } else { $fname = ""; }
        if(isset($_POST["c_lname"])){ $lname = $_POST["c_lname"]; } else { $lname = ""; }
        if(isset($_POST["c_email"])){ $email = $_POST["c_email"]; } else { $email = ""; }
        if(isset($_POST["c_password"])){ $password = $_POST["c_password"]; } else { $password = ""; }
        if(isset($_POST["c_confirm"])){ $confirm = $_POST["c_confirm"]; } else { $confirm = ""; }
        if(isset($_POST["c_address"])){ $address = $_POST["c_address"]; } else { $address = ""; }
    }

    $name = $fname . " " . $lname;

    if($fname == "" || $lname == ""){
        $msg = "First and last name are required.";

    } elseif($email == ""){
        $msg = "Please enter a valid email address.";

    } elseif(strlen($password) < 8){
        $msg = "Password must be at least 8 characters.";

    } elseif($password != $confirm){
        $msg = "Passwords do not match.";

    } else {
        $email_safe = mysqli_real_escape_string($conn, $email);

        $check = mysqli_query($conn, "SELECT id FROM users WHERE email='$email_safe'");
        if(mysqli_num_rows($check) > 0){
            $msg = "An account with this email already exists.";
        } else {
            $hash         = password_hash($password, PASSWORD_DEFAULT);
            $name_safe    = mysqli_real_escape_string($conn, $name);
            $address_safe = mysqli_real_escape_string($conn, $address);

            if($role === "manager"){
                $role_safe = "manager";
            } else {
                $role_safe = "customer";
            }

            $sql = "INSERT INTO users (name, email, `password`, address, role)
                    VALUES ('$name_safe', '$email_safe', '$hash', '$address_safe', '$role_safe')";

            if(mysqli_query($conn, $sql)){
                $user_id = mysqli_insert_id($conn);

                if($role === "manager"){
                    if(isset($_POST["m_shopname"])){ $shop_name = mysqli_real_escape_string($conn, $_POST["m_shopname"]); } else { $shop_name = ""; }
                    if(isset($_POST["m_shoptype"])){ $shop_type = mysqli_real_escape_string($conn, $_POST["m_shoptype"]); } else { $shop_type = ""; }
                    if(isset($_POST["m_shoploc"])){ $shop_loc = mysqli_real_escape_string($conn, $_POST["m_shoploc"]); } else { $shop_loc = ""; }

                    if($shop_type === "clothing"){
                        if(isset($_POST["m_clothing_cat"])){ $cat = $_POST["m_clothing_cat"]; } else { $cat = ""; }
                        if($cat != ""){
                            $shop_type = mysqli_real_escape_string($conn, $cat . " clothing");
                        }
                    }

                    if($shop_name != ""){
                        $sql = "INSERT INTO shops (name, type, location, manager_id)
                                VALUES ('$shop_name', '$shop_type', '$shop_loc', '$user_id')";
                        mysqli_query($conn, $sql);
                    }
                }

                header("Location: login.php");

            } else {
                $msg = "Registration failed. Please try again.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Mirae &mdash; Create Account</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <style>
    *,*::before,*::after{margin:0;padding:0;box-sizing:border-box;}
    :root{
      --purple:#6B3FEC;--blue:#2563EB;--cyan:#06B6D4;
      --grad:linear-gradient(135deg,#6B3FEC 0%,#2563EB 50%,#06B6D4 100%);
      --grad-text:linear-gradient(90deg,#7C5CFC,#38BDF8);
      --bg:#060810;--border:rgba(255,255,255,.08);
      --text:#E8EAF6;--muted:rgba(232,234,246,.45);--input-bg:rgba(255,255,255,.05);
    }
    html,body{font-family:'Outfit',sans-serif;background:var(--bg);color:var(--text);min-height:100%;}
    .bg{position:fixed;inset:0;z-index:0;overflow:hidden;pointer-events:none;}
    .bg::before{content:'';position:absolute;width:700px;height:700px;border-radius:50%;background:radial-gradient(circle,rgba(6,182,212,.16) 0%,transparent 70%);top:-200px;right:-100px;animation:dA 14s ease-in-out infinite alternate;}
    .bg::after{content:'';position:absolute;width:600px;height:600px;border-radius:50%;background:radial-gradient(circle,rgba(107,63,236,.18) 0%,transparent 70%);bottom:-150px;left:-100px;animation:dB 12s ease-in-out infinite alternate;}
    .grid{position:absolute;inset:0;background-image:linear-gradient(rgba(6,182,212,.04) 1px,transparent 1px),linear-gradient(90deg,rgba(6,182,212,.04) 1px,transparent 1px);background-size:60px 60px;}
    @keyframes dA{from{transform:translate(0,0)}to{transform:translate(-40px,50px)}}
    @keyframes dB{from{transform:translate(0,0)}to{transform:translate(40px,-40px)}}

    /* SVG LOGO */
    .logo-img{width:36px;height:36px;object-fit:contain;flex-shrink:0;}
    .logo-text{
      font-size:1.25rem;font-weight:700;letter-spacing:.04em;
      background:var(--grad-text);
      -webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;
    }

    /* TOPBAR */
    .topbar{position:fixed;top:0;left:0;right:0;z-index:10;display:flex;align-items:center;justify-content:space-between;padding:18px 40px;background:rgba(6,8,16,.6);border-bottom:1px solid var(--border);backdrop-filter:blur(20px);}
    .topbar-logo{display:flex;align-items:center;gap:10px;text-decoration:none;}
    .topbar-link{font-size:.85rem;color:var(--muted);text-decoration:none;transition:color .2s;}
    .topbar-link:hover{color:var(--text);}
    .topbar-link strong{color:#a78bfa;}

    .page{position:relative;z-index:1;pointer-events:auto;min-height:100vh;display:flex;flex-direction:column;align-items:center;justify-content:center;padding:40px 24px;}

    /* STEP INDICATOR */
    .steps-wrap{margin-top:90px;margin-bottom:28px;}
    .steps{display:flex;align-items:center;}
    .step{display:flex;flex-direction:column;align-items:center;}
    .step-circle{width:34px;height:34px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:.8rem;font-weight:600;border:2px solid var(--border);color:var(--muted);transition:all .3s;}
    .step.active .step-circle{background:var(--grad);border-color:transparent;color:#fff;box-shadow:0 0 16px rgba(107,63,236,.5);}
    .step.done .step-circle{background:rgba(107,63,236,.3);border-color:rgba(107,63,236,.5);color:#a78bfa;}
    .step-label{font-size:.72rem;color:var(--muted);margin-top:6px;text-align:center;white-space:nowrap;}
    .step.active .step-label{color:var(--text);}
    .step-line{width:60px;height:1px;background:var(--border);margin:0 4px;position:relative;top:-12px;}
    .step-line.filled{background:linear-gradient(90deg,#6B3FEC,#2563EB);}

    /* CARD */
    .card{position:relative;z-index:2;width:100%;max-width:520px;background:rgba(255,255,255,.04);border:1px solid var(--border);border-radius:24px;padding:44px 40px;backdrop-filter:blur(20px);box-shadow:0 30px 80px rgba(0,0,0,.5),0 0 0 1px rgba(255,255,255,.04) inset;animation:slideUp .5s cubic-bezier(.22,1,.36,1) both;}
    @keyframes slideUp{from{opacity:0;transform:translateY(30px)}to{opacity:1;transform:translateY(0)}}
    .panel{display:none;}
    .panel.visible{display:block;animation:fadeIn .35s ease both;}
    @keyframes fadeIn{from{opacity:0;transform:translateY(8px)}to{opacity:1;transform:translateY(0)}}

    .form-title{font-size:1.6rem;font-weight:700;margin-bottom:4px;letter-spacing:-.02em;}
    .form-sub{font-size:.875rem;color:var(--muted);margin-bottom:24px;line-height:1.5;}

    .type-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:24px;}
    .type-card{padding:18px 14px;border:2px solid var(--border);border-radius:14px;cursor:pointer;background:var(--input-bg);transition:all .25s;position:relative;text-align:center;}
    .type-card:hover{border-color:rgba(107,63,236,.4);}
    .type-card.selected{border-color:#7C5CFC;background:rgba(107,63,236,.1);box-shadow:0 0 20px rgba(107,63,236,.15);}
    .type-card input[type=radio]{display:none;}
    .type-icon{font-size:1.6rem;margin-bottom:8px;}
    .type-name{font-size:.85rem;font-weight:600;margin-bottom:2px;}
    .type-desc{font-size:.72rem;color:var(--muted);line-height:1.4;}
    .type-card.selected .type-name{color:#c4b5fd;}
    .check-badge{position:absolute;top:10px;right:10px;width:18px;height:18px;border-radius:50%;background:var(--grad);display:none;align-items:center;justify-content:center;}
    .type-card.selected .check-badge{display:flex;}
    .check-badge svg{width:10px;height:10px;stroke:#fff;fill:none;}

    .field{margin-bottom:16px;}
    .row2{display:grid;grid-template-columns:1fr 1fr;gap:14px;}
    .field label{display:block;font-size:.75rem;color:var(--muted);font-weight:500;letter-spacing:.06em;text-transform:uppercase;margin-bottom:7px;}
    .iw{position:relative;}
    .iw>svg:first-child{position:absolute;left:14px;top:50%;transform:translateY(-50%);opacity:.4;pointer-events:none;}
    .iw input,.iw select{width:100%;padding:12px 44px 12px 42px;background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.08);border-radius:12px;color:#E8EAF6;font-family:'Outfit',sans-serif;font-size:.9rem;outline:none;transition:border-color .25s,box-shadow .25s;appearance:none;}
    .iw input::placeholder{color:rgba(232,234,246,.22);}
    .iw input:focus,.iw select:focus{border-color:rgba(107,63,236,.6);background:rgba(107,63,236,.06);box-shadow:0 0 0 3px rgba(107,63,236,.12);}
    .iw select option{background:#0d1020;color:var(--text);}
    .eye{position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:rgba(232,234,246,.5);opacity:.6;transition:opacity .2s;z-index:10;pointer-events:all;padding:6px;line-height:0;}
    .eye:hover{opacity:1;}
    .eye svg{display:block;width:16px;height:16px;stroke:currentColor;}

    .section-div{display:flex;align-items:center;gap:10px;margin:20px 0 16px;font-size:.75rem;color:var(--muted);letter-spacing:.08em;text-transform:uppercase;}
    .section-div::before,.section-div::after{content:'';flex:1;height:1px;background:var(--border);}

    .info-box{background:rgba(107,63,236,.08);border:1px solid rgba(107,63,236,.25);border-radius:12px;padding:12px 16px;font-size:.82rem;color:#c4b5fd;line-height:1.6;margin-bottom:20px;}
    .info-box strong{color:#a78bfa;display:block;margin-bottom:4px;}

    .sbar{display:flex;gap:4px;margin-top:8px;}
    .seg{flex:1;height:3px;border-radius:2px;background:var(--border);transition:background .3s;}
    .slabel{font-size:.72rem;color:var(--muted);margin-top:5px;}

    .err-msg{background:rgba(248,113,113,.1);border:1px solid rgba(248,113,113,.3);border-radius:10px;padding:10px 14px;font-size:.83rem;color:#fca5a5;margin-bottom:16px;display:none;}
    .err-msg.show{display:block;}

    .btn-row{display:flex;gap:12px;margin-top:8px;}
    .btn-back{flex:0 0 auto;padding:13px 22px;background:var(--input-bg);border:1px solid var(--border);border-radius:12px;color:var(--muted);font-family:'Outfit',sans-serif;font-size:.9rem;cursor:pointer;transition:all .25s;}
    .btn-back:hover{border-color:rgba(107,63,236,.4);color:var(--text);}
    .btn-next,.btn-submit{flex:1;padding:13px;border:none;border-radius:12px;background:var(--grad);color:#fff;font-family:'Outfit',sans-serif;font-size:.95rem;font-weight:600;cursor:pointer;position:relative;overflow:hidden;transition:opacity .2s,transform .15s,box-shadow .25s;box-shadow:0 4px 24px rgba(107,63,236,.35);}
    .btn-next::before,.btn-submit::before{content:'';position:absolute;inset:0;background:linear-gradient(135deg,rgba(255,255,255,.15) 0%,transparent 60%);pointer-events:none;}
    .btn-next:hover,.btn-submit:hover{opacity:.9;transform:translateY(-1px);box-shadow:0 8px 32px rgba(107,63,236,.45);}

    .success-view{text-align:center;padding:20px 0;}
    .success-icon{width:72px;height:72px;border-radius:50%;background:linear-gradient(135deg,rgba(107,63,236,.3),rgba(6,182,212,.3));border:1px solid rgba(107,63,236,.4);display:flex;align-items:center;justify-content:center;margin:0 auto 24px;font-size:2rem;animation:popIn .4s cubic-bezier(.34,1.56,.64,1) both;}
    @keyframes popIn{from{opacity:0;transform:scale(.5)}to{opacity:1;transform:scale(1)}}
    .success-title{font-size:1.6rem;font-weight:700;margin-bottom:10px;}
    .success-sub{font-size:.9rem;color:var(--muted);margin-bottom:10px;line-height:1.6;}
    .success-note{font-size:.8rem;color:rgba(107,63,236,.7);background:rgba(107,63,236,.08);border:1px solid rgba(107,63,236,.2);border-radius:10px;padding:10px 14px;margin-bottom:24px;line-height:1.5;}
    .goto-btn{display:block;width:100%;padding:14px;border-radius:12px;background:var(--grad);color:#fff;text-decoration:none;font-weight:600;font-size:.95rem;text-align:center;box-shadow:0 4px 24px rgba(107,63,236,.35);transition:opacity .2s;}
    .goto-btn:hover{opacity:.85;}

    @media(max-width:480px){.card{padding:32px 24px;}.row2{grid-template-columns:1fr;}}
  </style>
</head>
<body>
<div class="bg"><div class="grid"></div></div>

<nav class="topbar">
  <a href="login.php" class="topbar-logo">
    <img src="customer/logo.png" alt="Mirae" class="logo-img"/>
    <span class="logo-text">Mirae</span>
  </a>
  <a href="login.php" class="topbar-link">Already have an account? <strong>Sign in</strong></a>
</nav>

<div class="page">
  <div class="steps-wrap" id="steps-wrap">
    <div class="steps" id="step-indicators"></div>
  </div>

  <?php if(isset($msg) && $msg !== ''): ?>
  <div style="max-width:520px;width:100%;margin-bottom:16px;">
    <div class="err-msg show"><?= htmlspecialchars($msg) ?></div>
  </div>
  <?php endif; ?>

  <form action="signup.php" method="post" id="signup-form">
  <div class="card">

    <!-- STEP 1: Account Type -->
    <div class="panel visible" id="panel-0">
      <h2 class="form-title">Join Mirae</h2>
      <p class="form-sub">Who are you signing up as?</p>
      <div class="type-grid">
        <label class="type-card selected" onclick="selType(this,'customer')">
          <input type="radio" value="customer" checked/>
          <div class="check-badge"><svg viewBox="0 0 10 10" stroke-width="2"><polyline points="2,5 4.5,7.5 8,3"/></svg></div>
          <div class="type-icon">🛍️</div>
          <div class="type-name">Customer</div>
          <div class="type-desc">Shop from curated stores &amp; track orders</div>
        </label>
        <label class="type-card" onclick="selType(this,'manager')">
          <input type="radio" value="manager"/>
          <div class="check-badge"><svg viewBox="0 0 10 10" stroke-width="2"><polyline points="2,5 4.5,7.5 8,3"/></svg></div>
          <div class="type-icon">🏪</div>
          <div class="type-name">Manager</div>
          <div class="type-desc">Register &amp; manage your shop on Mirae</div>
        </label>
      </div>
      <div class="btn-row">
        <button type="button" class="btn-next" onclick="nextStep()">Continue &rarr;</button>
      </div>
    </div>

    <!-- STEP 2: Customer Info -->
    <div class="panel" id="panel-customer-1">
      <h2 class="form-title">Your Details</h2>
      <p class="form-sub">Basic information for your account.</p>
      <div class="err-msg" id="err-customer-1"></div>
      <div class="row2">
        <div class="field">
          <label>First Name</label>
          <div class="iw">
            <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            <input type="text" id="c-fname" name="c_fname" placeholder="Jane"/>
          </div>
        </div>
        <div class="field">
          <label>Last Name</label>
          <div class="iw">
            <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            <input type="text" id="c-lname" name="c_lname" placeholder="Doe"/>
          </div>
        </div>
      </div>
      <div class="field">
        <label>Email Address</label>
        <div class="iw">
          <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
          <input type="email" id="c-email" name="c_email" placeholder="jane@example.com"/>
        </div>
      </div>
      <div class="field">
        <label>Phone Number</label>
        <div class="iw">
          <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07A19.5 19.5 0 013.07 9.81a19.79 19.79 0 01-3.07-8.68A2 2 0 012 .91h3a2 2 0 012 1.72c.127.96.361 1.903.7 2.81a2 2 0 01-.45 2.11L6.91 8.91a16 16 0 006.06 6.06l1.27-1.27a2 2 0 012.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0122 16.92z"/></svg>
          <input type="tel" id="c-phone" name="c_phone" placeholder="+1 555 000 0000"/>
        </div>
      </div>
      <div class="field">
        <label>Delivery Address</label>
        <div class="iw">
          <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
          <input type="text" id="c-address" name="c_address" placeholder="123 Main St, City, Country"/>
        </div>
      </div>
      <div class="btn-row">
        <button type="button" class="btn-back" onclick="prevStep()">&larr; Back</button>
        <button type="button" class="btn-next" onclick="nextStep()">Continue &rarr;</button>
      </div>
    </div>

    <!-- STEP 2: Manager Info -->
    <div class="panel" id="panel-manager-1">
      <h2 class="form-title">Manager Details</h2>
      <p class="form-sub">Tell us about yourself and your shop.</p>
      <div class="err-msg" id="err-manager-1"></div>
      <div class="info-box">
        <strong>📋 Manager Application</strong>
        Your application will be reviewed by our admin team before your shop goes live.
      </div>
      <div class="section-div">Personal Information</div>
      <div class="row2">
        <div class="field">
          <label>First Name</label>
          <div class="iw">
            <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            <input type="text" id="m-fname" name="m_fname" placeholder="Alex"/>
          </div>
        </div>
        <div class="field">
          <label>Last Name</label>
          <div class="iw">
            <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            <input type="text" id="m-lname" name="m_lname" placeholder="Smith"/>
          </div>
        </div>
      </div>
      <div class="field">
        <label>Email Address</label>
        <div class="iw">
          <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
          <input type="email" id="m-email" name="m_email" placeholder="alex@business.com"/>
        </div>
      </div>
      <div class="field">
        <label>Phone Number</label>
        <div class="iw">
          <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07A19.5 19.5 0 013.07 9.81a19.79 19.79 0 01-3.07-8.68A2 2 0 012 .91h3a2 2 0 012 1.72c.127.96.361 1.903.7 2.81a2 2 0 01-.45 2.11L6.91 8.91a16 16 0 006.06 6.06l1.27-1.27a2 2 0 012.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0122 16.92z"/></svg>
          <input type="tel" id="m-phone" name="m_phone" placeholder="+1 555 000 0000"/>
        </div>
      </div>
      <div class="field">
        <label>Personal Address</label>
        <div class="iw">
          <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
          <input type="text" id="m-address" name="m_address" placeholder="123 Main St, City, Country"/>
        </div>
      </div>
      <div class="section-div">Shop Information</div>
      <div class="field">
        <label>Shop Name</label>
        <div class="iw">
          <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><polyline stroke-linecap="round" points="9 22 9 12 15 12 15 22"/></svg>
          <input type="text" id="m-shopname" name="m_shopname" placeholder="My Awesome Shop"/>
        </div>
      </div>
      <div class="field">
        <label>Shop Type</label>
        <div class="iw">
          <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
          <select id="m-shoptype" name="m_shoptype" onchange="handleShopType(this.value)">
            <option value="" disabled selected>Select shop type&hellip;</option>
            <option value="coffeeshop">Coffeeshop</option>
            <option value="restaurant">Restaurant</option>
            <option value="clothing">Clothing</option>
            <option value="electronics">Electronics</option>
          </select>
        </div>
      </div>
      <div class="field" id="clothing-sub" style="display:none;">
        <label>Clothing Category</label>
        <div class="iw">
          <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
          <select id="m-clothing-cat" name="m_clothing_cat">
            <option value="" disabled selected>Select category&hellip;</option>
            <option value="men">Men's Clothing</option>
            <option value="women">Women's Clothing</option>
            <option value="children">Children's Clothing</option>
            <option value="general">General Clothing</option>
          </select>
        </div>
      </div>
      <div class="field">
        <label>Shop Location / Address</label>
        <div class="iw">
          <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
          <input type="text" id="m-shoploc" name="m_shoploc" placeholder="Mall Level 2, Shop 24"/>
        </div>
      </div>
      <div class="btn-row">
        <button type="button" class="btn-back" onclick="prevStep()">&larr; Back</button>
        <button type="button" class="btn-next" onclick="nextStep()">Continue &rarr;</button>
      </div>
    </div>

    <!-- STEP 3: Security (Customer) -->
    <div class="panel" id="panel-customer-2">
      <h2 class="form-title">Set Password</h2>
      <p class="form-sub">Secure your Mirae account.</p>
      <div class="err-msg" id="err-customer-2"></div>
      <div class="field">
        <label>Username</label>
        <div class="iw">
          <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
          <input type="text" id="c-user" name="c_username" placeholder="janedoe_99"/>
        </div>
      </div>
      <div class="field">
        <label>Password</label>
        <div class="iw">
          <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><rect x="3" y="11" width="18" height="11" rx="2"/><path stroke-linecap="round" d="M7 11V7a5 5 0 0110 0v4"/></svg>
          <input type="password" id="c-pw" name="c_password" placeholder="••••••••" oninput="strength(this.value,'c')"/>
          <button type="button" class="eye" onclick="tog('c-pw')"><svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></button>
        </div>
        <div class="sbar">
          <div class="seg" id="cs1"></div><div class="seg" id="cs2"></div>
          <div class="seg" id="cs3"></div><div class="seg" id="cs4"></div>
        </div>
        <div class="slabel" id="csl">Use 8+ characters, numbers &amp; symbols</div>
      </div>
      <div class="field">
        <label>Confirm Password</label>
        <div class="iw">
          <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><rect x="3" y="11" width="18" height="11" rx="2"/><path stroke-linecap="round" d="M7 11V7a5 5 0 0110 0v4"/></svg>
          <input type="password" id="c-cpw" name="c_confirm" placeholder="••••••••"/>
          <button type="button" class="eye" onclick="tog('c-cpw')"><svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></button>
        </div>
      </div>
      <div class="btn-row">
        <button type="button" class="btn-back" onclick="prevStep()">&larr; Back</button>
        <button type="button" class="btn-submit" onclick="handleSubmit()">Create Account ✦</button>
      </div>
    </div>

    <!-- STEP 3: Security (Manager) -->
    <div class="panel" id="panel-manager-2">
      <h2 class="form-title">Set Password</h2>
      <p class="form-sub">Secure your Manager account.</p>
      <div class="err-msg" id="err-manager-2"></div>
      <div class="field">
        <label>Username</label>
        <div class="iw">
          <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
          <input type="text" id="m-user" name="m_username" placeholder="shopmanager_99"/>
        </div>
      </div>
      <div class="field">
        <label>Password</label>
        <div class="iw">
          <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><rect x="3" y="11" width="18" height="11" rx="2"/><path stroke-linecap="round" d="M7 11V7a5 5 0 0110 0v4"/></svg>
          <input type="password" id="m-pw" name="m_password" placeholder="••••••••" oninput="strength(this.value,'m')"/>
          <button type="button" class="eye" onclick="tog('m-pw')"><svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></button>
        </div>
        <div class="sbar">
          <div class="seg" id="ms1"></div><div class="seg" id="ms2"></div>
          <div class="seg" id="ms3"></div><div class="seg" id="ms4"></div>
        </div>
        <div class="slabel" id="msl">Use 8+ characters, numbers &amp; symbols</div>
      </div>
      <div class="field">
        <label>Confirm Password</label>
        <div class="iw">
          <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><rect x="3" y="11" width="18" height="11" rx="2"/><path stroke-linecap="round" d="M7 11V7a5 5 0 0110 0v4"/></svg>
          <input type="password" id="m-cpw" name="m_confirm" placeholder="••••••••"/>
          <button type="button" class="eye" onclick="tog('m-cpw')"><svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></button>
        </div>
      </div>
      <div class="btn-row">
        <button type="button" class="btn-back" onclick="prevStep()">&larr; Back</button>
        <button type="button" class="btn-submit" onclick="handleSubmit()">Submit Application ✦</button>
      </div>
    </div>

    <!-- SUCCESS panels -->
    <div class="panel" id="panel-customer-success">
      <div class="success-view">
        <div class="success-icon">✦</div>
        <h2 class="success-title">Welcome to Mirae!</h2>
        <p class="success-sub">Your account has been created.<br>Start exploring the digital mall.</p>
        <a href="login.php" class="goto-btn">Go to Sign In &rarr;</a>
      </div>
    </div>

    <div class="panel" id="panel-manager-success">
      <div class="success-view">
        <div class="success-icon">🏪</div>
        <h2 class="success-title">Application Submitted!</h2>
        <p class="success-sub">Your manager application has been received.</p>
        <div class="success-note">⏳ Our admin team will review your shop details and activate your account within 1–2 business days.</div>
        <a href="login.php" class="goto-btn">Back to Sign In &rarr;</a>
      </div>
    </div>

    <input type="hidden" name="submit" id="h-submit" value=""/>
    <input type="hidden" name="role"   id="h-role"   value="customer"/>
  </div>
  </form>
</div>

<script>
let currentRole = 'customer';
let currentStep = 0;

const steps = {
  customer: ['Account Type','Your Info','Security'],
  manager:  ['Account Type','Manager Info','Security']
};

function buildSteps(role){
  const wrap = document.getElementById('step-indicators');
  const s = steps[role];
  let html = '';
  s.forEach(function(label,i){
    const cls = i===0 ? 'step active' : 'step';
    html += '<div class="'+cls+'" id="si-'+i+'"><div class="step-circle">'+(i+1)+'</div><div class="step-label">'+label+'</div></div>';
    if(i < s.length-1) html += '<div class="step-line" id="sl-'+i+'"></div>';
  });
  wrap.innerHTML = html;
}

function updateStepUI(){
  const total = steps[currentRole].length;
  for(let i=0;i<total;i++){
    const el = document.getElementById('si-'+i);
    if(!el) continue;
    el.className = 'step';
    if(i < currentStep) el.classList.add('done');
    else if(i === currentStep) el.classList.add('active');
    const line = document.getElementById('sl-'+i);
    if(line) line.classList.toggle('filled', i < currentStep);
  }
}

function showPanel(id){
  document.querySelectorAll('.panel').forEach(function(p){ p.classList.remove('visible'); });
  const p = document.getElementById(id);
  if(p) p.classList.add('visible');
}

function showErr(id, msg){
  const el = document.getElementById(id);
  if(!el) return;
  el.textContent = msg;
  el.classList.add('show');
}
function clearErr(id){
  const el = document.getElementById(id);
  if(el){ el.textContent=''; el.classList.remove('show'); }
}

function selType(card, role){
  document.querySelectorAll('.type-card').forEach(function(c){ c.classList.remove('selected'); });
  card.classList.add('selected');
  currentRole = role;
}

function nextStep(){
  if(currentStep === 0){
    buildSteps(currentRole);
    currentStep = 1;
    updateStepUI();
    showPanel('panel-' + currentRole + '-1');
    return;
  }
  if(currentStep === 1){
    const errId = 'err-' + currentRole + '-1';
    clearErr(errId);
    const p = currentRole === 'customer' ? 'c' : 'm';
    const fname = document.getElementById(p+'-fname').value.trim();
    const lname = document.getElementById(p+'-lname').value.trim();
    const email = document.getElementById(p+'-email').value.trim();
    if(!fname || !lname){ showErr(errId, 'Please enter your first and last name.'); return; }
    if(!email || email.indexOf('@') === -1){ showErr(errId, 'Please enter a valid email address.'); return; }
    currentStep = 2;
    updateStepUI();
    showPanel('panel-' + currentRole + '-2');
    return;
  }
}

function prevStep(){
  if(currentStep === 2){
    currentStep = 1;
    updateStepUI();
    showPanel('panel-' + currentRole + '-1');
    return;
  }
  if(currentStep === 1){
    currentStep = 0;
    buildSteps(currentRole);
    updateStepUI();
    showPanel('panel-0');
    return;
  }
}

function handleSubmit(){
  var detectedRole = currentRole;
  if(document.getElementById('panel-manager-2').classList.contains('visible')) detectedRole = 'manager';
  else if(document.getElementById('panel-customer-2').classList.contains('visible')) detectedRole = 'customer';
  currentRole = detectedRole;

  var p = detectedRole === 'manager' ? 'm' : 'c';
  var errId = 'err-' + detectedRole + '-2';
  clearErr(errId);

  var pw  = document.getElementById(p + '-pw').value;
  var cpw = document.getElementById(p + '-cpw').value;

  if(pw.length < 8){ showErr(errId, 'Password must be at least 8 characters.'); return; }
  if(pw !== cpw){ showErr(errId, 'Passwords do not match.'); return; }

  document.getElementById('h-role').value   = detectedRole;
  document.getElementById('h-submit').value = '1';
  HTMLFormElement.prototype.submit.call(document.getElementById('signup-form'));
}

function tog(id){
  const inp = document.getElementById(id);
  if(!inp) return;
  inp.type = inp.type === 'password' ? 'text' : 'password';
}

function strength(val, prefix){
  const label = document.getElementById(prefix+'sl');
  const segs = [
    document.getElementById(prefix+'s1'), document.getElementById(prefix+'s2'),
    document.getElementById(prefix+'s3'), document.getElementById(prefix+'s4')
  ];
  segs.forEach(function(s){ if(s) s.style.background = ''; });
  if(!label) return;
  if(val.length === 0){ label.textContent = 'Use 8+ characters, numbers & symbols'; label.style.color=''; return; }
  let score = 0;
  if(val.length >= 8)          score++;
  if(/[A-Z]/.test(val))        score++;
  if(/[0-9]/.test(val))        score++;
  if(/[^A-Za-z0-9]/.test(val)) score++;
  const colors = ['#f87171','#fb923c','#facc15','#34d399'];
  const labels = ['Too weak','Fair','Good','Strong'];
  for(let i=0;i<score;i++){ if(segs[i]) segs[i].style.background = colors[score-1]; }
  label.textContent = labels[score-1] || 'Too short';
  label.style.color = colors[score-1] || '';
}

function handleShopType(val){
  document.getElementById('clothing-sub').style.display = val === 'clothing' ? 'block' : 'none';
}

buildSteps('customer');
showPanel('panel-0');
</script>
</body>
</html>