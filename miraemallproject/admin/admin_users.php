<?php
session_start();
include "../connect.php";

if(!isset($_SESSION["loggedin"]) || $_SESSION["role"] != "admin"){
    header("Location: secure_login.php");
}

if(isset($_POST["edit_user"])){
    $uid     = intval($_POST["user_id"]);
    $name    = mysqli_real_escape_string($conn, $_POST["edit_name"]);
    $email   = mysqli_real_escape_string($conn, $_POST["edit_email"]);
    $address = mysqli_real_escape_string($conn, $_POST["edit_address"]);
    if($_POST["edit_role"] == "admin" || $_POST["edit_role"] == "manager" || $_POST["edit_role"] == "customer"){
        $role = $_POST["edit_role"];
    } else {
        $role = "customer";
    }
    mysqli_query($conn,"UPDATE users SET name='$name',email='$email',address='$address',role='$role' WHERE id='$uid'");
    header("Location: admin_users.php");
}

if(isset($_POST["delete_user"])){
    $uid = intval($_POST["user_id"]);
    if($uid != $_SESSION["id"]){
        mysqli_query($conn,"DELETE FROM users WHERE id='$uid'");
    }
    header("Location: admin_users.php");
}

if(isset($_POST["update_role"])){
    $uid = intval($_POST["user_id"]);
    if($_POST["new_role"] == "admin" || $_POST["new_role"] == "manager" || $_POST["new_role"] == "customer"){
        $role = $_POST["new_role"];
    } else {
        $role = "customer";
    }
    mysqli_query($conn,"UPDATE users SET role='$role' WHERE id='$uid'");
    header("Location: admin_users.php");
}

if(isset($_POST["add_manager"])){
    $name    = mysqli_real_escape_string($conn, $_POST["mgr_name"]);
    $email   = mysqli_real_escape_string($conn, $_POST["mgr_email"]);
    $pass    = password_hash($_POST["mgr_password"], PASSWORD_DEFAULT);
    $shop_id = intval($_POST["mgr_shop"]);

    $check = mysqli_query($conn,"SELECT id FROM users WHERE email='$email'");
    if(mysqli_num_rows($check) > 0){
        $add_error = "This email is already registered.";
    } else {
        mysqli_query($conn,"INSERT INTO users(name,email,`password`,role,address) VALUES('$name','$email','$pass','manager','')");
        $new_uid = mysqli_insert_id($conn);
        if($shop_id > 0){
            mysqli_query($conn,"UPDATE shops SET manager_id='$new_uid' WHERE id='$shop_id'");
        }
        header("Location: admin_users.php");
    }
}

$available_shops = [];
$sql = "SELECT id,name FROM shops WHERE manager_id IS NULL OR manager_id=0 ORDER BY name";
$sr = mysqli_query($conn, $sql);
while($r = mysqli_fetch_assoc($sr)) $available_shops[] = $r;

$per_page = 15;

if(isset($_GET["page"])){ $page = intval($_GET["page"]); } else { $page = 1; }
if($page < 1){ $page = 1; }

if(isset($_GET["role"])){ $role_filter = $_GET["role"]; } else { $role_filter = ""; }
if(isset($_GET["search"])){ $search = $_GET["search"]; } else { $search = ""; }

$where = "WHERE 1=1";
if($role_filter != ""){
    if($role_filter == "admin" || $role_filter == "manager" || $role_filter == "customer"){
        $rf = mysqli_real_escape_string($conn,$role_filter);
        $where .= " AND role='$rf'";
    }
}
if($search != ""){
    $s = mysqli_real_escape_string($conn,$search);
    $where .= " AND (name LIKE '%$s%' OR email LIKE '%$s%')";
}

$sql = "SELECT COUNT(*) as c FROM users $where";
$result = mysqli_query($conn, $sql);
$row = mysqli_fetch_assoc($result);
$total_users = $row["c"];

$total_pages = max(1, ceil($total_users / $per_page));
if($page > $total_pages){ $page = $total_pages; }
$offset = ($page - 1) * $per_page;

