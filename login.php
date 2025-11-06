<?php
session_start();
include 'db.php';

$error = '';

// kuhaa ang profile image (gamita as login background)
$profile = $conn->query("SELECT setting_value FROM settings WHERE setting_name='profile_image'")->fetch_assoc();
$login_bg = $profile ? $profile['setting_value'] : "../assets/login-bg.jpg";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST["username"]);
    $password = trim($_POST["password"]);

    // Fetch user with case-sensitive username (BINARY)
    $sql = "SELECT * FROM admin_account WHERE BINARY username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $res = $stmt->get_result();
    $user = $res->fetch_assoc();

    if ($user) {
        // Verify password (hashed in DB)
        if (password_verify($password, $user['password'])) {
            // Set sessions properly
            $_SESSION['admin'] = true;
            $_SESSION['admin_username'] = $user['username']; // important for user.php
            header("Location: dashboard.php");
            exit;
        } else {
            $error = "Invalid username or password.";
        }
    } else {
        $error = "Invalid username or password.";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Login - BEN and SOF Dormitory</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }
        body, html { height: 100%; margin: 0; font-family: 'Arial', sans-serif; }
        .login-container { display: flex; height: 100vh; }
        .login-left {
            flex: 1;
            background: url('<?php echo $login_bg; ?>') no-repeat center center;
            background-size: cover;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 2rem;
            font-weight: bold;
            text-align: center;
            padding: 20px;
            text-shadow: 2px 2px 8px rgba(0,0,0,0.7);
            animation: fadeIn 2s ease forwards;
        }
        .login-right {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #A3C9C3, #f0f4f3);
            position: relative;
        }
        .welcome-text { font-size: 2rem; font-weight: bold; color: #2C3E50; margin-bottom: 40px; text-align: center; text-shadow: 1px 1px 3px rgba(0,0,0,0.2); }
        .login-box {
            width: 350px;
            background-color: rgba(255,255,255,0.95);
            padding: 40px 30px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.2);
        }
        .login-box h2 { text-align: center; margin-bottom: 25px; color: #333; }
        .form-label { font-weight: bold; color: #555; }
        .btn-login { width: 100%; background-color: #5A7D7C; color: white; font-weight: bold; border: none; border-radius: 5px; }
        .btn-login:hover { background-color: #496766; transform: scale(1.05); }
        .alert { font-size: 0.9rem; }
        @keyframes fadeIn { 0% { opacity: 0; transform: translateY(-30px); } 100% { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body>
<div class="login-container">
    <div class="login-left"></div>

    <div class="login-right">
        <div class="welcome-text">WELCOME TO BEN AND SOF DORMITORY</div>
        <div class="login-box">
            <h2>Hi Admin!</h2>
            <?php if ($error): ?>
                <div class="alert alert-danger text-center"><?= $error ?></div>
            <?php endif; ?>
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">Username</label>
                    <input type="text" name="username" class="form-control" required autofocus>
                </div>
                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-login">Login</button>
            </form>
        </div>
    </div>
</div>
</body>
</html>
