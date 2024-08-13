<?php
require 'db.php';
session_start();

$data = json_decode(file_get_contents('php://input'), true);

$chatId = $data['chatId'] ?? null;
$senderId = $data['senderId'] ?? null;
$message = $data['message'] ?? null;

if (!$chatId || !$senderId || !$message) {
    echo json_encode(['success' => false, 'message' => 'Missing data']);
    exit;
}

$stmt = $pdo->prepare("INSERT INTO messages (chat_id, sender_id, message) VALUES (?, ?, ?)");
$success = $stmt->execute([$chatId, $senderId, $message]);

echo json_encode(['success' => $success]);
