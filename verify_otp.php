<?php
// ============================================================
//  Arandia College eLMS — OTP Verification
//  File: verify_otp.php
// ============================================================
session_start();
require_once 'config/conn.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
} else {
    die("Error: Composer dependencies missing. Run 'composer install'.");
}

// ── Guard: must have a pending OTP session ───────────────────
if (empty($_SESSION['otp_pending_user_id'])) {
    header('Location: login.php');
    exit;
}

// ── Already fully logged in? Redirect ───────────────────────
if (isset($_SESSION['user_id'])) {
    $r = $_SESSION['role'] ?? '';
    if ($r === 'Admin') {
        header('Location: admin.php');
        exit;
    }
    if ($r === 'Teacher') {
        header('Location: teacher.php');
        exit;
    }
    if ($r === 'Student') {
        header('Location: student.php');
        exit;
    }
}

$userId = (int) $_SESSION['otp_pending_user_id'];
$emailHint = $_SESSION['otp_pending_email_hint'] ?? 'your registered email';

$error = '';
$success = '';

// ── Resend OTP handler ───────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['resend_otp'])) {
    // Fetch user email
    $stmt = $conn->prepare("SELECT email, first_name FROM users WHERE id = ? LIMIT 1");
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $resendUser = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($resendUser) {
        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $otpHash = password_hash($otp, PASSWORD_DEFAULT);

        $upd = $conn->prepare(
            "INSERT INTO login_otps (user_id, otp_hash, expires_at, used)
       VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 10 MINUTE), 0)
       ON DUPLICATE KEY UPDATE otp_hash = VALUES(otp_hash),
         expires_at = DATE_ADD(NOW(), INTERVAL 10 MINUTE), used = 0"
        );
        $upd->bind_param('is', $userId, $otpHash);
        $upd->execute();
        $upd->close();

        $mail = new PHPMailer(true);
        try {
            $mail->SMTPDebug = SMTP::DEBUG_OFF;
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'marc.macarubbo@gmail.com';
            $mail->Password = 'zkre tdpp feve edeh';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port = 465;

            $mail->setFrom('marc.macarubbo@gmail.com', 'Arandia College eLMS');
            $mail->addAddress($resendUser['email']);
            $mail->isHTML(true);
            $mail->Subject = 'Arandia College eLMS — Your New Login OTP';

            $name = htmlspecialchars($resendUser['first_name']);
            $mail->Body = '
        <!DOCTYPE html><html><head><meta charset="UTF-8">
        <style>
          body{font-family:Arial,sans-serif;line-height:1.6;color:#333;margin:0;padding:0}
          .container{max-width:600px;margin:0 auto;padding:20px}
          .header{background:linear-gradient(135deg,#003087 0%,#0055cc 100%);color:white;padding:35px 30px;text-align:center;border-radius:12px 12px 0 0}
          .header h1{margin:0 0 6px 0;font-size:22px}.header p{margin:0;font-size:13px;opacity:.8}
          .gold-bar{width:40px;height:3px;background:#FFD700;border-radius:2px;margin:10px auto 0}
          .content{background:#f9faff;padding:32px 30px;border-radius:0 0 12px 12px;border:1px solid #e0e8ff;border-top:none}
          .otp-box{background:white;border:2px solid #003087;border-radius:12px;padding:28px 20px;text-align:center;margin:24px 0}
          .otp-code{font-size:42px;font-weight:bold;letter-spacing:10px;color:#003087;font-family:monospace}
          .otp-label{color:#666;font-size:13px;margin:0 0 14px 0}
          .otp-expires{margin-top:14px;font-size:12px;color:#888}
          .footer{text-align:center;margin-top:24px;padding-top:20px;border-top:1px solid #e8edf5}
          .footer p{margin:4px 0;font-size:12px;color:#999}.footer strong{color:#003087}
        </style></head>
        <body><div class="container">
          <div class="header">
            <h1>🔐 New Login OTP</h1>
            <p>Arandia College eLMS; SHS &amp; HS Portal</p>
            <div class="gold-bar"></div>
          </div>
          <div class="content">
            <h2 style="margin-top:0;color:#1a1a2e;">Hi ' . $name . ',</h2>
            <p>Here is your new One-Time Password to complete your login:</p>
            <div class="otp-box">
              <p class="otp-label">Your One-Time Password (OTP)</p>
              <div class="otp-code">' . $otp . '</div>
              <p class="otp-expires">⏱ This code expires in <strong>10 minutes</strong></p>
            </div>
            <div class="footer">
              <p><strong>Arandia College eLMS</strong></p>
              <p>SHS &amp; HS Learning Portal</p>
              <p style="font-size:10px;margin-top:12px;">This is an automated message. Do not reply.</p>
            </div>
          </div>
        </div></body></html>';

            $mail->AltBody = "Hi {$name},\n\nYour new Arandia College eLMS OTP is: {$otp}\n\nExpires in 10 minutes.\n\n— Arandia College eLMS";
            $mail->send();
            $success = 'A new OTP has been sent to your email.';
        } catch (Exception $e) {
            error_log("OTP resend error: " . $mail->ErrorInfo);
            $error = 'Failed to resend OTP. Please try again.';
        }
    }
}

// ── OTP verification handler ─────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify_otp'])) {
    $enteredOtp = trim(implode('', $_POST['otp_digits'] ?? []));

    if (strlen($enteredOtp) !== 6 || !ctype_digit($enteredOtp)) {
        $error = 'Please enter the complete 6-digit OTP.';
    } else {
        // Fetch OTP record
        $stmt = $conn->prepare(
            "SELECT otp_hash, expires_at, used FROM login_otps
       WHERE user_id = ? LIMIT 1"
        );
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $otpRecord = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$otpRecord) {
            $error = 'No OTP found. Please go back and log in again.';
        } elseif ($otpRecord['used']) {
            $error = 'This OTP has already been used. Please request a new one.';
        } elseif (strtotime($otpRecord['expires_at']) < time()) {
            $error = 'Your OTP has expired. Please request a new one.';
        } elseif (!password_verify($enteredOtp, $otpRecord['otp_hash'])) {
            $error = 'Invalid OTP. Please try again.';
        } else {
            // ✅ OTP is correct — mark as used
            $upd = $conn->prepare("UPDATE login_otps SET used = 1 WHERE user_id = ?");
            $upd->bind_param('i', $userId);
            $upd->execute();
            $upd->close();

            // ✅ Complete the login
            session_regenerate_id(true);
            $_SESSION['user_id'] = $userId;
            $_SESSION['username'] = $_SESSION['otp_pending_username'];
            $_SESSION['role'] = $_SESSION['otp_pending_role'];
            $_SESSION['first_name'] = $_SESSION['otp_pending_first_name'];
            $_SESSION['last_name'] = $_SESSION['otp_pending_last_name'];

            // ── Remember Me: set or clear cookie ─────────────
            if (!empty($_SESSION['otp_pending_remember'])) {
                setcookie('remember_login', $_SESSION['otp_pending_remember'], time() + (30 * 24 * 60 * 60), '/', '', false, true);
            } else {
                setcookie('remember_login', '', time() - 3600, '/');
            }

            // Clean up OTP session vars
            foreach ([
                'otp_pending_user_id',
                'otp_pending_username',
                'otp_pending_role',
                'otp_pending_first_name',
                'otp_pending_last_name',
                'otp_pending_email_hint',
                'otp_pending_remember'
            ] as $k) {
                unset($_SESSION[$k]);
            }

            $role = $_SESSION['role'];
            if ($role === 'Admin') {
                header('Location: admin.php');
                exit;
            }
            if ($role === 'Teacher') {
                header('Location: teacher.php');
                exit;
            }
            if ($role === 'Student') {
                header('Location: student.php');
                exit;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify OTP — Arandia College eLMS</title>
    <link
        href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&family=Inter:wght@400;500;600&display=swap"
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
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, .05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, .1), 0 2px 4px -1px rgba(0, 0, 0, .06);
            --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, .1), 0 10px 10px -5px rgba(0, 0, 0, .04);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0
        }

        html {
            scroll-behavior: smooth
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg-light);
            color: var(--text-dark);
            line-height: 1.5;
            overflow-x: hidden;
            min-height: 100vh;
            display: flex;
            flex-direction: column
        }

        /* TOPBAR */
        .topbar {
            background: var(--primary-dark);
            color: rgba(255, 255, 255, .8);
            font-size: .75rem;
            padding: .5rem 5%;
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
            font-weight: 500
        }

        .topbar a {
            color: rgba(255, 255, 255, .8);
            text-decoration: none;
            transition: color .2s
        }

        .topbar a:hover {
            color: var(--accent)
        }

        /* NAV */
        nav {
            background: rgba(255, 255, 255, .95);
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
            border-bottom: 1px solid rgba(0, 0, 0, .05)
        }

        .nav-brand {
            display: flex;
            align-items: center;
            gap: 1rem;
            text-decoration: none
        }

        .nav-logo {
            width: 50px;
            height: 50px;
            object-fit: contain
        }

        .nav-brand-text {
            line-height: 1.1
        }

        .nav-brand-text strong {
            display: block;
            font-family: 'Nunito', sans-serif;
            font-size: 1.1rem;
            font-weight: 900;
            color: var(--primary);
            letter-spacing: -.5px
        }

        .nav-brand-text span {
            font-size: .75rem;
            color: var(--text-light);
            font-weight: 500
        }

        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            font-family: 'Nunito', sans-serif;
            font-size: .85rem;
            font-weight: 800;
            color: white;
            text-decoration: none;
            padding: .55rem 1.4rem;
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            border-radius: 50px;
            box-shadow: 0 4px 14px rgba(0, 48, 135, .3);
            transition: all .2s ease
        }

        .back-btn::before {
            content: '←';
            font-size: 1rem;
            line-height: 1
        }

        .back-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 48, 135, .45);
            color: white
        }

        /* WRAPPER */
        .otp-wrapper {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2.5rem 1rem;
            background: linear-gradient(135deg, #e8f0ff 0%, #f0f2f5 50%, #fffbe6 100%)
        }

        .otp-card {
            background: var(--white);
            border-radius: 20px;
            box-shadow: var(--shadow-xl);
            padding: 2.5rem 2rem;
            width: 100%;
            max-width: 440px;
            text-align: center
        }

        .otp-icon {
            font-size: 3rem;
            margin-bottom: .75rem
        }

        .otp-title {
            font-family: 'Nunito', sans-serif;
            font-size: 1.6rem;
            font-weight: 900;
            color: var(--primary);
            margin-bottom: .4rem
        }

        .otp-desc {
            font-size: .9rem;
            color: var(--text-light);
            margin-bottom: .25rem
        }

        .otp-email {
            font-size: .85rem;
            color: var(--primary);
            font-weight: 600;
            margin-bottom: 1.75rem;
            word-break: break-all
        }

        /* OTP Input Boxes */
        .otp-inputs {
            display: flex;
            gap: .6rem;
            justify-content: center;
            margin-bottom: 1.5rem
        }

        .otp-inputs input {
            width: 48px;
            height: 58px;
            text-align: center;
            font-size: 1.5rem;
            font-weight: 700;
            font-family: 'Nunito', sans-serif;
            border: 2px solid #d1d9f0;
            border-radius: 10px;
            outline: none;
            transition: border-color .2s, box-shadow .2s;
            color: var(--primary);
            background: #f9faff
        }

        .otp-inputs input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(0, 48, 135, .15)
        }

        .otp-inputs input.filled {
            border-color: var(--primary-light);
            background: #eef3ff
        }

        /* Buttons */
        .btn-verify {
            width: 100%;
            padding: .85rem;
            border: none;
            border-radius: 50px;
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            color: white;
            font-family: 'Nunito', sans-serif;
            font-size: 1rem;
            font-weight: 800;
            cursor: pointer;
            box-shadow: 0 4px 14px rgba(0, 48, 135, .3);
            transition: all .2s;
            margin-bottom: 1rem
        }

        .btn-verify:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 48, 135, .45)
        }

        .btn-verify:disabled {
            opacity: .6;
            cursor: not-allowed;
            transform: none
        }

        .resend-row {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: .5rem;
            font-size: .85rem;
            color: var(--text-light)
        }

        .btn-resend {
            background: none;
            border: none;
            color: var(--primary);
            font-weight: 700;
            cursor: pointer;
            font-size: .85rem;
            padding: 0;
            text-decoration: underline
        }

        .btn-resend:disabled {
            color: var(--text-light);
            text-decoration: none;
            cursor: default
        }

        .back-login {
            display: block;
            margin-top: 1.25rem;
            font-size: .83rem;
            color: var(--text-light);
            text-decoration: none
        }

        .back-login:hover {
            color: var(--primary)
        }

        /* Alerts */
        .alert {
            padding: .8rem 1rem;
            border-radius: 10px;
            font-size: .875rem;
            margin-bottom: 1rem;
            text-align: left
        }

        .alert-error {
            background: #fff0f0;
            color: #c0392b;
            border: 1px solid #fbd5d5
        }

        .alert-success {
            background: #f0fff4;
            color: #1a7f4b;
            border: 1px solid #b7f0d1
        }

        /* Timer */
        .otp-timer {
            font-size: .8rem;
            color: var(--text-light);
            margin-bottom: 1.25rem
        }

        .otp-timer span {
            font-weight: 700;
            color: var(--primary)
        }

        /* Footer */
        .page-footer {
            text-align: center;
            padding: 1.25rem;
            font-size: .75rem;
            color: var(--text-light);
            border-top: 1px solid #e2e8f0;
            background: var(--white)
        }

        @media(max-width:480px) {
            .otp-inputs input {
                width: 40px;
                height: 50px;
                font-size: 1.2rem
            }

            .otp-card {
                padding: 2rem 1.25rem
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
            <img src="picture/logo.jpg" alt="Logo" class="nav-logo">
            <div class="nav-brand-text">
                <strong>Arandia College eLMS</strong>
                <span>Electronic Learning Management System</span>
            </div>
        </a>
        <a href="login.php" class="back-btn">Back to Login</a>
    </nav>

    <div class="otp-wrapper">
        <div class="otp-card">

            <div class="otp-icon">📧</div>
            <div class="otp-title">Check Your Email</div>
            <p class="otp-desc">We sent a 6-digit code to</p>
            <p class="otp-email"><?= htmlspecialchars($emailHint) ?></p>

            <?php if ($error): ?>
                <div class="alert alert-error">⚠️ <?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="alert alert-success">✅ <?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <!-- OTP Verify Form -->
            <form method="POST" action="verify_otp.php" id="otpForm">
                <input type="hidden" name="verify_otp" value="1">
                <div class="otp-inputs" id="otpInputs">
                    <input type="text" name="otp_digits[]" class="otp-digit" maxlength="1" inputmode="numeric"
                        pattern="[0-9]" autocomplete="one-time-code" required>
                    <input type="text" name="otp_digits[]" class="otp-digit" maxlength="1" inputmode="numeric"
                        pattern="[0-9]" required>
                    <input type="text" name="otp_digits[]" class="otp-digit" maxlength="1" inputmode="numeric"
                        pattern="[0-9]" required>
                    <input type="text" name="otp_digits[]" class="otp-digit" maxlength="1" inputmode="numeric"
                        pattern="[0-9]" required>
                    <input type="text" name="otp_digits[]" class="otp-digit" maxlength="1" inputmode="numeric"
                        pattern="[0-9]" required>
                    <input type="text" name="otp_digits[]" class="otp-digit" maxlength="1" inputmode="numeric"
                        pattern="[0-9]" required>
                </div>

                <div class="otp-timer">
                    Code expires in: <span id="timerDisplay">10:00</span>
                </div>

                <button type="submit" class="btn-verify" id="verifyBtn">Verify OTP →</button>
            </form>

            <!-- Resend Form -->
            <div class="resend-row">
                <span>Didn't receive the code?</span>
                <form method="POST" action="verify_otp.php" style="display:inline">
                    <input type="hidden" name="resend_otp" value="1">
                    <button type="submit" class="btn-resend" id="resendBtn" disabled>
                        Resend OTP (<span id="resendTimer">30</span>s)
                    </button>
                </form>
            </div>

            <a href="login.php" class="back-login">← Back to Sign In</a>

        </div>
    </div>

    <div class="page-footer">© 2026 Arandia College eLMS — SHS &amp; HS Portal. All rights reserved.</div>

    <script>
        // ── OTP digit input auto-advance ──────────────────────────
        const digits = document.querySelectorAll('.otp-digit');

        digits.forEach((input, i) => {
            input.addEventListener('input', function () {
                this.value = this.value.replace(/\D/g, '').slice(0, 1);
                this.classList.toggle('filled', this.value !== '');
                if (this.value && i < digits.length - 1) digits[i + 1].focus();
            });

            input.addEventListener('keydown', function (e) {
                if (e.key === 'Backspace' && !this.value && i > 0) {
                    digits[i - 1].focus();
                    digits[i - 1].value = '';
                    digits[i - 1].classList.remove('filled');
                }
            });

            // Handle paste on first digit
            input.addEventListener('paste', function (e) {
                e.preventDefault();
                const pasted = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '').slice(0, 6);
                pasted.split('').forEach((ch, idx) => {
                    if (digits[idx]) {
                        digits[idx].value = ch;
                        digits[idx].classList.add('filled');
                    }
                });
                const next = Math.min(pasted.length, digits.length - 1);
                digits[next].focus();
            });
        });

        // Focus first input on load
        digits[0].focus();

        // ── Expiry countdown (10 minutes) ────────────────────────
        let totalSeconds = 10 * 60;
        const timerDisplay = document.getElementById('timerDisplay');

        function updateTimer() {
            const m = Math.floor(totalSeconds / 60);
            const s = totalSeconds % 60;
            timerDisplay.textContent = `${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`;
            if (totalSeconds === 0) {
                timerDisplay.style.color = '#c0392b';
                timerDisplay.textContent = 'Expired';
                document.getElementById('verifyBtn').disabled = true;
            } else {
                totalSeconds--;
            }
        }
        updateTimer();
        setInterval(updateTimer, 1000);

        // ── Resend cooldown (30 seconds) ─────────────────────────
        let resendSeconds = 30;
        const resendBtn = document.getElementById('resendBtn');
        const resendTimer = document.getElementById('resendTimer');

        const resendInterval = setInterval(() => {
            resendSeconds--;
            resendTimer.textContent = resendSeconds;
            if (resendSeconds <= 0) {
                clearInterval(resendInterval);
                resendBtn.disabled = false;
                resendBtn.innerHTML = 'Resend OTP';
            }
        }, 1000);
    </script>

</body>

</html>