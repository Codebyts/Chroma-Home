<?php
session_start();
include '../../db.php';
require_once __DIR__ . '/../../db.php';

// Check login
if (!isset($_SESSION['userID'])) {
    // User not logged in → redirect or show login message
    echo "<script>alert('Please log in first.'); window.location.href='../login.php';</script>";
    exit;
}

$currentUser = $_SESSION['userID'];
$chatUser = isset($_GET['chatUser']) ? (int) $_GET['chatUser'] : null;
$chatUserName = "";

// If chat user selected → get their name
if ($chatUser) {
    $stmt_receiver_name = $conn->prepare("SELECT name FROM users WHERE userID = ?");
    $stmt_receiver_name->bind_param("i", $chatUser);
    $stmt_receiver_name->execute();
    $stmt_receiver_name->bind_result($chatUserName);
    $stmt_receiver_name->fetch();
    $stmt_receiver_name->close();
}

// Get contacts that the user already messaged with
$contacts = [];
$stmt_contacts = $conn->prepare("
    SELECT DISTINCT u.userID, u.name 
    FROM users u
    JOIN message m 
      ON (u.userID = m.senderID AND m.receiverID = ?) 
      OR (u.userID = m.receiverID AND m.senderID = ?)
    WHERE u.userID != ?
");
$stmt_contacts->bind_param("iii", $currentUser, $currentUser, $currentUser);
$stmt_contacts->execute();
$result_contacts = $stmt_contacts->get_result();
while ($row = $result_contacts->fetch_assoc()) {
    $contacts[] = $row;
}
$stmt_contacts->close();

// Get conversation messages
// $sql_messages = "SELECT * FROM message 
//         WHERE (senderID = ? AND receiverID = ?) 
//            OR (senderID = ? AND receiverID = ?)
//         ORDER BY sent_at ASC";
// $stmt_messages = $conn->prepare($sql_messages);
// $stmt_messages->bind_param("iiii", $currentUser, $chatUser, $chatUser, $currentUser);
// $stmt_messages->execute();
// $messages = $stmt_messages->get_result();
?>



<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chroma Home</title>

<!-- Bootstrap and font awesome -->
    <link rel="stylesheet" href="../../GlobalFile/bootstrap-5.3.7-dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="../../GlobalFile/fontawesome-free-7.0.0-web/css/all.min.css">

    <!-- Global styles -->
    <link rel="stylesheet" href="../../GlobalFile/global.css">
    <link rel="stylesheet" href="../../GlobalFile/nav-side.css">

    <!-- Home Page styles -->
    <link rel="stylesheet" href="../styles/FavP-style.css">

    <style>
        /* Chat Styles */
        .container { display: flex; height: 90vh; }
        .contacts { width: 25%; border-right: 1px solid #ccc; overflow-y: auto; }
        .contacts a { display: block; padding: 10px; text-decoration: none; color: #000; }
        .contacts a:hover { background: #f1f1f1; }
        .chat-container { flex: 1; display: flex; flex-direction: column; }
        .chat-header { padding: 15px; background: #eee; font-weight: bold; }
        .messages { flex: 1; padding: 10px; overflow-y: auto; }
        .chat-input { display: flex; border-top: 1px solid #ccc; }
        .chat-input button { padding: 10px 15px; border: none; background: #007bff; color: white; cursor: pointer; }
        .chat-input button:hover { background: #0056b3; }

        /* Copied from chat.php */
        .msg { 
            margin: 5px 0; 
            padding: 8px; 
            border-radius: 5px; 
            display: inline-block; 
            max-width: 70%; 
            word-break: break-word; 
        }
        .sent { 
            background: #dcf8c6; 
            text-align: right; 
            float: right; 
            clear: both; 
        }
        .received { 
            background: #f1f0f0; 
            text-align: left; 
            float: left; 
            clear: both; 
        }
        .time { 
            font-size: 10px; 
            color: #555; 
            display: block; 
            margin-top: 3px; 
        }
    </style>

</head>

<body>

 <!-- SIDEBAR -->
    <section id="sidebar">
        <ul class="side-menu">
            <li><a href="../B-HomePage.php"><i class="fas fa-solid fa-house" style="color: #ffffff;"></i> Home</a></li>
            <li><a href="../CartPage.php"><i class="fas fa-solid fa-cart-shopping" style="color: #ffffff;"></i> Cart</a></li>
            <li><a href="#" class="active"><i class="fas fa-solid fa-message"
                        style="color: #ffffff;"></i> Message</a></li>
            <li><a href="../FavoritePage/Favorites.php"><i class="fas fa-solid fa-heart" style="color: #ffffff;"></i>
                    Favorite</a></li>
        </ul>
    </section>
    <!-- SIDEBAR -->

    <!-- NAVBAR -->
    <section id="content">
        <nav>
            <button class="toggle-sidebar btn btn-outline-dark">☰</button>
            <form action="#">
                <div class="form-group">
                    <input type="text" placeholder="Search...">
                    <i class="fas fa-solid fa-magnifying-glass"></i>
                </div>
            </form>

            <span class="divider"></span>
            <div class="profile">
                <img src="../<?php echo htmlspecialchars($profileImage); ?>" alt="Profile" class="rounded-circle" style="width:40px; height:40px; object-fit:cover;">

                <ul class="profile-link">
                    <li><a href="../B-EditProfile.php"><i class="fas fa-regular fa-circle-user"></i> Profile</a></li>
                    <li><a href="../../index.php"><i class="fas fa-solid fa-arrow-right-from-bracket"></i> Logout</a></li>
                </ul>
            </div>
        </nav>
        <!-- NAVBAR -->

    <div class="container">
        <!-- Seller List (contacts) -->
        <div class="contacts">
            <?php foreach ($contacts as $c): ?>
                <a href="?chatUser=<?php echo $c['userID']; ?>">
                    <?php echo htmlspecialchars($c['name']); ?>
                </a>
            <?php endforeach; ?>
        </div>

        <!-- Chat Section -->
        <div class="chat-container">
            <div class="chat-header">
                <?php echo $chatUser ? htmlspecialchars($chatUserName) : "Start chatting"; ?>
            </div>
            <div class="messages" id="messages">
                <?php if (!$chatUser){ ?>
                    <p style="color:#777; text-align:center; margin-top:20px;"></p>
                <?php } else { ?>
                    <div class="chat-box" id="chatBox">
                        <?php // Fetch messages between current user and chat user
                        $stmt_message = $conn->prepare("SELECT * FROM message WHERE (senderID = ? AND receiverID = ?) OR (senderID = ? AND receiverID = ?) ORDER BY sent_at ASC");
                        $stmt_message->bind_param("iiii", $currentUser, $chatUser, $chatUser, $currentUser);
                        $stmt_message->execute();
                        $messages = $stmt_message->get_result(); ?>

                        <?php while ($row = $messages->fetch_assoc()): ?>
                            <div class="msg <?php echo $row['senderID'] == $currentUser ? 'sent' : 'received'; ?>">
                                <?php echo htmlspecialchars($row['message']); ?>
                                <span class="time"><?php echo htmlspecialchars($row['sent_at']); ?></span>
                            </div>
                        <?php endwhile; $stmt_message->close(); ?>
                    </div>
                <?php } ?>
            </div>

            <!-- TYPE BOX -->
            <?php if ($chatUser): ?>
            <form action="send_message.php" method="post" class="send-box chat-input" autocomplete="off" id="chatForm">
                <textarea id="messageInput" name="message" placeholder="Type your message..." required style="flex:1;resize:none;border-radius:20px;padding:10px;border:1px solid #ddd;"></textarea>
                <input type="hidden" name="receiverID" value="<?php echo htmlspecialchars($chatUser); ?>">
                <button type="submit" class="btn btn-primary ms-2">Send</button>
            </form>
            <?php endif; ?>
        </div>
    </div>

<script>
const chatUser = <?php echo $chatUser ?: 'null'; ?>;
const currentUser = <?php echo $currentUser; ?>;
const messagesDiv = document.getElementById("messages");
const form = document.getElementById("chatForm");
const input = document.getElementById("messageInput");

let lastMessageCount = 0;

// Fetch messages every 2s
function loadMessages() {
    if (!chatUser) return;
    fetch("fetch_message.php?otherID=" + chatUser)
        .then(res => res.text())
        .then(data => {
            messagesDiv.innerHTML = data;

            // Auto scroll only if new messages appear
            const newCount = messagesDiv.querySelectorAll(".msg").length;
            if (newCount > lastMessageCount) {
                messagesDiv.scrollTop = messagesDiv.scrollHeight;
                lastMessageCount = newCount;
            }
        });
}
setInterval(loadMessages, 2000);
loadMessages();

// Send message via AJAX
if (form) {
    form.addEventListener("submit", function(e) {
        e.preventDefault();
        fetch("send_message.php", {
            method: "POST",
            body: new FormData(form)
        }).then(() => {
            input.value = "";
            loadMessages();
        });
    });
}
</script>
</body>
</html>