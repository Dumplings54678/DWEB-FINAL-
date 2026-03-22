<?php
session_start();
// If already logged in, redirect to dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PLANORA - Accounting Ko ang Account Mo.</title>
    
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
            line-height: 1.5;
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
            background: transparent;
        }

        .navbar.scrolled {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            padding: 15px 0;
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
            color: white;
            transition: color 0.3s ease;
            letter-spacing: -0.5px;
        }

        .navbar.scrolled .brand-name {
            color: #1e293b;
        }

        .nav-center {
            display: flex;
            gap: 48px;
        }

        .nav-link {
            color: white;
            text-decoration: none;
            font-weight: 500;
            font-size: 16px;
            transition: color 0.3s ease;
            position: relative;
        }

        .navbar.scrolled .nav-link {
            color: #1e293b;
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
            color: white;
            text-decoration: none;
            font-weight: 500;
            font-size: 16px;
            transition: color 0.3s ease;
        }

        .navbar.scrolled .signin-link {
            color: #1e293b;
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

        .get-started-btn i {
            transition: transform 0.3s ease;
        }

        .get-started-btn:hover i {
            transform: translateX(4px);
        }

        /* Hero Section */
        .hero {
            min-height: 100vh;
            background: radial-gradient(circle at 20% 30%, rgba(6, 182, 212, 0.15) 0%, transparent 40%),
                        radial-gradient(circle at 80% 70%, rgba(59, 130, 246, 0.15) 0%, transparent 40%),
                        linear-gradient(135deg, #0f172a, #1e40af, #0891b2);
            display: flex;
            align-items: center;
            padding: 120px 0 80px;
            position: relative;
            overflow: hidden;
        }

        .hero::before {
            content: '';
            position: absolute;
            width: 600px;
            height: 600px;
            background: radial-gradient(circle, rgba(6, 182, 212, 0.2) 0%, transparent 70%);
            border-radius: 50%;
            top: -200px;
            right: -100px;
            filter: blur(60px);
            animation: float 25s infinite alternate;
        }

        .hero::after {
            content: '';
            position: absolute;
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.15) 0%, transparent 70%);
            border-radius: 50%;
            bottom: -150px;
            left: -150px;
            filter: blur(80px);
            animation: float 20s infinite alternate-reverse;
        }

        @keyframes float {
            0% { transform: translate(0, 0) scale(1); }
            100% { transform: translate(40px, 30px) scale(1.1); }
        }

        .hero-container {
            max-width: 1280px;
            margin: 0 auto;
            padding: 0 24px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 60px;
            align-items: center;
            position: relative;
            z-index: 2;
        }

        .hero-badge {
            display: inline-block;
            background: rgba(6, 182, 212, 0.2);
            color: #06B6D4;
            padding: 8px 18px;
            border-radius: 40px;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 25px;
            border: 1px solid rgba(6, 182, 212, 0.4);
            backdrop-filter: blur(5px);
            animation: fadeInUp 0.8s ease;
        }

        .hero-title {
            font-size: 64px;
            line-height: 1.1;
            margin-bottom: 25px;
            font-weight: 800;
            animation: fadeInUp 0.8s ease 0.1s both;
        }

        .title-white {
            color: white;
            display: block;
            text-shadow: 0 4px 20px rgba(0,0,0,0.2);
        }

        .title-gradient {
            background: linear-gradient(135deg, #06B6D4, #ffffff);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            display: block;
        }

        .hero-subtitle {
            font-size: 18px;
            color: rgba(255,255,255,0.9);
            margin-bottom: 40px;
            max-width: 550px;
            line-height: 1.7;
            animation: fadeInUp 0.8s ease 0.2s both;
        }

        .hero-buttons {
            display: flex;
            gap: 20px;
            animation: fadeInUp 0.8s ease 0.3s both;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(40px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .btn-black {
            background: #000000;
            color: white;
            text-decoration: none;
            padding: 16px 38px;
            border-radius: 40px;
            font-weight: 600;
            font-size: 16px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0,0,0,0.3);
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }

        .btn-black:hover {
            background: #1e293b;
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.4);
        }

        .btn-outline-cyan {
            background: transparent;
            color: white;
            text-decoration: none;
            padding: 16px 38px;
            border-radius: 40px;
            font-weight: 600;
            font-size: 16px;
            border: 2px solid #06B6D4;
            transition: all 0.3s ease;
        }

        .btn-outline-cyan:hover {
            background: #06B6D4;
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(6, 182, 212, 0.4);
        }

        /* Glass Card */
        .glass-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 30px;
            padding: 35px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 420px;
            margin-left: auto;
            animation: fadeInRight 0.8s ease 0.2s both;
        }

        @keyframes fadeInRight {
            from {
                opacity: 0;
                transform: translateX(50px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }

        .card-title {
            color: white;
            font-size: 18px;
            font-weight: 600;
        }

        .live-indicator {
            color: #10B981;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 5px;
            background: rgba(0,0,0,0.2);
            padding: 4px 12px;
            border-radius: 30px;
        }

        .live-indicator i {
            font-size: 10px;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.5; transform: scale(1.2); }
            100% { opacity: 1; transform: scale(1); }
        }

        .profit-card {
            background: rgba(16, 185, 129, 0.2);
            border: 1px solid rgba(16, 185, 129, 0.3);
            border-radius: 20px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .profit-label {
            color: rgba(255,255,255,0.8);
            font-size: 14px;
            margin-bottom: 8px;
            display: block;
        }

        .profit-value {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .profit-value .amount {
            font-size: 40px;
            font-weight: 700;
            color: white;
        }

        .profit-value .growth {
            background: #10B981;
            color: white;
            padding: 4px 12px;
            border-radius: 30px;
            font-size: 14px;
            font-weight: 600;
        }

        .stats-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 20px;
        }

        .stat-item {
            background: rgba(255,255,255,0.1);
            border-radius: 16px;
            padding: 15px;
        }

        .stat-item .stat-label {
            color: rgba(255,255,255,0.7);
            font-size: 13px;
            display: block;
            margin-bottom: 5px;
        }

        .stat-item .stat-amount {
            color: white;
            font-size: 20px;
            font-weight: 600;
        }

        .status-badge {
            display: flex;
            align-items: center;
            gap: 8px;
            background: #10B981;
            color: white;
            padding: 10px 18px;
            border-radius: 40px;
            margin-bottom: 15px;
            font-weight: 600;
            font-size: 14px;
            width: fit-content;
        }

        .update-time {
            color: rgba(255,255,255,0.6);
            font-size: 12px;
            text-align: right;
        }

        /* Features Section */
        .features {
            padding: 100px 0;
            background: white;
            position: relative;
            overflow: hidden;
        }

        .container {
            max-width: 1280px;
            margin: 0 auto;
            padding: 0 24px;
        }

        .section-header {
            text-align: center;
            max-width: 700px;
            margin: 0 auto 60px;
        }

        .section-subtitle {
            color: #06B6D4;
            font-size: 14px;
            font-weight: 600;
            letter-spacing: 2px;
            display: block;
            margin-bottom: 15px;
            text-transform: uppercase;
        }

        .section-title {
            font-size: 44px;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 20px;
            line-height: 1.2;
            font-family: 'Enriqueta', serif;
        }

        .section-description {
            color: #475569;
            font-size: 18px;
            line-height: 1.7;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 30px;
        }

        .feature-card {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 24px;
            padding: 35px 25px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 20px rgba(0,0,0,0.02);
        }

        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 30px rgba(0,0,0,0.1);
            border-color: #06B6D4;
        }

        .feature-icon {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, #cffafe, #ccfbf1);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 25px;
            transition: all 0.3s ease;
        }

        .feature-card:hover .feature-icon {
            background: linear-gradient(135deg, #06B6D4, #14B8A6);
        }

        .feature-icon i {
            color: #06B6D4;
            font-size: 32px;
            transition: all 0.3s ease;
        }

        .feature-card:hover .feature-icon i {
            color: white;
        }

        .feature-card h3 {
            font-size: 22px;
            font-weight: 700;
            margin-bottom: 15px;
            color: #0f172a;
        }

        .feature-card p {
            color: #475569;
            line-height: 1.7;
            font-size: 15px;
        }

        /* Creators Section */
        .creators {
            padding: 80px 0;
            background: linear-gradient(135deg, #f8fafc, #ffffff);
        }

        .creators-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 30px;
            margin-top: 50px;
        }

        .creator-card {
            background: white;
            border-radius: 30px;
            padding: 40px 30px;
            text-align: center;
            border: 1px solid #e2e8f0;
            transition: all 0.3s ease;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
        }

        .creator-card:hover {
            transform: translateY(-15px);
            border-color: #06B6D4;
            box-shadow: 0 20px 40px rgba(6, 182, 212, 0.15);
        }

        .creator-avatar {
            width: 140px;
            height: 140px;
            background: linear-gradient(135deg, #06B6D4, #14B8A6);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 25px;
            color: white;
            font-size: 48px;
            font-weight: 600;
            border: 5px solid white;
            box-shadow: 0 10px 20px rgba(6, 182, 212, 0.2);
        }

        .creator-card h3 {
            font-size: 24px;
            color: #0f172a;
            margin-bottom: 5px;
            font-weight: 700;
        }

        .creator-role {
            color: #06B6D4;
            font-weight: 600;
            margin-bottom: 15px;
            font-size: 14px;
            letter-spacing: 1px;
        }

        .creator-bio {
            color: #64748b;
            font-size: 14px;
            line-height: 1.7;
            margin-bottom: 20px;
        }

        .creator-social {
            display: flex;
            justify-content: center;
            gap: 15px;
        }

        .creator-social a {
            width: 40px;
            height: 40px;
            background: #f1f5f9;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #64748b;
            transition: all 0.3s ease;
        }

        .creator-social a:hover {
            background: #06B6D4;
            color: white;
            transform: translateY(-3px);
        }

        /* Testimonials */
        .testimonials {
            padding: 100px 0;
            background: white;
        }

        .testimonial-carousel {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 30px;
            margin-top: 50px;
        }

        .testimonial-card {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 24px;
            padding: 35px;
            position: relative;
            transition: all 0.3s ease;
        }

        .testimonial-card.featured {
            border-color: #06B6D4;
            box-shadow: 0 20px 30px rgba(6, 182, 212, 0.1);
        }

        .testimonial-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 30px rgba(0,0,0,0.1);
        }

        .quote-icon {
            color: #06B6D4;
            font-size: 32px;
            margin-bottom: 20px;
            opacity: 0.5;
        }

        .testimonial-text {
            color: #334155;
            line-height: 1.8;
            margin-bottom: 25px;
            font-style: italic;
            font-size: 16px;
        }

        .testimonial-author {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .author-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            overflow: hidden;
            background: linear-gradient(135deg, #06B6D4, #14B8A6);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 20px;
        }

        .author-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .author-info h4 {
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 4px;
            font-size: 18px;
        }

        .author-info p {
            color: #64748b;
            font-size: 13px;
        }

        .carousel-dots {
            display: flex;
            justify-content: center;
            gap: 12px;
            margin-top: 50px;
        }

        .dot {
            width: 12px;
            height: 12px;
            background: #cbd5e1;
            border-radius: 50%;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .dot.active {
            background: #06B6D4;
            width: 40px;
            border-radius: 20px;
        }

        .dot:hover {
            background: #06B6D4;
        }

        /* CTA Section */
        .cta-section {
            padding: 100px 0;
            background: linear-gradient(135deg, #06B6D4, #14B8A6, #0891b2);
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .cta-section::before {
            content: '';
            position: absolute;
            width: 600px;
            height: 600px;
            background: radial-gradient(circle, rgba(255,255,255,0.2) 0%, transparent 70%);
            border-radius: 50%;
            top: -200px;
            right: -100px;
        }

        .cta-title {
            font-size: 48px;
            font-weight: 800;
            color: white;
            margin-bottom: 30px;
            position: relative;
            z-index: 2;
            text-shadow: 0 4px 20px rgba(0,0,0,0.2);
        }

        .btn-white {
            background: white;
            color: #06B6D4;
            text-decoration: none;
            padding: 18px 50px;
            border-radius: 50px;
            font-weight: 700;
            font-size: 18px;
            transition: all 0.3s ease;
            display: inline-block;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            position: relative;
            z-index: 2;
        }

        .btn-white:hover {
            transform: translateY(-5px) scale(1.05);
            box-shadow: 0 15px 40px rgba(0,0,0,0.3);
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
            .hero-title {
                font-size: 52px;
            }

            .features-grid,
            .testimonial-carousel,
            .creators-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .hero-container {
                grid-template-columns: 1fr;
                text-align: center;
            }

            .hero-subtitle {
                margin: 0 auto 40px;
            }

            .hero-buttons {
                justify-content: center;
            }

            .glass-card {
                margin: 0 auto;
            }
        }

        @media screen and (max-width: 768px) {
            .nav-center {
                display: none;
            }

            .hero-title {
                font-size: 42px;
            }

            .features-grid,
            .testimonial-carousel,
            .creators-grid,
            .footer-grid {
                grid-template-columns: 1fr;
            }

            .section-title {
                font-size: 36px;
            }

            .cta-title {
                font-size: 36px;
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

            .hero-buttons {
                flex-direction: column;
            }
        }

        @media screen and (max-width: 480px) {
            .hero-title {
                font-size: 36px;
            }

            .glass-card {
                padding: 25px;
            }

            .profit-value {
                flex-direction: column;
                align-items: flex-start;
            }
        }

        .creator-avatar {
    width: 80px;  /* Changed from 120px to 80px */
    height: 80px; /* Changed from 120px to 80px */
    margin: 0 auto 1rem;
    border-radius: 50%;
    overflow: hidden;
}

.creator-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar" id="navbar">
        <div class="nav-container">
            <div class="logo">
                <div class="logo-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <span class="brand-name">PLANORA</span>
            </div>
            
            <div class="nav-center">
                <a href="#features" class="nav-link">Features</a>
                <a href="#creators" class="nav-link">Creators</a>
                <a href="#testimonials" class="nav-link">Testimonials</a>
                <a href="#contact" class="nav-link">Contact</a>
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

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-container">
            <div class="hero-content">
                <span class="hero-badge" data-aos="fade-down">COMPLETE BUSINESS CONTROL</span>
                <h1 class="hero-title">
                    <span class="title-white">Manage Everything.</span>
                    <span class="title-gradient">Profit from Anything.</span>
                </h1>
                <p class="hero-subtitle" data-aos="fade-up" data-aos-delay="100">
                    Real-time insights into your business performance. Track inventory, expenses, sales, and profitability all in one powerful dashboard.
                </p>
                <div class="hero-buttons" data-aos="fade-up" data-aos-delay="200">
                    <a href="register.php" class="btn-black">
                        Start Free Trial
                        <i class="fas fa-rocket"></i>
                    </a>
                    <a href="#features" class="btn-outline-cyan">Learn More</a>
                </div>
            </div>
            
            <div class="hero-card">
                <div class="glass-card" data-aos="fade-left" data-aos-delay="300">
                    <div class="card-header">
                        <span class="card-title">Business Overview</span>
                        <span class="live-indicator">
                            <i class="fas fa-circle"></i>
                            Live
                        </span>
                    </div>
                    
                    <div class="profit-card">
                        <span class="profit-label">Net Profit</span>
                        <div class="profit-value">
                            <span class="amount">₱45,230</span>
                            <span class="growth">+12.5%</span>
                        </div>
                    </div>
                    
                    <div class="stats-row">
                        <div class="stat-item">
                            <span class="stat-label">Revenue</span>
                            <span class="stat-amount">₱137,997</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-label">Expenses</span>
                            <span class="stat-amount">₱87,989</span>
                        </div>
                    </div>
                    
                    <div class="status-badge">
                        <i class="fas fa-check-circle"></i>
                        <span>Profitable</span>
                    </div>
                    
                    <div class="update-time">
                        <i class="far fa-clock"></i> Updated just now
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="features">
        <div class="container">
            <div class="section-header" data-aos="fade-up">
                <span class="section-subtitle">POWERFUL FEATURES</span>
                <h2 class="section-title">Everything You Need to Succeed</h2>
                <p class="section-description">
                    Comprehensive business management tools designed to help you make better decisions and grow faster.
                </p>
            </div>

            <div class="features-grid">
                <!-- Feature 1 -->
                <div class="feature-card" data-aos="fade-up" data-aos-delay="100">
                    <div class="feature-icon">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <h3>Sales Management</h3>
                    <p>Track all your sales transactions with automatic receipt generation and tax calculations.</p>
                </div>

                <!-- Feature 2 -->
                <div class="feature-card" data-aos="fade-up" data-aos-delay="150">
                    <div class="feature-icon">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <h3>Expense Tracking</h3>
                    <p>Categorize and monitor all business expenses with budget alerts and monthly summaries.</p>
                </div>

                <!-- Feature 3 -->
                <div class="feature-card" data-aos="fade-up" data-aos-delay="200">
                    <div class="feature-icon">
                        <i class="fas fa-archive"></i>
                    </div>
                    <h3>Inventory Control</h3>
                    <p>Real-time stock tracking with automatic deduction after every sale and low-stock alerts.</p>
                </div>

                <!-- Feature 4 -->
                <div class="feature-card" data-aos="fade-up" data-aos-delay="250">
                    <div class="feature-icon">
                        <i class="fas fa-chart-bar"></i>
                    </div>
                    <h3>Reports & Analytics</h3>
                    <p>Generate comprehensive financial reports and gain business insights with visual charts.</p>
                </div>

                <!-- Feature 5 -->
                <div class="feature-card" data-aos="fade-up" data-aos-delay="300">
                    <div class="feature-icon">
                        <i class="fas fa-wallet"></i>
                    </div>
                    <h3>Budget Planning</h3>
                    <p>Set sales targets and expense limits to stay on track with real-time performance comparison.</p>
                </div>

                <!-- Feature 6 -->
                <div class="feature-card" data-aos="fade-up" data-aos-delay="350">
                    <div class="feature-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <h3>Multi-User Access</h3>
                    <p>Add staff members with customizable permissions and track all activities with detailed logs.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Creators Section -->
    <section id="creators" class="creators">
        <div class="container">
            <div class="section-header" data-aos="fade-up">
                <span class="section-subtitle">THE TEAM</span>
                <h2 class="section-title">Meet the Creators</h2>
                <p class="section-description">
                    The brilliant minds behind PLANORA who made this platform possible.
                </p>
            </div>

            <div class="creators-grid">
                <!-- Almer Lalic -->
                <div class="creator-card" data-aos="fade-up" data-aos-delay="100">
                    <div class="creator-avatar">
                        <img src="Alm.jpg" alt="Almer Lalic">
                    </div>
                    <h3>Almer Lalic</h3>
                    <div class="creator-role">FOUNDER & LEAD DEVELOPER</div>
                    <p class="creator-bio">Full-stack developer with expertise in PHP and MySQL. Passionate about creating intuitive business solutions that help entrepreneurs succeed.</p>
                    <div class="creator-social">
                        <a href="#"><i class="fab fa-github"></i></a>
                        <a href="#"><i class="fab fa-linkedin-in"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                    </div>
                </div>

                <!-- Sean Yambao -->
                <div class="creator-card" data-aos="fade-up" data-aos-delay="200">
                    <div class="creator-avatar">
                        <img src="Bao.jpg" alt="Almer Lalic">
                    </div>
                    <h3>Sean Yambao</h3>
                    <div class="creator-role">UI/UX DESIGNER</div>
                    <p class="creator-bio">Creative designer focused on user experience and interface design. Ensures PLANORA is both beautiful and easy to use for all business owners.</p>
                    <div class="creator-social">
                        <a href="#"><i class="fab fa-behance"></i></a>
                        <a href="#"><i class="fab fa-linkedin-in"></i></a>
                        <a href="#"><i class="fab fa-dribbble"></i></a>
                    </div>
                </div>

                <!-- Benedict Ong -->
                <div class="creator-card" data-aos="fade-up" data-aos-delay="300">
                    <div class="creator-avatar">
                        <img src="Ben.jpg" alt="Almer Lalic">
                    </div>
                    <h3>Benedict Ong</h3>
                    <div class="creator-role">BACKEND DEVELOPER</div>
                    <p class="creator-bio">Database architect and backend specialist. Ensures data integrity, security, and optimal performance for all financial transactions.</p>
                    <div class="creator-social">
                        <a href="#"><i class="fab fa-github"></i></a>
                        <a href="#"><i class="fab fa-linkedin-in"></i></a>
                        <a href="#"><i class="fab fa-stack-overflow"></i></a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Testimonials Section -->
    <section id="testimonials" class="testimonials">
        <div class="container">
            <div class="section-header" data-aos="fade-up">
                <span class="section-subtitle">SUCCESS STORIES</span>
                <h2 class="section-title">Trusted by Growing Businesses</h2>
            </div>

            <div class="testimonial-carousel">
                <div class="testimonial-card featured" data-aos="fade-up" data-aos-delay="100">
                    <div class="quote-icon">
                        <i class="fas fa-quote-left"></i>
                    </div>
                    <p class="testimonial-text">
                        "PLANORA transformed how we manage our daily operations. The real-time insights and automated reports save us hours every week."
                    </p>
                    <div class="testimonial-author">
                        <div class="author-avatar">
                            <img src="Ong.jpg" alt="Casey Ong">
                        </div>
                        <div class="author-info">
                            <h4>Casey, Ong</h4>
                            <p>2nd year Web Development</p>
                        </div>
                    </div>
                </div>

                <div class="testimonial-card" data-aos="fade-up" data-aos-delay="200">
                    <div class="quote-icon">
                        <i class="fas fa-quote-left"></i>
                    </div>
                    <p class="testimonial-text">
                        "The tax computation feature alone is worth it. No more manual calculations and the VAT reports are ready for filing in minutes."
                    </p>
                    <div class="testimonial-author">
                        <div class="author-avatar">
                            <img src="ABY.jfif" alt="Abygale Santos">
                        </div>
                        <div class="author-info">
                            <h4>Abygale, Santos</h4>
                            <p>2nd year Web Development</p>
                        </div>
                    </div>
                </div>

                <div class="testimonial-card" data-aos="fade-up" data-aos-delay="300">
                    <div class="quote-icon">
                        <i class="fas fa-quote-left"></i>
                    </div>
                    <p class="testimonial-text">
                        "Multi-user access with permissions is perfect for our team. I can give my staff access without worrying about sensitive data."
                    </p>
                    <div class="testimonial-author">
                        <div class="author-avatar">
                            <img src="Ayenna.jpg" alt="Ayenne Waje">
                        </div>
                        <div class="author-info">
                            <h4>Ayenne, Waje</h4>
                            <p>2nd year Web Development</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="carousel-dots">
                <span class="dot active"></span>
                <span class="dot"></span>
                <span class="dot"></span>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section">
        <div class="container">
            <h2 class="cta-title" data-aos="zoom-in">Ready to Transform Your Business?</h2>
            <a href="register.php" class="btn-white" data-aos="zoom-in" data-aos-delay="100">
                Start Your Free Trial
                <i class="fas fa-arrow-right"></i>
            </a>
        </div>
    </section>

    <!-- Footer -->
<footer id="contact" class="footer">
    <div class="container">
        <div class="footer-grid">
            <div class="footer-column" data-aos="fade-up" data-aos-delay="100">
                <h4>Product</h4>
                <ul>
                    <li><a href="features.php"><i class="fas fa-chevron-right"></i> Features</a></li>
                </ul>
            </div>
            
            <div class="footer-column" data-aos="fade-up" data-aos-delay="150">
                <h4>Company</h4>
                <ul>
                    <li><a href="about.php"><i class="fas fa-chevron-right"></i> About Us</a></li>
                    <li><a href="careers.php"><i class="fas fa-chevron-right"></i> Careers</a></li>
                </ul>
            </div>
            
            <div class="footer-column" data-aos="fade-up" data-aos-delay="200">
                <h4>Resources</h4>
                <ul>
                    <li><a href="documentation.php"><i class="fas fa-chevron-right"></i> Documentation</a></li>
                    <li><a href="support.php"><i class="fas fa-chevron-right"></i> Support</a></li>
                    <li><a href="community.php"><i class="fas fa-chevron-right"></i> Community</a></li>
                    <li><a href="guides.php"><i class="fas fa-chevron-right"></i> Guides</a></li>
                </ul>
            </div>
            
            <div class="footer-column" data-aos="fade-up" data-aos-delay="250">
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
            <p>"Accounting Ko ang Account Mo." - Created with <i class="fas fa-heart" style="color: #EF4444;"></i> by Almer Lalic, Sean Yambao, and Benedict Ong</p>
        </div>
    </div>
</footer>

    <!-- AOS Script -->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        // Initialize AOS
        AOS.init({
            duration: 800,
            once: true,
            mirror: false
        });

        // Navbar scroll effect
        window.addEventListener('scroll', function() {
            const navbar = document.getElementById('navbar');
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });

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