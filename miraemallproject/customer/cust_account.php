<?php
include "../connect.php";
include "../session.php";
if($_SESSION["role"] != "customer"){
    header("Location: ../login.php");
}
$cid = $_SESSION["id"];
if(isset($_POST["save_profile"])){
    $name    = mysqli_real_escape_string($conn, $_POST["name"]);
    $address = mysqli_real_escape_string($conn, $_POST["address"]);
    if($name != ""){
        mysqli_query($conn,"UPDATE users SET name='$name', address='$address' WHERE id='$cid'");
        $_SESSION["name"] = $name;
        $saved = true;
    } else {
        $err = "Name cannot be empty.";
    }
}
if(isset($_POST["change_password"])){
    if(isset($_POST["current_pw"])){ $cur = $_POST["current_pw"]; } else { $cur = ""; }
    if(isset($_POST["new_pw"])){ $new = $_POST["new_pw"]; } else { $new = ""; }
    if(isset($_POST["confirm_pw"])){ $conf = $_POST["confirm_pw"]; } else { $conf = ""; }

    $sql = "SELECT * FROM users WHERE id='$cid'";
    $result = mysqli_query($conn, $sql);
    $user = mysqli_fetch_assoc($result);

    if(!password_verify($cur, $user["password"])){
        $pw_err = "Current password is incorrect.";
    } elseif(strlen($new) < 8){
        $pw_err = "New password must be at least 8 characters.";
    } elseif($new != $conf){
        $pw_err = "New passwords do not match.";
    } else {
        $hash = password_hash($new, PASSWORD_DEFAULT);
        mysqli_query($conn,"UPDATE users SET password='$hash' WHERE id='$cid'");
        $pw_ok = true;
    }
}

$sql = "SELECT * FROM users WHERE id='$cid'";
$result = mysqli_query($conn, $sql);
$user = mysqli_fetch_assoc($result);

$sql = "SELECT COUNT(*) as c FROM orders WHERE customer_id='$cid'";
$result = mysqli_query($conn, $sql);
$row = mysqli_fetch_assoc($result);
$order_count = $row["c"];

$sql = "SELECT COUNT(*) as c FROM cart WHERE customer_id='$cid'";
$result = mysqli_query($conn, $sql);
$row = mysqli_fetch_assoc($result);
$cart_count2 = $row["c"];

