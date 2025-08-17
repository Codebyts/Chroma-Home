<?php
session_start();
include '../../db.php';

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

// Redirect to favorites page
header("Location: Favorites.php");
exit();
?>
