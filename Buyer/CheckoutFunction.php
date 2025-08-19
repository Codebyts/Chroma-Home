<?php 
session_start();
include '../db.php';

// Redirect to login if not logged in
if (!isset($_SESSION['userID'])) {
    header("Location: ../LandingPage/LoginPage.php");
    exit();
}

$sellerID = $_SESSION['userID'];

if (isset($_POST['grandTotal']) && isset($_POST['products'])) {
    $grandTotal = $_POST['grandTotal'];
    $products = $_POST['products'];
    
    if (isset($_POST['cartIDs']) && $_POST['mode'] == "cart") {
        $concatCartID = $_POST['cartIDs'];
        $sqlRemoveCart = "DELETE FROM cart WHERE userID = ? AND ($concatCartID)";
        $stmtRemoveCart = $conn->prepare($sqlRemoveCart);  
        $stmtRemoveCart->bind_param("i", $sellerID);
        $stmtRemoveCart->execute();
        $stmtRemoveCart->close();
    }

    $sqlInsertOrder = "INSERT INTO orders (buyerID, total_price) VALUES (?, ?)";
    $stmtInsertOrder = $conn->prepare($sqlInsertOrder);
    $stmtInsertOrder->bind_param("id", $sellerID, $grandTotal);
    $stmtInsertOrder->execute();
    $orderID = $stmtInsertOrder->insert_id; 
    $stmtInsertOrder->close();

    foreach ($_POST['products'] as $productID => $details) {
        $productID = intval($productID);
        $quantity  = intval($details['quantity']);
        $productPrice     = floatval($details['price']);

        $total = $quantity * $productPrice;
        
        $sqlInsertOrderItems = "INSERT INTO order_items (orderID, productID, quantity, price) VALUES (?, ?, ?, ?)";
        $stmtInsertOrderItems = $conn->prepare($sqlInsertOrderItems);
        $stmtInsertOrderItems->bind_param("iiid", $orderID, $productID, $quantity, $productPrice);
        $stmtInsertOrderItems->execute();
        $stmtInsertOrderItems->close();
    }

    if ($stmtInsertOrder && $stmtInsertOrderItems) {
        header("Location: B-HomePage.php?message=Order placed successfully!");
        exit();
    }
} else {
    header("Location:CheckoutPage.php?message=An error occurred. Please try again.");
    exit();
}
?>