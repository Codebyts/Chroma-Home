<?php
    session_start();
    include '../db.php';

    $search = isset($_GET['search']) ? trim($_GET['search']) : '';

    // Redirect to login if not logged in
    if (!isset($_SESSION['userID'])) {
        header("Location: ../LandingPage/LoginPage.php");
        exit();
    } else {
        $sellerID = $_SESSION['userID'];
        if ($search !== '') {
            $sql = "SELECT productID, product_name, description, stock, categoryID, price, image, create_at 
                    FROM product 
                    WHERE stock <> 0 AND product_name LIKE ?";
            $stmt = $conn->prepare($sql);
            $like = "%$search%";
            $stmt->bind_param("s", $sellerID, $like);
        } else {
            $sql = "SELECT productID, product_name, description, stock, categoryID, price, image, create_at 
                    FROM product 
                    WHERE stock <> 0";
            $stmt = $conn->prepare($sql);
        }
    } 

    $sellerID = $_SESSION['userID'];

    // Fetch profile image of logged-in user
    $sqlProfile = "SELECT profile FROM users WHERE userID = ?";
    $stmtProfile = $conn->prepare($sqlProfile);
    $stmtProfile->bind_param("i", $sellerID);
    $stmtProfile->execute();
    $resultProfile = $stmtProfile->get_result();
    $userData = $resultProfile->fetch_assoc();

    // If user has uploaded a profile, use it; otherwise default
    $profileImage = (!empty($userData['profile'])) ? $userData['profile'] : "default.png";

    // Read category from GET (but do NOT store in session)
    // One-time category filter: apply on click, ignore on reload
    $categoryFilter = 0;

    if (isset($_GET['category'])) {
        $clickedCategory = (int) $_GET['category'];

        // If this is the first load after clicking a category link, apply the filter.
        // If the same category is already marked in session, treat it as a reload and show ALL.
        if (!isset($_SESSION['category_once']) || (int)$_SESSION['category_once'] !== $clickedCategory) {
            $categoryFilter = $clickedCategory;          // apply filter
            $_SESSION['category_once'] = $clickedCategory; // mark as consumed
        } else {
            // Reload (F5/back/soft refresh) with same ?category -> reset to ALL
            $categoryFilter = 0;
            unset($_SESSION['category_once']); // allow filtering again on next click
        }
    } else {
        // No category in URL -> normal ALL products; also clear marker so next click filters again
        unset($_SESSION['category_once']);
    }

    // Build SQL query based on category filter
    if ($categoryFilter > 0) {
        $sql = "SELECT productID, product_name, description, stock, categoryID, price, image, create_at
                FROM product
                WHERE categoryID = ? AND stock <> 0;";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $categoryFilter);
    } else {
        $sql = "SELECT productID, product_name, description, stock, categoryID, price, image, create_at
                FROM product
                WHERE stock <> 0;";
        $stmt = $conn->prepare($sql);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    $sqlCart = "SELECT c.cartID, c.productID, c.quantity, p.product_name, p.stock, p.price, p.image
                FROM cart c 
                JOIN product p ON c.productID = p.productID
                WHERE c.userID = ?";

    $stmtCart = $conn->prepare($sqlCart);
    $stmtCart->bind_param("i", $sellerID);
    $stmtCart->execute();
    $resultCart = $stmtCart->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chroma Home</title>

    <!-- Bootstrap and font awesome -->
    <link rel="stylesheet" href="../GlobalFile/bootstrap-5.3.7-dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="../GlobalFile/fontawesome-free-7.0.0-web/css/all.min.css">

    <!-- Global styles -->
    <link rel="stylesheet" href="../GlobalFile/global.css">
    <link rel="stylesheet" href="../GlobalFile/nav-side.css">

    <!-- Home Page styles -->
    <link rel="stylesheet" href="styles/HP-style.css">

    <style>
        /* POPUP GENERAL */
        .popup-side {
        position: fixed;
        top: 0;
        height: 100%;
        width: 0;
        overflow: hidden;
        background: #fff;
        box-shadow: 2px 0 12px rgba(0,0,0,0.15);
        z-index: 1200;
        transition: width 0.3s ease;
        }

        .popup-side.active {
        width: 500px;
        }

        .popup-content {
        height: 100%;
        display: flex;
        flex-direction: column;
        padding: 20px;
        overflow: hidden;
        }

        .close-popup {
        font-size: 20px;
        cursor: pointer;
        align-self: flex-end;
        margin-bottom: 10px;
        }

        /* PRODUCT LIST */
        .product-list {
        flex: 1;
        overflow-y: auto;
        margin-bottom: 10px;
        }

        .product {
        display: flex;
        align-items: center; /* gitna vertically */
        gap: 20px;
        padding: 15px;
        background: var(--profile-bg-color);
        border-radius: 10px;
        cursor: pointer;
        margin-bottom: 15px;
        transition: 0.2s;
        }

        .product img {
        width: 150px;
        height: 200px;
        object-fit: cover;
        border-radius: 10px;
        }

        .product input[type="checkbox"] {
        margin-left: 10px;  /* usog papalapit sa image */
        margin-right: 10px;  /* dagdag space sa kanan */
        width: 20px;
        height: 20px;
        cursor: pointer;
        accent-color: #27ae60; /* green checkbox */
        }

        .product-info {
        flex: 1;
        }

        /* MAKE PRODUCT TEXT WHITE */
        .product-info h3,
        .product-info p {
        color: #fff;
        }

        /* MAKE PRICE STAND OUT WITH BRIGHT GREEN */
        .product-info strong {
        font-size: 16px;
        color: #2ecc71; /* bright green */
        font-weight: bold;
        letter-spacing: 1px;
        }

        .product-info h3 {
        font-size: 18px;
        margin-bottom: 5px;
        }

        .product-info p {
        font-size: 14px;
        margin-bottom: 8px;
        }

        /* CART SUMMARY */
        .cart-summary {
        position: sticky;
        bottom: 0;
        background: #fff;
        padding: 15px;
        border-top: 1px solid #ccc;
        display: flex;
        justify-content: space-between;
        align-items: center;
        }

        .cart-summary button {
        background: #27ae60;
        border: none;
        color: #fff;
        padding: 10px 18px;
        border-radius: 6px;
        cursor: pointer;
        font-size: 15px;
        }

        .cart-summary button:hover {
        background: #219150;
        }

        .cart-summary button:disabled {
            background-color: gray;
            cursor: not-allowed;
        }

        /* Background paragraph content */
        .content {
        margin-left: 220px;
        padding: 20px;
        }
    </style>

    <!-- Checkbox Style -->
    <style>
        .checkbox-wrapper-19 {
            box-sizing: border-box;
            --background-color: #fff;
            --checkbox-height: 25px;
        }

        @-moz-keyframes dothabottomcheck-19 {
            0% {
            height: 0;
            }
            100% {
            height: calc(var(--checkbox-height) / 2);
            }
        }

        @-webkit-keyframes dothabottomcheck-19 {
            0% {
            height: 0;
            }
            100% {
            height: calc(var(--checkbox-height) / 2);
            }
        }

        @keyframes dothabottomcheck-19 {
            0% {
            height: 0;
            }
            100% {
            height: calc(var(--checkbox-height) / 2);
            }
        }

        @keyframes dothatopcheck-19 {
            0% {
            height: 0;
            }
            50% {
            height: 0;
            }
            100% {
            height: calc(var(--checkbox-height) * 1.2);
            }
        }

        @-webkit-keyframes dothatopcheck-19 {
            0% {
            height: 0;
            }
            50% {
            height: 0;
            }
            100% {
            height: calc(var(--checkbox-height) * 1.2);
            }
        }

        @-moz-keyframes dothatopcheck-19 {
            0% {
            height: 0;
            }
            50% {
            height: 0;
            }
            100% {
            height: calc(var(--checkbox-height) * 1.2);
            }
        }

        .checkbox-wrapper-19 input[type=checkbox] {
            display: none;
        }

        .checkbox-wrapper-19 .check-box {
            height: var(--checkbox-height);
            width: var(--checkbox-height);
            background-color: transparent;
            border: calc(var(--checkbox-height) * .1) solid #000;
            border-radius: 5px;
            position: relative;
            display: inline-block;
            -moz-box-sizing: border-box;
            -webkit-box-sizing: border-box;
            box-sizing: border-box;
            -moz-transition: border-color ease 0.2s;
            -o-transition: border-color ease 0.2s;
            -webkit-transition: border-color ease 0.2s;
            transition: border-color ease 0.2s;
            cursor: pointer;
        }
        .checkbox-wrapper-19 .check-box::before,
        .checkbox-wrapper-19 .check-box::after {
            -moz-box-sizing: border-box;
            -webkit-box-sizing: border-box;
            box-sizing: border-box;
            position: absolute;
            height: 0;
            width: calc(var(--checkbox-height) * .2);
            background-color: #34b93d;
            display: inline-block;
            -moz-transform-origin: left top;
            -ms-transform-origin: left top;
            -o-transform-origin: left top;
            -webkit-transform-origin: left top;
            transform-origin: left top;
            border-radius: 5px;
            content: " ";
            -webkit-transition: opacity ease 0.5;
            -moz-transition: opacity ease 0.5;
            transition: opacity ease 0.5;
        }
        .checkbox-wrapper-19 .check-box::before {
            top: calc(var(--checkbox-height) * .72);
            left: calc(var(--checkbox-height) * .41);
            box-shadow: 0 0 0 calc(var(--checkbox-height) * .05) var(--background-color);
            -moz-transform: rotate(-135deg);
            -ms-transform: rotate(-135deg);
            -o-transform: rotate(-135deg);
            -webkit-transform: rotate(-135deg);
            transform: rotate(-135deg);
        }
        .checkbox-wrapper-19 .check-box::after {
            top: calc(var(--checkbox-height) * .37);
            left: calc(var(--checkbox-height) * .05);
            -moz-transform: rotate(-45deg);
            -ms-transform: rotate(-45deg);
            -o-transform: rotate(-45deg);
            -webkit-transform: rotate(-45deg);
            transform: rotate(-45deg);
        }

        .checkbox-wrapper-19 input[type=checkbox]:checked + .check-box,
        .checkbox-wrapper-19 .check-box.checked {
            border-color: #34b93d;
        }
        .checkbox-wrapper-19 input[type=checkbox]:checked + .check-box::after,
        .checkbox-wrapper-19 .check-box.checked::after {
            height: calc(var(--checkbox-height) / 2);
            -moz-animation: dothabottomcheck-19 0.2s ease 0s forwards;
            -o-animation: dothabottomcheck-19 0.2s ease 0s forwards;
            -webkit-animation: dothabottomcheck-19 0.2s ease 0s forwards;
            animation: dothabottomcheck-19 0.2s ease 0s forwards;
        }
        .checkbox-wrapper-19 input[type=checkbox]:checked + .check-box::before,
        .checkbox-wrapper-19 .check-box.checked::before {
            height: calc(var(--checkbox-height) * 1.2);
            -moz-animation: dothatopcheck-19 0.4s ease 0s forwards;
            -o-animation: dothatopcheck-19 0.4s ease 0s forwards;
            -webkit-animation: dothatopcheck-19 0.4s ease 0s forwards;
            animation: dothatopcheck-19 0.4s ease 0s forwards;
        }
    </style>

</head>

<body>
    <!-- SIDEBAR -->
    <section id="sidebar">
        <ul class="side-menu">
            <li><a href="B-HomePage.php" class="active" id="homeBtn"><i class="fas fa-solid fa-house" style="color: #ffffff;"></i> Home</a></li>
            <li><a href="#" id="cartBtn" onclick="openPopup('productPopup')"><i class="fas fa-solid fa-cart-shopping" style="color: #ffffff;"></i> Cart</a></li>
            <li><a href="MessagePage/MessagePage.php"><i class="fas fa-solid fa-message" style="color: #ffffff;"></i> Message</a></li>
            <li><a href="FavoritePage/Favorites.php"><i class="fas fa-solid fa-heart" style="color: #ffffff;"></i> Favorite</a></li>
        </ul>
    </section>
    <!-- SIDEBAR -->

    <!-- NAVBAR -->
    <section id="content">
        <nav>
            <button class="toggle-sidebar btn btn-outline-dark">☰</button>
            <form method="GET" action="index.php" style="width:90%;margin-right:auto;">
                <div class="form-group">
                    <input type="text" name="search" placeholder="Search product name..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                </div>
            </form>

            <span class="divider"></span>
            <div class="profile">
                <img src="<?php echo htmlspecialchars($profileImage); ?>" alt="Profile" class="rounded-circle"  style="width:40px; height:40px; object-fit:cover;">

                
                <ul class="profile-link">
                    <li><a href="B-EditProfile.php"><i class="fas fa-regular fa-circle-user"></i> Profile</a></li>
                    <li><a href="../index.php"><i class="fas fa-solid fa-arrow-right-from-bracket"></i>
                            Logout</a></li>
                </ul>
            </div>
        </nav>
        <!-- NAVBAR -->

        <!-- POPUP MESSAGE BOX -->
        <?php if (isset($_GET['message'])) { ?>
            <div id="popup-message">
                <p><?php echo $_GET['message']?></p>
            </div>
        <?php } ?>
        <!-- POP MESSAGE BOX -->

        <!-- MAIN -->
        <main class="container-fluid py-3">
            <!-- Categories -->
            <div class="row g-3">
                <div class="col-6 col-md-3 col-lg-2">
                    <div class="category-item"><a href="?category=2">🖥️ Living Room</a></div>
                </div>
                <div class="col-6 col-md-3 col-lg-2">
                    <div class="category-item"><a href="?category=1">🎛️ Kitchen Appliances</a></div>
                </div>
                <div class="col-6 col-md-3 col-lg-2">
                    <div class="category-item"><a href="?category=5">🚽 Laundry Appliances</a></div>
                </div>
                <div class="col-6 col-md-3 col-lg-2">
                    <div class="category-item"><a href="?category=3">🛏️ Bedroom Appliances</a></div>
                </div>
                <div class="col-6 col-md-3 col-lg-2">
                    <div class="category-item"><a href="?category=4">🛁 Bathroom Appliance</a></div>
                </div>
                <div class="col-6 col-md-3 col-lg-2">
                    <div class="category-item"><a href="?category=6">🚜 Outdoor Appliances</a></div>
                </div>
            </div>

            <!-- Product Cards -->
            <div class="row g-3 mt-4">
                <?php $_SESSION['previous-page'] = 'homepage';
                while ($row = $result->fetch_assoc()): ?>
                    <div class="col-12 col-sm-6 col-md-4 col-lg-3 col-xl-2">
                        <div class="card h-100 text-center p-2">
                            <a href="ProductDetails.php?productID=<?php echo $row['productID']; ?>">
                                <?php if (!empty($row['image'])): ?>
                                    <img src="../uploads/products/<?php echo htmlspecialchars($row['image']); ?>" alt="Product" class="img-fluid rounded">
                                <?php else: ?>
                                    <img src="../uploads/products/no-image.png" alt="No Image">
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

        <!-- Cart Popup -->
        <div class="popup-side" id="productPopup">
            <div class="popup-content wide">
            <div class="close-popup" onclick="closePopup('productPopup')">✖</div>
            <h1>Product List</h1>

            <!-- Scrollable products -->
            <div class="product-list">
                <?php while ($row = $resultCart->fetch_assoc()) { ?>
                    <label class="product">
                        <?php if (!empty($row['image'])): ?>
                            <div class="checkbox-wrapper-19">
                                <?php if ($row['stock'] === 0) { ?>
                                    <input type="checkbox" id="product<?php echo $row['productID']?>" class="item" data-id="<?php echo $row['productID']?>" data-price="<?php echo $row['price']?>" onchange="calculateTotal()" value="<?php echo $row['productID']?>" disabled>
                                    <label for="product<?php echo $row['productID']?>" class="check-box">
                                <?php } else { ?>
                                    <input type="checkbox" id="product<?php echo $row['productID']?>" class="item" data-id="<?php echo $row['productID']?>" data-price="<?php echo $row['price']?>" onchange="calculateTotal()" value="<?php echo $row['productID']?>">
                                    <label for="product<?php echo $row['productID']?>" class="check-box">
                                <?php } ?>
                            </div>
                            <img src="../uploads/products/<?php echo htmlspecialchars($row['image']); ?>" alt="Product" class="img-fluid rounded">
                        <?php else: ?>
                            <img src="../uploads/products/no-image.png" alt="No Image">
                        <?php endif; ?>
                        
                        <div class="product-info">
                            <h3><?php echo $row['product_name']?></h3>
                            <?php if ($row['stock'] <= 0) { ?>
                                <p class="text-danger">OUT OF STOCK</p>
                            <?php } else if ($row['stock'] <= 10) { ?>
                                <p><?php echo $row['stock']?> stocks left </p>
                                <label for="quantity">Quantity: </label>
                                <input type="number" step="1" name="quantity" id="quantity" min="1" 
                                       value="<?php echo $row['quantity']?>" oninput="calculateTotal()" 
                                       max="<?php echo $row['stock']?>" required> <br>
                            <?php } else { ?>
                                <label for="quantity">Quantity: </label>
                                <input type="number" step="1" name="quantity" id="quantity" min="1" 
                                       value="<?php echo $row['quantity']?>" oninput="enforceMax(this);calculateTotal()" 
                                       max="<?php echo $row['stock']?>" required> <br>
                            <?php } ?>

                            <input type="hidden" name="products[<?php echo $row['productID']?>]" id="quantity_<?php echo $row['productID']?>" form="checkoutForm">

                            <strong>₱<?php echo number_format($row['price'], 2)?></strong>
                        </div>
                    </label>
                <?php } ?>
            </div>

            <!-- Fixed bottom -->
            <div class="cart-summary">
                <span><strong>Total:</strong> <span id="totalPrice">₱0.00</span></span>

                <form id="checkoutForm" action="checkoutPage.php" method="post">
                    <button type="submit">Checkout</button>
                </form>
            </div>
            </div>
        </div>
    </section>

    <script src="../../bootstrap-5.3.7-dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../fontawesome-free-7.0.0-web/js/all.js"></script>
    <script src="../GlobalFile/nav-side.js"></script>
    <script src="../GlobalFile/popup-message.js"></script>
    <script>

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

        function openPopup(id) {
        document.getElementById(id).classList.add("active")
        }

        function closePopup(id) {
        document.getElementById(id).classList.remove("active");
        }

        const menuItems = document.querySelectorAll('.side-menu a');

        // Function to switch active
        function setActive(element) {
            menuItems.forEach(item => item.classList.remove('active')); // remove active from all
            element.classList.add('active'); // add active to clicked
        }

        // Handle Cart separately (since it doesn’t navigate)
        document.getElementById('cartBtn').addEventListener('click', function(e) {
            e.preventDefault(); // prevent page reload
            setActive(this);    // set Cart as active
            openPopup('productPopup'); // open popup
        });

        window.addEventListener("DOMContentLoaded", function () {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get("open") === "cart") {
                // remove active from all
                document.querySelectorAll('.side-menu a').forEach(item => item.classList.remove('active'));
                // highlight cart
                document.getElementById("cartBtn").classList.add("active");
                // open popup
                openPopup('productPopup');
            }
        });

        const checkoutBtn = document.querySelector('#checkoutForm button[type="submit"]');
        const checkboxes = document.querySelectorAll('input.item[type="checkbox"]');

        function toggleCheckoutButton() {
            const anyChecked = Array.from(checkboxes).some(cb => cb.checked);
            checkoutBtn.disabled = !anyChecked;
        }

        checkboxes.forEach(cb => cb.addEventListener('change', toggleCheckoutButton));
        toggleCheckoutButton();

        // For normal links (Home, Favorite, etc.)
        menuItems.forEach(item => {
            if (item.id !== "cartBtn") { // exclude Cart
                item.addEventListener('click', function() {
                    setActive(this);
                });
            }
        });

        function enforceMax(input) {
            const max = parseInt(input.max);
            const value = parseInt(input.value) || 0;

            if (value > max) {
                input.value = max;
            } else if (value < parseInt(input.min)) {
                input.value = input.min;
            }
        }
        function calculateTotal() {
            let total = 0;
            let products = document.querySelectorAll('.product');

            products.forEach((product) => {
                let checkbox = product.querySelector('input[type="checkbox"]');
                let quantityInput = product.querySelector('input[type="number"]');
                let productId = checkbox.dataset.id;
                let price = parseFloat(checkbox.dataset.price);

                let qtyField = document.getElementById('quantity_' + productId);

                if (checkbox.checked) {
                    let quantity = parseInt(quantityInput.value) || 1;
                    total += price * quantity;

                    qtyField.value = quantity;
                } else {
                    qtyField.value = "";
                }
            });

            // Formats the total to currency formatting
            const formatted = new Intl.NumberFormat("en-PH", {
                style: "currency",
                currency: "PHP",
                minimumFractionDigits: 2
            }).format(total);

            document.getElementById('totalPrice').textContent = formatted;
            document.getElementById('grandTotal').value = total; 
        }        
    </script>
</body>

</html>