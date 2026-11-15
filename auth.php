<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
session_start();
require 'db.php';

$action = $_GET['action'] ?? '';

// ─── LOGIN ───────────────────────────────────────────────
if ($action === 'login') {
    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');

    if (!$email || !$password) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Email and password are required.']);
        exit;
    }

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || !password_verify($password, $user['password_hash'])) {
        echo json_encode(['success' => false, 'error' => 'Incorrect email or password.']);
        exit;
    }

    // Save session
    $_SESSION['user_id']   = $user['id'];
    $_SESSION['user_name'] = $user['full_name'];
    $_SESSION['user_role'] = $user['role'];

    $redirect = ($user['role'] === 'admin') ? 'admin.php' : 'index.html';
    echo json_encode(['success' => true, 'redirect' => $redirect, 'role' => $user['role']]);
    exit;
}

// ─── REGISTER ────────────────────────────────────────────
if ($action === 'register') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email     = trim($_POST['email']     ?? '');
    $phone     = trim($_POST['phone']     ?? '');
    $password  = trim($_POST['password']  ?? '');

    if (!$full_name || !$email || !$password) {
        echo json_encode(['success' => false, 'error' => 'Name, email and password are required.']);
        exit;
    }
    if (strlen($password) < 6) {
        echo json_encode(['success' => false, 'error' => 'Password must be at least 6 characters.']);
        exit;
    }

    // Check duplicate email
    $check = $pdo->prepare("SELECT id FROM users WHERE email = :email");
    $check->execute([':email' => $email]);
    if ($check->fetch()) {
        echo json_encode(['success' => false, 'error' => 'This email is already in use.']);
        exit;
    }

    $hash = password_hash($password, PASSWORD_BCRYPT);
    $stmt = $pdo->prepare("INSERT INTO users (full_name, email, phone, password_hash, role) VALUES (:name, :email, :phone, :hash, 'client')");
    $stmt->execute([':name' => $full_name, ':email' => $email, ':phone' => $phone, ':hash' => $hash]);

    $userId = $pdo->lastInsertId();
    $_SESSION['user_id']   = $userId;
    $_SESSION['user_name'] = $full_name;
    $_SESSION['user_role'] = 'client';

    echo json_encode(['success' => true, 'redirect' => 'index.html']);
    exit;
}

// ─── LOGOUT ──────────────────────────────────────────────
if ($action === 'logout') {
    session_destroy();
    echo json_encode(['success' => true, 'redirect' => 'login.html']);
    exit;
}

// ─── CHECK SESSION ────────────────────────────────────────
if ($action === 'check') {
    if (isset($_SESSION['user_id'])) {
        echo json_encode([
            'logged_in' => true,
            'name'      => $_SESSION['user_name'],
            'role'      => $_SESSION['user_role'],
            'id'        => $_SESSION['user_id']
        ]);
    } else {
        echo json_encode(['logged_in' => false]);
    }
    exit;
}

echo json_encode(['success' => false, 'error' => 'Unknown action.']);
?>