<?php
session_start();

// Destroy any existing session
if (isset($_SESSION['accNum'])) {
    session_unset();
    session_destroy();
}

// Include the database connection file
include 'db_conn.php';

// Initialize variables
$userType = isset($_POST['userType']) ? $_POST['userType'] : '';
$message = '';

function test_input($data) {
    return htmlspecialchars(stripslashes(trim($data)));
}

// Handle Login Form Submission
if (isset($_POST['login'])) {
    $username = test_input($_POST['username']);
    $password = test_input($_POST['password']);
    $userType = test_input($_POST['userType']);

    if ($userType == 'admin') {
        $query = "SELECT accNum, password FROM admin_account WHERE username = ?";
    } elseif ($userType == 'famo') {
        $query = "SELECT sa.accNum, sa.password, uv.approvalStatus 
                  FROM staff_account sa 
                  LEFT JOIN user_validation uv ON sa.accNum = uv.staffAccNum 
                  WHERE sa.username = ?";
    } else {
        $message = "Invalid user type selected.";
    }

    if (!empty($query)) {
        $stmt = $link->prepare($query);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if ($user) {
            if ($userType == 'admin') {
                // For admin login, compare plaintext passwords directly
                if ($password === $user['password']) {
                    $_SESSION['accNum'] = $user['accNum'];
                    $_SESSION['username'] = $username;
                    $_SESSION['userType'] = $userType;
                    header("Location: home.php");
                    exit();
                } else {
                    $message = "Incorrect username or password.";
                }
            } elseif ($userType == 'famo') {
                // For FaMO staff login, use password_verify for hashed passwords
                if (password_verify($password, $user['password'])) {
                    $_SESSION['accNum'] = $user['accNum'];
                    $_SESSION['username'] = $username;
                    $_SESSION['userType'] = $userType;

                    if ($user['approvalStatus'] == 1) {
                        header("Location: home.php");
                        exit();
                    } else {
                        $message = "Your account is pending approval. Please contact the administrator.";
                    }
                } else {
                    $message = "Incorrect username or password.";
                }
            }
        } else {
            $message = "Incorrect username or password.";
        }
    }
}