$activePage = 'account';
$nameParts = explode(" ", $user["name"], 2);
if(isset($nameParts[0])){ $fname = $nameParts[0]; } else { $fname = ""; }
if(isset($nameParts[1])){ $lname = $nameParts[1]; } else { $lname = ""; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1.0"/>
  <title>Mirae — Account</title>
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="mirae.css"/>
</head>
<body>
<?php include "nav.php"; ?>

<div class="pg">
  <div class="ptitle">My <span>Account</span></div>
  <div class="psub">Manage your profile and preferences</div>

  <div class="acc-wrap">

   
    <div>
      <div class="profile-card">
        <div class="p-avatar"><?= strtoupper(substr($user["name"],0,1)) ?></div>
        <div class="p-name"><?= htmlspecialchars($user["name"]) ?></div>
        <div class="p-email"><?= htmlspecialchars($user["email"]) ?></div>
        <span class="p-role">&#128717; Customer</span>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-top:20px;">
          <div style="background:var(--surf2);border:1px solid var(--bdr);border-radius:10px;padding:12px;text-align:center;">
            <div style="font-size:1.2rem;font-weight:800;background:var(--gtext);-webkit-background-clip:text;-webkit-text-fill-color:transparent;"><?= $order_count ?></div>
            <div style="font-size:.67rem;color:var(--mut);margin-top:2px;">Orders</div>
          </div>
          <div style="background:var(--surf2);border:1px solid var(--bdr);border-radius:10px;padding:12px;text-align:center;">
            <div style="font-size:1.2rem;font-weight:800;background:var(--gtext);-webkit-background-clip:text;-webkit-text-fill-color:transparent;"><?= $cart_count2 ?></div>
            <div style="font-size:.67rem;color:var(--mut);margin-top:2px;">In Cart</div>
          </div>
        </div>
      </div>
    </div>

   
    <div class="acc-sections">

     
      <div class="acc-card">
        <div class="ac-title">&#128100; Personal Information</div>

        <?php if(isset($saved)): ?>
        <div class="alert alert-ok" style="margin-bottom:14px;">&#10003; Profile updated successfully.</div>
        <?php endif; ?>
        <?php if(isset($err)): ?>
        <div class="alert alert-err" style="margin-bottom:14px;"><?= htmlspecialchars($err) ?></div>
        <?php endif; ?>

        <form method="post" action="cust_account.php">
          <div class="frow">
            <div class="fld">
              <label class="flbl">First Name</label>
              <input class="finp" type="text" name="name" value="<?= htmlspecialchars($fname) ?>" placeholder="First name"/>
            </div>
            <div class="fld">
              <label class="flbl">Last Name</label>
              <input class="finp" type="text" value="<?= htmlspecialchars($lname) ?>" placeholder="Last name" readonly/>
            </div>
          </div>
          <div class="fld">
            <label class="flbl">Email Address</label>
            <input class="finp" type="email" value="<?= htmlspecialchars($user["email"]) ?>" readonly/>
          </div>
          <div class="fld">
            <label class="flbl">Delivery Address</label>
            <input class="finp" type="text" name="address" value="<?= htmlspecialchars($user["address"] ?? '') ?>" placeholder="123 Main St, City, Country"/>
          </div>
          <button type="submit" name="save_profile" class="save-btn">Save Changes</button>
        </form>
      </div>

      
      <div class="acc-card">
        <div class="ac-title">&#128274; Change Password</div>

        <?php if(isset($pw_ok)): ?>
        <div class="alert alert-ok" style="margin-bottom:14px;">&#10003; Password changed successfully.</div>
        <?php endif; ?>
        <?php if(isset($pw_err)): ?>
        <div class="alert alert-err" style="margin-bottom:14px;"><?= htmlspecialchars($pw_err) ?></div>
        <?php endif; ?>

        <form method="post" action="cust_account.php">
          <div class="fld">
            <label class="flbl">Current Password</label>
            <input class="finp" type="password" name="current_pw" placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;"/>
          </div>
          <div class="frow">
            <div class="fld">
              <label class="flbl">New Password</label>
              <input class="finp" type="password" name="new_pw" placeholder="8+ characters"/>
            </div>
            <div class="fld">
              <label class="flbl">Confirm New</label>
              <input class="finp" type="password" name="confirm_pw" placeholder="Repeat password"/>
            </div>
          </div>
          <button type="submit" name="change_password" class="save-btn">Update Password</button>
        </form>
      </div>

     
      <div class="acc-card">
        <div class="ac-title">&#9881; Preferences</div>
        <div class="tog-row">
          <div>
            <div class="tog-lbl">Dark Mode</div>
            <div class="tog-sub">Switch between light and dark theme</div>
          </div>
          <button class="tog" id="darkTog" onclick="toggleTheme()"></button>
        </div>
        <div class="tog-row">
          <div>
            <div class="tog-lbl">Order Notifications</div>
            <div class="tog-sub">Get notified when your order status changes</div>
          </div>
          <button class="tog on" onclick="this.classList.toggle('on')"></button>
        </div>
      </div>

    </div>
  </div>
</div>

<script>
function applyTheme(t){
    document.body.classList.toggle('light',t==='light');
    document.getElementById('tbtn').textContent = t==='light'?'☀️':'🌙';
    localStorage.setItem('mt',t);
    var tog = document.getElementById('darkTog');
    if(tog) tog.classList.toggle('on', t==='dark');
}
function toggleTheme(){applyTheme(document.body.classList.contains('light')?'dark':'light');}
applyTheme(localStorage.getItem('mt')||'dark');
</script>
</body>
</html>