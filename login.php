<?php
session_start();

// Destroy any existing session
if (isset($_SESSION['accNum']) || isset($_SESSION['adminAccNum']) || isset($_SESSION['guestAccNum'])) {
    session_unset();
    session_destroy();
}

// Include the database connection file
include 'db_conn.php';

// Initialize variables
$message = '';
$errors = [];
$userType = '';  

// Function to sanitize inputs
function test_input($data) {
    return htmlspecialchars(stripslashes(trim($data)));
}

// Handle Login Form Submission
if (isset($_POST['login'])) {
    if (isset($_POST['userType'])) {
        $userType = test_input($_POST['userType']);
    } else {
        $userType = ''; // Handle case where userType is missing
    }

    $username = test_input($_POST['username']);
    $password = test_input($_POST['password']);

    $query = ''; // Initialize query to avoid empty query error

    if ($userType == 'admin') {
        $query = "SELECT accNum, password FROM admin_account WHERE username = ?";
    } elseif ($userType == 'staff') {
        $query = "SELECT sa.accNum, sa.password, uv.approvalStatus 
                  FROM staff_account sa 
                  LEFT JOIN user_validation uv ON sa.accNum = uv.staffAccNum 
                  WHERE sa.username = ?";
    } elseif ($userType == 'guest') {
        $query = "SELECT accNum, password FROM guest_account WHERE username = ?";
    } else {
        $message = "Invalid user type selected.";
    }

    if (!empty($query)) {
        if ($stmt = $link->prepare($query)) {
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();

            if ($user) {
                if ($userType == 'admin') {
                    if ($password === $user['password']) {
                        $_SESSION['adminAccNum'] = $user['accNum'];
                        $_SESSION['username'] = $username;
                        $_SESSION['userType'] = $userType;
                        header("Location: home.php");
                        exit();
                    } else {
                        $message = "Incorrect username or password.";
                    }
                } elseif ($userType == 'staff') {
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
                } elseif ($userType == 'guest') {
                    if (password_verify($password, $user['password'])) {
                        $_SESSION['guestAccNum'] = $user['accNum'];
                        $_SESSION['username'] = $username;
                        $_SESSION['userType'] = $userType;
                        header("Location: home.php");
                        exit();
                    } else {
                        $message = "Incorrect username or password.";
                    }
                }
            } else {
                $message = "Incorrect username or password.";
            }
            $stmt->close();
        } else {
            $message = "Database query failed: " . $link->error;
        }
    }
}

