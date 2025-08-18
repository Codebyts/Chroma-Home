<?php
    session_start();
    require '../../db.php';
    require __DIR__ . '/SendMail.php'; // Use central mail function

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $name     = trim($_POST['name']);
        $email    = trim($_POST['email']);
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $role     = trim($_POST['role']);
        $province = $_POST['province'] ?? '';
        $city = $_POST['location'] ?? '';
        $verified_code = (string)rand(100000, 999999);

        // Check if email already exists
        $check = $conn->prepare("SELECT userID FROM users WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            echo "Email already registered.";
            exit();
        }
        $check->close();

        $loc_stmt = $conn->prepare("SELECT id FROM location WHERE province = ? AND city = ? LIMIT 1");
        $loc_stmt->bind_param("ss", $province, $city);
        $loc_stmt->execute();
        $loc_stmt->bind_result($locationID);
        if (!$loc_stmt->fetch()) {
            $_SESSION['error'] = "Invalid province or city selected.";
            exit;
        }
        $loc_stmt->close();
        // Insert into database
        $stmt = $conn->prepare("INSERT INTO users (name, email, password, role, locationID, verified_code, verified) VALUES (?, ?, ?, ?, ?, ?, 0)");
        $stmt->bind_param("ssssis", $name, $email, $password, $role, $locationID, $verified_code);

        if ($stmt->execute()) {
            // Send email
            if (sendVerificationEmail($email, $verified_code)) {
                $_SESSION['verify_email'] = $email;
                header("Location: verify.php");
                exit();
            } else {
                echo "Signup successful but failed to send verification email.";
            }
        } else {
            echo "Error: " . $stmt->error;
        }
    }
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chroma Home</title>
    <style>
        @import url("https://fonts.googleapis.com/css2?family=Poppins:wght@400;500&display=swap");

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

        .login__img {
            position: absolute;
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center;
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

<body onload="loadProvinces()">
    <div class="login">
        <form action="" class="login__form" method="POST">
            <h1 class="login__title">Register</h1>

            <div class="login__content">
                <div class="login__box">
                    <i class="bx bx-lock-alt"></i>
                    <div class="login__box-input">
                        <input name="name" type="text" name="fullname" class="login__input" placeholder="" required>
                        <label for="name" class="login__label">Full Name:</label>
                    </div>
                </div>

                <div class="login__box">
                    <i class="bx bx-lock-alt"></i>
                    <div class="login__box-input">
                        <input name="email" type="email"  class="login__input" placeholder="" required>
                        <label for="email" class="login__label">Email</label>
                    </div>
                </div>

                <div class="login__box">
                    <i class="ri-lock-2-line login__icon"></i>
                    <div class="login__box-input">
                        <input name="password" type="password"  class="login__input" id="login-pass" placeholder="" required>
                        <label for="password" class="login__label">Password</label>
                        <i class="ri-eye-off-line login__eye"></i>
                    </div>
                </div>

                <div>
                    <i class="bx bx-lock-alt"></i>
                    <div class="login__box-input">
                        <label>Role:</label><br>
                        <select name="role" required>
                            <option value="">Select Role</option>
                            <option value="seller">Seller</option>
                            <option value="buyer">Buyer</option>
                        </select>
                    </div>
                </div>

                <div>
                    <i class="bx bx-lock-alt"></i>
                    <div class="login__box-input">
                        <label>Region:</label><br>
                        <input type="text" value="Region 4A - CALABARZON" disabled>
                    </div>
                </div>

                <div>
                    <i class="bx bx-lock-alt"></i>
                    <div class="login__box-input">
                        <label>Province:</label><br>
                        <select id="province" name="province" onchange="loadCities()" required>
                            <option value="">Select Province</option>
                        </select>
                    </div>
                </div>

                <div>
                    <i class="bx bx-lock-alt"></i>
                    <div class="login__box-input">
                        <label>City:</label><br>
                        <select id="city" name="location" required>
                            <option value="">Select City</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="login__check">
                <div class="login__check-group">
                    <input type="checkbox" class="login__check-input">
                    <label for="" class="login__check-label">Remember me</label>
                </div>
            </div>

            <button type="submit" class="login__button">Register</button>

            <p class="login__register">
                Already have an account? <a href="../LoginPage.php">Login</a>
            </p>
        </form>
    </div>

    <script>
        const locationData = <?php
        $sql = "SELECT * FROM location WHERE region='Region 4A - CALABARZON'";
        $result = $conn->query($sql);
        $data = [];
        while($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        echo json_encode($data);
        ?>;

        function loadProvinces() {
            const provinceSelect = document.getElementById("province");
            const citySelect = document.getElementById("city");
            provinceSelect.innerHTML = '<option value="">Select Province</option>';
            citySelect.innerHTML = '<option value="">Select City</option>';

            const provinces = [...new Set(locationData.map(item => item.province))];
            provinces.forEach(province => {
                let opt = document.createElement("option");
                opt.value = province;
                opt.textContent = province;
                provinceSelect.appendChild(opt);
            });
        }

        function loadCities() {
            const province = document.getElementById("province").value;
            const citySelect = document.getElementById("city");
            citySelect.innerHTML = '<option value="">Select City</option>';

            locationData.filter(item => item.province === province).forEach(cityItem => {
                let opt = document.createElement("option");
                opt.value = cityItem.city;
                opt.textContent = cityItem.city;
                citySelect.appendChild(opt);
            });
        }
    </script>
</body>

</html>