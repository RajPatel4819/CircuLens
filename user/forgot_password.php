<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';
startSession();

$error   = '';
$success = '';
$step    = 'email'; // 'email' or 'reset'

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['send_reset'])) {
        $email = trim($_POST['email'] ?? '');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } else {
            try {
                $stmt = db()->prepare('SELECT id FROM users WHERE email = ? AND is_active = 1 LIMIT 1');
                $stmt->execute([$email]);
                $user = $stmt->fetch();
                if ($user) {
                    $token   = bin2hex(random_bytes(32));
                    $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
                    db()->prepare('UPDATE users SET reset_token = ?, reset_expires = ? WHERE id = ?')
                        ->execute([$token, $expires, $user['id']]);
                    // In production, send email with reset link.
                    // For demo, store token in session for display.
                    $_SESSION['demo_reset_token'] = $token;
                    $_SESSION['demo_reset_email'] = $email;
                }
                // Always show the same message to prevent enumeration
                $success = 'If that email is registered, a reset link has been sent.';
            } catch (Exception $e) {
                $error = 'An error occurred. Please try again.';
            }
        }
    } elseif (isset($_POST['do_reset'])) {
        $token    = trim($_POST['token']    ?? '');
        $password = $_POST['new_password']  ?? '';
        $confirm  = $_POST['confirm']       ?? '';

        if (!$token || !$password || !$confirm) {
            $error = 'All fields are required.';
            $step  = 'reset';
        } elseif (strlen($password) < 6) {
            $error = 'Password must be at least 6 characters.';
            $step  = 'reset';
        } elseif ($password !== $confirm) {
            $error = 'Passwords do not match.';
            $step  = 'reset';
        } else {
            try {
                $stmt = db()->prepare('SELECT id FROM users WHERE reset_token = ? AND reset_expires > NOW() LIMIT 1');
                $stmt->execute([$token]);
                $user = $stmt->fetch();
                if (!$user) {
                    $error = 'Invalid or expired reset token.';
                    $step  = 'reset';
                } else {
                    $hash = password_hash($password, PASSWORD_BCRYPT);
                    db()->prepare('UPDATE users SET password = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?')
                        ->execute([$hash, $user['id']]);
                    unset($_SESSION['demo_reset_token'], $_SESSION['demo_reset_email']);
                    setFlash('success', 'Password reset successfully. Please sign in.');
                    header('Location: ' . APP_URL . '/user/login.php');
                    exit;
                }
            } catch (Exception $e) {
                $error = 'An error occurred. Please try again.';
            }
        }
    }
}

// Check for token in URL (from email link)
$tokenFromUrl = filter_input(INPUT_GET, 'token', FILTER_SANITIZE_SPECIAL_CHARS) ?? '';
if ($tokenFromUrl) {
    $step = 'reset';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password | <?php echo APP_NAME; ?></title>
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
        </div>
        <div class="px-8 py-8">
            <h2 class="text-2xl font-bold text-gray-800 mb-2 text-center">
                <?php echo $step === 'reset' ? 'Reset Password' : 'Forgot Password'; ?>
            </h2>
            <p class="text-gray-500 text-sm text-center mb-6">
                <?php echo $step === 'reset' ? 'Enter your new password.' : 'Enter your email to receive a reset link.'; ?>
            </p>

            <?php if ($error): ?>
                <div class="bg-red-50 border border-red-300 text-red-700 px-4 py-3 rounded-lg mb-4 text-sm" role="alert">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="bg-green-50 border border-green-300 text-green-700 px-4 py-3 rounded-lg mb-4 text-sm">
                    <?php echo htmlspecialchars($success); ?>
                    <?php if (isset($_SESSION['demo_reset_token'])): ?>
                        <div class="mt-2 p-2 bg-yellow-50 rounded text-yellow-800 text-xs break-all">
                            <strong>Demo mode:</strong> Use this token to reset:
                            <a href="?token=<?php echo htmlspecialchars($_SESSION['demo_reset_token']); ?>"
                               class="underline ml-1">Reset now</a>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if ($step === 'reset'): ?>
                <form method="POST" class="space-y-4">
                    <input type="hidden" name="token" value="<?php echo htmlspecialchars($tokenFromUrl ?: ($_SESSION['demo_reset_token'] ?? '')); ?>">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">New Password</label>
                        <input type="password" name="new_password" required minlength="6"
                               class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm"
                               placeholder="At least 6 characters">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Confirm New Password</label>
                        <input type="password" name="confirm" required
                               class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm"
                               placeholder="Repeat password">
                    </div>
                    <button type="submit" name="do_reset"
                            class="w-full bg-blue-800 hover:bg-blue-700 text-white py-3 rounded-xl font-semibold transition-colors">
                        Reset Password
                    </button>
                </form>
            <?php else: ?>
                <form method="POST" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                        <input type="email" name="email" required
                               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                               class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm"
                               placeholder="you@example.com">
                    </div>
                    <button type="submit" name="send_reset"
                            class="w-full bg-blue-800 hover:bg-blue-700 text-white py-3 rounded-xl font-semibold transition-colors">
                        Send Reset Link
                    </button>
                </form>
            <?php endif; ?>

            <div class="mt-6 text-center">
                <a href="<?php echo APP_URL; ?>/user/login.php" class="text-blue-600 hover:text-blue-800 text-sm">← Back to Login</a>
            </div>
        </div>
    </div>
</div>
</body>
</html>
