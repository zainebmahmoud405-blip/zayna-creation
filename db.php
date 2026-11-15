<?php
$host = 'localhost';
$dbname = 'zayna';
$user = 'root';       // ton user MySQL
$pass = '';           // ton mot de passe (vide par défaut sur XAMPP)

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    http_response_code(500);
    die(json_encode(['success' => false, 'error' => 'Connexion BD échouée']));
}
?>