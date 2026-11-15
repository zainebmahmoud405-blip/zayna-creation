<?php
// Renvoie la liste des produits (utilisé par index.html pour afficher la boutique)
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
require 'db.php';

$stmt = $pdo->query("SELECT * FROM products ORDER BY sort_order ASC, id ASC");
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

$out = array_map(function ($p) {
    return [
        'id'             => (int)$p['id'],
        'name'           => $p['name'],
        'description'    => $p['description'],
        'image'          => $p['image'],
        'category'       => $p['category'],
        'in_stock'       => (bool)$p['in_stock'],
        'base_price'     => $p['base_price']     !== null ? (float)$p['base_price']     : null,
        'price_s'        => $p['price_s']        !== null ? (float)$p['price_s']        : null,
        'price_m'        => $p['price_m']        !== null ? (float)$p['price_m']        : null,
        'price_l'        => $p['price_l']        !== null ? (float)$p['price_l']        : null,
        'price_pack_sm'  => $p['price_pack_sm']  !== null ? (float)$p['price_pack_sm']  : null,
        'price_pack_sml' => $p['price_pack_sml'] !== null ? (float)$p['price_pack_sml'] : null,
    ];
}, $products);

echo json_encode(['success' => true, 'products' => $out]);
