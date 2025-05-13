<?php
session_start();
require_once '../db_config.php';

if (!isset($_SESSION['user_id']) || !isset($_POST['room_id']) || !isset($_POST['message'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
    exit;
}

$room_id = $_POST['room_id'];
$user_id = $_SESSION['user_id'];
$message = trim($_POST['message']);

if (empty($message)) {
    echo json_encode(['success' => false, 'error' => 'Message cannot be empty']);
    exit;
}

try {
    // Kullanıcının bu odaya erişimi var mı kontrol et
    $stmt = $pdo->prepare("SELECT * FROM chat_participants WHERE room_id = ? AND user_id = ?");
    $stmt->execute([$room_id, $user_id]);
    
    if ($stmt->rowCount() == 0) {
        echo json_encode(['success' => false, 'error' => 'You are not in this room']);
        exit;
    }

    // Mesajı kaydet
    $stmt = $pdo->prepare("INSERT INTO chat_messages (room_id, user_id, message) VALUES (?, ?, ?)");
    $stmt->execute([$room_id, $user_id, $message]);
    $message_id = $pdo->lastInsertId();

    // Mesaj bilgilerini getir
    $stmt = $pdo->prepare("
        SELECT cm.*, u.username, u.profile_pic 
        FROM chat_messages cm
        JOIN users u ON cm.user_id = u.id
        WHERE cm.id = ?
    ");
    $stmt->execute([$message_id]);
    $message_data = $stmt->fetch();

    echo json_encode(['success' => true, 'message' => $message_data]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>