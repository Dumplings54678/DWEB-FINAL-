<?php
/**
 * HAUccountant How-To Guides
 * User guides and tutorials
 */

session_start();
require_once 'config/database.php';

// Public page - no login required
$user_id = $_SESSION['user_id'] ?? null;
$user_name = $_SESSION['user_name'] ?? 'Guest';
$user_role = $_SESSION['user_role'] ?? 'guest';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>How-To Guides - PLANORA</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Enriqueta:wght@400;600;700&display=swap" rel="stylesheet">
    
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- AOS Animation -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Plus Jakarta Sans', 'Inter', sans-serif;
            color: #1e293b;
            overflow-x: hidden;
            line-height: 1.6;
            background: #ffffff;
        }

        /* Navigation */
        .navbar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            padding: 20px 0;
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }

        .nav-container {
            max-width: 1280px;
            margin: 0 auto;
            padding: 0 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
        }

        .logo-icon {
            width: 52px;
            height: 52px;
            background: linear-gradient(135deg, #06B6D4, #3B82F6);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 10px rgba(6, 182, 212, 0.3);
            transition: all 0.3s ease;
        }

        .logo-icon i {
            color: white;
            font-size: 26px;
        }

        .logo:hover .logo-icon {
            transform: rotate(5deg) scale(1.05);
        }

        .brand-name {
            font-size: 24px;
            font-weight: 700;
            color: #1e293b;
            letter-spacing: -0.5px;
        }

        .nav-center {
            display: flex;
            gap: 48px;
        }

        .nav-link {
            color: #1e293b;
            text-decoration: none;
            font-weight: 500;
            font-size: 16px;
            transition: color 0.3s ease;
            position: relative;
        }

        .nav-link::after {
            content: '';
            position: absolute;
            bottom: -4px;
            left: 0;
            width: 0;
            height: 2px;
            background: #06B6D4;
            transition: width 0.3s ease;
        }

        .nav-link:hover::after {
            width: 100%;
        }

        .nav-right {
            display: flex;
            align-items: center;
            gap: 32px;
        }

        .signin-link {
            color: #1e293b;
            text-decoration: none;
            font-weight: 500;
            font-size: 16px;
            transition: color 0.3s ease;
        }

        .signin-link:hover {
            color: #06B6D4;
        }

        .get-started-btn {
            background: #000000;
            color: white;
            text-decoration: none;
            padding: 12px 28px;
            border-radius: 40px;
            font-weight: 600;
            font-size: 15px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .get-started-btn:hover {
            background: #1e293b;
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.3);
        }

        /* Page Header */
        .page-header {
            background: linear-gradient(135deg, #06B6D4, #14B8A6, #0891b2);
            padding: 140px 0 80px;
            text-align: center;
            color: white;
            position: relative;
            overflow: hidden;
        }

        .page-header::before {
            content: '';
            position: absolute;
            width: 600px;
            height: 600px;
            background: radial-gradient(circle, rgba(255,255,255,0.2) 0%, transparent 70%);
            border-radius: 50%;
            top: -200px;
            right: -100px;
        }

        .page-header::after {
            content: '';
            position: absolute;
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, rgba(255,255,255,0.15) 0%, transparent 70%);
            border-radius: 50%;
            bottom: -150px;
            left: -150px;
        }

        .page-header h1 {
            font-family: 'Enriqueta', serif;
            font-size: 48px;
            font-weight: 700;
            margin-bottom: 15px;
            position: relative;
            z-index: 2;
            text-shadow: 0 4px 20px rgba(0,0,0,0.2);
        }

        .page-header p {
            font-size: 18px;
            opacity: 0.95;
            position: relative;
            z-index: 2;
            max-width: 600px;
            margin: 0 auto;
        }

        .container {
            max-width: 1280px;
            margin: 0 auto;
            padding: 0 24px;
        }

        /* Guides Page */
        .guides-page {
            padding: 80px 0;
            background: #f8fafc;
        }

        .guides-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 30px;
        }

        .guide-card {
            background: white;
            border-radius: 20px;
            padding: 30px;
            border: 1px solid #e2e8f0;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }

        .guide-card:hover {
            transform: translateY(-5px);
            border-color: #06B6D4;
            box-shadow: 0 12px 24px rgba(0,0,0,0.1);
        }

        .guide-icon {
            width: 60px;
            height: 60px;
            background: #cffafe;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
        }

        .guide-icon i {
            font-size: 28px;
            color: #06B6D4;
        }

        .guide-category {
            font-size: 12px;
            color: #06B6D4;
            font-weight: 600;
            margin-bottom: 10px;
            display: block;
        }

        .guide-card h3 {
            font-family: 'Enriqueta', serif;
            font-size: 20px;
            margin-bottom: 12px;
            color: #0f172a;
        }

        .guide-card p {
            color: #64748b;
            font-size: 14px;
            line-height: 1.6;
            margin-bottom: 20px;
        }

        .read-more {
            color: #06B6D4;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .read-more:hover {
            gap: 10px;
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
            border-radius: 24px;
            width: 90%;
            max-width: 800px;
            max-height: 85vh;
            overflow-y: auto;
            box-shadow: 0 25px 60px rgba(0,0,0,0.2);
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 25px 30px;
            border-bottom: 1px solid #e2e8f0;
            background: linear-gradient(135deg, #06B6D4, #14B8A6);
            border-radius: 24px 24px 0 0;
        }

        .modal-header h3 {
            font-family: 'Enriqueta', serif;
            font-size: 24px;
            color: white;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .modal-header h3 i {
            font-size: 28px;
        }

        .modal-header .close {
            width: 36px;
            height: 36px;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 20px;
            color: white;
            transition: all 0.3s ease;
        }

        .modal-header .close:hover {
            background: rgba(255,255,255,0.3);
            transform: rotate(90deg);
        }

        .modal-body {
            padding: 30px;
        }

        .guide-full-content {
            line-height: 1.8;
        }

        .guide-full-content h2 {
            font-family: 'Enriqueta', serif;
            font-size: 28px;
            color: #0f172a;
            margin: 20px 0 15px;
        }

        .guide-full-content h3 {
            font-size: 20px;
            color: #1e293b;
            margin: 20px 0 10px;
        }

        .guide-full-content p {
            color: #475569;
            margin-bottom: 15px;
        }

        .guide-full-content ul, 
        .guide-full-content ol {
            margin: 15px 0;
            padding-left: 25px;
            color: #475569;
        }

        .guide-full-content li {
            margin-bottom: 8px;
        }

        .guide-full-content .step {
            background: #f8fafc;
            padding: 15px 20px;
            border-radius: 12px;
            margin: 15px 0;
            border-left: 4px solid #06B6D4;
        }

        .guide-full-content .step h4 {
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 8px;
        }

        .guide-full-content .tip {
            background: linear-gradient(135deg, #cffafe, #ccfbf1);
            padding: 15px 20px;
            border-radius: 12px;
            margin: 15px 0;
        }

        .modal-actions {
            padding: 20px 30px;
            border-top: 1px solid #e2e8f0;
            display: flex;
            justify-content: flex-end;
            gap: 15px;
        }

        .modal-actions button {
            padding: 10px 24px;
            border-radius: 30px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            border: none;
        }

        .modal-actions .btn-close {
            background: #f1f5f9;
            color: #64748b;
        }

        .modal-actions .btn-close:hover {
            background: #e2e8f0;
        }

        .modal-actions .btn-print {
            background: #06B6D4;
            color: white;
        }

        .modal-actions .btn-print:hover {
            background: #0891b2;
            transform: translateY(-2px);
        }

        /* Footer */
        .footer {
            background: #0f172a;
            color: white;
            padding: 80px 0 30px;
        }

        .footer-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 40px;
            margin-bottom: 60px;
        }

        .footer-column h4 {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 20px;
            color: white;
            position: relative;
            display: inline-block;
        }

        .footer-column h4::after {
            content: '';
            position: absolute;
            bottom: -8px;
            left: 0;
            width: 40px;
            height: 3px;
            background: #06B6D4;
            border-radius: 3px;
        }

        .footer-column ul {
            list-style: none;
        }

        .footer-column li {
            margin-bottom: 12px;
        }

        .footer-column a {
            color: rgba(255,255,255,0.7);
            text-decoration: none;
            transition: all 0.3s ease;
            font-size: 15px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .footer-column a:hover {
            color: #06B6D4;
            transform: translateX(6px);
        }

        .footer-bottom {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 30px;
            border-top: 1px solid rgba(255,255,255,0.1);
        }

        .social-links {
            display: flex;
            gap: 20px;
        }

        .social-links a {
            color: white;
            font-size: 20px;
            transition: all 0.3s ease;
            width: 44px;
            height: 44px;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .social-links a:hover {
            background: #06B6D4;
            transform: translateY(-5px);
        }

        .copyright {
            color: rgba(255,255,255,0.7);
            font-size: 14px;
        }

        .footer-links {
            display: flex;
            gap: 30px;
        }

        .footer-links a {
            color: rgba(255,255,255,0.7);
            text-decoration: none;
            font-size: 14px;
            transition: color 0.3s ease;
        }

        .footer-links a:hover {
            color: #06B6D4;
        }

        .back-to-top {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #06B6D4, #14B8A6);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(6, 182, 212, 0.3);
            transition: all 0.3s ease;
            border: none;
            z-index: 99;
            opacity: 0;
            visibility: hidden;
        }

        .back-to-top.show {
            opacity: 1;
            visibility: visible;
        }

        .back-to-top:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(6, 182, 212, 0.4);
        }

        @media screen and (max-width: 1024px) {
            .guides-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media screen and (max-width: 768px) {
            .nav-center {
                display: none;
            }
            
            .page-header h1 {
                font-size: 36px;
            }
            
            .guides-grid {
                grid-template-columns: 1fr;
            }
            
            .footer-grid {
                grid-template-columns: 1fr;
            }
            
            .footer-bottom {
                flex-direction: column;
                gap: 20px;
                text-align: center;
            }
            
            .modal-content {
                width: 95%;
                padding: 0;
            }
            
            .modal-header h3 {
                font-size: 18px;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar" id="navbar">
        <div class="nav-container">
            <a href="landing.php" class="logo">
                <div class="logo-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <span class="brand-name">PLANORA</span>
            </a>
            
            <div class="nav-center">
                <a href="features.php" class="nav-link">Features</a>
                <a href="about.php" class="nav-link">About</a>
                <a href="support.php" class="nav-link">Support</a>
            </div>
            
            <div class="nav-right">
                <?php if ($user_id): ?>
                    <a href="index.php" class="signin-link">Dashboard</a>
                <?php else: ?>
                    <a href="login.php" class="signin-link">Sign In</a>
                <?php endif; ?>
                <a href="register.php" class="get-started-btn">
                    Get Started Free
                    <i class="fas fa-arrow-right"></i>
                </a>
            </div>
        </div>
    </nav>

    <!-- Page Header -->
    <section class="page-header">
        <div class="container">
            <h1 data-aos="fade-up">How-To Guides</h1>
            <p data-aos="fade-up" data-aos-delay="100">Learn how to make the most of PLANORA</p>
        </div>
    </section>

    <!-- Guides Content -->
    <section class="guides-page">
        <div class="container">
            <div class="guides-grid" data-aos="fade-up">
                <!-- Guide 1 - Getting Started -->
                <div class="guide-card" onclick="openGuide(1)">
                    <div class="guide-icon">
                        <i class="fas fa-rocket"></i>
                    </div>
                    <span class="guide-category">Getting Started</span>
                    <h3>Getting Started with PLANORA</h3>
                    <p>Set up your account, add products, and start tracking sales.</p>
                    <span class="read-more">Read Guide <i class="fas fa-arrow-right"></i></span>
                </div>

                <!-- Guide 2 - Recording Sales -->
                <div class="guide-card" onclick="openGuide(2)">
                    <div class="guide-icon">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <span class="guide-category">Sales</span>
                    <h3>Recording Sales</h3>
                    <p>Step-by-step guide on recording sales and generating receipts.</p>
                    <span class="read-more">Read Guide <i class="fas fa-arrow-right"></i></span>
                </div>

                <!-- Guide 3 - Tracking Expenses -->
                <div class="guide-card" onclick="openGuide(3)">
                    <div class="guide-icon">
                        <i class="fas fa-receipt"></i>
                    </div>
                    <span class="guide-category">Expenses</span>
                    <h3>Tracking Expenses</h3>
                    <p>Learn how to categorize and track your business expenses.</p>
                    <span class="read-more">Read Guide <i class="fas fa-arrow-right"></i></span>
                </div>

                <!-- Guide 4 - Managing Inventory -->
                <div class="guide-card" onclick="openGuide(4)">
                    <div class="guide-icon">
                        <i class="fas fa-boxes"></i>
                    </div>
                    <span class="guide-category">Inventory</span>
                    <h3>Managing Inventory</h3>
                    <p>Tips for managing stock levels and tracking inventory value.</p>
                    <span class="read-more">Read Guide <i class="fas fa-arrow-right"></i></span>
                </div>

                <!-- Guide 5 - Understanding Reports -->
                <div class="guide-card" onclick="openGuide(5)">
                    <div class="guide-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <span class="guide-category">Reports</span>
                    <h3>Understanding Reports</h3>
                    <p>Learn how to read and interpret your financial reports.</p>
                    <span class="read-more">Read Guide <i class="fas fa-arrow-right"></i></span>
                </div>

                <!-- Guide 6 - Setting Up Budget -->
                <div class="guide-card" onclick="openGuide(6)">
                    <div class="guide-icon">
                        <i class="fas fa-wallet"></i>
                    </div>
                    <span class="guide-category">Budget</span>
                    <h3>Setting Up Budget</h3>
                    <p>How to create and track your monthly budget goals.</p>
                    <span class="read-more">Read Guide <i class="fas fa-arrow-right"></i></span>
                </div>
            </div>
        </div>
    </section>

    <!-- Guide Modal -->
    <div id="guideModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-book-open"></i> <span id="modalTitle"></span></h3>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <div class="modal-body" id="modalBody">
                <!-- Content will be loaded here -->
            </div>
            <div class="modal-actions">
                <button class="btn-close" onclick="closeModal()">Close</button>
                <button class="btn-print" onclick="printGuide()"><i class="fas fa-print"></i> Print</button>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-column">
                    <h4>Product</h4>
                    <ul>
                        <li><a href="features.php"><i class="fas fa-chevron-right"></i> Features</a></li>
                        <li><a href="pricing.php"><i class="fas fa-chevron-right"></i> Pricing</a></li>
                        <li><a href="integrations.php"><i class="fas fa-chevron-right"></i> Integrations</a></li>
                        <li><a href="api.php"><i class="fas fa-chevron-right"></i> API</a></li>
                    </ul>
                </div>
                
                <div class="footer-column">
                    <h4>Company</h4>
                    <ul>
                        <li><a href="about.php"><i class="fas fa-chevron-right"></i> About Us</a></li>
                        <li><a href="careers.php"><i class="fas fa-chevron-right"></i> Careers</a></li>
                        <li><a href="blog.php"><i class="fas fa-chevron-right"></i> Blog</a></li>
                        <li><a href="press.php"><i class="fas fa-chevron-right"></i> Press</a></li>
                    </ul>
                </div>
                
                <div class="footer-column">
                    <h4>Resources</h4>
                    <ul>
                        <li><a href="documentation.php"><i class="fas fa-chevron-right"></i> Documentation</a></li>
                        <li><a href="support.php"><i class="fas fa-chevron-right"></i> Support</a></li>
                        <li><a href="community.php"><i class="fas fa-chevron-right"></i> Community</a></li>
                        <li><a href="guides.php"><i class="fas fa-chevron-right"></i> Guides</a></li>
                    </ul>
                </div>
                
                <div class="footer-column">
                    <h4>Legal</h4>
                    <ul>
                        <li><a href="privacy.php"><i class="fas fa-chevron-right"></i> Privacy Policy</a></li>
                        <li><a href="terms.php"><i class="fas fa-chevron-right"></i> Terms of Service</a></li>
                        <li><a href="security.php"><i class="fas fa-chevron-right"></i> Security</a></li>
                        <li><a href="compliance.php"><i class="fas fa-chevron-right"></i> Compliance</a></li>
                    </ul>
                </div>
            </div>

            <div class="footer-bottom">
                <div class="social-links">
                    <a href="#"><i class="fab fa-linkedin-in"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                    <a href="#"><i class="fab fa-facebook-f"></i></a>
                    <a href="#"><i class="fab fa-github"></i></a>
                </div>
                <div class="copyright">
                    © 2026 PLANORA. All rights reserved.
                </div>
                <div class="footer-links">
                    <a href="privacy.php">Privacy</a>
                    <a href="terms.php">Terms</a>
                    <a href="cookies.php">Cookies</a>
                    <a href="sitemap.php">Sitemap</a>
                </div>
            </div>
            
            <div style="text-align: center; margin-top: 40px; color: rgba(255,255,255,0.5); font-size: 12px;">
                <p>"Accounting Ko ang Account Mo." - Your Trusted Business Partner</p>
            </div>
        </div>
    </footer>

    <!-- Back to Top Button -->
    <div class="back-to-top" id="backToTop" onclick="scrollToTop()">
        <i class="fas fa-arrow-up"></i>
    </div>

    <!-- AOS Script -->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init({
            duration: 800,
            once: true,
            mirror: false
        });

        window.addEventListener('scroll', function() {
            const backToTop = document.getElementById('backToTop');
            if (window.scrollY > 300) {
                backToTop.classList.add('show');
            } else {
                backToTop.classList.remove('show');
            }
        });

        function scrollToTop() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        }

        // Guide content
        const guides = {
            1: {
                title: "Getting Started with HAUccountant",
                content: `
                    <div class="guide-full-content">
                        <p>Welcome to HAUccountant! This guide will help you set up your account and start using the platform.</p>
                        
                        <h2>Step 1: Create Your Account</h2>
                        <p>Visit the registration page and fill in your business details. You'll need your business name, owner name, email address, and a secure password.</p>
                        
                        <div class="step">
                            <h4>📝 Tip:</h4>
                            <p>Use a strong password with at least 8 characters, including uppercase, lowercase, numbers, and special characters.</p>
                        </div>
                        
                        <h2>Step 2: Set Up Your Business Profile</h2>
                        <p>After logging in, go to Settings to complete your business profile. Add your business address, contact number, and tax information.</p>
                        
                        <h2>Step 3: Add Your Products</h2>
                        <p>Navigate to Inventory Management and click "Add Product". Enter product details including:</p>
                        <ul>
                            <li>Product name and category</li>
                            <li>Stock quantity</li>
                            <li>Cost price and selling price</li>
                            <li>SKU or barcode (optional)</li>
                            <li>Reorder level for low stock alerts</li>
                        </ul>
                        
                        <h2>Step 4: Record Your First Sale</h2>
                        <p>Go to Sales page, select a product, enter quantity, and record the sale. The system will automatically:</p>
                        <ul>
                            <li>Calculate tax (12% VAT)</li>
                            <li>Generate a receipt number</li>
                            <li>Update your inventory stock</li>
                        </ul>
                        
                        <h2>Step 5: Track Your Expenses</h2>
                        <p>Visit Expenses page to add your business expenses. Categorize them properly for accurate reporting.</p>
                        
                        <h2>Step 6: View Your Dashboard</h2>
                        <p>The dashboard shows real-time metrics including:</p>
                        <ul>
                            <li>Today's sales and revenue</li>
                            <li>Profit/loss overview</li>
                            <li>Low stock alerts</li>
                            <li>Recent transactions</li>
                        </ul>
                        
                        <div class="tip">
                            <strong>💡 Pro Tip:</strong> Set up budget goals in the Budget Planning section to track your monthly targets!
                        </div>
                    </div>
                `
            },
            2: {
                title: "Recording Sales",
                content: `
                    <div class="guide-full-content">
                        <p>Learn how to record sales efficiently in HAUccountant.</p>
                        
                        <h2>Single Sale</h2>
                        <div class="step">
                            <h4>Step 1:</h4>
                            <p>Go to Sales page and click "Add New Sale".</p>
                        </div>
                        <div class="step">
                            <h4>Step 2:</h4>
                            <p>Select the product from the dropdown menu.</p>
                        </div>
                        <div class="step">
                            <h4>Step 3:</h4>
                            <p>Enter the quantity sold. The system will check available stock.</p>
                        </div>
                        <div class="step">
                            <h4>Step 4:</h4>
                            <p>Review the calculated total including 12% VAT.</p>
                        </div>
                        <div class="step">
                            <h4>Step 5:</h4>
                            <p>Click "Record Sale" to complete the transaction.</p>
                        </div>
                        
                        <h2>Bulk Orders</h2>
                        <p>For customers purchasing multiple items, use the "Bulk Order" feature:</p>
                        <ul>
                            <li>Click "Bulk Order" button on Sales page</li>
                            <li>Add multiple items to the order</li>
                            <li>Each item will have its own receipt</li>
                            <li>The system groups them under one order ID</li>
                        </ul>
                        
                        <h2>Receipts</h2>
                        <p>After each sale, a receipt is automatically generated with:</p>
                        <ul>
                            <li>Unique receipt number</li>
                            <li>Product details and quantity</li>
                            <li>Unit price, tax, and total amount</li>
                            <li>Date and time of transaction</li>
                            <li>Cashier name</li>
                        </ul>
                        
                        <div class="tip">
                            <strong>💡 Pro Tip:</strong> You can search past sales by receipt number, product name, or customer name in the sales table.
                        </div>
                    </div>
                `
            },
            3: {
                title: "Tracking Expenses",
                content: `
                    <div class="guide-full-content">
                        <p>Learn how to effectively track your business expenses.</p>
                        
                        <h2>Adding an Expense</h2>
                        <div class="step">
                            <h4>Step 1:</h4>
                            <p>Go to Expenses page and click "Add Expense".</p>
                        </div>
                        <div class="step">
                            <h4>Step 2:</h4>
                            <p>Select the appropriate category (Rent, Utilities, Supplies, etc.).</p>
                        </div>
                        <div class="step">
                            <h4>Step 3:</h4>
                            <p>Enter the amount and description of the expense.</p>
                        </div>
                        <div class="step">
                            <h4>Step 4:</h4>
                            <p>Choose the expense date and payment method.</p>
                        </div>
                        <div class="step">
                            <h4>Step 5:</h4>
                            <p>Add vendor name and reference number (optional).</p>
                        </div>
                        <div class="step">
                            <h4>Step 6:</h4>
                            <p>Click "Save Expense" to record it.</p>
                        </div>
                        
                        <h2>Expense Categories</h2>
                        <p>Default categories include:</p>
                        <ul>
                            <li>🏢 Rent</li>
                            <li>💡 Utilities</li>
                            <li>📦 Supplies</li>
                            <li>👥 Payroll</li>
                            <li>📢 Marketing</li>
                            <li>🚚 Transportation</li>
                            <li>🔧 Maintenance</li>
                            <li>🛡️ Insurance</li>
                            <li>📊 Taxes</li>
                        </ul>
                        
                        <h2>Bulk Add Expenses</h2>
                        <p>Use the "Bulk Add" feature to add multiple expenses at once:</p>
                        <ul>
                            <li>Click "Bulk Add" button</li>
                            <li>Add rows for each expense</li>
                            <li>Fill in details for each expense</li>
                            <li>Submit to add all at once</li>
                        </ul>
                        
                        <div class="tip">
                            <strong>💡 Pro Tip:</strong> Set up monthly expense limits in Budget Planning to track spending against your goals!
                        </div>
                    </div>
                `
            },
            4: {
                title: "Managing Inventory",
                content: `
                    <div class="guide-full-content">
                        <p>Learn how to manage your inventory effectively.</p>
                        
                        <h2>Adding Products</h2>
                        <div class="step">
                            <h4>Step 1:</h4>
                            <p>Go to Inventory page and click "Add Product".</p>
                        </div>
                        <div class="step">
                            <h4>Step 2:</h4>
                            <p>Enter product name, category, and description.</p>
                        </div>
                        <div class="step">
                            <h4>Step 3:</h4>
                            <p>Set initial stock quantity and reorder level.</p>
                        </div>
                        <div class="step">
                            <h4>Step 4:</h4>
                            <p>Enter cost price and selling price.</p>
                        </div>
                        <div class="step">
                            <h4>Step 5:</h4>
                            <p>Add SKU or barcode (optional).</p>
                        </div>
                        <div class="step">
                            <h4>Step 6:</h4>
                            <p>Click "Add Product" to save.</p>
                        </div>
                        
                        <h2>Stock Management</h2>
                        <p>HAUccountant automatically updates inventory when:</p>
                        <ul>
                            <li>A sale is recorded - stock decreases</li>
                            <li>You manually adjust stock</li>
                            <li>You add new products</li>
                        </ul>
                        
                        <h2>Low Stock Alerts</h2>
                        <p>Products with stock below reorder level appear in:</p>
                        <ul>
                            <li>Dashboard low stock watchlist</li>
                            <li>Inventory page with warning badge</li>
                            <li>Alerts section</li>
                        </ul>
                        
                        <h2>Stock Adjustments</h2>
                        <p>To adjust stock manually:</p>
                        <ul>
                            <li>Click the adjust button (scale icon) next to a product</li>
                            <li>Enter adjustment quantity (+ for adding, - for removing)</li>
                            <li>Select reason for adjustment</li>
                            <li>Apply the change</li>
                        </ul>
                        
                        <div class="tip">
                            <strong>💡 Pro Tip:</strong> Use the "Stock History" to track all inventory changes and identify patterns!
                        </div>
                    </div>
                `
            },
            5: {
                title: "Understanding Reports",
                content: `
                    <div class="guide-full-content">
                        <p>Learn how to read and interpret your financial reports.</p>
                        
                        <h2>Available Reports</h2>
                        <ul>
                            <li><strong>Overview Dashboard:</strong> Quick snapshot of business health</li>
                            <li><strong>Sales Reports:</strong> Daily, weekly, monthly sales trends</li>
                            <li><strong>Expense Reports:</strong> Category breakdown and spending patterns</li>
                            <li><strong>Profit & Loss:</strong> Revenue vs Expenses with net profit calculation</li>
                            <li><strong>Inventory Reports:</strong> Stock levels and portfolio value</li>
                        </ul>
                        
                        <h2>Key Metrics to Track</h2>
                        <div class="step">
                            <h4>Revenue:</h4>
                            <p>Total income from sales before expenses</p>
                        </div>
                        <div class="step">
                            <h4>Expenses:</h4>
                            <p>Total business costs and operational expenses</p>
                        </div>
                        <div class="step">
                            <h4>Net Profit:</h4>
                            <p>Revenue minus Expenses - your actual profit</p>
                        </div>
                        <div class="step">
                            <h4>Profit Margin:</h4>
                            <p>Net Profit divided by Revenue - shows efficiency</p>
                        </div>
                        
                        <h2>Reading Charts</h2>
                        <ul>
                            <li><strong>Revenue vs Expenses Chart:</strong> Line chart showing daily trends</li>
                            <li><strong>Category Breakdown:</strong> Doughnut chart showing sales by category</li>
                            <li><strong>Monthly Performance:</strong> Bar chart comparing sales and expenses by month</li>
                        </ul>
                        
                        <h2>Exporting Reports</h2>
                        <p>Export reports in multiple formats:</p>
                        <ul>
                            <li><strong>PDF:</strong> For printing and sharing</li>
                            <li><strong>Excel:</strong> For analysis and manipulation</li>
                            <li><strong>CSV:</strong> For import into other applications</li>
                        </ul>
                        
                        <div class="tip">
                            <strong>💡 Pro Tip:</strong> Use the period filters (Today, Week, Month, Year) to analyze different timeframes!
                        </div>
                    </div>
                `
            },
            6: {
                title: "Setting Up Budget",
                content: `
                    <div class="guide-full-content">
                        <p>Learn how to create and track your monthly budget goals.</p>
                        
                        <h2>Setting a Budget</h2>
                        <div class="step">
                            <h4>Step 1:</h4>
                            <p>Go to Budget Planning page.</p>
                        </div>
                        <div class="step">
                            <h4>Step 2:</h4>
                            <p>Select the month you want to budget for.</p>
                        </div>
                        <div class="step">
                            <h4>Step 3:</h4>
                            <p>Enter your monthly Sales Target.</p>
                        </div>
                        <div class="step">
                            <h4>Step 4:</h4>
                            <p>Enter your monthly Expense Limit.</p>
                        </div>
                        <div class="step">
                            <h4>Step 5:</h4>
                            <p>Click "Save Budget" to set your goals.</p>
                        </div>
                        
                        <h2>Tracking Performance</h2>
                        <p>Once you set a budget, the system tracks:</p>
                        <ul>
                            <li><strong>Sales Achievement:</strong> Percentage of sales target reached</li>
                            <li><strong>Expense Usage:</strong> Percentage of expense limit used</li>
                            <li><strong>Remaining:</strong> Amount left in budget</li>
                            <li><strong>Warnings:</strong> Alerts when approaching or exceeding limits</li>
                        </ul>
                        
                        <h2>Budget Alerts</h2>
                        <p>You'll receive warnings when:</p>
                        <ul>
                            <li>Expenses reach 80% of limit - Yellow warning</li>
                            <li>Expenses exceed limit - Red warning</li>
                            <li>Sales are behind schedule</li>
                        </ul>
                        
                        <h2>Quick Adjust</h2>
                        <p>Admins can quickly adjust budgets by percentage:</p>
                        <ul>
                            <li>Increase both sales and expenses</li>
                            <li>Decrease both sales and expenses</li>
                            <li>Increase sales only</li>
                            <li>Decrease expenses only</li>
                        </ul>
                        
                        <h2>Budget History</h2>
                        <p>View past budgets to compare performance month-to-month.</p>
                        
                        <div class="tip">
                            <strong>💡 Pro Tip:</strong> Keep expenses below 80% of your limit to maintain a healthy profit margin!
                        </div>
                    </div>
                `
            }
        };

        function openGuide(guideId) {
            const guide = guides[guideId];
            if (guide) {
                document.getElementById('modalTitle').innerHTML = guide.title;
                document.getElementById('modalBody').innerHTML = guide.content;
                document.getElementById('guideModal').style.display = 'flex';
            }
        }

        function closeModal() {
            document.getElementById('guideModal').style.display = 'none';
        }

        function printGuide() {
            const printContent = document.getElementById('modalBody').innerHTML;
            const originalTitle = document.title;
            document.title = document.getElementById('modalTitle').innerHTML;
            
            const printWindow = window.open('', '_blank');
            printWindow.document.write(`
                <html>
                <head>
                    <title>${document.title}</title>
                    <style>
                        body {
                            font-family: 'Plus Jakarta Sans', 'Inter', sans-serif;
                            padding: 40px;
                            max-width: 800px;
                            margin: 0 auto;
                            line-height: 1.6;
                        }
                        h1 {
                            color: #06B6D4;
                            font-family: 'Enriqueta', serif;
                        }
                        .guide-full-content h2 {
                            color: #0f172a;
                            margin-top: 20px;
                        }
                        .guide-full-content .step {
                            background: #f8fafc;
                            padding: 10px 15px;
                            margin: 10px 0;
                            border-left: 4px solid #06B6D4;
                        }
                        .guide-full-content .tip {
                            background: #cffafe;
                            padding: 10px 15px;
                            border-radius: 8px;
                        }
                        @media print {
                            body {
                                padding: 20px;
                            }
                        }
                    </style>
                </head>
                <body>
                    <h1>${document.getElementById('modalTitle').innerHTML}</h1>
                    ${printContent}
                    <hr>
                    <p style="color: #64748b; font-size: 12px;">Printed from HAUccountant on ${new Date().toLocaleDateString()}</p>
                </body>
                </html>
            `);
            printWindow.document.close();
            printWindow.print();
            document.title = originalTitle;
        }

        // Close modal on escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeModal();
            }
        });

        // Close modal on outside click
        window.onclick = function(event) {
            const modal = document.getElementById('guideModal');
            if (event.target === modal) {
                closeModal();
            }
        }
    </script>
</body>
</html>