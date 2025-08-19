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
                    <p class="buyer-details"><strong><?php echo $userData['name']." &nbsp● ".$userData['email'] ?></strong> &nbsp● <?php echo $userData['city'].", ".$userData['province'].", ".$userData['region'] ?></p>
                </div>
            </div>
        </section>

        <section class="card shadow-sm p-3">
            <div class="row g-4 table-responsive">
                <table class="table table-striped-columns text-center">
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
                        $conditions = [];
                        foreach ($_POST['products'] as $productID => $quantity) {

                            $productID = intval($productID);
                            $quantity = intval($quantity);

                            if ($quantity > 0) {

                                $conditions[] = "c.productID = " . $productID;

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
                            <th scope="row"><img src="../uploads/products/<?php echo $productImage ?>" alt="No Image" style="width: 50px;"> &nbsp <?php echo $productName ?></th>
                            <td><?php echo "₱".number_format($productPrice, 2) ?></td>
                            <td><?php echo $quantity ?></td>
                            <td><?php echo "₱".number_format($itemSubtotal, 2) ?></td>
                        </tr>
                    </tbody>
                    <?php 
                                    $orderTotal += $quantity;
                                    $grandTotal += $itemSubtotal;
                    ?>
                                    <input type="hidden" name="products[<?php echo $productID; ?>][quantity]" value="<?php echo $quantity; ?>" form="placeorder">
                                    <input type="hidden" name="products[<?php echo $productID; ?>][price]" value="<?php echo $productPrice; ?>" form="placeorder">
                    <?php       }
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
                            <a href="#" class="btn btn-outline-danger" role="button" data-bs-toggle="button"
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
                        <form id="placeorder" action="CheckoutFunction.php" method="post"> 
                            <?php
                                // Process for CartID of placed orders
                                if (count($conditions) > 1) {
                                    $concatProductID = implode(" OR ", $conditions);
                                } else {
                                    $concatProductID = $conditions[0]; 
                                } 
                                
                                $sqlCart = "SELECT c.cartID, c.productID
                                            FROM cart c
                                            JOIN product p ON c.productID = p.productID
                                            WHERE c.userID = ? AND $concatProductID";
                                $stmtCart = $conn->prepare($sqlCart);
                                $stmtCart->bind_param("i", $sellerID);
                                $stmtCart->execute();
                                $resultCart = $stmtCart->get_result();

                                $cart_whereConditions = []; 
                                while ($row = $resultCart->fetch_assoc()) {
                                    $cartID = htmlspecialchars($row['cartID']);

                                    $cart_whereConditions[] = "cartID = " . $row['cartID'];
                                }

                                if (count($cart_whereConditions) > 1) {
                                    $concatProductID = implode(" OR ", $cart_whereConditions);
                                } else {
                                    $concatProductID = $cart_whereConditions[0]; // only one element
                                } 

                                $concatCartID = implode(" OR ", $cart_whereConditions);
                                // Array for productID and quantity

                            ?>
                            <input type="hidden" name="cartIDs" value="<?php echo $concatCartID ?>">
                            <input type="hidden" name="grandTotal" id="grandTotal" value="<?php echo $grandTotal ?>">
                            <button class="btn btn-danger" type="submit">Place Order</button>
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