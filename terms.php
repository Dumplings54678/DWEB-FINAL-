<?php
session_start();
// Public page - no redirect needed
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terms of Service - PLANORA</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Enriqueta:wght@400;600;700&display=swap" rel="stylesheet">
    
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- AOS Animation -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    
    <style>
        /* Copy all styles from privacy.php - same design */
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

        .info-card {
            background: #f8fafc;
            border-radius: 12px;
            padding: 20px;
            margin: 20px 0;
            border-left: 4px solid #06B6D4;
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
    </style>
</head>
<body>
    <!-- Navigation (same as privacy.php) -->
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
            <h1 data-aos="fade-up">Terms of Service</h1>
            <p data-aos="fade-up" data-aos-delay="100">Terms and conditions for using PLANORA</p>
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
                            <li><a href="#agreement"><i class="fas fa-chevron-right"></i> Agreement to Terms</a></li>
                            <li><a href="#eligibility"><i class="fas fa-chevron-right"></i> Eligibility</a></li>
                            <li><a href="#accounts"><i class="fas fa-chevron-right"></i> User Accounts</a></li>
                            <li><a href="#subscriptions"><i class="fas fa-chevron-right"></i> Subscriptions & Payments</a></li>
                            <li><a href="#content"><i class="fas fa-chevron-right"></i> User Content</a></li>
                            <li><a href="#prohibited"><i class="fas fa-chevron-right"></i> Prohibited Activities</a></li>
                            <li><a href="#intellectual"><i class="fas fa-chevron-right"></i> Intellectual Property</a></li>
                            <li><a href="#termination"><i class="fas fa-chevron-right"></i> Termination</a></li>
                            <li><a href="#disclaimers"><i class="fas fa-chevron-right"></i> Disclaimers</a></li>
                            <li><a href="#limitation"><i class="fas fa-chevron-right"></i> Limitation of Liability</a></li>
                            <li><a href="#indemnification"><i class="fas fa-chevron-right"></i> Indemnification</a></li>
                            <li><a href="#governing"><i class="fas fa-chevron-right"></i> Governing Law</a></li>
                            <li><a href="#changes"><i class="fas fa-chevron-right"></i> Changes to Terms</a></li>
                            <li><a href="#contact"><i class="fas fa-chevron-right"></i> Contact Us</a></li>
                        </ul>
                    </div>

                    <div class="sidebar-card" style="margin-top: 20px;">
                        <h3 class="sidebar-title">Related</h3>
                        <ul class="sidebar-nav">
                            <li><a href="privacy.php"><i class="fas fa-lock"></i> Privacy Policy</a></li>
                            <li><a href="security.php"><i class="fas fa-shield-alt"></i> Security</a></li>
                            <li><a href="compliance.php"><i class="fas fa-check-circle"></i> Compliance</a></li>
                            <li><a href="cookies.php"><i class="fas fa-cookie-bite"></i> Cookie Policy</a></li>
                        </ul>
                    </div>
                </aside>

                <!-- Main Article -->
                <main class="main-article" data-aos="fade-left">
                    <section id="agreement" class="article-section">
                        <h2>1. Agreement to Terms</h2>
                        <p>These Terms of Service constitute a legally binding agreement made between you, whether personally or on behalf of an entity ("you") and PLANORA ("we," "us," or "our"), concerning your access to and use of the PLANORA website and accounting platform.</p>
                        
                        <p>By accessing or using the Services, you agree that you have read, understood, and agree to be bound by all of these Terms of Service. IF YOU DO NOT AGREE WITH ALL OF THESE TERMS OF SERVICE, THEN YOU ARE EXPRESSLY PROHIBITED FROM USING THE SERVICES AND YOU MUST DISCONTINUE USE IMMEDIATELY.</p>

                        <div class="info-card">
                            <p><strong>Supplemental terms and conditions</strong> or documents that may be posted on the Services from time to time are hereby expressly incorporated herein by reference. We reserve the right, in our sole discretion, to make changes or modifications to these Terms of Service at any time.</p>
                        </div>
                    </section>

                    <section id="eligibility" class="article-section">
                        <h2>2. Eligibility</h2>
                        <p>By using the Services, you represent and warrant that:</p>
                        <ul>
                            <li>You are at least 18 years of age and have the legal capacity to enter into a binding contract.</li>
                            <li>You are not a person barred from using the Services under the laws of the Philippines or any other applicable jurisdiction.</li>
                            <li>You are not located in a country that is subject to a Philippines government embargo.</li>
                            <li>You will comply with these Terms and all applicable local, national, and international laws and regulations.</li>
                            <li>If you are using the Services on behalf of a business, that business accepts these terms, and you have the authority to bind that business to these terms.</li>
                        </ul>
                    </section>

                    <section id="accounts" class="article-section">
                        <h2>3. User Accounts</h2>
                        <p>To access certain features of the Services, you may be required to register for an account. You agree to provide accurate, current, and complete information during the registration process and to update such information to keep it accurate, current, and complete.</p>

                        <h3>Account Responsibilities</h3>
                        <ul>
                            <li>You are responsible for safeguarding your password and for all activities that occur under your account.</li>
                            <li>You agree to notify us immediately of any unauthorized use of your account or any other breach of security.</li>
                            <li>We reserve the right to disable any user account at any time in our sole discretion for any or no reason.</li>
                            <li>You may not transfer your account to anyone else without our prior written permission.</li>
                        </ul>

                        <div class="highlight-box">
                            <h4>Multi-User Accounts</h4>
                            <p>If you create an account for a business or organization, you represent and warrant that you are authorized to grant each user access to the account. The primary account holder is responsible for all activities conducted through the account, including activities of authorized users.</p>
                        </div>
                    </section>

                    <section id="subscriptions" class="article-section">
                        <h2>4. Subscriptions & Payments</h2>
                        
                        <h3>Subscription Plans</h3>
                        <p>We offer various subscription plans for access to our Services. The features and pricing for each plan are described on our Pricing page. We reserve the right to modify subscription plans and pricing at any time upon notice to you.</p>

                        <h3>Billing and Payments</h3>
                        <ul>
                            <li><strong>Fees:</strong> You agree to pay all fees applicable to your selected subscription plan.</li>
                            <li><strong>Payment Method:</strong> You must provide a valid payment method at the time of purchase.</li>
                            <li><strong>Auto-Renewal:</strong> Subscriptions automatically renew at the end of each billing cycle unless canceled.</li>
                            <li><strong>Taxes:</strong> You are responsible for all taxes associated with your subscription.</li>
                            <li><strong>Refunds:</strong> All fees are non-refundable except as required by law or as expressly stated in these Terms.</li>
                        </ul>

                        <h3>Cancellation</h3>
                        <p>You may cancel your subscription at any time through your account settings. Cancellation will be effective at the end of the current billing period. No refunds will be issued for partial billing periods.</p>

                        <div class="info-card">
                            <p><strong>Free Trial:</strong> We may offer free trials for certain subscription plans. If you do not cancel before the end of the trial period, you will be automatically charged the subscription fee for the next billing cycle.</p>
                        </div>
                    </section>

                    <section id="content" class="article-section">
                        <h2>5. User Content</h2>
                        
                        <h3>Your Content</h3>
                        <p>Our Services allow you to upload, store, and manage financial data, business information, and other content ("User Content"). You retain all ownership rights to your User Content.</p>

                        <h3>License to Us</h3>
                        <p>By submitting User Content to the Services, you grant us a worldwide, non-exclusive, royalty-free license to use, reproduce, modify, adapt, publish, and display such User Content solely for the purpose of providing, maintaining, and improving the Services.</p>

                        <h3>Data Security</h3>
                        <p>We implement security measures to protect your User Content. However, no method of transmission over the Internet or electronic storage is 100% secure. We cannot guarantee absolute security of your data.</p>

                        <h3>Data Deletion</h3>
                        <p>Upon termination of your account, we will delete your User Content from our active servers within 30 days. Backup copies may persist for a reasonable period for disaster recovery purposes.</p>
                    </section>

                    <section id="prohibited" class="article-section">
                        <h2>6. Prohibited Activities</h2>
                        <p>You may not access or use the Services for any purpose other than that for which we make the Services available. The Services may not be used in connection with any commercial endeavors except those that are specifically endorsed or approved by us.</p>

                        <p>As a user of the Services, you agree not to:</p>
                        <ul>
                            <li>Systematically retrieve data or other content from the Services to create or compile a collection, database, or directory.</li>
                            <li>Trick, defraud, or mislead us or other users, especially in any attempt to learn sensitive account information.</li>
                            <li>Circumvent, disable, or otherwise interfere with security-related features of the Services.</li>
                            <li>Disparage, tarnish, or otherwise harm, in our opinion, us and/or the Services.</li>
                            <li>Use any information obtained from the Services in order to harass, abuse, or harm another person.</li>
                            <li>Make improper use of our support services or submit false reports of abuse or misconduct.</li>
                            <li>Use the Services in a manner inconsistent with any applicable laws or regulations.</li>
                            <li>Attempt to decompile or reverse engineer any software contained on the Services.</li>
                            <li>Interfere with, disrupt, or create an undue burden on the Services or the networks or services connected to the Services.</li>
                            <li>Harass, annoy, intimidate, or threaten any of our employees or agents.</li>
                            <li>Attempt to bypass any measures of the Services designed to prevent or restrict access.</li>
                            <li>Copy or adapt the Services' software, including but not limited to Flash, PHP, HTML, JavaScript, or other code.</li>
                            <li>Use the Services to store or transmit malware, viruses, or other harmful code.</li>
                            <li>Use the Services to engage in any illegal activity or to promote illegal activities.</li>
                        </ul>
                    </section>

                    <section id="intellectual" class="article-section">
                        <h2>7. Intellectual Property Rights</h2>
                        
                        <h3>Our Intellectual Property</h3>
                        <p>The Services, including their entire contents, features, and functionality, are owned by PLANORA and are protected by copyright, trademark, and other intellectual property laws. You may not reproduce, distribute, modify, create derivative works of, publicly display, publicly perform, republish, download, store, or transmit any of the material on our Services without our prior written consent.</p>

                        <h3>Trademarks</h3>
                        <p>The PLANORA name, logo, and all related names, logos, product and service names, designs, and slogans are trademarks of PLANORA. You may not use such marks without our prior written permission.</p>

                        <h3>Feedback</h3>
                        <p>If you provide us with any feedback or suggestions regarding the Services, you hereby assign to us all rights in such feedback and agree that we shall have the right to use such feedback and related information in any manner we deem appropriate.</p>
                    </section>

                    <section id="termination" class="article-section">
                        <h2>8. Termination</h2>
                        <p>We may terminate or suspend your account and bar access to the Services immediately, without prior notice or liability, under our sole discretion, for any reason whatsoever and without limitation, including but not limited to a breach of the Terms.</p>

                        <p>If you wish to terminate your account, you may simply discontinue using the Services or contact us to request account deletion.</p>

                        <p>All provisions of the Terms which by their nature should survive termination shall survive termination, including, without limitation, ownership provisions, warranty disclaimers, indemnity, and limitations of liability.</p>
                    </section>

                    <section id="disclaimers" class="article-section">
                        <h2>9. Disclaimers</h2>
                        
                        <div class="highlight-box">
                            <p><strong>THE SERVICES ARE PROVIDED ON AN "AS IS" AND "AS AVAILABLE" BASIS. WE MAKE NO REPRESENTATIONS OR WARRANTIES OF ANY KIND, EXPRESS OR IMPLIED, REGARDING THE OPERATION OR AVAILABILITY OF THE SERVICES, OR THE INFORMATION, CONTENT, AND MATERIALS INCLUDED THEREIN.</strong></p>
                        </div>

                        <p>To the fullest extent permitted by law, we disclaim all warranties, express or implied, including but not limited to implied warranties of merchantability, fitness for a particular purpose, and non-infringement.</p>

                        <p>We do not warrant that the Services will be uninterrupted or error-free, that defects will be corrected, or that the Services or the server that makes them available are free of viruses or other harmful components.</p>

                        <p>We do not warrant or make any representations regarding the use or the results of the use of the Services in terms of their correctness, accuracy, reliability, or otherwise.</p>
                    </section>

                    <section id="limitation" class="article-section">
                        <h2>10. Limitation of Liability</h2>
                        
                        <p>To the fullest extent permitted by applicable law, in no event shall PLANORA, its affiliates, officers, directors, employees, agents, suppliers, or licensors be liable for any indirect, incidental, special, consequential, or punitive damages, including without limitation, loss of profits, data, use, goodwill, or other intangible losses, resulting from:</p>
                        
                        <ul>
                            <li>Your use or inability to use the Services.</li>
                            <li>Any conduct or content of any third party on the Services.</li>
                            <li>Any content obtained from the Services.</li>
                            <li>Unauthorized access, use, or alteration of your transmissions or content.</li>
                        </ul>

                        <p>In no event shall our total liability to you for all claims exceed the amount you paid to us during the twelve (12) months prior to the event giving rise to the liability.</p>

                        <div class="info-card">
                            <p><strong>Jurisdictions that do not allow the exclusion or limitation of liability for consequential or incidental damages</strong> may not be fully subject to these limitations. In such jurisdictions, our liability shall be limited to the maximum extent permitted by law.</p>
                        </div>
                    </section>

                    <section id="indemnification" class="article-section">
                        <h2>11. Indemnification</h2>
                        <p>You agree to defend, indemnify, and hold harmless PLANORA and its employees, contractors, and agents from and against any and all claims, damages, obligations, losses, liabilities, costs or debt, and expenses (including but not limited to attorney's fees) arising from:</p>
                        
                        <ul>
                            <li>Your use of and access to the Services.</li>
                            <li>Your violation of any term of these Terms.</li>
                            <li>Your violation of any third-party right, including without limitation any copyright, property, or privacy right.</li>
                            <li>Any claim that your User Content caused damage to a third party.</li>
                        </ul>
                    </section>

                    <section id="governing" class="article-section">
                        <h2>12. Governing Law</h2>
                        <p>These Terms shall be governed and construed in accordance with the laws of the Republic of the Philippines, without regard to its conflict of law provisions.</p>

                        <p>Any legal suit, action, or proceeding arising out of or related to these Terms or the Services shall be instituted exclusively in the courts of Makati City, Philippines. You waive any and all objections to the exercise of jurisdiction over you by such courts and to venue in such courts.</p>
                    </section>

                    <section id="changes" class="article-section">
                        <h2>13. Changes to Terms</h2>
                        <p>We reserve the right, at our sole discretion, to modify or replace these Terms at any time. If a revision is material, we will try to provide at least 30 days' notice prior to any new terms taking effect. What constitutes a material change will be determined at our sole discretion.</p>

                        <p>By continuing to access or use our Services after those revisions become effective, you agree to be bound by the revised terms. If you do not agree to the new terms, please stop using the Services.</p>
                    </section>

                    <section id="contact" class="article-section">
                        <h2>14. Contact Us</h2>
                        <p>If you have any questions about these Terms, please contact us:</p>

                        <div class="highlight-box">
                            <p style="margin-bottom: 5px;"><strong>PLANORA Legal Department</strong></p>
                            <p style="margin-bottom: 5px;">123 Business District</p>
                            <p style="margin-bottom: 5px;">Makati City, 1200</p>
                            <p style="margin-bottom: 5px;">Philippines</p>
                            <p style="margin-bottom: 0;">Email: legal@PLANORA.com</p>
                        </div>
                    </section>
                </main>
            </div>
        </div>
    </section>

    <!-- Footer (same as privacy.php) -->
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