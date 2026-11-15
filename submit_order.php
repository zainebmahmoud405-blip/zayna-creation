<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'You must be logged in to place an order.', 'redirect' => 'login.html']);
    exit;
}

$name    = trim($_POST['client_name']  ?? '');
$phone   = trim($_POST['phone']        ?? '');
$gov     = trim($_POST['governorate']  ?? '');
$address = trim($_POST['address']      ?? '');
$product = trim($_POST['product_name'] ?? '');
$size    = trim($_POST['size']         ?? '');
$qty     = intval($_POST['quantity']   ?? 1);
$note    = trim($_POST['note']         ?? '');
$user_id = $_SESSION['user_id'];

if (!$name || !$phone || !$gov || !$product || !$address) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Required fields are missing.']);
    exit;
}

// Add address column if it does not exist yet
try {
    $pdo->exec("ALTER TABLE orders ADD COLUMN IF NOT EXISTS address VARCHAR(255) NULL");
} catch(Exception $e) {}

$stmt = $pdo->prepare("
    INSERT INTO orders (client_name, phone, governorate, address, product_name, size, quantity, note, user_id, status)
    VALUES (:name, :phone, :gov, :address, :product, :size, :qty, :note, :user_id, 'pending')
");
$stmt->execute([
    ':name'    => $name,
    ':phone'   => $phone,
    ':gov'     => $gov,
    ':address' => $address,
    ':product' => $product,
    ':size'    => $size,
    ':qty'     => $qty,
    ':note'    => $note,
    ':user_id' => $user_id,
]);

echo json_encode(['success' => true, 'message' => 'Order placed successfully! 🌸']);
?>
