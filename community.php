<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Community - PLANOR</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Enriqueta:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Plus Jakarta Sans', 'Inter', sans-serif; color: #1e293b; background: #ffffff; }
        .navbar { position: fixed; top: 0; left: 0; right: 0; z-index: 1000; padding: 20px 0; background: rgba(255,255,255,0.95); backdrop-filter: blur(10px); box-shadow: 0 4px 20px rgba(0,0,0,0.1); }
        .nav-container { max-width: 1280px; margin: 0 auto; padding: 0 24px; display: flex; justify-content: space-between; align-items: center; }
        .logo { display: flex; align-items: center; gap: 10px; text-decoration: none; }
        .logo-icon { width: 52px; height: 52px; background: linear-gradient(135deg, #06B6D4, #3B82F6); border-radius: 12px; display: flex; align-items: center; justify-content: center; }
        .logo-icon i { color: white; font-size: 26px; }
        .brand-name { font-size: 24px; font-weight: 700; color: #1e293b; }
        .nav-center { display: flex; gap: 48px; }
        .nav-link { color: #1e293b; text-decoration: none; font-weight: 500; position: relative; }
        .nav-link::after { content: ''; position: absolute; bottom: -4px; left: 0; width: 0; height: 2px; background: #06B6D4; transition: width 0.3s ease; }
        .nav-link:hover::after { width: 100%; }
        .nav-right { display: flex; align-items: center; gap: 32px; }
        .signin-link { color: #1e293b; text-decoration: none; }
        .signin-link:hover { color: #06B6D4; }
        .get-started-btn { background: #000000; color: white; text-decoration: none; padding: 12px 28px; border-radius: 40px; font-weight: 600; display: inline-flex; align-items: center; gap: 8px; transition: all 0.3s ease; }
        .get-started-btn:hover { background: #1e293b; transform: translateY(-3px); }
        .page-header { background: linear-gradient(135deg, #06B6D4, #14B8A6, #0891b2); padding: 140px 0 80px; text-align: center; color: white; }
        .page-header h1 { font-family: 'Enriqueta', serif; font-size: 48px; margin-bottom: 15px; }
        .page-header p { font-size: 18px; opacity: 0.95; max-width: 600px; margin: 0 auto; }
        .container { max-width: 1280px; margin: 0 auto; padding: 0 24px; }
        .content-section { padding: 80px 0; background: #f8fafc; }
        .coming-soon { text-align: center; padding: 60px 20px; background: white; border-radius: 24px; border: 1px solid #e2e8f0; }
        .coming-soon i { font-size: 64px; color: #06B6D4; margin-bottom: 20px; }
        .coming-soon h3 { font-size: 28px; color: #0f172a; margin-bottom: 15px; }
        .coming-soon p { color: #64748b; }
        .footer { background: #0f172a; color: white; padding: 80px 0 30px; }
        .footer-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 40px; margin-bottom: 60px; }
        .footer-column h4 { font-size: 18px; font-weight: 700; margin-bottom: 20px; position: relative; display: inline-block; }
        .footer-column h4::after { content: ''; position: absolute; bottom: -8px; left: 0; width: 40px; height: 3px; background: #06B6D4; border-radius: 3px; }
        .footer-column ul { list-style: none; }
        .footer-column li { margin-bottom: 12px; }
        .footer-column a { color: rgba(255,255,255,0.7); text-decoration: none; transition: all 0.3s ease; display: inline-flex; align-items: center; gap: 6px; }
        .footer-column a:hover { color: #06B6D4; transform: translateX(6px); }
        .footer-bottom { display: flex; justify-content: space-between; align-items: center; padding-top: 30px; border-top: 1px solid rgba(255,255,255,0.1); }
        .social-links { display: flex; gap: 20px; }
        .social-links a { color: white; font-size: 20px; width: 44px; height: 44px; background: rgba(255,255,255,0.1); border-radius: 50%; display: flex; align-items: center; justify-content: center; transition: all 0.3s ease; }
        .social-links a:hover { background: #06B6D4; transform: translateY(-5px); }
        .copyright { color: rgba(255,255,255,0.7); font-size: 14px; }
        .footer-links { display: flex; gap: 30px; }
        .footer-links a { color: rgba(255,255,255,0.7); text-decoration: none; font-size: 14px; }
        .footer-links a:hover { color: #06B6D4; }
        .back-to-top { position: fixed; bottom: 30px; right: 30px; width: 50px; height: 50px; background: linear-gradient(135deg, #06B6D4, #14B8A6); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; opacity: 0; visibility: hidden; transition: all 0.3s ease; z-index: 99; }
        .back-to-top.show { opacity: 1; visibility: visible; }
        .back-to-top:hover { transform: translateY(-5px); }
        @media screen and (max-width: 768px) {
            .nav-center { display: none; }
            .page-header h1 { font-size: 36px; }
            .footer-grid { grid-template-columns: 1fr; }
            .footer-bottom { flex-direction: column; gap: 20px; text-align: center; }
        }
    </style>
</head>
<body>
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
    <section class="page-header">
        <div class="container">
            <h1 data-aos="fade-up">Community</h1>
            <p data-aos="fade-up" data-aos-delay="100">Connect with other PLANORA users</p>
        </div>
    </section>
    <section class="content-section">
        <div class="container">
            <div class="coming-soon" data-aos="fade-up">
                <i class="fas fa-users"></i>
                <h3>Community Coming Soon</h3>
                <p>We're building a space for users to share ideas and help each other.</p>
                <p style="margin-top: 20px; color: #06B6D4;">Stay tuned!</p>
            </div>
        </div>
    </section>
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
    <div class="back-to-top" id="backToTop" onclick="scrollToTop()"><i class="fas fa-arrow-up"></i></div>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init({ duration: 800, once: true });
        window.addEventListener('scroll', function() {
            document.getElementById('backToTop').classList.toggle('show', window.scrollY > 300);
        });
        function scrollToTop() { window.scrollTo({ top: 0, behavior: 'smooth' }); }
    </script>
</body>
</html>