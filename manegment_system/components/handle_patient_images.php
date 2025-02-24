<?php
require_once 'auth.php';
require_once 'db.php';

// Get patient ID from query string
$patient_id = $_GET['id'] ?? null;

if (!$patient_id) {
    die("Invalid patient ID.");
}

// Fetch patient details
$stmt = $pdo->prepare("SELECT * FROM patients WHERE id = :id");
$stmt->execute(['id' => $patient_id]);
$patient = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$patient) {
    die("Patient not found.");
}

// Handle Image/File Upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['image'])) {
    echo "POST request detected.<br>";

    // Define base upload directorya
    $upload_dir = '../uploads/';

    // Determine the column based on the image type
    $image_type = $_POST['image_type'] ?? 'other';
    $column = '';
    if ($image_type === 'xray') {
        $column = 'xray_images';
    } elseif ($image_type === 'test') {
        $column = 'test_files';
    } elseif ($image_type === 'prescription') {
        $column = 'prescriptions';
    } else {
        $column = 'file_path';
    }

    // Ensure the medical_id directory is included in the path
    $current_date = date('Y-m-d'); // Get the current date
    $medical_id = $patient['medical_id'] ?? 'default'; // Fallback if medical_id is not set

    // Construct the directory path without duplicating "uploads/"
    $file_type_dir = $upload_dir . $column . '/' . $current_date . '/' . $medical_id . '/';

    // Create directories recursively if they don't exist
    if (!file_exists($file_type_dir)) {
        if (!mkdir($file_type_dir, 0777, true) && !is_dir($file_type_dir)) {
            die("Failed to create directory: $file_type_dir");
        }
    }

    // Generate a unique file name and move the uploaded file
    $file_name = basename($_FILES['image']['name']);
    $file_path_relative = $column . '/' . $current_date . '/' . $medical_id . '/' . uniqid() . '_' . $file_name; // Relative path for DB storage
    $file_path_full = $upload_dir . $file_path_relative; // Full path for file system

    if (move_uploaded_file($_FILES['image']['tmp_name'], $file_path_full)) {
        echo "File moved successfully.<br>";

        // Prepend "../" to the relative path before storing in the database
        $db_file_path = '../uploads/' . $file_path_relative;

        // Retrieve existing file paths from the database
        $existing_files_str = $patient[$column] ?? '[]'; // Default to an empty JSON array if no existing files
        $existing_files = json_decode($existing_files_str, true); // Decode JSON string into an array

        // Ensure $existing_files is an array
        if (!is_array($existing_files)) {
            $existing_files = [];
        }

        // Add the new file path to the list
        $existing_files[] = $db_file_path;

        // Convert the file paths back to a JSON-encoded string
        $new_files = json_encode(array_values($existing_files)); // Re-index the array and encode it as JSON

        // Update the corresponding column in the database
        try {
            $stmt = $pdo->prepare("UPDATE patients SET $column = :file_paths WHERE id = :id");
            if ($stmt->execute(['file_paths' => $new_files, 'id' => $patient_id])) {
                echo "Database updated successfully.<br>";
                $_SESSION['success_message'] = "Image/file uploaded successfully.";
            } else {
                echo "Database update failed: " . $stmt->errorInfo()[2] . "<br>";
            }
        } catch (\Exception $e) {
            echo "Database error: " . $e->getMessage() . "<br>";
        }
    } else {
        echo "Failed to move uploaded file.<br>";
        $_SESSION['error_message'] = "Failed to upload image/file.";
    }

    header("Location: handle_patient_images.php?id=" . $patient_id);
    exit;
}

if (isset($_GET['delete_image'])) {
    $image_path = $_GET['delete_image'];
    $image_type = $_GET['type'];

    // Determine the column based on the image type
    $column = '';
    if ($image_type === 'xray') {
        $column = 'xray_images';
    } elseif ($image_type === 'test') {
        $column = 'test_files';
    } elseif ($image_type === 'prescription') {
        $column = 'prescriptions';
    } else {
        $column = 'file_path';
    }

    // Remove the file from the server
    if (file_exists($image_path)) {
        unlink($image_path);
    }

    // Remove the file path from the database
    $existing_files = json_decode($patient[$column], true) ?? [];
    $existing_files = array_filter($existing_files, function ($file) use ($image_path) {
        return $file !== $image_path;
    });
    $new_files = json_encode(array_values($existing_files)); // Re-index the array

    $stmt = $pdo->prepare("UPDATE patients SET $column = :file_paths WHERE id = :id");
    $stmt->execute([
        'file_paths' => $new_files,
        'id' => $patient_id
    ]);

    $_SESSION['success_message'] = "Image/file deleted successfully.";
    header("Location: handle_patient_images.php?id=" . $patient_id); // Redirect to refresh the page
    exit;
} ?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Patient Images</title>
    <style>
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
        }

        .btn:hover {
            background-color: #0056b3;
        }
    </style>
