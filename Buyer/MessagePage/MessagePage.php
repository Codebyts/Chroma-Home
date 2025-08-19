<?php
session_start();
include '../../db.php';
require_once __DIR__ . '/../../db.php';

// Check login
if (!isset($_SESSION['userID'])) {
    echo "<script>alert('Please log in first.'); window.location.href='../login.php';</script>";
    exit;
}

$currentUser = $_SESSION['userID'];
$chatUser = isset($_GET['chatUser']) ? (int) $_GET['chatUser'] : null;


$chatUserName = "";
$chatUserProfile = "../../Buyer/uploads/profiles/"; // fallback local profile

// Fetch selected chat user
if ($chatUser) {
    $stmt_receiver = $conn->prepare("SELECT name, profile FROM users WHERE userID = ?");
    $stmt_receiver->bind_param("i", $chatUser);
    $stmt_receiver->execute();
    $stmt_receiver->bind_result($chatUserName, $chatUserProfile);
    $stmt_receiver->fetch();
    $stmt_receiver->close();
    if (!$chatUserProfile) $chatUserProfile = "../../GlobalFile/default-avatar.png";
}

// Fetch contacts
$contacts = [];
$stmt_contacts = $conn->prepare("
    SELECT DISTINCT u.userID, u.name, u.profile
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
    if (!$row['profile']) $row['profile'] = "../Buyer/uploads/profiles/";
    $contacts[] = $row;
}
$stmt_contacts->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Messages | Chroma Home</title>

<!-- Bootstrap & Icons (local files) -->
<link rel="stylesheet" href="../../GlobalFile/bootstrap-5.3.7-dist/css/bootstrap.min.css">
<link rel="stylesheet" href="../../GlobalFile/fontawesome-free-7.0.0-web/css/all.min.css">

