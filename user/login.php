<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';
startSession();

if (isUserLoggedIn()) {
    header('Location: ' . APP_URL . '/user/dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
    $email    = trim($_POST['email']    ?? '');
    $password = $_POST['password']      ?? '';

    if (!$email || !$password) {
        $error = 'Please enter your email and password.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email address.';
    } else {
        try {
            $stmt = db()->prepare('SELECT id, name, email, password, is_active FROM users WHERE email = ? LIMIT 1');
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if (!$user) {
                $error = 'No account found with that email.';
            } elseif (!$user['is_active']) {
                $error = 'Your account is inactive. Please contact support.';
            } elseif (!password_verify($password, $user['password'])) {
                $error = 'Incorrect password.';
            } else {
                session_regenerate_id(true);
                $_SESSION['user_id']    = $user['id'];
                $_SESSION['user_name']  = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                header('Location: ' . APP_URL . '/user/dashboard.php');
                exit;
            }
        } catch (Exception $e) {
            $error = 'Database error. Please try again.';
        }
    }
    } // end csrf check
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | <?php echo APP_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/assets/css/style.css">
</head>
<body class="bg-gradient-to-br from-blue-900 to-blue-700 min-h-screen flex items-center justify-center">
<div class="w-full max-w-md px-4">
    <div class="bg-white rounded-2xl shadow-2xl overflow-hidden">
        <div class="bg-gradient-to-r from-blue-800 to-blue-900 px-8 py-6 text-center">
            <a href="<?php echo APP_URL; ?>">
                <span class="text-orange-400 font-bold text-3xl">Circu</span><span class="text-white font-bold text-3xl">Lens</span>
            </a>
            <p class="text-blue-300 text-sm mt-1">GTU Circular Management</p>
        </div>
        <div class="px-8 py-8">
            <h2 class="text-2xl font-bold text-gray-800 mb-6 text-center">Welcome Back</h2>

            <?php if ($error): ?>
                <div class="bg-red-50 border border-red-300 text-red-700 px-4 py-3 rounded-lg mb-4 text-sm" role="alert">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-4">
                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                    <input type="email" name="email" required autocomplete="email"
                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                           class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm"
                           placeholder="you@example.com">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                    <input type="password" name="password" required autocomplete="current-password"
                           class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm"
                           placeholder="••••••••">
                </div>
                <div class="flex justify-end">
                    <a href="<?php echo APP_URL; ?>/user/forgot_password.php" class="text-blue-600 hover:text-blue-800 text-sm">Forgot password?</a>
                </div>
                <button type="submit"
                        class="w-full bg-blue-800 hover:bg-blue-700 text-white py-3 rounded-xl font-semibold transition-colors">
                    Sign In
                </button>
            </form>

            <div class="mt-6 text-center space-y-2">
                <p class="text-gray-500 text-sm">
                    Don't have an account?
                    <a href="<?php echo APP_URL; ?>/user/register.php" class="text-blue-600 hover:text-blue-800 font-medium">Register</a>
                </p>
                <a href="<?php echo APP_URL; ?>" class="text-gray-400 hover:text-gray-600 text-sm block">← Back to Home</a>
            </div>
        </div>
    </div>
</div>
</body>
</html>
