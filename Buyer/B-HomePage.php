<?php
    session_start();
    include '../db.php';

    // Redirect to login if not logged in
    if (!isset($_SESSION['userID'])) {
        header("Location: ../LandingPage/LoginPage.php");
        exit();
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
    $categoryFilter = isset($_GET['category']) ? (int)$_GET['category'] : 0;

    // Build SQL query based on category filter
    if ($categoryFilter > 0) {
        $sql = "SELECT productID, product_name, description, stock, categoryID, price, image, create_at
                FROM product
                WHERE categoryID = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $categoryFilter);
    } else {
        $sql = "SELECT productID, product_name, description, stock, categoryID, price, image, create_at
                FROM product;";
        $stmt = $conn->prepare($sql);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    $sqlCart = "SELECT c.cartID, c.productID, c.quantity, p.product_name, p.price, p.image
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

        /* Background paragraph content */
        .content {
        margin-left: 220px;
        padding: 20px;
        }
    </style>
</head>

<body onload="showAlert()">
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
            <form action="#">
                <div class="form-group">
                    <input type="text" placeholder="Search...">
                    <i class="fas fa-solid fa-magnifying-glass"></i>
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
                    <div class="category-item"><a href="?category=2">Living Room</a></div>
                </div>
                <div class="col-6 col-md-3 col-lg-2">
                    <div class="category-item"><a href="?category=1">Kitchen Appliances</a></div>
                </div>
                <div class="col-6 col-md-3 col-lg-2">
                    <div class="category-item"><a href="?category=5">Laundry Appliances</a></div>
                </div>
                <div class="col-6 col-md-3 col-lg-2">
                    <div class="category-item"><a href="?category=3">Bedroom Appliances</a></div>
                </div>
                <div class="col-6 col-md-3 col-lg-2">
                    <div class="category-item"><a href="?category=4">Bathroom Appliance</a></div>
                </div>
                <div class="col-6 col-md-3 col-lg-2">
                    <div class="category-item"><a href="?category=6">Outdoor Appliances</a></div>
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
                <?php while ($row = $resultCart->fetch_assoc()): ?>
                    <label class="product">
                        <?php if (!empty($row['image'])): ?>
                            <input type="checkbox" class="item" 
                                   data-id="<?php echo $row['productID']?>" data-price="<?php echo $row['price']?>"
                                   onchange="calculateTotal()"
                                   value="<?php echo $row['productID']?>">
                            <img src="../uploads/products/<?php echo htmlspecialchars($row['image']); ?>" alt="Product" class="img-fluid rounded">
                        <?php else: ?>
                            <img src="../uploads/products/no-image.png" alt="No Image">
                        <?php endif; ?>
                        <div class="product-info">
                            <h3><?php echo $row['product_name']?></h3>
                            <label for="quantity">Quantity:</label>
                            <input type="number" step="1" name="quantity" id="quantity" min="1"
                                   value="<?php echo $row['quantity']?>" oninput="calculateTotal()" required>
                            <input type="hidden" name="products[<?php echo $row['productID']?>]" id="quantity_1" form="checkoutForm">
                            <strong>₱<?php echo number_format($row['price'], 2)?></strong>
                        </div>
                    </label>
                <?php endwhile; ?>
            </div>

            <!-- Fixed bottom -->
            <div class="cart-summary">
                <form id="checkout" action="CheckoutPage.php" method="post">
                    <input type="hidden" name="quantity" value="">
                    <input type="hidden" name="productID" value="">
                    <input type="hidden" name="totalPrice" id="totalPrice" value="">
                </form>
                <span><strong>Total:</strong> <span id="totalPrice">₱0.00</span></span>

                <form id="checkoutForm" action="checkoutPage.php" method="post">
  <!-- Only one hidden for the grand total if you still want it -->
                    <input type="hidden" name="grandTotal" id="grandTotal">
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

        // For normal links (Home, Favorite, etc.)
        menuItems.forEach(item => {
            if (item.id !== "cartBtn") { // exclude Cart
                item.addEventListener('click', function() {
                    setActive(this);
                });
            }
        });

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

                    // ✅ Update hidden field
                    qtyField.value = quantity;
                } else {
                // Not checked → clear hidden value
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
            document.getElementById('grandTotal').value = total; // raw number for PHP
        }

        // Remove this function if you will replace the alert (popup message). This is connected to <body> using the onoad attribute
        // <?php if (isset($_GET['message'])) { ?>
        //     function showAlert() {
        //         alert("<?php echo htmlspecialchars($_GET['message']); ?>");
        //     }
        // <?php } ?>
        
    </script>
</body>

</html>