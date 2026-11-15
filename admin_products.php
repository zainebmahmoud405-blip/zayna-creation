<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: login.html');
    exit;
}

$uploadDir = __DIR__ . '/uploads/products/';
if (!is_dir($uploadDir)) { mkdir($uploadDir, 0755, true); }

function handleUpload($fileField, $uploadDir) {
    if (!isset($_FILES[$fileField]) || $_FILES[$fileField]['error'] !== UPLOAD_ERR_OK) return null;
    $allowed = ['jpg'=>'image/jpeg','jpeg'=>'image/jpeg','png'=>'image/png','webp'=>'image/webp','gif'=>'image/gif'];
    $ext = strtolower(pathinfo($_FILES[$fileField]['name'], PATHINFO_EXTENSION));
    if (!array_key_exists($ext, $allowed)) return null;
    $newName = 'p_' . uniqid() . '.' . $ext;
    move_uploaded_file($_FILES[$fileField]['tmp_name'], $uploadDir . $newName);
    return 'uploads/products/' . $newName;
}

// ── AJOUT / MODIFICATION ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_product'])) {
    $id       = intval($_POST['id'] ?? 0);
    $name     = trim($_POST['name'] ?? '');
    $desc     = trim($_POST['description'] ?? '');
    $category = $_POST['category'] ?? 'standard';
    $inStock  = isset($_POST['in_stock']) ? 1 : 0;

    $base_price = $_POST['base_price'] !== '' ? floatval($_POST['base_price']) : null;
    $price_s    = $_POST['price_s']    !== '' ? floatval($_POST['price_s'])    : null;
    $price_m    = $_POST['price_m']    !== '' ? floatval($_POST['price_m'])    : null;
    $price_l    = $_POST['price_l']    !== '' ? floatval($_POST['price_l'])    : null;
    $pack_sm    = $_POST['price_pack_sm']  !== '' ? floatval($_POST['price_pack_sm'])  : null;
    $pack_sml   = $_POST['price_pack_sml'] !== '' ? floatval($_POST['price_pack_sml']) : null;

    $newImage = handleUpload('image', $uploadDir);

    if ($id > 0) {
        // édition
        if ($newImage) {
            $old = $pdo->prepare("SELECT image FROM products WHERE id=:id");
            $old->execute([':id'=>$id]);
            $oldImg = $old->fetchColumn();
            $upd = $pdo->prepare("UPDATE products SET name=:n, description=:d, category=:c, image=:img, base_price=:bp, price_s=:ps, price_m=:pm, price_l=:pl, price_pack_sm=:psm, price_pack_sml=:psml, in_stock=:st WHERE id=:id");
            $upd->execute([':n'=>$name,':d'=>$desc,':c'=>$category,':img'=>$newImage,':bp'=>$base_price,':ps'=>$price_s,':pm'=>$price_m,':pl'=>$price_l,':psm'=>$pack_sm,':psml'=>$pack_sml,':st'=>$inStock,':id'=>$id]);
            if ($oldImg && strpos($oldImg,'uploads/products/')===0 && file_exists(__DIR__.'/'.$oldImg)) @unlink(__DIR__.'/'.$oldImg);
        } else {
            $upd = $pdo->prepare("UPDATE products SET name=:n, description=:d, category=:c, base_price=:bp, price_s=:ps, price_m=:pm, price_l=:pl, price_pack_sm=:psm, price_pack_sml=:psml, in_stock=:st WHERE id=:id");
            $upd->execute([':n'=>$name,':d'=>$desc,':c'=>$category,':bp'=>$base_price,':ps'=>$price_s,':pm'=>$price_m,':pl'=>$price_l,':psm'=>$pack_sm,':psml'=>$pack_sml,':st'=>$inStock,':id'=>$id]);
        }
    } else {
        // ajout — image obligatoire
        if (!$newImage) { $error = "Merci d'ajouter une photo pour ce produit."; }
        else {
            $maxOrder = $pdo->query("SELECT COALESCE(MAX(sort_order),0)+1 FROM products")->fetchColumn();
            $ins = $pdo->prepare("INSERT INTO products (name, description, image, category, base_price, price_s, price_m, price_l, price_pack_sm, price_pack_sml, in_stock, sort_order) VALUES (:n,:d,:img,:c,:bp,:ps,:pm,:pl,:psm,:psml,:st,:so)");
            $ins->execute([':n'=>$name,':d'=>$desc,':img'=>$newImage,':c'=>$category,':bp'=>$base_price,':ps'=>$price_s,':pm'=>$price_m,':pl'=>$price_l,':psm'=>$pack_sm,':psml'=>$pack_sml,':st'=>$inStock,':so'=>$maxOrder]);
        }
    }
    if (!isset($error)) { header('Location: admin_products.php'); exit; }
}

