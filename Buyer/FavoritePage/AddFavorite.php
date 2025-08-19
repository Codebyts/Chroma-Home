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

$sqlCheckFavorite = "SELECT * FROM favorites WHERE userID = ? AND productID = ?";
$stmtCheckFavorite = $conn->prepare($sqlCheckFavorite);
$stmtCheckFavorite->bind_param("ii", $userID, $productID);
$stmtCheckFavorite->execute();
$resultCheck = $stmtCheckFavorite->get_result();
if ($resultCheck->num_rows > 0) {
    header("Location: ../ProductDetails.php?productID=$productID&message=Product already in favorites.");
    exit();
} else {
    $sql = "INSERT IGNORE INTO favorites (userID, productID) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $userID, $productID);
    $stmt->execute();
    header("Location: Favorites.php?message=Product added to favorites.");
}
// Insert only if not already favorited



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
?>
