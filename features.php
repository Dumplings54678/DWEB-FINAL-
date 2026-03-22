<?php
session_start();
// Public page - no login required
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Features - PLANORA</title>
    
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

        /* Container */
        .container {
            max-width: 1280px;
            margin: 0 auto;
            padding: 0 24px;
        }

        /* Features Grid */
        .features-page {
            padding: 80px 0;
            background: #f8fafc;
        }

        .features-grid-large {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 30px;
            margin-bottom: 60px;
        }

        .feature-large-card {
            background: white;
            border-radius: 30px;
            padding: 40px 30px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 10px 40px rgba(0,0,0,0.02);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .feature-large-card:hover {
            transform: translateY(-10px);
            border-color: #06B6D4;
            box-shadow: 0 20px 60px rgba(6, 182, 212, 0.1);
        }

        .feature-large-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, #06B6D4, #14B8A6);
        }

        .feature-icon-large {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #cffafe, #ccfbf1);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 25px;
            transition: all 0.3s ease;
        }

        .feature-large-card:hover .feature-icon-large {
            background: linear-gradient(135deg, #06B6D4, #14B8A6);
        }

        .feature-icon-large i {
            font-size: 40px;
            color: #06B6D4;
            transition: all 0.3s ease;
        }

        .feature-large-card:hover .feature-icon-large i {
            color: white;
        }

        .feature-large-card h3 {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 15px;
            color: #0f172a;
        }

        .feature-large-card p {
            color: #475569;
            line-height: 1.7;
            margin-bottom: 20px;
        }

        .feature-link {
            color: #06B6D4;
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .feature-link:hover {
            gap: 12px;
        }

        /* Feature Categories */
        .feature-category {
            margin-bottom: 60px;
        }

        .category-title {
            font-family: 'Enriqueta', serif;
            font-size: 32px;
            color: #0f172a;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .category-title i {
            color: #06B6D4;
            font-size: 28px;
        }

        .category-title::after {
            content: '';
            flex: 1;
            height: 2px;
            background: linear-gradient(90deg, #06B6D4, transparent);
        }

        .feature-list {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }

        .feature-list-item {
            background: white;
            border-radius: 16px;
            padding: 25px;
            border: 1px solid #e2e8f0;
            display: flex;
            align-items: flex-start;
            gap: 20px;
            transition: all 0.3s ease;
        }

        .feature-list-item:hover {
            border-color: #06B6D4;
            box-shadow: 0 10px 30px rgba(6, 182, 212, 0.05);
        }

        .feature-list-icon {
            width: 50px;
            height: 50px;
            background: #cffafe;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .feature-list-icon i {
            font-size: 24px;
            color: #06B6D4;
        }

        .feature-list-content h4 {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 8px;
            color: #0f172a;
        }

        .feature-list-content p {
            color: #475569;
            font-size: 14px;
            line-height: 1.6;
            margin-bottom: 0;
        }

        /* CTA Section */
        .cta-section {
            background: linear-gradient(135deg, #0f172a, #1e293b);
            border-radius: 30px;
            padding: 60px;
            text-align: center;
            color: white;
            margin: 40px 0;
        }

        .cta-section h2 {
            font-family: 'Enriqueta', serif;
            font-size: 36px;
            margin-bottom: 20px;
        }

        .cta-section p {
            font-size: 18px;
            opacity: 0.9;
            margin-bottom: 30px;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }

        .cta-button {
            background: white;
            color: #0f172a;
            text-decoration: none;
            padding: 16px 40px;
            border-radius: 40px;
            font-weight: 700;
            font-size: 16px;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            border: 2px solid transparent;
        }

        .cta-button:hover {
            transform: translateY(-3px);
            box-shadow: 0 20px 30px rgba(0,0,0,0.2);
            background: transparent;
            color: white;
            border-color: white;
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

        /* Back to Top */
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

        /* Responsive */
        @media screen and (max-width: 1024px) {
            .features-grid-large {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .page-header h1 {
                font-size: 40px;
            }
        }

        @media screen and (max-width: 768px) {
            .nav-center {
                display: none;
            }
            
            .page-header {
                padding: 120px 0 40px;
            }
            
            .page-header h1 {
                font-size: 32px;
            }
            
            .features-grid-large {
                grid-template-columns: 1fr;
            }
            
            .feature-list {
                grid-template-columns: 1fr;
            }
            
            .cta-section {
                padding: 40px 20px;
            }
            
            .cta-section h2 {
                font-size: 28px;
            }
            
            .footer-grid {
                grid-template-columns: 1fr;
                gap: 30px;
            }
            
            .footer-bottom {
                flex-direction: column;
                gap: 20px;
                text-align: center;
            }
            
            .footer-links {
                flex-wrap: wrap;
                justify-content: center;
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
                <a href="login.php" class="signin-link">Sign In</a>
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
            <h1 data-aos="fade-up">Powerful Features</h1>
            <p data-aos="fade-up" data-aos-delay="100">Everything you need to manage your business finances in one place</p>
        </div>
    </section>

    <!-- Features Page Content -->
    <section class="features-page">
        <div class="container">
            <!-- Main Features Grid -->
            <div class="features-grid-large" data-aos="fade-up">
                <div class="feature-large-card">
                    <div class="feature-icon-large">
                        <i class="fas fa-chart-pie"></i>
                    </div>
                    <h3>Dashboard</h3>
                    <p>Get a complete overview of your business performance with real-time charts, KPIs, and insights.</p>
                    <a href="#" class="feature-link">Learn more <i class="fas fa-arrow-right"></i></a>
                </div>

                <div class="feature-large-card">
                    <div class="feature-icon-large">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <h3>Sales Management</h3>
                    <p>Track every sale with automatic receipt generation, tax calculations, and real-time inventory updates.</p>
                    <a href="#" class="feature-link">Learn more <i class="fas fa-arrow-right"></i></a>
                </div>

                <div class="feature-large-card">
                    <div class="feature-icon-large">
                        <i class="fas fa-receipt"></i>
                    </div>
                    <h3>Expense Tracking</h3>
                    <p>Categorize and monitor all expenses with budget alerts, monthly summaries, and receipt uploads.</p>
                    <a href="#" class="feature-link">Learn more <i class="fas fa-arrow-right"></i></a>
                </div>

                <div class="feature-large-card">
                    <div class="feature-icon-large">
                        <i class="fas fa-boxes"></i>
                    </div>
                    <h3>Inventory Control</h3>
                    <p>Real-time stock tracking with automatic deduction, low-stock alerts, and reorder management.</p>
                    <a href="#" class="feature-link">Learn more <i class="fas fa-arrow-right"></i></a>
                </div>

                <div class="feature-large-card">
                    <div class="feature-icon-large">
                        <i class="fas fa-file-alt"></i>
                    </div>
                    <h3>Reports & Analytics</h3>
                    <p>Generate comprehensive financial reports with visual charts, profit & loss statements, and tax summaries.</p>
                    <a href="#" class="feature-link">Learn more <i class="fas fa-arrow-right"></i></a>
                </div>

                <div class="feature-large-card">
                    <div class="feature-icon-large">
                        <i class="fas fa-wallet"></i>
                    </div>
                    <h3>Budget Planning</h3>
                    <p>Set monthly targets, track performance against goals, and receive alerts when approaching limits.</p>
                    <a href="#" class="feature-link">Learn more <i class="fas fa-arrow-right"></i></a>
                </div>
            </div>

            <!-- Category: Financial Management -->
            <div class="feature-category">
                <h2 class="category-title" data-aos="fade-right">
                    <i class="fas fa-coins"></i> Financial Management
                </h2>
                <div class="feature-list" data-aos="fade-up">
                    <div class="feature-list-item">
                        <div class="feature-list-icon">
                            <i class="fas fa-calculator"></i>
                        </div>
                        <div class="feature-list-content">
                            <h4>Profit & Loss Calculation</h4>
                            <p>Automatic calculation of revenue, expenses, and net profit with visual indicators.</p>
                        </div>
                    </div>
                    <div class="feature-list-item">
                        <div class="feature-list-icon">
                            <i class="fas fa-percent"></i>
                        </div>
                        <div class="feature-list-content">
                            <h4>Tax Computation</h4>
                            <p>Automatic VAT calculation with configurable rates and printable tax reports.</p>
                        </div>
                    </div>
                    <div class="feature-list-item">
                        <div class="feature-list-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <div class="feature-list-content">
                            <h4>Financial Dashboard</h4>
                            <p>Real-time view of your business health with color-coded status indicators.</p>
                        </div>
                    </div>
                    <div class="feature-list-item">
                        <div class="feature-list-icon">
                            <i class="fas fa-file-invoice"></i>
                        </div>
                        <div class="feature-list-content">
                            <h4>Export Reports</h4>
                            <p>Download reports in PDF, Excel, or CSV format for sharing with your accountant.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Category: Inventory & Stock -->
            <div class="feature-category">
                <h2 class="category-title" data-aos="fade-right">
                    <i class="fas fa-archive"></i> Inventory & Stock
                </h2>
                <div class="feature-list" data-aos="fade-up">
                    <div class="feature-list-item">
                        <div class="feature-list-icon">
                            <i class="fas fa-box-open"></i>
                        </div>
                        <div class="feature-list-content">
                            <h4>Real-time Stock Updates</h4>
                            <p>Inventory automatically updates after every sale or stock adjustment.</p>
                        </div>
                    </div>
                    <div class="feature-list-item">
                        <div class="feature-list-icon">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <div class="feature-list-content">
                            <h4>Low Stock Alerts</h4>
                            <p>Get notified when products are running low or out of stock.</p>
                        </div>
                    </div>
                    <div class="feature-list-item">
                        <div class="feature-list-icon">
                            <i class="fas fa-barcode"></i>
                        </div>
                        <div class="feature-list-content">
                            <h4>Barcode Support</h4>
                            <p>Scan and manage products with barcode integration for faster checkout.</p>
                        </div>
                    </div>
                    <div class="feature-list-item">
                        <div class="feature-list-icon">
                            <i class="fas fa-history"></i>
                        </div>
                        <div class="feature-list-content">
                            <h4>Stock History</h4>
                            <p>Track all stock movements with detailed history and adjustment logs.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Category: Multi-User & Security -->
            <div class="feature-category">
                <h2 class="category-title" data-aos="fade-right">
                    <i class="fas fa-users-cog"></i> Multi-User & Security
                </h2>
                <div class="feature-list" data-aos="fade-up">
                    <div class="feature-list-item">
                        <div class="feature-list-icon">
                            <i class="fas fa-user-tie"></i>
                        </div>
                        <div class="feature-list-content">
                            <h4>Role-Based Access</h4>
                            <p>Admin and staff roles with customizable permissions for each user.</p>
                        </div>
                    </div>
                    <div class="feature-list-item">
                        <div class="feature-list-icon">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <div class="feature-list-content">
                            <h4>Data Encryption</h4>
                            <p>All sensitive data encrypted with industry-standard security protocols.</p>
                        </div>
                    </div>
                    <div class="feature-list-item">
                        <div class="feature-list-icon">
                            <i class="fas fa-history"></i>
                        </div>
                        <div class="feature-list-content">
                            <h4>Activity Logs</h4>
                            <p>Complete audit trail of all user actions for accountability.</p>
                        </div>
                    </div>
                    <div class="feature-list-item">
                        <div class="feature-list-icon">
                            <i class="fas fa-cloud-upload-alt"></i>
                        </div>
                        <div class="feature-list-content">
                            <h4>Cloud Backup</h4>
                            <p>Automatic backups and manual export options for data security.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- CTA Section -->
            <div class="cta-section" data-aos="zoom-in">
                <h2>Ready to transform your business?</h2>
                <p>Join thousands of businesses using PLANORA to manage their finances.</p>
                <a href="register.php" class="cta-button">
                    Start Your Free Trial <i class="fas fa-arrow-right"></i>
                </a>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-column">
                    <h4>Product</h4>
                    <ul>
                        <li><a href="features.php"><i class="fas fa-chevron-right"></i> Features</a></li>
                    </ul>
                </div>
                
                <div class="footer-column">
                    <h4>Company</h4>
                    <ul>
                        <li><a href="about.php"><i class="fas fa-chevron-right"></i> About Us</a></li>
                        <li><a href="careers.php"><i class="fas fa-chevron-right"></i> Careers</a></li>
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
    </script>
</body>
</html>