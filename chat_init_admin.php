<?php
session_start();
require 'db.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

$adminId = $_SESSION['admin_id'];
$userId = $_GET['userId'] ?? null;

if ($userId) {
    $stmt = $pdo->prepare("SELECT id FROM chats WHERE admin_id = ? AND user_id = ?");
    $stmt->execute([$adminId, $userId]);
    $chat = $stmt->fetch();

    if (!$chat) {
        $stmt = $pdo->prepare("INSERT INTO chats (admin_id, user_id) VALUES (?, ?)");
        $stmt->execute([$adminId, $userId]);
        $chatId = $pdo->lastInsertId();
    } else {
        $chatId = $chat['id'];
    }

    header("Location: handle_chat_admin.php?chatId=$chatId");
    exit();
}

die("User ID not provided.");
