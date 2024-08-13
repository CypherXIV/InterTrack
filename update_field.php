<?php
require 'db.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

$allowedFields = ['username', 'student_id', 'field', 'total_hours', 'setup_completed'];
$field = $data['field'] ?? '';
if (!in_array($field, $allowedFields)) {
    echo json_encode(['success' => false, 'message' => 'Invalid field specified']);
    exit;
}

$id = $data['id'] ?? 0;
$value = $data['value'] ?? '';
$stmt = $pdo->prepare("UPDATE users SET $field = :value WHERE id = :id");
$result = $stmt->execute(['value' => $value, 'id' => $id]);

if ($result) {
    echo json_encode(['success' => true, 'message' => 'Field updated successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update the field']);
}
