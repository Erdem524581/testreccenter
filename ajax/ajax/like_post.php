<?php
session_start();
require_once '../db_config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'not_logged_in']);
    exit;
}

$post_id = $_POST['post_id'] ?? null;
$action = $_POST['action'] ?? null; // 'like' or 'dislike'

if (!$post_id || !in_array($action, ['like', 'dislike'])) {
    echo json_encode(['success' => false, 'error' => 'invalid_data']);
    exit;
}

$user_id = $_SESSION['user_id'];
$rating = $action === 'like' ? 1 : -1;

try {
    // Mevcut ratingi kontrol et
    $stmt = $pdo->prepare("SELECT rating FROM post_ratings WHERE user_id = ? AND post_id = ?");
    $stmt->execute([$user_id, $post_id]);
    $existing = $stmt->fetch();
    
    if ($existing) {
        if ($existing['rating'] == $rating) {
            // Aynı rating tekrar gönderildi, kaldır
            $stmt = $pdo->prepare("DELETE FROM post_ratings WHERE user_id = ? AND post_id = ?");
            $stmt->execute([$user_id, $post_id]);
            $rating = 0;
        } else {
            // Ratingi güncelle
            $stmt = $pdo->prepare("UPDATE post_ratings SET rating = ? WHERE user_id = ? AND post_id = ?");
            $stmt->execute([$rating, $user_id, $post_id]);
        }
    } else {
        // Yeni rating ekle
        $stmt = $pdo->prepare("INSERT INTO post_ratings (user_id, post_id, rating) VALUES (?, ?, ?)");
        $stmt->execute([$user_id, $post_id, $rating]);
    }
    
    // Yeni like/dislike sayılarını al
    $stmt = $pdo->prepare("SELECT 
        SUM(CASE WHEN rating = 1 THEN 1 ELSE 0 END) as likes,
        SUM(CASE WHEN rating = -1 THEN 1 ELSE 0 END) as dislikes
        FROM post_ratings WHERE post_id = ?");
    $stmt->execute([$post_id]);
    $ratings = $stmt->fetch();
    
    // Kullanıcının mevcut ratingini al
    $user_rating = 0;
    if ($rating !== 0) {
        $user_rating = $rating;
    } else {
        $stmt = $pdo->prepare("SELECT rating FROM post_ratings WHERE user_id = ? AND post_id = ?");
        $stmt->execute([$user_id, $post_id]);
        $user_rating = $stmt->fetchColumn();
    }
    
    echo json_encode([
        'success' => true,
        'likes' => $ratings['likes'] ?? 0,
        'dislikes' => $ratings['dislikes'] ?? 0,
        'user_rating' => $user_rating
    ]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}