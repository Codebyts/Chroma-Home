<?php
session_start();
require_once __DIR__ . '/../../db.php';

if (!isset($_SESSION['userID'])) {
    http_response_code(401);
    exit;
}

$user = $_SESSION['userID'];
$other = isset($_GET['otherID']) ? (int)$_GET['otherID'] : 0;

if ($other === 0) {
    http_response_code(400);
    exit;
}

$stmt = $conn->prepare(
    "SELECT * FROM message
     WHERE (senderID = ? AND receiverID = ?)
        OR (senderID = ? AND receiverID = ?)
     ORDER BY sent_at ASC"
);

$stmt->bind_param('iiii', $user, $other, $other, $user);
$stmt->execute();
$res = $stmt->get_result();

while ($row = $res->fetch_assoc()) {
    $class = $row['senderID'] == $user ? 'sent' : 'received';
    echo '<div class="msg ' . $class . '">';
    echo htmlspecialchars($row['message']);
    echo '<span class="time">' . htmlspecialchars($row['sent_at']) . '</span>';
    echo '</div>';
}

$stmt->close();
$conn->close();
