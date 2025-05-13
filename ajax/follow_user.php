<?php
session_start();
require_once '../db_config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'not_logged_in']);
    exit;
}

$channel_id = $_POST['user_id'] ?? null;
$action = $_POST['action'] ?? null; // 'follow' or 'unfollow'

if (!$channel_id || !in_array($action, ['follow', 'unfollow'])) {
    echo json_encode(['success' => false, 'error' => 'invalid_data']);
    exit;
}

$subscriber_id = $_SESSION['user_id'];

try {
    if ($action === 'follow') {
        // Takip et
        $stmt = $pdo->prepare("INSERT INTO subscriptions (subscriber_id, channel_id) VALUES (?, ?)");
        $stmt->execute([$subscriber_id, $channel_id]);
    } else {
        // Takibi bırak
        $stmt = $pdo->prepare("DELETE FROM subscriptions WHERE subscriber_id = ? AND channel_id = ?");
        $stmt->execute([$subscriber_id, $channel_id]);
    }
    
    // Yeni takipçi sayısını al
    $stmt = $pdo->prepare("SELECT COUNT(*) as follower_count FROM subscriptions WHERE channel_id = ?");
    $stmt->execute([$channel_id]);
    $follower_count = $stmt->fetch()['follower_count'];
    
    echo json_encode([
        'success' => true,
        'follower_count' => $follower_count,
        'action' => $action
    ]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}