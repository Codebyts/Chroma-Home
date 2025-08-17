<?php
session_start();
include '../db.php';

if (!isset($_SESSION['userID'])) {
    header("Location: ../LandingPage/LoginPage.php");
    exit();
}

if (isset($_GET['id'])) {
    $productID = intval($_GET['id']);
    $sellerID = $_SESSION['userID'];

    // Delete from DB
    $stmt = $conn->prepare("DELETE FROM product WHERE productID = ? AND sellerID = ?");
    $stmt->bind_param("ii", $productID, $sellerID);
    $stmt->execute();

    header("Location: S-HomePage.php");
    exit();
} else {
    header("Location: S-HomePage.php");
    exit();
}
?>