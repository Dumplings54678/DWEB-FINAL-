<?php
/**
 * HAUccountant Login Page
 * Enhanced with CSRF protection, remember me, rate limiting, and better UX
 */


session_start();
require_once 'config/database.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: index.php');
    exit();
}

// Rate limiting - prevent brute force attacks
$rate_limit_key = 'login_attempts_' . $_SERVER['REMOTE_ADDR'];
$rate_limit_window = 900; // 15 minutes
$max_attempts = 5;

if (!isset($_SESSION[$rate_limit_key])) {
    $_SESSION[$rate_limit_key] = [
        'count' => 0,
        'first_attempt' => time()
    ];
}

// Check if rate limit exceeded
if ($_SESSION[$rate_limit_key]['count'] >= $max_attempts) {
    $time_passed = time() - $_SESSION[$rate_limit_key]['first_attempt'];
    if ($time_passed < $rate_limit_window) {
        $wait_time = ceil(($rate_limit_window - $time_passed) / 60);
        $error = "Too many login attempts. Please try again in {$wait_time} minutes.";
    } else {
        // Reset counter
        $_SESSION[$rate_limit_key] = [
            'count' => 0,
            'first_attempt' => time()
        ];
    }
}

// Generate CSRF token
$csrf_token = generateCSRFToken();

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !isset($error)) {
    
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $error = "Invalid security token. Please try again.";
    } else {
        
        // Sanitize inputs
        $email = sanitizeInput($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $remember = isset($_POST['remember']);
        
        // Validate inputs
        if (empty($email) || empty($password)) {
            $error = "Please enter both email and password.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Please enter a valid email address.";
        } else {
            
            // Increment login attempts
            $_SESSION[$rate_limit_key]['count']++;
            
            // Get user from database
            $stmt = $pdo->prepare("
                SELECT id, owner_name, email, password, role, status 
                FROM users 
                WHERE email = ? AND status = 'active'
            ");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            // Verify password
            if ($user && password_verify($password, $user['password'])) {
                
                // Check if password needs rehash
                if (password_needs_rehash($user['password'], PASSWORD_DEFAULT)) {
                    $newHash = password_hash($password, PASSWORD_DEFAULT);
                    $updateStmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                    $updateStmt->execute([$newHash, $user['id']]);
                }
                
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['owner_name'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['login_time'] = time();
                
                // Record login history
                recordLoginHistory($pdo, $user['id'], 'success');
                
                // Set remember me cookie if requested
                if ($remember) {
                    $token = bin2hex(random_bytes(32));
                    $expires = time() + (30 * 24 * 60 * 60); // 30 days
                    setcookie('remember_token', $token, $expires, '/', '', true, true);
                }
                
                // Log successful login
                logActivity($pdo, $user['id'], 'LOGIN', 'user', 'Successful login', $_SERVER['REMOTE_ADDR']);
                
                // Reset login attempts on success
                unset($_SESSION[$rate_limit_key]);
                
                // Redirect to intended page or dashboard
                $redirect = $_SESSION['redirect_url'] ?? 'index.php';
                unset($_SESSION['redirect_url']);
                header('Location: ' . $redirect);
                exit();
                
            } else {
                $error = "Invalid email or password.";
                // Record failed login attempt
                recordLoginHistory($pdo, 0, 'failed');
                error_log("Failed login attempt for email: $email from IP: " . $_SERVER['REMOTE_ADDR']);
            }
        }
    }
}

// Get registered success message
$registered = isset($_GET['registered']) ? true : false;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In - PLANORA</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Enriqueta:wght@400;600;700&display=swap" rel="stylesheet">
    
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- AOS Animation -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="auth.css">
</head>
<body class="auth-page">
    
    <!-- Animated background elements -->
    <div class="orb orb-1"></div>
    <div class="orb orb-2"></div>
    <div class="orb orb-3"></div>
    
    <div class="auth-container" data-aos="fade-up" data-aos-duration="800">
        <div class="auth-box">
            
            <!-- Logo Section -->
            <div class="logo-section" data-aos="fade-down" data-aos-delay="100">
                <div class="logo">
                    <i class="fas fa-chart-line"></i>
                </div>
                <h1>PLANORA</h1>
                <p class="tagline">"ACCOUNTIN NAMIN ANG ACCOUNT MO"</p>
            </div>
            
            <!-- Header -->
            <div class="auth-header" data-aos="fade-right" data-aos-delay="200">
                <h2>Welcome Back</h2>
                <p>Sign in to your PLANORA account</p>
            </div>
            
            <!-- Error Message -->
            <?php if (isset($error)): ?>
                <div class="alert error" data-aos="fade-in" data-aos-delay="250">
                    <i class="fas fa-exclamation-circle"></i>
                    <span><?php echo htmlspecialchars($error); ?></span>
                    <button class="alert-close"><i class="fas fa-times"></i></button>
                </div>
            <?php endif; ?>
            
            <!-- Success Message -->
            <?php if ($registered): ?>
                <div class="alert success" data-aos="fade-in" data-aos-delay="250">
                    <i class="fas fa-check-circle"></i>
                    <span>Registration successful! Please login.</span>
                    <button class="alert-close"><i class="fas fa-times"></i></button>
                </div>
            <?php endif; ?>
            
            <!-- Login Form -->
            <form method="POST" action="" id="loginForm" data-aos="fade-up" data-aos-delay="300">
                
                <!-- CSRF Token -->
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                
                <div class="form-group">
                    <label for="email">
                        <i class="fas fa-envelope"></i>
                        Email Address
                    </label>
                    <div class="input-wrapper">
                        <i class="fas fa-envelope input-icon"></i>
                        <input 
                            type="email" 
                            id="email"
                            name="email" 
                            placeholder="you@example.com" 
                            required 
                            value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                            autocomplete="email"
                            autofocus
                        >
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="password">
                        <i class="fas fa-lock"></i>
                        Password
                    </label>
                    <div class="input-wrapper">
                        <i class="fas fa-lock input-icon"></i>
                        <input 
                            type="password" 
                            id="password"
                            name="password" 
                            placeholder="Enter your password" 
                            required
                            autocomplete="current-password"
                        >
                        <button type="button" class="toggle-password" onclick="togglePassword()">
                            <i class="fas fa-eye" id="toggleIcon"></i>
                        </button>
                    </div>
                </div>
                
                <!-- Remember Me & Forgot Password -->
                <div class="form-row">
                    <label class="checkbox-label">
                        <input type="checkbox" name="remember" id="remember">
                        <span class="checkbox-custom"></span>
                        <span class="checkbox-text">Remember me</span>
                    </label>
                    <a href="forgot-password.php" class="forgot-link">
                        <i class="fas fa-question-circle"></i>
                        Forgot Password?
                    </a>
                </div>
                
                <!-- Sign In Button -->
                <button type="submit" class="auth-btn" id="loginBtn">
                    <span class="btn-text">Sign In</span>
                    <i class="fas fa-arrow-right"></i>
                    <div class="btn-loader"></div>
                </button>
                
                <!-- Create Account Link -->
                <div class="auth-link">
                    Don't have an account? 
                    <a href="register.php">
                        Create Account
                        <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
                
                <!-- Back to Home -->
                <div class="back-home">
                    <a href="landing.php">
                        <i class="fas fa-arrow-left"></i>
                        Back to Home
                    </a>
                </div>
                
                <!-- Demo Credentials -->
                <div class="demo-hint" data-aos="zoom-in" data-aos-delay="400">
                    <i class="fas fa-info-circle"></i>
                    <span>Demo:</span>
                    <span class="demo-cred">admin@PLANORA.com</span>
                    <span class="demo-sep">/</span>
                    <span class="demo-cred">admin123</span>
                </div>
            </form>
        </div>
    </div>
    
    <!-- JavaScript -->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        // Initialize AOS
        AOS.init({
            once: true,
            mirror: false
        });
        
        // Toggle password visibility
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.getElementById('toggleIcon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }
        
        // Form submission with loading state
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const btn = document.getElementById('loginBtn');
            btn.classList.add('loading');
            btn.querySelector('.btn-text').style.opacity = '0';
        });
        
        // Close alerts
        document.querySelectorAll('.alert-close').forEach(btn => {
            btn.addEventListener('click', function() {
                this.closest('.alert').remove();
            });
        });
        
        // Auto-hide alerts after 5 seconds
        setTimeout(() => {
            document.querySelectorAll('.alert').forEach(alert => {
                alert.style.transition = 'opacity 0.5s';
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 500);
            });
        }, 5000);
        
        // Input focus effects
        document.querySelectorAll('.form-group input').forEach(input => {
            input.addEventListener('focus', function() {
                this.closest('.form-group').classList.add('focused');
            });
            
            input.addEventListener('blur', function() {
                this.closest('.form-group').classList.remove('focused');
            });
        });
    </script>
</body>
</html>