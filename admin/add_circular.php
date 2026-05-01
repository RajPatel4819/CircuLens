<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';
$pageTitle = 'Add Circular';

$types = ['academic', 'examination', 'events', 'placement', 'timetable', 'general'];
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title       = trim($_POST['title']       ?? '');
    $description = trim($_POST['description'] ?? '');
    $circType    = $_POST['circular_type']    ?? 'general';
    $sourceUrl   = trim($_POST['source_url']  ?? '');

    if (!$title) {
        $error = 'Title is required.';
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
                setFlash('success', 'Circular added successfully.');
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

    <form method="POST" enctype="multipart/form-data" class="space-y-5">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
                Title <span class="text-red-500">*</span>
            </label>
            <input type="text" name="title" required maxlength="255"
                   value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>"
                   class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm"
                   placeholder="e.g. Academic Calendar 2024-25">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
            <textarea name="description" rows="4" maxlength="5000"
                      class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm resize-none"
                      placeholder="Describe the circular content..."><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
                Circular Type <span class="text-red-500">*</span>
            </label>
            <select name="circular_type"
                    class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                <?php foreach ($types as $t): ?>
                    <option value="<?php echo $t; ?>" <?php echo ($_POST['circular_type'] ?? 'general') === $t ? 'selected' : ''; ?>>
                        <?php echo ucfirst($t); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Upload PDF (optional)</label>
            <input type="file" name="pdf" accept=".pdf"
                   class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-blue-50 file:text-blue-700 file:font-medium hover:file:bg-blue-100">
            <p class="text-gray-400 text-xs mt-1">Max size: 10 MB. PDF files only.</p>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Source URL (optional)</label>
            <input type="url" name="source_url" maxlength="500"
                   value="<?php echo htmlspecialchars($_POST['source_url'] ?? ''); ?>"
                   class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm"
                   placeholder="https://www.gtu.ac.in/...">
        </div>

        <div class="flex gap-3 pt-2">
            <button type="submit"
                    class="bg-blue-800 hover:bg-blue-700 text-white px-8 py-2.5 rounded-xl font-semibold text-sm transition-colors">
                Add Circular
            </button>
            <a href="<?php echo APP_URL; ?>/admin/circulars.php"
               class="px-6 py-2.5 border border-gray-300 text-gray-700 rounded-xl text-sm font-medium hover:bg-gray-50 transition-colors">
                Cancel
            </a>
        </div>
    </form>
</div>

<?php include __DIR__ . '/../includes/admin_footer.php'; ?>
