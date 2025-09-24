<?php
session_start();

// Prevent caching and going back to previous pages
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

$page_title = "Login";
$error_message = "";

// Handle login form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    require_once 'db/db_connect.php';
    
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    if (empty($username) || empty($password)) {
        $error_message = "Please enter both username and password.";
    } else {
        // Prepare SQL statement to prevent SQL injection
        $stmt = $conn->prepare("SELECT id, username, password, role FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            // Verify password (assuming passwords are hashed with password_hash)
            if (password_verify($password, $user['password'])) {
                // Password is correct, create session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                
                // Redirect based on user role
                if ($user['role'] === 'registrar') {
                    header("Location: registrar_dashboard.php");
                } else {
                    header("Location: dashboard.php");
                }
                exit();
            } else {
                $error_message = "Invalid username or password.";
            }
        } else {
            $error_message = "Invalid username or password.";
        }
        
        $stmt->close();
        $conn->close();
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <title><?php echo $page_title; ?></title>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/login.css">
</head>
<body>
    <div class="login-container">
        <div class="login-panel">
            <!-- Logo and Branding -->
            <div class="branding">
                <div class="logo">
                    <div class="logo-shape">
                        <img src="img/aegio.png" alt="AEGIO Logo" style="width:80px; height:auto; border-radius: 10px;">
                    </div>
                </div>
                <h1 class="brand-name">AEGIO</h1>
                <p class="tagline">RFID Entry & Attendance System</p>
            </div>

            <!-- Error Message Display -->
            <?php if (!empty($error_message)): ?>
                <div class="error-message">
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <!-- Login Form -->
            <form class="login-form" method="POST" action="">
                <div class="form-group">
                    <label for="username">
                        <svg class="input-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M12 12C14.7614 12 17 9.76142 17 7C17 4.23858 14.7614 2 12 2C9.23858 2 7 4.23858 7 7C7 9.76142 9.23858 12 12 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M20.5899 22C20.5899 18.13 16.7399 15 11.9999 15C7.25991 15 3.40991 18.13 3.40991 22" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        Username
                    </label>
                    <input type="text" id="username" name="username" placeholder="Enter your username" required value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label for="password">
                        <svg class="input-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M19 11H5C3.89543 11 3 11.8954 3 13V20C3 21.1046 3.89543 22 5 22H19C20.1046 22 21 21.1046 21 20V13C21 11.8954 20.1046 11 19 11Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M7 11V7C7 5.67392 7.52678 4.40215 8.46447 3.46447C9.40215 2.52678 10.6739 2 12 2C13.3261 2 14.5979 2.52678 15.5355 3.46447C16.4732 4.40215 17 5.67392 17 7V11" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        Password
                    </label>
                    <div class="password-input-wrapper">
                        <input type="password" id="password" name="password" placeholder="Enter your password" required>
                        <button type="button" class="password-toggle" onclick="togglePassword()">
                            <svg class="eye-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M1 12S5 4 12 4S23 12 23 12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M12 5C14.5013 5 16.891 5.80951 18.6685 7.33112C20.446 8.85273 21.5 10.8284 21.5 13C21.5 15.1716 20.446 17.1473 18.6685 18.6689C16.891 20.1905 14.5013 21 12 21C9.49872 21 7.10899 20.1905 5.33147 18.6689C3.55395 17.1473 2.5 15.1716 2.5 13C2.5 10.8284 3.55395 8.85273 5.33147 7.33112C7.10899 5.80951 9.49872 5 12 5Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <button type="submit" class="signin-btn">Sign In</button>
            </form>

            <!-- Forgot Password Link -->
            <!--<div class="forgot-password">
                <a href="#" class="forgot-link">Forgot your Password?</a>
            </div> -->

            <!-- Teacher Access Link -->
            <div class="teacher-access" style="margin-top: 12px; text-align: center;">
                <a href="searchBar.php" class="teacher-link" style="display: inline-block; padding: 10px 16px; background-color: #2b6cb0; color: #fff; border-radius: 8px; text-decoration: none; font-weight: 600;">Teacher Access</a>
            </div>

            <!-- Divider -->
            <div class="divider"></div>

            <!-- Copyright -->
            <div class="copyright">
                <p>&copy; 2025 Young Generation Academy. All rights Reserved.</p>
            </div>
        </div>
    </div>

    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const eyeIcon = document.querySelector('.eye-icon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeIcon.innerHTML = `
                    <path d="M17.94 17.94C16.2306 19.243 14.1491 19.9649 12 20C5 20 1 12 1 12C1 12 4.243 6.06 7.94 2.06" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M9.9 4.24C10.5883 4.0789 11.2931 3.99836 12 4C18 4 22 12 22 12C22 12 21.308 15.74 20.1 18.24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M14.12 14.12C13.8454 14.4148 13.5141 14.6512 13.1462 14.8151C12.7782 14.9791 12.3809 15.0663 11.9781 15.0709C11.5753 15.0755 11.1747 15.0065 10.801 14.8685C10.4272 14.7305 10.0867 14.5263 9.80385 14.2688C9.52097 14.0113 9.30123 13.7048 9.15821 13.3648C9.0152 13.0248 8.95279 12.6587 8.97434 12.2925C8.99589 11.9263 9.10112 11.5685 9.28342 11.2403C9.46572 10.9121 9.72059 10.6208 10.03 10.39" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M1 1L23 23" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                `;
            } else {
                passwordInput.type = 'password';
                eyeIcon.innerHTML = `
                    <path d="M1 12S5 4 12 4S23 12 23 12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M12 5C14.5013 5 16.891 5.80951 18.6685 7.33112C20.446 8.85273 21.5 10.8284 21.5 13C21.5 15.1716 20.446 17.1473 18.6685 18.6689C16.891 20.1905 14.5013 21 12 21C9.49872 21 7.10899 20.1905 5.33147 18.6689C3.55395 17.1473 2.5 15.1716 2.5 13C2.5 10.8284 3.55395 8.85273 5.33147 7.33112C7.10899 5.80951 9.49872 5 12 5Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                `;
            }
        }
    </script>
</body>
</html>
