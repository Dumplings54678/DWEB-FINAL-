<?php
session_start();
// Public page - no login required
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Press - PLANORA</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Enriqueta:wght@400;600;700&display=swap" rel="stylesheet">
    
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- AOS Animation -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    
    <style>
        /* Copy navigation and footer styles from previous files */
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

        /* Press Page Specific */
        .press-page {
            padding: 80px 0;
            background: #f8fafc;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 30px;
            margin-bottom: 60px;
        }

        .stat-card {
            background: white;
            border-radius: 20px;
            padding: 40px 20px;
            text-align: center;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 20px rgba(0,0,0,0.02);
        }

        .stat-number {
            font-size: 48px;
            font-weight: 800;
            background: linear-gradient(135deg, #06B6D4, #14B8A6);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 10px;
        }

        .stat-label {
            color: #64748b;
            font-size: 16px;
        }

        .press-kit {
            background: white;
            border-radius: 30px;
            padding: 50px;
            margin-bottom: 60px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 30px;
        }

        .press-kit h2 {
            font-family: 'Enriqueta', serif;
            font-size: 28px;
            margin-bottom: 10px;
        }

        .press-kit p {
            color: #64748b;
        }

        .press-kit-btn {
            background: #06B6D4;
            color: white;
            padding: 15px 30px;
            border-radius: 30px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .press-kit-btn:hover {
            background: #0891b2;
            transform: translateY(-2px);
        }

        .coverage-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 30px;
            margin-bottom: 60px;
        }

        .coverage-card {
            background: white;
            border-radius: 20px;
            padding: 30px;
            border: 1px solid #e2e8f0;
            transition: all 0.3s ease;
        }

        .coverage-card:hover {
            border-color: #06B6D4;
            transform: translateY(-5px);
        }

        .coverage-logo {
            font-size: 24px;
            font-weight: 700;
            color: #06B6D4;
            margin-bottom: 15px;
        }

        .coverage-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .coverage-date {
            color: #64748b;
            font-size: 13px;
            margin-bottom: 15px;
        }

        .coverage-link {
            color: #06B6D4;
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .releases-section {
            background: white;
            border-radius: 30px;
            padding: 50px;
            margin-bottom: 60px;
        }

        .release-item {
            padding: 30px 0;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            gap: 30px;
            flex-wrap: wrap;
        }

        .release-item:last-child {
            border-bottom: none;
        }

        .release-date {
            min-width: 100px;
            font-weight: 600;
            color: #06B6D4;
        }

        .release-content {
            flex: 1;
        }

        .release-content h3 {
            font-size: 20px;
            margin-bottom: 10px;
        }

        .release-content p {
            color: #64748b;
            margin-bottom: 15px;
        }

        .media-contacts {
            background: white;
            border-radius: 30px;
            padding: 50px;
        }

        .contacts-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 30px;
            margin-top: 30px;
        }

        .contact-card {
            text-align: center;
        }

        .contact-card i {
            font-size: 32px;
            color: #06B6D4;
            margin-bottom: 15px;
        }

        .contact-card h4 {
            font-size: 18px;
            margin-bottom: 5px;
        }

        .contact-card p {
            color: #64748b;
        }

        .contact-card a {
            color: #06B6D4;
            text-decoration: none;
            font-weight: 600;
        }

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
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .contacts-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media screen and (max-width: 768px) {
            .nav-center {
                display: none;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .coverage-grid {
                grid-template-columns: 1fr;
            }
            
            .contacts-grid {
                grid-template-columns: 1fr;
            }
            
            .press-kit {
                flex-direction: column;
                text-align: center;
            }
            
            .footer-grid {
                grid-template-columns: 1fr;
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
                <a href="pricing.php" class="nav-link">Pricing</a>
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
            <h1 data-aos="fade-up">Press Room</h1>
            <p data-aos="fade-up" data-aos-delay="100">Latest news and updates from PLANORA</p>
        </div>
    </section>

    <!-- Press Content -->
    <section class="press-page">
        <div class="container">
            <!-- Stats -->
            <div class="stats-grid" data-aos="fade-up">
                <div class="stat-card">
                    <div class="stat-number">50K+</div>
                    <div class="stat-label">Businesses Served</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">₱2B+</div>
                    <div class="stat-label">Transactions Processed</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">100+</div>
                    <div class="stat-label">Team Members</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">2022</div>
                    <div class="stat-label">Founded</div>
                </div>
            </div>

            <!-- Press Kit -->
            <div class="press-kit" data-aos="fade-up">
                <div>
                    <h2>Press Kit</h2>
                    <p>Download logos, screenshots, and brand assets</p>
                </div>
                <a href="#" class="press-kit-btn" onclick="alert('Press kit download started'); return false;">
                    <i class="fas fa-download"></i> Download Press Kit
                </a>
            </div>

            <!-- Media Coverage -->
            <h2 style="font-family: 'Enriqueta', serif; font-size: 32px; margin-bottom: 30px;" data-aos="fade-right">Media Coverage</h2>
            
            <div class="coverage-grid" data-aos="fade-up">
                <div class="coverage-card">
                    <div class="coverage-logo">TechCrunch</div>
                    <div class="coverage-title">PLANORA Raises $5M to Help Filipino Small Businesses</div>
                    <div class="coverage-date">March 10, 2026</div>
                    <a href="#" class="coverage-link">Read Article <i class="fas fa-arrow-right"></i></a>
                </div>

                <div class="coverage-card">
                    <div class="coverage-logo">Manila Bulletin</div>
                    <div class="coverage-title">Local Accounting Startup Sees 300% Growth in 2025</div>
                    <div class="coverage-date">February 28, 2026</div>
                    <a href="#" class="coverage-link">Read Article <i class="fas fa-arrow-right"></i></a>
                </div>

                <div class="coverage-card">
                    <div class="coverage-logo">Business World</div>
                    <div class="coverage-title">How PLANORA is Digitizing SME Finance</div>
                    <div class="coverage-date">January 15, 2026</div>
                    <a href="#" class="coverage-link">Read Article <i class="fas fa-arrow-right"></i></a>
                </div>

                <div class="coverage-card">
                    <div class="coverage-logo">Philippine Star</div>
                    <div class="coverage-title">Top 10 Filipino Startups to Watch in 2026</div>
                    <div class="coverage-date">January 5, 2026</div>
                    <a href="#" class="coverage-link">Read Article <i class="fas fa-arrow-right"></i></a>
                </div>
            </div>

            <!-- Press Releases -->
            <div class="releases-section" data-aos="fade-up">
                <h2 style="font-family: 'Enriqueta', serif; font-size: 28px; margin-bottom: 30px;">Press Releases</h2>
                
                <div class="release-item">
                    <div class="release-date">Mar 1, 2026</div>
                    <div class="release-content">
                        <h3>PLANORA Launches New Inventory Management Features</h3>
                        <p>Real-time stock tracking and low-stock alerts now available for all users.</p>
                        <a href="#" class="coverage-link">Read Release <i class="fas fa-arrow-right"></i></a>
                    </div>
                </div>

                <div class="release-item">
                    <div class="release-date">Feb 15, 2026</div>
                    <div class="release-content">
                        <h3>PLANORA Expands Team, Opens New Office in Makati</h3>
                        <p>Company doubles workforce to meet growing demand for its services.</p>
                        <a href="#" class="coverage-link">Read Release <i class="fas fa-arrow-right"></i></a>
                    </div>
                </div>

                <div class="release-item">
                    <div class="release-date">Jan 20, 2026</div>
                    <div class="release-content">
                        <h3>PLANORA Partners with GCash for Seamless Payments</h3>
                        <p>Integration allows users to accept and track GCash payments directly.</p>
                        <a href="#" class="coverage-link">Read Release <i class="fas fa-arrow-right"></i></a>
                    </div>
                </div>

                <div class="release-item">
                    <div class="release-date">Jan 5, 2026</div>
                    <div class="release-content">
                        <h3>PLANORA Named to 2026 List of Top B2B Companies</h3>
                        <p>Recognition from leading industry analysts highlights company growth.</p>
                        <a href="#" class="coverage-link">Read Release <i class="fas fa-arrow-right"></i></a>
                    </div>
                </div>
            </div>

            <!-- Media Contacts -->
            <div class="media-contacts" data-aos="fade-up">
                <h2 style="font-family: 'Enriqueta', serif; font-size: 28px; text-align: center; margin-bottom: 20px;">Media Contacts</h2>
                <p style="text-align: center; color: #64748b; max-width: 600px; margin: 0 auto 40px;">For press inquiries, please contact our media relations team.</p>
                
                <div class="contacts-grid">
                    <div class="contact-card">
                        <i class="fas fa-envelope"></i>
                        <h4>Email</h4>
                        <p><a href="mailto:press@PLANORA.com">press@PLANORA.com</a></p>
                    </div>
                    <div class="contact-card">
                        <i class="fas fa-phone"></i>
                        <h4>Phone</h4>
                        <p>+63 (02) 1234-5678</p>
                    </div>
                    <div class="contact-card">
                        <i class="fas fa-comment"></i>
                        <h4>Social</h4>
                        <p>@PLANORA</p>
                    </div>
                </div>
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
    </script>
</body>
</html>