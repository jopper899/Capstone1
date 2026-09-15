<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Help Center — Arandia College eLMS</title>
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
            padding: 4.5rem 5% 5rem;
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
            font-size: 2.5rem;
            font-weight: 900;
            color: white;
            letter-spacing: -.02em;
            margin-bottom: .6rem;
            position: relative;
        }

        .page-hero p {
            font-size: .95rem;
            color: rgba(255, 255, 255, .75);
            max-width: 500px;
            margin: 0 auto 2rem;
            line-height: 1.65;
            position: relative;
        }

        /* HERO SEARCH */
        .hero-search {
            display: flex;
            align-items: center;
            gap: .75rem;
            background: white;
            border-radius: 14px;
            padding: .75rem 1.4rem;
            width: 100%;
            max-width: 560px;
            margin: 0 auto;
            box-shadow: 0 8px 32px rgba(0, 0, 0, .2);
            position: relative;
        }

        .hero-search input {
            border: none;
            outline: none;
            font-family: 'Open Sans', sans-serif;
            font-size: .95rem;
            color: var(--text-dark);
            width: 100%;
        }

        .hero-search span {
            font-size: 1.2rem;
            color: #aaa;
        }

        /* BODY */
        .hc-body {
            padding: 3.5rem 5% 5rem;
            max-width: 1100px;
            margin: 0 auto;
        }

        /* QUICK LINKS */
        .quick-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1.25rem;
            margin-bottom: 3.5rem;
        }

        .quick-card {
            background: white;
            border-radius: 16px;
            padding: 2rem 1.5rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, .07);
            border: 1px solid rgba(0, 0, 0, .05);
            text-align: center;
            text-decoration: none;
            color: inherit;
            transition: transform .3s, box-shadow .3s;
            display: block;
            position: relative;
            overflow: hidden;
        }

        .quick-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--grad);
            transform: scaleX(0);
            transition: transform .3s;
            transform-origin: left;
        }

        .quick-card:hover::before {
            transform: scaleX(1);
        }

        .quick-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 40px rgba(0, 0, 0, .12);
        }

        .qc-1 {
            --grad: linear-gradient(90deg, #003087, #0055cc);
        }

        .qc-2 {
            --grad: linear-gradient(90deg, #FF5C35, #FFB800);
        }

        .qc-3 {
            --grad: linear-gradient(90deg, #00C97F, #0055cc);
        }

        .qc-4 {
            --grad: linear-gradient(90deg, #9B59B6, #003087);
        }

        .qc-5 {
            --grad: linear-gradient(90deg, #FFB800, #FF5C35);
        }

        .qc-6 {
            --grad: linear-gradient(90deg, #003087, #9B59B6);
        }

        .qc-icon {
            font-size: 2.5rem;
            margin-bottom: .85rem;
            display: block;
        }

        .qc-title {
            font-family: 'Nunito', sans-serif;
            font-size: 1rem;
            font-weight: 900;
            color: var(--text-dark);
            margin-bottom: .35rem;
        }

        .qc-desc {
            font-size: .78rem;
            color: #888;
            line-height: 1.55;
        }

        .qc-arrow {
            display: inline-block;
            margin-top: .75rem;
            font-size: .78rem;
            font-weight: 700;
            color: var(--primary);
        }

        /* GUIDE SECTIONS */
        .section-eyebrow {
            display: inline-block;
            background: rgba(0, 48, 135, 0.1);
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
            font-size: 1.6rem;
            font-weight: 900;
            color: var(--text-dark);
            margin-bottom: .4rem;
        }

        .section-sub {
            font-size: .88rem;
            color: #666;
            line-height: 1.65;
            margin-bottom: 2rem;
        }

        .guide-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.25rem;
            margin-bottom: 3.5rem;
        }

        .guide-card {
            background: white;
            border-radius: 14px;
            padding: 1.5rem 1.5rem;
            box-shadow: 0 2px 12px rgba(0, 0, 0, .05);
            border: 1px solid rgba(0, 0, 0, .05);
            display: flex;
            gap: 1rem;
            align-items: flex-start;
        }

        .gc-num {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            color: white;
            font-family: 'Nunito', sans-serif;
            font-weight: 900;
            font-size: .9rem;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .gc-title {
            font-family: 'Nunito', sans-serif;
            font-size: .95rem;
            font-weight: 800;
            color: var(--text-dark);
            margin-bottom: .3rem;
        }

        .gc-desc {
            font-size: .82rem;
            color: #666;
            line-height: 1.6;
        }

        /* VIDEO GUIDES placeholder */
        .video-row {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1.25rem;
            margin-bottom: 3.5rem;
        }

        .video-card {
            background: white;
            border-radius: 14px;
            overflow: hidden;
            box-shadow: 0 2px 12px rgba(0, 0, 0, .05);
            transition: transform .3s;
        }

        .video-card:hover {
            transform: translateY(-4px);
        }

        .video-thumb {
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            height: 140px;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }

        .video-thumb span {
            font-size: 3rem;
        }

        .play-badge {
            position: absolute;
            width: 44px;
            height: 44px;
            background: rgba(255, 255, 255, .2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.1rem;
            backdrop-filter: blur(4px);
            border: 2px solid rgba(255, 255, 255, .5);
        }

        .video-info {
            padding: 1rem 1.15rem;
        }

        .vi-title {
            font-family: 'Nunito', sans-serif;
            font-weight: 800;
            font-size: .9rem;
            color: var(--text-dark);
            margin-bottom: .25rem;
        }

        .vi-dur {
            font-size: .75rem;
            color: #aaa;
        }

        /* CTA STRIP */
        .cta-strip {
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            border-radius: 20px;
            padding: 3rem 2.5rem;
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 2rem;
            align-items: center;
        }

        .cta-strip h3 {
            font-family: 'Nunito', sans-serif;
            font-size: 1.5rem;
            font-weight: 900;
            color: white;
            margin-bottom: .4rem;
        }

        .cta-strip p {
            color: rgba(255, 255, 255, .75);
            font-size: .88rem;
        }

        .cta-btns {
            display: flex;
            gap: .75rem;
            flex-wrap: wrap;
        }

        .cta-btn {
            padding: .75rem 1.5rem;
            border-radius: 10px;
            font-weight: 700;
            font-size: .88rem;
            text-decoration: none;
            transition: opacity .2s;
            white-space: nowrap;
        }

        .cta-btn-w {
            background: white;
            color: var(--primary);
        }

        .cta-btn-g {
            background: rgba(255, 255, 255, .15);
            color: white;
            border: 1px solid rgba(255, 255, 255, .35);
        }

        .cta-btn:hover {
            opacity: .85;
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
            .quick-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .guide-grid {
                grid-template-columns: 1fr;
            }

            .video-row {
                grid-template-columns: 1fr 1fr;
            }

            .cta-strip {
                grid-template-columns: 1fr;
            }

            .nav-links {
                display: none;
            }
        }

        @media(max-width:600px) {
            .quick-grid {
                grid-template-columns: 1fr;
            }

            .video-row {
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
            <li><a href="contact.php">Contact Us</a></li>
            <li><a href="login.php" class="btn-login">Log In</a></li>
        </ul>
    </nav>

    <div class="page-hero">
        <div class="page-hero-eyebrow">🛟 Help Center</div>
        <h1>How Can We Help You?</h1>
        <p>Browse guides, tutorials, and resources to get the most out of the Arandia College eLMS platform.</p>
        <div class="hero-search">
            <span>🔍</span>
            <input type="text" placeholder="Search for guides, tutorials, or topics..." id="hcSearch">
        </div>
    </div>

    <div class="hc-body">

        <!-- QUICK LINKS -->
        <div class="quick-grid" style="margin-top:.5rem;">
            <a href="faq.php" class="quick-card qc-1">
                <span class="qc-icon">❓</span>
                <div class="qc-title">Frequently Asked Questions</div>
                <p class="qc-desc">Quick answers to the most common student and teacher questions.</p>
                <div class="qc-arrow">Browse FAQ →</div>
            </a>
            <a href="helpdesk.php" class="quick-card qc-2">
                <span class="qc-icon">🖥️</span>
                <div class="qc-title">Campus Helpdesk</div>
                <p class="qc-desc">Submit a ticket or contact the IT support team directly.</p>
                <div class="qc-arrow">Go to Helpdesk →</div>
            </a>
            <a href="contact.php" class="quick-card qc-3">
                <span class="qc-icon">📩</span>
                <div class="qc-title">Contact Us</div>
                <p class="qc-desc">Send us a message and our team will respond within 1–2 business days.</p>
                <div class="qc-arrow">Send a Message →</div>
            </a>
            <a href="#student-guide" class="quick-card qc-4">
                <span class="qc-icon">🎓</span>
                <div class="qc-title">Student Guide</div>
                <p class="qc-desc">Step-by-step instructions for students on using the platform.</p>
                <div class="qc-arrow">Read Guide →</div>
            </a>
            <a href="#teacher-guide" class="quick-card qc-5">
                <span class="qc-icon">👩‍🏫</span>
                <div class="qc-title">Teacher Guide</div>
                <p class="qc-desc">Learn how to set up courses, post modules, and grade students.</p>
                <div class="qc-arrow">Read Guide →</div>
            </a>
            <a href="privacy.php" class="quick-card qc-6">
                <span class="qc-icon">🔒</span>
                <div class="qc-title">Privacy Policy</div>
                <p class="qc-desc">Understand how we collect, use, and protect your personal data.</p>
                <div class="qc-arrow">Read Policy →</div>
            </a>
        </div>

        <!-- STUDENT GUIDE -->
        <div id="student-guide">
            <div class="section-eyebrow">🎓 For Students</div>
            <h2 class="section-title">Student Step-by-Step Guide</h2>
            <p class="section-sub">New to Arandia College eLMS? Follow these steps to get started with your digital
                learning.</p>
            <div class="guide-grid">
                <div class="guide-card">
                    <div class="gc-num">1</div>
                    <div>
                        <div class="gc-title">Create Your Account</div>
                        <p class="gc-desc">Go to the Login page and click "Create Account." Enter your school ID or
                            registered email and set a secure password.</p>
                    </div>
                </div>
                <div class="guide-card">
                    <div class="gc-num">2</div>
                    <div>
                        <div class="gc-title">Log In and Update Profile</div>
                        <p class="gc-desc">After logging in, go to your profile settings and complete your information,
                            including your program and year level.</p>
                    </div>
                </div>
                <div class="guide-card">
                    <div class="gc-num">3</div>
                    <div>
                        <div class="gc-title">Enroll in Your Subjects</div>
                        <p class="gc-desc">Go to "Courses" and click "Enroll" on your assigned subjects. Use the
                            enrollment key provided by your teacher if required.</p>
                    </div>
                </div>
                <div class="guide-card">
                    <div class="gc-num">4</div>
                    <div>
                        <div class="gc-title">Access Learning Materials</div>
                        <p class="gc-desc">Inside each course, go to "Modules" to read uploaded lessons, videos, and
                            files shared by your instructor.</p>
                    </div>
                </div>
                <div class="guide-card">
                    <div class="gc-num">5</div>
                    <div>
                        <div class="gc-title">Submit Assignments</div>
                        <p class="gc-desc">Go to "Assignments," pick the task, attach your file or type your answer, and
                            hit "Submit" before the deadline.</p>
                    </div>
                </div>
                <div class="guide-card">
                    <div class="gc-num">6</div>
                    <div>
                        <div class="gc-title">Take Quizzes and Exams</div>
                        <p class="gc-desc">Open "Quizzes" inside your course. Read the instructions carefully, answer
                            all questions, and submit when done.</p>
                    </div>
                </div>
                <div class="guide-card">
                    <div class="gc-num">7</div>
                    <div>
                        <div class="gc-title">Track Your Progress</div>
                        <p class="gc-desc">Visit "My Grades" to see your scores, completion rate, and overall standing
                            per subject anytime.</p>
                    </div>
                </div>
                <div class="guide-card">
                    <div class="gc-num">8</div>
                    <div>
                        <div class="gc-title">Communicate with Teachers</div>
                        <p class="gc-desc">Use the Virtual Classroom to post questions in discussion boards or send a
                            message directly to your instructor.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- TEACHER GUIDE -->
        <div id="teacher-guide">
            <div class="section-eyebrow">👩‍🏫 For Teachers</div>
            <h2 class="section-title">Teacher & Faculty Guide</h2>
            <p class="section-sub">Set up your courses and manage your classes effectively using the eLMS tools.</p>
            <div class="guide-grid">
                <div class="guide-card">
                    <div class="gc-num">1</div>
                    <div>
                        <div class="gc-title">Set Up Your Course</div>
                        <p class="gc-desc">Log in and go to "My Courses." Click "Create Course," fill in the subject
                            details, and set it to visible for students.</p>
                    </div>
                </div>
                <div class="guide-card">
                    <div class="gc-num">2</div>
                    <div>
                        <div class="gc-title">Upload Learning Modules</div>
                        <p class="gc-desc">Inside your course, go to "Modules" and upload PDF, PPTX, Word files, or
                            embed video links for your students.</p>
                    </div>
                </div>
                <div class="guide-card">
                    <div class="gc-num">3</div>
                    <div>
                        <div class="gc-title">Create Assignments</div>
                        <p class="gc-desc">Use the "Assignments" feature to post tasks with instructions, file type
                            restrictions, and a clear deadline.</p>
                    </div>
                </div>
                <div class="guide-card">
                    <div class="gc-num">4</div>
                    <div>
                        <div class="gc-title">Build Quizzes and Exams</div>
                        <p class="gc-desc">Create online quizzes with multiple choice, true/false, and essay questions.
                            Set time limits and attempt rules.</p>
                    </div>
                </div>
                <div class="guide-card">
                    <div class="gc-num">5</div>
                    <div>
                        <div class="gc-title">Grade Student Work</div>
                        <p class="gc-desc">Go to the "Submissions" section of each assignment to review and grade
                            student work and leave feedback.</p>
                    </div>
                </div>
                <div class="guide-card">
                    <div class="gc-num">6</div>
                    <div>
                        <div class="gc-title">Post Announcements</div>
                        <p class="gc-desc">Keep your students informed by posting announcements inside the Virtual
                            Classroom. Students will be notified automatically.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- VIDEO GUIDES -->
        <div class="section-eyebrow">🎬 Video Tutorials</div>
        <h2 class="section-title">Watch & Learn</h2>
        <p class="section-sub">Quick video tutorials to walk you through the most important features of the platform.
        </p>
        <div class="video-row">
            <div class="video-card">
                <div class="video-thumb" style="background:linear-gradient(135deg,var(--primary),var(--primary-light));">
                    <div class="play-badge">▶</div>
                </div>
                <div class="video-info">
                    <div class="vi-title">How to Create Your Account</div>
                    <div class="vi-dur">⏱ 2 min · Beginner</div>
                </div>
            </div>
            <div class="video-card">
                <div class="video-thumb" style="background:linear-gradient(135deg,#FF5C35,#FFB800);">
                    <div class="play-badge">▶</div>
                </div>
                <div class="video-info">
                    <div class="vi-title">Submitting an Assignment</div>
                    <div class="vi-dur">⏱ 3 min · Beginner</div>
                </div>
            </div>
            <div class="video-card">
                <div class="video-thumb" style="background:linear-gradient(135deg,#00C97F,#0055cc);">
                    <div class="play-badge">▶</div>
                </div>
                <div class="video-info">
                    <div class="vi-title">Taking a Quiz or Exam</div>
                    <div class="vi-dur">⏱ 4 min · Intermediate</div>
                </div>
            </div>
        </div>

        <!-- CTA -->
        <div class="cta-strip">
            <div>
                <h3>Still need help?</h3>
                <p>Our support team is available to assist you. Submit a ticket or send us a message.</p>
            </div>
            <div class="cta-btns">
                <a href="helpdesk.php" class="cta-btn cta-btn-w">🖥️ Helpdesk</a>
                <a href="contact.php" class="cta-btn cta-btn-g">📩 Contact Us</a>
            </div>
        </div>

    </div>

    <footer>
        <span>&copy; 2026 Arandia College eLMS. All rights reserved.</span>
    </footer>

    <script>
        document.getElementById('hcSearch').addEventListener('input', function () {
            const val = this.value.toLowerCase();
            if (!val) {
                document.querySelectorAll('.guide-card, .quick-card').forEach(c => c.style.opacity = '1');
                return;
            }
            document.querySelectorAll('.guide-card').forEach(c => {
                c.style.opacity = c.textContent.toLowerCase().includes(val) ? '1' : '.3';
            });
        });
    </script>
</body>

</html>