<?php
require_once 'config.php';
$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $full_name = trim($_POST['full_name']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if (empty($full_name) || empty($username) || empty($email) || empty($password)) {
        $error = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        
        if ($stmt->rowCount() > 0) {
            $error = "Username or Email already taken.";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $insert = $pdo->prepare("INSERT INTO users (full_name, username, email, password) VALUES (?, ?, ?, ?)");
            
            if ($insert->execute([$full_name, $username, $email, $hashed_password])) {
                header("Location: login.php?success=Account created! Please login.");
                exit;
            } else {
                $error = "Something went wrong. Please try again.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>GreedilyDone | Sign Up</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link rel="stylesheet" href="style.css">
  <script src="https://accounts.google.com/gsi/client" async defer></script>
</head>
<body>

  <main class="page login-page">
    <div class="auth-container">

      <section class="form-side">
        <div class="brand">
           <img src="GD_LOGO.png" alt="GreedilyDone Logo" class="brand-logo-img">
           <h2><span>Greedily</span>Done</h2>
        </div>

        <h1>Create account</h1>
        <p class="subtitle">Start organizing your workflow faster with <span>GreedilyDone.</span></p>

        <form action="signup.php" method="POST">
            <div class="input-box">
                <i class="fa-regular fa-user left-icon"></i>
                <input type="text" name="full_name" placeholder="Full Name" required>
            </div>
            <div class="input-box">
                <i class="fa-regular fa-envelope left-icon"></i>
                <input type="email" name="email" placeholder="Email Address" required>
            </div>
            <div class="input-box">
                <i class="fa-regular fa-user left-icon"></i>
                <input type="text" name="username" placeholder="Username" required>
            </div>
            <div class="input-box">
                <i class="fa-solid fa-lock left-icon"></i>
                <input type="password" name="password" placeholder="Password" required>
            </div>
            <div class="input-box">
                <i class="fa-solid fa-lock left-icon"></i>
                <input type="password" name="confirm_password" placeholder="Confirm Password" required>
            </div>

            <button type="submit" class="login-btn">Register</button>

            <?php if($error): ?>
                <div style="color: #ff4d4d; margin-top: 10px; font-size: 0.85rem; text-align: center;"><?php echo $error; ?></div>
            <?php endif; ?>
        </form>

        <div class="divider">
          <span></span><p>or sign up with</p><span></span>
        </div>

        <div class="socials">
            <div id="g_id_onload"
                 data-client_id="42274550322-99kgifbru4dhjd2djuvfm0kibl96ng5n.apps.googleusercontent.com"
                 data-context="signup"
                 data-ux_mode="popup" 
                 data-login_uri="http://localhost/GreedilyDone_System/Front_end/callback.php"                
                 data-auto_prompt="false">
            </div>
            <div class="g_id_signin" data-type="icon" data-shape="circle" data-theme="outline" data-size="large"></div>
        </div>

        <p class="bottom-text">
          Already have an account? <a href="login.php">Login now</a>
        </p>
      </section>

      <section class="image-side">
          <img src="right_pic.png" alt="Workspace" class="right-image">
          <div class="caption">
            <h2>Build better habits<br>and finish tasks</h2>
            <p>with <span>GreedilyDone</span></p>
          </div>
      </section>

    </div>
  </main>

</body>
</html>