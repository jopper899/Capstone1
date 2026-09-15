<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us — Arandia College eLMS</title>
    <link
        href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&family=Open+Sans:wght@400;500;600&display=swap"
        rel="stylesheet">
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
    }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Open Sans', sans-serif;
            background: var(--bg-light);
            color: var(--text-dark);
        }

        .topbar {
            background: var(--primary-dark);
            color: rgba(255,255,255,0.8);
            font-size: .78rem;
            padding: .4rem 5%;
            display: flex;
            justify-content: flex-end;
            gap: 1.5rem;
        }

        .topbar a {
            color: rgba(255, 255, 255, .8);
            text-decoration: none;
        }

        .topbar a:hover {
            color: var(--accent);
        }

        nav {
            background: white;
            box-shadow: 0 2px 12px rgba(0, 0, 0, .1);
            position: sticky;
            top: 0;
            z-index: 200;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 5%;
            height: 80px;
            backdrop-filter: blur(12px);
            background: rgba(255,255,255,0.95);
        }

        .nav-brand {
            display: flex;
            align-items: center;
            gap: .85rem;
            text-decoration: none;
        }

        .nav-logo-svg {
            width: 50px;
            height: 50px;
            object-fit: contain;
        }

        .nav-brand-text strong {
            display: block;
            font-family: 'Nunito', sans-serif;
            font-size: 1rem;
            font-weight: 900;
            color: var(--primary);
        }

        .nav-brand-text span {
            font-size: .72rem;
            color: var(--text-light);
        }

        .nav-links {
            display: flex;
            align-items: center;
            list-style: none;
            gap: .25rem;
        }

        .nav-links a {
            font-size: .85rem;
            font-weight: 600;
            color: #444;
            text-decoration: none;
            padding: .5rem .9rem;
            border-radius: 6px;
            transition: background .2s, color .2s;
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
            box-shadow: 0 4px 14px rgba(0,48,135,0.3);
            transition: all 0.2s !important;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0,48,135,0.4) !important;
            background: var(--primary-dark) !important;
        }

        .page-hero {
            background: linear-gradient(135deg, #003087 0%, var(--primary-light) 100%);
            padding: 4rem 5% 3rem;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .page-hero::before {
            content: '';
            position: absolute;
            inset: 0;
            background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.04'%3E%3Ccircle cx='30' cy='30' r='20'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
        }

        .page-hero-eyebrow {
            display: inline-block;
            background: rgba(255, 255, 255, .15);
            color: rgba(255, 255, 255, .9);
            font-size: .72rem;
            font-weight: 700;
            letter-spacing: .1em;
            text-transform: uppercase;
            padding: .3rem 1rem;
            border-radius: 100px;
            margin-bottom: .75rem;
            position: relative;
        }

        .page-hero h1 {
            font-family: 'Nunito', sans-serif;
            font-size: 2.4rem;
            font-weight: 900;
            color: white;
            letter-spacing: -.02em;
            margin-bottom: .5rem;
            position: relative;
        }

        .page-hero p {
            font-size: .95rem;
            color: rgba(255, 255, 255, .75);
            max-width: 520px;
            margin: 0 auto;
            line-height: 1.65;
            position: relative;
        }

        /* BODY */
        .contact-body {
            padding: 3.5rem 5% 5rem;
            max-width: 1100px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 1.35fr 1fr;
            gap: 3rem;
            align-items: start;
        }

        /* FORM SIDE */
        .form-card {
            background: white;
            border-radius: 20px;
            padding: 2.5rem 2rem;
            box-shadow: 0 4px 24px rgba(0, 0, 0, .07);
        }

        .section-eyebrow {
            display: inline-block;
            background: rgba(0,48,135,0.1);
            color: var(--primary);
            font-size: .72rem;
            font-weight: 700;
            letter-spacing: .1em;
            text-transform: uppercase;
            padding: .3rem 1rem;
            border-radius: 100px;
            margin-bottom: .75rem;
        }

        .section-title {
            font-family: 'Nunito', sans-serif;
            font-size: 1.5rem;
            font-weight: 900;
            color: var(--primary);
            margin-bottom: .35rem;
        }

        .section-sub {
            font-size: .85rem;
            color: #666;
            line-height: 1.65;
            margin-bottom: 2rem;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: .4rem;
            margin-bottom: 1.2rem;
        }

        .form-row2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        label {
            font-size: .78rem;
            font-weight: 700;
            color: #444;
            letter-spacing: .03em;
        }

        input,
        select,
        textarea {
            border: 1.5px solid #e0e0e0;
            border-radius: 9px;
            padding: .65rem 1rem;
            font-family: 'Open Sans', sans-serif;
            font-size: .88rem;
            color: var(--text-dark);
            outline: none;
            transition: border .2s;
            background: #fafafa;
        }

        input:focus,
        select:focus,
        textarea:focus {
            border-color: var(--primary);
            background: white;
        }

        textarea {
            resize: vertical;
            min-height: 140px;
        }

        .submit-btn {
            background: linear-gradient(90deg, var(--primary), var(--primary-light));
            color: white;
            border: none;
            padding: .85rem 2.2rem;
            border-radius: 10px;
            font-family: 'Nunito', sans-serif;
            font-weight: 800;
            font-size: .95rem;
            cursor: pointer;
            transition: opacity .2s;
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            width: 100%;
            justify-content: center;
        }

        .submit-btn:hover {
            opacity: .88;
        }

        .success-msg {
            display: none;
            background: #e6fff4;
            border: 1.5px solid #00C97F;
            border-radius: 10px;
            padding: 1.25rem 1.5rem;
            color: #006644;
            font-weight: 600;
            font-size: .88rem;
            margin-top: 1rem;
        }

        /* INFO SIDE */
        .info-side {
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
        }

        .info-card {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, .07);
            border: 1px solid rgba(0, 0, 0, .05);
            display: flex;
            gap: 1rem;
            align-items: flex-start;
        }

        .ic-icon {
            font-size: 1.8rem;
            flex-shrink: 0;
        }

        .ic-label {
            font-size: .7rem;
            font-weight: 800;
            letter-spacing: .1em;
            text-transform: uppercase;
            color: var(--primary);
            margin-bottom: .2rem;
        }

        .ic-val {
            font-family: 'Nunito', sans-serif;
            font-size: .95rem;
            font-weight: 800;
            color: var(--text-dark);
            margin-bottom: .15rem;
        }

        .ic-note {
            font-size: .78rem;
            color: #888;
            line-height: 1.5;
        }

        .map-placeholder {
            background: linear-gradient(135deg, #e8f0ff, #d0e0ff);
            border-radius: 16px;
            height: 180px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: .5rem;
            color: var(--primary);
        }

        .map-placeholder span {
            font-size: 2.5rem;
        }

        .map-placeholder p {
            font-family: 'Nunito', sans-serif;
            font-weight: 800;
            font-size: .88rem;
        }

        .map-placeholder small {
            font-size: .75rem;
            color: #555;
        }

        .social-row {
            display: flex;
            gap: .75rem;
            flex-wrap: wrap;
        }

        .social-btn {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            padding: .55rem 1.1rem;
            border-radius: 9px;
            font-size: .8rem;
            font-weight: 700;
            text-decoration: none;
            transition: opacity .2s;
        }

        .social-btn:hover {
            opacity: .8;
        }

        .fb {
            background: rgba(0,48,135,0.1);
            color: var(--primary);
        }

        .gh {
            background: #f0f2f5;
            color: #333;
        }

        footer {
            background: #0f172a;
            color: #94a3b8;
            padding: 2rem 5%;
            text-align: center;
            font-size: .78rem;
        }

        footer span {
            color: #64748b;
        }

        @media(max-width:900px) {
            .contact-body {
                grid-template-columns: 1fr;
            }

            .nav-links {
                display: none;
            }

            .form-row2 {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>

    <div class="topbar">
        <a href="helpdesk.php">Campus Helpdesk</a>
        <a href="faq.php">FAQ</a>
        <a href="contact.php">Contact Us</a>
    </div>

    <nav>
        <a href="index.php" class="nav-brand">
            <img src="picture/logo.jpg" alt="Arandia College Logo" class="nav-logo-svg">
            <div class="nav-brand-text">
                <strong>Arandia College eLMS</strong>
                <span>Electronic Learning Management System</span>
            </div>
        </a>
        <ul class="nav-links">
            <li><a href="index.php">Home</a></li>
            <li><a href="index.php#features">Features</a></li>
            <li><a href="faq.php">FAQ</a></li>
            <li><a href="helpdesk.php">Helpdesk</a></li>
            <li><a href="login.php" class="btn-login">Log In</a></li>
        </ul>
    </nav>

    <div class="page-hero">
        <div class="page-hero-eyebrow">📩 Get in Touch</div>
        <h1>Contact Us</h1>
        <p>Have a question, concern, or feedback? We'd love to hear from you. Send us a message and we'll respond as
            soon as we can.</p>
    </div>

    <div class="contact-body">

        <!-- FORM -->
        <div class="form-card">
            <div class="section-eyebrow">✉️ Message Us</div>
            <h2 class="section-title">Send a Message</h2>
            <p class="section-sub">Fill out the form below. Our team will get back to you within 1–2 business days.</p>

            <div class="form-row2">
                <div class="form-group">
                    <label>First Name *</label>
                    <input type="text" placeholder="Juan" id="c-fname">
                </div>
                <div class="form-group">
                    <label>Last Name *</label>
                    <input type="text" placeholder="Dela Cruz" id="c-lname">
                </div>
            </div>
            <div class="form-group">
                <label>Email Address *</label>
                <input type="email" placeholder="your@email.com" id="c-email">
            </div>
            <div class="form-group">
                <label>Role</label>
                <select id="c-role">
                    <option value="">Select your role</option>
                    <option>Student</option>
                    <option>Teacher / Faculty</option>
                    <option>Parent / Guardian</option>
                    <option>Staff / Administrator</option>
                    <option>Other</option>
                </select>
            </div>
            <div class="form-group">
                <label>Subject *</label>
                <input type="text" placeholder="e.g. Problem with quiz submission" id="c-subject">
            </div>
            <div class="form-group">
                <label>Message *</label>
                <textarea placeholder="Write your message here..." id="c-message"></textarea>
            </div>
            <button class="submit-btn" onclick="submitContact()">📨 Send Message</button>
            <div class="success-msg" id="c-success">✅ Your message has been sent! We will get back to you at the email
                address you provided within 1–2 business days. Thank you for reaching out.</div>
        </div>

        <!-- INFO -->
        <div class="info-side">

            <div class="info-card">
                <div class="ic-icon">📧</div>
                <div>
                    <div class="ic-label">Email</div>
                    <div class="ic-val">info@arandiacollege.edu.ph</div>
                    <p class="ic-note">For general inquiries. Replies within 1–2 business days.</p>
                </div>
            </div>

            <div class="info-card">
                <div class="ic-icon">📞</div>
                <div>
                    <div class="ic-label">Phone</div>
                    <div class="ic-val">(02) 8123-4567</div>
                    <p class="ic-note">Mon–Fri, 8:00 AM – 5:00 PM. Saturday until noon.</p>
                </div>
            </div>

            <div class="info-card">
                <div class="ic-icon">📍</div>
                <div>
                    <div class="ic-label">Address</div>
                    <div class="ic-val">Arandia College</div>
                    <p class="ic-note">123 Arandia Street, Quezon City, Metro Manila, Philippines 1100</p>
                </div>
            </div>

            <div class="map-placeholder">
                <span>🗺️</span>
                <p>Arandia College — Quezon City</p>
                <small>123 Arandia Street, Metro Manila</small>
            </div>

            <div>
                <p
                    style="font-size:.78rem;font-weight:700;color:#888;text-transform:uppercase;letter-spacing:.08em;margin-bottom:.65rem;">
                    Follow Us</p>
                <div class="social-row">
                    <a href="#" class="social-btn fb">📘 Facebook Page</a>
                    <a href="helpdesk.php" class="social-btn gh">🖥️ Helpdesk</a>
                </div>
            </div>

        </div>
    </div>

    <footer>
        <span>&copy; 2026 Arandia College eLMS. All rights reserved.</span>
    </footer>

    <script>
        function submitContact() {
            const fname = document.getElementById('c-fname').value.trim();
            const lname = document.getElementById('c-lname').value.trim();
            const email = document.getElementById('c-email').value.trim();
            const subject = document.getElementById('c-subject').value.trim();
            const msg = document.getElementById('c-message').value.trim();
            if (!fname || !lname || !email || !subject || !msg) { alert('Please fill in all required fields.'); return; }
            document.getElementById('c-success').style.display = 'block';
            document.getElementById('c-success').scrollIntoView({ behavior: 'smooth' });
        }
    </script>
</body>

</html>