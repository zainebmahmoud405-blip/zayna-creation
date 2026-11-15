<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: login.html');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $order_id  = intval($_POST['order_id']);
    $newStatus = $_POST['status'];
    $allowed   = ['pending','confirmed','shipped','delivered','cancelled'];
    if (in_array($newStatus, $allowed)) {
        $upd = $pdo->prepare("UPDATE orders SET status=:s WHERE id=:id");
        $upd->execute([':s' => $newStatus, ':id' => $order_id]);
    }
    header('Location: admin.php'); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_order'])) {
    $order_id = intval($_POST['order_id']);
    $pdo->prepare("DELETE FROM orders WHERE id=:id")->execute([':id' => $order_id]);
    header('Location: admin.php'); exit;
}

$filterStatus = $_GET['status'] ?? 'all';
$search       = $_GET['search'] ?? '';
$page         = max(1, intval($_GET['page'] ?? 1));
$perPage      = 12;
$offset       = ($page - 1) * $perPage;

$where  = []; $params = [];
if ($filterStatus !== 'all') { $where[] = "status = :status"; $params[':status'] = $filterStatus; }
if ($search) { $where[] = "(client_name LIKE :s OR phone LIKE :s OR product_name LIKE :s)"; $params[':s'] = '%'.$search.'%'; }
$whereSQL = $where ? 'WHERE '.implode(' AND ',$where) : '';

$totalRow = $pdo->prepare("SELECT COUNT(*) FROM orders $whereSQL");
$totalRow->execute($params);
$total = $totalRow->fetchColumn();
$pages = ceil($total / $perPage);

$stmt = $pdo->prepare("SELECT * FROM orders $whereSQL ORDER BY created_at DESC LIMIT $perPage OFFSET $offset");
$stmt->execute($params);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stats = $pdo->query("SELECT COUNT(*) AS total, SUM(status='pending') AS pending, SUM(status='confirmed') AS confirmed, SUM(status='shipped') AS shipped, SUM(status='delivered') AS delivered, SUM(status='cancelled') AS cancelled FROM orders")->fetch(PDO::FETCH_ASSOC);

