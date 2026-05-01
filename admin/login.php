<?php
require_once __DIR__ . '/../config/auth.php';
startSession();

if (isAdminLoggedIn()) {
    header('Location: ' . APP_URL . '/admin/index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/../config/database.php';

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$username || !$password) {
        $error = 'Please enter both username and password.';
    } else {
        try {
            $stmt = db()->prepare('SELECT id, username, password FROM admin WHERE username = ? LIMIT 1');
            $stmt->execute([$username]);
            $admin = $stmt->fetch();

            if ($admin && password_verify($password, $admin['password'])) {
                session_regenerate_id(true);
                $_SESSION['admin_id']       = $admin['id'];
                $_SESSION['admin_username'] = $admin['username'];
                header('Location: ' . APP_URL . '/admin/index.php');
                exit;
            } else {
                $error = 'Invalid username or password.';
            }
        } catch (Exception $e) {
            $error = 'Database error. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login | <?php echo APP_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/assets/css/style.css">
</head>
<body class="bg-gradient-to-br from-blue-900 to-blue-800 min-h-screen flex items-center justify-center">
<div class="w-full max-w-md px-4">
    <div class="bg-white rounded-2xl shadow-2xl overflow-hidden">
        <div class="bg-blue-900 px-8 py-6 text-center">
            <span class="text-orange-400 font-bold text-3xl">Circu</span><span class="text-white font-bold text-3xl">Lens</span>
            <p class="text-blue-300 text-sm mt-1">Admin Panel</p>
        </div>
        <div class="px-8 py-8">
            <h2 class="text-2xl font-bold text-gray-800 mb-6 text-center">Admin Login</h2>

            <?php if ($error): ?>
                <div class="bg-red-50 border border-red-300 text-red-700 px-4 py-3 rounded-lg mb-4 text-sm" role="alert">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Username</label>
                    <input type="text" name="username" required autocomplete="username"
                           value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                           class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                           placeholder="admin">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                    <input type="password" name="password" required autocomplete="current-password"
                           class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                           placeholder="••••••••">
                </div>
                <button type="submit"
                        class="w-full bg-blue-800 hover:bg-blue-700 text-white py-3 rounded-xl font-semibold transition-colors mt-2">
                    Sign In
                </button>
            </form>

            <div class="mt-6 text-center">
                <a href="<?php echo APP_URL; ?>" class="text-blue-600 hover:text-blue-800 text-sm">
                    ← Back to Public Site
                </a>
            </div>
        </div>
    </div>
</div>
</body>
</html>
