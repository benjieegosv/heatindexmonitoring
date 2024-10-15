<?php
// Include database connection and start session
include 'db_conn.php';

// Check if user is not logged in and redirect to login page
if (!isset($_SESSION['accNum']) && !isset($_SESSION['adminAccNum']) && !isset($_SESSION['guestAccNum'])) {
    header("Location: login.php");
    exit();
}
?>

<!-- footer.php -->
<footer class="footer">
    <div class="footer-container">
        <div class="footer-links">
            <div class="footer-column">
                <ul>
                    <li><a href="about.php">About Us</a></li>
                    <li><a href="contact.php">Contact</a></li>
                </ul>
            </div>
            <div class="footer-column">
                <ul>
                    <li><a href="terms_of_use.php">Terms of Use</a></li>
                    <li><a href="privacy_policy.php">Privacy Policy</a></li>
                </ul>
            </div>
        </div>

        <div class="footer-disclaimer">
            <p>We recognize our responsibility to use data and technology for good. We may use or share your data with our data vendors. Take control of your data.</p>
            <a href="data_rights.php" class="data-rights">Data Rights</a>
        </div>

        <div class="footer-socials">
            <ul class="social-icons">
                <li><a href="mailto:kazeynaval0329@gmail.com"><i class="fas fa-envelope"></i></a></li>
            </ul>
        </div>

        <div class="footer-copyright">
            <p>&copy; Copyright PUP Heat Index Monitoring, <?php echo date("Y"); ?></p>
        </div>
    </div>
</footer>

<!-- Footer CSS -->
<style>
    .footer {
        background-color: #800000; /* Maroon background */
        color: #fff; /* White text */
        padding: 40px 20px;
    }

    .footer-container {
        max-width: 1200px;
        margin: 0 auto;
        text-align: center;
    }

    .footer-links {
        display: flex;
        justify-content: center;
        margin-bottom: 20px;
    }

    .footer-column {
        margin: 0 20px;
    }

    .footer-column ul {
        list-style-type: none;
        padding: 0;
    }

    .footer-column ul li {
        margin-bottom: 10px;
    }

    .footer-column ul li a {
        color: #fff; /* White text for links */
        text-decoration: none;
    }

    .footer-column ul li a:hover {
        text-decoration: underline;
    }

    .footer-disclaimer {
        margin-top: 20px;
        margin-bottom: 20px;
        font-size: 14px;
    }

    .data-rights {
        color: #fff; /* White text */
        text-decoration: underline; /* Underlined to differentiate links */
    }

    .footer-socials {
    margin: 20px 0;
    }

    .social-icons {
        list-style-type: none;
        padding: 0;
        display: flex;
        justify-content: center;
    }

    .social-icons li {
        margin: 0 10px;
    }

    .social-icons li a {
        color: #fff; /* White color for icons */
        font-size: 24px;
        text-decoration: none;
    }

    .social-icons li a:hover {
        color: #ccc; /* Lighter color on hover */
    }

    .footer-copyright {
        font-size: 12px;
        color: #ccc; /* Light gray for less emphasis */
    }

    @media (max-width: 768px) {
        .footer-links {
            flex-direction: column;
        }
    }
</style>