// Handle Sign Up Form Submission
if (isset($_POST['signup'])) {
    $firstName = test_input($_POST['firstName']);
    $lastName = test_input($_POST['lastName']);
    $email = test_input($_POST['email']);
    $username = test_input($_POST['username']);
    $password = test_input($_POST['password']);
    $confirmPassword = test_input($_POST['confirmPassword']);

    $errors = [];

    // Validate inputs
    if (empty($firstName) || empty($lastName) || empty($email) || empty($username) || empty($password) || empty($confirmPassword)) {
        $errors[] = "All fields are required.";
    }

    if ($password !== $confirmPassword) {
        $errors[] = "Passwords do not match.";
    }

    // Check for existing username or email
    $checkQuery = "SELECT * FROM staff_account WHERE username = ? OR email = ?";
    $stmt = $link->prepare($checkQuery);
    $stmt->bind_param("ss", $username, $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $errors[] = "Username or Email already exists.";
    }

    // If no errors, proceed to insert
    if (empty($errors)) {
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

        $insertStaff = "INSERT INTO staff_account (firstName, lastName, email, username, password) VALUES (?, ?, ?, ?, ?)";
        $stmt = $link->prepare($insertStaff);
        $stmt->bind_param("sssss", $firstName, $lastName, $email, $username, $hashedPassword);

        if ($stmt->execute()) {
            $staffAccNum = $stmt->insert_id;

            $insertValidation = "INSERT INTO user_validation (staffAccNum, approvalStatus) VALUES (?, 0)";
            $stmt = $link->prepare($insertValidation);
            $stmt->bind_param("i", $staffAccNum);
            $stmt->execute();

            $message = "Account created successfully! Please wait for admin approval.";
        } else {
            $errors[] = "Failed to create account. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PUP Heat Index Monitoring System</title>
    <link rel="stylesheet" type="text/css" href="login.css">
    <link rel="icon" href="Images/jagran_logo1.jpg" type="image/x-icon">
    <link href="https://fonts.googleapis.com/css?family=Quicksand&display=swap" rel="stylesheet">
</head>
<body>
    <div class="opening-rem">
        <h2>PUP Heat Index Monitoring System</h2>
    </div>

    <div class="form1">
        <button id="admin-btn" class="button">Admin</button>
        <button id="famo-btn" class="button">FaMO Staff</button>
        <button id="guest-btn" class="button">Guest</button>
    </div>

    <div class="modal-overlay" id="modal-overlay"></div>

    <!-- Admin Login Form -->
    <div class="form log" id="admin-login-form" style="display: none;">
        <div class="box">
            <h3>Admin Login</h3>
            <?php if ($userType == 'admin' && !empty($message)): ?>
                <div class="error-message"><?php echo $message; ?></div>
            <?php endif; ?>
            <form action="login.php" method="POST">
                <input type="hidden" name="userType" value="admin">
                <input class="input" type="text" name="username" placeholder="Username" required><br>
                <input class="input" type="password" name="password" placeholder="Password" required><br>
                <input class="button" type="submit" name="login" value="Login"><br>
                <a href="#" id="cancel-admin-login">Cancel</a>
            </form>
        </div>
    </div>

    <!-- FaMO Staff Options -->
    <div class="form2" id="famo-options" style="display: none;">
        <div class="box">
            <h3>FaMO Staff</h3>
            <button id="famo-login-btn" class="button">Login</button>
            <button id="famo-signup-btn" class="button">Sign Up</button>
            <a href="#" id="cancel-famo-options">Cancel</a>
        </div>
    </div>

    <!-- FaMO Staff Login Form -->
    <div class="form log" id="famo-login-form" style="display: none;">
        <div class="box">
            <h3>FaMO Staff Login</h3>
            <?php if ($userType == 'famo' && !empty($message) && isset($_POST['login'])): ?>
                <div class="error-message"><?php echo $message; ?></div>
            <?php endif; ?>
            <form action="login.php" method="POST">
                <input type="hidden" name="userType" value="famo">
                <input class="input" type="text" name="username" placeholder="Username" required><br>
                <input class="input" type="password" name="password" placeholder="Password" required><br>
                <input class="button" type="submit" name="login" value="Login"><br>
                <a href="#" id="famo-login-signup">Sign Up here</a> | <a href="#">Forgot Password?</a><br>
                <a href="#" id="cancel-famo-login">Cancel</a>
            </form>
        </div>
    </div>

    <!-- FaMO Staff Sign Up Form -->
    <div class="form reg" id="famo-signup-form" style="display: none;">
        <div class="box">
            <h3>FaMO Staff Sign Up</h3>
            <?php if (!empty($errors)): ?>
                <div class="error-message">
                    <?php foreach ($errors as $error): ?>
                        <p><?php echo $error; ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <form action="login.php" method="POST">
                <input type="hidden" name="signup" value="1">
                <input class="input" type="text" name="firstName" placeholder="First Name" required><br>
                <input class="input" type="text" name="lastName" placeholder="Last Name" required><br>
                <input class="input" type="email" name="email" placeholder="Email" required><br>
                <input class="input" type="text" name="username" placeholder="Username" required><br>
                <input class="input" type="password" name="password" placeholder="Password" required><br>
                <input class="input" type="password" name="confirmPassword" placeholder="Confirm Password" required><br>
                <input class="button" type="submit" value="Sign Up"><br>
                <a href="#" id="cancel-famo-signup">Cancel</a>
            </form>
        </div>
    </div>

    <script>
        document.getElementById('admin-btn').addEventListener('click', function() {
            document.getElementById('admin-login-form').style.display = 'block';
            document.getElementById('modal-overlay').style.display = 'block';
        });

        document.getElementById('famo-btn').addEventListener('click', function() {
            document.getElementById('famo-options').style.display = 'block';
            document.getElementById('modal-overlay').style.display = 'block';
        });

        document.getElementById('guest-btn').addEventListener('click', function() {
            window.location.href = 'guest.php';
        });

        document.getElementById('cancel-admin-login').addEventListener('click', function(e) {
            e.preventDefault();
            document.getElementById('admin-login-form').style.display = 'none';
            document.getElementById('modal-overlay').style.display = 'none';
        });

        document.getElementById('famo-login-btn').addEventListener('click', function() {
            document.getElementById('famo-login-form').style.display = 'block';
            document.getElementById('famo-options').style.display = 'none';
        });

        document.getElementById('famo-signup-btn').addEventListener('click', function() {
            document.getElementById('famo-signup-form').style.display = 'block';
            document.getElementById('famo-options').style.display = 'none';
        });

        document.getElementById('cancel-famo-login').addEventListener('click', function(e) {
            e.preventDefault();
            document.getElementById('famo-login-form').style.display = 'none';
            document.getElementById('modal-overlay').style.display = 'none';
        });

        document.getElementById('cancel-famo-signup').addEventListener('click', function(e) {
            e.preventDefault();
            document.getElementById('famo-signup-form').style.display = 'none';
            document.getElementById('modal-overlay').style.display = 'none';
        });
        
        document.getElementById('cancel-famo-options').addEventListener('click', function(e) {
            e.preventDefault();
            document.getElementById('famo-options').style.display = 'none';
            document.getElementById('modal-overlay').style.display = 'none';
        });

        document.getElementById('famo-login-signup').addEventListener('click', function(e) {
            e.preventDefault();
            document.getElementById('famo-login-form').style.display = 'none';
            document.getElementById('famo-signup-form').style.display = 'block';
        });
    </script>
</body>
</html>