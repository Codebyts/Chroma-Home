<?php
session_start();
include '../../db.php';

// Adding a product to favorites
if (!isset($_SESSION['userID'])) {
    header("Location: ../../LandingPage/LoginPage.php");
    exit();
}

$userID = $_SESSION['userID'];
$productID = (int)$_GET['productID'];

// Insert only if not already favorited
$sql = "INSERT IGNORE INTO favorites (userID, productID) VALUES (?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $userID, $productID);
$stmt->execute();

// Removing a product from favorites
if(isset($_POST['productID']) && $_POST['status'] === 'favorite') {
    $productID = $_POST['productID'];
    // Remove from favorites if status is 'favorite'
    $sqlRemove = "DELETE FROM favorites WHERE userID = ? AND productID = ?";
    $stmtRemove = $conn->prepare($sqlRemove);
    $stmtRemove->bind_param("ii", $userID, $productID);
    
    if ($stmtRemove->execute()) {
        header("Location: ../B-HomePage.php?message=Product removed from favorites.");
        //Edit message variable for popup dialogue
        exit();
    } else {
        header("Location: ../B-HomePage.php?message=Failed to remove product from favorites.");
        //Edit message variable for popup dialogue
        exit();
    }
}

// Redirect to favorites page
header("Location: Favorites.php");
exit();
?>
