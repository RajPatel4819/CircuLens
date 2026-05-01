<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';
startSession();

if (isUserLoggedIn()) {
    header('Location: ' . APP_URL . '/user/dashboard.php');
    exit;
}

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['name']     ?? '');
    $email    = trim($_POST['email']    ?? '');
    $password = $_POST['password']      ?? '';
    $confirm  = $_POST['confirm']       ?? '';

    if (!$name || !$email || !$password || !$confirm) {
        $error = 'All fields are required.';
    } elseif (strlen($name) < 2 || strlen($name) > 100) {
        $error = 'Name must be between 2 and 100 characters.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        try {
            $check = db()->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
            $check->execute([$email]);
            if ($check->fetch()) {
                $error = 'An account with this email already exists.';
            } else {
                $hash = password_hash($password, PASSWORD_BCRYPT);
                $stmt = db()->prepare('INSERT INTO users (name, email, password, is_active) VALUES (?, ?, ?, 1)');
                $stmt->execute([$name, $email, $hash]);
                setFlash('success', 'Account created successfully! Please sign in.');
                header('Location: ' . APP_URL . '/user/login.php');
                exit;
            }
        } catch (Exception $e) {
            $error = 'Registration failed. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | <?php echo APP_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/assets/css/style.css">
</head>
<body class="bg-gradient-to-br from-blue-900 to-blue-700 min-h-screen flex items-center justify-center py-8">
<div class="w-full max-w-md px-4">
    <div class="bg-white rounded-2xl shadow-2xl overflow-hidden">
        <div class="bg-gradient-to-r from-blue-800 to-blue-900 px-8 py-6 text-center">
            <a href="<?php echo APP_URL; ?>">
                <span class="text-orange-400 font-bold text-3xl">Circu</span><span class="text-white font-bold text-3xl">Lens</span>
            </a>
            <p class="text-blue-300 text-sm mt-1">Create Your Account</p>
        </div>
        <div class="px-8 py-8">
            <h2 class="text-2xl font-bold text-gray-800 mb-6 text-center">Register</h2>

            <?php if ($error): ?>
                <div class="bg-red-50 border border-red-300 text-red-700 px-4 py-3 rounded-lg mb-4 text-sm" role="alert">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
                    <input type="text" name="name" required maxlength="100"
                           value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>"
                           class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm"
                           placeholder="Your full name">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                    <input type="email" name="email" required autocomplete="email"
                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                           class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm"
                           placeholder="you@example.com">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                    <input type="password" name="password" required minlength="6" autocomplete="new-password"
                           class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm"
                           placeholder="At least 6 characters">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Confirm Password</label>
                    <input type="password" name="confirm" required autocomplete="new-password"
                           class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm"
                           placeholder="Repeat password">
                </div>
                <button type="submit"
                        class="w-full bg-orange-600 hover:bg-orange-500 text-white py-3 rounded-xl font-semibold transition-colors">
                    Create Account
                </button>
            </form>

            <div class="mt-6 text-center space-y-2">
                <p class="text-gray-500 text-sm">
                    Already have an account?
                    <a href="<?php echo APP_URL; ?>/user/login.php" class="text-blue-600 hover:text-blue-800 font-medium">Sign In</a>
                </p>
                <a href="<?php echo APP_URL; ?>" class="text-gray-400 hover:text-gray-600 text-sm block">← Back to Home</a>
            </div>
        </div>
    </div>
</div>
</body>
</html>
