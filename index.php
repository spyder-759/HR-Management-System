<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HR Management System - Modern HR Solution</title>
    <link rel="stylesheet" href="assets/css/enhanced-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <style>
        /* Landing Page Specific Styles */
        :root {
            --hero-primary: #667eea;
            --hero-secondary: #764ba2;
            --hero-accent: #f093fb;
            --hero-tertiary: #4facfe;
            --text-light: #f1f5f9;
            --text-muted: #94a3b8;
            --section-bg: #f8fafc;
            --card-bg: #ffffff;
            --glass-bg: rgba(255, 255, 255, 0.1);
            --glass-border: rgba(255, 255, 255, 0.2);
        }

        body {
            font-family: 'Poppins', 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: var(--dark-color);
            overflow-x: hidden;
            margin: 0;
            padding: 0;
            min-height: 100vh;
        }

        * {
            box-sizing: border-box;
        }

        /* Animated Background */
        .animated-bg {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 25%, #f093fb 50%, #4facfe 75%, #667eea 100%);
            background-size: 400% 400%;
            animation: gradientShift 15s ease infinite;
        }

        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        /* Floating Particles */
        .particles {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            overflow: hidden;
        }

        .particle {
            position: absolute;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            animation: float 20s infinite;
        }

        @keyframes float {
            0%, 100% {
                transform: translateY(0) translateX(0) scale(1);
                opacity: 0;
            }
            10% {
                opacity: 0.4;
            }
            90% {
                opacity: 0.4;
            }
            100% {
                transform: translateY(-100vh) translateX(100px) scale(0.5);
                opacity: 0;
            }
        }

        /* Navigation */
        .landing-nav {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(25px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.3s ease;
            padding: 1rem 0;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }

        .landing-nav.scrolled {
            background: rgba(255, 255, 255, 0.95);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
            backdrop-filter: blur(30px);
        }

        .nav-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .nav-logo {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 1.5rem;
            font-weight: 800;
            color: #1a202c;
            text-decoration: none;
            transition: transform 0.3s ease;
        }

        .nav-logo:hover {
            transform: scale(1.05);
        }

        .nav-logo i {
            font-size: 2rem;
            background: linear-gradient(135deg, #1a202c, #2d3748);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            animation: glow 2s ease-in-out infinite alternate;
        }

        @keyframes glow {
            from { filter: drop-shadow(0 0 10px rgba(26, 32, 44, 0.3)); }
            to { filter: drop-shadow(0 0 20px rgba(26, 32, 44, 0.6)); }
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 2rem;
            list-style: none;
            margin: 0;
            padding: 0;
        }

        .nav-link {
            color: #1a202c;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            position: relative;
            padding: 0.5rem 0;
        }

        .nav-link:hover {
            color: #2d3748;
            text-shadow: 0 0 10px rgba(26, 32, 44, 0.3);
        }

        .nav-link::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 0;
            height: 2px;
            background: linear-gradient(90deg, #1a202c, rgba(26, 32, 44, 0.3));
            transition: width 0.3s ease;
        }

        .nav-link:hover::after {
            width: 100%;
        }

        .nav-buttons {
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        /* Hero Section */
        .hero {
            min-height: 100vh;
            display: flex;
            align-items: center;
            position: relative;
            overflow: hidden;
            margin-top: 80px;
            perspective: 1000px;
        }

        .hero-bg-elements {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 0;
        }

        .floating-shape {
            position: absolute;
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            border: 1px solid var(--glass-border);
            border-radius: 50%;
            animation: floatShape 20s infinite ease-in-out;
        }

        .shape-1 {
            width: 300px;
            height: 300px;
            top: 10%;
            left: 10%;
            animation-delay: 0s;
        }

        .shape-2 {
            width: 200px;
            height: 200px;
            top: 60%;
            right: 10%;
            animation-delay: 3s;
        }

        .shape-3 {
            width: 150px;
            height: 150px;
            bottom: 20%;
            left: 20%;
            animation-delay: 6s;
        }

        @keyframes floatShape {
            0%, 100% {
                transform: translateY(0) rotate(0deg) scale(1);
                opacity: 0.3;
            }
            25% {
                transform: translateY(-30px) rotate(90deg) scale(1.1);
                opacity: 0.5;
            }
            50% {
                transform: translateY(20px) rotate(180deg) scale(0.9);
                opacity: 0.4;
            }
            75% {
                transform: translateY(-20px) rotate(270deg) scale(1.05);
                opacity: 0.6;
            }
        }

        .hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: radial-gradient(circle at 20% 50%, rgba(255, 255, 255, 0.1) 0%, transparent 50%),
                        radial-gradient(circle at 80% 20%, rgba(255, 255, 255, 0.05) 0%, transparent 50%),
                        radial-gradient(circle at 40% 80%, rgba(255, 255, 255, 0.08) 0%, transparent 50%);
            opacity: 0.6;
        }

        .hero-content {
            position: relative;
            z-index: 2;
            max-width: 1200px;
            margin: 0 auto;
            padding: 4rem 2rem;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 4rem;
            align-items: center;
            transform-style: preserve-3d;
        }

        .hero-text {
            color: var(--white);
        }

        .hero-title {
            font-size: 4.5rem;
            font-weight: 900;
            line-height: 1.1;
            margin-bottom: 2rem;
            background: linear-gradient(135deg, #ffffff, rgba(255, 255, 255, 0.8), rgba(240, 147, 251, 0.9));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            animation: fadeInUp 1s ease-out, textGlow 3s ease-in-out infinite alternate;
            text-shadow: 0 0 30px rgba(255, 255, 255, 0.3);
        }

        @keyframes textGlow {
            from { filter: drop-shadow(0 0 20px rgba(255, 255, 255, 0.4)); }
            to { filter: drop-shadow(0 0 40px rgba(240, 147, 251, 0.6)); }
        }

        .hero-subtitle {
            font-size: 1.5rem;
            font-weight: 500;
            margin-bottom: 3rem;
            opacity: 0.95;
            line-height: 1.6;
            animation: fadeInUp 1s ease-out 0.2s both;
            color: rgba(255, 255, 255, 0.9);
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
        }

        .hero-buttons {
            display: flex;
            gap: 1.5rem;
            flex-wrap: wrap;
            animation: fadeInUp 1s ease-out 0.4s both;
        }

        .btn-hero-primary {
            background: linear-gradient(135deg, #ffffff, rgba(255, 255, 255, 0.9));
            color: var(--hero-primary);
            padding: 1rem 2.5rem;
            border-radius: 50px;
            font-weight: 700;
            text-decoration: none;
            transition: all 0.4s ease;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2), 
                        inset 0 1px 0 rgba(255, 255, 255, 0.5);
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
            position: relative;
            overflow: hidden;
            transform-style: preserve-3d;
        }

        .btn-hero-primary::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.4), transparent);
            transition: left 0.6s ease;
        }

        .btn-hero-primary:hover::before {
            left: 100%;
        }

        .btn-hero-primary:hover {
            transform: translateY(-5px) rotateX(-10deg);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3), 
                        inset 0 1px 0 rgba(255, 255, 255, 0.6);
        }

        .btn-hero-secondary {
            background: var(--glass-bg);
            color: white;
            padding: 1rem 2.5rem;
            border-radius: 50px;
            font-weight: 700;
            text-decoration: none;
            transition: all 0.4s ease;
            border: 2px solid var(--glass-border);
            backdrop-filter: blur(20px);
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
            position: relative;
            overflow: hidden;
            transform-style: preserve-3d;
        }

        .btn-hero-secondary::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.6s ease;
        }

        .btn-hero-secondary:hover::before {
            left: 100%;
        }

        .btn-hero-secondary:hover {
            background: rgba(255, 255, 255, 0.25);
            transform: translateY(-5px) rotateX(-10deg);
            border-color: rgba(255, 255, 255, 0.4);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
        }

        .hero-visual {
            position: relative;
            display: flex;
            justify-content: center;
            align-items: center;
            animation: fadeInUp 1s ease-out 0.6s both;
            transform-style: preserve-3d;
            perspective: 1000px;
        }

        .hero-card {
            background: var(--glass-bg);
            backdrop-filter: blur(25px);
            border-radius: 30px;
            padding: 3rem;
            border: 1px solid var(--glass-border);
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.2), 
                        inset 0 0 30px rgba(255, 255, 255, 0.1);
            position: relative;
            overflow: hidden;
            transform: rotateY(5deg) rotateX(-5deg);
            transition: transform 0.6s ease;
            animation: cardFloat 6s ease-in-out infinite;
        }

        .hero-card:hover {
            transform: rotateY(0deg) rotateX(0deg) scale(1.05);
        }

        @keyframes cardFloat {
            0%, 100% {
                transform: rotateY(5deg) rotateX(-5deg) translateY(0);
            }
            50% {
                transform: rotateY(-5deg) rotateX(5deg) translateY(-20px);
            }
        }

        .hero-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #ffffff, rgba(240, 147, 251, 0.8), rgba(79, 172, 254, 0.8));
            animation: shimmer 3s ease-in-out infinite;
        }

        @keyframes shimmer {
            0%, 100% { opacity: 0.8; }
            50% { opacity: 1; }
        }

        .hero-icon {
            font-size: 8rem;
            background: linear-gradient(135deg, #ffffff, rgba(240, 147, 251, 0.9), rgba(79, 172, 254, 0.9));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-align: center;
            margin-bottom: 2rem;
            animation: iconPulse 2s ease-in-out infinite;
            filter: drop-shadow(0 0 30px rgba(255, 255, 255, 0.5));
        }

        @keyframes iconPulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }

        .hero-features {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
        }

        .hero-feature {
            display: flex;
            align-items: center;
            gap: 1rem;
            color: rgba(255, 255, 255, 0.9);
            transition: all 0.3s ease;
            padding: 0.5rem;
            border-radius: 10px;
        }

        .hero-feature:hover {
            color: white;
            background: rgba(255, 255, 255, 0.1);
            transform: translateX(5px);
        }

        .hero-feature i {
            font-size: 1.5rem;
            opacity: 0.9;
            background: linear-gradient(135deg, #ffffff, rgba(240, 147, 251, 0.9));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        /* Features Section */
        .features {
            padding: 8rem 0;
            background: linear-gradient(135deg, rgba(248, 250, 252, 0.95), rgba(255, 255, 255, 0.9));
            position: relative;
            backdrop-filter: blur(10px);
        }

        .features::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: radial-gradient(circle at 30% 20%, rgba(102, 126, 234, 0.1) 0%, transparent 50%),
                        radial-gradient(circle at 70% 80%, rgba(118, 75, 162, 0.1) 0%, transparent 50%);
            z-index: 0;
        }

        .features-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
            position: relative;
            z-index: 1;
        }

        .section-header {
            text-align: center;
            margin-bottom: 4rem;
        }

        .section-title {
            font-size: 3.5rem;
            font-weight: 900;
            color: var(--dark-color);
            margin-bottom: 1rem;
            background: linear-gradient(135deg, var(--hero-primary), var(--hero-secondary), var(--hero-accent));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            animation: titleGlow 3s ease-in-out infinite alternate;
        }

        @keyframes titleGlow {
            from { filter: drop-shadow(0 0 20px rgba(102, 126, 234, 0.3)); }
            to { filter: drop-shadow(0 0 40px rgba(240, 147, 251, 0.5)); }
        }

        .section-subtitle {
            font-size: 1.25rem;
            color: var(--gray-600);
            font-weight: 500;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 2rem;
        }

        .feature-card {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(20px);
            border-radius: 25px;
            padding: 2.5rem;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1), 
                        inset 0 1px 0 rgba(255, 255, 255, 0.5);
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.4s ease;
            position: relative;
            overflow: hidden;
            transform-style: preserve-3d;
            perspective: 1000px;
        }

        .feature-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--hero-primary), var(--hero-secondary), var(--hero-accent));
            animation: shimmer 3s ease-in-out infinite;
        }

        .feature-card:hover {
            transform: translateY(-12px) rotateX(5deg) scale(1.02);
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15), 
                        inset 0 1px 0 rgba(255, 255, 255, 0.7);
        }

        .feature-card::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: radial-gradient(circle at var(--mouse-x, 50%) var(--mouse-y, 50%), rgba(102, 126, 234, 0.1) 0%, transparent 50%);
            opacity: 0;
            transition: opacity 0.3s ease;
            pointer-events: none;
        }

        .feature-card:hover::after {
            opacity: 1;
        }

        .feature-icon {
            width: 90px;
            height: 90px;
            border-radius: 25px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 2rem;
            font-size: 2.5rem;
            color: white;
            background: linear-gradient(135deg, var(--hero-primary), var(--hero-secondary), var(--hero-accent));
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3), 
                        inset 0 1px 0 rgba(255, 255, 255, 0.3);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .feature-icon::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(45deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transform: rotate(45deg);
            transition: all 0.6s ease;
            opacity: 0;
        }

        .feature-card:hover .feature-icon {
            transform: scale(1.1) rotateY(180deg);
            box-shadow: 0 15px 40px rgba(102, 126, 234, 0.4);
        }

        .feature-card:hover .feature-icon::before {
            animation: iconShine 0.6s ease;
            opacity: 1;
        }

        @keyframes iconShine {
            0% { transform: translateX(-100%) translateY(-100%) rotate(45deg); }
            100% { transform: translateX(100%) translateY(100%) rotate(45deg); }
        }

        .feature-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--dark-color);
            margin-bottom: 1rem;
        }

        .feature-description {
            color: var(--gray-600);
            line-height: 1.6;
            margin-bottom: 1.5rem;
        }

        .feature-link {
            color: var(--hero-primary);
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: gap var(--transition);
        }

        .feature-link:hover {
            gap: 1rem;
            color: var(--hero-secondary);
            text-shadow: 0 0 10px rgba(118, 75, 162, 0.3);
        }

        /* Stats Section */
        .stats {
            padding: 8rem 0;
            background: linear-gradient(135deg, var(--hero-primary) 0%, var(--hero-secondary) 50%, var(--hero-accent) 100%);
            position: relative;
            overflow: hidden;
            backdrop-filter: blur(10px);
        }

        .stats::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: radial-gradient(circle at 20% 50%, rgba(255, 255, 255, 0.1) 0%, transparent 50%),
                        radial-gradient(circle at 80% 20%, rgba(255, 255, 255, 0.05) 0%, transparent 50%),
                        radial-gradient(circle at 40% 80%, rgba(255, 255, 255, 0.08) 0%, transparent 50%);
            opacity: 0.6;
            animation: statsBgFloat 20s ease-in-out infinite;
        }

        @keyframes statsBgFloat {
            0%, 100% { transform: translateY(0) scale(1); }
            50% { transform: translateY(-20px) scale(1.05); }
        }

        .stats-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
            position: relative;
            z-index: 2;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 3rem;
        }

        .stat-card {
            text-align: center;
            color: white;
            padding: 2rem;
            border-radius: 20px;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.4s ease;
            transform-style: preserve-3d;
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: radial-gradient(circle at var(--mouse-x, 50%) var(--mouse-y, 50%), rgba(255, 255, 255, 0.1) 0%, transparent 50%);
            opacity: 0;
            transition: opacity 0.3s ease;
            pointer-events: none;
        }

        .stat-card:hover {
            transform: translateY(-10px) rotateX(5deg) scale(1.05);
            background: rgba(255, 255, 255, 0.15);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
        }

        .stat-card:hover::after {
            opacity: 1;
        }

        .stat-number {
            font-size: 4.5rem;
            font-weight: 900;
            margin-bottom: 1rem;
            background: linear-gradient(135deg, #ffffff, rgba(255, 255, 255, 0.8), rgba(240, 147, 251, 0.9));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            animation: numberGlow 2s ease-in-out infinite alternate;
            text-shadow: 0 0 30px rgba(255, 255, 255, 0.3);
        }

        @keyframes numberGlow {
            from { filter: drop-shadow(0 0 20px rgba(255, 255, 255, 0.4)); }
            to { filter: drop-shadow(0 0 40px rgba(240, 147, 251, 0.6)); }
        }

        .stat-label {
            font-size: 1.25rem;
            font-weight: 600;
            opacity: 0.95;
            text-transform: uppercase;
            letter-spacing: 1px;
            animation: fadeInUp 1s ease-out;
        }

        /* CTA Section */
        .cta {
            padding: 8rem 0;
            background: linear-gradient(135deg, rgba(248, 250, 252, 0.95), rgba(255, 255, 255, 0.9));
            text-align: center;
            position: relative;
            backdrop-filter: blur(10px);
        }

        .cta::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: radial-gradient(circle at 50% 50%, rgba(102, 126, 234, 0.1) 0%, transparent 70%);
            z-index: 0;
        }

        .cta-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 0 2rem;
            position: relative;
            z-index: 1;
        }

        .cta-title {
            font-size: 3.5rem;
            font-weight: 900;
            color: var(--dark-color);
            margin-bottom: 2rem;
            background: linear-gradient(135deg, var(--hero-primary), var(--hero-secondary), var(--hero-accent));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            animation: titleGlow 3s ease-in-out infinite alternate;
        }

        .cta-description {
            font-size: 1.25rem;
            color: var(--gray-600);
            margin-bottom: 3rem;
            line-height: 1.6;
            opacity: 0.9;
        }

        .cta-buttons {
            display: flex;
            gap: 1.5rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        .cta-btn {
            padding: 1.2rem 3rem;
            border-radius: 50px;
            font-weight: 700;
            text-decoration: none;
            transition: all 0.4s ease;
            position: relative;
            overflow: hidden;
            transform-style: preserve-3d;
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
        }

        .cta-btn-primary {
            background: linear-gradient(135deg, var(--hero-primary), var(--hero-secondary));
            color: white;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
        }

        .cta-btn-secondary {
            background: rgba(255, 255, 255, 0.9);
            color: var(--hero-primary);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .cta-btn:hover {
            transform: translateY(-5px) rotateX(-10deg);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
        }

        .cta-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transition: left 0.6s ease;
        }

        .cta-btn:hover::before {
            left: 100%;
        }

        /* Footer */
        .footer {
            background: linear-gradient(135deg, #1a202c, #2d3748);
            color: var(--white);
            padding: 4rem 0 2rem;
            position: relative;
            overflow: hidden;
        }

        .footer::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: radial-gradient(circle at 30% 20%, rgba(102, 126, 234, 0.1) 0%, transparent 50%),
                        radial-gradient(circle at 70% 80%, rgba(118, 75, 162, 0.1) 0%, transparent 50%);
            z-index: 0;
        }

        .footer-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
            position: relative;
            z-index: 1;
        }

        .footer-content {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 3rem;
            margin-bottom: 3rem;
        }

        .footer-section h3 {
            font-size: 1.25rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            color: white;
            background: linear-gradient(135deg, var(--hero-primary), var(--hero-secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .footer-links {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .footer-links li {
            margin-bottom: 1rem;
        }

        .footer-links a {
            color: var(--gray-400);
            text-decoration: none;
            transition: color var(--transition);
        }

        .footer-links a:hover {
            color: var(--hero-primary);
            text-shadow: 0 0 10px rgba(102, 126, 234, 0.3);
            transform: translateX(5px);
        }

        .footer-bottom {
            border-top: 1px solid var(--gray-700);
            padding-top: 2rem;
            text-align: center;
            color: var(--gray-400);
        }

        /* Mobile Menu */
        .mobile-menu-toggle {
            display: none;
            background: none;
            border: none;
            font-size: 1.5rem;
            color: var(--hero-primary);
            cursor: pointer;
        }

        /* Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Job Postings Section */
        .jobs-section {
            padding: 8rem 0;
            background: var(--white);
            position: relative;
        }

        .jobs-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(380px, 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
        }

        .job-card {
            background: var(--card-bg);
            border-radius: var(--radius-xl);
            padding: 2rem;
            box-shadow: var(--shadow);
            border: 1px solid var(--gray-200);
            transition: all var(--transition);
            position: relative;
            overflow: hidden;
        }

        .job-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--hero-primary), var(--hero-secondary));
        }

        .job-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--shadow-xl);
        }

        .job-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        .job-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--dark-color);
            margin-bottom: 1rem;
            line-height: 1.3;
        }

        .job-meta {
            display: flex;
            gap: 1.5rem;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
            font-size: 0.875rem;
            color: var(--gray-600);
        }

        .job-meta span {
            display: flex;
            align-items: center;
        }

        .job-description {
            color: var(--gray-600);
            line-height: 1.6;
            margin-bottom: 1.5rem;
        }

        .job-requirements {
            margin-bottom: 1.5rem;
        }

        .job-requirements h4 {
            font-size: 1rem;
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 0.75rem;
        }

        .job-requirements ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .job-requirements li {
            position: relative;
            padding-left: 1.5rem;
            margin-bottom: 0.5rem;
            color: var(--gray-600);
            font-size: 0.875rem;
        }

        .job-requirements li::before {
            content: '✓';
            position: absolute;
            left: 0;
            color: var(--success-color);
            font-weight: bold;
        }

        .job-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 1rem;
            border-top: 1px solid var(--gray-200);
        }

        .job-posted {
            color: var(--gray-500);
            font-size: 0.875rem;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .nav-links {
                display: none;
            }

            .mobile-menu-toggle {
                display: block;
            }

            .hero-content {
                grid-template-columns: 1fr;
                gap: 2rem;
                padding: 2rem 1rem;
            }

            .hero-title {
                font-size: 2.5rem;
            }

            .hero-subtitle {
                font-size: 1.25rem;
            }

            .hero-buttons {
                justify-content: center;
            }

            .hero-features {
                grid-template-columns: 1fr;
            }

            .section-title {
                font-size: 2rem;
            }

            .features-grid {
                grid-template-columns: 1fr;
            }

            .jobs-grid {
                grid-template-columns: 1fr;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 2rem;
            }

            .stat-number {
                font-size: 3rem;
            }

            .cta-title {
                font-size: 2rem;
            }

            .footer-content {
                grid-template-columns: 1fr;
                gap: 2rem;
            }

            .nav-container {
                padding: 0 1rem;
            }
        }

        @media (max-width: 480px) {
            .hero-title {
                font-size: 2rem;
            }

            .section-title {
                font-size: 1.75rem;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .cta-buttons {
                flex-direction: column;
                align-items: center;
            }

            .btn-hero-primary,
            .btn-hero-secondary {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <!-- Animated Background -->
    <div class="animated-bg"></div>
    
    <!-- Floating Particles -->
    <div class="particles" id="particles"></div>
    <!-- Navigation -->
    <nav class="landing-nav" id="navbar">
        <div class="nav-container">
            <a href="#" class="nav-logo">
                <i class="fas fa-users-cog"></i>
                <span>HR System</span>
            </a>
            
            <ul class="nav-links">
                <li><a href="#features" class="nav-link">Features</a></li>
               
                <li><a href="#stats" class="nav-link">Statistics</a></li>
                <li><a href="#about" class="nav-link">About</a></li>
                <li><a href="#contact" class="nav-link">Contact</a></li>
            </ul>
            
            <div class="nav-buttons">
                <a href="login.php" class="btn btn-primary btn-sm">
                    <i class="fas fa-sign-in-alt me-2"></i>Admin Login
                </a>
                <a href="employee/login.php" class="btn btn-outline-light btn-sm">
                    <i class="fas fa-user me-2"></i>Employee Portal
                </a>
                <button class="mobile-menu-toggle" id="mobileMenuToggle">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero">
        <!-- Background Elements -->
        <div class="hero-bg-elements">
            <div class="floating-shape shape-1"></div>
            <div class="floating-shape shape-2"></div>
            <div class="floating-shape shape-3"></div>
        </div>
        
        <div class="hero-content">
            <div class="hero-text">
                <h1 class="hero-title">Transform Your HR Management</h1>
                <p class="hero-subtitle">Streamline operations, empower employees, and drive business growth with our comprehensive HR solution.</p>
                <div class="hero-buttons">
                    <a href="login.php" class="btn-hero-primary">
                        <i class="fas fa-rocket"></i>
                        Get Started Now
                    </a>
                    <a href="#features" class="btn-hero-secondary">
                        <i class="fas fa-play-circle"></i>
                        Watch Demo
                    </a>
                </div>
            </div>
            
            <div class="hero-visual">
                <div class="hero-card">
                    <div class="hero-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="hero-features">
                        <div class="hero-feature">
                            <i class="fas fa-check-circle"></i>
                            <span>Employee Management</span>
                        </div>
                        <div class="hero-feature">
                            <i class="fas fa-check-circle"></i>
                            <span>Attendance Tracking</span>
                        </div>
                        <div class="hero-feature">
                            <i class="fas fa-check-circle"></i>
                            <span>Payroll Processing</span>
                        </div>
                        <div class="hero-feature">
                            <i class="fas fa-check-circle"></i>
                            <span>Advanced Analytics</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features" id="features">
        <div class="features-container">
            <div class="section-header">
                <h2 class="section-title">Powerful Features</h2>
                <p class="section-subtitle">Everything you need to manage your workforce efficiently</p>
            </div>
            
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <h3 class="feature-title">Employee Management</h3>
                    <p class="feature-description">Complete employee database with profiles, contracts, document management, and lifecycle tracking.</p>
                    <a href="#" class="feature-link">
                        Learn more <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <h3 class="feature-title">Attendance & Time Tracking</h3>
                    <p class="feature-description">Real-time attendance monitoring with biometric integration, leave management, and timesheet tracking.</p>
                    <a href="#" class="feature-link">
                        Learn more <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                    <h3 class="feature-title">Payroll Processing</h3>
                    <p class="feature-description">Automated payroll calculations with tax deductions, benefits management, and direct deposit support.</p>
                    <a href="#" class="feature-link">
                        Learn more <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-tasks"></i>
                    </div>
                    <h3 class="feature-title">Task Management</h3>
                    <p class="feature-description">Assign and track tasks, monitor progress, and improve team collaboration with our task management system.</p>
                    <a href="#" class="feature-link">
                        Learn more <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-chart-bar"></i>
                    </div>
                    <h3 class="feature-title">Analytics & Reports</h3>
                    <p class="feature-description">Comprehensive reporting and analytics for data-driven HR decisions and business insights.</p>
                    <a href="#" class="feature-link">
                        Learn more <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <h3 class="feature-title">Security & Compliance</h3>
                    <p class="feature-description">Enterprise-grade security with role-based access control, data encryption, and compliance management.</p>
                    <a href="#" class="feature-link">
                        Learn more <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Job Postings Section -->
    <section class="jobs-section" id="jobs">
        <div class="features-container">
            <div class="section-header">
                <h2 class="section-title">Current Job Openings</h2>
                <p class="section-subtitle">Join our team and grow your career with exciting opportunities</p>
            </div>
            
            <div class="jobs-grid">
               
                
                <div class="job-card">
                    <div class="job-header">
                        <div class="job-type badge badge-primary">Full-time</div>
                        <div class="job-department badge badge-secondary">HR</div>
                    </div>
                    <h3 class="job-title">HR Business Partner</h3>
                    <div class="job-meta">
                        <span><i class="fas fa-map-marker-alt me-2"></i>Remote</span>
                        <span><i class="fas fa-dollar-sign me-2"></i>$80k - $100k</span>
                    </div>
                    <p class="job-description">
                        Partner with business leaders to develop and implement HR strategies that align with organizational goals.
                    </p>
                    <div class="job-requirements">
                        <h4>Requirements:</h4>
                        <ul>
                            <li>4+ years of HR experience with focus on business partnership</li>
                            <li>Strong knowledge of HR best practices and employment law</li>
                            <li>Excellent communication and relationship-building skills</li>
                        </ul>
                    </div>
                    <div class="job-footer">
                        <span class="job-posted">Posted 1 week ago</span>
                        <a href="#" class="btn btn-primary btn-sm">Apply Now</a>
                    </div>
                </div>
                
                <div class="job-card">
                    <div class="job-header">
                        <div class="job-type badge badge-info">Contract</div>
                        <div class="job-department badge badge-primary">Design</div>
                    </div>
                    <h3 class="job-title">UX/UI Designer</h3>
                    <div class="job-meta">
                        <span><i class="fas fa-map-marker-alt me-2"></i>Austin, TX</span>
                        <span><i class="fas fa-dollar-sign me-2"></i>$70k - $90k</span>
                    </div>
                    <p class="job-description">
                        Create beautiful and intuitive user interfaces for our HR management platform and related products.
                    </p>
                    <div class="job-requirements">
                        <h4>Requirements:</h4>
                        <ul>
                            <li>3+ years of UX/UI design experience</li>
                            <li>Proficiency in Figma, Adobe Creative Suite, and prototyping tools</li>
                            <li>Strong portfolio demonstrating user-centered design process</li>
                        </ul>
                    </div>
                    <div class="job-footer">
                        <span class="job-posted">Posted 3 days ago</span>
                        <a href="#" class="btn btn-primary btn-sm">Apply Now</a>
                    </div>
                </div>
                
                <div class="job-card">
                    <div class="job-header">
                        <div class="job-type badge badge-primary">Full-time</div>
                        <div class="job-department badge badge-success">Sales</div>
                    </div>
                    <h3 class="job-title">Sales Representative</h3>
                    <div class="job-meta">
                        <span><i class="fas fa-map-marker-alt me-2"></i>Chicago, IL</span>
                        <span><i class="fas fa-dollar-sign me-2"></i>$60k - $80k + Commission</span>
                    </div>
                    <p class="job-description">
                        Drive sales growth by identifying new business opportunities and building strong client relationships.
                    </p>
                    <div class="job-requirements">
                        <h4>Requirements:</h4>
                        <ul>
                            <li>2+ years of B2B sales experience</li>
                            <li>Proven track record of meeting and exceeding sales targets</li>
                            <li>Excellent communication and negotiation skills</li>
                        </ul>
                    </div>
                    <div class="job-footer">
                        <span class="job-posted">Posted 4 days ago</span>
                        <a href="#" class="btn btn-primary btn-sm">Apply Now</a>
                    </div>
                </div>
                
                <div class="job-card">
                    <div class="job-header">
                        <div class="job-type badge badge-warning">Part-time</div>
                        <div class="job-department badge badge-info">Support</div>
                    </div>
                    <h3 class="job-title">Customer Support Specialist</h3>
                    <div class="job-meta">
                        <span><i class="fas fa-map-marker-alt me-2"></i>Remote</span>
                        <span><i class="fas fa-dollar-sign me-2"></i>$40k - $50k</span>
                    </div>
                    <p class="job-description">
                        Provide exceptional customer support to our clients and help them maximize the value of our HR solutions.
                    </p>
                    <div class="job-requirements">
                        <h4>Requirements:</h4>
                        <ul>
                            <li>1+ years of customer support experience</li>
                            <li>Excellent problem-solving and communication skills</li>
                            <li>Experience with CRM systems and support tools</li>
                        </ul>
                    </div>
                    <div class="job-footer">
                        <span class="job-posted">Posted 6 days ago</span>
                        <a href="#" class="btn btn-primary btn-sm">Apply Now</a>
                    </div>
                </div>
            </div>
            
            <div class="text-center mt-5">
                <a href="#" class="btn btn-outline-primary btn-lg">
                    <i class="fas fa-briefcase me-2"></i>View All Job Openings
                </a>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="stats" id="stats">
        <div class="stats-container">
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number">500+</div>
                    <div class="stat-label">Companies Trust Us</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">50K+</div>
                    <div class="stat-label">Employees Managed</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">99.9%</div>
                    <div class="stat-label">Uptime Guaranteed</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">24/7</div>
                    <div class="stat-label">Expert Support</div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta" id="about">
        <div class="cta-container">
            <h2 class="cta-title">Ready to Transform Your HR?</h2>
            <p class="cta-description">
                Join thousands of companies that have streamlined their HR operations with our comprehensive management solution. 
                Get started today and experience the difference.
            </p>
            <div class="cta-buttons">
                <a href="login.php" class="cta-btn cta-btn-primary">
                    <i class="fas fa-rocket"></i>Start Free Trial
                </a>
                <a href="#" class="cta-btn cta-btn-secondary">
                    <i class="fas fa-phone"></i>Schedule Demo
                </a>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer" id="contact">
        <div class="footer-container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>HR Management System</h3>
                    <p style="color: var(--gray-400); margin-bottom: 1.5rem;">
                        Modern HR solution for growing businesses. Streamline your operations and empower your team.
                    </p>
                    <div style="display: flex; gap: 1rem;">
                        <a href="#" style="color: var(--gray-400); font-size: 1.25rem;">
                            <i class="fab fa-facebook"></i>
                        </a>
                        <a href="#" style="color: var(--gray-400); font-size: 1.25rem;">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="#" style="color: var(--gray-400); font-size: 1.25rem;">
                            <i class="fab fa-linkedin"></i>
                        </a>
                        <a href="#" style="color: var(--gray-400); font-size: 1.25rem;">
                            <i class="fab fa-instagram"></i>
                        </a>
                    </div>
                </div>
                
                <div class="footer-section">
                    <h3>Quick Links</h3>
                    <ul class="footer-links">
                        <li><a href="#features">Features</a></li>
                        <li><a href="#jobs">Jobs</a></li>
                        <li><a href="#stats">Statistics</a></li>
                        <li><a href="#about">About Us</a></li>
                        <li><a href="login.php">Admin Login</a></li>
                        <li><a href="employee/login.php">Employee Portal</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h3>Contact Info</h3>
                    <ul class="footer-links">
                        <li>
                            <i class="fas fa-envelope me-2"></i>
                            support@hrsystem.com
                        </li>
                        <li>
                            <i class="fas fa-phone me-2"></i>
                            +1 (555) 123-4567
                        </li>
                        <li>
                            <i class="fas fa-map-marker-alt me-2"></i>
                            123 Business St, Suite 100<br>
                            New York, NY 10001
                        </li>
                    </ul>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; 2026 HR Management System. All rights reserved. | 
                    <a href="#" style="color: var(--gray-400);">Privacy Policy</a> | 
                    <a href="#" style="color: var(--gray-400);">Terms of Service</a>
                </p>
            </div>
        </div>
    </footer>

    <script>
        // Navbar scroll effect
        window.addEventListener('scroll', function() {
            const navbar = document.getElementById('navbar');
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });

        // Smooth scrolling for navigation links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    const offsetTop = target.offsetTop - 80;
                    window.scrollTo({
                        top: offsetTop,
                        behavior: 'smooth'
                    });
                }
            });
        });

        // Mobile menu toggle
        document.getElementById('mobileMenuToggle').addEventListener('click', function() {
            // This would typically show/hide a mobile menu
            alert('Mobile menu would be implemented here');
        });

        // Intersection Observer for animations
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver(function(entries) {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, observerOptions);

        // Observe feature cards and stats
        document.querySelectorAll('.feature-card, .stat-card, .job-card').forEach(card => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(30px)';
            card.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
            observer.observe(card);
        });

        // Counter animation for stats
        function animateCounter(element, target) {
            let current = 0;
            const increment = target / 100;
            const timer = setInterval(() => {
                current += increment;
                if (current >= target) {
                    current = target;
                    clearInterval(timer);
                }
                element.textContent = Math.floor(current) + (element.textContent.includes('+') ? '+' : '') + (element.textContent.includes('%') ? '%' : '');
            }, 20);
        }

        // Trigger counter animation when stats section is visible
        const statsObserver = new IntersectionObserver(function(entries) {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const statNumbers = entry.target.querySelectorAll('.stat-number');
                    statNumbers.forEach(stat => {
                        const text = stat.textContent;
                        const number = parseInt(text.replace(/\D/g, ''));
                        if (!isNaN(number)) {
                            animateCounter(stat, number);
                        }
                    });
                    statsObserver.unobserve(entry.target);
                }
            });
        }, { threshold: 0.5 });

        const statsSection = document.querySelector('.stats');
        if (statsSection) {
            statsObserver.observe(statsSection);
        }

        // Enhanced Particle System
        function createParticles() {
            const particlesContainer = document.getElementById('particles');
            if (!particlesContainer) return;
            
            const particleCount = 50;
            
            for (let i = 0; i < particleCount; i++) {
                const particle = document.createElement('div');
                particle.className = 'particle';
                
                // Random size
                const size = Math.random() * 6 + 2;
                particle.style.width = size + 'px';
                particle.style.height = size + 'px';
                
                // Random position
                particle.style.left = Math.random() * 100 + '%';
                particle.style.top = Math.random() * 100 + '%';
                
                // Random animation delay
                particle.style.animationDelay = Math.random() * 20 + 's';
                particle.style.animationDuration = (Math.random() * 20 + 10) + 's';
                
                particlesContainer.appendChild(particle);
            }
        }

        // Initialize AOS library if available
        if (typeof AOS !== 'undefined') {
            AOS.init({
                duration: 1000,
                once: true,
                offset: 100
            });
        }

        // 3D Card Mouse Tracking Effect
        document.addEventListener('mousemove', (e) => {
            const cards = document.querySelectorAll('.hero-card');
            const x = e.clientX / window.innerWidth;
            const y = e.clientY / window.innerHeight;
            
            cards.forEach(card => {
                const rect = card.getBoundingClientRect();
                const cardX = (rect.left + rect.width / 2) / window.innerWidth;
                const cardY = (rect.top + rect.height / 2) / window.innerHeight;
                
                const angleX = (y - cardY) * 15;
                const angleY = (cardX - x) * 15;
                
                card.style.transform = `rotateX(${angleX}deg) rotateY(${angleY}deg) scale(1.02)`;
            });
        });

        // Reset card transform on mouse leave
        document.addEventListener('mouseleave', () => {
            const cards = document.querySelectorAll('.hero-card');
            cards.forEach(card => {
                card.style.transform = '';
            });
        });

        // Initialize particles on page load
        document.addEventListener('DOMContentLoaded', createParticles);
        
        // Enhanced smooth scrolling
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
