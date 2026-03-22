<?php
/**
 * HAUccountant About Page
 * Company information, features, and platform overview
 */

session_start();
require_once 'config/database.php';
redirectIfNotLoggedIn();

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
$user_role = $_SESSION['user_role'];

// Get total users count
$stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
$total_users = $stmt->fetch()['count'];

// Get total sales count
$stmt = $pdo->query("SELECT COUNT(*) as count FROM sales");
$total_sales = $stmt->fetch()['count'];

// Get total products count
$stmt = $pdo->query("SELECT COUNT(*) as count FROM products");
$total_products = $stmt->fetch()['count'];

// Get total expenses count
$stmt = $pdo->query("SELECT COUNT(*) as count FROM expenses");
$total_expenses = $stmt->fetch()['count'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About - PLANORA</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Enriqueta:wght@400;600;700&display=swap" rel="stylesheet">
    
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- AOS Animation -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="style.css">
    
    <style>
        
    </style>
</head>
<body>
    <div class="container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="logo-section">
                <h1>PLANORA</h1>
                <p class="tagline">"Accounting Ko ang Account Mo."</p>
            </div>
            
            <nav class="nav-menu">
    <a href="index.php" class="nav-item home">
        <i class="fas fa-chart-pie"></i>
        <span>Dashboard</span>
    </a>
    <a href="sales.php" class="nav-item sales">
        <i class="fas fa-shopping-cart"></i>
        <span>Sales</span>
    </a>
    <a href="expenses.php" class="nav-item expenses">
        <i class="fas fa-receipt"></i>
        <span>Expenses</span>
    </a>
    <a href="inventory.php" class="nav-item inventory">
        <i class="fas fa-boxes"></i>
        <span>Inventory</span>
    </a>
    <a href="reports.php" class="nav-item reports">
        <i class="fas fa-file-alt"></i>
        <span>Reports</span>
    </a>
    <a href="budget.php" class="nav-item budget">
        <i class="fas fa-wallet"></i>
        <span>Budget</span>
    </a>
    
    <!-- ADD THESE TWO LINES HERE - AFTER BUDGET -->
    <a href="history.php" class="nav-item history">
        <i class="fas fa-history"></i>
        <span>History</span>
    </a>
    <a href="contact.php" class="nav-item contact">
        <i class="fas fa-envelope"></i>
        <span>Contact</span>
    </a>
    
    <?php if ($user_role === 'admin'): ?>
    <a href="users.php" class="nav-item users">
        <i class="fas fa-users"></i>
        <span>Users</span>
    </a>
    <a href="settings.php" class="nav-item settings">
        <i class="fas fa-cog"></i>
        <span>Settings</span>
    </a>
    
    <!-- ADD THESE TWO LINES HERE - AFTER SETTINGS (FOR ADMIN ONLY) -->
    <a href="admin_messages.php" class="nav-item admin-messages">
        <i class="fas fa-inbox"></i>
        <span>Messages</span>
        <?php 
        $unread_count = getUnreadContactCount($pdo);
        if ($unread_count > 0): 
        ?>
        <span style="background: #EF4444; color: white; font-size: 10px; padding: 2px 6px; border-radius: 30px; margin-left: 5px;"><?php echo $unread_count; ?></span>
        <?php endif; ?>
    </a>
    <a href="admin_users.php" class="nav-item admin-users">
        <i class="fas fa-users-cog"></i>
        <span>Users Mgmt</span>
    </a>
    <?php endif; ?>
    
    <a href="about.php" class="nav-item about">
        <i class="fas fa-info-circle"></i>
        <span>About</span>
    </a>
</nav>
            <div class="user-info">
                <div class="user-avatar">
                    <i class="fas fa-user-circle"></i>
                </div>
                <div class="user-details">
                    <p class="user-name"><?php echo htmlspecialchars($user_name); ?></p>
                    <p class="user-role"><?php echo ucfirst($user_role); ?></p>
                </div>
                <a href="logout.php" class="logout-btn" title="Logout">
                    <i class="fas fa-sign-out-alt"></i>
                </a>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <!-- About Header -->
            <div class="about-header" data-aos="fade-down">
                <h1>PLANORA</h1>
                <p class="tagline">"Accounting Ko ang Account Mo."</p>
                <p class="version">Version 2.0.0 | Released February 2025</p>
            </div>

            <!-- Mission Section -->
            <div class="mission-section" data-aos="fade-up">
                <div class="mission-content">
                    <h2><i class="fas fa-bullseye"></i> Our Mission</h2>
                    <p>PLANORA is a comprehensive accounting and business management platform designed specifically for small to medium-sized businesses in the Philippines. Our mission is to simplify financial management and help business owners make informed decisions through real-time insights and easy-to-use tools.</p>
                    <p>With PLANORA, you can manage sales, track expenses, monitor inventory, generate reports, and much more—all in one place. Our platform is built to help Filipino entrepreneurs focus on growing their business while we handle the numbers.</p>
                </div>
            </div>

            <!-- Statistics -->
            <div class="stats-grid-about" data-aos="fade-up">
                <div class="stat-card-about">
                    <div class="stat-icon-about">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-number-about"><?php echo number_format($total_users); ?>+</div>
                    <div class="stat-label-about">Active Users</div>
                </div>
                <div class="stat-card-about">
                    <div class="stat-icon-about">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <div class="stat-number-about"><?php echo number_format($total_sales); ?>+</div>
                    <div class="stat-label-about">Transactions</div>
                </div>
                <div class="stat-card-about">
                    <div class="stat-icon-about">
                        <i class="fas fa-boxes"></i>
                    </div>
                    <div class="stat-number-about"><?php echo number_format($total_products); ?>+</div>
                    <div class="stat-label-about">Products</div>
                </div>
                <div class="stat-card-about">
                    <div class="stat-icon-about">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-number-about">99.9%</div>
                    <div class="stat-label-about">Uptime</div>
                </div>
            </div>

            <!-- Features Showcase -->
            <div class="features-showcase">
                <h2 data-aos="fade-up"><i class="fas fa-star"></i> Key Features</h2>
                
                <div class="features-grid-about">
                    <div class="feature-item-about" data-aos="fade-up" data-aos-delay="100">
                        <div class="feature-icon-about">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <h4>Profit & Loss Dashboard</h4>
                        <p>Automatically calculates total revenue, expenses, and net profit with real-time updates. Visual charts and business status indicators.</p>
                    </div>
                    
                    <div class="feature-item-about" data-aos="fade-up" data-aos-delay="150">
                        <div class="feature-icon-about">
                            <i class="fas fa-boxes"></i>
                        </div>
                        <h4>Inventory Management</h4>
                        <p>Real-time stock tracking with automatic deduction after every sale. Low-stock alerts and organized product listing.</p>
                    </div>
                    
                    <div class="feature-item-about" data-aos="fade-up" data-aos-delay="200">
                        <div class="feature-icon-about">
                            <i class="fas fa-receipt"></i>
                        </div>
                        <h4>Expense Tracking</h4>
                        <p>Records all business expenses with smart categorization. Monthly summaries and budget tracking.</p>
                    </div>
                    
                    <div class="feature-item-about" data-aos="fade-up" data-aos-delay="250">
                        <div class="feature-icon-about">
                            <i class="fas fa-chart-pie"></i>
                        </div>
                        <h4>Sales Analytics</h4>
                        <p>Identifies best-selling products and tracks sales trends. Peak sales days analysis and restocking insights.</p>
                    </div>
                    
                    <div class="feature-item-about" data-aos="fade-up" data-aos-delay="300">
                        <div class="feature-icon-about">
                            <i class="fas fa-calculator"></i>
                        </div>
                        <h4>Tax & VAT Computation</h4>
                        <p>Automatic tax calculation on sales with printable reports. VAT tracking and organized tax records.</p>
                    </div>
                    
                    <div class="feature-item-about" data-aos="fade-up" data-aos-delay="350">
                        <div class="feature-icon-about">
                            <i class="fas fa-users"></i>
                        </div>
                        <h4>Multi-User Access</h4>
                        <p>Admin and staff accounts with customizable permissions. Activity logs and transaction monitoring.</p>
                    </div>
                    
                    <div class="feature-item-about" data-aos="fade-up" data-aos-delay="400">
                        <div class="feature-icon-about">
                            <i class="fas fa-cloud"></i>
                        </div>
                        <h4>Cloud Backup</h4>
                        <p>Secure data storage with automatic backups. Export to PDF, Excel, or CSV formats.</p>
                    </div>
                    
                    <div class="feature-item-about" data-aos="fade-up" data-aos-delay="450">
                        <div class="feature-icon-about">
                            <i class="fas fa-tachometer-alt"></i>
                        </div>
                        <h4>Performance Indicator</h4>
                        <p>Color-coded system for quick business health check. Profitable, break-even, and loss warnings.</p>
                    </div>
                    
                    <div class="feature-item-about" data-aos="fade-up" data-aos-delay="500">
                        <div class="feature-icon-about">
                            <i class="fas fa-wallet"></i>
                        </div>
                        <h4>Budget Planning</h4>
                        <p>Set monthly targets and compare actual vs planned performance. Financial planning tools.</p>
                    </div>
                </div>
            </div>

            <!-- Team Section -->
            <div class="team-section">
                <h2 data-aos="fade-up"><i class="fas fa-users-cog"></i> Meet the Team</h2>
                
                <div class="team-grid">
                    <div class="team-card" data-aos="fade-up" data-aos-delay="100">
                        <div class="team-avatar">AL</div>
                        <h4>Almer Lalic</h4>
                        <div class="position">Founder & CEO</div>
                        <p class="bio">Former accountant with 15 years of experience. Built PLANORA to help small businesses manage their finances better.</p>
                    </div>
                    
                    <div class="team-card" data-aos="fade-up" data-aos-delay="150">
                        <div class="team-avatar">SY</div>
                        <h4>Sean Yambao</h4>
                        <div class="position">Lead Developer</div>
                        <p class="bio">Full-stack developer with expertise in PHP and MySQL. Passionate about creating intuitive business solutions.</p>
                    </div>
                    
                    <div class="team-card" data-aos="fade-up" data-aos-delay="200">
                        <div class="team-avatar">BO</div>
                        <h4>Benedict Ong</h4>
                        <div class="position">Customer Success</div>
                        <p class="bio">Dedicated to helping users get the most out of PLANORA. Former business owner herself.</p>
                    </div>
                </div>
            </div>

            <!-- FAQ Section -->
            <div class="faq-section">
                <h2 data-aos="fade-up"><i class="fas fa-question-circle"></i> Frequently Asked Questions</h2>
                
                <div class="faq-grid">
                    <div class="faq-item" data-aos="fade-right">
                        <h4><i class="fas fa-shield-alt"></i> Is my data secure?</h4>
                        <p>Yes, we take security seriously. All data is encrypted and stored securely. We use industry-standard security measures to protect your information.</p>
                    </div>
                    
                    <div class="faq-item" data-aos="fade-left">
                        <h4><i class="fas fa-users"></i> Can I add multiple users?</h4>
                        <p>Yes! You can add staff members with customizable permissions. Admin accounts have full access while staff accounts have limited permissions.</p>
                    </div>
                    
                    <div class="faq-item" data-aos="fade-right">
                        <h4><i class="fas fa-file-export"></i> How do I export reports?</h4>
                        <p>Navigate to the Reports page and use the export buttons to download reports in PDF, Excel, or CSV format.</p>
                    </div>
                    
                    <div class="faq-item" data-aos="fade-left">
                        <h4><i class="fas fa-box"></i> What happens when stock runs out?</h4>
                        <p>The system will prevent sales of out-of-stock items and display low-stock alerts on your dashboard.</p>
                    </div>
                    
                    <div class="faq-item" data-aos="fade-right">
                        <h4><i class="fas fa-percent"></i> Can I customize tax rates?</h4>
                        <p>Yes, you can configure VAT rates and tax settings in the System Settings page.</p>
                    </div>
                    
                    <div class="faq-item" data-aos="fade-left">
                        <h4><i class="fas fa-cloud"></i> Is cloud backup included?</h4>
                        <p>Yes, all data is automatically backed up. You can also create manual backups and export your data anytime.</p>
                    </div>
                </div>
            </div>

            <!-- Contact Section -->
            <div class="contact-section-about" data-aos="zoom-in">
                <h2><i class="fas fa-headset"></i> Get in Touch</h2>
                
                <div class="contact-info-about">
                    <div class="contact-item">
                        <i class="fas fa-envelope"></i>
                        <span>support@PLANORA.com</span>
                    </div>
                    <div class="contact-item">
                        <i class="fas fa-phone"></i>
                        <span>+63 (02) 1234-5678</span>
                    </div>
                    <div class="contact-item">
                        <i class="fas fa-map-marker-alt"></i>
                        <span>Makati City, Philippines</span>
                    </div>
                </div>
            </div>

            <!-- Footer Note -->
            <div style="text-align: center; margin-top: 50px; padding: 20px; color: #94a3b8; border-top: 1px solid #e2e8f0;">
                <p>© 2025 PLANORA. All rights reserved. "Accounting Ko ang Account Mo."</p>
            </div>
        </main>
    </div>

    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init({
            duration: 600,
            once: true
        });
    </script>
</body>
</html>