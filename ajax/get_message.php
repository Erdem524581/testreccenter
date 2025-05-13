<?php
session_start();
require_once '../db_config.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['room_id'])) {
    echo json_encode([]);
    exit;
}

$room_id = $_GET['room_id'];
$last_id = $_GET['last_id'] ?? 0;

// Kullanıcının bu odaya erişimi var mı kontrol et
$stmt = $pdo->prepare("SELECT * FROM chat_participants WHERE room_id = ? AND user_id = ?");
$stmt->execute([$room_id, $_SESSION['user_id']]);

if ($stmt->rowCount() == 0) {
    echo json_encode([]);
    exit;
}

// Yeni mesajları getir
$stmt = $pdo->prepare("
    SELECT cm.*, u.username, u.profile_pic 
    FROM chat_messages cm
    JOIN users u ON cm.user_id = u.id
    WHERE cm.room_id = ? AND cm.id > ?
    ORDER BY cm.sent_at ASC
");
$stmt->execute([$room_id, $last_id]);
$messages = $stmt->fetchAll();

echo json_encode($messages);
?>