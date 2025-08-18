<?php
    session_start();
    require '../../db.php';
    require __DIR__ . '/SendMail.php';

    if (!isset($_SESSION['verify_email'])) {
        header("Location: RegPage.php");
        exit();
    }

    $email = $_SESSION['verify_email'];

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
                $message = "Invalid verification code.";
                header("Location: verify.php?message=$message");
                exit();
            }
        }

        if (isset($_POST['resend_code'])) {
            $newCode = (string)rand(100000, 999999);
            $update = $conn->prepare("UPDATE users SET verified_code = ? WHERE email = ?");
            $update->bind_param("ss", $newCode, $email);
            $update->execute();
            $update->close();

            if (sendVerificationEmail($email, $newCode)) {
                $message = "Verification code resent to your email.";
                header("Location: verify.php?message=$message");
                exit();
            } else {
                $message = "Failed to resend verification code.";
                header("Location: verify.php?message=$message");
                exit();
            }
        }
    }
?>
<!DOCTYPE html>
<html>
<head>
    <title>Email Verification</title>
    <link rel="stylesheet" href="../../GlobalFile/bootstrap-5.3.7-dist/css/bootstrap.min.css">
    <style>
        :root {
            --primary-color: #182c63;
            --secondary-color: #430707;
            --text-color: #fff;
            --background-color: #11101d;
            --hover-color: #1e0707;
            --icon-color: #fff;
            --hover-text-color: #11101d;
            --profile-bg-color: #1d1b31;
            --btn-bg-color: #182c63;

            --white-color: hsl(0, 0%, 100%);
            --black-color: hsl(0, 0%, 0%);
            --body-font: "Poppins", sans-serif;
            --h1-font-size: 1.75rem;
            --normal-font-size: 1rem;
            --small-font-size: 0.813rem;
            --font-medium: 500;
        }

        * {
            box-sizing: border-box;
            padding: 0;
            margin: 0;
        }

        body,
        input,
        button {
            font-size: var(--normal-font-size);
            font-family: var(--body-font);
        }

        body {
            color: var(--white-color);
            background-color: var(--background-color);
        }

        input,
        button {
            border: none;
            outline: none;
        }

        a {
            text-decoration: none;
        }

        .register {
            position: relative;
            height: 100vh;
            display: grid;
            align-items: center;
        }

        .register__form {
            position: relative;
            background-color: hsla(0, 0%, 10%, 0.1);
            border: 2px solid var(--white-color);
            margin-inline: 1.5rem;
            padding: 2.5rem 1.5rem;
            border-radius: 1rem;
            backdrop-filter: blur(8px);
        }

        .register__title {
            text-align: center;
            font-size: var(--h1-font-size);
            font-weight: var(--font-medium);
            margin-bottom: 2rem;
        }

        .register__content,
        .register__box {
            display: grid;
        }

        .register__content {
            row-gap: 1.75rem;
            margin-bottom: 1.5rem;
        }

        .register__box {
            grid-template-columns: max-content 1fr;
            align-items: center;
            column-gap: 0.75rem;
            border-bottom: 2px solid var(--white-color);
        }


        .register__input {
            width: 100%;
            padding-block: 0.8rem;
            background: none;
            color: var(--white-color);
            position: relative;
            z-index: 1;
        }

        .register__box-input {
            position: relative;
        }

        .register__input:focus+.register__label,
        .register__input:not(:placeholder-shown)+.register__label {
            top: -12px;
            font-size: var(--small-font-size);
        }

        @media screen and (min-width: 576px) {
            .register {
                justify-content: center;
            }

            .register__form {
                width: 432px;
                padding: 4rem 3rem 3.5rem;
                border-radius: 1.5rem;
            }

            .register__title {
                font-size: 2rem;
            }
        }

        #popup-message {
            position: fixed;
            top: 80px;
            left: 50%;
            transform: translate(-50%, -50%);
            padding: 15px;
            z-index: 9999;
            border-radius: 5px;
            opacity: 1;
            transition: opacity 0.8s ease;
        }

        #popup-message.hide {
            opacity: 0;
        }
    </style>
</head>
<body>
    <!-- POPUP MESSAGE BOX -->
        <?php if (isset($_GET['message'])) { ?>
            <div id="popup-message">
                <p><?php echo $_GET['message']?></p>
            </div>
        <?php } ?>
        <!-- POP MESSAGE BOX -->
    <div class="register">
         <?php if (!empty($error)): ?>
            <p style="color:red;"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>
        <?php if (!empty($success)): ?>
            <p style="color:green;"><?= htmlspecialchars($success) ?></p>
        <?php endif; ?>

        <form action="" class="register__form" method="POST">
            <h1 class="register__title">Enter Verification Code</h1>

            <div class="register__content">
                <div class="register__box">
                    <i class="bx bx-lock-alt"></i>
                    <div class="register__box-input">
                        <input class="register__input" type="text" name="code" placeholder="Enter 6-digit code" maxlength="6" pattern="\d{6}">
                    </div>
                </div> 
            </div>
            <button class="btn btn-outline-primary" type="submit" name="resend_code">Resend Code</button>
            <button class="btn btn-primary" type="submit" name="verify_code">Verify</button>
        </form>
    </div>
    <script src="../../GlobalFile/popup-message.js"></script>
</body>
</html>
