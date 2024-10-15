<?php
include 'db_conn.php';
session_start();

// Check if user is logged in
if (!isset($_SESSION['accNum']) && !isset($_SESSION['adminAccNum']) && !isset($_SESSION['guestAccNum'])) {
    header("Location: login.php");
    exit();
}

// Initialize userType and user_id variables
if (isset($_SESSION['adminAccNum'])) {
    $userType = 'admin';
    $user_id = $_SESSION['adminAccNum'];
} elseif (isset($_SESSION['accNum'])) {
    $userType = 'staff';
    $user_id = $_SESSION['accNum'];
} elseif (isset($_SESSION['guestAccNum'])) {
    $userType = 'guest';
    $user_id = $_SESSION['guestAccNum'];
} else {
    echo "Error: User type not recognized.";
    exit();
}

// Include the appropriate header file based on user type
if ($userType === 'admin') {
    include 'header.php';
} elseif ($userType === 'staff') {
    include 'header_staff.php';
} elseif ($userType === 'guest') {
    include 'header_guest.php';
}

$feedbackMessage = ''; // Variable to store feedback messages
$table = $userType === 'admin' ? 'admin_account' : ($userType === 'staff' ? 'staff_account' : 'guest_account');

// Handle profile update
if (isset($_POST['update_profile'])) {
    $firstName = trim($_POST['firstName']);
    $lastName = trim($_POST['lastName']);
    $email = trim($_POST['email']);
    $contactNum = trim($_POST['contactNum']);

    // Handle profile picture upload (using cropped image)
    if (isset($_POST['croppedImage'])) {
        $croppedImage = $_POST['croppedImage']; // Base64 encoded image data

        if (preg_match('/^data:image\/(\w+);base64,/', $croppedImage, $type)) {
            $croppedImage = substr($croppedImage, strpos($croppedImage, ',') + 1);
            $croppedImage = base64_decode($croppedImage);

            // Update query with profile image (store base64 directly)
            $sql_update = "UPDATE $table SET firstName = ?, lastName = ?, email = ?, contactNum = ?, profilePic = ? WHERE accNum = ?";
            $stmt_update = $link->prepare($sql_update);
            $stmt_update->bind_param("sssssi", $firstName, $lastName, $email, $contactNum, $croppedImage, $user_id);
        } else {
            $feedbackMessage = "Invalid image data.";
        }
    } else {
        // Update without changing profile picture
        $sql_update = "UPDATE $table SET firstName = ?, lastName = ?, email = ?, contactNum = ? WHERE accNum = ?";
        $stmt_update = $link->prepare($sql_update);
        $stmt_update->bind_param("ssssi", $firstName, $lastName, $email, $contactNum, $user_id);
    }

    if ($stmt_update->execute()) {
        // Provide feedback if changes were made or not
        $feedbackMessage = $stmt_update->affected_rows > 0 ? "Profile updated successfully." : "No changes were made to the profile.";
    } else {
        $feedbackMessage = "Failed to update profile: " . $link->error;
    }
    $stmt_update->close();
}

// Fetch user profile information
$sql = "SELECT firstName, lastName, email, contactNum, profilePic FROM $table WHERE accNum = ?";
$stmt = $link->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$userData = $result->fetch_assoc();
$stmt->close();

$link->close();

// Set default profile picture based on user type
$defaultProfilePic = ($userType === 'admin') ? 'Image/Admin.png' : ($userType === 'staff' ? 'Image/Staff.png' : 'Image/Guest.png');
$profilePicSrc = !empty($userData['profilePic']) ? 'data:image/png;base64,' . base64_encode($userData['profilePic']) : $defaultProfilePic;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.12/cropper.min.css" />
    <style>
        .profile-container {
            display: flex;
            flex-direction: row;
            max-width: 900px;
            background-color: white;
            margin: 50px auto;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        .profile-left {
            background-color: #800000;
            color: white;
            width: 40%;
            padding: 40px;
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            position: relative;
        }
        .profile-left img {
            border-radius: 50%;
            width: 160px;
            height: 160px;
            object-fit: cover;
            margin-bottom: 20px;
            transition: all 0.3s ease;
            cursor: pointer;
            box-shadow: 0px 4px 12px rgba(0, 0, 0, 0.2);
            pointer-events: none; /* Disable pointer events until editing is enabled */
        }
        .profile-left img:hover {
            transform: scale(1.05);
            box-shadow: 0px 6px 16px rgba(0, 0, 0, 0.3);
            opacity: 0.8; /* Fade effect */
        }
        .hover-text {
            position: absolute;
            top: 10px;
            left: 50%;
            transform: translateX(-50%);
            background-color: rgba(255, 255, 255, 0.8);
            color: #800000;
            padding: 5px 10px;
            border-radius: 5px;
            display: none; /* Hidden by default */
            font-weight: bold;
            font-size: 14px;
        }
        .profile-left.active .hover-text {
            display: block; /* Show on hover when in edit mode */
        }
        .profile-right {
            padding: 40px;
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            font-weight: bold;
            margin-bottom: 10px;
            font-size: 16px;
        }
        .form-group input, .form-group button {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            margin-top: 5px;
            font-size: 15px;
        }
        .btn {
            padding: 12px 25px;
            background-color: #800000;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            margin-top: 25px;
            transition: background-color 0.3s ease;
            font-size: 16px;
        }
        .btn:hover {
            background-color: #a10000;
        }
        .btn[disabled] {
            background-color: grey;
            cursor: not-allowed;
        }
        .feedback-message {
            margin-top: 20px;
            font-size: 16px;
            padding: 10px;
            border-radius: 6px;
            width: 100%;
            color: #fff; /* Text color for feedback */
        }
        .feedback-success {
            background-color: #28a745; /* Green background */
        }
        .feedback-no-change {
            background-color: #6c757d; /* Gray background */
        }
        #previewModal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.7);
            justify-content: center;
            align-items: center;
            z-index: 999;
        }
        .crop-container {
            background-color: white;
            padding: 15px;
            border-radius: 10px;
            max-width: 300px;
            max-height: 300px;
        }
        .crop-container img {
            width: 100%;
            max-height: 100%;
        }
        @media (max-width: 768px) {
            .profile-container {
                flex-direction: column;
            }
            .profile-left {
                width: 100%;
                padding: 20px;
            }
            .profile-right {
                padding: 20px;
            }
        }
    </style>
