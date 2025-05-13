<?php
session_start();
require_once '../db_config.php';

if (!isset($_SESSION['user_id']) || !isset($_POST['room_name'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
    exit;
}

$room_name = trim($_POST['room_name']);
$user_id = $_SESSION['user_id'];

if (empty($room_name)) {
    echo json_encode(['success' => false, 'error' => 'Room name cannot be empty']);
    exit;
}

try {
    // Odayı oluştur
    $stmt = $pdo->prepare("INSERT INTO chat_rooms (name) VALUES (?)");
    $stmt->execute([$room_name]);
    $room_id = $pdo->lastInsertId();

    // Kullanıcıyı odaya ekle
    $pdo->prepare("INSERT INTO chat_participants (room_id, user_id) VALUES (?, ?)")
        ->execute([$room_id, $user_id]);

    echo json_encode(['success' => true, 'room_id' => $room_id]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>