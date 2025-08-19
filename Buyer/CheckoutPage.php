<?php
    session_start();
    include '../db.php';

    // Redirect to login if not logged in
    if (!isset($_SESSION['userID'])) {
        header("Location: ../LandingPage/LoginPage.php");
        exit();
    }

    $sellerID = $_SESSION['userID'];

    $sqlProfile = "SELECT u.name, u.email, u.locationID, u.profile, l.region, l.province, l.city
                   FROM users u
                   JOIN location l ON u.locationID = l.id
                   WHERE userID = ?";
    $stmtProfile = $conn->prepare($sqlProfile);
    $stmtProfile->bind_param("i", $sellerID);
    $stmtProfile->execute();
    $resultProfile = $stmtProfile->get_result();
    $userData = $resultProfile->fetch_assoc();

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
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

        table {
            table-layout: fixed;
            width: 100%;
            font-size: 14px;
        }

        table td,
        table th {
            word-wrap: break-word;
            white-space: normal;
        }

        @media (max-width:425px) {
            table {
                font-size: 12px;
            }
        }

        @media (max-width:375px) {
            table {
                font-size: 12px;
            }
        }
    </style>
</head>

<body>
    <!-- MAIN -->
    <main class="container py-4 d-flex flex-column gap-3">
        <section class="card shadow-sm p-3">
            <div class="row g-4">
                <!-- Product Image -->
                <div class="col-md-12">
                    <p style="padding: 0; margin: 0;color: #d9534f;">Delivery Address</p>
                    <p class="buyer-details"><strong><?php echo $userData['name']." ".$userData['email'] ?></strong> <?php echo $userData['city'].", ".$userData['province'].", ".$userData['region'] ?></p>
                </div>
            </div>
        </section>

        <section class="card shadow-sm p-3">
            <div class="row g-4 table-responsive">
                <table class="table table-striped-columns">
                    <thead>
                        <tr>
                            <th scope="col">Product Ordered</th>
                            <th scope="col">Unit Price</th>
                            <th scope="col">Quantity</th>
                            <th scope="col">Item Subtotal</th>
                        </tr>
                    </thead>

                    <?php 
                    $orderTotal = 0;
                    $grandTotal = 0;
                    
                    if (isset($_POST['products'])) {
                        foreach ($_POST['products'] as $productID => $quantity) {
                        // sanitize values
                            $productID = intval($productID);
                            $quantity = intval($quantity);

                        // only process items with quantity > 0
                            if ($quantity > 0) {
                                // Fetch product details from the database
                                $sqlProduct = "SELECT product_name, price, image FROM product WHERE productID = ?";
                                $stmtProduct = $conn->prepare($sqlProduct);
                                $stmtProduct->bind_param("i", $productID);
                                $stmtProduct->execute();
                                $resultProduct = $stmtProduct->get_result();
                                
                                while ($row = $resultProduct->fetch_assoc()) {
                                    $productName = htmlspecialchars($row['product_name']);
                                    $productPrice = htmlspecialchars($row['price']);
                                    $productImage = htmlspecialchars($row['image']);
                                    $itemSubtotal = $productPrice * $quantity;
                    ?> 
                    <tbody>
                        <tr>
                            <th scope="row"><img src="<?php echo $productImage ?>" alt="No Image"><?php echo $productName ?></th>
                            <td><?php echo "₱".number_format($productPrice, 2) ?></td>
                            <td><?php echo $quantity ?></td>
                            <td><?php echo "₱".number_format($itemSubtotal, 2) ?></td>
                        </tr>
                    </tbody>
                    <?php 
                    $orderTotal += $quantity;
                    $grandTotal += $itemSubtotal;
                                 }
                            }
                        }
                    }
                    ?>                   
                </table>
                <p class="text-end">Order Total (<?php echo $orderTotal ?>): <span><?php echo "₱".number_format($grandTotal, 2)?></span></p>
            </div>
        </section>

        <section class="card shadow-sm p-3">
            <div class="row g-4">
                <!-- Product Image -->
                <div class="col-md-12">
                    <div class="payment-method">
                        <span style="padding-right: 100px;"><strong>Payment Method</strong></span>
                        <p class="d-inline-flex gap-1">
                            <a class="btn disabled" aria-disabled="true" role="button"
                                data-bs-toggle="button">Credit/Debit Card</a>
                            <a class="btn disabled" aria-disabled="true" role="button" data-bs-toggle="button">Payment
                                Center / E-Wallet</a>
                            <a href="#" class="btn active" role="button" data-bs-toggle="button"
                                aria-pressed="true">Cash on Delivery</a>
                        </p>
                    </div>
                    <hr>
                    <div class="total text-end">
                        <p>Mechandise Subtotal: <span><?php echo "₱".number_format($grandTotal, 2)?></span></p>
                        <p>Shipping Subtotal: <span>₱0.00</span></p>
                        <p>Total Payment: <span><?php echo "₱".number_format($grandTotal, 2)?></span></p>
                    </div>
                    <hr width="100%">
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <form action="checkout.php" method="post"> 
                            <input type="hidden" name="products[<?php echo $productID ?>]" value="<?php echo $quantity ?>">
                            <input type="hidden" name="grandTotal" id="grandTotal" value="<?php echo $grandTotal ?>">
                            <button class="btn btn-danger" type="button">Place Order</button>
                        </form>
                    </div>
                </div>
            </div>
        </section>

    </main>
    </section>

    <script src="../../bootstrap-5.3.7-dist/js/bootstrap.bundle.min.js"></script>
    <script src="../GlobalFile/nav-side.js"></script>
</body>

</html>