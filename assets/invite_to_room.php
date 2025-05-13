<?php
session_start();
require_once '../db_config.php';

if (!isset($_SESSION['user_id']) || !isset($_POST['room_id']) || !isset($_POST['username'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
    exit;
}

$room_id = $_POST['room_id'];
$username = trim($_POST['username']);

// Davet edenin odaya erişimi var mı kontrol et
$stmt = $pdo->prepare("SELECT * FROM chat_participants WHERE room_id = ? AND user_id = ?");
$stmt->execute([$room_id, $_SESSION['user_id']]);

if ($stmt->rowCount() == 0) {
    echo json_encode(['success' => false, 'error' => 'You are not in this room']);
    exit;
}

// Davet edilecek kullanıcıyı bul
$stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
$stmt->execute([$username]);
$user = $stmt->fetch();

if (!$user) {
    echo json_encode(['success' => false, 'error' => 'User not found']);
    exit;
}

// Kullanıcı zaten odada mı kontrol et
$stmt = $pdo->prepare("SELECT * FROM chat_participants WHERE room_id = ? AND user_id = ?");
$stmt->execute([$room_id, $user['id']]);

if ($stmt->rowCount() > 0) {
    echo json_encode(['success' => false, 'error' => 'User is already in this room']);
    exit;
}

// Kullanıcıyı odaya ekle
try {
    $pdo->prepare("INSERT INTO chat_participants (room_id, user_id) VALUES (?, ?)")
        ->execute([$room_id, $user['id']]);
    
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>