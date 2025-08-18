<?php
session_start();
include __DIR__ . '/../../db.php';

if (!isset($_SESSION['userID'])) {
    header("Location: login.php");
    exit();
}

if (!isset($_POST['receiverID'], $_POST['message'])) {
    header("Location: MessagePage.php");
    exit();
}

$senderID = $_SESSION['userID'];
$receiverID = intval($_POST['receiverID']);
$message = trim($_POST['message']);

if ($receiverID > 0 && !empty($message)) {
    $stmt = $conn->prepare("INSERT INTO message (senderID, receiverID, message) VALUES (?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("iis", $senderID, $receiverID, $message);
        $stmt->execute();
        $stmt->close();
    }
}

// Only redirect if NOT AJAX
if (
    !isset($_SERVER['HTTP_X_REQUESTED_WITH']) ||
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest'
) {
    header("Location: MessagePage.php?chatUser=" . $receiverID);
    exit();
}

// For AJAX, just return 200 OK and nothing else
http_response_code(200);
exit();
