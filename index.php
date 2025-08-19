<?php 
    session_start();
    session_unset();
    session_destroy();
    session_start();
    include 'db.php'; 

    $search = isset($_GET['search']) ? trim($_GET['search']) : '';

    if (isset($_SESSION['userID'])) {
        $sellerID = $_SESSION['userID'];
        if ($search !== '') {
            $sql = "SELECT productID, product_name, description, stock, categoryID, price, image, create_at 
                    FROM product 
                    WHERE sellerID = ? AND product_name LIKE ?";
            $stmt = $conn->prepare($sql);
            $like = "%$search%";
            $stmt->bind_param("is", $sellerID, $like);
        } else {
            $sql = "SELECT productID, product_name, description, stock, categoryID, price, image, create_at 
                    FROM product 
                    WHERE sellerID = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $sellerID);
        }
    } else {
        if ($search !== '') {
            $sql = "SELECT productID, product_name, description, stock, categoryID, price, image, create_at 
                    FROM product
                    WHERE product_name LIKE ?";
            $stmt = $conn->prepare($sql);
            $like = "%$search%";
            $stmt->bind_param("s", $like);
        } else {
            $sql = "SELECT productID, product_name, description, stock, categoryID, price, image, create_at 
                    FROM product";
            $stmt = $conn->prepare($sql);
        }
    }

    $stmt->execute(); 
    $result = $stmt->get_result(); 
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chroma Home</title>

    <!-- Bootstrap and font awesome -->
    <link rel="stylesheet" href="GlobalFile/bootstrap-5.3.7-dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="GlobalFile/fontawesome-free-7.0.0-web/css/all.min.css">

    <!-- Home Page styles -->
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
        }

        body {
            overflow-x: hidden;
        }

        a {
            text-decoration: none;
        }

        li {
            list-style: none;
        }

        button {
            padding: 5px 10px;
            border-radius: 5px;
            background: var(--btn-bg-color);
            color: var(--text-color);
            cursor: pointer;
            transition: background 0.3s ease;
            width: fit-content;
        }

        /* CONTENT */
        #content {
            position: relative;
            width: 100%;
            transition: left .28s ease, width .28s ease;
        }

        /* NAVBAR */
        nav {
            background: var(--profile-bg-color);
            height: 64px;
            padding: 0 20px;
            display: flex;
            align-items: center;
            grid-gap: 28px;
            position: sticky;
            top: 0;
            left: 0;
            z-index: 100;
        }

        nav .toggle-sidebar {
            font-size: 18px;
            cursor: pointer;
            color: var(--icon-color);
        }

        nav form {
            width: 90%;
            margin-right: auto;
        }

        nav .form-group {
            position: relative;
            display: flex;
            align-items: center;
        }


        nav .form-group input {
            width: 100%;
            background: var(--background-color);
            border-radius: 5px;
            border: none;
            outline: none;
            padding: 10px 40px 10px 16px;
            /* extra right padding for the icon */
            color: var(--text-color);
            transition: all .3s ease;
        }

        nav .form-group input:focus {
            box-shadow: 0 0 0 1px var(--primary-color), 0 0 0 4px var(--secondary-color);
        }

        nav .form-group .fas {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            right: 16px;
            color: var(--secondary-color);
            font-size: 16px;
            /* match input text size */
            line-height: 1;
            /* prevent extra vertical space */
            height: auto;
            /* avoid weird vertical alignment from FA */
        }

        nav .divider {
            width: 1px;
            background: var(--hover-color);
            height: 12px;
            display: block;
        }

        /* ----- Responsive ----- */
        @media screen and (max-width: 768px) {
            nav .nav-link,
            nav .divider {
                display: none;
            }
        }

        /* ----- Global Main Container ----- */
        main {
            width: 100%;
            padding: 0px 20px 20px 20px;
        }

        /* ----- Categories ----- */
        .category-item {
            padding: 5px;
            border-radius: 10px;
            background: var(--profile-bg-color);
            box-shadow: 4px 4px 16px rgba(0, 0, 0, .2);
            color: aliceblue;
            font-size: 16px;
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 60px;
            transition: transform 0.2s ease;
        }

        .category-item:hover {
            transform: translateY(-3px);
        }

        .category-item a {
            color: var(--text-color);
            text-decoration: none;
            display: block;
            width: 100%;
            height: 100%;
        }

        /* ----- Cards ----- */
        .card {
            border-radius: 10px;
            background: var(--profile-bg-color);
            box-shadow: 4px 4px 16px rgba(0, 0, 0, .2);
            color: var(--text-color);
            border: none;
            overflow: hidden;
            transition: transform 0.2s ease;
        }

        .card:hover {
            transform: translateY(-5px);
        }

        .card a {
            text-decoration: none;
            color: inherit;
            display: block;
            width: 100%;
            height: 100%;
        }

        .card img {
            max-width: 100%;
            height: auto;
            border-radius: 5px;
        }

        .card h2 {
            font-size: 18px;
            font-weight: 600;
            margin: 10px 0 5px;
            color: var(--text-color);
        }

        .card p {
            font-size: 14px;
            color: var(--text-color);
        }
    </style>
</head>

<body>
    <!-- NAVBAR -->
    <section id="content">
        <nav>
            <form method="GET" action="index.php" style="width:90%;margin-right:auto;">
                <div class="form-group">
                    <input type="text" name="search" placeholder="Search product name..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                    <button type="submit" style="padding:6px 12px; border-radius:6px; border:none; background:#007bff; color:white;">Search</button>
                </div>
            </form>

            <span class="divider"></span>

            <a href="" class="same-link"><button>Login</button></a>
        </nav>
        <!-- NAVBAR -->

        <!-- MAIN -->
        <main class="container-fluid py-3">
            
            <!-- Product Cards -->
            <div class="row g-3 mt-4">
                <?php
                while ($row = $result->fetch_assoc()):?>
                    <div class="col-12 col-sm-6 col-md-4 col-lg-3 col-xl-2">
                        <div class="card h-100 text-center p-2" style="cursor:pointer;" onclick="window.location.href='LandingPage/LoginPage.php'">
                            <a href="#" class="same-link" tabindex="-1" style="pointer-events:none;">
                                <?php if (!empty($row['image'])): ?>
                                    <img src="uploads/products/<?php echo htmlspecialchars($row['image']); ?>" alt="Product" class="img-fluid rounded">
                                <?php else: ?>
                                    <img src="uploads/products/no-image.png" alt="No Image">
                                <?php endif; ?>
                                <hr>
                                <h2><?php echo htmlspecialchars($row['product_name']); ?></h2>
                                <p>₱<?php echo number_format($row['price'], 2); ?></p>
                            </a>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
            <!-- End of Product Cards -->
        </main>
    </section>

    <script src="GlobalFile/bootstrap-5.3.7-dist/js/bootstrap.bundle.min.js"></script>
    <script src="GlobalFile/fontawesome-free-7.0.0-web/js/all.js"></script>

    <script>
        document.querySelector('.same-link').href = "LandingPage/LoginPage.php";

        // LIVE SEARCH FUNCTIONALITY
        document.querySelector('input[name="search"]').addEventListener('input', function() {
            const q = this.value.toLowerCase();
            document.querySelectorAll('.card h2').forEach(function(h2) {
                const card = h2.closest('.col-12');
                if (h2.innerText.toLowerCase().includes(q)) {
                    card.style.display = '';
                } else {
                    card.style.display = 'none';
                }
            });
        });
    </script>
</body>

</html>