// ── TOGGLE STOCK (rapide, en un clic) ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_stock'])) {
    $id = intval($_POST['id']);
    $pdo->prepare("UPDATE products SET in_stock = 1 - in_stock WHERE id=:id")->execute([':id'=>$id]);
    header('Location: admin_products.php'); exit;
}

// ── SUPPRESSION ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_product'])) {
    $id = intval($_POST['id']);
    $img = $pdo->prepare("SELECT image FROM products WHERE id=:id");
    $img->execute([':id'=>$id]);
    $imgPath = $img->fetchColumn();
    $pdo->prepare("DELETE FROM products WHERE id=:id")->execute([':id'=>$id]);
    if ($imgPath && strpos($imgPath,'uploads/products/')===0 && file_exists(__DIR__.'/'.$imgPath)) @unlink(__DIR__.'/'.$imgPath);
    header('Location: admin_products.php'); exit;
}

$products = $pdo->query("SELECT * FROM products ORDER BY sort_order ASC, id ASC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Produits — Admin Zayna Creation</title>
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
.btn-add{padding:12px 22px;background:var(--rose-deep);color:white;border:none;border-radius:50px;font-size:14px;font-weight:600;cursor:pointer;font-family:'DM Sans',sans-serif;box-shadow:0 6px 18px rgba(189,65,88,0.3);transition:0.2s;}
.btn-add:hover{background:#a03348;transform:translateY(-2px);}
.grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(230px,1fr));gap:18px;}
.pcard{background:var(--card-bg);border-radius:var(--radius);overflow:hidden;box-shadow:0 2px 12px rgba(0,0,0,0.05);border:1px solid var(--border);position:relative;}
.pcard .img-wrap{height:170px;overflow:hidden;position:relative;background:#f4e8ec;}
.pcard .img-wrap img{width:100%;height:100%;object-fit:cover;}
.stock-flag{position:absolute;top:10px;left:10px;padding:4px 12px;border-radius:50px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;}
.stock-flag.ok{background:#e8fbef;color:#1a7a40;}
.stock-flag.out{background:#fff0f0;color:#c0392b;}
.pcard .body{padding:14px 16px 16px;}
.pcard h3{font-family:'Cormorant Garamond',serif;font-size:19px;margin-bottom:4px;}
.pcard .cat{font-size:11px;color:var(--muted);text-transform:uppercase;letter-spacing:0.6px;margin-bottom:8px;}
.pcard .prices{font-size:12px;color:var(--rose-deep);margin-bottom:12px;line-height:1.6;}
.pcard .actions{display:flex;gap:6px;flex-wrap:wrap;}
.btn-sm{padding:7px 12px;border-radius:8px;border:1.5px solid var(--border);background:white;font-size:12px;font-weight:600;cursor:pointer;font-family:'DM Sans',sans-serif;transition:0.2s;}
.btn-sm.edit{color:var(--rose-deep);border-color:var(--rose);}
.btn-sm.edit:hover{background:var(--rose-light);}
.btn-sm.toggle{color:#8a5a00;}
.btn-sm.toggle:hover{background:#fff8e1;}
.btn-sm.del{color:#e74c3c;border-color:#fcc;}
.btn-sm.del:hover{background:#ffe0e0;}
.empty-state{text-align:center;padding:80px 20px;color:var(--muted);}
.empty-state .big{font-size:44px;margin-bottom:14px;}

/* modal */
.modal-overlay{position:fixed;inset:0;background:rgba(0,0,0,0.45);display:none;align-items:center;justify-content:center;z-index:999;padding:20px;}
.modal-overlay.active{display:flex;}
.modal{background:white;border-radius:20px;max-width:520px;width:100%;max-height:88vh;overflow-y:auto;padding:28px 30px;}
.modal h2{font-family:'Cormorant Garamond',serif;font-size:26px;margin-bottom:18px;color:var(--rose-deep);}
.field{margin-bottom:14px;}
.field label{display:block;font-size:12px;font-weight:600;color:var(--muted);text-transform:uppercase;letter-spacing:0.6px;margin-bottom:5px;}
.field input,.field select,.field textarea{width:100%;padding:10px 14px;border:1.5px solid var(--border);border-radius:10px;font-size:14px;font-family:'DM Sans',sans-serif;outline:none;}
.field input:focus,.field select:focus,.field textarea:focus{border-color:var(--rose);}
.row2{display:grid;grid-template-columns:1fr 1fr;gap:12px;}
.row3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;}
.checkbox-row{display:flex;align-items:center;gap:8px;margin:14px 0;}
.modal-actions{display:flex;gap:10px;margin-top:18px;}
.btn-cancel{flex:1;padding:12px;border-radius:12px;border:1.5px solid var(--border);background:white;font-weight:600;cursor:pointer;font-family:'DM Sans',sans-serif;}
.btn-save{flex:2;padding:12px;border-radius:12px;border:none;background:var(--rose-deep);color:white;font-weight:600;cursor:pointer;font-family:'DM Sans',sans-serif;}
.btn-save:hover{background:#a03348;}
.sizefields{display:none;}
.sizefields.show{display:block;}
.basefield{display:none;}
.basefield.show{display:block;}
.current-img{width:70px;height:70px;object-fit:cover;border-radius:10px;margin-bottom:8px;}
@media(max-width:768px){.sidebar{display:none;}.main{margin-left:0;padding:20px 16px;}}
</style>
</head>
<body>

<nav class="sidebar">
  <div class="sidebar-logo">Zayna<span>Espace Admin</span></div>
  <a href="admin.php" class="nav-item"><span class="icon">📋</span> Commandes</a>
  <a href="admin_products.php" class="nav-item active"><span class="icon">🧵</span> Produits</a>
  <div class="sidebar-divider"></div>
  <a href="index.html" class="nav-item" target="_blank"><span class="icon">🌸</span> Voir le site</a>
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
      <button class="nav-item" type="submit" style="width:100%;"><span class="icon">🚪</span> Déconnexion</button>
    </form>
  </div>
</nav>

<main class="main">
  <div class="topbar">
    <h1>Gérer les <span>Produits</span></h1>
    <button class="btn-add" onclick="openAdd()">+ Ajouter un produit</button>
  </div>

  <?php if (!empty($error)): ?>
    <div style="background:#fff0f0;color:#c0392b;padding:12px 18px;border-radius:12px;margin-bottom:18px;border:1.5px solid #fcc;"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <?php if (empty($products)): ?>
    <div class="empty-state"><div class="big">🧵</div>Aucun produit pour le moment. Ajoutez votre premier article !</div>
  <?php else: ?>
  <div class="grid">
    <?php foreach ($products as $p): ?>
    <div class="pcard">
      <div class="img-wrap">
        <span class="stock-flag <?= $p['in_stock'] ? 'ok' : 'out' ?>"><?= $p['in_stock'] ? 'Disponible' : 'Épuisé' ?></span>
        <img src="<?= htmlspecialchars($p['image']) ?>" alt="<?= htmlspecialchars($p['name']) ?>">
      </div>
      <div class="body">
        <h3><?= htmlspecialchars($p['name']) ?></h3>
        <div class="cat"><?= $p['category']==='laptop' ? 'Laptop' : ($p['category']==='autre' ? 'Autre' : 'Standard') ?></div>
        <div class="prices">
          <?php if ($p['category']==='standard'): ?>
            S <?= $p['price_s'] ?> DT · M <?= $p['price_m'] ?> DT · L <?= $p['price_l'] ?> DT
          <?php else: ?>
            <?= $p['base_price'] ?> DT
          <?php endif; ?>
        </div>
        <div class="actions">
          <button class="btn-sm edit" onclick='openEdit(<?= json_encode($p) ?>)'>✎ Modifier</button>
          <form method="post" style="display:inline;">
            <input type="hidden" name="id" value="<?= $p['id'] ?>">
            <button type="submit" name="toggle_stock" class="btn-sm toggle"><?= $p['in_stock'] ? '🚫 Marquer épuisé' : '✅ Remettre en stock' ?></button>
          </form>
          <form method="post" style="display:inline;" onsubmit="return confirm('Supprimer ce produit ?');">
            <input type="hidden" name="id" value="<?= $p['id'] ?>">
            <button type="submit" name="delete_product" class="btn-sm del">🗑️</button>
          </form>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</main>

<!-- MODAL AJOUT/EDITION -->
<div class="modal-overlay" id="modalOverlay">
  <div class="modal">
    <h2 id="modalTitle">Ajouter un produit</h2>
    <form method="post" enctype="multipart/form-data">
      <input type="hidden" name="id" id="f_id" value="0">
      <div id="currentImgWrap"></div>

      <div class="field">
        <label>Nom du produit *</label>
        <input type="text" name="name" id="f_name" required>
      </div>
      <div class="field">
        <label>Description</label>
        <textarea name="description" id="f_description" rows="3"></textarea>
      </div>
      <div class="field">
        <label>Photo <span id="imgRequiredNote">*</span></label>
        <input type="file" name="image" id="f_image" accept="image/*">
      </div>
      <div class="field">
        <label>Catégorie</label>
        <select name="category" id="f_category" onchange="toggleCategoryFields()">
          <option value="standard">Standard (tailles S / M / L)</option>
          <option value="laptop">Laptop (prix unique)</option>
          <option value="autre">Autre (prix unique)</option>
        </select>
      </div>

      <div class="sizefields" id="sizeFields">
        <div class="row3">
          <div class="field"><label>Prix S</label><input type="number" step="0.5" name="price_s" id="f_price_s"></div>
          <div class="field"><label>Prix M</label><input type="number" step="0.5" name="price_m" id="f_price_m"></div>
          <div class="field"><label>Prix L</label><input type="number" step="0.5" name="price_l" id="f_price_l"></div>
        </div>
        <div class="row2">
          <div class="field"><label>Pack S+M</label><input type="number" step="0.5" name="price_pack_sm" id="f_price_pack_sm"></div>
          <div class="field"><label>Pack S+M+L</label><input type="number" step="0.5" name="price_pack_sml" id="f_price_pack_sml"></div>
        </div>
      </div>

      <div class="basefield" id="baseField">
        <div class="field"><label>Prix (DT)</label><input type="number" step="0.5" name="base_price" id="f_base_price"></div>
      </div>

      <div class="checkbox-row">
        <input type="checkbox" name="in_stock" id="f_in_stock" checked style="width:auto;">
        <label for="f_in_stock" style="text-transform:none;font-weight:500;color:var(--text);">Disponible en stock</label>
      </div>

      <div class="modal-actions">
        <button type="button" class="btn-cancel" onclick="closeModal()">Annuler</button>
        <button type="submit" name="save_product" class="btn-save">Enregistrer</button>
      </div>
    </form>
  </div>
</div>

<script>
function toggleCategoryFields() {
  const cat = document.getElementById('f_category').value;
  document.getElementById('sizeFields').classList.toggle('show', cat === 'standard');
  document.getElementById('baseField').classList.toggle('show', cat !== 'standard');
}

function resetForm() {
  document.getElementById('f_id').value = 0;
  document.getElementById('f_name').value = '';
  document.getElementById('f_description').value = '';
  document.getElementById('f_image').value = '';
  document.getElementById('f_category').value = 'standard';
  document.getElementById('f_price_s').value = '';
  document.getElementById('f_price_m').value = '';
  document.getElementById('f_price_l').value = '';
  document.getElementById('f_price_pack_sm').value = '';
  document.getElementById('f_price_pack_sml').value = '';
  document.getElementById('f_base_price').value = '';
  document.getElementById('f_in_stock').checked = true;
  document.getElementById('currentImgWrap').innerHTML = '';
  document.getElementById('imgRequiredNote').style.display = 'inline';
  toggleCategoryFields();
}

function openAdd() {
  resetForm();
  document.getElementById('modalTitle').textContent = 'Ajouter un produit';
  document.getElementById('modalOverlay').classList.add('active');
}

function openEdit(p) {
  resetForm();
  document.getElementById('modalTitle').textContent = 'Modifier « ' + p.name + ' »';
  document.getElementById('f_id').value = p.id;
  document.getElementById('f_name').value = p.name;
  document.getElementById('f_description').value = p.description || '';
  document.getElementById('f_category').value = p.category;
  document.getElementById('f_price_s').value = p.price_s || '';
  document.getElementById('f_price_m').value = p.price_m || '';
  document.getElementById('f_price_l').value = p.price_l || '';
  document.getElementById('f_price_pack_sm').value = p.price_pack_sm || '';
  document.getElementById('f_price_pack_sml').value = p.price_pack_sml || '';
  document.getElementById('f_base_price').value = p.base_price || '';
  document.getElementById('f_in_stock').checked = p.in_stock == 1;
  document.getElementById('currentImgWrap').innerHTML = '<img class="current-img" src="' + p.image + '"><div style="font-size:12px;color:#999;margin-bottom:8px;">Photo actuelle — laissez vide pour la garder</div>';
  document.getElementById('imgRequiredNote').style.display = 'none';
  toggleCategoryFields();
  document.getElementById('modalOverlay').classList.add('active');
}

function closeModal() {
  document.getElementById('modalOverlay').classList.remove('active');
}
toggleCategoryFields();
</script>
</body>
</html>
