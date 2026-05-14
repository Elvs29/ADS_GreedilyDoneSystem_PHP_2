<?php
$host = 'localhost';
$dbname = 'auth_system'; // Palitan kung iba ang pangalan ng DB mo
$db_user = 'root';
$db_pass = ''; 

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Could not connect to the database: " . $e->getMessage());
}
?>