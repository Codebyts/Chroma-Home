<?php
include '../db.php';

// 🔹 Always start fresh when opening login.php
session_start();
session_unset();
session_destroy();
session_start();

// Show verification success popup message if set
if (isset($_SESSION['verified_success'])) {
    echo "<script>alert('" . addslashes($_SESSION['verified_success']) . "');</script>";
    unset($_SESSION['verified_success']);
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // Fetch user details
    $stmt = $conn->prepare("SELECT userID, password, name, role FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 1) {
        $stmt->bind_result($userID, $hashed_password, $name, $role);
        $stmt->fetch();

        if (password_verify($password, $hashed_password)) {
            // Store session variables
            $_SESSION['userID'] = $userID;
            $_SESSION['name']   = $name;
            $_SESSION['role']   = $role;

            // Redirect based on role
            if ($role === 'Seller') {
                header("Location: ../Seller/S-HomePage.php");
            } elseif ($role === 'Buyer') {
                header("Location: ../Buyer/B-HomePage.php");
            }
            exit();
        } else {
            $error = "Incorrect password.";
        }
    } else {
        $error = "Email not found.";
    }
    $stmt->close();
}
?>





<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chroma Home</title>
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

        img {
            max-width: 100%;
            height: auto;
        }

        .login {
            position: relative;
            height: 100vh;
            display: grid;
            align-items: center;
        }

        .login__form {
            position: relative;
            background-color: hsla(0, 0%, 10%, 0.1);
            border: 2px solid var(--white-color);
            margin-inline: 1.5rem;
            padding: 2.5rem 1.5rem;
            border-radius: 1rem;
            backdrop-filter: blur(8px);
        }

        .login__title {
            text-align: center;
            font-size: var(--h1-font-size);
            font-weight: var(--font-medium);
            margin-bottom: 2rem;
        }

        .login__content,
        .login__box {
            display: grid;
        }

        .login__content {
            row-gap: 1.75rem;
            margin-bottom: 1.5rem;
        }

        .login__box {
            grid-template-columns: max-content 1fr;
            align-items: center;
            column-gap: 0.75rem;
            border-bottom: 2px solid var(--white-color);
        }

        .login__icon,
        .login__eye {
            font-size: 1.25rem;
        }

        .login__input {
            width: 100%;
            padding-block: 0.8rem;
            background: none;
            color: var(--white-color);
            position: relative;
            z-index: 1;
        }

        .login__box-input {
            position: relative;
        }

        .login__label {
            position: absolute;
            left: 0;
            top: 13px;
            font-weight: var(--font-medium);
            transition: top 0.3s, font-size 0.3s;
        }

        .login__eye {
            position: absolute;
            right: 0;
            top: 18px;
            z-index: 10;
            cursor: pointer;
        }

        .login__box:nth-child(2) input {
            padding-right: 1.8rem;
        }

        .login__check,
        .login__check-group {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .login__check {
            margin-bottom: 1.5rem;
        }

        .login__check-label,
        .login__forgot,
        .login__register {
            font-size: var(--small-font-size);
        }

        .login__check-group {
            column-gap: 0.5rem;
        }

        .login__check-input {
            width: 16px;
            height: 16px;
        }

        .login__forgot {
            color: var(--white-color);
        }

        .login__forgot:hover {
            text-decoration: underline;
        }

        .login__button {
            width: 100%;
            padding: 1rem;
            border-radius: 0.5rem;
            background-color: var(--btn-bg-color);
            font-weight: var(--font-medium);
            cursor: pointer;
            margin-bottom: 2rem;
            color: var(--text-color);
        }

        .login__register {
            text-align: center;
        }

        .login__register a {
            color: var(--white-color);
            font-weight: var(--font-medium);
        }

        .login__register a:hover {
            text-decoration: underline;
        }

        /* FIX: Keep label on top if input has value */
        .login__input:focus+.login__label,
        .login__input:not(:placeholder-shown)+.login__label {
            top: -12px;
            font-size: var(--small-font-size);
        }

        @media screen and (min-width: 576px) {
            .login {
                justify-content: center;
            }

            .login__form {
                width: 432px;
                padding: 4rem 3rem 3.5rem;
                border-radius: 1.5rem;
            }

            .login__title {
                font-size: 2rem;
            }
        }
    </style>
</head>

<body>
    <div class="login">
        <?php if ($error): ?>
            <p class="error" style="color:red;"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>
        <form action="" class="login__form" method="POST">
            <h1 class="login__title">Login</h1>

            <div class="login__content">
                <div class="login__box">
                    <i class="bx bx-lock-alt"></i>
                    <div class="login__box-input">
                        <input type="email" required class="login__input" placeholder="" name="email">
                        <label for="email" class="login__label">Email</label>
                    </div>
                </div>

                <div class="login__box">
                    <i class="ri-lock-2-line login__icon"></i>
                    <div class="login__box-input">
                        <input type="password" required class="login__input" id="login-pass" name="password" placeholder="">
                        <label for="password" class="login__label">Password</label>
                        <i class="ri-eye-off-line login__eye"></i>
                    </div>
                </div>
                
            </div>

            <div class="login__check">
                <div class="login__check-group">
                    <input type="checkbox" class="login__check-input">
                    <label for="" class="login__check-label">Remember me</label>
                </div>

                <a href="#" class="login__forgot">Forgot Password?</a>
            </div>

            <button type="submit" class="login__button">Login</button>

            <p class="login__register">
                Don't have an account? <a href="RegisterPage/RegPage.php">Register</a>
            </p>
        </form>
    </div>

    <script>
        const showHiddenPass = (loginPass, loginEye) => {
            const input = document.getElementById(loginPass),
                iconEye = document.querySelector(loginEye);

            iconEye.addEventListener('click', () => {
                if (input.type === 'password') {
                    input.type = 'text';
                    iconEye.classList.add('ri-eye-line');
                    iconEye.classList.remove('ri-eye-off-line');
                } else {
                    input.type = 'password';
                    iconEye.classList.remove('ri-eye-line');
                    iconEye.classList.add('ri-eye-off-line');
                }
            });
        };

        showHiddenPass('login-pass', '.login__eye');
    </script>
</body>

</html>