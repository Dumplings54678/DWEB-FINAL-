<?php
session_start();
// Public page - no login required
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blog - HAUccountant</title>
    
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

        /* Blog Page Specific */
        .blog-page {
            padding: 80px 0;
            background: #f8fafc;
        }

        .featured-post {
            background: white;
            border-radius: 30px;
            overflow: hidden;
            box-shadow: 0 10px 40px rgba(0,0,0,0.05);
            margin-bottom: 60px;
            display: grid;
            grid-template-columns: 1fr 1fr;
        }

        .featured-image {
            height: 100%;
            background: linear-gradient(135deg, #06B6D4, #14B8A6);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 48px;
        }

        .featured-content {
            padding: 50px;
        }

        .featured-category {
            display: inline-block;
            padding: 4px 12px;
            background: #cffafe;
            color: #0891b2;
            border-radius: 30px;
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 15px;
        }

        .featured-content h2 {
            font-family: 'Enriqueta', serif;
            font-size: 32px;
            margin-bottom: 15px;
            color: #0f172a;
        }

        .featured-meta {
            display: flex;
            gap: 20px;
            color: #64748b;
            font-size: 14px;
            margin-bottom: 20px;
        }

        .featured-excerpt {
            color: #475569;
            margin-bottom: 30px;
            line-height: 1.8;
        }

        .read-more {
            color: #06B6D4;
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .read-more:hover {
            gap: 12px;
        }

        .blog-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 30px;
            margin-bottom: 60px;
        }

        .blog-card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            border: 1px solid #e2e8f0;
            transition: all 0.3s ease;
        }

        .blog-card:hover {
            transform: translateY(-10px);
            border-color: #06B6D4;
            box-shadow: 0 20px 40px rgba(6, 182, 212, 0.1);
        }

        .blog-image {
            height: 200px;
            background: linear-gradient(135deg, #cffafe, #ccfbf1);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #06B6D4;
            font-size: 40px;
        }

        .blog-content {
            padding: 25px;
        }

        .blog-meta {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            font-size: 12px;
            color: #64748b;
        }

        .blog-category {
            background: #cffafe;
            color: #0891b2;
            padding: 2px 10px;
            border-radius: 30px;
            font-weight: 600;
        }

        .blog-card h3 {
            font-family: 'Enriqueta', serif;
            font-size: 18px;
            margin-bottom: 10px;
            color: #0f172a;
        }

        .blog-card p {
            color: #64748b;
            font-size: 14px;
            margin-bottom: 15px;
        }

        .categories-section {
            background: white;
            border-radius: 30px;
            padding: 40px;
            margin-bottom: 60px;
        }

        .categories-grid {
            display: grid;
            grid-template-columns: repeat(6, 1fr);
            gap: 15px;
            margin-top: 30px;
        }

        .category-tag {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 30px;
            padding: 10px;
            text-align: center;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .category-tag:hover {
            border-color: #06B6D4;
            color: #06B6D4;
        }

        .newsletter-section {
            background: linear-gradient(135deg, #0f172a, #1e293b);
            border-radius: 30px;
            padding: 60px;
            text-align: center;
            color: white;
        }

        .newsletter-section h2 {
            font-family: 'Enriqueta', serif;
            font-size: 32px;
            margin-bottom: 15px;
        }

        .newsletter-section p {
            opacity: 0.9;
            margin-bottom: 30px;
        }

        .newsletter-form {
            display: flex;
            gap: 10px;
            max-width: 500px;
            margin: 0 auto;
        }

        .newsletter-input {
            flex: 1;
            padding: 15px 20px;
            border: none;
            border-radius: 30px;
            font-size: 16px;
        }

        .newsletter-btn {
            background: #06B6D4;
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 30px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .newsletter-btn:hover {
            background: #0891b2;
            transform: translateY(-2px);
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
            .blog-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .categories-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        @media screen and (max-width: 768px) {
            .nav-center {
                display: none;
            }
            
            .featured-post {
                grid-template-columns: 1fr;
            }
            
            .blog-grid {
                grid-template-columns: 1fr;
            }
            
            .categories-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .newsletter-form {
                flex-direction: column;
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
            <h1 data-aos="fade-up">PLANORA Blog</h1>
            <p data-aos="fade-up" data-aos-delay="100">Insights, tips, and news for Filipino business owners</p>
        </div>
    </section>

    <!-- Blog Content -->
    <section class="blog-page">
        <div class="container">
            <!-- Featured Post -->
            <div class="featured-post" data-aos="fade-up">
                <div class="featured-image">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="featured-content">
                    <span class="featured-category">Featured</span>
                    <h2>5 Accounting Tips for Small Business Owners</h2>
                    <div class="featured-meta">
                        <span><i class="far fa-calendar"></i> March 15, 2026</span>
                        <span><i class="far fa-user"></i> Almer Lalic</span>
                        <span><i class="far fa-clock"></i> 5 min read</span>
                    </div>
                    <p class="featured-excerpt">Learn the essential accounting practices that will help you manage your business finances more effectively and avoid common pitfalls that many small business owners face.</p>
                    <a href="#" class="read-more">Read Full Article <i class="fas fa-arrow-right"></i></a>
                </div>
            </div>

            <!-- Blog Grid -->
            <div class="blog-grid" data-aos="fade-up">
                <div class="blog-card">
                    <div class="blog-image">
                        <i class="fas fa-calculator"></i>
                    </div>
                    <div class="blog-content">
                        <div class="blog-meta">
                            <span class="blog-category">Tax Tips</span>
                            <span>Mar 10, 2026</span>
                        </div>
                        <h3>Understanding VAT in the Philippines</h3>
                        <p>A comprehensive guide to Value Added Tax for Filipino businesses - when to register, how to compute, and filing requirements.</p>
                        <a href="#" class="read-more">Read More <i class="fas fa-arrow-right"></i></a>
                    </div>
                </div>

                <div class="blog-card">
                    <div class="blog-image">
                        <i class="fas fa-boxes"></i>
                    </div>
                    <div class="blog-content">
                        <div class="blog-meta">
                            <span class="blog-category">Inventory</span>
                            <span>Mar 5, 2026</span>
                        </div>
                        <h3>Effective Inventory Management Strategies</h3>
                        <p>Learn how to optimize your inventory levels, reduce carrying costs, and prevent stockouts with these proven strategies.</p>
                        <a href="#" class="read-more">Read More <i class="fas fa-arrow-right"></i></a>
                    </div>
                </div>

                <div class="blog-card">
                    <div class="blog-image">
                        <i class="fas fa-file-invoice-dollar"></i>
                    </div>
                    <div class="blog-content">
                        <div class="blog-meta">
                            <span class="blog-category">Business</span>
                            <span>Feb 28, 2026</span>
                        </div>
                        <h3>How to Prepare for Tax Season</h3>
                        <p>Get your business ready for tax filing with this step-by-step guide, including deadlines, requirements, and best practices.</p>
                        <a href="#" class="read-more">Read More <i class="fas fa-arrow-right"></i></a>
                    </div>
                </div>

                <div class="blog-card">
                    <div class="blog-image">
                        <i class="fas fa-chart-pie"></i>
                    </div>
                    <div class="blog-content">
                        <div class="blog-meta">
                            <span class="blog-category">Analytics</span>
                            <span>Feb 20, 2026</span>
                        </div>
                        <h3>Key Metrics Every Business Should Track</h3>
                        <p>Discover the most important financial metrics that will help you understand your business performance and make better decisions.</p>
                        <a href="#" class="read-more">Read More <i class="fas fa-arrow-right"></i></a>
                    </div>
                </div>

                <div class="blog-card">
                    <div class="blog-image">
                        <i class="fas fa-mobile-alt"></i>
                    </div>
                    <div class="blog-content">
                        <div class="blog-meta">
                            <span class="blog-category">Technology</span>
                            <span>Feb 15, 2026</span>
                        </div>
                        <h3>Digital Tools for Modern Businesses</h3>
                        <p>Explore the essential digital tools and software that can help streamline your operations and boost productivity.</p>
                        <a href="#" class="read-more">Read More <i class="fas fa-arrow-right"></i></a>
                    </div>
                </div>

                <div class="blog-card">
                    <div class="blog-image">
                        <i class="fas fa-hand-holding-usd"></i>
                    </div>
                    <div class="blog-content">
                        <div class="blog-meta">
                            <span class="blog-category">Finance</span>
                            <span>Feb 10, 2026</span>
                        </div>
                        <h3>Cash Flow Management Guide</h3>
                        <p>Learn how to maintain healthy cash flow, manage receivables and payables, and ensure your business stays liquid.</p>
                        <a href="#" class="read-more">Read More <i class="fas fa-arrow-right"></i></a>
                    </div>
                </div>
            </div>

            <!-- Categories Section -->
            <div class="categories-section" data-aos="fade-up">
                <h2 style="font-family: 'Enriqueta', serif; font-size: 24px; text-align: center;">Browse by Category</h2>
                <div class="categories-grid">
                    <div class="category-tag">All Posts</div>
                    <div class="category-tag">Tax Tips</div>
                    <div class="category-tag">Inventory</div>
                    <div class="category-tag">Business</div>
                    <div class="category-tag">Analytics</div>
                    <div class="category-tag">Technology</div>
                    <div class="category-tag">Finance</div>
                    <div class="category-tag">Accounting</div>
                    <div class="category-tag">Startup</div>
                    <div class="category-tag">E-commerce</div>
                    <div class="category-tag">Marketing</div>
                    <div class="category-tag">HR</div>
                </div>
            </div>

            <!-- Newsletter Section -->
            <div class="newsletter-section" data-aos="zoom-in">
                <h2>Subscribe to Our Newsletter</h2>
                <p>Get the latest business tips and updates delivered straight to your inbox.</p>
                <form class="newsletter-form" onsubmit="alert('Thank you for subscribing!'); return false;">
                    <input type="email" class="newsletter-input" placeholder="Enter your email address" required>
                    <button type="submit" class="newsletter-btn">Subscribe</button>
                </form>
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