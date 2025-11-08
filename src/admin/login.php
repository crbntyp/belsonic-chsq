<?php
require_once 'auth.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (login($username, $password)) {
        header('Location: /admin/gigs.php');
        exit;
    } else {
        $error = 'Invalid username or password';
    }
}

// If already logged in, redirect to gigs
if (isLoggedIn()) {
    header('Location: /admin/gigs.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    <link rel="stylesheet" href="https://maxst.icons8.com/vue-static/landings/line-awesome/line-awesome/1.3.0/css/line-awesome.min.css">
    <link rel="stylesheet" href="/styles/main.css">
    <link rel="stylesheet" href="/admin/admin.css?v=<?php echo time(); ?>">
</head>
<body class="admin-login">
    <div class="login-container">
        <div class="login-box">
            <div class="login-header">
                <i class="las la-user-shield la-3x"></i>
                <h1>Admin Login</h1>
                <p>Content Management System</p>
            </div>

            <form method="POST" class="login-form">
                <?php if ($error): ?>
                    <div class="alert alert-error">
                        <i class="las la-exclamation-circle"></i>
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
                <div class="form-group">
                    <label for="username">
                        <i class="las la-user"></i>
                        Username
                    </label>
                    <input type="text" id="username" name="username" required autofocus>
                </div>

                <div class="form-group">
                    <label for="password">
                        <i class="las la-lock"></i>
                        Password
                    </label>
                    <input type="password" id="password" name="password" required>
                </div>

                <button type="submit" class="btn btn-primary btn-block">
                    <i class="las la-sign-in-alt"></i>
                    Login
                </button>
            </form>

            <div class="login-footer">
                <a href="/">
                    <i class="las la-arrow-left"></i>
                    Back to Website
                </a>
            </div>
        </div>
    </div>
</body>
</html>
