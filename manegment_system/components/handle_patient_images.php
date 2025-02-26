<?php
require_once 'auth.php';
require_once 'db.php';

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['image'])) {
    $patient_id = $_POST['patient_id'];
    $image_type = $_POST['image_type'] ?? 'other';

    // Get patient data
    $stmt = $pdo->prepare("SELECT * FROM patients WHERE id = ?");
    $stmt->execute([$patient_id]);
    $patient = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$patient) {
        die("Patient not found");
    }

    // Determine column based on image type
    $column = match ($image_type) {
        'xray' => 'xray_images',
        'test' => 'test_files',
        'prescription' => 'prescriptions',
        default => 'file_path'
    };

    // Create upload directory
    $upload_dir = __DIR__ . '/../uploads/';
    $medical_id = $patient['medical_id'] ?? 'default';
    $date_dir = date('Y-m-d');
    $target_dir = "{$upload_dir}{$column}/{$date_dir}/{$medical_id}/";

    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    // Handle file upload
    $file_name = uniqid() . '_' . basename($_FILES['image']['name']);
    $target_file = $target_dir . $file_name;

    if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
        // Save relative path to database
        $relative_path = "../uploads/{$column}/{$date_dir}/{$medical_id}/{$file_name}";
        $existing = json_decode($patient[$column] ?? '[]', true);
        $existing[] = $relative_path;

        $stmt = $pdo->prepare("UPDATE patients SET {$column} = ? WHERE id = ?");
        $stmt->execute([json_encode($existing), $patient_id]);

        $_SESSION['message'] = 'File uploaded successfully';
    } else {
        $_SESSION['error'] = 'File upload failed';
    }

    header("Location: {$_SERVER['PHP_SELF']}?id={$patient_id}");
    exit;
}

// Handle file deletion
if (isset($_GET['delete_image'])) {
    $file_path = $_GET['delete_image'];
    $patient_id = $_GET['patient_id'];

    // Convert to absolute path
    $absolute_path = realpath(__DIR__ . '/../' . $file_path);

    // Remove from filesystem
    if ($absolute_path && file_exists($absolute_path)) {
        unlink($absolute_path);
    }

    // Remove from database
    $stmt = $pdo->prepare("SELECT * FROM patients WHERE id = ?");
    $stmt->execute([$patient_id]);
    $patient = $stmt->fetch(PDO::FETCH_ASSOC);

    foreach (['xray_images', 'test_files', 'prescriptions', 'file_path'] as $col) {
        $files = json_decode($patient[$col] ?? '[]', true);
        $files = array_filter($files, fn($f) => $f !== $file_path);
        $stmt = $pdo->prepare("UPDATE patients SET {$col} = ? WHERE id = ?");
        $stmt->execute([json_encode(array_values($files)), $patient_id]);
    }

    $_SESSION['message'] = 'File deleted successfully';
    header("Location: {$_SERVER['PHP_SELF']}?id={$patient_id}");
    exit;
}
// Get patient data for display
if (isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM patients WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $patient = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>File Management</title>
    <style>
        .image-container {
            position: relative;
            display: inline-block;
            margin: 15px;
            border: 1px solid #ddd;
            padding: 10px;
            border-radius: 8px;
        }

        .thumbnail {
            max-width: 200px;
            height: auto;
            display: block;
            margin-bottom: 5px;
        }

        .delete-btn {
            position: absolute;
            top: 5px;
            right: 5px;
            background: #ff4444;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 4px;
            cursor: pointer;
            /* Ensure cursor shows pointer */
            font-size: 12px;
            z-index: 10;
            /* Add this line */
        }

        .file-list {
            margin-bottom: 30px;
        }
    </style>
</head>

<body>
    <?php if (isset($_SESSION['message'])): ?>
        <div><?= $_SESSION['message'] ?></div>
        <?php unset($_SESSION['message']) ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div style="color: red"><?= $_SESSION['error'] ?></div>
        <?php unset($_SESSION['error']) ?>
    <?php endif; ?>

    <h1>Manage Patient Files</h1>

    <form method="post" enctype="multipart/form-data">
        <input type="hidden" name="patient_id" value="<?= htmlspecialchars($_GET['id'] ?? '') ?>">

        <div>
            <label>File:</label>
            <input type="file" name="image" required>
        </div>

        <div>
            <label>Type:</label>
            <select name="image_type">
                <option value="xray">X-Ray</option>
                <option value="test">Test Files</option>
                <option value="prescription">Prescriptions</option>
                <option value="other">Other</option>
            </select>
        </div>

        <button type="submit">Upload</button>
    </form>

    <?php if (isset($patient)): ?>
        <h2>Files for Patient: <?= htmlspecialchars($patient['name'] ?? 'N/A') ?></h2>

        <?php foreach (['xray_images', 'test_files', 'prescriptions', 'file_path'] as $column): ?>
            <?php
            $files = json_decode($patient[$column] ?? '[]', true);
            $file_type = substr($column, 0, strpos($column, '_')); // Get type from column name
            ?>

            <?php if (!empty($files)): ?>
                <div class="file-list">
                    <h3><?= ucfirst(str_replace('_', ' ', $column)) ?></h3>

                    <?php foreach ($files as $file): ?>
                        <?php
                        $base_url = '../uploads/';
                        $web_path = str_replace('../uploads/', $base_url, $file);
                        $file_type = explode('/', $file)[0]; // Get type from directory structure
                        ?>

                        <div class="image-container">
                            <?php if (strpos(mime_content_type($file), 'image') === 0): ?>
                                <img src="<?= $web_path ?>" alt="Patient File" class="thumbnail">
                            <?php else: ?>
                                <a href="<?= $web_path ?>" target="_blank" class="thumbnail">
                                    <?= htmlspecialchars(basename($file)) ?>
                                </a>
                            <?php endif; ?>

                            <button class="delete-btn" onclick="confirmDelete('<?= $file ?>', '<?= $file_type ?>')">Delete</button>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
    <?php endif; ?>

    <script>
        function confirmDelete(filePath, fileType) {
            if (confirm('Are you sure?')) {
                window.location.href = `?delete_image=${encodeURIComponent(filePath)}&patient_id=<?= $_GET['id'] ?>`;
            }
        }
    </script>
</body>

</html>