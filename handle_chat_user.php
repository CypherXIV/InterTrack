<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$chatId = $_GET['chatId'] ?? die('Chat ID not provided.');

if (isset($_GET['action']) && $_GET['action'] === 'fetchMessages') {
    $stmt = $pdo->prepare("
        SELECT m.message, m.timestamp, 
               CASE WHEN m.sender_type = 'admin' THEN a.username 
                    WHEN m.sender_type = 'user' THEN u.username 
               END AS username 
        FROM messages m
        LEFT JOIN admins a ON m.sender_id = a.id AND m.sender_type = 'admin'
        LEFT JOIN users u ON m.sender_id = u.id AND m.sender_type = 'user'
        WHERE m.chat_id = ?
        ORDER BY m.timestamp ASC
    ");
    $stmt->execute([$chatId]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($messages);
    exit();
}

$stmt = $pdo->prepare("
    SELECT m.message, m.timestamp, 
           CASE WHEN m.sender_type = 'admin' THEN a.username 
                WHEN m.sender_type = 'user' THEN u.username 
           END AS username 
    FROM messages m
    LEFT JOIN admins a ON m.sender_id = a.id AND m.sender_type = 'admin'
    LEFT JOIN users u ON m.sender_id = u.id AND m.sender_type = 'user'
    WHERE m.chat_id = ? 
    ORDER BY m.timestamp ASC
");
$stmt->execute([$chatId]);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message'])) {
    $message = trim($_POST['message']);
    if (!empty($message)) {
        $userId = $_SESSION['user_id'];
        $insertStmt = $pdo->prepare("INSERT INTO messages (chat_id, sender_id, message, sender_type) VALUES (?, ?, ?, 'user')");
        $insertStmt->execute([$chatId, $userId, $message]);
        header("Location: handle_chat_user.php?chatId=$chatId");
        exit();
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Chat Interface</title>
    <link rel="stylesheet" href="css/styles.css">
</head>

<body style="background-image: none; background-color: #ffffff;">
    <div id="chat-container">
        <h2>User Chat Room</h2>
        <div id="chat-box">
            <ul id="chat-messages">
                <?php foreach ($messages as $message) : ?>
                    <li>
                        <strong><?php echo htmlspecialchars($message['username']); ?>:</strong>
                        <span><?php echo htmlspecialchars($message['message']); ?></span>
                        <div style="font-size: 0.8em; color: grey;"><?php echo htmlspecialchars($message['timestamp']); ?></div>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <form action="handle_chat_user.php?chatId=<?php echo $chatId; ?>" method="post">
            <textarea id="chat-input" name="message" placeholder="Type your message here..." required></textarea>
            <button type="submit" id="sendButton">Send</button>
        </form>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const chatBox = document.getElementById('chat-messages');
            const chatId = <?php echo json_encode($chatId); ?>;

            function fetchMessages() {
                fetch('handle_chat_user.php?chatId=' + chatId + '&action=fetchMessages')
                    .then(response => response.json())
                    .then(messages => {
                        chatBox.innerHTML = '';
                        messages.forEach(message => {
                            const messageElement = document.createElement('li');
                            messageElement.innerHTML = `<strong>${message.username}:</strong> ${message.message} <div style="font-size: 0.8em; color: grey;">${message.timestamp}</div>`;
                            chatBox.appendChild(messageElement);
                        });
                        chatBox.scrollTop = chatBox.scrollHeight;
                    })
                    .catch(error => console.error('Error fetching messages:', error));
            }

            setInterval(fetchMessages, 3000);
        });
    </script>
</body>

</html>