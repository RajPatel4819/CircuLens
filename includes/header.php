<?php
require_once __DIR__ . '/../config/auth.php';
$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) . ' - ' . APP_NAME : APP_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/assets/css/style.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: { 600: '#2563eb', 700: '#1d4ed8', 800: '#1e40af' },
                        accent:  { 500: '#f97316', 600: '#ea580c' }
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-50 min-h-screen flex flex-col">
<nav class="bg-blue-800 shadow-lg">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">
            <a href="<?php echo APP_URL; ?>" class="flex items-center space-x-1">
                <span class="text-orange-400 font-bold text-2xl">Circu</span><span class="text-white font-bold text-2xl">Lens</span>
            </a>
            <div class="flex items-center space-x-4">
                <a href="<?php echo APP_URL; ?>" class="text-blue-100 hover:text-white text-sm font-medium">Home</a>
                <?php if (isUserLoggedIn()): ?>
                    <a href="<?php echo APP_URL; ?>/user/dashboard.php" class="text-blue-100 hover:text-white text-sm font-medium">Dashboard</a>
                    <a href="<?php echo APP_URL; ?>/user/logout.php" class="bg-orange-600 hover:bg-orange-500 text-white px-4 py-2 rounded-lg text-sm font-medium">Logout</a>
                <?php else: ?>
                    <a href="<?php echo APP_URL; ?>/user/login.php" class="text-blue-100 hover:text-white text-sm font-medium">Login</a>
                    <a href="<?php echo APP_URL; ?>/user/register.php" class="bg-orange-600 hover:bg-orange-500 text-white px-4 py-2 rounded-lg text-sm font-medium">Register</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>
<?php if ($flash): ?>
<div class="max-w-7xl mx-auto px-4 pt-4">
    <div class="<?php echo $flash['type'] === 'success' ? 'bg-green-100 border border-green-400 text-green-700' : 'bg-red-100 border border-red-400 text-red-700'; ?> px-4 py-3 rounded-lg" role="alert">
        <?php echo htmlspecialchars($flash['message']); ?>
    </div>
</div>
<?php endif; ?>
