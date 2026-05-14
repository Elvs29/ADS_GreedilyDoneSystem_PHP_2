<?php
session_start();
require_once 'config.php';
$error = "";

if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['identifier'])) {
    $identifier = trim($_POST['identifier']);
    $password = $_POST['password'];

    if (empty($identifier) || empty($password)) {
        $error = "Please fill in all fields.";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$identifier, $identifier]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['full_name'] = $user['full_name'];
            header("Location: dashboard.php");
            exit;
        } else {
            $error = "Invalid username/email or password.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GreedilyDone | Login</title>
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

                <h1>Welcome Back!</h1>
                <p class="subtitle">Log in to continue organizing your workflow.</p>
                
                <?php if($error): ?>
                    <div style="color: #ff4d4d; margin-bottom: 15px; font-size: 0.85rem;"><?php echo $error; ?></div>
                <?php endif; ?>

                <form action="login.php" method="POST">
                    <div class="input-box">
                        <i class="fa-regular fa-user left-icon"></i>
                        <input type="text" name="identifier" placeholder="Username or Email" required>
                    </div>
                    <div class="input-box">
                        <i class="fa-solid fa-lock left-icon"></i>
                        <input type="password" name="password" placeholder="Password" required>
                    </div>
                    <button type="submit" class="login-btn">Login</button>
                </form>

                <div class="divider">
                    <span></span><p>or login with</p><span></span>
                </div>

                <div class="social-login">
                    <div id="g_id_onload"
                         data-client_id="42274550322-99kgifbru4dhjd2djuvfm0kibl96ng5n.apps.googleusercontent.com"
                         data-context="signin"
                         data-ux_mode="popup"
                         data-callback="handleCredentialResponse"
                         data-auto_prompt="false">
                    </div>
                    <div class="g_id_signin" data-type="icon" data-shape="circle" data-theme="outline" data-size="large"></div>
                </div>

                <p class="bottom-text">
                  Don't have an account? <a href="signup.php">Sign Up now</a>
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

    <script>
    function handleCredentialResponse(response) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'callback.php';
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'credential';
        input.value = response.credential;
        form.appendChild(input);
        document.body.appendChild(form);
        form.submit();
    }
    </script>
</body>
</html>