<?php
session_start();
require '../../db.php';
require __DIR__ . '/SendMail.php';

if (!isset($_SESSION['verify_email'])) {
    header("Location: RegPage.php");
    exit();
}

$email = $_SESSION['verify_email'];
$error = "";
$success = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['verify_code'])) {
        $inputCode = trim($_POST['code'] ?? '');

        // Get stored code from DB
        $stmt = $conn->prepare("SELECT verified_code FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->bind_result($storedCode);
        $stmt->fetch();
        $stmt->close();

        if ($inputCode === $storedCode && !empty($storedCode)) {
            $update = $conn->prepare("UPDATE users SET verified = 1, verified_code = NULL WHERE email = ?");
            $update->bind_param("s", $email);
            $update->execute();
            $update->close();

            unset($_SESSION['verify_email']);
            $_SESSION['verified_success'] = "Your email is verified! Please log in.";
            header("Location: ../LoginPage.php");
            exit();
        } else {
            $error = "Invalid verification code.";
        }
    }

    if (isset($_POST['resend_code'])) {
        $newCode = (string)rand(100000, 999999);
        $update = $conn->prepare("UPDATE users SET verified_code = ? WHERE email = ?");
        $update->bind_param("ss", $newCode, $email);
        $update->execute();
        $update->close();

        if (sendVerificationEmail($email, $newCode)) {
            $success = "Verification code resent to your email.";
        } else {
            $error = "Failed to resend verification code.";
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Email Verification</title>
</head>
<body>
    <h2>Enter Verification Code</h2>

    <?php if (!empty($error)): ?>
        <p style="color:red;"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>
    <?php if (!empty($success)): ?>
        <p style="color:green;"><?= htmlspecialchars($success) ?></p>
    <?php endif; ?>

    <form method="POST">
        <input type="text" name="code" placeholder="Enter 6-digit code" required maxlength="6" pattern="\d{6}">
        <button type="submit" name="verify_code">Verify</button>
    </form>

    <form method="POST">
        <button type="submit" name="resend_code">Resend Code</button>
    </form>
</body>
</html>
