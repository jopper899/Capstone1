<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Arandia College eLMS — Electronic Learning Management System</title>
  <link
    href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&family=Inter:wght@400;500;600&display=swap"
    rel="stylesheet">
  <link rel="stylesheet" href="https://ka-f.webawesome.com/webawesome@3.7.0/styles/native.css">
  <script type="module" src="https://ka-f.webawesome.com/webawesome@3.7.0/webawesome.loader.js"></script>
  <style>
    :root {
      --primary: #003087;
      --primary-dark: #001a4d;
      --primary-light: #0044cc;
      --accent: #FFD700;
      --text-dark: #0f172a;
      --text-light: #64748b;
      --bg-light: #f8fafc;
      --white: #ffffff;
      --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
      --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
      --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
      --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
    }

    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    html {
      scroll-behavior: smooth;
    }

    body {
      font-family: 'Inter', sans-serif;
      background: var(--bg-light);
      color: var(--text-dark);
      line-height: 1.5;
      overflow-x: hidden;
    }

    /* ─── TOPBAR ─── */
    .topbar {
      background: var(--primary-dark);
      color: rgba(255, 255, 255, 0.8);
      font-size: 0.75rem;
      padding: 0.5rem 5%;
      display: flex;
      justify-content: flex-end;
      gap: 1rem;
      font-weight: 500;
    }

    .topbar a {
      color: rgba(255, 255, 255, 0.8);
      text-decoration: none;
      transition: color 0.2s;
    }

    .topbar a:hover {
      color: var(--accent);
    }

    /* ─── NAV ─── */
    nav {
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(12px);
      box-shadow: var(--shadow-sm);
      position: sticky;
      top: 0;
      z-index: 1000;
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 0 5%;
      height: 80px;
      border-bottom: 1px solid rgba(0, 0, 0, 0.05);
    }

    .nav-brand {
      display: flex;
      align-items: center;
      gap: 1rem;
      text-decoration: none;
    }

    .nav-logo-svg {
      width: 50px;
      height: 50px;
      object-fit: contain;
    }

    .nav-brand-text {
      line-height: 1.1;
    }

    .nav-brand-text strong {
      display: block;
      font-family: 'Nunito', sans-serif;
      font-size: 1.1rem;
      font-weight: 900;
      color: var(--primary);
      letter-spacing: -0.5px;
    }

    .nav-brand-text span {
      font-size: 0.75rem;
      color: var(--text-light);
      font-weight: 500;
    }

    .nav-links {
      display: flex;
      align-items: center;
      list-style: none;
      gap: 0.5rem;
    }

    .nav-links a {
      font-size: 0.9rem;
      font-weight: 600;
      color: var(--text-dark);
      text-decoration: none;
      padding: 0.6rem 1.2rem;
      border-radius: 8px;
      transition: background .2s;
    }

    .nav-links a:hover {
      background: #eff6ff;
      color: var(--primary);
    }

    .btn-login {
      background: linear-gradient(135deg, var(--primary), var(--primary-light));
      color: white !important;
      padding: 0.6rem 1.8rem !important;
      border-radius: 50px !important;
      font-weight: 700 !important;
      box-shadow: 0 4px 14px rgba(0, 48, 135, 0.3);
      transition: all 0.2s !important;
    }

    .btn-login:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(0, 48, 135, 0.4) !important;
      background: var(--primary-dark) !important;
    }

    /* ─── HAMBURGER TOGGLE ─── */
    .mobile-menu-toggle {
      display: none;
      width: 40px;
      height: 40px;
      background: var(--primary);
      border: none;
      border-radius: 8px;
      cursor: pointer;
      align-items: center;
      justify-content: center;
      flex-direction: column;
      gap: 5px;
      padding: 10px;
      flex-shrink: 0;
      z-index: 1100;
    }

    .mobile-menu-toggle span {
      display: block;
      width: 20px;
      height: 2px;
      background: white;
      border-radius: 2px;
      transition: all 0.3s ease;
      transform-origin: center;
    }

    /* Animate to X when open */
    .mobile-menu-toggle.open span:nth-child(1) {
      transform: translateY(7px) rotate(45deg);
    }

    .mobile-menu-toggle.open span:nth-child(2) {
      opacity: 0;
      transform: scaleX(0);
    }

    .mobile-menu-toggle.open span:nth-child(3) {
      transform: translateY(-7px) rotate(-45deg);
    }

    /* ─── MOBILE DROPDOWN ─── */
    .mobile-dropdown {
      display: none;
      position: absolute;
      top: calc(100% + 8px);
      right: 5%;
      background: white;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
      border-radius: 16px;
      min-width: 220px;
      overflow: hidden;
      opacity: 0;
      transform: translateY(-10px);
      transition: opacity 0.25s ease, transform 0.25s ease;
      pointer-events: none;
      z-index: 1050;
    }

    .mobile-dropdown.mobile-active {
      opacity: 1;
      transform: translateY(0);
      pointer-events: auto;
    }

    .mobile-dropdown a {
      font-size: 0.95rem;
      font-weight: 600;
      color: var(--text-dark);
      text-decoration: none;
      display: block;
      padding: 14px 20px;
      border-bottom: 1px solid #f1f5f9;
      transition: background 0.2s;
    }

    .mobile-dropdown a:hover {
      background: #eff6ff;
      color: var(--primary);
    }

    .mobile-dropdown a:last-child {
      border-bottom: none;
    }

    .btn-mobile-login {
      background: linear-gradient(135deg, var(--primary), var(--primary-light)) !important;
      color: white !important;
      text-align: center;
      margin: 12px;
      border-radius: 50px !important;
      border-bottom: none !important;
    }

    .btn-mobile-login:hover {
      background: var(--primary-dark) !important;
      color: white !important;
    }

    /* ─── HERO ─── */
    .hero {
      background: var(--primary);
      padding: 5rem 5%;
      text-align: center;
      position: relative;
      overflow: hidden;
    }

    .hero-bubble {
      position: absolute;
      border-radius: 50%;
      background: rgba(255, 255, 255, 0.05);
      animation: floatBubble linear infinite;
    }

    .hero-bubble:nth-child(1)  { width: 300px; height: 300px; top: -80px;  left: -80px;  animation-duration: 0s; }
    .hero-bubble:nth-child(2)  { width: 180px; height: 180px; top: 30px;   right: 8%;    animation-duration: 0s; }
    .hero-bubble:nth-child(3)  { width: 120px; height: 120px; bottom: -40px; left: 15%;  animation-duration: 0s; }
    .hero-bubble:nth-child(4)  { width: 80px;  height: 80px;  bottom: 20px; right: 20%;  animation-duration: 0s; }
    .hero-bubble:nth-child(5)  { width: 220px; height: 220px; top: 50%;    right: -60px; animation-duration: 0s; }
    .hero-bubble:nth-child(6)  { width: 60px;  height: 60px;  top: 20%;    left: 10%;   animation-duration: 0s; }
    .hero-bubble:nth-child(7)  { width: 40px;  height: 40px;  top: 60%;    left: 25%;   animation-duration: 0s; }
    .hero-bubble:nth-child(8)  { width: 100px; height: 100px; bottom: 10%; right: 5%;   animation-duration: 0s; }

    .hero-bubble.gold {
      background: rgba(255, 215, 0, 0.07);
      border-color: rgba(255, 215, 0, 0.15);
    }

    .hero-content {
      position: relative;
      z-index: 2;
    }

    .hero-eyebrow {
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      background: rgba(255, 215, 0, 0.15);
      border: 1px solid rgba(255, 215, 0, 0.35);
      color: var(--accent);
      font-size: 0.75rem;
      font-weight: 700;
      letter-spacing: 0.1em;
      text-transform: uppercase;
      padding: 0.4rem 1.2rem;
      border-radius: 50px;
      margin-bottom: 1.5rem;
    }

    .hero-title {
      font-family: 'Nunito', sans-serif;
      font-size: 3rem;
      font-weight: 900;
      color: #fff;
      line-height: 1.15;
      margin: 0 0 1rem;
    }

    .hero-title span {
      color: var(--accent);
    }

    .hero-sub {
      font-size: 1.05rem;
      color: rgba(255, 255, 255, 0.65);
      line-height: 1.75;
      margin: 0 auto 2rem;
      max-width: 520px;
    }

    .hero-btns {
      display: flex;
      gap: 1rem;
      justify-content: center;
      flex-wrap: wrap;
    }

    .hero-btn-primary {
      background: var(--accent);
      color: var(--primary);
      border: none;
      padding: 0.85rem 2.2rem;
      border-radius: 50px;
      font-size: 1rem;
      font-weight: 800;
      cursor: pointer;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      transition: all 0.2s;
      font-family: 'Nunito', sans-serif;
    }

    .hero-btn-primary:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(255, 215, 0, 0.35);
    }

    .hero-btn-ghost {
      background: rgba(255, 255, 255, 0.1);
      color: #fff;
      border: 1px solid rgba(255, 255, 255, 0.3);
      padding: 0.85rem 2.2rem;
      border-radius: 50px;
      font-size: 1rem;
      font-weight: 700;
      cursor: pointer;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      transition: all 0.2s;
      font-family: 'Nunito', sans-serif;
    }

    .hero-btn-ghost:hover {
      background: rgba(255, 255, 255, 0.2);
      transform: translateY(-2px);
    }

    @media (max-width: 600px) {
      .hero-title {
        font-size: 2rem;
      }
      .hero {
        padding: 3.5rem 5%;
      }
    }

    /* ─── SECTION COMMONS ─── */
    .section-header {
      text-align: center;
      padding: 5rem 5% 3rem;
    }

    .section-eyebrow {
      display: inline-block;
      background: rgba(0, 48, 135, 0.1);
      color: var(--primary);
      font-size: 0.8rem;
      font-weight: 800;
      letter-spacing: 0.1em;
      text-transform: uppercase;
      padding: 0.4rem 1.2rem;
      border-radius: 50px;
      margin-bottom: 1rem;
    }

    .section-title {
      font-family: 'Nunito', sans-serif;
      font-size: 2.5rem;
      font-weight: 900;
      color: var(--text-dark);
      margin-bottom: 1rem;
      letter-spacing: -1px;
    }

    .section-sub {
      font-size: 1.1rem;
      color: var(--text-light);
      max-width: 600px;
      margin: 0 auto;
      line-height: 1.7;
    }

    /* ─── MISSION & VISION ─── */
    .mv-section {
      padding: 4rem 5% 6rem;
      background: white;
    }

    .mv-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 3rem;
      margin-top: 3rem;
    }

    .mv-card {
      background: var(--bg-light);
      border-radius: 24px;
      padding: 3rem 2.5rem;
      border: 1px solid rgba(0, 0, 0, 0.05);
      position: relative;
      transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
      overflow: hidden;
    }

    .mv-card:hover {
      transform: translateY(-10px);
      box-shadow: var(--shadow-xl);
      background: white;
    }

    .mv-card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 6px;
    }

    .mv-vision::before {
      background: linear-gradient(90deg, var(--primary), var(--primary-light));
    }

    .mv-mission::before {
      background: linear-gradient(90deg, #f59e0b, var(--accent));
    }

    .mv-icon-wrap {
      width: 72px;
      height: 72px;
      border-radius: 20px;
      display: flex;
      align-items: center;
      justify-content: center;
      margin-bottom: 1.5rem;
      filter: drop-shadow(0 10px 10px rgba(0, 0, 0, 0.1));
      position: relative;
      z-index: 1;
    }

    .mv-icon-wrap wa-icon {
      font-size: 34px;
    }

    .mv-label {
      display: inline-block;
      font-size: 0.75rem;
      font-weight: 800;
      letter-spacing: 0.1em;
      text-transform: uppercase;
      padding: 0.4rem 1rem;
      border-radius: 50px;
      margin-bottom: 1.5rem;
      position: relative;
      z-index: 1;
    }

    .mv-vision .mv-label {
      background: #e0e7ff;
      color: var(--primary);
    }

    .mv-mission .mv-label {
      background: #fef3c7;
      color: #b45309;
    }

    .mv-title {
      font-family: 'Nunito', sans-serif;
      font-size: 1.8rem;
      font-weight: 900;
      color: var(--text-dark);
      margin-bottom: 1.5rem;
    }

    .mv-text {
      font-size: 1rem;
      color: var(--text-light);
      line-height: 1.8;
    }

    /* ─── FEATURES ─── */
    .cards-section {
      padding: 4rem 5% 6rem;
      background: linear-gradient(to bottom, #ffffff, #f8fafc);
    }

    .cards-grid {
      display: flex;
      flex-wrap: wrap;
      gap: 2rem;
      margin-top: 3rem;
      justify-content: center;
    }

    .cards-grid .card {
      flex: 0 1 calc(25% - 1.5rem);
      min-width: 220px;
    }

    .card {
      background: white;
      border-radius: 20px;
      padding: 2.5rem 2rem;
      text-align: center;
      box-shadow: var(--shadow-sm);
      border: 1px solid rgba(0, 0, 0, 0.05);
      transition: all 0.3s;
      cursor: pointer;
      position: relative;
      z-index: 1;
    }

    .card:hover {
      transform: translateY(-8px);
      box-shadow: var(--shadow-lg);
      border-color: rgba(0, 48, 135, 0.1);
    }

    .card-1 .card-icon-wrap {
      background: #eff6ff;
      color: #003087;
    }

    .card-2 .card-icon-wrap {
      background: #fff1f2;
      color: #e11d48;
    }

    .card-3 .card-icon-wrap {
      background: #ecfdf5;
      color: #059669;
    }

    .card-4 .card-icon-wrap {
      background: #fffbeb;
      color: #d97706;
    }

    .card-5 .card-icon-wrap {
      background: #f5f3ff;
      color: #7c3aed;
    }

    .card-6 .card-icon-wrap {
      background: #ecfeff;
      color: #0891b2;
    }

    .card-7 .card-icon-wrap {
      background: #fff7ed;
      color: #ea580c;
    }

    .card-8 .card-icon-wrap {
      background: #f8fafc;
      color: #475569;
    }

    .card-icon-wrap {
      width: 80px;
      height: 80px;
      border-radius: 20px;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 1.5rem;
      transition: transform 0.3s;
    }

    .card-icon-wrap wa-icon {
      font-size: 36px;
    }

    .card:hover .card-icon-wrap {
      transform: rotate(-5deg) scale(1.1);
    }

    .card-title {
      font-family: 'Nunito', sans-serif;
      font-size: 1.2rem;
      font-weight: 800;
      color: var(--text-dark);
      margin-bottom: 0.75rem;
    }

    .card-desc {
      font-size: 0.95rem;
      color: var(--text-light);
      line-height: 1.6;
      margin-bottom: 1.5rem;
    }

    .card-link {
      display: inline-flex;
      align-items: center;
      font-size: 0.9rem;
      font-weight: 700;
      color: var(--primary);
      text-decoration: none;
      pointer-events: none;
      cursor: default;
      gap: 0.3rem;
    }

    /* ─── HOW IT WORKS ─── */
    .steps-section {
      padding: 5rem 5%;
      background: white;
      position: relative;
      overflow: hidden;
    }

    .steps-grid {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 2rem;
      margin-top: 4rem;
      position: relative;
    }

    .steps-grid::before {
      content: '';
      position: absolute;
      top: 40px;
      left: 15%;
      right: 15%;
      height: 2px;
      background: #e2e8f0;
      z-index: 0;
    }

    .step {
      text-align: center;
      position: relative;
      z-index: 1;
    }

    .step-num {
      width: 80px;
      height: 80px;
      border-radius: 50%;
      background: white;
      color: var(--primary);
      border: 3px solid var(--accent);
      font-family: 'Nunito', sans-serif;
      font-size: 1.8rem;
      font-weight: 900;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 1.5rem;
      box-shadow: var(--shadow-md);
      transition: all 0.3s;
    }

    .step:hover .step-num {
      background: var(--primary);
      color: white;
      transform: scale(1.1);
    }

    .step-title {
      font-family: 'Nunito', sans-serif;
      font-size: 1.2rem;
      font-weight: 800;
      color: var(--text-dark);
      margin-bottom: 0.75rem;
    }

    .step-desc {
      font-size: 0.95rem;
      color: var(--text-light);
      line-height: 1.6;
    }

    /* ─── FOOTER ─── */
    footer {
      background: #0f172a;
      color: #94a3b8;
      padding: 5rem 5% 2rem;
    }

    .footer-top {
      display: grid;
      grid-template-columns: 2fr 1fr 1fr 1fr;
      gap: 4rem;
      padding-bottom: 3rem;
      border-bottom: 1px solid rgba(255, 255, 255, 0.1);
      margin-bottom: 2rem;
    }

    .footer-brand-wrap {
      display: flex;
      align-items: flex-start;
      gap: 1.5rem;
    }

    .footer-logo {
      width: 60px;
      height: 60px;
      background: white;
      border-radius: 12px;
      padding: 5px;
      object-fit: contain;
      flex-shrink: 0;
    }

    .footer-brand strong {
      font-family: 'Nunito', sans-serif;
      font-size: 1.3rem;
      font-weight: 900;
      color: white;
      display: block;
      margin-bottom: 0.25rem;
    }

    .footer-brand .footer-brand-sub {
      font-size: 0.8rem;
      color: var(--accent);
      font-weight: 700;
      letter-spacing: 0.05em;
      display: block;
      margin-bottom: 1rem;
      text-transform: uppercase;
    }

    .footer-brand p {
      font-size: 0.9rem;
      line-height: 1.7;
    }

    .footer-col h4 {
      font-family: 'Nunito', sans-serif;
      font-weight: 800;
      color: white;
      font-size: 1rem;
      margin-bottom: 1.5rem;
    }

    .footer-col ul {
      list-style: none;
    }

    .footer-col li {
      margin-bottom: 0.75rem;
    }

    .footer-col span {
      color: #94a3b8;
      font-size: 0.9rem;
      cursor: default;
      pointer-events: none;
    }

    .footer-col a {
      color: #94a3b8;
      font-size: 0.9rem;
      text-decoration: none;
      transition: all 0.2s;
      display: block;
    }

    .footer-col a:hover {
      color: var(--accent);
      padding-left: 5px;
    }

    .footer-bottom {
      display: flex;
      align-items: center;
      justify-content: space-between;
      font-size: 0.85rem;
      flex-wrap: wrap;
      gap: 1.5rem;
    }

    /* ─── RESPONSIVE ─── */
    @media (max-width: 1024px) {

      .nav-links a {
        font-size: 0.82rem;
        padding: 0.5rem 0.8rem;
      }

      .section-title {
        font-size: 2rem;
      }

      .mv-grid {
        grid-template-columns: 1fr;
        gap: 1rem;
      }

      .mv-card {
        padding: 1.5rem 1.25rem;
        border-radius: 16px;
      }

      .mv-icon-wrap {
        font-size: 1.8rem;
        margin-bottom: 0.75rem;
      }

      .mv-title {
        font-size: 1.3rem;
      }

      .section-sub {
        font-size: 1rem;
      }

      .cards-grid .card {
        flex: 0 1 calc(50% - 1rem);
      }

      .steps-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 2rem;
      }

      .steps-grid::before {
        display: none;
      }

      .footer-top {
        grid-template-columns: 1fr 1fr;
        gap: 2.5rem;
      }
    }

    @media (max-width: 900px) {
      .nav-links {
        display: none;
      }

      .mobile-menu-toggle {
        display: flex;
      }

      .mobile-dropdown {
        display: block;
      }

      .mv-grid {
        grid-template-columns: 1fr 1fr;
        gap: 1.5rem;
      }

      .cards-grid .card {
        flex: 0 1 calc(50% - 0.5rem);
      }

      .footer-top {
        grid-template-columns: 1fr 1fr;
        gap: 2rem;
      }



      .section-title {
        font-size: 1.8rem;
      }
    }

    @media (max-width: 480px) {
      .cards-grid {
        gap: 0.9rem;
        margin-top: 2rem;
      }

      .cards-grid .card {
        flex: 0 1 calc(50% - 0.45rem);
        min-width: 0;
        padding: 1.25rem 0.9rem;
        border-radius: 16px;
      }

      .card-icon-wrap {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        margin: 0 auto 0.8rem;
      }

      .card-icon-wrap wa-icon {
        font-size: 22px;
      }

      .card-title {
        font-size: 0.9rem;
        margin-bottom: 0.4rem;
      }

      .card-desc {
        font-size: 0.75rem;
        line-height: 1.45;
        margin-bottom: 0.5rem;
      }

      .card-link {
        display: none;
      }

      .footer-top {
        grid-template-columns: 1fr;
      }

      .steps-grid {
        grid-template-columns: 1fr;
      }

      .mv-grid {
        grid-template-columns: 1fr;
        gap: 1.25rem;
        margin-top: 2rem;
      }

      .mv-card {
        padding: 1.75rem 1.5rem;
        border-radius: 18px;
      }

      .mv-title {
        font-size: 1.4rem;
        margin-bottom: 0.8rem;
      }

      .mv-text {
        font-size: 0.92rem;
      }

      .section-header {
        padding: 3rem 5% 2rem;
      }

      .section-sub {
        font-size: 0.95rem;
      }
    }
  </style>
</head>

<body>
  <!-- TOPBAR -->
  <div class="topbar">
    <a href="helpdesk.php">Campus Helpdesk</a>
    <a href="FAQ.php">FAQ</a>
    <a href="contact.php">Contact Us</a>
  </div>

  <!-- NAV -->
  <nav>
    <a href="#" class="nav-brand">
      <img src="picture/logo.jpg" alt="Arandia College Logo" class="nav-logo-svg">
      <div class="nav-brand-text">
        <strong>Arandia College eLMS</strong>
        <span>Electronic Learning Management System</span>
      </div>
    </a>

    <!-- Desktop Nav Links -->
    <ul class="nav-links">
      <li><a href="#">Home</a></li>
      <li><a href="#features">Features</a></li>
      <li><a href="#how">How It Works</a></li>
      <li><a href="#mv">Updates</a></li>
      <li><a href="login.php" class="btn-login">Log In</a></li>
    </ul>

    <!-- Hamburger Button (3 lines) -->
    <button class="mobile-menu-toggle" id="menuToggle" aria-label="Open menu">
      <span></span>
      <span></span>
      <span></span>
    </button>

    <!-- Mobile Dropdown Menu -->
    <div class="mobile-dropdown" id="mobileDropdown">
      <a href="#">Home</a>
      <a href="#features">Features</a>
      <a href="#how">How It Works</a>
      <a href="#mv">Updates</a>
      <a href="login.php" class="btn-mobile-login">Log In</a>
    </div>
  </nav>

  <!-- HERO -->
  <section class="hero">
    <div class="hero-bubble"></div>
    <div class="hero-bubble gold"></div>
    <div class="hero-bubble"></div>
    <div class="hero-bubble gold"></div>
    <div class="hero-bubble"></div>
    <div class="hero-bubble gold"></div>
    <div class="hero-bubble"></div>
    <div class="hero-bubble"></div>
    <div class="hero-content">
      <div class="hero-eyebrow">
        <wa-icon name="graduation-cap" variant="solid"></wa-icon>
        Arandia College eLMS
      </div>
      <h1 class="hero-title">Your learning,<br><span>your future.</span></h1>
      <p class="hero-sub">Access lessons, submit assignments, and track your academic progress — all in one place, anytime and anywhere.</p>
      <div class="hero-btns">
        <a href="login.php" class="hero-btn-primary">
          <wa-icon name="arrow-right-to-bracket" variant="solid"></wa-icon>
          Log In
        </a>
        <a href="#features" class="hero-btn-ghost">
          Learn More
          <wa-icon name="arrow-down" variant="solid"></wa-icon>
        </a>
      </div>
    </div>
  </section>

  <!-- MISSION & VISION -->
  <section class="mv-section" id="mv">
    <div class="section-header">
      <div class="section-eyebrow">Who We Are</div>
      <h2 class="section-title">Mission &amp; Vision</h2>
      <p class="section-sub">The guiding principles and aspirations that define Arandia College and its commitment to
        excellence in education.</p>
    </div>
    <div class="mv-grid">

      <div class="mv-card mv-vision">
        <div class="mv-label">Vision</div>
        <h3 class="mv-title">Our Vision</h3>
        <p class="mv-text">This institution is dedicated in pursuit of quality education and skills of learners for
          future endeavors in this ever-changing world.</p>
      </div>

      <div class="mv-card mv-mission">
        <div class="mv-label">Mission</div>
        <h3 class="mv-title">Our Mission</h3>
        <p class="mv-text">Provide learners necessary skills to be critical and creative thinkers for global
          competitiveness.</p>
      </div>

    </div>
  </section>

  <!-- FEATURES -->
  <section id="features">
    <div class="section-header">
      <div class="section-eyebrow">Platform Features</div>
      <h2 class="section-title">Everything You Need in One Place</h2>
      <p class="section-sub">Arandia College eLMS brings together all the tools students and teachers need to make
        digital learning effective and accessible.</p>
    </div>
    <div class="cards-section">
      <div class="cards-grid">

        <div class="card card-1">
          <div class="card-icon-wrap">
            <wa-icon name="book-open" variant="solid" style="color:#003087;"></wa-icon>
          </div>
          <div class="card-title">Course Modules</div>
          <p class="card-desc">Access organized lessons and reading materials uploaded by your instructors anytime.</p>
        </div>

        <div class="card card-2">
          <div class="card-icon-wrap">
            <wa-icon name="clipboard-list" variant="solid" style="color:#e11d48;"></wa-icon>
          </div>
          <div class="card-title">Assignments</div>
          <p class="card-desc">Submit tasks and written work online. Track deadlines and never miss a requirement.</p>
        </div>

        <div class="card card-3">
          <div class="card-icon-wrap">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-linecap="round" stroke-linejoin="round">
              <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><polyline points="9 15 11 17 15 13"/>
            </svg>
          </div>
          <div class="card-title">Quizzes &amp; Exams</div>
          <p class="card-desc">Take online quizzes with instant scoring and detailed performance feedback.</p>
        </div>

        <div class="card card-4">
          <div class="card-icon-wrap">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="#d97706" stroke-linecap="round" stroke-linejoin="round">
              <line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/><line x1="2" y1="20" x2="22" y2="20"/>
            </svg>
          </div>
          <div class="card-title">Progress Reports</div>
          <p class="card-desc">View your grades, completion rates, and academic performance at a glance.</p>
        </div>

        <div class="card card-6">
          <div class="card-icon-wrap">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="#0891b2" stroke-linecap="round" stroke-linejoin="round">
              <rect x="5" y="2" width="14" height="20" rx="2" ry="2"/><line x1="12" y1="18" x2="12.01" y2="18" stroke-width="2.5"/>
            </svg>
          </div>
          <div class="card-title">Mobile Access</div>
          <p class="card-desc">Use the platform on your smartphone or tablet — learning on-the-go made easy.</p>
        </div>

        <div class="card card-7">
          <div class="card-icon-wrap">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="#ea580c" stroke-linecap="round" stroke-linejoin="round">
              <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/>
            </svg>
          </div>
          <div class="card-title">Notifications</div>
          <p class="card-desc">Get alerts for upcoming deadlines, new modules, quiz results, and instructor
            announcements.</p>
        </div>

        <div class="card card-8">
          <div class="card-icon-wrap">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="#475569" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="12" cy="8" r="6"/><path d="M15.477 12.89L17 22l-5-3-5 3 1.523-9.11"/>
            </svg>
          </div>
          <div class="card-title">Achievements</div>
          <p class="card-desc">Earn badges and track milestones as you complete courses and hit academic goals.</p>
        </div>

      </div>
    </div>
  </section>

  <!-- HOW IT WORKS -->
  <section class="steps-section" id="how">
    <div class="section-header" style="padding-top:0">
      <div class="section-eyebrow">Getting Started</div>
      <h2 class="section-title">How It Works</h2>
      <p class="section-sub">Start using Arandia College eLMS in just four simple steps and begin your digital learning
        journey today.</p>
    </div>
    <div class="steps-grid">
      <div class="step">
        <div class="step-num">1</div>
        <div class="step-title">Create Your Account</div>
        <p class="step-desc">Register using your school ID or email address provided by Arandia College.</p>
      </div>
      <div class="step">
        <div class="step-num">2</div>
        <div class="step-title">Enroll in Courses</div>
        <p class="step-desc">Browse available subjects and enroll in your assigned or elective courses.</p>
      </div>
      <div class="step">
        <div class="step-num">3</div>
        <div class="step-title">Access Lessons</div>
        <p class="step-desc">View uploaded modules, videos, and reading materials prepared by your teachers.</p>
      </div>
      <div class="step">
        <div class="step-num">4</div>
        <div class="step-title">Complete &amp; Succeed</div>
        <p class="step-desc">Submit assignments, take quizzes, and track your grades and progress over time.</p>
      </div>
    </div>
  </section>

  <!-- FOOTER -->
  <footer>
    <div class="footer-top">

      <div class="footer-brand-wrap">
        <img src="picture/logo.jpg" alt="Arandia College Logo" class="footer-logo">
        <div class="footer-brand">
          <strong>Arandia College eLMS</strong>
          <span class="footer-brand-sub">Est. 1999</span>
          <p>Electronic Learning Management System — a digital platform supporting online and blended education for
            Arandia College students and teachers.</p>
        </div>
      </div>

      <div class="footer-col">
        <h4>For Students</h4>
        <ul>
          <li><span>View Courses</span></li>
          <li><span>My Assignments</span></li>
          <li><span>Take a Quiz</span></li>
          <li><span>My Grades</span></li>
        </ul>
      </div>

      <div class="footer-col">
        <h4>For Teachers</h4>
        <ul>
          <li><span>Upload Module</span></li>
          <li><span>Create Assessment</span></li>
          <li><span>Student Reports</span></li>
          <li><span>Class Management</span></li>
        </ul>
      </div>

      <div class="footer-col">
        <h4>Support</h4>
        <ul>
          <li><a href="helpcenter.php">Help Center</a></li>
          <li><a href="FAQ.php">FAQ</a></li>
          <li><a href="contact.php">Contact Us</a></li>
          <li><a href="privacy.php">Privacy Policy</a></li>
        </ul>
      </div>

    </div>
    <div class="footer-bottom">
      <span>&copy; 2026 Arandia College eLMS. All rights reserved.</span>
    </div>
  </footer>



  <!-- HAMBURGER MENU SCRIPT -->
  <script>
    const menuToggle = document.getElementById('menuToggle');
    const mobileDropdown = document.getElementById('mobileDropdown');

    menuToggle.addEventListener('click', function (e) {
      e.stopPropagation();
      const isOpen = mobileDropdown.classList.toggle('mobile-active');
      menuToggle.classList.toggle('open', isOpen);
      menuToggle.setAttribute('aria-label', isOpen ? 'Close menu' : 'Open menu');
    });

    // Close dropdown when clicking a link inside it
    mobileDropdown.querySelectorAll('a').forEach(function (link) {
      link.addEventListener('click', function () {
        mobileDropdown.classList.remove('mobile-active');
        menuToggle.classList.remove('open');
        menuToggle.setAttribute('aria-label', 'Open menu');
      });
    });

    // Close dropdown when clicking anywhere outside
    document.addEventListener('click', function (e) {
      if (!menuToggle.contains(e.target) && !mobileDropdown.contains(e.target)) {
        mobileDropdown.classList.remove('mobile-active');
        menuToggle.classList.remove('open');
        menuToggle.setAttribute('aria-label', 'Open menu');
      }
    });
  </script>

</body>

</html>