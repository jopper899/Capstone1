<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FAQ — Arandia College eLMS</title>
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

        /* PAGE HERO */
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

        /* SEARCH */
        .search-wrap {
            background: white;
            padding: 2rem 5%;
            display: flex;
            justify-content: center;
            box-shadow: 0 4px 20px rgba(0, 0, 0, .06);
        }

        .search-box {
            display: flex;
            align-items: center;
            gap: .75rem;
            background: var(--bg-light);
            border-radius: 12px;
            padding: .6rem 1.2rem;
            width: 100%;
            max-width: 560px;
            border: 2px solid transparent;
            transition: border .2s;
        }

        .search-box:focus-within {
            border-color: var(--primary);
            background: white;
        }

        .search-box input {
            border: none;
            background: transparent;
            outline: none;
            font-family: 'Open Sans', sans-serif;
            font-size: .9rem;
            color: var(--text-dark);
            width: 100%;
        }

        .search-box span {
            font-size: 1.1rem;
            opacity: .5;
        }

        /* FAQ BODY */
        .faq-body {
            padding: 3rem 5% 5rem;
            max-width: 860px;
            margin: 0 auto;
        }

        .faq-category {
            margin-bottom: 2.5rem;
        }

        .faq-cat-label {
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            background: rgba(0, 48, 135, 0.1);
            color: var(--primary);
            font-size: .72rem;
            font-weight: 800;
            letter-spacing: .1em;
            text-transform: uppercase;
            padding: .3rem 1rem;
            border-radius: 100px;
            margin-bottom: 1.25rem;
        }

        .faq-item {
            background: white;
            border-radius: 14px;
            margin-bottom: .85rem;
            box-shadow: 0 2px 12px rgba(0, 0, 0, .05);
            border: 1px solid rgba(0, 0, 0, .05);
            overflow: hidden;
            transition: box-shadow .2s;
        }

        .faq-item:hover {
            box-shadow: 0 6px 24px rgba(0, 0, 0, .09);
        }

        .faq-q {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding: 1.2rem 1.5rem;
            cursor: pointer;
            user-select: none;
        }

        .faq-q-text {
            font-family: 'Nunito', sans-serif;
            font-weight: 800;
            font-size: .95rem;
            color: var(--text-dark);
        }

        .faq-toggle {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background: var(--bg-light);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: .8rem;
            flex-shrink: 0;
            transition: background .2s, transform .3s;
        }

        .faq-item.open .faq-toggle {
            background: var(--primary);
            color: white;
            transform: rotate(180deg);
        }

        .faq-a {
            max-height: 0;
            overflow: hidden;
            transition: max-height .35s ease, padding .3s;
            padding: 0 1.5rem;
        }

        .faq-item.open .faq-a {
            max-height: 400px;
            padding: 0 1.5rem 1.2rem;
        }

        .faq-a p {
            font-size: .88rem;
            color: #555;
            line-height: 1.75;
        }

        .faq-a a {
            color: var(--primary);
            font-weight: 600;
        }

        /* STILL NEED HELP */
        .help-cta {
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            border-radius: 20px;
            padding: 3rem 2.5rem;
            text-align: center;
            margin: 1rem 0;
        }

        .help-cta h3 {
            font-family: 'Nunito', sans-serif;
            font-size: 1.5rem;
            font-weight: 900;
            color: white;
            margin-bottom: .5rem;
        }

        .help-cta p {
            color: rgba(255, 255, 255, .75);
            font-size: .88rem;
            margin-bottom: 1.5rem;
        }

        .help-cta-btns {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        .help-btn {
            padding: .7rem 1.6rem;
            border-radius: 10px;
            font-weight: 700;
            font-size: .88rem;
            text-decoration: none;
            transition: opacity .2s;
        }

        .help-btn-primary {
            background: white;
            color: var(--primary);
        }

        .help-btn-secondary {
            background: rgba(255, 255, 255, .15);
            color: white;
            border: 1px solid rgba(255, 255, 255, .35);
        }

        .help-btn:hover {
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

        @media(max-width:700px) {
            .nav-links {
                display: none;
            }

            .page-hero h1 {
                font-size: 1.8rem;
            }
        }
    </style>
</head>

<body>

    <div class="topbar">
        <a href="helpdesk.php">Campus Helpdesk</a>
        <a href="FAQ.php">FAQ</a>
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
            <li><a href="index.php#how">How It Works</a></li>
            <li><a href="FAQ.php">FAQ</a></li>
            <li><a href="login.php" class="btn-login">Log In</a></li>
        </ul>
    </nav>

    <div class="page-hero">
        <div class="page-hero-eyebrow">❓ Help Center</div>
        <h1>Frequently Asked Questions</h1>
        <p>Find quick answers to the most common questions about the Arandia College eLMS platform.</p>
    </div>

    <div class="search-wrap">
        <div class="search-box">
            <span>🔍</span>
            <input type="text" placeholder="Search for answers..." id="faqSearch">
        </div>
    </div>

    <div class="faq-body">

        <div class="faq-category">
            <div class="faq-cat-label">📚 Getting Started</div>

            <div class="faq-item">
                <div class="faq-q"><span class="faq-q-text">How do I create my eLMS account?</span>
                    <div class="faq-toggle">▼</div>
                </div>
                <div class="faq-a">
                    <p>You can register using your official Arandia College school ID number or the email address
                        provided to you by the Registrar's Office. Go to the <a href="login.php">login page</a> and
                        click "Create Account." If you encounter issues, contact the <a href="helpdesk.php">Campus
                            Helpdesk</a>.</p>
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-q"><span class="faq-q-text">I forgot my password. How do I reset it?</span>
                    <div class="faq-toggle">▼</div>
                </div>
                <div class="faq-a">
                    <p>On the login page, click "Forgot Password" and enter your registered email address. A password
                        reset link will be sent to your inbox within a few minutes. Check your spam folder if you don't
                        see it. If you still can't access your account, reach out to the <a href="helpdesk.php">Campus
                            Helpdesk</a>.</p>
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-q"><span class="faq-q-text">Can I use eLMS on my smartphone or tablet?</span>
                    <div class="faq-toggle">▼</div>
                </div>
                <div class="faq-a">
                    <p>Yes! Arandia College eLMS is fully mobile-responsive. You can access all features — including
                        course modules, quizzes, and assignments — from any modern smartphone or tablet browser without
                        downloading a separate app.</p>
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-q"><span class="faq-q-text">Which browsers are supported?</span>
                    <div class="faq-toggle">▼</div>
                </div>
                <div class="faq-a">
                    <p>We recommend using Google Chrome (latest), Mozilla Firefox, or Microsoft Edge for the best
                        experience. Safari on iOS is also supported. Older browsers like Internet Explorer may not work
                        correctly.</p>
                </div>
            </div>
        </div>

        <div class="faq-category">
            <div class="faq-cat-label">📋 Courses & Enrollment</div>

            <div class="faq-item">
                <div class="faq-q"><span class="faq-q-text">How do I enroll in my subjects?</span>
                    <div class="faq-toggle">▼</div>
                </div>
                <div class="faq-a">
                    <p>After logging in, go to the "Courses" section and browse the available subjects. Click "Enroll"
                        on the courses assigned to you. Some courses may require an enrollment key provided by your
                        instructor. If you cannot find your subject, contact your adviser or the registrar.</p>
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-q"><span class="faq-q-text">What if my course or subject is not listed?</span>
                    <div class="faq-toggle">▼</div>
                </div>
                <div class="faq-a">
                    <p>Some courses are set up by instructors at the start of each semester. If your subject is not yet
                        visible, it may not have been activated yet. Wait for an announcement from your instructor or
                        report it via the <a href="contact.php">Contact Us</a> page.</p>
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-q"><span class="faq-q-text">Can I unenroll from a course?</span>
                    <div class="faq-toggle">▼</div>
                </div>
                <div class="faq-a">
                    <p>Students cannot unenroll from courses on their own. Course enrollment changes must go through
                        your instructor or the Registrar's Office, following the standard academic procedures of Arandia
                        College.</p>
                </div>
            </div>
        </div>

        <div class="faq-category">
            <div class="faq-cat-label">📝 Assignments & Quizzes</div>

            <div class="faq-item">
                <div class="faq-q"><span class="faq-q-text">How do I submit an assignment?</span>
                    <div class="faq-toggle">▼</div>
                </div>
                <div class="faq-a">
                    <p>Open the course, go to the "Assignments" section, and click on the assignment you want to submit.
                        You can upload a file or type your answer directly, depending on how the instructor set it up.
                        Click "Submit" before the deadline. Late submissions may not be accepted.</p>
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-q"><span class="faq-q-text">Can I retake a quiz?</span>
                    <div class="faq-toggle">▼</div>
                </div>
                <div class="faq-a">
                    <p>Whether a quiz can be retaken depends on the settings configured by your instructor. Some quizzes
                        allow multiple attempts while others only allow one. Check the quiz description for details, or
                        ask your teacher directly.</p>
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-q"><span class="faq-q-text">What file formats are accepted for assignment
                        uploads?</span>
                    <div class="faq-toggle">▼</div>
                </div>
                <div class="faq-a">
                    <p>Commonly accepted formats include PDF, DOCX, PPTX, XLSX, JPG, and PNG. The accepted file types
                        and maximum file size are shown on each assignment page. We recommend converting your work to
                        PDF before submitting for best compatibility.</p>
                </div>
            </div>
        </div>

        <div class="faq-category">
            <div class="faq-cat-label">📊 Grades & Progress</div>

            <div class="faq-item">
                <div class="faq-q"><span class="faq-q-text">How can I check my grades?</span>
                    <div class="faq-toggle">▼</div>
                </div>
                <div class="faq-a">
                    <p>Log in and go to "My Grades" or the "Progress Reports" section. You'll see your scores per
                        activity, overall grade per subject, and completion rate. Grades are updated by instructors
                        after checking your submissions.</p>
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-q"><span class="faq-q-text">Why is my grade not showing yet?</span>
                    <div class="faq-toggle">▼</div>
                </div>
                <div class="faq-a">
                    <p>Grades are posted by your instructor after they have reviewed and marked your work. If a grade is
                        missing for an activity you completed more than a week ago, you can send your instructor a
                        message through the Virtual Classroom or email them directly.</p>
                </div>
            </div>
        </div>

        <div class="faq-category">
            <div class="faq-cat-label">🔧 Technical Issues</div>

            <div class="faq-item">
                <div class="faq-q"><span class="faq-q-text">The page won't load or keeps giving an error. What do I
                        do?</span>
                    <div class="faq-toggle">▼</div>
                </div>
                <div class="faq-a">
                    <p>Try refreshing the page (Ctrl + R or F5). Clear your browser cache and cookies, then try again.
                        If the problem continues, try a different browser or device. If the issue persists, report it at
                        our <a href="helpdesk.php">Campus Helpdesk</a> with a screenshot of the error message.</p>
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-q"><span class="faq-q-text">My uploaded file keeps failing. What should I check?</span>
                    <div class="faq-toggle">▼</div>
                </div>
                <div class="faq-a">
                    <p>Check that your file does not exceed the size limit shown on the assignment page (usually 20MB).
                        Make sure you're using an accepted file format. Avoid special characters in your filename (e.g.,
                        use "Activity1_JuanDela Cruz.pdf" instead of symbols). A slow internet connection can also cause
                        upload failures.</p>
                </div>
            </div>
        </div>

        <div class="help-cta">
            <h3>Still can't find your answer?</h3>
            <p>Our support team is ready to help you with any concern about the Arandia College eLMS.</p>
            <div class="help-cta-btns">
                <a href="contact.php" class="help-btn help-btn-primary">📩 Contact Us</a>
                <a href="helpdesk.php" class="help-btn help-btn-secondary">🖥️ Visit Helpdesk</a>
            </div>
        </div>

    </div>

    <footer>
        <span>&copy; 2026 Arandia College eLMS. All rights reserved.</span>
    </footer>

    <script>
        document.querySelectorAll('.faq-q').forEach(q => {
            q.addEventListener('click', () => {
                const item = q.parentElement;
                const wasOpen = item.classList.contains('open');
                document.querySelectorAll('.faq-item').forEach(i => i.classList.remove('open'));
                if (!wasOpen) item.classList.add('open');
            });
        });

        document.getElementById('faqSearch').addEventListener('input', function () {
            const val = this.value.toLowerCase();
            document.querySelectorAll('.faq-item').forEach(item => {
                const text = item.textContent.toLowerCase();
                item.style.display = text.includes(val) ? '' : 'none';
            });
            document.querySelectorAll('.faq-category').forEach(cat => {
                const visible = [...cat.querySelectorAll('.faq-item')].some(i => i.style.display !== 'none');
                cat.style.display = visible ? '' : 'none';
            });
        });
    </script>
</body>

</html>