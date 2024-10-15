<?php
// Include database connection and start session
include 'db_conn.php';
session_start();

// Check if user is logged in
if (!isset($_SESSION['accNum']) && !isset($_SESSION['adminAccNum'])) {
    header("Location: login.php");
    exit();
}

// Initialize userType variable
$userType = '';
if (isset($_SESSION['adminAccNum'])) {
    $userType = 'admin'; 
} elseif (isset($_SESSION['accNum'])) {
    $userType = 'staff';
} else {
    echo "Error: User type not recognized.";
    exit();
}

// Include the appropriate header file based on user type
if ($userType === 'admin') {
    include 'header.php';
} elseif ($userType === 'staff') {
    include 'header_staff.php';
}

// If a file download is requested
if (isset($_GET['file_id'])) {
    // Get the file ID from the query string
    $fileId = intval($_GET['file_id']);

    // Fetch file details from the database
    $query = "SELECT file_path, report_name, file_format FROM report_files WHERE id = ?";
    $stmt = $link->prepare($query);
    $stmt->bind_param("i", $fileId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        die("File not found.");
    }

    $file = $result->fetch_assoc();
    $filePath = $file['file_path'];  // Use the literal path stored in the database
    $fileName = $file['report_name'] . '.' . $file['file_format'];  // The name the file will be saved as when downloaded

    // Check if the file exists on the server
    if (!file_exists($filePath)) {
        die("File not found on the server.");
    }

    // Set the correct headers for Excel files and other formats
    $fileFormat = strtolower($file['file_format']);
    switch ($fileFormat) {
        case 'csv':
            header('Content-Type: text/csv');
            break;
        case 'pdf':
            header('Content-Type: application/pdf');
            break;
        case 'xlsx':
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            break;
        default:
            header('Content-Type: application/octet-stream');
    }

    header('Content-Disposition: attachment; filename="' . basename($fileName) . '"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($filePath));

    // Clear output buffer to prevent any extra characters from corrupting the file
    if (ob_get_length()) {
        ob_end_clean();
    }

    // Read the file and send it to the browser
    readfile($filePath);
    exit();
}

// Fetch all reports from the report_files table
$query = "SELECT id, report_name, report_type, file_format, generated_by, generated_at FROM report_files ORDER BY generated_at ASC";
$result = $link->query($query);

if ($result === false) {
    die("Database query failed: " . $link->error);
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Reports</title>
    <style>
        body::before {
            content: "";
            position: fixed; /* Make sure the image stays in place while scrolling */
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: url("Images/Thermo.jpg");
            background-repeat: no-repeat;
            background-size: cover;
            background-position: center;
            filter: blur(8px); /* Apply blur effect to the background image */
            z-index: -1; /* Ensure the blurred background stays behind the content */
        }
        .container {
            max-width: 1000px;
            margin: 50px auto;
            padding: 20px;
            background-color: #fff;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
        }
        h2 {
            text-align: center;
            color: #333;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            padding: 12px;
            text-align: center;
            border: 1px solid #ddd;
        }
        th {
            background-color: #800000;
            color: #fff;
        }
        .btn {
            padding: 8px 15px;
            background-color: #800000;
            color: #fff;
            text-align: center;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .btn:hover {
            background-color: #4d0000;
        }

        .back-link {
            display: inline-block;
            padding: 10px 20px;
            margin-bottom: 20px;
            background-color: #800000;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.3s ease;
        }

        .back-link:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="reports.php" class="back-link">Back</a>
        <h2>Generated Reports</h2>
        
        <?php if ($result->num_rows > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Report Name</th>
                        <th>Report Type</th>
                        <th>File Format</th>
                        <th>Generated By</th>
                        <th>Generated At</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['id']); ?></td>
                            <td><?= htmlspecialchars($row['report_name']); ?></td>
                            <td><?= htmlspecialchars($row['report_type']); ?></td>
                            <td><?= strtoupper(htmlspecialchars($row['file_format'])); ?></td>
                            <td><?= htmlspecialchars($row['generated_by']); ?></td>
                            <td><?= htmlspecialchars($row['generated_at']); ?></td>
                            <td>
                                <a href="view_reports.php?file_id=<?= $row['id']; ?>" class="btn">View</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No reports found.</p>
        <?php endif; ?>
    </div>
    <?php include 'footer.php'; ?>
</body>
</html>

<?php
// Close the database connection
$link->close();
?>
