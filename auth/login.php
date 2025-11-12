<?php
/**
 * Secure Login Page with Rate Limiting and CSRF Protection
 */
require_once __DIR__ . '/../config/db.php';

$error = '';
$show_timeout_message = false;

// Check for session timeout message
if (isset($_GET['timeout']) && $_GET['timeout'] == 1) {
    $show_timeout_message = true;
}

// Redirect if already logged in
if (Session::isLoggedIn()) {
    header('Location: ' . BASE_PATH . '/modules/dashboard/');
    exit;
}

// Fetch login background image securely
try {
    $bg_result = $db->select('settings', ['setting_name' => 'profile_image']);
    $bg_row = $bg_result->fetch_assoc();
    $login_bg = $bg_row['setting_value'] ?? 'assets/login-bg.jpg';
} catch (Exception $e) {
    $login_bg = 'assets/login-bg.jpg';
}

// Normalize background path
if (!empty($login_bg) && !preg_match('/^(https?:)?\/\//', $login_bg) && strpos($login_bg, 'data:') !== 0) {
    $login_bg = BASE_PATH . '/' . ltrim($login_bg, '/');
}

// Handle login form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // Verify CSRF token
        if (!CSRF::verify()) {
            throw new Exception("Security token validation failed. Please refresh and try again.");
        }

        $username = trim($_POST["username"] ?? '');
        $password = $_POST["password"] ?? '';

        // Validate inputs
        if (empty($username) || empty($password)) {
            throw new Exception("Username and password are required.");
        }

        // Initialize rate limiter
        $rateLimiter = new RateLimiter();

        // Check rate limit before attempting login
        $rateLimiter->checkLimit($username);

        // Fetch user with case-sensitive username using secure Database helper
        $sql = "SELECT * FROM admin_account WHERE BINARY username = ?";
        $result = $db->query($sql, [$username]);
        $user = $result->fetch_assoc();

        if ($user && password_verify($password, $user['password'])) {
            // Success - clear rate limit and create session
            $rateLimiter->recordAttempt($username, true);

            // Use Session helper for secure login
            Session::login($username);

            // Keep admin_username for compatibility with existing code (user.php)
            $_SESSION['admin_username'] = $user['username'];

            header('Location: ' . BASE_PATH . '/modules/dashboard/');
            exit;
        } else {
            // Failed login - record attempt
            $rateLimiter->recordAttempt($username, false);

            // Get remaining attempts for user feedback
            $remaining = $rateLimiter->getRemainingAttempts($username);
            if ($remaining > 0) {
                throw new Exception("Invalid username or password. $remaining attempt(s) remaining.");
            } else {
                throw new Exception("Invalid username or password.");
            }
        }

    } catch (Exception $e) {
        $error = $e->getMessage();
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

            <?php if ($show_timeout_message): ?>
                <div class="alert alert-warning text-center">Session expired. Please login again.</div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-danger text-center"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST" action="login.php">
                <?php echo CSRF::getTokenField(); ?>

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
