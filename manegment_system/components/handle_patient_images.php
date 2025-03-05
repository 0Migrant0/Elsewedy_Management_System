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
    <title>إدارة الملفات</title>
    <link rel="stylesheet" href="../styles/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            margin: 0;
            padding: 20px;
            animation: fadeIn 1s ease-in-out;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 15px;
        }

        h1 {
            color: #2c3e50;
            text-align: center;
            margin: 2rem 0;
            letter-spacing: 1px;
        }

        /* Upload Form */
        .upload-form {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            padding: 20px;
            transition: all 0.3s ease;
        }

        .upload-form:hover {
            transform: translateY(-5px);
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #34495e;
            font-weight: 600;
        }

        .form-control {
            max-width: max-content;
            padding: 12px;
            border: 2px solid #bdc3c7;
            border-radius: 6px;
            transition: all 0.3s ease;
            font-size: 16px;
        }

        .form-control:focus {
            border-color: #3498db;
            box-shadow: 0 0 0 2px rgba(52, 152, 219, 0.2);
        }

        .btn {
            background: #3498db;
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 600;
            width: 100%;
            margin-top: 1rem;
        }

        .btn:hover {
            background: #2980b9;
            transform: scale(1.05);
        }

        /* File List */
        .file-list {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
        }

        .file-item {
            background: white;
            border-radius: 10px;
            padding: 15px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
            position: relative;
        }

        .file-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 12px rgba(0, 0, 0, 0.1);
        }

        .file-thumbnail {
            width: 100%;
            aspect-ratio: 16/9;
            object-fit: cover;
            border-radius: 6px;
            margin-bottom: 10px;
            transition: transform 0.3s ease;
        }

        .file-item:hover .file-thumbnail {
            transform: scale(1.05);
        }

        .file-name {
            color: #2c3e50;
            font-size: 16px;
            margin: 0.5rem 0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .file-type {
            color: #7f8c8d;
            font-size: 14px;
            margin-bottom: 1rem;
        }

        .delete-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            background: #e74c3c;
            color: white;
            border: none;
            padding: 8px 12px;
            border-radius: 4px;
            cursor: pointer;
            opacity: 0;
            transition: all 0.3s ease;
            z-index: 2;
        }

        .file-item:hover .delete-btn {
            opacity: 1;
        }


        /* Alerts */
        .alert {
            position: fixed;
            top: 20px;
            right: 20px;
            min-width: 250px;
            max-width: 90%;
            opacity: 1;
            transition: all 0.5s ease-in-out;
            animation: slideIn 0.5s ease;
            z-index: 1000;
        }

        .alert.auto-dismiss {
            animation: fadeOut 0.5s ease 2s forwards;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        /* Animations */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes pulse {
            0% {
                transform: scale(1);
            }

            50% {
                transform: scale(1.1);
            }

            100% {
                transform: scale(1);
            }
        }

        @keyframes slideIn {
            from {
                transform: translateY(-20px);
                opacity: 0;
            }

            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        @keyframes fadeOut {
            to {
                opacity: 0;
                transform: translateX(20px);
            }
        }

        /* Mobile-specific styles */
        @media (max-width: 768px) {
            h1 {
                font-size: 1.5rem;
                margin: 1.5rem 0;
            }

            .file-list {
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
                gap: 1rem;
            }

            .file-item {
                padding: 10px;
            }

            .file-thumbnail {
                aspect-ratio: 1/1;
            }

            .btn {
                font-size: 14px;
                padding: 10px;
            }

            .delete-btn {
                padding: 6px 10px;
                font-size: 12px;
            }
        }

        @media (max-width: 480px) {
            .container {
                padding: 0 10px;
            }

            .upload-form {
                padding: 15px;
            }

            .form-control {
                font-size: 14px;
                padding: 10px;
            }
        }
    </style>
</head>

<body>
    <header>
        <nav>
            <div class="menu-toggle" onclick="toggleMenu()">
                <i class="fas fa-bars"></i>
            </div>
            <div class="menu">
                <a href="../index.php"><i class="fas fa-home"></i> الرئيسية</a>
                <a href="add_patient.php"><i class="fas fa-user-plus"></i> إضافة مريض</a>
                <a href="../../booking_system/dashboard.php"><i class="fas fa-calendar-alt"></i> الحجوزات</a>
                <a href="../../index.php"><i class="fas fa-calendar-check"></i> حجز موعد</a>
                <a href="components/logout.php"><i class="fas fa-sign-out-alt"></i> تسجيل الخروج</a>
            </div>
        </nav>
    </header>
    <div class="container">
        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-success auto-dismiss"><?= $_SESSION['message'] ?></div>
            <?php unset($_SESSION['message']) ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error"><?= $_SESSION['error'] ?></div>
            <?php unset($_SESSION['error']) ?>
        <?php endif; ?>

        <h1>إدارة ملفات المرضى</h1>

        <div class="upload-form">
            <form method="post" enctype="multipart/form-data">
                <input type="hidden" name="patient_id" value="<?= htmlspecialchars($_GET['id'] ?? '') ?>">

                <div class="form-group">
                    <label>ملف:</label>
                    <input type="file" name="image" class="form-control" required>
                </div>

                <div class="form-group">
                    <label>نوع الملف:</label>
                    <select name="image_type" class="form-control">
                        <option value="xray">أشعة</option>
                        <option value="test"> التحليل</option>
                        <option value="prescription">وصفات طبية</option>
                        <option value="other">ملفات</option>
                    </select>
                </div>

                <button type="submit" class="btn">رفع الملف</button>
            </form>
        </div>

        <?php if (isset($patient)): ?>
            <h2 class="mt-4">الملفات الخاصة بـ: <?= htmlspecialchars($patient['name'] ?? 'N/A') ?></h2>

            <div class="file-list">
                <?php foreach (['xray_images', 'test_files', 'prescriptions', 'file_path'] as $column): ?>
                    <?php
                    $files = json_decode($patient[$column] ?? '[]', true);
                    $file_type = ucfirst(str_replace('_', ' ', $column));
                    ?>
                    <?php foreach ($files as $file): ?>
                        <?php
                        $base_url = '../uploads/';
                        $web_path = str_replace('../uploads/', $base_url, $file);
                        $file_extension = pathinfo($file, PATHINFO_EXTENSION);
                        $is_image = in_array(strtolower($file_extension), ['jpg', 'jpeg', 'png', 'gif']);
                        ?>
                        <div class="file-item">
                            <button class="delete-btn" onclick="confirmDelete('<?= $file ?>', event)">حذف</button>

                            <?php if ($is_image): ?>
                                <img src="<?= $web_path ?>" alt="File" class="file-thumbnail">
                            <?php else: ?>
                                <div class="file-thumbnail"
                                    style="background: #f1f1f1; display: flex; align-items: center; justify-content: center;">
                                    <i class="fas fa-file-alt" style="font-size: 48px; color: #999;"></i>
                                </div>
                            <?php endif; ?>

                            <div class="file-name"><?= htmlspecialchars(basename($file)) ?></div>
                            <div class="file-type"><?= $file_type ?></div>
                        </div>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function confirmDelete(filePath, event) {
            event.stopPropagation();
            if (confirm('هل أنت متأكد أنك تريد حذف هذا الملف؟')) {
                window.location.href = `?delete_image=${encodeURIComponent(filePath)}&patient_id=<?= $_GET['id'] ?? '' ?>`;
            }
        }
        document.querySelectorAll('.alert').forEach(alert => {
            if (!alert.classList.contains('auto-dismiss')) {
                const closeButton = document.createElement('button');
                closeButton.innerHTML = '&times;';
                closeButton.style.position = 'absolute';
                closeButton.style.top = '5px';
                closeButton.style.right = '10px';
                closeButton.style.background = 'none';
                closeButton.style.border = 'none';
                closeButton.style.fontSize = '1.2em';
                closeButton.style.cursor = 'pointer';
                closeButton.onclick = () => alert.remove();
                alert.appendChild(closeButton);
            }
        });
    </script>
</body>

</html>