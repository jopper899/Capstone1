<?php
// ============================================================
//  Arandia College eLMS — Login
//  File: login.php  |  Target: SHS & HS
// ============================================================
session_start();
require_once 'config/conn.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Check if vendor/autoload.php exists before loading to prevent fatal errors
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
  require_once __DIR__ . '/vendor/autoload.php';
} else {
  die("Error: Composer dependencies missing. Please run 'composer install'.");
}

// ── Redirect if already logged in ───────────────────────────
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

 $error   = '';
 $fpMsg   = '';   // forgot-password feedback
 $fpError = '';

// ── Remember Me: read cookie on page load ────────────────────
$rememberedLogin = '';
if (!empty($_COOKIE['remember_login'])) {
  $rememberedLogin = htmlspecialchars(strip_tags($_COOKIE['remember_login']));
}

// ── Forgot-password handler ──────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['forgot_password'])) {
  $fpEmail = trim($_POST['fp_email'] ?? '');

  if (empty($fpEmail)) {
    $fpError = 'Please enter your email address.';
  } elseif (!filter_var($fpEmail, FILTER_VALIDATE_EMAIL)) {
    $fpError = 'Please enter a valid email address.';
  } else {
    // Check if email exists
    $stmt = $conn->prepare("SELECT id, first_name FROM users WHERE email = ? AND status = 'Active' LIMIT 1");
    $stmt->bind_param('s', $fpEmail);
    $stmt->execute();
    $fpUser = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($fpUser) {
      // Generate a secure token
      $token = bin2hex(random_bytes(32));

      // ── Store token in DB (Using DATE_ADD for Timezone fix) ─────────
      $ins = $conn->prepare(
        "INSERT INTO password_resets (user_id, token, expires_at, used)
         VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 20 MINUTE), 0) 
         ON DUPLICATE KEY UPDATE token = VALUES(token), expires_at = DATE_ADD(NOW(), INTERVAL 20 MINUTE), used = 0"
      );

      // Bind params: 'i' for integer user_id, 's' for string token
      $ins->bind_param('is', $fpUser['id'], $token);

      // Execute the query first
      if ($ins->execute()) {
        
        // ── Generate dynamic reset link ────────────────────────
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
        $domainName = $_SERVER['HTTP_HOST'];
        $path = dirname($_SERVER['PHP_SELF']);
        $path = rtrim($path, '/\\');

        $resetLink = $protocol . $domainName . $path . "/reset_password.php?token=" . urlencode($token);
        $name = htmlspecialchars($fpUser['first_name']);

        // ── Send reset e-mail via PHPMailer ───────────────────
        $mail = new PHPMailer(true);
        try {
          // Server settings
          $mail->SMTPDebug = SMTP::DEBUG_OFF; // Ensure this is OFF in production
          $mail->isSMTP();
          $mail->Host = 'smtp.gmail.com';
          $mail->SMTPAuth = true;
          $mail->Username = 'marc.macarubbo@gmail.com';   // UPDATE: Your Gmail
          $mail->Password = 'zkre tdpp feve edeh';        // UPDATE: Gmail App Password
          $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
          $mail->Port = 465;

          $mail->setFrom('marc.macarubbo@gmail.com', 'Arandia College eLMS');
          $mail->addAddress($fpEmail);

          $mail->isHTML(true);
          $mail->Subject = 'Arandia College eLMS - Password Reset';

          $mail->Body = '
                    <!DOCTYPE html>
                    <html>
                    <head>
                        <meta charset="UTF-8">
                        <style>
                            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; }
                            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                            .header { background: linear-gradient(135deg, #003087 0%, #0055cc 100%); color: white; padding: 35px 30px; text-align: center; border-radius: 12px 12px 0 0; }
                            .header h1 { margin: 0 0 6px 0; font-size: 22px; }
                            .header p { margin: 0; font-size: 13px; opacity: .8; }
                            .content { background: #f9faff; padding: 32px 30px; border-radius: 0 0 12px 12px; border: 1px solid #e0e8ff; border-top: none; }
                            .reset-box { background: white; border: 2px solid #003087; border-radius: 12px; padding: 24px 20px; text-align: center; margin: 24px 0; }
                            .reset-box p.label { margin: 0 0 14px 0; color: #666; font-size: 13px; }
                            .btn { display: inline-block; background: linear-gradient(135deg, #003087 0%, #0055cc 100%); color: white !important; padding: 14px 36px; text-decoration: none; border-radius: 25px; font-weight: bold; font-size: 15px; }
                            .reset-link { margin: 14px 0 0 0; font-size: 11px; color: #555; word-break: break-all; line-height: 1.6; }
                            ul { padding-left: 18px; }
                            ul li { margin-bottom: 5px; font-size: 13px; color: #555; }
                            .footer { text-align: center; margin-top: 24px; padding-top: 20px; border-top: 1px solid #e8edf5; }
                            .footer p { margin: 4px 0; font-size: 12px; color: #999; }
                            .footer strong { color: #003087; }
                            .gold-bar { width: 40px; height: 3px; background: #FFD700; border-radius: 2px; margin: 10px auto 0; }
                        </style>
                    </head>
                    <body>
                        <div class="container">
                            <div class="header">
                                <h1>Password Reset Request</h1>
                                <p>Arandia College eLMS &mdash; SHS &amp; HS Portal</p>
                                <div class="gold-bar"></div>
                            </div>
                            <div class="content">
                                <h2 style="margin-top:0; color:#1a1a2e;">Hi ' . $name . ',</h2>
                                <p>We received a request to reset your <strong>Arandia College eLMS</strong> account password.</p>
                                <p>Click the button below to set a new password:</p>

                                <div class="reset-box">
                                    <p class="label">This link is valid for <strong>20 minutes</strong></p>
                                    <a href="' . htmlspecialchars($resetLink) . '" class="btn">Reset My Password &rarr;</a>
                                    
                                    <p class="reset-link">If the button above does not work, you can copy and paste this link into your browser:</p>
                                    <p class="reset-link-url">' . htmlspecialchars($resetLink) . '</p>
                                </div>

                                <p><strong>Security Reminders:</strong></p>
                                <ul>
                                    <li>This link will expire in <strong>20 minutes</strong></li>
                                    <li>Never share this link with anyone</li>
                                    <li>Arandia College eLMS will never ask for your password via email</li>
                                    <li>If you did not request this, you can safely ignore this email</li>
                                </ul>

                                <div class="footer">
                                    <p><strong>Arandia College eLMS</strong></p>
                                    <p>SHS &amp; HS Learning Portal</p>
                                    <p style="font-size:10px; margin-top:12px;">This is an automated message. Please do not reply to this email.</p>
                                </div>
                            </div>
                        </div>
                    </body>
                    </html>';

          $mail->AltBody = "Hi {$name},\n\nReset your Arandia College eLMS password using the link below (valid for 20 minutes):\n\n{$resetLink}\n\nIf the button above does not work, you can copy and paste the link above into your browser.\n\n— Arandia College eLMS";

          $mail->send();
        } catch (Exception $e) {
          // Log error
          error_log("PHPMailer error: " . $mail->ErrorInfo);
        }
      }
      
      // Close the statement HERE, after the email logic
      $ins->close();
    }

    // Show success if email was found, or a clear error if not registered
    if ($fpUser) {
      $fpMsg = 'A password reset link has been sent. Please check your inbox (and spam folder).';
    } else {
      $fpError = 'This email address is not registered in our system.';
    }
  }
}

// ── Normal login handler ─────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['forgot_password'])) {
  $login = trim($_POST['login'] ?? '');
  $password = $_POST['password'] ?? '';

  if (empty($login) || empty($password)) {
    $error = 'Please fill in all fields.';
  } else {
    $stmt = $conn->prepare(
      "SELECT id, username, email, password, role, first_name, last_name, status
             FROM users WHERE (username = ? OR email = ?) LIMIT 1"
    );
    $stmt->bind_param('ss', $login, $login);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$user) {
      $error = 'Invalid username/email or password.';
    } elseif ($user['status'] === 'Inactive') {
      $error = 'Your account is inactive. Please contact your teacher or admin.';
    } else {
      // Support for legacy plain text passwords + upgrade to hash on login
      $isHash = password_verify($password, $user['password']);
      $isPlain = (!$isHash && $password === $user['password']);

      if ($isHash || $isPlain) {
        // If password is plain text, re-hash it immediately
        if ($isPlain) {
          $hash = password_hash($password, PASSWORD_DEFAULT);
          $upd = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
          $upd->bind_param('si', $hash, $user['id']);
          $upd->execute();
          $upd->close();
        }

        // ── Admin: skip OTP, log in directly ─────────────────
        if ($user['role'] === 'Admin') {
          session_regenerate_id(true);
          $_SESSION['user_id']    = $user['id'];
          $_SESSION['username']   = $user['username'];
          $_SESSION['role']       = $user['role'];
          $_SESSION['first_name'] = $user['first_name'];
          $_SESSION['last_name']  = $user['last_name'];
          // ── Remember Me ───────────────────────────────────
          if (!empty($_POST['remember'])) {
            setcookie('remember_login', $login, time() + (30 * 24 * 60 * 60), '/', '', false, true);
          } else {
            setcookie('remember_login', '', time() - 3600, '/');
          }
          header('Location: admin.php');
          exit;
        }

        // ── Non-admin: Generate & store OTP ──────────────────
        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $otpHash = password_hash($otp, PASSWORD_DEFAULT);

        $otpStmt = $conn->prepare(
          "INSERT INTO login_otps (user_id, otp_hash, expires_at, used)
           VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 10 MINUTE), 0)
           ON DUPLICATE KEY UPDATE otp_hash = VALUES(otp_hash),
             expires_at = DATE_ADD(NOW(), INTERVAL 10 MINUTE), used = 0"
        );
        $otpStmt->bind_param('is', $user['id'], $otpHash);
        $otpStmt->execute();
        $otpStmt->close();

        // ── Send OTP via email ────────────────────────────────
        $otpSent = false;
        $otpMail = new PHPMailer(true);
        try {
          $otpMail->SMTPDebug  = SMTP::DEBUG_OFF;
          $otpMail->isSMTP();
          $otpMail->Host       = 'smtp.gmail.com';
          $otpMail->SMTPAuth   = true;
          $otpMail->Username   = 'marc.macarubbo@gmail.com';
          $otpMail->Password   = 'zkre tdpp feve edeh';
          $otpMail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
          $otpMail->Port       = 465;

          $otpMail->setFrom('marc.macarubbo@gmail.com', 'Arandia College eLMS');
          $otpMail->addAddress($user['email']);

          $otpMail->isHTML(true);
          $otpMail->Subject = 'Arandia College eLMS — Your Login OTP';

          $otpName = htmlspecialchars($user['first_name']);
          $otpMail->Body = '
            <!DOCTYPE html>
            <html>
            <head>
              <meta charset="UTF-8">
              <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #003087 0%, #0055cc 100%); color: white; padding: 35px 30px; text-align: center; border-radius: 12px 12px 0 0; }
                .header h1 { margin: 0 0 6px 0; font-size: 22px; }
                .header p { margin: 0; font-size: 13px; opacity: .8; }
                .gold-bar { width: 40px; height: 3px; background: #FFD700; border-radius: 2px; margin: 10px auto 0; }
                .content { background: #f9faff; padding: 32px 30px; border-radius: 0 0 12px 12px; border: 1px solid #e0e8ff; border-top: none; }
                .otp-box { background: white; border: 2px solid #003087; border-radius: 12px; padding: 28px 20px; text-align: center; margin: 24px 0; }
                .otp-code { font-size: 42px; font-weight: bold; letter-spacing: 10px; color: #003087; font-family: monospace; }
                .otp-label { color: #666; font-size: 13px; margin: 0 0 14px 0; }
                .otp-expires { margin-top: 14px; font-size: 12px; color: #888; }
                ul { padding-left: 18px; }
                ul li { margin-bottom: 5px; font-size: 13px; color: #555; }
                .footer { text-align: center; margin-top: 24px; padding-top: 20px; border-top: 1px solid #e8edf5; }
                .footer p { margin: 4px 0; font-size: 12px; color: #999; }
                .footer strong { color: #003087; }
              </style>
            </head>
            <body>
              <div class="container">
                <div class="header">
                  <h1>Login Verification</h1>
                  <p>Arandia College eLMS &mdash; SHS &amp; HS Portal</p>
                  <div class="gold-bar"></div>
                </div>
                <div class="content">
                  <h2 style="margin-top:0; color:#1a1a2e;">Hi ' . $otpName . ',</h2>
                  <p>Someone (hopefully you!) just signed in to your <strong>Arandia College eLMS</strong> account.</p>
                  <p>Use the code below to complete your login:</p>
                  <div class="otp-box">
                    <p class="otp-label">Your One-Time Password (OTP)</p>
                    <div class="otp-code">' . $otp . '</div>
                    <p class="otp-expires">This code expires in <strong>10 minutes</strong></p>
                  </div>
                  <p><strong>Security Reminders:</strong></p>
                  <ul>
                    <li>Never share this code with anyone</li>
                    <li>Arandia College eLMS staff will never ask for your OTP</li>
                    <li>If you did not attempt to log in, change your password immediately</li>
                  </ul>
                  <div class="footer">
                    <p><strong>Arandia College eLMS</strong></p>
                    <p>SHS &amp; HS Learning Portal</p>
                    <p style="font-size:10px; margin-top:12px;">This is an automated message. Please do not reply to this email.</p>
                  </div>
                </div>
              </div>
            </body>
            </html>';

          $otpMail->AltBody = "Hi {$otpName},\n\nYour Arandia College eLMS login OTP is: {$otp}\n\nThis code expires in 10 minutes. Do not share it with anyone.\n\n— Arandia College eLMS";
          $otpMail->send();
          $otpSent = true;
        } catch (Exception $e) {
          error_log("OTP PHPMailer error: " . $otpMail->ErrorInfo);
        }

        if ($otpSent) {
          // Store pending login data in session (no full login yet)
          $_SESSION['otp_pending_user_id']   = $user['id'];
          $_SESSION['otp_pending_username']   = $user['username'];
          $_SESSION['otp_pending_role']       = $user['role'];
          $_SESSION['otp_pending_first_name'] = $user['first_name'];
          $_SESSION['otp_pending_last_name']  = $user['last_name'];
          $_SESSION['otp_pending_remember']   = !empty($_POST['remember']) ? $login : '';
          $_SESSION['otp_pending_email_hint'] = substr($user['email'], 0, 3)
            . str_repeat('*', max(0, strpos($user['email'], '@') - 3))
            . substr($user['email'], strpos($user['email'], '@'));

          header('Location: verify_otp.php');
          exit;
        } else {
          $error = 'Could not send OTP email. Please try again or contact support.';
        }
      } else {
        $error = 'Invalid username/email or password.';
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
  <title>Login — Arandia College eLMS</title>
  <link
    href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&family=Inter:wght@400;500;600&display=swap"
    rel="stylesheet">
  <style>
    /* ─── VARIABLES & RESET (Matches Index) ─── */
    :root {
      --primary: #003087;
      --primary-dark: #001a4d;
      --primary-light: #0044cc;
      --accent: #FFD700;
      --accent-light: #fffbe6;
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
      padding: 0
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

    /* ─── TOPBAR (Matches Index) ─── */
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
      color: var(--accent)
    }

    /* ─── NAV (Matches Index) ─── */
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

    .nav-logo {
      width: 50px;
      height: 50px;
      object-fit: contain;
    }

    .nav-brand-text {
      line-height: 1.1
    }

    .nav-brand-text strong {
      display: block;
      font-family: 'Nunito', sans-serif; /* Matches Index */
      font-size: 1.1rem;
      font-weight: 900;
      color: var(--primary);
      letter-spacing: -0.5px
    }

    .nav-brand-text span {
      font-size: 0.75rem;
      color: var(--text-light);
      font-weight: 500
    }

    .back-btn {
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      font-family: 'Nunito', sans-serif;
      font-size: 0.85rem;
      font-weight: 800;
      color: white;
      text-decoration: none;
      padding: 0.55rem 1.4rem;
      background: linear-gradient(135deg, var(--primary), var(--primary-light));
      border-radius: 50px;
      box-shadow: 0 4px 14px rgba(0, 48, 135, 0.3);
      transition: all 0.2s ease;
      letter-spacing: 0.01em;
    }

    .back-btn::before {
      content: '←';
      font-size: 1rem;
      line-height: 1;
    }

    .back-btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(0, 48, 135, 0.45);
      background: linear-gradient(135deg, var(--primary-dark), var(--primary));
      color: white;
    }

    /* ─── Login wrapper ─── */
    .login-wrapper {
      flex: 1;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 2.5rem 1rem;
      background: linear-gradient(135deg, #e8f0ff 0%, #f0f2f5 50%, #fffbe6 100%)
    }

    .login-container {
      display: flex;
      width: 100%;
      max-width: 900px;
      background: white;
      border-radius: 24px; /* Matches Index Cards */
      box-shadow: var(--shadow-xl); /* Matches Index Cards */
      overflow: hidden;
      min-height: 520px
    }

    /* ─── Left panel ─── */
    .login-left {
      flex: 1;
      background: linear-gradient(160deg, #003087 0%, #0044bb 60%, #1a5ccc 100%);
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      padding: 3rem 2.5rem;
      position: relative;
      overflow: hidden
    }

    .login-left::before {
      content: '';
      position: absolute;
      width: 320px;
      height: 320px;
      border-radius: 50%;
      background: rgba(255, 255, 255, .05);
      top: -80px;
      right: -80px
    }

    .login-left::after {
      content: '';
      position: absolute;
      width: 200px;
      height: 200px;
      border-radius: 50%;
      background: rgba(255, 215, 0, .1);
      bottom: -50px;
      left: -50px
    }

    .left-logo {
      width: 90px;
      height: 90px;
      object-fit: contain;
      margin-bottom: 1.5rem;
      filter: drop-shadow(0 4px 16px rgba(0, 0, 0, .3));
      position: relative;
      z-index: 1
    }

    .left-title {
      font-family: 'Nunito', sans-serif;
      font-size: 1.5rem;
      font-weight: 900;
      color: white;
      text-align: center;
      margin-bottom: .4rem;
      position: relative;
      z-index: 1
    }

    .left-sub {
      font-size: .8rem;
      color: rgba(255, 255, 255, .7);
      text-align: center;
      line-height: 1.6;
      max-width: 220px;
      position: relative;
      z-index: 1
    }

    .left-divider {
      width: 40px;
      height: 3px;
      background: #FFD700;
      border-radius: 2px;
      margin: 1.25rem auto;
      position: relative;
      z-index: 1
    }

    .left-badges {
      display: flex;
      gap: .5rem;
      flex-wrap: wrap;
      justify-content: center;
      margin-top: 1rem;
      position: relative;
      z-index: 1
    }

    .left-badge {
      background: rgba(255, 255, 255, .12);
      border: 1px solid rgba(255, 255, 255, .2);
      color: rgba(255, 255, 255, .85);
      font-size: .68rem;
      font-weight: 700;
      padding: .3rem .75rem;
      border-radius: 100px
    }

    /* ─── Right panel ─── */
    .login-right {
      flex: 1.1;
      display: flex;
      flex-direction: column;
      justify-content: center;
      padding: 3rem 2.75rem
    }

    .login-greeting {
      font-size: .78rem;
      font-weight: 700;
      color: var(--primary);
      letter-spacing: .08em;
      text-transform: uppercase;
      margin-bottom: .4rem
    }

    .login-heading {
      font-family: 'Nunito', sans-serif; /* Matches Index */
      font-size: 2.5rem; /* Bigger to match Index headers */
      font-weight: 900;
      color: var(--text-dark);
      margin-bottom: .4rem
    }

    .login-desc {
      font-size: 1.1rem; /* Matches Index sub */
      color: var(--text-light);
      margin-bottom: 2rem;
      line-height: 1.7;
    }

    .form-group {
      margin-bottom: 1.5rem
    }

    .form-label {
      display: block;
      font-size: .8rem;
      font-weight: 700;
      color: #444;
      margin-bottom: .5rem
    }

    .form-input {
      width: 100%;
      padding: .8rem 1rem; /* Matches Index inputs */
      border: 1.5px solid #e0e4ee; /* Matches Index inputs */
      border-radius: 10px; /* Matches Index inputs */
      font-family: 'Inter', sans-serif; /* Matches Index inputs */
      font-size: .95rem; /* Matches Index inputs */
      color: var(--text-dark);
      background: var(--bg-light);
      transition: border-color .2s, box-shadow .2s;
      outline: none
    }

    .form-input:focus {
      border-color: var(--primary);
      box-shadow: 0 0 0 3px rgba(0, 48, 135, 0.08);
      background: white
    }

    .password-wrap {
      position: relative
    }

    .password-wrap .form-input {
      padding-right: 3rem
    }

    .toggle-pw {
      position: absolute;
      right: 0.75rem;
      top: 50%;
      transform: translateY(-50%);
      background: none;
      border: none;
      cursor: pointer;
      padding: 0.25rem;
      color: var(--text-light);
      display: flex;
      align-items: center;
      justify-content: center;
      border-radius: 6px;
      transition: color 0.2s, background 0.2s;
      outline: none;
    }

    .toggle-pw:hover {
      color: var(--primary);
      background: rgba(0, 48, 135, 0.07);
    }

    .toggle-pw:focus-visible {
      box-shadow: 0 0 0 3px rgba(0, 48, 135, 0.15);
    }

    .toggle-pw svg {
      width: 20px;
      height: 20px;
      display: block;
      transition: opacity 0.2s;
    }

    /* eye-off lines animate in/out */
    .toggle-pw .eye-slash {
      display: none;
    }

    .toggle-pw.is-visible .eye-open {
      display: none;
    }

    .toggle-pw.is-visible .eye-slash {
      display: block;
    }

    .form-footer {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 2rem
    }

    .remember-me {
      display: flex;
      align-items: center;
      gap: .4rem;
      font-size: .85rem;
      color: var(--text-light);
      cursor: pointer
    }

    .remember-me input {
      width: 16px;
      height: 16px;
      accent-color: var(--primary);
      cursor: pointer
    }

    .forgot-link {
      font-size: .85rem;
      font-weight: 600; /* Matches Index links */
      color: var(--primary);
      text-decoration: none;
      cursor: pointer;
      background: none;
      border: none;
      padding: 0
    }

    .forgot-link:hover {
      text-decoration: underline
    }

    .btn-login-main {
      width: 100%;
      padding: .9rem;
      background: linear-gradient(135deg, var(--primary), var(--primary-light)); /* Matches Index Buttons */
      color: white;
      font-family: 'Nunito', sans-serif; /* Matches Index Buttons */
      font-size: 1rem;
      font-weight: 800;
      border: none;
      border-radius: 12px; /* Matches Index Buttons */
      cursor: pointer;
      box-shadow: 0 4px 14px rgba(0, 48, 135, 0.3);
      transition: transform .2s, box-shadow .2s
    }

    .btn-login-main:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(0, 48, 135, 0.4)
    }

    .login-note {
      text-align: center;
      font-size: .85rem;
      color: var(--text-light);
      margin-top: 1.5rem
    }

    .login-note a {
      color: var(--primary);
      font-weight: 700;
      text-decoration: none
    }

    .error-msg {
      background: #fff0ec;
      border: 1px solid #ffcfbf;
      color: #c0392b;
      font-size: .85rem;
      font-weight: 600;
      padding: .8rem 1rem;
      border-radius: 10px;
      margin-bottom: 2rem;
      display: flex;
      align-items: center;
      gap: .5rem;
    }

    .page-footer {
      background: #0f172a;
      color: rgba(255, 255, 255, .6);
      font-size: .75rem;
      text-align: center;
      padding: .9rem 5%
    }

    /* ─── Forgot-Password Modal (Matches Index) ─── */
    .modal-overlay {
      position: fixed;
      inset: 0;
      background: rgba(0, 0, 0, .45);
      backdrop-filter: blur(5px); /* Matches Index Modal */
      display: flex;
      align-items: center;
      justify-content: center;
      z-index: 9999;
      opacity: 0;
      pointer-events: none;
      transition: opacity .25s;
    }

    .modal-overlay.open {
      opacity: 1;
      pointer-events: all
    }

    .modal-box {
      background: white;
      border-radius: 24px; /* Matches Index */
      padding: 3rem 2.5rem;
      width: 100%;
      max-width: 440px;
      box-shadow: var(--shadow-xl); /* Matches Index */
      position: relative;
      transform: translateY(24px) scale(.97);
      transition: transform .28s cubic-bezier(.22, 1, .36, 1);
    }

    .modal-overlay.open .modal-box {
      transform: translateY(0) scale(1)
    }

    .modal-close {
      position: absolute;
      top: 1.5rem;
      right: 1.5rem;
      background: #f1f5f9;
      border: none;
      cursor: pointer;
      font-size: 1.2rem;
      color: #64748b;
      line-height: 1;
      border-radius: 50%;
      width: 32px;
      height: 32px;
      transition: all 0.2s;
    }

    .modal-close:hover {
      background: #e2e8f0;
      color: var(--primary)
    }

    .modal-icon {
      width: 60px;
      height: 60px;
      background: linear-gradient(135deg, var(--primary), var(--primary-light));
      border-radius: 16px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.5rem;
      margin-bottom: 1.5rem;
      box-shadow: 0 4px 16px rgba(0, 48, 135, 0.25);
    }

    .modal-title {
      font-family: 'Nunito', sans-serif; /* Matches Index */
      font-size: 1.5rem;
      font-weight: 900;
      color: var(--text-dark);
      margin-bottom: .5rem
    }

    .modal-desc {
      font-size: .95rem;
      color: var(--text-light);
      margin-bottom: 2rem;
      line-height: 1.6
    }

    .modal-success {
      background: #f0fdf4;
      border: 1px solid #bbf7d0;
      color: #166534;
      font-size: .9rem;
      font-weight: 600;
      padding: .8rem 1rem;
      border-radius: 12px;
      margin-bottom: 2rem;
      display: flex;
      align-items: flex-start;
      gap: .5rem;
    }

    .modal-error {
      background: #fef2f2;
      border: 1px solid #fecaca;
      color: #991b1b;
      font-size: .9rem;
      font-weight: 600;
      padding: .8rem 1rem;
      border-radius: 12px;
      margin-bottom: 2rem;
      display: flex;
      align-items: flex-start;
      gap: .5rem;
    }

    .btn-reset {
      width: 100%;
      padding: .9rem;
      background: linear-gradient(135deg, var(--primary), var(--primary-light)); /* Matches Index Buttons */
      color: white;
      font-family: 'Nunito', sans-serif; /* Matches Index Buttons */
      font-size: 1rem;
      font-weight: 800;
      border: none;
      border-radius: 12px;
      cursor: pointer;
      box-shadow: 0 4px 14px rgba(0, 48, 135, .3);
      transition: transform .2s;
      margin-top: .5rem;
    }

    .btn-reset:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(0, 48, 135, .4)
    }

    .btn-reset:disabled {
      opacity: .6;
      cursor: not-allowed;
      transform: none;
      box-shadow: 0 4px 14px rgba(0, 48, 135, .3)
    }

    .resend-note {
      text-align: center;
      font-size: .8rem;
      color: var(--text-light);
      margin-top: .75rem;
      display: none;
    }

    .back-to-login {
      display: block;
      text-align: center;
      margin-top: 1.5rem;
      font-size: .9rem;
      color: var(--primary);
      font-weight: 700;
      cursor: pointer;
      background: none;
      border: none;
      width: 100%;
    }

    .back-to-login:hover {
      text-decoration: underline
    }

    /* ─── Responsive (Matches Index) ─── */
    @media (max-width: 900px) {
      .login-container {
        flex-direction: column
      }

      .login-left {
        padding: 3rem 2rem
      }

      .login-right {
        padding: 3rem 2rem
      }

      .login-heading {
        font-size: 2rem;
      }
      
      .nav-links { display: none; }
    }

    @media (max-width: 600px) {

      nav {
        padding: 0 4%;
        height: auto;
        min-height: 70px;
        flex-wrap: wrap;
        gap: 0.6rem;
        padding-top: 0.6rem;
        padding-bottom: 0.6rem;
      }

      .nav-logo {
        width: 38px;
        height: 38px;
      }

      .nav-brand {
        gap: 0.6rem;
      }

      .nav-brand-text strong {
        font-size: 0.9rem;
      }

      .nav-brand-text span {
        display: none;
      }

      .back-btn {
        padding: 0.5rem 1rem;
        font-size: 0.75rem;
      }

      .login-wrapper {
        padding: 1.5rem 1rem;
      }

      .login-container {
        min-height: auto;
        border-radius: 18px;
      }

      .login-left {
        padding: 2rem 1.5rem;
      }

      .left-logo {
        width: 64px;
        height: 64px;
        margin-bottom: 1rem;
      }

      .left-title {
        font-size: 1.2rem;
      }

      .left-sub {
        font-size: 0.75rem;
        max-width: none;
      }

      .left-badges {
        margin-top: 0.75rem;
      }

      .login-right {
        padding: 2rem 1.5rem;
      }

      .login-greeting {
        font-size: 0.72rem;
      }

      .login-heading {
        font-size: 1.6rem;
      }

      .login-desc {
        font-size: 0.9rem;
        margin-bottom: 1.5rem;
      }

      .form-group {
        margin-bottom: 1.25rem;
      }

      .form-footer {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.75rem;
        margin-bottom: 1.5rem;
      }

      .btn-login-main {
        font-size: 0.95rem;
      }

      .modal-overlay {
        padding: 1rem;
      }

      .modal-box {
        padding: 2rem 1.5rem;
        border-radius: 18px;
      }

      .modal-icon {
        width: 50px;
        height: 50px;
      }

      .modal-title {
        font-size: 1.25rem;
      }
    }
  </style>
</head>

<body>

  <div class="topbar">
    <a href="helpdesk.php">Campus Helpdesk</a><a href="FAQ.php">FAQ</a><a href="contact.php">Contact Us</a>
  </div>

  <nav>
    <a href="index.php" class="nav-brand">
      <img src="picture/logo.jpg" alt="Logo" class="nav-logo">
      <div class="nav-brand-text">
        <strong>Arandia College eLMS</strong>
        <span>Electronic Learning Management System</span>
      </div>
    </a>
    <a href="index.php" class="back-btn">Back to Home</a>
  </nav>

  <!-- ══════════════════════════════════════════════════════════
       Forgot-Password Modal
  ══════════════════════════════════════════════════════════ -->
  <div class="modal-overlay" id="fpModal" role="dialog" aria-modal="true" aria-labelledby="fpTitle">
    <div class="modal-box">
      <button class="modal-close" onclick="closeModal()" aria-label="Close">&times;</button>
      <div class="modal-icon">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:28px;height:28px"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
      </div>
      <div class="modal-title" id="fpTitle">Forgot Password?</div>
      <p class="modal-desc">Enter the email address linked to your account and we'll send you a password-reset link.</p>

      <?php if ($fpMsg): ?>
        <div class="modal-success"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;flex-shrink:0;margin-top:2px"><polyline points="20 6 9 17 4 12"/></svg> <?= htmlspecialchars($fpMsg) ?></div>
      <?php endif; ?>
      <?php if ($fpError): ?>
        <div class="modal-error"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;flex-shrink:0;margin-top:2px"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg> <?= htmlspecialchars($fpError) ?></div>
      <?php endif; ?>

      <form method="POST" action="login.php#fpModal" id="fpForm">
        <input type="hidden" name="forgot_password" value="1">
        <div class="form-group">
          <label class="form-label"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;vertical-align:middle;margin-right:4px"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg> Email Address</label>
          <input class="form-input" type="email" name="fp_email" placeholder="Enter your registered email"
            value="<?= htmlspecialchars($_POST['fp_email'] ?? '') ?>" autocomplete="email" required>
        </div>
        <button type="submit" class="btn-reset" id="fpSubmitBtn">Send Reset Link →</button>
        <p class="resend-note" id="fpResendNote"></p>
      </form>

      <button class="back-to-login" onclick="closeModal()">← Back to Sign In</button>
    </div>
  </div>

  <!-- ══════════════════════════════════════════════════════════
       Login
  ══════════════════════════════════════════════════════════ -->
  <div class="login-wrapper">
    <div class="login-container">

      <div class="login-left">
        <img src="picture/logo.jpg" alt="Logo" class="left-logo">
        <div class="left-title">Arandia College</div>
        <div class="left-divider"></div>
        <div class="left-sub">eLMS for Senior High School &amp; High School students and teachers.</div>
        <div class="left-badges">
          <span class="left-badge"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:13px;height:13px;vertical-align:middle;margin-right:4px"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg> Senior High</span>
          <span class="left-badge"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:13px;height:13px;vertical-align:middle;margin-right:4px"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg> High School</span>
          <span class="left-badge"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:13px;height:13px;vertical-align:middle;margin-right:4px"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg> Secure</span>
        </div>
      </div>

      <div class="login-right">
        <div class="login-greeting">Welcome Back</div>
        <h1 class="login-heading">Sign In</h1>
        <p class="login-desc">Enter your username or email and password to continue.</p>

        <?php if ($error): ?>
          <div class="error-msg"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;flex-shrink:0"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg> <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="login.php">
          <div class="form-group">
            <label class="form-label"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;vertical-align:middle;margin-right:4px"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg> Username or Email</label>
            <input class="form-input" type="text" name="login" placeholder="Enter your username or email"
              value="<?= htmlspecialchars($_POST['login'] ?? $rememberedLogin) ?>" autocomplete="username" required>
          </div>
          <div class="form-group">
            <label class="form-label"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;vertical-align:middle;margin-right:4px"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg> Password</label>
            <div class="password-wrap">
              <input class="form-input" type="password" name="password" id="password" placeholder="Enter your password"
                autocomplete="current-password" required>
              <button type="button" class="toggle-pw" id="togglePwBtn" onclick="togglePassword()" aria-label="Show password">
                <!-- Eye open -->
                <svg class="eye-open" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                  <circle cx="12" cy="12" r="3"/>
                </svg>
                <!-- Eye slash (when password is visible) -->
                <svg class="eye-slash" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/>
                  <path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/>
                  <line x1="1" y1="1" x2="23" y2="23"/>
                </svg>
              </button>
            </div>
          </div>
          <div class="form-footer">
            <label class="remember-me"><input type="checkbox" name="remember" <?= $rememberedLogin ? 'checked' : '' ?>> Remember me</label>
            <button type="button" class="forgot-link" onclick="openModal()">Forgot password?</button>
          </div>
          <button type="submit" class="btn-login-main">Sign In →</button>
        </form>

        <p class="login-note">Having trouble? <a href="contact.php">Contact your teacher or admin</a></p>
      </div>

    </div>
  </div>

  <div class="page-footer">© 2026 Arandia College eLMS — SHS &amp; HS Portal. All rights reserved.</div>

  <script>
    // ── Toggle password visibility ──────────────────────────
    function togglePassword() {
      const pw  = document.getElementById('password');
      const btn = document.getElementById('togglePwBtn');
      const showing = pw.type === 'text';
      pw.type = showing ? 'password' : 'text';
      btn.classList.toggle('is-visible', !showing);
      btn.setAttribute('aria-label', showing ? 'Show password' : 'Hide password');
    }

    // ── Modal helpers ───────────────────────────────────────
    const modal = document.getElementById('fpModal');

    function openModal() {
      modal.classList.add('open');
      const emailInput = modal.querySelector('input[type="email"]');
      if (emailInput) emailInput.focus();
    }

    function closeModal() {
      modal.classList.remove('open');
    }

    // Close on overlay click (but not on box click)
    modal.addEventListener('click', function (e) {
      if (e.target === modal) closeModal();
    });

    // Close on Escape
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') closeModal();
    });

    // Auto-open modal if the forgot-password form was submitted
    // (so the user sees their success/error feedback)
    <?php if ($fpMsg || $fpError): ?>
      window.addEventListener('DOMContentLoaded', openModal);
    <?php endif; ?>

    // ── Forgot-password resend cooldown (60s) ───────────────
    (function () {
      const COOLDOWN = 60; // seconds
      const STORAGE_KEY = 'fpLastSent';

      const fpForm    = document.getElementById('fpForm');
      const fpBtn      = document.getElementById('fpSubmitBtn');
      const resendNote = document.getElementById('fpResendNote');
      let fpTimer = null;

      function remainingSeconds() {
        const last = parseInt(localStorage.getItem(STORAGE_KEY) || '0', 10);
        const elapsed = Math.floor((Date.now() - last) / 1000);
        return Math.max(0, COOLDOWN - elapsed);
      }

      function startCooldown(seconds) {
        clearInterval(fpTimer);
        resendNote.style.display = 'block';
        fpBtn.disabled = true;

        function tick() {
          if (seconds <= 0) {
            clearInterval(fpTimer);
            fpBtn.disabled = false;
            fpBtn.textContent = 'Send Reset Link →';
            resendNote.style.display = 'none';
            return;
          }
          fpBtn.textContent = 'Resend in ' + seconds + 's';
          resendNote.textContent = 'You can request another link in ' + seconds + 's.';
          seconds--;
        }

        tick();
        fpTimer = setInterval(tick, 1000);
      }

      // Resume cooldown on page load if one is still active
      const remaining = remainingSeconds();
      if (remaining > 0) startCooldown(remaining);

      // Start cooldown the moment the form is submitted
      if (fpForm) {
        fpForm.addEventListener('submit', function () {
          localStorage.setItem(STORAGE_KEY, Date.now().toString());
        });
      }
    })();
  </script>
</body>

</html>