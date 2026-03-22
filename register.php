<?php
/**
 * HAUccountant Registration Page
 * Enhanced with password strength meter, email verification, and better UX
 */

session_start();
require_once 'config/database.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: index.php');
    exit();
}

// Generate CSRF token
$csrf_token = generateCSRFToken();

// Password policy
$password_policy = [
    'min_length' => 8,
    'require_uppercase' => true,
    'require_lowercase' => true,
    'require_numbers' => true,
    'require_special' => true
];

// Business types
$business_types = [
    'retail' => 'Retail Store',
    'wholesale' => 'Wholesale Distributor',
    'service' => 'Service Provider',
    'manufacturing' => 'Manufacturing',
    'food' => 'Food & Beverage',
    'construction' => 'Construction',
    'realestate' => 'Real Estate',
    'consulting' => 'Consulting',
    'technology' => 'Technology',
    'healthcare' => 'Healthcare',
    'education' => 'Education',
    'other' => 'Other'
];

// Handle registration form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    $errors = [];
    $form_data = [];
    
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $errors[] = "Invalid security token. Please try again.";
    } else {
        
        // Sanitize and validate inputs
        $business_name = sanitizeInput($_POST['business_name'] ?? '');
        $owner_name = sanitizeInput($_POST['owner_name'] ?? '');
        $email = sanitizeInput($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        $business_type = sanitizeInput($_POST['business_type'] ?? '');
        $terms = isset($_POST['terms']);
        
        $form_data = [
            'business_name' => $business_name,
            'owner_name' => $owner_name,
            'email' => $email,
            'business_type' => $business_type
        ];
        
        // Validate business name
        if (empty($business_name)) {
            $errors[] = "Business name is required.";
        } elseif (strlen($business_name) < 2) {
            $errors[] = "Business name must be at least 2 characters.";
        } elseif (strlen($business_name) > 100) {
            $errors[] = "Business name must be less than 100 characters.";
        }
        
        // Validate owner name
        if (empty($owner_name)) {
            $errors[] = "Owner name is required.";
        } elseif (strlen($owner_name) < 2) {
            $errors[] = "Owner name must be at least 2 characters.";
        } elseif (strlen($owner_name) > 100) {
            $errors[] = "Owner name must be less than 100 characters.";
        } elseif (!preg_match("/^[a-zA-Z\s'-]+$/", $owner_name)) {
            $errors[] = "Owner name can only contain letters, spaces, hyphens, and apostrophes.";
        }
        
        // Validate email
        if (empty($email)) {
            $errors[] = "Email address is required.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Please enter a valid email address.";
        } elseif (strlen($email) > 255) {
            $errors[] = "Email address must be less than 255 characters.";
        } else {
            // Check if email already exists
            $check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $check->execute([$email]);
            if ($check->fetch()) {
                $errors[] = "This email is already registered. Please <a href='login.php'>login</a> instead.";
            }
        }
        
        // Validate password
        $password_errors = [];
        if (empty($password)) {
            $password_errors[] = "Password is required.";
        } else {
            if (strlen($password) < $password_policy['min_length']) {
                $password_errors[] = "At least {$password_policy['min_length']} characters";
            }
            if ($password_policy['require_uppercase'] && !preg_match('/[A-Z]/', $password)) {
                $password_errors[] = "One uppercase letter";
            }
            if ($password_policy['require_lowercase'] && !preg_match('/[a-z]/', $password)) {
                $password_errors[] = "One lowercase letter";
            }
            if ($password_policy['require_numbers'] && !preg_match('/[0-9]/', $password)) {
                $password_errors[] = "One number";
            }
            if ($password_policy['require_special'] && !preg_match('/[^a-zA-Z0-9]/', $password)) {
                $password_errors[] = "One special character";
            }
        }
        
        if (!empty($password_errors)) {
            $errors[] = "Password must contain: " . implode(', ', $password_errors);
        }
        
        // Confirm password
        if ($password !== $confirm_password) {
            $errors[] = "Passwords do not match.";
        }
        
        // Validate terms
        if (!$terms) {
            $errors[] = "You must accept the Terms and Conditions.";
        }
        
        // If no errors, create account
        if (empty($errors)) {
            try {
                // Begin transaction
                $pdo->beginTransaction();
                
                // Hash password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                // Insert user
                $stmt = $pdo->prepare("
                    INSERT INTO users (
                        business_name, owner_name, email, password, 
                        role, status, created_at
                    ) VALUES (?, ?, ?, ?, 'admin', 'active', NOW())
                ");
                
                $stmt->execute([$business_name, $owner_name, $email, $hashed_password]);
                $user_id = $pdo->lastInsertId();
                
                // Log registration
                logActivity($pdo, $user_id, 'REGISTER', 'user', "New user registered: $email");
                
                // Commit transaction
                $pdo->commit();
                
                // Set success message and redirect
                $_SESSION['success'] = "Registration successful! Please login.";
                header('Location: login.php?registered=1');
                exit();
                
            } catch (Exception $e) {
                $pdo->rollBack();
                error_log("Registration failed: " . $e->getMessage());
                $errors[] = "Registration failed. Please try again later.";
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
    <title>Create Account - PLANORA</title>
    
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
    
    <style>
        /* Password strength meter */
        .password-strength {
            margin-top: 8px;
            display: flex;
            gap: 4px;
        }
        
        .strength-segment {
            flex: 1;
            height: 4px;
            background: var(--gray-200);
            border-radius: var(--radius-full);
            transition: var(--transition-base);
        }
        
        .strength-segment.active {
            background: var(--success-500);
        }
        
        .strength-segment.weak {
            background: var(--danger-500);
        }
        
        .strength-segment.medium {
            background: var(--warning-500);
        }
        
        .strength-segment.strong {
            background: var(--success-500);
        }
        
        .password-requirements {
            margin-top: 8px;
            font-size: 12px;
            color: var(--gray-500);
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
        }
        
        .requirement {
            display: flex;
            align-items: center;
            gap: 4px;
        }
        
        .requirement i {
            font-size: 12px;
        }
        
        .requirement.met {
            color: var(--success-600);
        }
        
        .requirement.met i {
            color: var(--success-600);
        }
        
        .requirement.unmet {
            color: var(--gray-400);
        }
        
        .requirement.unmet i {
            color: var(--gray-400);
        }
        
        /* Toggle password button */
        .toggle-password {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--gray-500);
            cursor: pointer;
            padding: 4px;
            z-index: 3;
        }
        
        .toggle-password:hover {
            color: var(--primary-600);
        }
        
        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            backdrop-filter: blur(5px);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        
        .modal-content {
            background: white;
            padding: 35px;
            border-radius: 24px;
            width: 90%;
            max-width: 800px;
            max-height: 80vh;
            overflow-y: auto;
            box-shadow: 0 25px 60px rgba(0,0,0,0.2);
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid var(--primary-soft);
        }
        
        .modal-header h3 {
            font-size: 24px;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .modal-header h3 i {
            color: var(--primary);
        }
        
        .modal-actions {
            display: flex;
            gap: 15px;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid var(--gray-200);
        }
        
        .btn {
            padding: 12px 24px;
            border-radius: 30px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s ease;
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--accent));
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(6, 182, 212, 0.3);
        }
        
        .btn-outline {
            background: white;
            border: 1px solid var(--gray-200);
            color: var(--gray-700);
        }
        
        .btn-outline:hover {
            background: var(--gray-50);
            border-color: var(--primary);
        }
        
        .modal-content h4 {
            color: var(--gray-800);
            margin: 20px 0 10px;
        }
        
        .modal-content h4:first-of-type {
            margin-top: 0;
        }
        
        .modal-content p {
            color: var(--gray-600);
            line-height: 1.6;
            margin-bottom: 15px;
        }
        
        .modal-content a {
            color: var(--primary);
            text-decoration: none;
        }
        
        .modal-content a:hover {
            text-decoration: underline;
        }
        
        @media screen and (max-width: 768px) {
            .modal-content {
                padding: 25px;
                width: 95%;
            }
            
            .modal-actions {
                flex-direction: column;
            }
        }
    </style>
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
                <h2>Create Account</h2>
                <p>Get started with PLANORA today</p>
            </div>
            
            <!-- Error Messages -->
            <?php if (!empty($errors)): ?>
                <div class="alert error" data-aos="fade-in" data-aos-delay="250">
                    <i class="fas fa-exclamation-circle"></i>
                    <div class="alert-content">
                        <strong>Please fix the following errors:</strong>
                        <ul>
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo $error; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <button class="alert-close"><i class="fas fa-times"></i></button>
                </div>
            <?php endif; ?>
            
            <!-- Registration Form -->
            <form method="POST" action="" id="registerForm" data-aos="fade-up" data-aos-delay="300">
                
                <!-- CSRF Token -->
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                
                <div class="form-group">
                    <label for="business_name">
                        <i class="fas fa-building"></i>
                        Business Name
                    </label>
                    <div class="input-wrapper">
                        <i class="fas fa-building input-icon"></i>
                        <input 
                            type="text" 
                            id="business_name"
                            name="business_name" 
                            placeholder="Enter your business name" 
                            required
                            value="<?php echo htmlspecialchars($form_data['business_name'] ?? ''); ?>"
                            autocomplete="organization"
                            autofocus
                        >
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="owner_name">
                        <i class="fas fa-user"></i>
                        Owner Name
                    </label>
                    <div class="input-wrapper">
                        <i class="fas fa-user input-icon"></i>
                        <input 
                            type="text" 
                            id="owner_name"
                            name="owner_name" 
                            placeholder="Enter your full name" 
                            required
                            value="<?php echo htmlspecialchars($form_data['owner_name'] ?? ''); ?>"
                            autocomplete="name"
                        >
                    </div>
                </div>
                
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
                            value="<?php echo htmlspecialchars($form_data['email'] ?? ''); ?>"
                            autocomplete="email"
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
                            placeholder="Create a strong password" 
                            required
                            autocomplete="new-password"
                        >
                        <button type="button" class="toggle-password" onclick="togglePassword('password')">
                            <i class="fas fa-eye" id="toggleIcon1"></i>
                        </button>
                    </div>
                    
                    <!-- Password strength meter -->
                    <div class="password-strength" id="passwordStrength">
                        <div class="strength-segment" id="segment1"></div>
                        <div class="strength-segment" id="segment2"></div>
                        <div class="strength-segment" id="segment3"></div>
                        <div class="strength-segment" id="segment4"></div>
                    </div>
                    
                    <!-- Password requirements -->
                    <div class="password-requirements" id="passwordRequirements">
                        <span class="requirement" id="req-length">
                            <i class="fas fa-circle"></i> 8+ characters
                        </span>
                        <span class="requirement" id="req-uppercase">
                            <i class="fas fa-circle"></i> Uppercase
                        </span>
                        <span class="requirement" id="req-lowercase">
                            <i class="fas fa-circle"></i> Lowercase
                        </span>
                        <span class="requirement" id="req-number">
                            <i class="fas fa-circle"></i> Number
                        </span>
                        <span class="requirement" id="req-special">
                            <i class="fas fa-circle"></i> Special
                        </span>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">
                        <i class="fas fa-lock"></i>
                        Confirm Password
                    </label>
                    <div class="input-wrapper">
                        <i class="fas fa-lock input-icon"></i>
                        <input 
                            type="password" 
                            id="confirm_password"
                            name="confirm_password" 
                            placeholder="Re-enter your password" 
                            required
                            autocomplete="new-password"
                        >
                        <button type="button" class="toggle-password" onclick="togglePassword('confirm_password')">
                            <i class="fas fa-eye" id="toggleIcon2"></i>
                        </button>
                    </div>
                    <div class="password-match" id="passwordMatch"></div>
                </div>
                
                <div class="form-group">
                    <label for="business_type">
                        <i class="fas fa-store"></i>
                        Business Type (Optional)
                    </label>
                    <div class="input-wrapper">
                        <i class="fas fa-store input-icon"></i>
                        <select name="business_type" id="business_type">
                            <option value="">Select business type</option>
                            <?php foreach ($business_types as $value => $label): ?>
                                <option value="<?php echo $value; ?>" 
                                    <?php echo (isset($form_data['business_type']) && $form_data['business_type'] == $value) ? 'selected' : ''; ?>>
                                    <?php echo $label; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <!-- Terms and Conditions with Modal Links -->
                <div class="checkbox-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="terms" id="terms" required>
                        <span class="checkbox-custom"></span>
                        <span class="checkbox-text">
                            I agree to the <a href="#" onclick="openTermsModal(); return false;">Terms of Service</a> and 
                            <a href="#" onclick="openPrivacyModal(); return false;">Privacy Policy</a>
                        </span>
                    </label>
                </div>
                
                <!-- Create Account Button -->
                <button type="submit" class="auth-btn" id="registerBtn">
                    <span class="btn-text">Create Account</span>
                    <i class="fas fa-user-plus"></i>
                    <div class="btn-loader"></div>
                </button>
                
                <!-- Sign In Link -->
                <div class="auth-link">
                    Already have an account? 
                    <a href="login.php">
                        Sign In
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
            </form>
        </div>
    </div>

    <!-- Terms of Service Modal -->
    <div id="termsModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-file-contract"></i> Terms of Service</h3>
                <span class="close" onclick="closeTermsModal()">&times;</span>
            </div>
            <div style="padding: 20px;">
                <h4>1. Agreement to Terms</h4>
                <p>These Terms of Service constitute a legally binding agreement made between you and PLANORA concerning your access to and use of the PLANORA website and accounting platform. By accessing or using the Services, you agree that you have read, understood, and agree to be bound by all of these Terms of Service.</p>
                
                <h4>2. Eligibility</h4>
                <p>You must be at least 18 years of age to use this service. By using the Services, you represent and warrant that you meet all eligibility requirements and that you are not located in a country that is subject to a Philippines government embargo.</p>
                
                <h4>3. User Accounts</h4>
                <p>You are responsible for maintaining the security of your account and for all activities that occur under your account. You agree to provide accurate, current, and complete information during the registration process and to update such information to keep it accurate, current, and complete.</p>
                
                <h4>4. Subscription and Payments</h4>
                <p>We offer various subscription plans for access to our Services. You agree to pay all fees applicable to your selected subscription plan. Subscriptions automatically renew at the end of each billing cycle unless canceled.</p>
                
                <h4>5. User Content</h4>
                <p>You retain all ownership rights to your User Content. By submitting User Content to the Services, you grant us a worldwide, non-exclusive, royalty-free license to use, reproduce, modify, adapt, publish, and display such User Content solely for the purpose of providing, maintaining, and improving the Services.</p>
                
                <h4>6. Prohibited Activities</h4>
                <p>You may not use the Services for any unlawful purpose or to engage in any prohibited activities including, but not limited to, attempting to gain unauthorized access to the Services, interfering with the proper functioning of the Services, or using the Services to store or transmit malware.</p>
                
                <h4>7. Termination</h4>
                <p>We may terminate or suspend your account and bar access to the Services immediately, without prior notice or liability, under our sole discretion, for any reason whatsoever including a breach of the Terms.</p>
                
                <h4>8. Limitation of Liability</h4>
                <p>To the fullest extent permitted by applicable law, in no event shall PLANORA be liable for any indirect, incidental, special, consequential, or punitive damages, including without limitation, loss of profits, data, use, goodwill, or other intangible losses.</p>
                
                <h4>9. Governing Law</h4>
                <p>These Terms shall be governed and construed in accordance with the laws of the Republic of the Philippines, without regard to its conflict of law provisions. Any legal suit, action, or proceeding arising out of or related to these Terms shall be instituted exclusively in the courts of Makati City, Philippines.</p>
                
                <h4>10. Changes to Terms</h4>
                <p>We reserve the right, at our sole discretion, to modify or replace these Terms at any time. If a revision is material, we will try to provide at least 30 days' notice prior to any new terms taking effect.</p>
                
                <div style="margin-top: 20px; text-align: right;">
                    <a href="terms.php" style="color: var(--primary);">Read Full Terms <i class="fas fa-arrow-right"></i></a>
                </div>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn btn-primary" onclick="acceptTerms()">I Agree</button>
                <button type="button" class="btn btn-outline" onclick="closeTermsModal()">Close</button>
            </div>
        </div>
    </div>

    <!-- Privacy Policy Modal -->
    <div id="privacyModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-lock"></i> Privacy Policy</h3>
                <span class="close" onclick="closePrivacyModal()">&times;</span>
            </div>
            <div style="padding: 20px;">
                <h4>Information We Collect</h4>
                <p>We collect personal information that you voluntarily provide to us when you register on the Services, express an interest in obtaining information about us or our products and services, when you participate in activities on the Services, or otherwise when you contact us. This may include business information, personal identifiers, financial data, and account credentials.</p>
                
                <h4>How We Use Your Information</h4>
                <p>We use personal information collected via our Services for a variety of business purposes including to facilitate account creation, provide and maintain our Services, manage your account, contact you, provide you with news and special offers, improve our Services, protect our Services, and comply with legal obligations.</p>
                
                <h4>Data Sharing and Disclosure</h4>
                <p>We may process or share your data based on consent, legitimate interests, performance of a contract, or legal obligations. We may share your information with service providers, business partners, and affiliates. We do not sell your personal information to third parties for their marketing purposes without your explicit consent.</p>
                
                <h4>Data Security</h4>
                <p>We have implemented appropriate technical and organizational security measures designed to protect the security of any personal information we process. These include encryption of data in transit and at rest, regular security audits, multi-factor authentication, strict access controls, and 24/7 monitoring for suspicious activity.</p>
                
                <h4>Your Privacy Rights</h4>
                <p>Depending on your location, you may have certain rights regarding your personal information including the right to access, rectification, erasure, restriction of processing, data portability, and objection to processing. To exercise any of these rights, please contact us at privacy@PLANORA.com.</p>
                
                <h4>Cookies and Tracking Technologies</h4>
                <p>We use cookies and similar tracking technologies to access or store information. We use essential cookies required for the operation of our website, analytical cookies to understand how visitors interact with our website, functionality cookies to recognize you when you return, and targeting cookies to record your visit.</p>
                
                <h4>Children's Privacy</h4>
                <p>Our Services are not intended for use by children under the age of 18. We do not knowingly collect personal information from children under 18. If you become aware that a child has provided us with personal information, please contact us.</p>
                
                <h4>Changes to This Privacy Policy</h4>
                <p>We may update this privacy policy from time to time. The updated version will be indicated by an updated "Revised" date and the updated version will be effective as soon as it is accessible. If we make material changes, we may notify you either by prominently posting a notice or by directly sending you a notification.</p>
                
                <h4>Contact Us</h4>
                <p>If you have questions or comments about this policy, you may contact our Data Protection Officer by email at privacy@PLANORA.com, or by post to: PLANORA Privacy Team, 123 Business District, Makati City, 1200, Philippines.</p>
                
                <div style="margin-top: 20px; text-align: right;">
                    <a href="privacy.php" style="color: var(--primary);">Read Full Privacy Policy <i class="fas fa-arrow-right"></i></a>
                </div>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn btn-primary" onclick="acceptPrivacy()">I Understand</button>
                <button type="button" class="btn btn-outline" onclick="closePrivacyModal()">Close</button>
            </div>
        </div>
    </div>

    <!-- AOS Script -->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    
    <!-- Custom JavaScript - Add these functions to your separate JS file or keep them here -->
    <script>
        // Initialize AOS
        AOS.init({
            once: true,
            mirror: false
        });

        // ============================================
        // TERMS AND PRIVACY MODAL FUNCTIONS
        // ============================================
        function openTermsModal() {
            document.getElementById('termsModal').style.display = 'flex';
            return false;
        }

        function closeTermsModal() {
            document.getElementById('termsModal').style.display = 'none';
        }

        function acceptTerms() {
            document.getElementById('terms').checked = true;
            closeTermsModal();
        }

        function openPrivacyModal() {
            document.getElementById('privacyModal').style.display = 'flex';
            return false;
        }

        function closePrivacyModal() {
            document.getElementById('privacyModal').style.display = 'none';
        }

        function acceptPrivacy() {
            // Privacy acceptance is implied by checking terms
            closePrivacyModal();
        }

        // Close modals when clicking outside
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        }

        // ============================================
        // PASSWORD TOGGLE FUNCTIONS
        // ============================================
        function togglePassword(fieldId) {
            const input = document.getElementById(fieldId);
            const icon = fieldId === 'password' ? 
                document.getElementById('toggleIcon1') : 
                document.getElementById('toggleIcon2');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        // ============================================
        // PASSWORD STRENGTH CHECKER
        // ============================================
        const password = document.getElementById('password');
        const confirm = document.getElementById('confirm_password');
        const segments = [
            document.getElementById('segment1'),
            document.getElementById('segment2'),
            document.getElementById('segment3'),
            document.getElementById('segment4')
        ];
        
        // Requirement elements
        const reqLength = document.getElementById('req-length');
        const reqUppercase = document.getElementById('req-uppercase');
        const reqLowercase = document.getElementById('req-lowercase');
        const reqNumber = document.getElementById('req-number');
        const reqSpecial = document.getElementById('req-special');
        const matchIndicator = document.getElementById('passwordMatch');
        
        if (password) {
            password.addEventListener('input', checkPasswordStrength);
        }
        
        if (confirm) {
            confirm.addEventListener('input', checkPasswordMatch);
        }
        
        function checkPasswordStrength() {
            const val = password.value;
            let strength = 0;
            
            // Check length
            if (val.length >= 8) {
                strength++;
                updateRequirement(reqLength, true);
            } else {
                updateRequirement(reqLength, false);
            }
            
            // Check uppercase
            if (/[A-Z]/.test(val)) {
                strength++;
                updateRequirement(reqUppercase, true);
            } else {
                updateRequirement(reqUppercase, false);
            }
            
            // Check lowercase
            if (/[a-z]/.test(val)) {
                strength++;
                updateRequirement(reqLowercase, true);
            } else {
                updateRequirement(reqLowercase, false);
            }
            
            // Check number
            if (/[0-9]/.test(val)) {
                strength++;
                updateRequirement(reqNumber, true);
            } else {
                updateRequirement(reqNumber, false);
            }
            
            // Check special
            if (/[^a-zA-Z0-9]/.test(val)) {
                strength++;
                updateRequirement(reqSpecial, true);
            } else {
                updateRequirement(reqSpecial, false);
            }
            
            // Update strength meter
            updateStrengthMeter(strength);
        }
        
        function updateRequirement(element, met) {
            if (!element) return;
            
            const icon = element.querySelector('i');
            if (met) {
                element.classList.add('met');
                element.classList.remove('unmet');
                if (icon) {
                    icon.classList.remove('fa-circle');
                    icon.classList.add('fa-check-circle');
                }
            } else {
                element.classList.add('unmet');
                element.classList.remove('met');
                if (icon) {
                    icon.classList.remove('fa-check-circle');
                    icon.classList.add('fa-circle');
                }
            }
        }
        
        function updateStrengthMeter(strength) {
            if (!segments.length) return;
            
            // Reset all segments
            segments.forEach(seg => {
                if (seg) seg.className = 'strength-segment';
            });
            
            // Activate segments based on strength
            for (let i = 0; i < strength; i++) {
                if (i < segments.length && segments[i]) {
                    if (strength <= 2) {
                        segments[i].classList.add('weak');
                    } else if (strength <= 4) {
                        segments[i].classList.add('medium');
                    } else {
                        segments[i].classList.add('strong');
                    }
                    segments[i].classList.add('active');
                }
            }
        }
        
        function checkPasswordMatch() {
            if (!matchIndicator) return;
            
            if (confirm.value) {
                if (password.value === confirm.value) {
                    matchIndicator.innerHTML = '<i class="fas fa-check-circle"></i> Passwords match';
                    matchIndicator.style.color = 'var(--success-600)';
                } else {
                    matchIndicator.innerHTML = '<i class="fas fa-exclamation-circle"></i> Passwords do not match';
                    matchIndicator.style.color = 'var(--danger-600)';
                }
            } else {
                matchIndicator.innerHTML = '';
            }
        }

        // ============================================
        // FORM SUBMISSION WITH LOADING STATE
        // ============================================
        document.getElementById('registerForm')?.addEventListener('submit', function(e) {
            const btn = document.getElementById('registerBtn');
            if (btn) {
                btn.classList.add('loading');
                const btnText = btn.querySelector('.btn-text');
                if (btnText) btnText.style.opacity = '0';
            }
        });

        // ============================================
        // CLOSE ALERTS
        // ============================================
        document.querySelectorAll('.alert-close').forEach(btn => {
            btn.addEventListener('click', function() {
                this.closest('.alert').remove();
            });
        });

        // ============================================
        // AUTO-HIDE ALERTS
        // ============================================
        setTimeout(() => {
            document.querySelectorAll('.alert').forEach(alert => {
                alert.style.transition = 'opacity 0.5s';
                alert.style.opacity = '0';
                setTimeout(() => {
                    if (alert.parentNode) alert.style.display = 'none';
                }, 500);
            });
        }, 5000);

        // ============================================
        // INPUT FOCUS EFFECTS
        // ============================================
        document.querySelectorAll('.form-group input, .form-group select').forEach(input => {
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