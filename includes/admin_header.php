<?php
require_once __DIR__ . '/../config/auth.php';
requireAdmin();
$flash = getFlash();
$admin = getCurrentAdmin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) . ' - Admin | ' . APP_NAME : 'Admin | ' . APP_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/assets/css/style.css">
</head>
<body class="bg-gray-100 min-h-screen">
<nav class="bg-blue-900 shadow-xl">
    <div class="max-w-7xl mx-auto px-4">
        <div class="flex justify-between items-center h-16">
            <div class="flex items-center space-x-6">
                <a href="<?php echo APP_URL; ?>/admin/index.php" class="flex items-center">
                    <span class="text-orange-400 font-bold text-xl">Circu</span><span class="text-white font-bold text-xl">Lens</span>
                    <span class="ml-2 text-blue-300 text-sm font-medium">Admin</span>
                </a>
                <div class="hidden md:flex space-x-1">
                    <a href="<?php echo APP_URL; ?>/admin/index.php" class="text-blue-200 hover:text-white text-sm font-medium px-3 py-2 rounded-md hover:bg-blue-800">Dashboard</a>
                    <a href="<?php echo APP_URL; ?>/admin/circulars.php" class="text-blue-200 hover:text-white text-sm font-medium px-3 py-2 rounded-md hover:bg-blue-800">Circulars</a>
                    <a href="<?php echo APP_URL; ?>/admin/add_circular.php" class="text-blue-200 hover:text-white text-sm font-medium px-3 py-2 rounded-md hover:bg-blue-800">Add Circular</a>
                </div>
            </div>
            <div class="flex items-center space-x-3">
                <span class="text-blue-300 text-sm">Welcome, <?php echo htmlspecialchars($admin['username']); ?></span>
                <a href="<?php echo APP_URL; ?>/admin/logout.php" class="bg-orange-600 hover:bg-orange-500 text-white px-3 py-2 rounded-lg text-sm font-medium">Logout</a>
            </div>
        </div>
    </div>
</nav>
<?php if ($flash): ?>
<div class="max-w-7xl mx-auto px-4 pt-4">
    <div class="<?php echo $flash['type'] === 'success' ? 'bg-green-100 border border-green-400 text-green-700' : 'bg-red-100 border border-red-400 text-red-700'; ?> px-4 py-3 rounded-lg flex items-center justify-between" role="alert">
        <span><?php echo htmlspecialchars($flash['message']); ?></span>
    </div>
</div>
<?php endif; ?>
<main class="max-w-7xl mx-auto px-4 py-6">
