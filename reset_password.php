<?php
// ============================================================
//  Arandia College eLMS — Reset Password
//  File: reset_password.php
// ============================================================
session_start();
require_once 'config/conn.php';

 $error = '';
 $success = '';
 $tokenValid = false;
 $user_id = null;

// ── 1. Check if Token is present in URL ─────────────────────
 $token = $_GET['token'] ?? '';

if (empty($token)) {
    $error = "Invalid request. No token provided.";
} else {
    // ── 2. Validate Token in Database ─────────────────────────
    // Join with users table to check if account is active
    $stmt = $conn->prepare("
        SELECT pr.user_id 
        FROM password_resets pr
        JOIN users u ON pr.user_id = u.id
        WHERE pr.token = ? 
          AND pr.used = 0 
          AND pr.expires_at > NOW()
          AND u.status = 'Active'
        LIMIT 1
    ");
    $stmt->bind_param('s', $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $user_id = $row['user_id'];
        $tokenValid = true;
    } else {
        $error = "This reset link is invalid, has expired, or has already been used. 
                  <br><a href='login.php'>Click here to request a new one.</a>";
    }
    $stmt->close();
}

// ── 3. Handle Password Update (POST) ────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $tokenValid) {
    $new_password = trim($_POST['new_password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');

    // Basic Validation
    if (empty($new_password) || empty($confirm_password)) {
        $error = "Please fill in both password fields.";
    } elseif (strlen($new_password) < 6) {
        $error = "Password must be at least 6 characters long.";
    } elseif ($new_password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        // Hash the new password
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

        // Update Password in Users Table
        $updateStmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $updateStmt->bind_param('si', $hashed_password, $user_id);
        
        if ($updateStmt->execute()) {
            // Invalidate the token so it can't be used again
            $invalidateStmt = $conn->prepare("UPDATE password_resets SET used = 1 WHERE user_id = ?");
            $invalidateStmt->bind_param('i', $user_id);
            $invalidateStmt->execute();
            $invalidateStmt->close();

            // Set success flag
            $success = true;
            $tokenValid = false; // Hide form after success
        } else {
            $error = "An error occurred while updating your password. Please try again.";
        }
        $updateStmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password — Arandia College eLMS</title>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&family=Open+Sans:wght@400;500;600&display=swap"
        rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0 }
        body {
            font-family: 'Open Sans', sans-serif;
            background: linear-gradient(135deg, #e8f0ff 0%, #f0f2f5 50%, #fffbe6 100%);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }
        .reset-container {
            background: white;
            width: 100%;
            max-width: 450px;
            padding: 2.5rem 2rem;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0, 48, 135, 0.15);
            text-align: center;
        }
        .logo-area {
            margin-bottom: 1.5rem;
        }
        .logo-img {
            width: 70px;
            height: 70px;
            object-fit: contain;
        }
        .title {
            font-family: 'Nunito', sans-serif;
            font-size: 1.8rem;
            font-weight: 900;
            color: #003087;
            margin-bottom: 0.5rem;
        }
        .subtitle {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 2rem;
        }
        .form-group {
            margin-bottom: 1.25rem;
            text-align: left;
        }
        .label {
            display: block;
            font-size: 0.8rem;
            font-weight: 700;
            color: #444;
            margin-bottom: 0.4rem;
        }
        .input {
            width: 100%;
            padding: 0.8rem 1rem;
            border: 1.5px solid #e0e4ee;
            border-radius: 10px;
            font-family: 'Open Sans', sans-serif;
            font-size: 0.95rem;
            outline: none;
            transition: border-color 0.2s;
        }
        .input:focus {
            border-color: #003087;
            box-shadow: 0 0 0 3px rgba(0, 48, 135, 0.1);
        }
        .btn {
            width: 100%;
            padding: 0.9rem;
            background: linear-gradient(135deg, #003087, #0055cc);
            color: white;
            font-family: 'Nunito', sans-serif;
            font-size: 1rem;
            font-weight: 800;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            transition: transform 0.2s;
            margin-top: 0.5rem;
        }
        .btn:hover { transform: translateY(-2px); }
        
        .alert-error {
            background: #fff0ec;
            border: 1px solid #ffcfbf;
            color: #c0392b;
            font-size: 0.85rem;
            padding: 0.8rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            text-align: left;
        }
        
        .success-box {
            padding: 2rem 0;
        }
        .success-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
            display: block;
        }
        .success-text {
            color: #1a7a47;
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 2rem;
        }
        .btn-secondary {
            background: transparent;
            color: #003087;
            border: 2px solid #003087;
        }
        .btn-secondary:hover {
            background: #003087;
            color: white;
        }

        /* Password strength visual hint */
        .strength-hint {
            font-size: 0.75rem;
            color: #888;
            margin-top: 4px;
        }
    </style>
</head>

<body>

    <div class="reset-container">
        <div class="logo-area">
            <!-- I-adjust ang path ng logo kung nasa ibang folder -->
            <img src="picture/logo.jpg" alt="Arandia Logo" class="logo-img" onerror="this.style.display='none'">
        </div>

        <?php if ($success): ?>
            <!-- ── Success State ──────────────────────────────────── -->
            <div class="success-box">
                <span class="success-icon">🎉</span>
                <h2 class="title">Password Changed!</h2>
                <p class="success-text">Your password has been successfully reset. You can now log in with your new password.</p>
                <a href="login.php" class="btn">Go to Login →</a>
            </div>

        <?php elseif ($tokenValid): ?>
            <!-- ── Reset Form ─────────────────────────────────────── -->
            <h2 class="title">Create New Password</h2>
            <p class="subtitle">Please enter your new password below.</p>

            <?php if ($error): ?>
                <div class="alert-error">
                    ⚠️ <?= $error ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="reset_password.php?token=<?= htmlspecialchars($token) ?>">
                <!-- Hidden user ID passed securely for processing -->
                <input type="hidden" name="user_id" value="<?= $user_id ?>">

                <div class="form-group">
                    <label class="label">🔑 New Password</label>
                    <input type="password" name="new_password" class="input" required minlength="6" placeholder="Enter new password">
                    <p class="strength-hint">Minimum 6 characters</p>
                </div>

                <div class="form-group">
                    <label class="label">🔒 Confirm Password</label>
                    <input type="password" name="confirm_password" class="input" required minlength="6" placeholder="Confirm new password">
                </div>

                <button type="submit" class="btn">Reset Password</button>
            </form>

        <?php else: ?>
            <!-- ── Error State (Invalid/Expired Token) ────────────── -->
            <h2 class="title">Oops!</h2>
            <div class="alert-error" style="text-align: center;">
                <?= $error ?>
            </div>
            <a href="login.php" class="btn btn-secondary">← Back to Login</a>
        <?php endif; ?>
    </div>

</body>
</html>