<nav class="navbar sticky-top navbar-expand-sm navbar-dark bg-maroon">
    <a class="navbar-brand" href="home.php">
        <img src="Images/PUP.png" width="30" height="30" class="d-inline-block align-top" alt="PUP Logo"> PUP Heat Index Monitoring
    </a>
    <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav ml-auto">
            <li class="nav-item active">
                <a class="nav-link" href="#" id="home-nav">Home</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#" id="monitoring-nav">Monitoring</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#" id="reports-nav">Reports</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#" id="manage-account-nav">Manage Account</a>
            </li>
        </ul>
        <ul class="navbar-nav">
            <li class="nav-item dropdown profile">
                <a href="#" class="nav-link dropdown-toggle" id="navbarDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <img src="Images/FaMO.png" alt="user-image" class="img-circle img-inline" width="30" height="30">
                    <span class="ml-2"><?php echo $firstName; ?></span>
                </a>
                <div class="dropdown-menu dropdown-menu-right" aria-labelledby="navbarDropdown">
                    <a class="dropdown-item" href="profile.php?id=<?php echo (int)$_SESSION['id']; ?>">
                        <i class="glyphicon glyphicon-user"></i> Profile
                    </a>
                    <a class="dropdown-item" href="edit_account.php">
                        <i class="glyphicon glyphicon-cog"></i> Settings
                    </a>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item" href="login.php">
                        <i class="glyphicon glyphicon-off"></i> Logout
                    </a>
                </div>
            </li>
        </ul>
    </div>
</nav>
