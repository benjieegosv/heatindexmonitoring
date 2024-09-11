<?php include 'header.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Accounts</title>
    <style>

        #manage-accounts-section {
            max-width: 800px;
            margin: 50px auto;
            padding: 40px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .option-container {
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .option-container button {
            padding: 20px 30px;
            font-size: 18px;
            color: #fff;
            width: 80%;
            background-color: #800000; /* Maroon */
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: background-color 0.3s ease;
            margin: 18px 0; /* Space between buttons */
        }

        .option-container button:hover {
            background-color: #4d0000; /* Darker maroon for hover */
        }
    </style>
</head>
<body>
    <div id="manage-accounts-section">
        <h2>Account Management</h2>
        <div class="option-container">
            <button id="user-approval-btn">User Approval</button>
            <button id="remove-user-btn">Remove User</button>
        </div>
    </div>
    
    <script>
        document.getElementById('user-approval-btn').addEventListener('click', function() {
            window.location.href = 'user_approval.php'; // Redirect to the user approval page
        });

        document.getElementById('remove-user-btn').addEventListener('click', function() {
            window.location.href = 'remove_user.php'; // Redirect to the remove user page
        });
    </script>
</body>
</html>
