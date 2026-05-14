<?php
session_start();
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['credential'])) {
    $id_token = $_POST['credential'];

    // 1. I-verify ang token sa Google
    $url = "https://oauth2.googleapis.com/tokeninfo?id_token=" . $id_token;
    $response = file_get_contents($url);
    $data = json_decode($response, true);

    if (isset($data['email'])) {
        $email = $data['email'];
        $full_name = $data['name'];

        // 2. I-check kung registered na ang email sa database mo
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            // KUNG EXISTING USER: I-set ang session at papasukin
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['full_name'] = $user['full_name'];
        } else {
            // KUNG BAGONG USER: I-register muna bago papasukin (Auto-Signup)
            $username = strtolower(explode('@', $email)[0]) . rand(10, 99); // Gawa ng temporary username
            $dummy_pass = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT); // Secure random password
            
            $insert = $pdo->prepare("INSERT INTO users (full_name, username, email, password) VALUES (?, ?, ?, ?)");
            $insert->execute([$full_name, $username, $email, $dummy_pass]);

            // I-set ang session para sa bagong gawa na account
            $_SESSION['user_id'] = $pdo->lastInsertId();
            $_SESSION['full_name'] = $full_name;
        }
        
        // 3. DIRETSO SA DASHBOARD
        header("Location: dashboard.php");
        exit;
    } else {
        // Kung nag-fail ang Google Login
        header("Location: login.php?error=Google authentication failed");
        exit;
    }
}
?>