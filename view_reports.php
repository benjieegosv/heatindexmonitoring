<?php
include 'db_conn.php';
session_start();

// Redirect if user is not authenticated
if (!isset($_SESSION['accNum']) && !isset($_SESSION['adminAccNum'])) {
    header("Location: login.php");
    exit();
}

// Determine user type
$userType = isset($_SESSION['adminAccNum']) ? 'admin' : (isset($_SESSION['accNum']) ? 'staff' : null);

if (!$userType) {
    echo "Error: User type not recognized.";
    exit();
}

// Include the appropriate header based on user type
include $userType === 'admin' ? 'header.php' : 'header_staff.php';

// Handle file download request
if (isset($_GET['file_id'])) {
    $fileId = filter_var($_GET['file_id'], FILTER_VALIDATE_INT);
    
    if ($fileId === false) {
        die("Invalid file ID.");
    }

    $query = "SELECT file_path, report_name, file_format FROM report_files WHERE id = ?";
    $stmt = $link->prepare($query);
    
    if (!$stmt) {
        die("Database query preparation failed: " . $link->error);
    }

    $stmt->bind_param("i", $fileId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        die("File not found.");
    }

    $file = $result->fetch_assoc();
    $filePath = $file['file_path'];
    $fileName = $file['report_name'] . '.' . $file['file_format'];

    // Check if file exists on server
    if (!file_exists($filePath)) {
        die("File not found on the server.");
    }

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

    // Clear output buffer
    if (ob_get_length()) {
        ob_end_clean();
    }

    readfile($filePath);
    exit();
}

// Handle report deletion
if (isset($_POST['delete_id'])) {
    $deleteId = filter_var($_POST['delete_id'], FILTER_VALIDATE_INT);
    
    if ($deleteId === false) {
        die("Invalid report ID.");
    }

    $query = "SELECT file_path FROM report_files WHERE id = ?";
    $stmt = $link->prepare($query);
    
    if (!$stmt) {
        die("Database query preparation failed: " . $link->error);
    }

    $stmt->bind_param("i", $deleteId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        die("File not found.");
    }

    $file = $result->fetch_assoc();
    $filePath = $file['file_path'];

    // Delete report from the database
    $query = "DELETE FROM report_files WHERE id = ?";
    $stmt = $link->prepare($query);
    
    if (!$stmt) {
        die("Database query preparation failed: " . $link->error);
    }

    $stmt->bind_param("i", $deleteId);
    if ($stmt->execute()) {
        // Remove the file from the server
        if (file_exists($filePath)) {
            unlink($filePath);
        }
        $successMessage = "Report deleted successfully!";
    } else {
        die("Failed to delete the report.");
    }
}

// Fetch all reports
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
    position: fixed; 
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-image: url("Images/Thermo.jpg");
    background-repeat: no-repeat;
    background-size: cover;
    background-position: center;
    filter: blur(8px);
    z-index: -1;
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

.table-responsive {
    overflow-x: auto; 
}

table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
}

th, td {
    padding: 8px; 
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

table td:last-child {
    display: flex;
    justify-content: center;
    gap: 10px;
}

@media (max-width: 768px) {
    .btn {
        width: 100%;
        margin: 5px 0; 
        padding: 10px;
        font-size: 12px; 
    }

    table td:last-child {
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
    }

    h2 {
        font-size: 1.4rem; 
    }

    .table-responsive {
        font-size: 12px; 
    }

    table {
        font-size: 12px; 
    }

    th, td {
        padding: 6px; 
    }

    .back-link {
        padding: 8px 15px;
        font-size: 12px; 
    }
}

@media (max-width: 480px) {
    .btn {
        padding: 8px;
        font-size: 10px; 
    }

    table td:last-child {
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
    }

    h2 {
        font-size: 1.2rem; 
    }

    .table-responsive {
        font-size: 10px; 
    }

    table {
        font-size: 10px; 
    }

    th, td {
        padding: 4px; 
    }

    .back-link {
        padding: 5px 10px; 
        font-size: 10px; 
    }
}


.success-message {
    color: green;
    font-weight: bold;
    text-align: center;
}

    </style>
</head>
<body>
    <div class="container">
        <a href="reports.php" class="back-link">Back</a>
        <h2>Generated Reports</h2>

        <?php if (isset($successMessage)): ?>
            <p class="success-message"><?= htmlspecialchars($successMessage); ?></p>
        <?php endif; ?>
        
        <?php if ($result->num_rows > 0): ?>
            <table>
                <thead>
                    <tr>
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
                            <td><?= htmlspecialchars($row['report_name']); ?></td>
                            <td><?= htmlspecialchars($row['report_type']); ?></td>
                            <td><?= htmlspecialchars($row['file_format']); ?></td>
                            <td><?= htmlspecialchars($row['generated_by']); ?></td>
                            <td><?= htmlspecialchars($row['generated_at']); ?></td>
                            <td>
                                <form method="GET" action="">
                                    <input type="hidden" name="file_id" value="<?= htmlspecialchars($row['id']); ?>">
                                    <button type="submit" class="btn">View</button>
                                </form>
                                <form method="POST" action="">
                                    <input type="hidden" name="delete_id" value="<?= htmlspecialchars($row['id']); ?>">
                                    <button type="submit" class="btn" onclick="return confirm('Are you sure you want to delete this report?');">Delete</button>
                                </form>
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
