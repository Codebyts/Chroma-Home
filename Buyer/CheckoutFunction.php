<?php 
session_start();
include '../db.php';

// Redirect to login if not logged in
if (!isset($_SESSION['userID'])) {
    header("Location: ../LandingPage/LoginPage.php");
    exit();
}

$sellerID = $_SESSION['userID'];

if (isset($_POST['products']) && isset($_POST['grandTotal'])) {
    $products = $_POST['products'];
    $grandTotal = $_POST['grandTotal'];

    $sqlRemoveCart = "DELETE FROM cart WHERE userID = ?";
    $stmtRemoveCart = $conn->prepare($sqlRemoveCart);  
    $stmtRemoveCart->bind_param("i", $sellerID);
    $stmtRemoveCart->execute();
    $stmtRemoveCart->close();
    
} else {
    header("Location:CheckoutPage.php?message=An error occurred. Please try again.");
    exit();
}
?>