<?php
// Include database connection and header
include('db_conn.php');
include('header.php');

// Initialize feedback variable
$feedbackMessage = '';
$searchTerm = '';

// Handle search query input
if (isset($_GET['search'])) {
    $searchTerm = trim($_GET['search']);
}

// Handle the removal action
if (isset($_POST['action']) && isset($_POST['staffAccNum'])) {
    $staffAccNum = intval($_POST['staffAccNum']);
    $action = $_POST['action'];

    if ($action === 'remove') {
        // Remove from validation table first to avoid foreign key constraint issues
        $sql_validation = "DELETE FROM user_validation WHERE staffAccNum = ?";
        $stmt_validation = $link->prepare($sql_validation);

        if ($stmt_validation === false) {
            die("Prepare failed: " . $link->error);
        }

        $stmt_validation->bind_param("i", $staffAccNum);
        $stmt_validation->execute();

        if ($stmt_validation->affected_rows === 0) {
            die("No rows deleted from user_validation: " . $link->error);
        }

        $stmt_validation->close();

        // Then delete from staff_account
        $sql = "DELETE FROM staff_account WHERE accNum = ?";
        $stmt = $link->prepare($sql);

        if ($stmt === false) {
            die("Prepare failed: " . $link->error);
        }

        $stmt->bind_param("i", $staffAccNum);
        $stmt->execute();

        if ($stmt->affected_rows === 0) {
            die("No rows deleted from staff_account: " . $link->error);
        }

        $stmt->close();
        $feedbackMessage = "User removed successfully.";
    }

    // Redirect to refresh the page
    header("Location: remove_user.php?message=" . urlencode($feedbackMessage));
    exit();
}

// Fetching all staff accounts, with optional search functionality
$sql = "SELECT u.firstName, u.lastName, u.email, u.accNum 
        FROM staff_account u
        LEFT JOIN user_validation v ON u.accNum = v.staffAccNum 
        WHERE (u.firstName LIKE ? OR u.lastName LIKE ? OR u.email LIKE ?)";
$stmt = $link->prepare($sql);

if ($stmt === false) {
    die("Prepare failed: " . $link->error);
}

$searchLike = '%' . $searchTerm . '%';
$stmt->bind_param("sss", $searchLike, $searchLike, $searchLike);
$stmt->execute();
$result = $stmt->get_result();

if ($result === false) {
    die("Query failed: " . $link->error);
}

$feedbackMessage = isset($_GET['message']) ? $_GET['message'] : '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Approval</title>
    <style>
        .feedback-container {
            width: 90%;
            max-width: 1200px;
            margin: 20px auto;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            background-color: #f8f9fa;
            color: #333;
            text-align: center;
        }

        .container {
            width: 90%;
            max-width: 1200px;
            background-color: #fff;
            padding: 20px;
            margin: 50px auto;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        h1 {
            text-align: center;
            margin-bottom: 30px;
            color: #343a40;
        }

        .search-container {
            text-align: right;
            margin-bottom: 20px;
        }

        .search-input {
            padding: 8px;
            width: 200px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }

        .table-container {
            max-height: 400px; 
            overflow-y: auto; 
            border: 1px solid #ddd; 
            border-radius: 5px; 
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #dee2e6;
        }

        th {
            background-color: #800000;
            color: white;
        }

        tr:nth-child(even) {
            background-color: #f2f2f2;
        }

        tr:hover {
            background-color: #f8f9fa;
        }

        .btn {
            padding: 8px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            margin: 2px;
            transition: background-color 0.3s ease;
        }

        .btn-remove {
            background-color: #dc3545;
            color: white;
        }

        .btn-remove:hover {
            background-color: #c82333;
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

        .no-data {
            text-align: center;
            padding: 20px;
            font-size: 16px;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <?php if ($feedbackMessage): ?>
        <div class="feedback-container">
            <?php echo htmlspecialchars($feedbackMessage); ?>
        </div>
    <?php endif; ?>

    <div class="container">
        <a href="manage_accounts.php" class="back-link">Back</a>
        <h1>User Removal</h1>

        <div class="search-container">
            <form method="get" action="remove_user.php">
                <input type="text" name="search" class="search-input" value="<?php echo htmlspecialchars($searchTerm); ?>" placeholder="Search by name or email">
                <button type="submit" class="btn btn-remove">Search</button>
            </form>
        </div>

         <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>First Name</th>
                        <th>Last Name</th>
                        <th>Email</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            echo "<tr>";
                            echo "<td>" . htmlspecialchars($row['firstName']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['lastName']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['email']) . "</td>";
                            echo "<td>";
                            echo '<form method="post" style="display:inline-block;">';
                            echo '<input type="hidden" name="staffAccNum" value="' . intval($row['accNum']) . '">';
                            echo '<button type="submit" name="action" value="remove" class="btn btn-remove">Remove</button>';
                            echo '</form>';
                            echo "</td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='4' class='no-data'>No users found.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php include 'footer.php'; ?>
</body>
</html>

<?php
$link->close();
?>
