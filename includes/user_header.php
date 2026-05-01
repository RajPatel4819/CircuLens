<?php
require_once __DIR__ . '/../config/auth.php';
requireUser();
$flash = getFlash();
$user  = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) . ' | ' . APP_NAME : APP_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/assets/css/style.css">
</head>
<body class="bg-gray-50 min-h-screen flex flex-col">
<nav class="bg-blue-800 shadow-lg">
    <div class="max-w-7xl mx-auto px-4">
        <div class="flex justify-between items-center h-16">
            <div class="flex items-center space-x-6">
                <a href="<?php echo APP_URL; ?>" class="flex items-center">
                    <span class="text-orange-400 font-bold text-xl">Circu</span><span class="text-white font-bold text-xl">Lens</span>
                </a>
                <div class="hidden md:flex space-x-1">
                    <a href="<?php echo APP_URL; ?>/user/dashboard.php"   class="text-blue-200 hover:text-white text-sm font-medium px-3 py-2 rounded-md hover:bg-blue-700">Dashboard</a>
                    <a href="<?php echo APP_URL; ?>/user/preferences.php" class="text-blue-200 hover:text-white text-sm font-medium px-3 py-2 rounded-md hover:bg-blue-700">Preferences</a>
                    <a href="<?php echo APP_URL; ?>/user/profile.php"     class="text-blue-200 hover:text-white text-sm font-medium px-3 py-2 rounded-md hover:bg-blue-700">Profile</a>
                </div>
            </div>
            <div class="flex items-center space-x-3">
                <span class="text-blue-300 text-sm hidden md:block">Hello, <?php echo htmlspecialchars($user['name']); ?></span>
                <a href="<?php echo APP_URL; ?>/user/logout.php" class="bg-orange-600 hover:bg-orange-500 text-white px-3 py-2 rounded-lg text-sm font-medium">Logout</a>
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
<main class="flex-1">