$users = [];
$sql = "SELECT * FROM users $where ORDER BY id DESC LIMIT $per_page OFFSET $offset";
$res = mysqli_query($conn, $sql);
while($r = mysqli_fetch_assoc($res)) $users[] = $r;
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Mirae Admin — Manage Users</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">
  <style>
    *,*::before,*::after{margin:0;padding:0;box-sizing:border-box;}
    :root{
      --bg:#060810;--surface:rgba(255,255,255,.03);--surface2:rgba(255,255,255,.055);
      --border:rgba(255,255,255,.07);--border2:rgba(255,255,255,.12);
      --text:#E8EAF6;--muted:rgba(232,234,246,.45);--muted2:rgba(232,234,246,.25);
      --purple:#6B3FEC;--blue:#2563EB;--cyan:#06B6D4;
      --green:#10B981;--amber:#F59E0B;--red:#EF4444;
      --grad:linear-gradient(135deg,#6B3FEC,#2563EB,#06B6D4);
      --grad-text:linear-gradient(90deg,#818CF8,#38BDF8);
      --nav-w:240px;--topbar-h:64px;
    }
    html,body{height:100%;font-family:'Outfit',sans-serif;background:var(--bg);color:var(--text);}
    .bg-canvas{position:fixed;inset:0;z-index:0;pointer-events:none;}
    .bg-blob{position:absolute;border-radius:50%;filter:blur(80px);opacity:.4;}
    .bg-blob-1{width:500px;height:500px;background:radial-gradient(circle,rgba(107,63,236,.15),transparent 70%);top:-100px;left:-100px;}
    .bg-blob-2{width:350px;height:350px;background:radial-gradient(circle,rgba(6,182,212,.10),transparent 70%);bottom:100px;right:100px;}
    .grid-lines{position:absolute;inset:0;background-image:linear-gradient(rgba(107,63,236,.03) 1px,transparent 1px),linear-gradient(90deg,rgba(107,63,236,.03) 1px,transparent 1px);background-size:56px 56px;}
    .shell{position:relative;z-index:1;display:flex;min-height:100vh;}

    
    .sidebar{position:fixed;top:0;left:0;width:var(--nav-w);height:100vh;background:rgba(6,8,16,.85);border-right:1px solid var(--border);backdrop-filter:blur(24px);display:flex;flex-direction:column;z-index:100;}
    .sidebar-brand{display:flex;align-items:center;gap:10px;padding:20px 24px;border-bottom:1px solid var(--border);text-decoration:none;min-height:var(--topbar-h);}
    .sidebar-brand img{width:32px;height:32px;object-fit:contain;}
    .sidebar-brand-name{font-size:1.05rem;font-weight:700;letter-spacing:.04em;background:var(--grad-text);-webkit-background-clip:text;-webkit-text-fill-color:transparent;}
    .sidebar-brand-role{font-size:.62rem;font-weight:500;letter-spacing:.14em;text-transform:uppercase;color:rgba(239,68,68,.7);}
    .sidebar-nav{flex:1;padding:16px 12px;display:flex;flex-direction:column;gap:4px;}
    .nav-section-label{font-size:.6rem;font-weight:600;letter-spacing:.18em;text-transform:uppercase;color:var(--muted2);padding:12px 12px 6px;}
    .nav-link{display:flex;align-items:center;gap:12px;padding:11px 14px;border-radius:12px;text-decoration:none;color:var(--muted);font-size:.875rem;font-weight:500;transition:all .22s;position:relative;border:1px solid transparent;}
    .nav-link:hover{color:var(--text);background:var(--surface2);border-color:var(--border);}
    .nav-link.active{color:var(--text);background:rgba(107,63,236,.15);border-color:rgba(107,63,236,.3);}
    .nav-link.active::before{content:'';position:absolute;left:-12px;top:50%;transform:translateY(-50%);width:3px;height:60%;border-radius:2px;background:linear-gradient(180deg,#818CF8,#38BDF8);}
    .nav-icon{width:18px;height:18px;opacity:.7;flex-shrink:0;}
    .nav-link.active .nav-icon,.nav-link:hover .nav-icon{opacity:1;}
    .nav-link.logout{color:rgba(239,68,68,.6);}
    .nav-link.logout:hover{color:#F87171;background:rgba(239,68,68,.08);border-color:rgba(239,68,68,.2);}
    .sidebar-footer{padding:16px;border-top:1px solid var(--border);font-size:.72rem;color:var(--muted2);font-family:'Space Mono',monospace;line-height:1.6;}
    .sidebar-footer span{color:rgba(107,63,236,.6);}

    
    .main{margin-left:var(--nav-w);flex:1;display:flex;flex-direction:column;}
    .topbar{height:var(--topbar-h);display:flex;align-items:center;justify-content:space-between;padding:0 32px;border-bottom:1px solid var(--border);background:rgba(6,8,16,.6);backdrop-filter:blur(20px);position:sticky;top:0;z-index:50;}
    .topbar-left{display:flex;align-items:center;gap:16px;}
    .breadcrumb{font-size:.8rem;color:var(--muted);}
    .breadcrumb strong{color:var(--text);font-weight:600;}
    .page-badge{display:inline-flex;align-items:center;gap:6px;padding:4px 12px;border-radius:100px;background:rgba(37,99,235,.12);border:1px solid rgba(37,99,235,.25);font-size:.68rem;font-weight:600;letter-spacing:.12em;text-transform:uppercase;color:#93C5FD;}
    .topbar-right{display:flex;align-items:center;gap:16px;}
    .admin-chip{display:flex;align-items:center;gap:8px;padding:6px 14px;border-radius:100px;background:var(--surface2);border:1px solid var(--border2);font-size:.8rem;font-weight:500;}
    .admin-avatar{width:26px;height:26px;border-radius:50%;background:var(--grad);display:flex;align-items:center;justify-content:center;font-size:.65rem;font-weight:700;color:#fff;}
    .status-dot{width:7px;height:7px;border-radius:50%;background:#10B981;box-shadow:0 0 6px rgba(16,185,129,.6);}
    .content{flex:1;padding:32px;}

    
    .page-header{display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:28px;gap:16px;}
    .page-header-left{}
    .page-title{font-size:1.8rem;font-weight:800;letter-spacing:-.025em;font-family:'Plus Jakarta Sans',sans-serif;margin-bottom:6px;}
    .page-title span{background:var(--grad-text);-webkit-background-clip:text;-webkit-text-fill-color:transparent;}
    .page-subtitle{font-size:.875rem;color:var(--muted);font-weight:300;}
    .btn-primary{display:inline-flex;align-items:center;gap:8px;padding:11px 20px;border-radius:12px;border:none;background:var(--grad);color:#fff;font-family:'Outfit',sans-serif;font-size:.875rem;font-weight:600;cursor:pointer;transition:opacity .2s,transform .15s,box-shadow .2s;box-shadow:0 4px 20px rgba(107,63,236,.3);}
    .btn-primary:hover{opacity:.9;transform:translateY(-1px);box-shadow:0 8px 28px rgba(107,63,236,.4);}

   
    .filters-bar{display:flex;align-items:center;gap:12px;margin-bottom:20px;flex-wrap:wrap;}
    .search-wrap{position:relative;flex:1;min-width:220px;max-width:360px;}
    .search-wrap svg{position:absolute;left:14px;top:50%;transform:translateY(-50%);opacity:.4;width:16px;height:16px;}
    .search-input{width:100%;padding:10px 14px 10px 42px;background:var(--surface2);border:1px solid var(--border);border-radius:12px;color:var(--text);font-family:'Outfit',sans-serif;font-size:.875rem;outline:none;transition:border-color .25s,box-shadow .25s;}
    .search-input::placeholder{color:var(--muted2);}
    .search-input:focus{border-color:rgba(107,63,236,.5);box-shadow:0 0 0 3px rgba(107,63,236,.1);}
    .filter-select{padding:10px 16px;background:var(--surface2);border:1px solid var(--border);border-radius:12px;color:var(--text);font-family:'Outfit',sans-serif;font-size:.875rem;outline:none;cursor:pointer;appearance:none;transition:border-color .25s;}
    .filter-select:focus{border-color:rgba(107,63,236,.5);}
    .filter-select option{background:#0d1020;}
    .results-count{font-size:.8rem;color:var(--muted);margin-left:auto;}
    .results-count strong{color:var(--text);}

  
    .table-card{background:var(--surface);border:1px solid var(--border);border-radius:16px;overflow:hidden;}
    .data-table{width:100%;border-collapse:collapse;}
    .data-table thead{background:rgba(255,255,255,.02);}
    .data-table th{font-size:.68rem;font-weight:600;letter-spacing:.12em;text-transform:uppercase;color:var(--muted2);padding:14px 20px;text-align:left;border-bottom:1px solid var(--border);white-space:nowrap;}
    .data-table th:first-child{padding-left:24px;}
    .data-table th:last-child{text-align:center;}
    .data-table tbody tr{transition:background .15s;}
    .data-table tbody tr:hover{background:rgba(255,255,255,.025);}
    .data-table td{padding:15px 20px;border-bottom:1px solid var(--border);vertical-align:middle;font-size:.865rem;}
    .data-table td:first-child{padding-left:24px;}
    .data-table tbody tr:last-child td{border-bottom:none;}

   
    .user-cell{display:flex;align-items:center;gap:12px;}
    .user-av{width:36px;height:36px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:.85rem;font-weight:700;color:#fff;flex-shrink:0;}
    .user-av-c{background:linear-gradient(135deg,#818CF8,#38BDF8);}
    .user-av-m{background:linear-gradient(135deg,#34D399,#06B6D4);}
    .user-av-a{background:linear-gradient(135deg,#F59E0B,#EF4444);}
    .user-name{font-weight:600;color:var(--text);}
    .user-email{font-size:.75rem;color:var(--muted);margin-top:1px;}

   
    .badge{display:inline-flex;align-items:center;gap:5px;padding:4px 11px;border-radius:100px;font-size:.7rem;font-weight:500;white-space:nowrap;}
    .badge-green{background:rgba(16,185,129,.1);border:1px solid rgba(16,185,129,.25);color:#34D399;}
    .badge-amber{background:rgba(245,158,11,.1);border:1px solid rgba(245,158,11,.25);color:#FCD34D;}
    .badge-red{background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.25);color:#FCA5A5;}
    .badge-purple{background:rgba(107,63,236,.12);border:1px solid rgba(107,63,236,.25);color:#a78bfa;}
    .badge-blue{background:rgba(37,99,235,.12);border:1px solid rgba(37,99,235,.25);color:#93C5FD;}
    .badge-cyan{background:rgba(6,182,212,.12);border:1px solid rgba(6,182,212,.25);color:#67E8F9;}

   
    .actions-cell{display:flex;align-items:center;justify-content:center;gap:8px;}
    .action-btn{
      display:inline-flex;align-items:center;justify-content:center;
      width:32px;height:32px;border-radius:8px;border:1px solid var(--border);
      background:transparent;cursor:pointer;transition:all .2s;color:var(--muted);
    }
    .action-btn svg{width:14px;height:14px;}
    .action-btn.edit:hover{border-color:rgba(37,99,235,.4);background:rgba(37,99,235,.1);color:#93C5FD;}
    .action-btn.delete:hover{border-color:rgba(239,68,68,.4);background:rgba(239,68,68,.1);color:#FCA5A5;}
    .action-btn.view:hover{border-color:rgba(107,63,236,.4);background:rgba(107,63,236,.1);color:#a78bfa;}

   
    .pagination{display:flex;align-items:center;justify-content:space-between;padding:16px 24px;border-top:1px solid var(--border);}
    .pagination-info{font-size:.8rem;color:var(--muted);}
    .pagination-btns{display:flex;gap:6px;}
    .pg-btn{padding:7px 14px;border-radius:8px;border:1px solid var(--border);background:transparent;color:var(--muted);font-family:'Outfit',sans-serif;font-size:.8rem;cursor:pointer;transition:all .2s;}
    .pg-btn:hover,.pg-btn.active{border-color:rgba(107,63,236,.4);background:rgba(107,63,236,.12);color:var(--text);}
    .pg-btn:disabled{opacity:.35;cursor:not-allowed;}

  
    .modal-overlay{position:fixed;inset:0;background:rgba(0,0,0,.7);backdrop-filter:blur(6px);z-index:200;display:flex;align-items:center;justify-content:center;opacity:0;pointer-events:none;transition:opacity .25s;}
    .modal-overlay.open{opacity:1;pointer-events:all;}
    .modal{width:100%;max-width:480px;background:#0d1120;border:1px solid var(--border2);border-radius:20px;padding:32px;box-shadow:0 40px 100px rgba(0,0,0,.7);transform:translateY(20px);transition:transform .25s;}
    .modal-overlay.open .modal{transform:translateY(0);}
    .modal-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;}
    .modal-title{font-size:1.15rem;font-weight:700;letter-spacing:-.01em;}
    .modal-close{background:none;border:none;cursor:pointer;color:var(--muted);padding:4px;border-radius:6px;transition:color .2s;}
    .modal-close:hover{color:var(--text);}
    .modal-close svg{width:18px;height:18px;}
    .form-row{display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:16px;}
    .form-field{margin-bottom:16px;}
    .form-field:last-of-type{margin-bottom:0;}
    .form-label{display:block;font-size:.73rem;font-weight:500;letter-spacing:.07em;text-transform:uppercase;color:var(--muted);margin-bottom:7px;}
    .form-input,.form-select{width:100%;padding:11px 14px;background:rgba(255,255,255,.05);border:1px solid var(--border);border-radius:10px;color:var(--text);font-family:'Outfit',sans-serif;font-size:.9rem;outline:none;transition:border-color .25s,box-shadow .25s;appearance:none;}
    .form-input::placeholder{color:var(--muted2);}
    .form-input:focus,.form-select:focus{border-color:rgba(107,63,236,.6);box-shadow:0 0 0 3px rgba(107,63,236,.12);}
    .form-select option{background:#0d1120;}
    .modal-footer{display:flex;gap:10px;margin-top:24px;}
    .btn-cancel{flex:1;padding:12px;border-radius:10px;border:1px solid var(--border);background:transparent;color:var(--muted);font-family:'Outfit',sans-serif;font-size:.9rem;cursor:pointer;transition:all .2s;}
    .btn-cancel:hover{border-color:var(--border2);color:var(--text);}
    .btn-save{flex:2;padding:12px;border-radius:10px;border:none;background:var(--grad);color:#fff;font-family:'Outfit',sans-serif;font-size:.9rem;font-weight:600;cursor:pointer;transition:opacity .2s,transform .15s;}
    .btn-save:hover{opacity:.9;transform:translateY(-1px);}

  
    .delete-icon{width:56px;height:56px;border-radius:14px;background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.25);display:flex;align-items:center;justify-content:center;margin:0 auto 16px;font-size:1.5rem;}
    .delete-title{text-align:center;font-size:1.1rem;font-weight:700;margin-bottom:8px;}
    .delete-sub{text-align:center;font-size:.875rem;color:var(--muted);line-height:1.6;margin-bottom:24px;}
    .btn-danger{flex:2;padding:12px;border-radius:10px;border:none;background:linear-gradient(135deg,#DC2626,#EF4444);color:#fff;font-family:'Outfit',sans-serif;font-size:.9rem;font-weight:600;cursor:pointer;transition:opacity .2s;}
    .btn-danger:hover{opacity:.9;}
  </style>
</head>
<body>
<div class="bg-canvas">
  <div class="bg-blob bg-blob-1"></div>
  <div class="bg-blob bg-blob-2"></div>
  <div class="grid-lines"></div>
</div>

<div class="shell">
 
  <aside class="sidebar">
    <a href="admin_dashboard.php" class="sidebar-brand">
      <img src="data:image/jpeg;base64,/9j/4AAQSkZJRgABAQAAAQABAAD/4gHYSUNDX1BST0ZJTEUAAQEAAAHIAAAAAAQwAABtbnRyUkdCIFhZWiAH4AABAAEAAAAAAABhY3NwAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAQAA9tYAAQAAAADTLQAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAlkZXNjAAAA8AAAACRyWFlaAAABFAAAABRnWFlaAAABKAAAABRiWFlaAAABPAAAABR3dHB0AAABUAAAABRyVFJDAAABZAAAAChnVFJDAAABZAAAAChiVFJDAAABZAAAAChjcHJ0AAABjAAAADxtbHVjAAAAAAAAAAEAAAAMZW5VUwAAAAgAAAAcAHMAUgBHAEJYWVogAAAAAAAAb6IAADj1AAADkFhZWiAAAAAAAABimQAAt4UAABjaWFlaIAAAAAAAACSgAAAPhAAAts9YWVogAAAAAAAA9tYAAQAAAADTLXBhcmEAAAAAAAQAAAACZmYAAPKnAAANWQAAE9AAAApbAAAAAAAAAABtbHVjAAAAAAAAAAEAAAAMZW5VUwAAACAAAAAcAEcAbwBvAGcAbABlACAASQBuAGMALgAgADIAMAAxADb/2wBDAAUDBAQEAwUEBAQFBQUGBwwIBwcHBw8LCwkMEQ8SEhEPERETFhwXExQaFRERGCEYGh0dHx8fExciJCIeJBweHx7/2wBDAQUFBQcGBw4ICA4eFBEUHh4eHh4eHh4eHh4eHh4eHh4eHh4eHh4eHh4eHh4eHh4eHh4eHh4eHh4eHh4eHh4eHh7/wAARCATmBOYDASIAAhEBAxEB/8QAHQABAQACAwEBAQAAAAAAAAAAAAECCAUGBwMECf/EAGAQAQABAgQCBAYKCg4HBgYDAQABAgMEBQYRByESMUFRCBNhcXWzFCIyNnJzgZGxsiMzN0JSdKHBw9IVFhcnNENTYmNkZYKSlCQmk5Wi0eFUVYSFo/AYJTVGVsKDpdPj/8QAHAEBAAIDAQEBAAAAAAAAAAAAAAEDAgYHBAUI/8QARREBAAECAwQGBgYJAwQCAwAAAAECAwQGEQUxcbESIUFRYYETMzRykcEiIyQ1odEUJTJSgqKy4fAWQmIHFUNTwtKS4vH/2gAMAwEAAhEDEQA/ANywAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA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RZt2+ummI4QAK1gAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAD/2Q==" alt="Mirae"/>
      <div>
        <div class="sidebar-brand-name">Mirae</div>
        <div class="sidebar-brand-role">⬡ Admin Portal</div>
      </div>
    </a>
    <nav class="sidebar-nav">
      <div class="nav-section-label">Main</div>
      <a href="admin_dashboard.php" class="nav-link">
        <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
        Dashboard
      </a>
      <div class="nav-section-label">Management</div>
      <a href="admin_users.php" class="nav-link active">
        <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path stroke-linecap="round" d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/></svg>
        Manage Users
      </a>
      <a href="admin_shops.php" class="nav-link">
        <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><polyline stroke-linecap="round" points="9 22 9 12 15 12 15 22"/></svg>
        Manage Shops
      </a>
      <div style="flex:1;"></div>
      <a href="admin_account.php" class="nav-link">
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
      <div class="topbar-left">
        <div class="breadcrumb">Mirae Admin / <strong>Manage Users</strong></div>
        <div class="page-badge">👥 Users</div>
      </div>
      <div class="topbar-right">
        <div class="admin-chip">
          <div class="admin-avatar"><?= strtoupper(substr($_SESSION['name'],0,1)) ?></div>
          <?= htmlspecialchars(explode(' ',$_SESSION['name'])[0]) ?>
          <div class="status-dot"></div>
        </div>
      </div>
    </div>

    <div class="content">
      <div class="page-header">
        <div class="page-header-left">
          <h1 class="page-title">Manage <span>Users</span></h1>
          <p class="page-subtitle">View, edit and manage all registered accounts in the system.</p>
        </div>
        <div style="display:flex;gap:10px;">
          <button class="btn-primary" onclick="openAddManagerModal()" style="background:linear-gradient(135deg,#0D9488,#2563EB);">
            <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" d="M12 5v14M5 12h14"/></svg>
            Add Manager
          </button>
        </div>
      </div>

  
      <form method="GET" action="admin_users.php">
      <div class="filters-bar">
        <div class="search-wrap">
          <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path stroke-linecap="round" d="M21 21l-4.35-4.35"/></svg>
          <input type="text" name="search" class="search-input" placeholder="Search by name or email…" value="<?= htmlspecialchars($search) ?>"/>
        </div>
        <select name="role" class="filter-select" onchange="this.form.submit()">
          <option value="">All Roles</option>
          <option value="customer" <?= $role_filter=="customer"?"selected":"" ?>>Customer</option>
          <option value="manager"  <?= $role_filter=="manager" ?"selected":"" ?>>Manager</option>
          <option value="admin"    <?= $role_filter=="admin"   ?"selected":"" ?>>Admin</option>
        </select>
        <button type="submit" class="btn-primary" style="padding:10px 16px;font-size:.82rem;">Search</button>
        <?php if($search||$role_filter): ?>
        <a href="admin_users.php" style="font-size:.8rem;color:var(--muted);text-decoration:none;padding:10px 4px;">Clear</a>
        <?php endif; ?>
        <div class="results-count">Showing <strong><?= count($users) ?></strong> of <strong><?= $total_users ?></strong> users</div>
      </div>
      </form>

      <div class="table-card">
        <table class="data-table" id="usersTable">
          <thead>
            <tr>
              <th>#</th>
              <th>User</th>
              <th>Role</th>
              <th>Address</th>
              <th style="text-align:center">Actions</th>
            </tr>
          </thead>
          <tbody id="usersTbody">
          <?php if(empty($users)): ?>
          <tr><td colspan="6" style="text-align:center;color:var(--muted);padding:28px;">No users found.</td></tr>
          <?php else: foreach($users as $u):
            $av_colors = ["admin"=>"linear-gradient(135deg,#F59E0B,#EF4444)","manager"=>"linear-gradient(135deg,#34D399,#06B6D4)","customer"=>"linear-gradient(135deg,#6B3FEC,#06B6D4)"];
            $av_col = $av_colors[$u["role"]] ?? $av_colors["customer"];
            $rbadge = ["admin"=>"badge-purple","manager"=>"badge-cyan","customer"=>"badge-blue"];
            $rcls = $rbadge[$u["role"]] ?? "badge-blue";
          ?>
          <tr>
            <td style="font-family:'Space Mono',monospace;font-size:.75rem;color:var(--muted2)">#<?= $u["id"] ?></td>
            <td>
              <div class="user-cell">
                <div class="user-av" style="background:<?= $av_col ?>"><?= strtoupper(substr($u["name"],0,1)) ?></div>
                <div>
                  <div class="user-name"><?= htmlspecialchars($u["name"]) ?></div>
                  <div class="user-email"><?= htmlspecialchars($u["email"]) ?></div>
                </div>
              </div>
            </td>
            <td><span class="badge <?= $rcls ?>">● <?= ucfirst($u["role"]) ?></span></td>
            <td style="color:var(--muted);font-size:.82rem"><?= htmlspecialchars($u["address"] ?? "—") ?></td>
            <td>
              <div class="actions-cell">
                <button class="action-btn edit" title="Edit user"
                  onclick="openEditModal(
                    '<?= $u['id'] ?>',
                    '<?= htmlspecialchars(addslashes($u['name'])) ?>',
                    '<?= htmlspecialchars(addslashes($u['email'])) ?>',
                    '<?= htmlspecialchars(addslashes($u['address'] ?? '')) ?>',
                    '<?= $u['role'] ?>'
                  )">
                  <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                </button>
                <?php if($u["id"] != $_SESSION["id"]): ?>
                <form method="post" onsubmit="return confirm('Delete <?= htmlspecialchars(addslashes($u['name'])) ?>?')">
                  <input type="hidden" name="user_id" value="<?= $u["id"] ?>">
                  <button type="submit" name="delete_user" class="action-btn delete" title="Delete user">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><polyline stroke-linecap="round" points="3 6 5 6 21 6"/><path stroke-linecap="round" d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6M10 11v6M14 11v6M9 6V4h6v2"/></svg>
                  </button>
                </form>
                <?php else: ?>
                <span style="font-size:.72rem;color:var(--muted2);">You</span>
                <?php endif; ?>
              </div>
            </td>
          </tr>
          <?php endforeach; endif; ?>
        </tbody>
        </table>
        <div class="pagination">
          <div class="pagination-info">Page <?= $page ?> of <?= $total_pages ?></div>
          <div class="pagination-btns">
            <?php
              $qs = http_build_query(array_filter(["search"=>$search,"role"=>$role_filter]));
              $qs = $qs ? "&$qs" : "";
            ?>
            <a href="?page=<?= max(1,$page-1) ?><?= $qs ?>" class="pg-btn <?= $page<=1?'disabled':'' ?>" <?= $page<=1?'style="pointer-events:none;opacity:.35;"':'' ?>>← Prev</a>
            <?php
              $start_p = max(1, $page - 2);
              $end_p   = min($total_pages, $page + 2);
              if($start_p > 1){ echo "<a href='?page=1$qs' class='pg-btn'>1</a>"; if($start_p > 2) echo "<span style='color:var(--muted2);padding:7px 4px;font-size:.8rem'>…</span>"; }
              for($p = $start_p; $p <= $end_p; $p++){
                  $active = $p == $page ? " active" : "";
                  echo "<a href='?page=$p$qs' class='pg-btn$active'>$p</a>";
              }
              if($end_p < $total_pages){ if($end_p < $total_pages-1) echo "<span style='color:var(--muted2);padding:7px 4px;font-size:.8rem'>…</span>"; echo "<a href='?page=$total_pages$qs' class='pg-btn'>$total_pages</a>"; }
            ?>
            <a href="?page=<?= min($total_pages,$page+1) ?><?= $qs ?>" class="pg-btn <?= $page>=$total_pages?'disabled':'' ?>" <?= $page>=$total_pages?'style="pointer-events:none;opacity:.35;"':'' ?>>Next →</a>
          </div>
        </div>
      </div>
    </div>
  </main>
</div>


<div class="modal-overlay" id="editModal">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title">Edit User</div>
      <button class="modal-close" onclick="closeModal('editModal')">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="M6 18L18 6M6 6l12 12"/></svg>
      </button>
    </div>
    <form method="post">
      <input type="hidden" name="user_id" id="editUserId">
      <div class="form-field">
        <label class="form-label">Full Name</label>
        <input class="form-input" name="edit_name" id="editName" placeholder="Full name" required/>
      </div>
      <div class="form-field">
        <label class="form-label">Email Address</label>
        <input class="form-input" name="edit_email" id="editEmail" type="email" required/>
      </div>
      <div class="form-field">
        <label class="form-label">Address</label>
        <input class="form-input" name="edit_address" id="editAddress" placeholder="City, Country"/>
      </div>
      <div class="form-field">
        <label class="form-label">Role</label>
        <select class="form-input" name="edit_role" id="editRole" style="appearance:none;cursor:pointer;">
          <option value="customer">Customer</option>
          <option value="manager">Manager</option>
          <option value="admin">Admin</option>
        </select>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn-cancel" onclick="closeModal('editModal')">Cancel</button>
        <button type="submit" name="edit_user" class="btn-save">Save Changes</button>
      </div>
    </form>
  </div>
</div>


<div class="modal-overlay" id="addModal">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title">Add New User</div>
      <button class="modal-close" onclick="closeModal('addModal')"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="M6 18L18 6M6 6l12 12"/></svg></button>
    </div>
    <div class="form-row">
      <div class="form-field">
        <label class="form-label">First Name</label>
        <input class="form-input" placeholder="First name"/>
      </div>
      <div class="form-field">
        <label class="form-label">Last Name</label>
        <input class="form-input" placeholder="Last name"/>
      </div>
    </div>
    <div class="form-field"><label class="form-label">Email</label><input class="form-input" type="email" placeholder="user@example.com"/></div>
    <div class="form-field"><label class="form-label">Password</label><input class="form-input" type="password" placeholder="••••••••"/></div>
    <div class="form-field"><label class="form-label">Address</label><input class="form-input" placeholder="City, Country"/></div>
    <div class="form-row">
      <div class="form-field"><label class="form-label">Role</label><select class="form-select"><option>Customer</option><option>Manager</option></select></div>
      <div class="form-field"><label class="form-label">Status</label><select class="form-select"><option>Active</option><option>Pending</option><option>Suspended</option></select></div>
    </div>
    <div class="modal-footer">
      <button class="btn-cancel" onclick="closeModal('addModal')">Cancel</button>
      <button class="btn-save" onclick="closeModal('addModal')">Create User</button>
    </div>
  </div>
</div>


<div class="modal-overlay" id="deleteModal">
  <div class="modal" style="max-width:400px;">
    <div class="delete-icon">🗑️</div>
    <div class="delete-title">Delete User</div>
    <div class="delete-sub" id="deleteMsg">Are you sure you want to delete this user? This action cannot be undone.</div>
    <div class="modal-footer">
      <button class="btn-cancel" onclick="closeModal('deleteModal')">Cancel</button>
      <button class="btn-danger" onclick="closeModal('deleteModal')">Delete User</button>
    </div>
  </div>
</div>

<div class="modal-overlay" id="addManagerModal">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title">Add Manager</div>
      <button class="modal-close" onclick="closeModal('addManagerModal')">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="M6 18L18 6M6 6l12 12"/></svg>
      </button>
    </div>

    <?php if(!empty($add_error)): ?>
    <div style="background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.25);border-radius:10px;padding:10px 14px;font-size:.82rem;color:#FCA5A5;margin-bottom:16px;">
      ⚠ <?= htmlspecialchars($add_error) ?>
    </div>
    <?php endif; ?>

    <form method="post" id="addManagerForm">
      <div class="form-field">
        <label class="form-label">Full Name</label>
        <input class="form-input" name="mgr_name" placeholder="e.g. Ahmet Yılmaz" required/>
      </div>
      <div class="form-field">
        <label class="form-label">Email Address</label>
        <input class="form-input" name="mgr_email" type="email" placeholder="manager@example.com" required/>
      </div>
      <div class="form-field">
        <label class="form-label">Password</label>
        <input class="form-input" name="mgr_password" type="password" placeholder="Min. 6 characters" minlength="6" required/>
      </div>
      <div class="form-field">
        <label class="form-label">Assign to Shop <span style="color:rgba(232,234,246,.4);font-size:.7rem;">(only unassigned shops shown)</span></label>
        <select class="form-input" name="mgr_shop" required style="appearance:none;cursor:pointer;">
          <option value="">— Select a shop —</option>
          <?php foreach($available_shops as $sh): ?>
          <option value="<?= $sh['id'] ?>"><?= htmlspecialchars($sh['name']) ?></option>
          <?php endforeach; ?>
          <?php if(empty($available_shops)): ?>
          <option value="" disabled>No unassigned shops available</option>
          <?php endif; ?>
        </select>
        <?php if(empty($available_shops)): ?>
        <p style="font-size:.75rem;color:#FCD34D;margin-top:6px;">⚠ All shops already have a manager. Add a new shop first.</p>
        <?php endif; ?>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn-cancel" onclick="closeModal('addManagerModal')">Cancel</button>
        <button type="submit" name="add_manager" class="btn-save" <?= empty($available_shops)?'disabled style="opacity:.5;cursor:not-allowed;"':'' ?>>Create Manager</button>
      </div>
    </form>
  </div>
</div>

<script>
function openAddManagerModal(){ document.getElementById('addManagerModal').classList.add('open'); }
function openEditModal(id, name, email, address, role) {
  document.getElementById('editUserId').value = id;
  document.getElementById('editName').value = name;
  document.getElementById('editEmail').value = email;
  document.getElementById('editAddress').value = address;
  document.getElementById('editRole').value = role;
  document.getElementById('editModal').classList.add('open');
}
function openAddModal() { document.getElementById('addModal').classList.add('open'); }
function openDeleteModal(name, id) {
  document.getElementById('deleteMsg').textContent = `Are you sure you want to delete "${name}" (${id})? This action cannot be undone.`;
  document.getElementById('deleteModal').classList.add('open');
}
function closeModal(id) { document.getElementById(id).classList.remove('open'); }
function viewUser(name) { alert('View profile: ' + name + '\n(Backend not yet implemented)'); }

document.querySelectorAll('.modal-overlay').forEach(o => {
  o.addEventListener('click', e => { if(e.target === o) o.classList.remove('open'); });
});


</script>
</body>
</html>