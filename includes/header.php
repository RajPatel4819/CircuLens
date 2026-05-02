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
                        gtublue: '#1D2951',
                        gtured: '#D32F2F',
                    }
                }
            }
        }
    </script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-[#F8F9FA] min-h-screen flex flex-col">

<!-- Top Bar -->
<div class="bg-white border-b border-gray-100 py-2 hidden md:block">
    <div class="max-w-7xl mx-auto px-4 flex justify-between items-center text-[11px] text-gray-500 uppercase tracking-wider">
        <div class="flex gap-4">
            <span>📞 +079-23267521/570</span>
            <span>📧 info@gtu.ac.in</span>
        </div>
        <div class="flex gap-4">
            <a href="#" class="hover:text-gtublue">IQAC</a>
            <a href="#" class="hover:text-gtublue">NIRF</a>
            <a href="#" class="hover:text-gtublue">Screen Reader</a>
        </div>
    </div>
</div>

<!-- Header -->
<header class="bg-white shadow-sm sticky top-0 z-50">
    <div class="max-w-7xl mx-auto px-4 py-3 flex justify-between items-center">
        <a href="<?php echo APP_URL; ?>" class="flex items-center gap-3">
            <img src="https://www.gtu.ac.in/img/logo.png" alt="GTU Logo" class="h-12 md:h-16">
            <div class="hidden sm:block">
                <h1 class="text-gtublue font-bold text-lg md:text-xl leading-tight">GUJARAT TECHNOLOGICAL UNIVERSITY</h1>
                <p class="text-[10px] md:text-xs text-gtured font-semibold tracking-widest">INTERNATIONAL INNOVATIVE UNIVERSITY</p>
            </div>
        </a>
        
        <div class="flex items-center gap-2 md:gap-6">
            <nav class="hidden lg:flex items-center gap-6 text-sm font-semibold text-gray-700">
                <a href="<?php echo APP_URL; ?>" class="hover:text-gtublue border-b-2 border-transparent hover:border-gtublue py-1">Circulars</a>
                <a href="#" class="hover:text-gtublue border-b-2 border-transparent hover:border-gtublue py-1">About Us</a>
                <a href="#" class="hover:text-gtublue border-b-2 border-transparent hover:border-gtublue py-1">Contact</a>
            </nav>
        </div>
    </div>
</header>

<?php if ($flash): ?>
<div class="max-w-7xl mx-auto px-4 pt-4">
    <div class="<?php echo $flash['type'] === 'success' ? 'bg-green-50 border border-green-200 text-green-700' : 'bg-red-50 border border-red-200 text-red-700'; ?> px-4 py-3 rounded text-sm font-medium" role="alert">
        <?php echo htmlspecialchars($flash['message']); ?>
    </div>
</div>
<?php endif; ?>
