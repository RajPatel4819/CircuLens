<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';
$pageTitle = 'My Profile';

$user = getCurrentUser();

try {
    $stmt = db()->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$user['id']]);
    $userData = $stmt->fetch();
    $dbOk = true;
} catch (Exception $e) {
    $userData = null;
    $dbOk = false;
}

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_name') {
        $name = trim($_POST['name'] ?? '');
        if (strlen($name) < 2 || strlen($name) > 100) {
            $error = 'Name must be 2–100 characters.';
        } else {
            try {
                db()->prepare('UPDATE users SET name = ? WHERE id = ?')->execute([$name, $user['id']]);
                $_SESSION['user_name'] = $name;
                setFlash('success', 'Name updated successfully.');
                header('Location: ' . APP_URL . '/user/profile.php');
                exit;
            } catch (Exception $e) {
                $error = 'Update failed. Please try again.';
            }
        }
    } elseif ($action === 'change_password') {
        $current = $_POST['current_password'] ?? '';
        $new     = $_POST['new_password']     ?? '';
        $confirm = $_POST['confirm']          ?? '';

        if (!$current || !$new || !$confirm) {
            $error = 'All password fields are required.';
        } elseif (strlen($new) < 6) {
            $error = 'New password must be at least 6 characters.';
        } elseif ($new !== $confirm) {
            $error = 'New passwords do not match.';
        } elseif (!$userData || !password_verify($current, $userData['password'])) {
            $error = 'Current password is incorrect.';
        } else {
            try {
                $hash = password_hash($new, PASSWORD_BCRYPT);
                db()->prepare('UPDATE users SET password = ? WHERE id = ?')->execute([$hash, $user['id']]);
                setFlash('success', 'Password changed successfully.');
                header('Location: ' . APP_URL . '/user/profile.php');
                exit;
            } catch (Exception $e) {
                $error = 'Password change failed. Please try again.';
            }
        }
    }
    } // end csrf check
}

include __DIR__ . '/../includes/user_header.php';
?>

<div class="max-w-2xl mx-auto px-4 py-8">
    <h1 class="text-2xl font-bold text-gray-900 mb-6">My Profile</h1>

    <?php if ($error): ?>
        <div class="bg-red-50 border border-red-300 text-red-700 px-4 py-3 rounded-lg mb-6 text-sm" role="alert">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <!-- Profile Info -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-6">
        <div class="flex items-center gap-4 mb-6">
            <div class="w-16 h-16 bg-blue-800 rounded-full flex items-center justify-center text-white font-bold text-2xl">
                <?php echo strtoupper(mb_substr($user['name'], 0, 1)); ?>
            </div>
            <div>
                <h2 class="text-lg font-semibold text-gray-900"><?php echo htmlspecialchars($user['name']); ?></h2>
                <p class="text-gray-500 text-sm"><?php echo htmlspecialchars($user['email']); ?></p>
                <?php if ($userData): ?>
                    <p class="text-gray-400 text-xs mt-0.5">Member since <?php echo date('F Y', strtotime($userData['created_at'])); ?></p>
                <?php endif; ?>
            </div>
        </div>

        <form method="POST" class="space-y-4">
            <input type="hidden" name="action" value="update_name">
            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
                <input type="text" name="name" required maxlength="100"
                       value="<?php echo htmlspecialchars($userData['name'] ?? $user['name']); ?>"
                       class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                <input type="email" value="<?php echo htmlspecialchars($user['email']); ?>" disabled
                       class="w-full px-4 py-2.5 border border-gray-200 rounded-xl bg-gray-50 text-gray-400 text-sm">
                <p class="text-gray-400 text-xs mt-0.5">Email cannot be changed.</p>
            </div>
            <button type="submit" class="bg-blue-800 hover:bg-blue-700 text-white px-6 py-2.5 rounded-xl text-sm font-medium transition-colors">
                Update Name
            </button>
        </form>
    </div>

    <!-- Change Password -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Change Password</h2>
        <form method="POST" class="space-y-4">
            <input type="hidden" name="action" value="change_password">
            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Current Password</label>
                <input type="password" name="current_password" required autocomplete="current-password"
                       class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm"
                       placeholder="••••••••">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">New Password</label>
                <input type="password" name="new_password" required minlength="6" autocomplete="new-password"
                       class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm"
                       placeholder="At least 6 characters">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Confirm New Password</label>
                <input type="password" name="confirm" required autocomplete="new-password"
                       class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm"
                       placeholder="Repeat new password">
            </div>
            <button type="submit" class="bg-orange-600 hover:bg-orange-500 text-white px-6 py-2.5 rounded-xl text-sm font-medium transition-colors">
                Change Password
            </button>
        </form>
    </div>

    <div class="mt-4 text-center">
        <a href="<?php echo APP_URL; ?>/user/dashboard.php" class="text-blue-600 hover:text-blue-800 text-sm">← Back to Dashboard</a>
    </div>
</div>

<?php include __DIR__ . '/../includes/user_footer.php'; ?>
