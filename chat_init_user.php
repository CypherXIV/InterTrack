<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION['user_id'];
$adminId = $_GET['adminId'] ?? null;

if ($adminId) {
    $stmt = $pdo->prepare("SELECT id FROM chats WHERE user_id = ? AND admin_id = ?");
    $stmt->execute([$userId, $adminId]);
    $chat = $stmt->fetch();

    if (!$chat) {
        $stmt = $pdo->prepare("INSERT INTO chats (user_id, admin_id) VALUES (?, ?)");
        $stmt->execute([$userId, $adminId]);
        $chatId = $pdo->lastInsertId();
    } else {
        $chatId = $chat['id'];
    }

    header("Location: handle_chat_user.php?chatId=$chatId");
    exit();
}

die("Admin ID not provided.");