// Handle Sign Up Form Submission for Staff and Guest
if (isset($_POST['signup'])) {
    if (isset($_POST['userType'])) {
        $userType = test_input($_POST['userType']);
    } else {
        $userType = ''; // Handle case where userType is missing
    }

    $firstName = test_input($_POST['firstName']);
    $lastName = test_input($_POST['lastName']);
    $email = test_input($_POST['email']);
    $contactNum = test_input($_POST['contactNum']);
    $username = test_input($_POST['username']);
    $password = test_input($_POST['password']);
    $confirmPassword = test_input($_POST['confirmPassword']);

    // Validate inputs
    if (empty($firstName) || empty($lastName) || empty($email) || empty($contactNum) || empty($username) || empty($password) || empty($confirmPassword)) {
        $errors[] = "All fields are required.";
    }

    if ($password !== $confirmPassword) {
        $errors[] = "Passwords do not match.";
    }

    // Check for existing username or email
    if ($userType == 'staff') {
        $checkQuery = "SELECT * FROM staff_account WHERE username = ? OR email = ?";
    } elseif ($userType == 'guest') {
        $checkQuery = "SELECT * FROM guest_account WHERE username = ? OR email = ?";
    } else {
        $errors[] = "Invalid user type.";
    }

    if (!empty($checkQuery)) {
        $stmt = $link->prepare($checkQuery);
        if ($stmt) {
            $stmt->bind_param("ss", $username, $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $errors[] = "Username or Email already exists.";
            }
            $stmt->close();
        } else {
            $errors[] = "Database query failed: " . $link->error;
        }
    }

    // If no errors, proceed to insert
    if (empty($errors)) {
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

        if ($userType == 'staff') {
            $insertStaff = "INSERT INTO staff_account (firstName, lastName, email, contactNum, username, password, dateCreated, timeCreated) VALUES (?, ?, ?, ?, ?, ?, CURDATE(), CURTIME())";;
            $stmt = $link->prepare($insertStaff);
            if ($stmt) {
                $stmt->bind_param("ssssss", $firstName, $lastName, $email, $contactNum, $username, $hashedPassword);
                if ($stmt->execute()) {
                    $staffAccNum = $stmt->insert_id;

                    $insertValidation = "INSERT INTO user_validation (staffAccNum, approvalStatus) VALUES (?, 0)";
                    $stmt = $link->prepare($insertValidation);
                    $stmt->bind_param("i", $staffAccNum);
                    $stmt->execute();

                    $message = "Staff account created successfully! Please wait for admin approval.";
                } else {
                    $errors[] = "Failed to create staff account: " . $stmt->error;
                }
            } else {
                $errors[] = "Failed to prepare statement: " . $link->error;
            }
        } elseif ($userType == 'guest') {
            $insertGuest = "INSERT INTO guest_account (firstName, lastName, email, contactNum, username, password, dateCreated, timeCreated) VALUES (?, ?, ?, ?, ?, ?, CURDATE(), CURTIME())";
            $stmt = $link->prepare($insertGuest);
            if ($stmt) {
                $stmt->bind_param("ssssss", $firstName, $lastName, $email, $contactNum, $username, $hashedPassword);
                if ($stmt->execute()) {
                    $message = "Guest account created successfully!";
                } else {
                    $errors[] = "Failed to create guest account: " . $stmt->error;
                }
            } else {
                $errors[] = "Failed to prepare statement: " . $link->error;
            }
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
        <button id="staff-btn" class="button">Staff</button>
        <button id="guest-btn" class="button">Guest</button>
    </div>

    <div class="modal-overlay" id="modal-overlay"></div>

    <!-- Admin Login Form -->
    <div class="form log" id="admin-login-form" style="display: none;">
        <div class="box">
            <h3>Admin Login</h3>
            <?php if (isset($userType) && $userType == 'admin' && !empty($message)): ?>
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

    <!-- Staff Options -->
    <div class="form2" id="staff-options" style="display: none;">
        <div class="box">
            <h3>Staff</h3>
            <button id="staff-login-btn" class="button">Login</button>
            <button id="staff-signup-btn" class="button">Sign Up</button>
            <a href="#" id="cancel-staff-options">Cancel</a>
        </div>
    </div>

    <!-- Staff Login Form -->
    <div class="form log" id="staff-login-form" style="display: none;">
        <div class="box">
            <h3>Staff Login</h3>
            <?php if (isset($userType) && $userType == 'staff' && !empty($message) && isset($_POST['login'])): ?>
                <div class="error-message"><?php echo $message; ?></div>
            <?php endif; ?>
            <form action="login.php" method="POST">
                <input type="hidden" name="userType" value="staff">
                <input class="input" type="text" name="username" placeholder="Username" required><br>
                <input class="input" type="password" name="password" placeholder="Password" required><br>
                <input class="button" type="submit" name="login" value="Login"><br>
                <a href="#" id="staff-login-signup">Sign Up here</a> | <a href="#">Forgot Password?</a><br>
                <a href="#" id="cancel-staff-login">Cancel</a>
            </form>
        </div>
    </div>

    <!-- Staff Sign Up Form -->
    <div class="form reg" id="staff-signup-form" style="display: none;">
        <div class="box">
            <h3>Staff Sign Up</h3>
            <?php if (isset($userType) && $userType == 'staff' && !empty($errors)): ?>
                <div class="error-message">
                    <?php foreach ($errors as $error): ?>
                        <p><?php echo $error; ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <form action="login.php" method="POST">
                <input type="hidden" name="signup" value="1">
                <input type="hidden" name="userType" value="staff">
                <input class="input" type="text" name="firstName" placeholder="First Name" required><br>
                <input class="input" type="text" name="lastName" placeholder="Last Name" required><br>
                <input class="input" type="email" name="email" placeholder="Email" required><br>
                <input class="input" type="text" name="contactNum" placeholder="Contact Number" required><br>
                <input class="input" type="text" name="username" placeholder="Username" required><br>
                <input class="input" type="password" name="password" placeholder="Password" required><br>
                <input class="input" type="password" name="confirmPassword" placeholder="Confirm Password" required><br>
                <input class="button" type="submit" value="Sign Up"><br>
                <a href="#" id="cancel-staff-signup">Cancel</a>
            </form>
        </div>
    </div>

    <!-- Guest Options -->
    <div class="form3" id="guest-options" style="display: none;">
        <div class="box">
            <h3>Guest</h3>
            <button id="guest-login-btn" class="button">Login</button>
            <button id="guest-signup-btn" class="button">Sign Up</button>
            <a href="#" id="cancel-guest-options">Cancel</a>
        </div>
    </div>

    <!-- Guest Login Form -->
    <div class="form log" id="guest-login-form" style="display: none;">
        <div class="box">
            <h3>Guest Login</h3>
            <?php if (isset($userType) && $userType == 'guest' && !empty($message) && isset($_POST['login'])): ?>
                <div class="error-message"><?php echo $message; ?></div>
            <?php endif; ?>
            <form action="login.php" method="POST">
                <input type="hidden" name="userType" value="guest">
                <input class="input" type="text" name="username" placeholder="Username" required><br>
                <input class="input" type="password" name="password" placeholder="Password" required><br>
                <input class="button" type="submit" name="login" value="Login"><br>
                <a href="#" id="guest-login-signup">Sign Up here</a> | <a href="#">Forgot Password?</a><br>
                <a href="#" id="cancel-guest-login">Cancel</a>
            </form>
        </div>
    </div>

    <!-- Guest Sign Up Form -->
    <div class="form reg" id="guest-signup-form" style="display: none;">
        <div class="box">
            <h3>Guest Sign Up</h3>
            <?php if (isset($userType) && $userType == 'guest' && !empty($errors)): ?>
                <div class="error-message">
                    <?php foreach ($errors as $error): ?>
                        <p><?php echo $error; ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <form action="login.php" method="POST">
                <input type="hidden" name="signup" value="1">
                <input type="hidden" name="userType" value="guest">
                <input class="input" type="text" name="firstName" placeholder="First Name" required><br>
                <input class="input" type="text" name="lastName" placeholder="Last Name" required><br>
                <input class="input" type="email" name="email" placeholder="Email" required><br>
                <input class="input" type="text" name="contactNum" placeholder="Contact Number" required><br> 
                <input class="input" type="text" name="username" placeholder="Username" required><br>
                <input class="input" type="password" name="password" placeholder="Password" required><br>
                <input class="input" type="password" name="confirmPassword" placeholder="Confirm Password" required><br>
                <input class="button" type="submit" value="Sign Up"><br>
                <a href="#" id="cancel-guest-signup">Cancel</a>
            </form>
        </div>
    </div>

    <script>
        // Admin
        document.getElementById('admin-btn').addEventListener('click', function() {
            document.getElementById('admin-login-form').style.display = 'block';
            document.getElementById('modal-overlay').style.display = 'block';
        });

        document.getElementById('cancel-admin-login').addEventListener('click', function(e) {
            e.preventDefault();
            document.getElementById('admin-login-form').style.display = 'none';
            document.getElementById('modal-overlay').style.display = 'none';
        });

        // Staff
        document.getElementById('staff-btn').addEventListener('click', function() {
            document.getElementById('staff-options').style.display = 'block';
            document.getElementById('modal-overlay').style.display = 'block';
        });

        document.getElementById('staff-login-btn').addEventListener('click', function() {
            document.getElementById('staff-login-form').style.display = 'block';
            document.getElementById('staff-options').style.display = 'none';
        });

        document.getElementById('staff-signup-btn').addEventListener('click', function() {
            document.getElementById('staff-signup-form').style.display = 'block';
            document.getElementById('staff-options').style.display = 'none';
        });

        document.getElementById('cancel-staff-login').addEventListener('click', function(e) {
            e.preventDefault();
            document.getElementById('staff-login-form').style.display = 'none';
            document.getElementById('modal-overlay').style.display = 'none';
        });

        document.getElementById('cancel-staff-signup').addEventListener('click', function(e) {
            e.preventDefault();
            document.getElementById('staff-signup-form').style.display = 'none';
            document.getElementById('modal-overlay').style.display = 'none';
        });
        
        document.getElementById('cancel-staff-options').addEventListener('click', function(e) {
            e.preventDefault();
            document.getElementById('staff-options').style.display = 'none';
            document.getElementById('modal-overlay').style.display = 'none';
        });

        document.getElementById('staff-login-signup').addEventListener('click', function(e) {
            e.preventDefault();
            document.getElementById('staff-login-form').style.display = 'none';
            document.getElementById('staff-signup-form').style.display = 'block';
        });

        // Guest
        document.getElementById('guest-btn').addEventListener('click', function() {
            document.getElementById('guest-options').style.display = 'block';
            document.getElementById('modal-overlay').style.display = 'block';
        });

        document.getElementById('guest-login-btn').addEventListener('click', function() {
            document.getElementById('guest-login-form').style.display = 'block';
            document.getElementById('guest-options').style.display = 'none';
        });

        document.getElementById('guest-signup-btn').addEventListener('click', function() {
            document.getElementById('guest-signup-form').style.display = 'block';
            document.getElementById('guest-options').style.display = 'none';
        });

        document.getElementById('cancel-guest-login').addEventListener('click', function(e) {
            e.preventDefault();
            document.getElementById('guest-login-form').style.display = 'none';
            document.getElementById('modal-overlay').style.display = 'none';
        });

        document.getElementById('cancel-guest-signup').addEventListener('click', function(e) {
            e.preventDefault();
            document.getElementById('guest-signup-form').style.display = 'none';
            document.getElementById('modal-overlay').style.display = 'none';
        });
        
        document.getElementById('cancel-guest-options').addEventListener('click', function(e) {
            e.preventDefault();
            document.getElementById('guest-options').style.display = 'none';
            document.getElementById('modal-overlay').style.display = 'none';
        });

        document.getElementById('guest-login-signup').addEventListener('click', function(e) {
            e.preventDefault();
            document.getElementById('guest-login-form').style.display = 'none';
            document.getElementById('guest-signup-form').style.display = 'block';
        });

    </script>
</body>
</html>
