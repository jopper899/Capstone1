<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Privacy Policy — Arandia College eLMS</title>
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
            color: #888;
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
            box-shadow: 0 4px 14px rgba(0, 48, 135, 0.3);
            transition: all 0.2s !important;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 48, 135, 0.4) !important;
            background: var(--primary-dark) !important;
        }

        /* HERO */
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
            font-size: .9rem;
            color: rgba(255, 255, 255, .7);
            position: relative;
        }

        /* LAYOUT */
        .policy-layout {
            display: grid;
            grid-template-columns: 240px 1fr;
            gap: 2.5rem;
            padding: 3rem 5% 5rem;
            max-width: 1100px;
            margin: 0 auto;
            align-items: start;
        }

        /* SIDEBAR TOC */
        .toc {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, .07);
            position: sticky;
            top: 90px;
        }

        .toc h3 {
            font-family: 'Nunito', sans-serif;
            font-size: .85rem;
            font-weight: 900;
            color: var(--primary);
            margin-bottom: 1rem;
            text-transform: uppercase;
            letter-spacing: .06em;
        }

        .toc a {
            display: block;
            font-size: .8rem;
            color: #666;
            text-decoration: none;
            padding: .4rem .75rem;
            border-radius: 7px;
            margin-bottom: .25rem;
            transition: background .2s, color .2s;
            border-left: 2px solid transparent;
        }

        .toc a:hover,
        .toc a.active {
            background: #eff6ff;
            color: var(--primary);
            border-left-color: #003087;
        }

        .toc-updated {
            margin-top: 1.5rem;
            font-size: .72rem;
            color: #aaa;
            padding-top: 1rem;
            border-top: 1px solid #f0f2f5;
        }

        /* POLICY CONTENT */
        .policy-content {
            background: white;
            border-radius: 20px;
            padding: 3rem 2.5rem;
            box-shadow: 0 4px 24px rgba(0, 0, 0, .07);
        }

        .policy-section {
            padding-bottom: 2.5rem;
            margin-bottom: 2.5rem;
            border-bottom: 1px solid #f0f2f5;
        }

        .policy-section:last-of-type {
            border-bottom: none;
            padding-bottom: 0;
            margin-bottom: 0;
        }

        .ps-eyebrow {
            display: inline-block;
            background: rgba(0, 48, 135, 0.1);
            color: var(--primary);
            font-size: .68rem;
            font-weight: 700;
            letter-spacing: .1em;
            text-transform: uppercase;
            padding: .2rem .8rem;
            border-radius: 100px;
            margin-bottom: .65rem;
        }

        .ps-title {
            font-family: 'Nunito', sans-serif;
            font-size: 1.3rem;
            font-weight: 900;
            color: var(--text-dark);
            margin-bottom: 1rem;
        }

        .ps-text {
            font-size: .9rem;
            color: #555;
            line-height: 1.8;
            margin-bottom: .85rem;
        }

        .ps-text:last-child {
            margin-bottom: 0;
        }

        .ps-text a {
            color: #003087;
            font-weight: 600;
        }

        .ps-list {
            list-style: none;
            margin: .5rem 0 .85rem;
        }

        .ps-list li {
            display: flex;
            align-items: flex-start;
            gap: .65rem;
            font-size: .9rem;
            color: #555;
            line-height: 1.65;
            padding: .5rem 0;
            border-bottom: 1px solid #f7f7f7;
        }

        .ps-list li:last-child {
            border-bottom: none;
        }

        .ps-list li::before {
            content: '✓';
            color: var(--primary);
            font-weight: 900;
            flex-shrink: 0;
            font-size: .85rem;
            margin-top: .1rem;
        }

        .ps-highlight {
            background: #eff6ff;
            border-left: 4px solid var(--primary);
            border-radius: 0 10px 10px 0;
            padding: 1rem 1.25rem;
            margin: 1rem 0;
            font-size: .88rem;
            color: var(--primary);
            font-weight: 600;
            line-height: 1.65;
        }

        .rights-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-top: 1rem;
        }

        .right-item {
            background: #f8f9fe;
            border-radius: 10px;
            padding: 1rem;
        }

        .ri-icon {
            font-size: 1.4rem;
            margin-bottom: .35rem;
            display: block;
        }

        .ri-title {
            font-family: 'Nunito', sans-serif;
            font-size: .88rem;
            font-weight: 800;
            color: var(--text-dark);
            margin-bottom: .2rem;
        }

        .ri-desc {
            font-size: .78rem;
            color: #777;
            line-height: 1.5;
        }

        /* ACCEPTANCE BAR */
        .accept-bar {
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            border-radius: 16px;
            padding: 2rem 2rem;
            display: flex;
            align-items: center;
            gap: 2rem;
            flex-wrap: wrap;
            margin-top: 2rem;
        }

        .ab-text strong {
            display: block;
            font-family: 'Nunito', sans-serif;
            font-size: 1rem;
            font-weight: 900;
            color: white;
            margin-bottom: .25rem;
        }

        .ab-text span {
            font-size: .82rem;
            color: rgba(255, 255, 255, .75);
        }

        .ab-btn {
            background: white;
            color: var(--primary);
            padding: .7rem 1.6rem;
            border-radius: 9px;
            font-weight: 800;
            font-size: .88rem;
            border: none;
            cursor: pointer;
            font-family: 'Nunito', sans-serif;
            transition: opacity .2s;
            white-space: nowrap;
        }

        .ab-btn:hover {
            opacity: .88;
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

        @media(max-width:860px) {
            .policy-layout {
                grid-template-columns: 1fr;
            }

            .toc {
                position: static;
            }

            .rights-grid {
                grid-template-columns: 1fr;
            }

            .nav-links {
                display: none;
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
            <li><a href="faq.php">FAQ</a></li>
            <li><a href="helpcenter.php">Help Center</a></li>
            <li><a href="contact.php">Contact Us</a></li>
            <li><a href="login.php" class="btn-login">Log In</a></li>
        </ul>
    </nav>

    <div class="page-hero">
        <div class="page-hero-eyebrow">🔒 Legal</div>
        <h1>Privacy Policy</h1>
        <p>Effective Date: January 1, 2026 &nbsp;·&nbsp; Last Updated: June 1, 2026</p>
    </div>

    <div class="policy-layout">

        <!-- TOC SIDEBAR -->
        <div class="toc">
            <h3>Contents</h3>
            <a href="#section-intro">1. Introduction</a>
            <a href="#section-collect">2. Data We Collect</a>
            <a href="#section-use">3. How We Use Your Data</a>
            <a href="#section-share">4. Data Sharing</a>
            <a href="#section-security">5. Data Security</a>
            <a href="#section-rights">6. Your Rights</a>
            <a href="#section-cookies">7. Cookies</a>
            <a href="#section-minors">8. Minors & Students</a>
            <a href="#section-changes">9. Policy Changes</a>
            <a href="#section-contact">10. Contact</a>
            <div class="toc-updated">Last updated: June 1, 2026</div>
        </div>

        <!-- CONTENT -->
        <div class="policy-content">

            <div class="policy-section" id="section-intro">
                <div class="ps-eyebrow">01 · Introduction</div>
                <h2 class="ps-title">Introduction</h2>
                <p class="ps-text">Arandia College ("the College," "we," "our," or "us") is committed to protecting the
                    privacy and security of your personal information. This Privacy Policy describes how the Arandia
                    College Electronic Learning Management System (eLMS) collects, uses, stores, and protects data about
                    students, teachers, and other users of our platform.</p>
                <p class="ps-text">By accessing and using the Arandia College eLMS, you agree to the terms of this
                    Privacy Policy. If you do not agree, please do not use the platform and contact your administrator
                    for assistance.</p>
                <div class="ps-highlight">This policy applies to all users of the Arandia College eLMS, including
                    students, faculty, staff, and administrators.</div>
            </div>

            <div class="policy-section" id="section-collect">
                <div class="ps-eyebrow">02 · Data Collection</div>
                <h2 class="ps-title">Data We Collect</h2>
                <p class="ps-text">We collect information necessary to provide educational services and maintain the
                    eLMS platform. The types of data we collect include:</p>
                <ul class="ps-list">
                    <li><strong>Account Information</strong> — Name, school ID number, email address, password
                        (encrypted), program, year level, and role (student, teacher, admin).</li>
                    <li><strong>Academic Data</strong> — Course enrollments, assignment submissions, quiz answers and
                        scores, module completion records, and grades.</li>
                    <li><strong>Activity Logs</strong> — Login timestamps, pages visited, time spent on modules, and
                        platform activity for academic monitoring purposes.</li>
                    <li><strong>Uploaded Files</strong> — Documents, images, and other files you submit as part of
                        assignments or course materials.</li>
                    <li><strong>Communication Data</strong> — Messages sent through the Virtual Classroom, discussion
                        board posts, and announcements.</li>
                    <li><strong>Device Information</strong> — Browser type, device type, operating system, and IP
                        address for security and troubleshooting purposes.</li>
                </ul>
            </div>

            <div class="policy-section" id="section-use">
                <div class="ps-eyebrow">03 · Data Use</div>
                <h2 class="ps-title">How We Use Your Data</h2>
                <p class="ps-text">Your data is used solely for the purpose of delivering and improving educational
                    services. Specifically, we use your information to:</p>
                <ul class="ps-list">
                    <li>Manage your account and authenticate your access to the platform.</li>
                    <li>Provide access to course materials, assignments, quizzes, and grading tools.</li>
                    <li>Track academic progress and generate performance reports for students and instructors.</li>
                    <li>Send system notifications, including deadline reminders and grade updates.</li>
                    <li>Facilitate communication between students and faculty.</li>
                    <li>Investigate technical issues and improve platform performance and security.</li>
                    <li>Comply with Arandia College's academic policies and regulatory requirements.</li>
                </ul>
                <p class="ps-text">We do not use your personal data for advertising, marketing to third parties, or any
                    commercial purpose unrelated to your education.</p>
            </div>

            <div class="policy-section" id="section-share">
                <div class="ps-eyebrow">04 · Data Sharing</div>
                <h2 class="ps-title">Data Sharing and Disclosure</h2>
                <p class="ps-text">Arandia College does not sell, rent, or trade your personal information. We may share
                    your data only in the following limited circumstances:</p>
                <ul class="ps-list">
                    <li><strong>Within the College</strong> — Authorized faculty, advisers, and administrators may
                        access your academic records as needed to fulfill their educational duties.</li>
                    <li><strong>Service Providers</strong> — We may work with trusted third-party technology providers
                        (such as hosting and email services) who are contractually bound to protect your data and use it
                        only as directed by the College.</li>
                    <li><strong>Legal Obligations</strong> — We may disclose information if required by law, court
                        order, or government authority.</li>
                    <li><strong>Emergency Situations</strong> — In cases where there is a risk to the safety of a
                        student or others, we may share relevant information with appropriate parties.</li>
                </ul>
                <div class="ps-highlight">We will never sell your personal data to advertisers or third-party marketers.
                </div>
            </div>

            <div class="policy-section" id="section-security">
                <div class="ps-eyebrow">05 · Security</div>
                <h2 class="ps-title">Data Security</h2>
                <p class="ps-text">We take the security of your personal information seriously. Arandia College
                    implements appropriate technical and organizational measures to protect your data, including:</p>
                <ul class="ps-list">
                    <li>Encrypted password storage using industry-standard hashing algorithms.</li>
                    <li>Secure HTTPS connections for all data transmitted between your device and our servers.</li>
                    <li>Access controls that restrict data to authorized personnel only.</li>
                    <li>Regular security reviews and system updates.</li>
                    <li>Secure backups to prevent data loss.</li>
                </ul>
                <p class="ps-text">While we strive to protect your information, no system is entirely immune to security
                    risks. You are responsible for keeping your account credentials confidential and for immediately
                    reporting any unauthorized access to the <a href="helpdesk.php">Campus Helpdesk</a>.</p>
            </div>

            <div class="policy-section" id="section-rights">
                <div class="ps-eyebrow">06 · Your Rights</div>
                <h2 class="ps-title">Your Rights</h2>
                <p class="ps-text">As a user of the Arandia College eLMS, you have the following rights regarding your
                    personal data under applicable Philippine law, including the Data Privacy Act of 2012 (Republic Act
                    No. 10173):</p>
                <div class="rights-grid">
                    <div class="right-item"><span class="ri-icon">👁️</span>
                        <div class="ri-title">Right to Access</div>
                        <p class="ri-desc">You may request a copy of the personal data we hold about you at any time.
                        </p>
                    </div>
                    <div class="right-item"><span class="ri-icon">✏️</span>
                        <div class="ri-title">Right to Correction</div>
                        <p class="ri-desc">You may ask us to correct any inaccurate or outdated information in your
                            records.</p>
                    </div>
                    <div class="right-item"><span class="ri-icon">🗑️</span>
                        <div class="ri-title">Right to Erasure</div>
                        <p class="ri-desc">You may request deletion of your data, subject to legal and academic
                            record-keeping requirements.</p>
                    </div>
                    <div class="right-item"><span class="ri-icon">🚫</span>
                        <div class="ri-title">Right to Object</div>
                        <p class="ri-desc">You may object to the processing of your data in certain circumstances.</p>
                    </div>
                    <div class="right-item"><span class="ri-icon">📦</span>
                        <div class="ri-title">Right to Portability</div>
                        <p class="ri-desc">You may request a copy of your data in a structured, machine-readable format.
                        </p>
                    </div>
                    <div class="right-item"><span class="ri-icon">⚠️</span>
                        <div class="ri-title">Right to Complain</div>
                        <p class="ri-desc">You may file a complaint with the National Privacy Commission if you believe
                            your rights have been violated.</p>
                    </div>
                </div>
                <p class="ps-text" style="margin-top:1.25rem;">To exercise any of these rights, contact our Data
                    Protection Officer at <strong>privacy@arandiacollege.edu.ph</strong>.</p>
            </div>

            <div class="policy-section" id="section-cookies">
                <div class="ps-eyebrow">07 · Cookies</div>
                <h2 class="ps-title">Cookies and Tracking</h2>
                <p class="ps-text">The Arandia College eLMS uses essential cookies to maintain your login session and
                    ensure the platform functions correctly. These cookies are necessary for the system to work and do
                    not track you across other websites.</p>
                <p class="ps-text">We do not use advertising cookies, third-party tracking pixels, or analytics services
                    that share your data with external companies. You may configure your browser to block cookies, but
                    doing so may prevent you from logging in or using certain features of the platform.</p>
            </div>

            <div class="policy-section" id="section-minors">
                <div class="ps-eyebrow">08 · Minors & Students</div>
                <h2 class="ps-title">Minors and Student Privacy</h2>
                <p class="ps-text">Arandia College is an educational institution and many of our students may be minors.
                    We are committed to handling student data with the utmost care and in compliance with the Data
                    Privacy Act of 2012 and the Family Code of the Philippines.</p>
                <p class="ps-text">Student data is collected solely for academic and educational purposes. Parents or
                    legal guardians of minor students may request access to their child's academic records by visiting
                    the Registrar's Office with valid identification.</p>
                <div class="ps-highlight">We do not knowingly allow minors to create accounts independently. All student
                    accounts on the eLMS are created through the official school enrollment process.</div>
            </div>

            <div class="policy-section" id="section-changes">
                <div class="ps-eyebrow">09 · Policy Changes</div>
                <h2 class="ps-title">Changes to This Policy</h2>
                <p class="ps-text">Arandia College reserves the right to update or modify this Privacy Policy at any
                    time. Significant changes will be communicated to users through an in-platform announcement or email
                    notification at least seven (7) days before taking effect.</p>
                <p class="ps-text">Your continued use of the Arandia College eLMS after any changes to this policy
                    constitutes your acceptance of the revised terms. We encourage you to review this policy
                    periodically.</p>
            </div>

            <div class="policy-section" id="section-contact">
                <div class="ps-eyebrow">10 · Contact</div>
                <h2 class="ps-title">Contact Our Privacy Office</h2>
                <p class="ps-text">If you have questions, concerns, or requests related to this Privacy Policy or the
                    handling of your personal data, you may reach our Data Protection Officer through any of the
                    following:</p>
                <ul class="ps-list">
                    <li><strong>Email:</strong> privacy@arandiacollege.edu.ph</li>
                    <li><strong>Phone:</strong> (02) 8123-4567</li>
                    <li><strong>Office:</strong> Admin Building, Room 104, Arandia College, Quezon City</li>
                    <li><strong>Office Hours:</strong> Monday to Friday, 8:00 AM – 5:00 PM</li>
                </ul>
                <p class="ps-text">You may also reach out through our <a href="contact.php">Contact Us page</a> or
                    visit the <a href="helpdesk.php">Campus Helpdesk</a> for general eLMS concerns.</p>
            </div>

            <div class="accept-bar">
                <div class="ab-text">
                    <strong>You have read and understood this Privacy Policy.</strong>
                    <span>By using Arandia College eLMS, you consent to the terms described above.</span>
                </div>
                <button class="ab-btn" onclick="this.textContent='✅ Acknowledged'; this.disabled=true;">I
                    Understand</button>
            </div>

        </div>
    </div>

    <footer>
        <span>&copy; 2026 Arandia College eLMS. All rights reserved. &nbsp;·&nbsp; In compliance with Republic Act No.
            10173 (Data Privacy Act of 2012)</span>
    </footer>

    <script>
        // Highlight active TOC section on scroll
        const sections = document.querySelectorAll('.policy-section');
        const tocLinks = document.querySelectorAll('.toc a');
        window.addEventListener('scroll', () => {
            let current = '';
            sections.forEach(s => {
                if (window.scrollY >= s.offsetTop - 120) current = s.id;
            });
            tocLinks.forEach(a => {
                a.classList.toggle('active', a.getAttribute('href') === '#' + current);
            });
        });
    </script>
</body>

</html>