</head>

<body>
    <h1>Manage Images/Files for Patient: <?= htmlspecialchars($patient['name']) ?></h1>

    <!-- Upload Form -->
    <h2>Upload New Image/File</h2>
    <form action="" method="POST" enctype="multipart/form-data">
        <label for="image">اختصر صورة:</label>
        <input type="file" name="image" id="image" required><br><br>

        <label for="image_type">نوع الملف:</label>
        <select name="image_type" id="image_type">
            <option value="xray">صور الأشعة</option>
            <option value="test">ملفات التحاليل</option>
            <option value="prescription">الوصفات الطبية</option>
            <option value="file_path">الملفات</option>
        </select><br><br>

        <button type="submit">Upload</button>
    </form>

    <h2>Existing Files</h2>
    <?php
    $base_url = "/Management_system/manegment_system/uploads/"; // Define the base URL for the uploads folder
    
    $columns = ['xray_images', 'test_files', 'prescriptions', 'file_path'];
    foreach ($columns as $column) {
        // Decode the JSON array from the database
        $files = json_decode($patient[$column], true) ?? [];
        if ($files) {
            echo "<h3>" . ucfirst(str_replace('_', ' ', $column)) . "</h3>";
            foreach ($files as $file) {
                // Convert the file path to a web-accessible URL
                $web_accessible_path = str_replace('../uploads/', $base_url, $file);

                echo "<div>";
                if (file_exists($_SERVER['DOCUMENT_ROOT'] . $web_accessible_path)) {
                    echo "<img src='" . htmlspecialchars($web_accessible_path) . "' width='150'><br>";
                } else {
                    echo "<p>File not found: " . htmlspecialchars($web_accessible_path) . "</p>";
                }
                echo "<a href='?id=$patient_id&delete_image=" . urlencode($file) . "&type=" . urlencode(substr($column, 0, strpos($column, '_'))) . "' onclick='return confirm(\"Are you sure?\")'>Delete</a>";
                echo "</div><hr>";
            }
        } else {// Define the file type based on the column name
            $file_type = '';
            switch ($column) {
                case 'xray_images':
                    $file_type = 'صور الأشعة';
                    break;
                case 'test_files':
                    $file_type = 'ملفات التحاليل';
                    break;
                case 'prescriptions':
                    $file_type = 'الوصفات الطبية';
                    break;
                default:
                    $file_type = 'الملفات';
                    break;
            }

            // Check if files exist for the patient
            if (!empty($patient[$column])) {
                // Decode the JSON array of file paths
                $file_paths = json_decode($patient[$column], true);

                // Ensure the decoded result is an array
                if (is_array($file_paths) && !empty($file_paths)) {
                    echo "<ul>";
                    foreach ($file_paths as $file_path) {
                        // Check if the file exists before displaying it
                        if (file_exists($_SERVER['DOCUMENT_ROOT'] . str_replace('../', '/', $file_path))) {
                            // Display the file type and a link to view the file
                            echo "<li><strong>$file_type:</strong> <a href='$file_path' target='_blank'>View</a></li>";
                        } else {
                            // Display a message if the file is missing
                            echo "<li><strong>$file_type:</strong> File not found: $file_path</li>";
                        }
                    }
                    echo "</ul>";
                } else if ($file_paths === []) {
                    // Handle case where the stored data is NULL
                    echo "<p>لايوجد صورٌ في $file_type.</p>";
                } else if ($file_paths === "") {
                    // Handle case where the stored data is NULL
                    echo "<p>لايوجد صور في $file_type.</p>";
                } else {
                    // Handle case where the stored data is not a valid JSON array
                    echo "<p>Error: Invalid $file_type data in the database.</p>";
                }
            } else {
                // Handle case where no files are available
                echo "<p>No $file_type files have been uploaded for this patient.</p>";
            }
        }
    }
    ?>
</body>

</html>