</head>
<body>

<div class="profile-container">
    <div class="profile-left">
        <img id="profileImage" src="<?php echo htmlspecialchars($profilePicSrc); ?>" alt="Profile Picture" disabled>
        <h2><?php echo htmlspecialchars($userData['firstName']) . ' ' . htmlspecialchars($userData['lastName']); ?></h2>
        <p><?php echo ucfirst($userType); ?></p>
        <input type="file" id="profilePicInput" accept="image/*" style="display:none;" disabled>
        <div class="hover-text">Modify Picture</div>
    </div>
    <div class="profile-right">
        <form id="profileForm" action="" method="post" enctype="multipart/form-data">
            <div class="form-group">
                <label for="firstName">First Name</label>
                <input type="text" id="firstName" name="firstName" value="<?php echo htmlspecialchars($userData['firstName']); ?>" required disabled>
            </div>
            <div class="form-group">
                <label for="lastName">Last Name</label>
                <input type="text" id="lastName" name="lastName" value="<?php echo htmlspecialchars($userData['lastName']); ?>" required disabled>
            </div>
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($userData['email']); ?>" required disabled>
            </div>
            <div class="form-group">
                <label for="contactNum">Contact Number</label>
                <input type="text" id="contactNum" name="contactNum" value="<?php echo htmlspecialchars($userData['contactNum']); ?>" required disabled>
            </div>
            <button type="button" id="editProfileBtn" class="btn">Modify Profile Information</button>
            <input type="submit" id="updateProfileBtn" class="btn" name="update_profile" value="Update Profile" disabled style="display:none;">
        </form>

        <?php if ($feedbackMessage): ?>
            <div class="feedback-message <?php echo strpos($feedbackMessage, 'No changes') !== false ? 'feedback-no-change' : 'feedback-success'; ?>">
                <?php echo htmlspecialchars($feedbackMessage); ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal for cropping and preview -->
<div id="previewModal">
    <div class="crop-container">
        <img id="imageToCrop" src="" alt="Image to Crop">
        <button class="btn" onclick="cropAndSave()">Crop and Save</button>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.12/cropper.min.js"></script>
<script>
    const profilePicInput = document.getElementById('profilePicInput');
    const profileImage = document.getElementById('profileImage');
    const imageToCrop = document.getElementById('imageToCrop');
    const previewModal = document.getElementById('previewModal');
    const editProfileBtn = document.getElementById('editProfileBtn');
    const updateProfileBtn = document.getElementById('updateProfileBtn');
    const formFields = document.querySelectorAll('#profileForm input[type="text"], #profileForm input[type="email"]');
    let cropper;

    // Initially disable form fields and profile picture hover functionality
    formFields.forEach(input => {
        input.disabled = true;
    });
    profileImage.style.pointerEvents = 'none';
    profilePicInput.disabled = true;

    // Enable fields for editing when "Modify Profile Information" is clicked
    editProfileBtn.addEventListener('click', () => {
        formFields.forEach(input => {
            input.disabled = false;
        });
        profileImage.style.pointerEvents = 'auto'; // Enable hover and click on profile picture
        profilePicInput.disabled = false;
        updateProfileBtn.style.display = 'inline-block';
        updateProfileBtn.disabled = false;
        editProfileBtn.style.display = 'none'; // Hide modify button

        // Add active class to show the hover text
        document.querySelector('.profile-left').classList.add('active');
    });

    // When the user clicks the profile picture, open the file input to select a new picture
    profileImage.addEventListener('click', () => {
        if (!profilePicInput.disabled) {
            profilePicInput.click();
        }
    });

    // When a new profile picture is selected, display the cropping modal
    profilePicInput.addEventListener('change', (event) => {
        const files = event.target.files;
        if (files && files.length > 0) {
            const file = files[0];
            const reader = new FileReader();
            reader.onload = (e) => {
                imageToCrop.src = e.target.result;
                previewModal.style.display = 'flex';
                if (cropper) {
                    cropper.destroy();
                }
                cropper = new Cropper(imageToCrop, {
                    aspectRatio: 1,
                    viewMode: 1
                });
            };
            reader.readAsDataURL(file);
        }
    });

    // Save the cropped image and update the profile picture preview
    function cropAndSave() {
        const croppedCanvas = cropper.getCroppedCanvas();
        const base64Image = croppedCanvas.toDataURL();

        // Display the cropped image in the profile picture area
        profileImage.src = base64Image;

        // Save the cropped image data to a hidden input for form submission
        const hiddenInput = document.createElement('input');
        hiddenInput.type = 'hidden';
        hiddenInput.name = 'croppedImage';
        hiddenInput.value = base64Image;
        document.getElementById('profileForm').appendChild(hiddenInput);

        previewModal.style.display = 'none';
        cropper.destroy();
    }
</script>
<?php include 'footer.php'; ?>
</body>
</html>
