<?php
    session_start();
    include '../db.php';

    // Redirect to login if not logged in
    if (!isset($_SESSION['userID'])) {
        header("Location: ../LandingPage/LoginPage.php");
        exit();
    }

    $sellerID = $_SESSION['userID'];

    // Check if productID is passed
    if (!isset($_GET['productID'])) {
        echo "Product not found!";
        exit();
    }

    $userID = $_SESSION['userID'];
    $productID = (int)$_GET['productID'];

    // Fetch product details
    $sql = "SELECT p.productID, p.sellerID, p.product_name, p.description, p.stock, p.categoryID,
                   p.price, p.image, p.create_at, u.name as seller, u.profile as seller_profile, u.created_at
            FROM product p
            JOIN users u ON p.sellerID = u.userID
            WHERE productID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $productID);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo "Product not found or you don't have access to it.";
        exit();
    }

    $product = $result->fetch_assoc();

    $sqlProductNumber = "SELECT COUNT(productID) AS count FROM product WHERE sellerID = ?;";
    $stmtProductNumber = $conn->prepare($sqlProductNumber);
    $stmtProductNumber->bind_param("i", $product['sellerID']);
    $stmtProductNumber->execute();
    $resultProductNumber = $stmtProductNumber->get_result();
    $productNumber = $resultProductNumber->fetch_row()[0];

    $joinDate = new DateTime($product['created_at']);
    // $joinDate = new DateTime("2025-08-19"); // This variable is for testing purposes
    $dateToday = new DateTime(date("Y-m-d H:i:s", time()));

    $dateDifference = $joinDate->diff($dateToday);

    $totalMonths = ($dateDifference->y * 12) + $dateDifference->m;

    if ($totalMonths == 0) {
        if ($dateDifference->d == 1) {
            $joined = $dateDifference->d . " day";
        } else if ($dateDifference->d > 1) {
            $joined = $dateDifference->d . " days";
        } else {
            $joined = "Today";
        }
    } else if ($totalMonths < 12) {
        if ($dateDifference->m == 1) {
            $joined = $totalMonths . " month";
        } else {
            $joined = $totalMonths . " months";
        } 
    } else if ($totalMonths >= 12) {
        if ($dateDifference->y == 1) {
            $joined = $dateDifference->y . " year";
        } else {
            $joined = $dateDifference->y . " years";
        }
    } 
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        <?php echo htmlspecialchars($product['product_name']); ?>
    </title>
    <link rel="stylesheet" href="../GlobalFile/bootstrap-5.3.7-dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="../GlobalFile/global.css">
    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            background-color: #f8f9fa;
        }

        .product-title {
            font-size: 2rem;
            font-weight: bold;
            margin-top: 50px;
        }

        .price {
            font-size: 1.8rem;
            color: #d9534f;
            font-weight: bold;
        }

        .old-price {
            text-decoration: line-through;
            color: gray;
            font-size: 1rem;
        }

        img {
            max-width: 100%;
            height: auto;
        }

        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            padding-top: 80px;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
        }

        .modal-content {
            background: white;
            margin: auto;
            padding: 20px;
            border-radius: 10px;
            width: 500px;
            max-width: 90%;
            max-height: 70vh;
            overflow-y: auto;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            white-space: pre-line;
        }

        .close {
            float: right;
            font-size: 22px;
            font-weight: bold;
            cursor: pointer;
            color: #333;
        }

        .close:hover {
            color: red;
        }

        .buttons {
            margin-top: 2rem;
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
    
    <!-- MAIN -->
    <main class="container py-4 d-flex flex-column gap-4">
        <section class="card shadow-sm p-3">
            <div class="row g-4">
                <!-- Product Image -->
                <div class="col-md-6">
                    <?php if (isset($_SESSION['previous-page'])) {
                        if ($_SESSION['previous-page'] === 'favorites') {
                            echo '<a href="FavoritePage/Favorites.php"><button class="btn btn-outline-primary" style="margin-bottom:10px;">🠀 Back</button></a>';
                        } else if ($_SESSION['previous-page'] === 'homepage') {
                            echo '<a href="B-HomePage.php"><button class="btn btn-outline-primary" style="margin-bottom:10px;"> 🠀 Back</button></a>';
                        }
                    } else {
                        echo '<a href="B-HomePage.php"><button>Back</button></a>';
                    } ?>
                    <figure>
                        <?php if (!empty($product['image'])): ?>
                        <img src="../uploads/products/<?php echo htmlspecialchars($product['image']); ?>"
                            alt="<?php echo htmlspecialchars($product['product_name']); ?>"
                            class="img-fluid rounded">
                        <?php else: ?>
                        <img src="../uploads/products/no-image.png" alt="No Image">
                        <?php endif; ?>
                    </figure>
                </div>

                <!-- Product Details -->
                <div class="col-md-6">
                    <article>
                        <h1 class="product-title">
                            <?php echo htmlspecialchars($product['product_name']); ?>
                        </h1>
                        <!-- Price -->
                        <div class="mb-3">
                            <span class="price">₱
                                <?php echo number_format($product['price'], 2); ?>
                            </span>
                        </div>

                        <!-- Stock -->
                        <div class="mb-3">
                            <p>Stock:
                                <?php echo (int)$product['stock']; ?>
                            </p>
                        </div>

                        <!-- Buttons -->
                        <div class="d-flex gap-2 buttons">
                            <form id="cart" method=post action="AddCart.php"> 
                                <input type="hidden" name="productID" value="<?php echo $product['productID']; ?>">
                            </form>
                            <form id="buy" method="post" action="CheckoutPage.php">
                                <input type="hidden" name="products[<?php echo $product['productID']; ?>]" value="1">
                                <input type="hidden" name="mode" value="buynow">
                            </form>
                            <!-- MUST submit: grandTotal = productID, price -->
                            <!-- Already defined: quantity = 1, price = SQL -->

                            <a href="FavoritePage/AddFavorite.php?productID=<?php echo $product['productID']; ?>"
                                class="btn btn-outline-danger">❤</a>
                            <?php if ($product['stock'] === 0) { ?> 
                                <button form="cart" class="btn btn-outline-danger w-50" disabled>Add To Cart</button>
                                <button form="buy" class="btn btn-danger w-50" disabled>Buy Now ₱
                                    <?php echo number_format($product['price'], 2); ?>
                                </button>
                            <?php } else { ?>
                                <button form="cart" class="btn btn-outline-danger w-50">Add To Cart</button>
                                <button form="buy" class="btn btn-danger w-50">Buy Now ₱
                                    <?php echo number_format($product['price'], 2); ?>
                                </button>
                            <?php } ?>
                        </div>
                    </article>
                </div>
            </div>
        </section>

        <section class="card shadow-sm p-3">
            <div class="row align-items-center g-3">
                <!-- Seller Image -->
                <div class="col-auto">
                    <img src="../Seller/<?php echo htmlspecialchars($product['seller_profile']); ?>" alt="seller image" 
                        class="img-fluid rounded-circle" 
                        style="width: 100px; height: 100px; object-fit: cover;">
                </div>

                <!-- Seller Info + Actions -->
                <div class="col-auto">
                    <h1 class="mb-2" style="font-size: 30px;"><?php echo $product['seller']?></h1>
                    <a href="MessagePage/MessagePage.php?chatUser=<?php echo $product['sellerID']?>">
                        <button class="btn btn-danger">💬 Chat Now</button>
                    </a>
                </div>

                <!-- Divider + Stats (inline labels + numbers) -->
                <div class="col-auto border-start ps-4">
                    <div class="d-flex flex-column">
                        <?php if ($productNumber === 1)  { ?>
                            <p class="mb-2 fw-bold">Product: <span class="fw-normal"><?php echo $productNumber?></span></p>
                        <?php } else if ($productNumber > 1) { ?>
                            <p class="mb-2 fw-bold">Products: <span class="fw-normal"><?php echo $productNumber?></span></p>
                        <?php } ?>

                        <p class="mb-0 fw-bold">Joined: <span class="fw-normal"><?php echo $joined ?></span></p>
                    </div>
                </div>
            </div>
        </section>

        <section class="card shadow-sm p-3">
            <div class="row g-4">
                <h1>Product Details</h1>
                <p><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
            </div>
        </section>

        

        <!-- Modal -->
        <div id="descModal" class="modal">
            <div class="modal-content">
                <span class="close" onclick="closeModal()">&times;</span>
                <h3>📖 Full Description</h3>
                <p id="fullDesc"></p>
            </div>
        </div>

    </main>
    </section>

    <script src="../../bootstrap-5.3.7-dist/js/bootstrap.bundle.min.js"></script>
    <script src="../GlobalFile/nav-side.js"></script>
    <script src="../GlobalFile/popup-message.js"></script>

    <script>
    function openModal(description) {
        document.getElementById("fullDesc").innerText = description;
        document.getElementById("descModal").style.display = "block";
    }

    function closeModal() {
        document.getElementById("descModal").style.display = "none";
    }

    window.onclick = function(event) {
        let modal = document.getElementById("descModal");
        if (event.target === modal) {
            modal.style.display = "none";
        }
    }
    </script>
</body>

</html>