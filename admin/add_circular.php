<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';
$pageTitle = 'Add Circular';

$types = ['academic', 'examination', 'events', 'placement', 'timetable', 'general'];
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $description = trim($_POST['description'] ?? '');
    $title       = trim($_POST['title']       ?? '');
    $sourceUrl   = trim($_POST['source_url']  ?? '');

    // Auto-detect circular type from description
    function detectCircularType($text) {
        $text = strtolower($text);
        $keywords = [
            'academic'    => ['academic', 'enrollment', 'admission', 'syllabus', 'scholarship', 'registration', 'course'],
            'examination' => ['exam', 'test', 'result', 'theory', 'practical', 'viva', 'hall ticket', 'center', 'internal'],
            'placement'   => ['placement', 'job', 'recruitment', 'tcs', 'company', 'interview', 'drive', 'hiring'],
            'timetable'   => ['timetable', 'schedule', 'lecture', 'timing'],
            'events'      => ['event', 'fest', 'celebration', 'workshop', 'seminar', 'competition', 'day']
        ];
        
        foreach ($keywords as $type => $keys) {
            foreach ($keys as $key) {
                if (strpos($text, $key) !== false) return $type;
            }
        }
        return 'general';
    }

    $circType = detectCircularType($description);

    // Auto-populate title from description if empty
    if (!$title && $description) {
        $title = mb_substr($description, 0, 100) . (mb_strlen($description) > 100 ? '...' : '');
    }

    if (!$description) {
        $error = 'Description (Circular Text) is required.';
    } elseif (!in_array($circType, $types)) {
        $error = 'Invalid circular type.';
    } else {
        $pdfPath = null;

        if (!empty($_FILES['pdf']['name'])) {
            $file     = $_FILES['pdf'];
            $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowed  = ['pdf'];

            if (!in_array($ext, $allowed)) {
                $error = 'Only PDF files are allowed.';
            } elseif ($file['size'] > MAX_FILE_SIZE) {
                $error = 'File size exceeds 10 MB limit.';
            } elseif ($file['error'] !== UPLOAD_ERR_OK) {
                $error = 'File upload error.';
            } else {
                $filename = uniqid('circ_', true) . '.pdf';
                $destPath = UPLOAD_DIR . $filename;
                if (!is_dir(UPLOAD_DIR)) {
                    mkdir(UPLOAD_DIR, 0755, true);
                }
                if (move_uploaded_file($file['tmp_name'], $destPath)) {
                    $pdfPath = $filename;
                } else {
                    $error = 'Failed to save uploaded file.';
                }
            }
        }

        if (!$error) {
            try {
                $hash = null;
                if ($pdfPath) {
                    $hash = hash_file('sha256', UPLOAD_DIR . $pdfPath);
                }
                $stmt = db()->prepare(
                    'INSERT INTO circulars (title, description, pdf_path, circular_type, source, source_url, content_hash, is_active)
                     VALUES (?, ?, ?, ?, "admin", ?, ?, 1)'
                );
                $stmt->execute([$title, $description, $pdfPath, $circType, $sourceUrl ?: null, $hash]);
                
                $newId = db()->lastInsertId();
                
                // Trigger email notifications in background
                $pythonPath = 'python'; // or full path like 'C:/Python39/python.exe'
                $scriptPath = realpath(__DIR__ . '/../scraper/notifier.py');
                if ($scriptPath) {
                    // Use start /B on Windows to run in background without blocking
                    $cmd = "start /B $pythonPath \"$scriptPath\" --id $newId > NUL 2>&1";
                    pclose(popen($cmd, "r"));
                }

                setFlash('success', 'Circular added successfully. Notifications are being sent.');
                header('Location: ' . APP_URL . '/admin/circulars.php');
                exit;
            } catch (Exception $e) {
                $error = 'Database error. Please try again.';
            }
        }
    }
}

include __DIR__ . '/../includes/admin_header.php';
?>

<div class="mb-6">
    <a href="<?php echo APP_URL; ?>/admin/circulars.php" class="text-blue-600 hover:text-blue-800 text-sm font-medium">← Back to Circulars</a>
    <h1 class="text-2xl font-bold text-gray-900 mt-2">Add New Circular</h1>
</div>

<div class="max-w-2xl bg-white rounded-xl shadow-sm border border-gray-100 p-8">
    <?php if ($error): ?>
        <div class="bg-red-50 border border-red-300 text-red-700 px-4 py-3 rounded-lg mb-6 text-sm" role="alert">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" class="space-y-6">
        <div>
            <label class="block text-sm font-bold text-gray-700 mb-1">
                Circular Description / Text <span class="text-red-500">*</span>
            </label>
            <textarea name="description" rows="6" required maxlength="5000"
                      class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm resize-none shadow-sm"
                      placeholder="Enter the main text of the circular (this will be the clickable link on the portal)..."><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Upload PDF File</label>
                <input type="file" name="pdf" accept=".pdf"
                       class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-blue-50 file:text-blue-700 file:font-medium hover:file:bg-blue-100">
                <p class="text-[10px] text-gray-400 mt-1 uppercase tracking-wider">MAX: 10MB | PDF ONLY</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">External Source URL (Optional)</label>
                <input type="url" name="source_url" maxlength="500"
                       value="<?php echo htmlspecialchars($_POST['source_url'] ?? ''); ?>"
                       class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm"
                       placeholder="https://www.gtu.ac.in/...">
            </div>
        </div>

        <div class="flex gap-4 pt-4">
            <button type="submit"
                    class="bg-blue-800 hover:bg-blue-700 text-white px-10 py-3 rounded-xl font-bold text-sm transition-all shadow-md active:scale-95">
                UPLOAD CIRCULAR
            </button>
            <a href="<?php echo APP_URL; ?>/admin/circulars.php"
               class="px-8 py-3 border border-gray-300 text-gray-600 rounded-xl text-sm font-bold hover:bg-gray-50 transition-colors">
                CANCEL
            </a>
        </div>
    </form>
</div>

<?php include __DIR__ . '/../includes/admin_footer.php'; ?>