<style>
    body {
        background: #f1f5f9;
        font-family: 'Segoe UI', sans-serif;
    }
    .container-chat {
        display: flex;
        height: 95vh;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 4px 20px rgba(0,0,0,0.15);
        background: #fff;
        position: relative;
    }
    /* Contacts */
    .contacts {
        width: 28%;
        background: #fdfdfd;
        border-right: 1px solid #ddd;
        overflow-y: auto;
        transition: transform 0.3s ease-in-out;
        z-index: 1050;
    }
    .contacts h5 {
        padding: 15px;
        margin: 0;
        font-weight: 600;
        background: #f7f9fc;
        border-bottom: 1px solid #ddd;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .contact-item {
        display: flex;
        align-items: center;
        padding: 10px;
        margin: 6px 8px;
        border-radius: 10px;
        text-decoration: none;
        color: #333;
        transition: 0.2s;
    }
    .contact-item:hover {
        background: #e9f1ff;
    }
    .contact-item img {
        width: 42px; height: 42px;
        border-radius: 50%;
        margin-right: 10px;
        object-fit: cover;
    }
    .contact-info strong {
        font-size: 15px;
    }
    .contact-info small {
        font-size: 12px;
        color: #666;
    }
    /* Chat Area */
    .chat-container {
        flex: 1;
        display: flex;
        flex-direction: column;
        background: #fafafa;
        position: relative;
    }
    .chat-header {
        display: flex;
        align-items: center;
        padding: 12px 15px;
        background: #007bff;
        color: #fff;
    }
    .chat-header img {
        width: 38px; height: 38px;
        border-radius: 50%;
        margin-right: 10px;
        object-fit: cover;
    }
    .chat-header .status {
        font-size: 12px;
        opacity: 0.9;
    }
    .messages {
        flex: 1;
        padding: 15px;
        overflow-y: auto;
    }
    .msg {
        margin: 8px 0;
        padding: 10px 14px;
        border-radius: 18px;
        display: inline-block;
        max-width: 75%;
        word-break: break-word;
        font-size: 15px;
    }
    .sent {
        background: #0d6efd;
        color: #fff;
        float: right;
        clear: both;
        border-bottom-right-radius: 5px;
    }
    .received {
        background: #e9ecef;
        float: left;
        clear: both;
        border-bottom-left-radius: 5px;
    }
    .time {
        font-size: 11px;
        opacity: 0.7;
        margin-top: 3px;
        display: block;
    }
    /* Input */
    .chat-input {
        display: flex;
        padding: 12px;
        border-top: 1px solid #ddd;
        background: #fff;
    }
    .chat-input textarea {
        flex: 1;
        resize: none;
        border-radius: 20px;
        padding: 10px 15px;
        border: 1px solid #ddd;
        font-size: 14px;
    }
    .chat-input button {
        border-radius: 50%;
        margin-left: 10px;
        width: 42px; height: 42px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    /* ✅ Responsive */
    @media (max-width: 992px) {
        .contacts {
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 80%;
            max-width: 280px;
            border-right: 1px solid #ddd;
            background: #fff;
            transform: translateX(-100%);
        }
        .contacts.show {
            transform: translateX(0);
        }
        .contacts h5 .close-btn {
            display: block;
        }
        .chat-header .hamburger {
            display: inline-block;
            margin-right: 12px;
            cursor: pointer;
        }
    }
    @media (min-width: 992px) {
        .chat-header .hamburger {
            display: none;
        }
        .contacts h5 .close-btn {
            display: none;
        }
    }
</style>
</head>

<body>
<div class="container p-3">
    <div class="container-chat">
        <!-- Contacts -->
        <div class="contacts" id="contactsPanel">
            <h5>
                <span><i class="fas fa-users me-2"></i> Contacts</span>
                <button class="btn btn-sm btn-light close-btn" onclick="toggleContacts()"><i class="fas fa-times"></i></button>
            </h5>
            <?php foreach ($contacts as $c): ?>
                <a href="?chatUser=<?php echo $c['userID']; ?>" class="contact-item">
                    <div class="contact-info">
                        <strong><?php echo htmlspecialchars($c['name']); ?></strong>
                        <small>Click to chat</small>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>

        <!-- Chat -->
        <div class="chat-container">
            <div class="chat-header">
                <a href="../B-HomePage.php" class="btn btn-light me-2 d-flex align-items-center" style="border-radius: 50%; width: 30px; height: 30px; justify-content: center;">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <i class="fas fa-bars hamburger" onclick="toggleContacts()"></i>
                <?php if ($chatUser): ?>
                    <div>
                        <div><?php echo htmlspecialchars($chatUserName); ?></div>
                        <div class="status"><i class="fas fa-circle text-success me-1"></i> Active</div>
                    </div>
                <?php else: ?>
                    <span>Select a conversation</span>
                <?php endif; ?>
            </div>

            <div class="messages" id="messages">
                <?php if ($chatUser): ?>
                    <div id="chatBox">
                        <?php
                        $stmt_message = $conn->prepare("SELECT * FROM message WHERE (senderID = ? AND receiverID = ?) OR (senderID = ? AND receiverID = ?) ORDER BY sent_at ASC");
                        $stmt_message->bind_param("iiii", $currentUser, $chatUser, $chatUser, $currentUser);
                        $stmt_message->execute();
                        $messages = $stmt_message->get_result();
                        while ($row = $messages->fetch_assoc()):
                        ?>
                            <div class="msg <?php echo $row['senderID'] == $currentUser ? 'sent' : 'received'; ?>">
                                <?php echo htmlspecialchars($row['message']); ?>
                                <span class="time"><?php echo date("g:i A", strtotime($row['sent_at'])); ?></span>
                            </div>
                        <?php endwhile; $stmt_message->close(); ?>
                    </div>
                <?php else: ?>
                    <p class="text-muted text-center mt-5">Start a conversation by selecting a contact</p>
                <?php endif; ?>
            </div>

            <!-- Input -->
            <?php if ($chatUser): ?>
            <form action="send_message.php" method="post" class="chat-input" autocomplete="off" id="chatForm">
                <textarea id="messageInput" name="message" placeholder="Type your message..." required></textarea>
                <input type="hidden" name="receiverID" value="<?php echo htmlspecialchars($chatUser); ?>">
                <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane"></i></button>
            </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Local Bootstrap JS -->
<script src="../../GlobalFile/bootstrap-5.3.7-dist/js/bootstrap.bundle.min.js"></script>

<script>
function toggleContacts() {
    document.getElementById("contactsPanel").classList.toggle("show");
}

const chatUser = <?php echo $chatUser ?: 'null'; ?>;
const messagesDiv = document.getElementById("messages");
const form = document.getElementById("chatForm");
const input = document.getElementById("messageInput");
let lastMessageCount = 0;

function loadMessages() {
    if (!chatUser) return;
    fetch("fetch_message.php?otherID=" + chatUser)
        .then(res => res.text())
        .then(data => {
            messagesDiv.innerHTML = data;
            const newCount = messagesDiv.querySelectorAll(".msg").length;
            if (newCount > lastMessageCount) {
                messagesDiv.scrollTop = messagesDiv.scrollHeight;
                lastMessageCount = newCount;
            }
        });
}
setInterval(loadMessages, 2000);
loadMessages();

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
