<?php
session_start();
require_once '../db_config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !isset($_POST['video_id']) || !isset($_POST['rating'])) {
    echo json_encode(['success' => false, 'error' => 'Eksik parametre']);
    exit;
}

$user_id = $_SESSION['user_id'];
$video_id = (int)$_POST['video_id'];
$rating = (int)$_POST['rating'];

// Geçerli rating değerlerini kontrol et
if (!in_array($rating, [-1, 1])) {
    echo json_encode(['success' => false, 'error' => 'Geçersiz rating değeri']);
    exit;
}

try {
    // Mevcut ratingi kontrol et
    $stmt = $pdo->prepare("SELECT id, rating FROM video_ratings WHERE user_id = ? AND video_id = ?");
    $stmt->execute([$user_id, $video_id]);
    $existing = $stmt->fetch();

    if ($existing) {
        if ($existing['rating'] == $rating) {
            // Aynı rating tekrar gönderildi, kaldır
            $pdo->prepare("DELETE FROM video_ratings WHERE id = ?")->execute([$existing['id']]);
            $action = 'removed';
        } else {
            // Ratingi güncelle
            $pdo->prepare("UPDATE video_ratings SET rating = ? WHERE id = ?")
                ->execute([$rating, $existing['id']]);
            $action = 'updated';
        }
    } else {
        // Yeni rating ekle
        $pdo->prepare("INSERT INTO video_ratings (user_id, video_id, rating) VALUES (?, ?, ?)")
            ->execute([$user_id, $video_id, $rating]);
        $action = 'added';
    }

    // Yeni like/dislike sayılarını al
    $stmt = $pdo->prepare("SELECT 
        SUM(CASE WHEN rating = 1 THEN 1 ELSE 0 END) as likes,
        SUM(CASE WHEN rating = -1 THEN 1 ELSE 0 END) as dislikes
        FROM video_ratings WHERE video_id = ?");
    $stmt->execute([$video_id]);
    $counts = $stmt->fetch();

    // Kullanıcının yeni rating durumu
    $user_rating = 0;
    if ($action != 'removed') {
        $user_rating = $rating;
    }

    echo json_encode([
        'success' => true,
        'action' => $action,
        'likes' => $counts['likes'] ?? 0,
        'dislikes' => $counts['dislikes'] ?? 0,
        'user_rating' => $user_rating
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Veritabanı hatası: ' . $e->getMessage()]);
}
?>