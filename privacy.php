<?php
session_start();
// If already logged in, keep the session but show public page
// No redirect needed - this is a public page
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Privacy Policy - PLANORA</title>
    
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
            padding: 140px 0 60px;
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
        }

        .last-updated {
            margin-top: 20px;
            font-size: 14px;
            opacity: 0.8;
            display: inline-block;
            background: rgba(255,255,255,0.2);
            padding: 8px 20px;
            border-radius: 40px;
            backdrop-filter: blur(5px);
        }

        /* Container */
        .container {
            max-width: 1280px;
            margin: 0 auto;
            padding: 0 24px;
        }

        /* Main Content */
        .legal-content {
            padding: 80px 0;
            background: #f8fafc;
        }

        .content-grid {
            display: grid;
            grid-template-columns: 280px 1fr;
            gap: 40px;
        }

        /* Sidebar */
        .content-sidebar {
            position: sticky;
            top: 100px;
            height: fit-content;
        }

        .sidebar-card {
            background: white;
            border-radius: 20px;
            padding: 30px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 20px rgba(0,0,0,0.02);
        }

        .sidebar-title {
            font-family: 'Enriqueta', serif;
            font-size: 18px;
            color: #0f172a;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #06B6D4;
        }

        .sidebar-nav {
            list-style: none;
        }

        .sidebar-nav li {
            margin-bottom: 12px;
        }

        .sidebar-nav a {
            color: #475569;
            text-decoration: none;
            font-size: 14px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .sidebar-nav a:hover {
            color: #06B6D4;
            transform: translateX(5px);
        }

        .sidebar-nav a i {
            font-size: 12px;
            color: #06B6D4;
            opacity: 0;
            transition: all 0.3s ease;
        }

        .sidebar-nav a:hover i {
            opacity: 1;
        }

        /* Main Article */
        .main-article {
            background: white;
            border-radius: 30px;
            padding: 50px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 10px 40px rgba(0,0,0,0.02);
        }

        .article-section {
            margin-bottom: 50px;
            scroll-margin-top: 100px;
        }

        .article-section:last-child {
            margin-bottom: 0;
        }

        .article-section h2 {
            font-family: 'Enriqueta', serif;
            font-size: 28px;
            color: #0f172a;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #cffafe;
        }

        .article-section h3 {
            font-size: 20px;
            color: #1e293b;
            margin: 25px 0 15px;
        }

        .article-section p {
            color: #475569;
            line-height: 1.8;
            margin-bottom: 20px;
            font-size: 16px;
        }

        .article-section ul, 
        .article-section ol {
            margin: 20px 0;
            padding-left: 30px;
            color: #475569;
        }

        .article-section li {
            margin-bottom: 10px;
        }

        .highlight-box {
            background: linear-gradient(135deg, #cffafe, #ccfbf1);
            border-radius: 16px;
            padding: 30px;
            margin: 30px 0;
            border: 1px solid rgba(6, 182, 212, 0.2);
        }

        .highlight-box h4 {
            color: #0f172a;
            margin-bottom: 15px;
            font-size: 18px;
        }

        .highlight-box p {
            margin-bottom: 0;
        }

        .info-card {
            background: #f8fafc;
            border-radius: 12px;
            padding: 20px;
            margin: 20px 0;
            border-left: 4px solid #06B6D4;
        }

        .info-card p {
            margin-bottom: 0;
        }

        /* Table */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin: 30px 0;
        }

        .data-table th {
            background: #f8fafc;
            padding: 12px 16px;
            text-align: left;
            font-weight: 600;
            color: #0f172a;
            border-bottom: 2px solid #e2e8f0;
        }

        .data-table td {
            padding: 12px 16px;
            border-bottom: 1px solid #e2e8f0;
            color: #475569;
        }

        .data-table tr:hover td {
            background: #f8fafc;
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

        /* Responsive */
        @media screen and (max-width: 1024px) {
            .content-grid {
                grid-template-columns: 1fr;
            }
            
            .content-sidebar {
                position: static;
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
            
            .main-article {
                padding: 30px 20px;
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

        @media screen and (max-width: 480px) {
            .page-header h1 {
                font-size: 28px;
            }
            
            .article-section h2 {
                font-size: 24px;
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
            <h1 data-aos="fade-up">Privacy Policy</h1>
            <p data-aos="fade-up" data-aos-delay="100">How we collect, use, and protect your data</p>
            <div class="last-updated" data-aos="fade-up" data-aos-delay="150">
                <i class="far fa-calendar-alt"></i> Last Updated: March 15, 2026
            </div>
        </div>
    </section>

    <!-- Main Content -->
    <section class="legal-content">
        <div class="container">
            <div class="content-grid">
                <!-- Sidebar Navigation -->
                <aside class="content-sidebar" data-aos="fade-right">
                    <div class="sidebar-card">
                        <h3 class="sidebar-title">Contents</h3>
                        <ul class="sidebar-nav">
                            <li><a href="#introduction"><i class="fas fa-chevron-right"></i> Introduction</a></li>
                            <li><a href="#information-we-collect"><i class="fas fa-chevron-right"></i> Information We Collect</a></li>
                            <li><a href="#how-we-use"><i class="fas fa-chevron-right"></i> How We Use Your Information</a></li>
                            <li><a href="#data-sharing"><i class="fas fa-chevron-right"></i> Data Sharing & Disclosure</a></li>
                            <li><a href="#data-security"><i class="fas fa-chevron-right"></i> Data Security</a></li>
                            <li><a href="#your-rights"><i class="fas fa-chevron-right"></i> Your Rights</a></li>
                            <li><a href="#cookies"><i class="fas fa-chevron-right"></i> Cookies & Tracking</a></li>
                            <li><a href="#children"><i class="fas fa-chevron-right"></i> Children's Privacy</a></li>
                            <li><a href="#changes"><i class="fas fa-chevron-right"></i> Changes to Policy</a></li>
                            <li><a href="#contact"><i class="fas fa-chevron-right"></i> Contact Us</a></li>
                        </ul>
                    </div>

                    <div class="sidebar-card" style="margin-top: 20px;">
                        <h3 class="sidebar-title">Related</h3>
                        <ul class="sidebar-nav">
                            <li><a href="terms.php"><i class="fas fa-file-contract"></i> Terms of Service</a></li>
                            <li><a href="security.php"><i class="fas fa-shield-alt"></i> Security</a></li>
                            <li><a href="compliance.php"><i class="fas fa-check-circle"></i> Compliance</a></li>
                            <li><a href="cookies.php"><i class="fas fa-cookie-bite"></i> Cookie Policy</a></li>
                        </ul>
                    </div>
                </aside>

                <!-- Main Article -->
                <main class="main-article" data-aos="fade-left">
                    <section id="introduction" class="article-section">
                        <h2>Introduction</h2>
                        <p>At PLANORA, we take your privacy seriously. This Privacy Policy explains how we collect, use, disclose, and safeguard your information when you use our accounting and business management platform. Please read this privacy policy carefully. If you do not agree with the terms of this privacy policy, please do not access the site.</p>
                        
                        <div class="info-card">
                            <p><strong>Our Commitment:</strong> We are committed to protecting your personal information and your right to privacy. If you have any questions or concerns about this policy, or our practices with regards to your personal information, please contact us at privacy@PLANORA.com.</p>
                        </div>
                    </section>

                    <section id="information-we-collect" class="article-section">
                        <h2>Information We Collect</h2>
                        <p>We collect personal information that you voluntarily provide to us when you register on the Services, express an interest in obtaining information about us or our products and services, when you participate in activities on the Services, or otherwise when you contact us.</p>

                        <h3>Personal Information You Disclose</h3>
                        <p>The personal information we collect depends on the context of your interactions with us and the Services, the choices you make, and the products and features you use. The personal information we collect may include the following:</p>

                        <ul>
                            <li><strong>Business Information:</strong> Business name, business type, business address, tax identification number</li>
                            <li><strong>Personal Identifiers:</strong> Name, email address, phone number, job title</li>
                            <li><strong>Financial Data:</strong> Sales data, expense data, inventory information, financial reports</li>
                            <li><strong>Payment Information:</strong> Credit card details, billing address (processed by secure third-party payment processors)</li>
                            <li><strong>Account Credentials:</strong> Username, password, security questions</li>
                        </ul>

                        <h3>Information Automatically Collected</h3>
                        <p>We automatically collect certain information when you visit, use, or navigate the Services. This information does not reveal your specific identity but may include device and usage information, such as your IP address, browser and device characteristics, operating system, language preferences, referring URLs, device name, country, location, information about how and when you use our Services, and other technical information.</p>

                        <div class="highlight-box">
                            <h4>We Collect This Information To:</h4>
                            <ul style="margin-bottom: 0;">
                                <li>Operate and improve our services</li>
                                <li>Personalize your experience</li>
                                <li>Communicate with you about updates and offers</li>
                                <li>Prevent fraud and enhance security</li>
                                <li>Comply with legal obligations</li>
                            </ul>
                        </div>
                    </section>

                    <section id="how-we-use" class="article-section">
                        <h2>How We Use Your Information</h2>
                        <p>We use personal information collected via our Services for a variety of business purposes described below. We process your personal information for these purposes in reliance on our legitimate business interests, in order to enter into or perform a contract with you, with your consent, and/or for compliance with our legal obligations.</p>

                        <h3>We use the information we collect or receive:</h3>
                        <ul>
                            <li><strong>To facilitate account creation and login process.</strong></li>
                            <li><strong>To provide and maintain our Services.</strong> Including to monitor the usage of our Services.</li>
                            <li><strong>To manage your account.</strong> To manage your registration as a user of the Services.</li>
                            <li><strong>To contact you.</strong> To contact you by email, telephone calls, SMS, or other equivalent forms of electronic communication regarding updates or informative communications related to the functionalities, products, or contracted services.</li>
                            <li><strong>To provide you with news and special offers.</strong> To send you information about products, services, and events we offer that are similar to those that you have already purchased or enquired about unless you have opted not to receive such information.</li>
                            <li><strong>To improve our Services.</strong> To understand how you use our Services and to improve your experience.</li>
                            <li><strong>To protect our Services.</strong> To prevent fraud and ensure the security of our platform.</li>
                            <li><strong>To comply with legal obligations.</strong> To comply with applicable laws and regulations.</li>
                        </ul>
                    </section>

                    <section id="data-sharing" class="article-section">
                        <h2>Data Sharing & Disclosure</h2>
                        <p>We may process or share your data that we hold based on the following legal basis:</p>

                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Basis</th>
                                    <th>Description</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><strong>Consent</strong></td>
                                    <td>We may process your data if you have given us specific consent to use your personal information for a specific purpose.</td>
                                </tr>
                                <tr>
                                    <td><strong>Legitimate Interests</strong></td>
                                    <td>We may process your data when it is reasonably necessary to achieve our legitimate business interests.</td>
                                </tr>
                                <tr>
                                    <td><strong>Performance of a Contract</strong></td>
                                    <td>Where we have entered into a contract with you, we may process your personal information to fulfill the terms of our contract.</td>
                                </tr>
                                <tr>
                                    <td><strong>Legal Obligations</strong></td>
                                    <td>We may disclose your information where we are legally required to do so in order to comply with applicable law, governmental requests, a judicial proceeding, court order, or legal process.</td>
                                </tr>
                            </tbody>
                        </table>

                        <h3>We may share your information with:</h3>
                        <ul>
                            <li><strong>Service Providers:</strong> We may share your information with third party vendors, service providers, contractors, or agents who perform services for us or on our behalf and require access to such information to do that work.</li>
                            <li><strong>Business Transfers:</strong> We may share or transfer your information in connection with, or during negotiations of, any merger, sale of company assets, financing, or acquisition of all or a portion of our business to another company.</li>
                            <li><strong>Affiliates:</strong> We may share your information with our affiliates, in which case we will require those affiliates to honor this privacy policy.</li>
                            <li><strong>Business Partners:</strong> We may share your information with our business partners to offer you certain products, services, or promotions.</li>
                        </ul>

                        <div class="info-card">
                            <p><i class="fas fa-shield-alt" style="color: #06B6D4; margin-right: 8px;"></i> <strong>We do not sell your personal information</strong> to third parties for their marketing purposes without your explicit consent.</p>
                        </div>
                    </section>

                    <section id="data-security" class="article-section">
                        <h2>Data Security</h2>
                        <p>We have implemented appropriate technical and organizational security measures designed to protect the security of any personal information we process. However, despite our safeguards and efforts to secure your information, no electronic transmission over the Internet or information storage technology can be guaranteed to be 100% secure, so we cannot promise or guarantee that hackers, cybercriminals, or other unauthorized third parties will not be able to defeat our security and improperly collect, access, steal, or modify your information.</p>

                        <div class="highlight-box">
                            <h4>Our Security Measures Include:</h4>
                            <ul style="margin-bottom: 0;">
                                <li>Encryption of data in transit and at rest using SSL/TLS protocols</li>
                                <li>Regular security audits and penetration testing</li>
                                <li>Multi-factor authentication for account access</li>
                                <li>Strict access controls and employee training</li>
                                <li>Automated backups and disaster recovery procedures</li>
                                <li>24/7 monitoring for suspicious activity</li>
                            </ul>
                        </div>
                    </section>

                    <section id="your-rights" class="article-section">
                        <h2>Your Privacy Rights</h2>
                        <p>Depending on your location, you may have certain rights regarding your personal information under applicable data protection laws. These may include the right to:</p>

                        <ul>
                            <li><strong>Access:</strong> Request access to your personal information and obtain a copy of it.</li>
                            <li><strong>Rectification:</strong> Request correction of inaccurate or incomplete personal information.</li>
                            <li><strong>Erasure:</strong> Request deletion of your personal information when certain conditions apply.</li>
                            <li><strong>Restriction:</strong> Request restriction of processing of your personal information.</li>
                            <li><strong>Data Portability:</strong> Request transfer of your personal information to another organization.</li>
                            <li><strong>Objection:</strong> Object to processing of your personal information.</li>
                            <li><strong>Withdraw Consent:</strong> Withdraw your consent at any time where we are relying on consent to process your personal information.</li>
                        </ul>

                        <div class="info-card">
                            <p><strong>To exercise any of these rights,</strong> please contact us at privacy@PLANORA.com. We will respond to your request within 30 days.</p>
                        </div>
                    </section>

                    <section id="cookies" class="article-section">
                        <h2>Cookies & Tracking Technologies</h2>
                        <p>We use cookies and similar tracking technologies (like web beacons and pixels) to access or store information. Specific information about how we use such technologies and how you can refuse certain cookies is set out in our <a href="cookies.php" style="color: #06B6D4;">Cookie Policy</a>.</p>

                        <p>We use cookies for the following purposes:</p>
                        <ul>
                            <li><strong>Essential Cookies:</strong> Required for the operation of our website and services.</li>
                            <li><strong>Analytical/Performance Cookies:</strong> Allow us to recognize and count the number of visitors and see how visitors move around our website.</li>
                            <li><strong>Functionality Cookies:</strong> Used to recognize you when you return to our website.</li>
                            <li><strong>Targeting Cookies:</strong> Record your visit to our website, the pages you have visited, and the links you have followed.</li>
                        </ul>
                    </section>

                    <section id="children" class="article-section">
                        <h2>Children's Privacy</h2>
                        <p>Our Services are not intended for use by children under the age of 18. We do not knowingly collect personal information from children under 18. If you become aware that a child has provided us with personal information, please contact us. If we become aware that we have collected personal information from children without verification of parental consent, we will take steps to remove that information from our servers.</p>
                    </section>

                    <section id="changes" class="article-section">
                        <h2>Changes to This Privacy Policy</h2>
                        <p>We may update this privacy policy from time to time. The updated version will be indicated by an updated "Revised" date and the updated version will be effective as soon as it is accessible. If we make material changes to this privacy policy, we may notify you either by prominently posting a notice of such changes or by directly sending you a notification. We encourage you to review this privacy policy frequently to be informed of how we are protecting your information.</p>
                    </section>

                    <section id="contact" class="article-section">
                        <h2>Contact Us</h2>
                        <p>If you have questions or comments about this policy, you may contact our Data Protection Officer (DPO) by email at privacy@PLANORA.com, or by post to:</p>

                        <div class="highlight-box">
                            <p style="margin-bottom: 5px;"><strong>PLANORA Privacy Team</strong></p>
                            <p style="margin-bottom: 5px;">123 Business District</p>
                            <p style="margin-bottom: 5px;">Makati City, 1200</p>
                            <p style="margin-bottom: 0;">Philippines</p>
                        </div>

                        <p>You can also contact us by phone at: <strong>+63 (02) 1234-5678</strong></p>
                    </section>
                </main>
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
        // Initialize AOS
        AOS.init({
            duration: 800,
            once: true,
            mirror: false
        });

        // Back to Top Button
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

        // Smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
    </script>
</body>
</html>