$statusColors = ['pending'=>'#f39c12','confirmed'=>'#3498db','shipped'=>'#9b59b6','delivered'=>'#27ae60','cancelled'=>'#e74c3c'];
$statusLabels = ['pending'=>'Pending','confirmed'=>'Confirmed','shipped'=>'Shipped','delivered'=>'Delivered','cancelled'=>'Cancelled'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin — Zayna Creation</title>
<link href="https://fonts.googleapis.com/css2?family=Pacifico&family=Cormorant+Garamond:wght@600&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<style>
:root{--rose:#e89ab5;--rose-deep:#bd4158;--rose-light:#fff0f5;--bg:#f8f4f6;--sidebar-bg:#1a0a10;--text:#2c1820;--muted:#8a7080;--border:rgba(232,154,181,0.2);--card-bg:white;--radius:16px;}
*{box-sizing:border-box;margin:0;padding:0;}
body{font-family:'DM Sans',sans-serif;background:var(--bg);color:var(--text);min-height:100vh;display:flex;}
.sidebar{width:240px;min-height:100vh;background:var(--sidebar-bg);padding:28px 20px;display:flex;flex-direction:column;gap:8px;position:fixed;left:0;top:0;bottom:0;z-index:100;}
.sidebar-logo{font-family:'Pacifico',cursive;font-size:22px;color:var(--rose);text-align:center;padding:16px 0 24px;border-bottom:1px solid rgba(232,154,181,0.15);margin-bottom:12px;}
.sidebar-logo span{display:block;font-family:'DM Sans',sans-serif;font-size:10px;color:rgba(255,255,255,0.4);letter-spacing:2px;text-transform:uppercase;margin-top:4px;}
.nav-item{display:flex;align-items:center;gap:10px;padding:12px 16px;border-radius:12px;color:rgba(255,255,255,0.55);font-size:14px;font-weight:500;text-decoration:none;transition:0.2s;cursor:pointer;border:none;background:none;width:100%;text-align:left;}
.nav-item:hover,.nav-item.active{background:rgba(232,154,181,0.12);color:var(--rose);}
.nav-item .icon{font-size:18px;width:22px;text-align:center;}
.sidebar-divider{height:1px;background:rgba(255,255,255,0.06);margin:8px 0;}
.sidebar-footer{margin-top:auto;padding-top:16px;border-top:1px solid rgba(255,255,255,0.06);}
.admin-badge{display:flex;align-items:center;gap:10px;padding:12px 14px;border-radius:12px;background:rgba(232,154,181,0.08);}
.admin-avatar{width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,var(--rose),var(--rose-deep));display:flex;align-items:center;justify-content:center;font-size:16px;color:white;font-weight:700;flex-shrink:0;}
.admin-name{font-size:13px;font-weight:600;color:white;}
.admin-role{font-size:11px;color:var(--rose);text-transform:uppercase;letter-spacing:0.8px;}
.main{margin-left:240px;flex:1;padding:32px;min-height:100vh;}
.topbar{display:flex;justify-content:space-between;align-items:center;margin-bottom:28px;gap:16px;flex-wrap:wrap;}
.topbar h1{font-family:'Cormorant Garamond',serif;font-size:30px;color:var(--text);}
.topbar h1 span{color:var(--rose-deep);}
.search-bar{display:flex;align-items:center;gap:10px;background:white;border:1.5px solid var(--border);border-radius:50px;padding:10px 18px;box-shadow:0 2px 8px rgba(0,0,0,0.04);}
.search-bar input{border:none;outline:none;font-size:14px;font-family:'DM Sans',sans-serif;background:none;width:220px;color:var(--text);}
.search-bar button{background:none;border:none;cursor:pointer;font-size:16px;color:var(--muted);padding:0;transition:0.2s;}
.search-bar button:hover{color:var(--rose-deep);}
.stats-row{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:14px;margin-bottom:24px;}
.stat-card{background:var(--card-bg);border-radius:var(--radius);padding:20px;box-shadow:0 2px 12px rgba(0,0,0,0.05);border:1px solid var(--border);cursor:pointer;transition:0.2s;text-decoration:none;display:block;}
.stat-card:hover{transform:translateY(-2px);box-shadow:0 6px 20px rgba(0,0,0,0.1);}
.stat-card.active-filter{border-color:var(--rose);box-shadow:0 0 0 3px rgba(232,154,181,0.2);}
.stat-num{font-size:28px;font-weight:700;color:var(--text);line-height:1;}
.stat-label{font-size:12px;color:var(--muted);margin-top:6px;font-weight:500;}
.stat-dot{width:8px;height:8px;border-radius:50%;display:inline-block;margin-right:5px;}
.filter-row{display:flex;gap:8px;margin-bottom:20px;flex-wrap:wrap;}
.ftab{padding:7px 18px;border-radius:50px;font-size:13px;font-weight:500;font-family:'DM Sans',sans-serif;border:1.5px solid var(--border);background:white;color:var(--muted);cursor:pointer;transition:0.2s;text-decoration:none;}
.ftab:hover{border-color:var(--rose);color:var(--rose-deep);}
.ftab.active{background:var(--rose-deep);color:white;border-color:var(--rose-deep);}
.table-card{background:var(--card-bg);border-radius:var(--radius);box-shadow:0 2px 12px rgba(0,0,0,0.05);border:1px solid var(--border);overflow:hidden;}
.table-header{padding:18px 24px;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center;}
.table-title{font-weight:600;font-size:15px;color:var(--text);}
.table-count{font-size:13px;color:var(--muted);}
.orders-table{width:100%;border-collapse:collapse;}
.orders-table th{padding:12px 14px;text-align:left;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:0.8px;color:var(--muted);background:#fdf8fa;border-bottom:1px solid var(--border);}
.orders-table td{padding:13px 14px;font-size:13px;border-bottom:1px solid rgba(0,0,0,0.04);vertical-align:middle;}
.orders-table tr:last-child td{border-bottom:none;}
.orders-table tr:hover td{background:rgba(232,154,181,0.04);}
.status-badge{display:inline-flex;align-items:center;gap:5px;padding:4px 12px;border-radius:50px;font-size:12px;font-weight:600;}
.client-name{font-weight:600;color:var(--text);}
.client-phone{font-size:12px;color:var(--muted);}
.address-cell{font-size:12px;color:var(--muted);max-width:130px;}
.action-form{display:inline;}
.select-status{padding:6px 10px;border-radius:8px;border:1.5px solid var(--border);font-size:13px;font-family:'DM Sans',sans-serif;background:white;color:var(--text);cursor:pointer;outline:none;}
.select-status:focus{border-color:var(--rose);}
.btn-save{padding:6px 14px;border-radius:8px;background:var(--rose-deep);color:white;border:none;font-size:12px;font-weight:600;cursor:pointer;margin-left:6px;font-family:'DM Sans',sans-serif;transition:0.2s;}
.btn-save:hover{background:#a03348;}
.btn-delete{padding:6px 10px;border-radius:8px;background:#fff0f0;color:#e74c3c;border:1.5px solid #fcc;font-size:12px;font-weight:600;cursor:pointer;margin-left:4px;font-family:'DM Sans',sans-serif;transition:0.2s;}
.btn-delete:hover{background:#ffe0e0;}
.empty-state{text-align:center;padding:60px 20px;color:var(--muted);font-size:15px;}
.empty-state .big{font-size:40px;margin-bottom:12px;}
.pagination{display:flex;justify-content:center;gap:6px;padding:20px;border-top:1px solid var(--border);}
.page-btn{min-width:36px;height:36px;padding:0 10px;border-radius:10px;border:1.5px solid var(--border);background:white;color:var(--muted);font-size:14px;font-family:'DM Sans',sans-serif;cursor:pointer;transition:0.2s;text-decoration:none;display:flex;align-items:center;justify-content:center;}
.page-btn:hover,.page-btn.active{background:var(--rose-deep);color:white;border-color:var(--rose-deep);}
@media(max-width:768px){.sidebar{display:none;}.main{margin-left:0;padding:20px 16px;}}
</style>
</head>
<body>

<nav class="sidebar">
  <div class="sidebar-logo">Zayna<span>Admin Panel</span></div>
  <a href="admin.php" class="nav-item active"><span class="icon">📋</span> Orders</a>
  <div class="sidebar-divider"></div>
  <a href="index.html" class="nav-item" target="_blank"><span class="icon">🌸</span> View Website</a>
  <div class="sidebar-divider"></div>
  <div class="sidebar-footer">
    <div class="admin-badge">
      <div class="admin-avatar"><?= strtoupper(substr($_SESSION['user_name'],0,1)) ?></div>
      <div>
        <div class="admin-name"><?= htmlspecialchars($_SESSION['user_name']) ?></div>
        <div class="admin-role">Admin</div>
      </div>
    </div>
    <form action="auth.php?action=logout" method="post" style="margin-top:10px;">
      <button class="nav-item" type="submit" style="width:100%;"><span class="icon">🚪</span> Sign Out</button>
    </form>
  </div>
</nav>

<main class="main">
  <div class="topbar">
    <h1>Manage <span>Orders</span></h1>
    <form action="admin.php" method="get" style="display:flex;align-items:center;gap:8px;">
      <?php if($filterStatus!=='all'):?><input type="hidden" name="status" value="<?=htmlspecialchars($filterStatus)?>"><?php endif;?>
      <div class="search-bar">
        <span>🔍</span>
        <input type="text" name="search" value="<?=htmlspecialchars($search)?>" placeholder="Name, phone, product...">
        <button type="submit">→</button>
      </div>
    </form>
  </div>

  <div class="stats-row">
    <?php
    $statCards=[['all','📦',$stats['total'],'Total Orders','#bd4158'],['pending','⏳',$stats['pending'],'Pending','#f39c12'],['confirmed','✅',$stats['confirmed'],'Confirmed','#3498db'],['shipped','🚚',$stats['shipped'],'Shipped','#9b59b6'],['delivered','🎉',$stats['delivered'],'Delivered','#27ae60'],['cancelled','❌',$stats['cancelled'],'Cancelled','#e74c3c']];
    foreach($statCards as [$key,$icon,$num,$label,$color]):
      $ac=($filterStatus===$key)?' active-filter':'';
    ?>
    <a href="admin.php?status=<?=$key?><?=$search?'&search='.urlencode($search):''?>" class="stat-card<?=$ac?>">
      <div class="stat-num"><?=$icon?> <?=$num??0?></div>
      <div class="stat-label"><span class="stat-dot" style="background:<?=$color?>"></span><?=$label?></div>
    </a>
    <?php endforeach;?>
  </div>

  <div class="filter-row">
    <?php foreach(array_merge(['all'=>'All'],$statusLabels) as $key=>$label):?>
      <a href="admin.php?status=<?=$key?><?=$search?'&search='.urlencode($search):''?>" class="ftab <?=$filterStatus===$key?'active':''?>"><?=$label?></a>
    <?php endforeach;?>
  </div>

  <div class="table-card">
    <div class="table-header">
      <span class="table-title">Orders</span>
      <span class="table-count"><?=$total?> result<?=$total!=1?'s':''?></span>
    </div>

    <?php if(empty($orders)):?>
      <div class="empty-state"><div class="big">🌸</div>No orders found</div>
    <?php else:?>
    <div style="overflow-x:auto;">
    <table class="orders-table">
      <thead>
        <tr>
          <th>#</th><th>Client</th><th>Governorate</th><th>Address</th>
          <th>Product</th><th>Size</th><th>Qty</th><th>Note</th>
          <th>Status</th><th>Date</th><th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach($orders as $o):
          $sc=$statusColors[$o['status']??'pending']??'#999';
          $sl=$statusLabels[$o['status']??'pending']??$o['status'];
        ?>
        <tr>
          <td><strong>#<?=$o['id']?></strong></td>
          <td>
            <div class="client-name"><?=htmlspecialchars($o['client_name'])?></div>
            <div class="client-phone">📞 <?=htmlspecialchars($o['phone'])?></div>
          </td>
          <td><?=htmlspecialchars($o['governorate'])?></td>
          <td class="address-cell"><?=isset($o['address'])&&$o['address']?htmlspecialchars(substr($o['address'],0,55)).(strlen($o['address'])>55?'…':''):'—'?></td>
          <td><strong><?=htmlspecialchars($o['product_name'])?></strong></td>
          <td><?=htmlspecialchars($o['size'])?:'—'?></td>
          <td><?=$o['quantity']?></td>
          <td style="max-width:110px;font-size:12px;color:#888;"><?=$o['note']?htmlspecialchars(substr($o['note'],0,40)).(strlen($o['note'])>40?'…':''):'—'?></td>
          <td><span class="status-badge" style="background:<?=$sc?>22;color:<?=$sc?>;"><?=$sl?></span></td>
          <td style="font-size:12px;color:var(--muted);white-space:nowrap;"><?=date('d/m/Y H:i',strtotime($o['created_at']))?></td>
          <td>
            <form action="admin.php" method="post" class="action-form" style="display:flex;align-items:center;gap:4px;">
              <input type="hidden" name="order_id" value="<?=$o['id']?>">
              <select name="status" class="select-status">
                <?php foreach($statusLabels as $k=>$v):?>
                  <option value="<?=$k?>" <?=($o['status']??'pending')===$k?'selected':''?>><?=$v?></option>
                <?php endforeach;?>
              </select>
              <button type="submit" name="update_status" class="btn-save">✓</button>
            </form>
            <form action="admin.php" method="post" class="action-form" onsubmit="return confirm('Delete this order?')" style="margin-top:4px;">
              <input type="hidden" name="order_id" value="<?=$o['id']?>">
              <button type="submit" name="delete_order" class="btn-delete">🗑️ Delete</button>
            </form>
          </td>
        </tr>
        <?php endforeach;?>
      </tbody>
    </table>
    </div>
    <?php if($pages>1):?>
    <div class="pagination">
      <?php for($p=1;$p<=$pages;$p++):?>
        <a href="admin.php?page=<?=$p?>&status=<?=urlencode($filterStatus)?>&search=<?=urlencode($search)?>" class="page-btn <?=$p===$page?'active':''?>"><?=$p?></a>
      <?php endfor;?>
    </div>
    <?php endif;?>
    <?php endif;?>
  </div>
</main>
</body>
</html>
