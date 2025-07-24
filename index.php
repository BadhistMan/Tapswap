<?php
// Start the session to manage user login state. This line is crucial.
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Tap Swap Clone</title>
    <!-- Link to the CSS file -->
    <link rel="stylesheet" href="style.css">
    <!-- Link to Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <div id="app-container">
        <!-- =============================================== -->
        <!-- AUTHENTICATION SECTION (Login & Register Forms) -->
        <!-- =============================================== -->
        <div id="auth-section">
            <!-- Login Form -->
            <div id="login-form">
                <h2>Login</h2>
                <input type="text" id="login-username" placeholder="Username" required>
                <input type="password" id="login-password" placeholder="Password" required>
                <button id="login-btn">Login</button>
                <p>Don't have an account? <a href="#" id="show-register">Register</a></p>
            </div>
            <!-- Register Form (hidden by default) -->
            <div id="register-form" style="display:none;">
                <h2>Register</h2>
                <input type="text" id="register-username" placeholder="Username" required>
                <input type="password" id="register-password" placeholder="Password" required>
                <button id="register-btn">Register</button>
                <p>Already have an account? <a href="#" id="show-login">Login</a></p>
            </div>
        </div>

        <!-- =============================================== -->
        <!-- MAIN CONTENT (Shown after successful login)   -->
        <!-- =============================================== -->
        <main id="main-content" style="display:none;">
            <!-- Dashboard / Tap Page -->
            <div id="dashboard-section" class="page-section active">
                <div class="header">
                    <div class="points-display">
                        <i class="fas fa-coins"></i>
                        <span id="points-value">0</span>
                    </div>
                    <div class="energy-display">
                        <i class="fas fa-bolt"></i>
                        <span id="energy-value">100/100</span>
                    </div>
                </div>
                <div class="cat-container">
                    <div id="cat-tapper">
                        <!-- SVG Placeholder for the tappable cat graphic -->
                        <svg viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg" aria-label="Tappable Cat">
                            <circle cx="50" cy="50" r="45" fill="#ffcc00"/>
                            <circle cx="35" cy="40" r="5" fill="#000"/>
                            <circle cx="65" cy="40" r="5" fill="#000"/>
                            <path d="M 40 60 Q 50 70 60 60" stroke="#000" stroke-width="2" fill="none"/>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Profile Page -->
            <div id="profile-section" class="page-section">
                <h2>Profile</h2>
                <p>Username: <strong id="profile-username"></strong></p>
                <p>Total Points: <strong id="profile-points"></strong></p>
                <button id="logout-btn">Logout</button>
            </div>

            <!-- Tasks Page -->
            <div id="tasks-section" class="page-section">
                <h2>Tasks</h2>
                <div id="task-list">
                    <!-- Tasks will be dynamically inserted here by JavaScript -->
                </div>
            </div>

            <!-- Withdraw Page -->
            <div id="withdraw-section" class="page-section">
                <h2>Withdraw</h2>
                <p>Your Points: <span id="withdraw-points">0</span></p>
                <p>Minimum for withdrawal: <span id="min-withdraw-points">100</span></p>
                <button id="withdraw-btn" disabled>Withdraw <span id="withdraw-amount">100</span> Points</button>
                <hr>
                <h3>Withdrawal History</h3>
                <div id="withdrawal-history">
                    <p>No withdrawal history.</p>
                </div>
            </div>
        </main>

        <!-- =============================================== -->
        <!-- BOTTOM NAVIGATION BAR                           -->
        <!-- =============================================== -->
        <nav id="bottom-nav" style="display:none;">
            <a href="#" class="nav-item" data-section="profile-section">
                <i class="fas fa-user"></i>
                <span>Profile</span>
            </a>
            <a href="#" class="nav-item active" data-section="dashboard-section">
                <i class="fas fa-paw"></i>
                <span>Tap</span>
            </a>
            <a href="#" class="nav-item" data-section="tasks-section">
                <i class="fas fa-tasks"></i>
                <span>Tasks</span>
            </a>
            <a href="#" class="nav-item" data-section="withdraw-section">
                <i class="fas fa-wallet"></i>
                <span>Withdraw</span>
            </a>
        </nav>
    </div>
    <!-- Link to the JavaScript file -->
    <script src="script.js"></script>
</body>
</html>```
