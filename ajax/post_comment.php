<?php
session_start();
require_once '../db_config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Oturum açılmamış']);
    exit;
}

if (!isset($_POST['video_id']) || !isset($_POST['comment'])) {
    echo json_encode(['success' => false, 'error' => 'Eksik parametre']);
    exit;
}

$user_id = $_SESSION['user_id'];
$video_id = (int)$_POST['video_id'];
$comment = trim($_POST['comment']);
$parent_id = isset($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;

// Yorum boş mu kontrolü
if (empty($comment)) {
    echo json_encode(['success' => false, 'error' => 'Yorum boş olamaz']);
    exit;
}

// Yorum uzunluğu kontrolü
if (strlen($comment) > 1000) {
    echo json_encode(['success' => false, 'error' => 'Yorum çok uzun (max 1000 karakter)']);
    exit;
}

try {
    // Yorumu veritabanına ekle
    $stmt = $pdo->prepare("INSERT INTO comments (user_id, video_id, parent_id, comment) VALUES (?, ?, ?, ?)");
    $stmt->execute([$user_id, $video_id, $parent_id, $comment]);
    
    // Yeni eklenen yorumu döndür
    $comment_id = $pdo->lastInsertId();
    $stmt = $pdo->prepare("SELECT c.*, u.username, u.profile_pic 
                          FROM comments c JOIN users u ON c.user_id = u.id 
                          WHERE c.id = ?");
    $stmt->execute([$comment_id]);
    $new_comment = $stmt->fetch();

    echo json_encode([
        'success' => true,
        'comment' => [
            'id' => $new_comment['id'],
            'username' => $new_comment['username'],
            'profile_pic' => $new_comment['profile_pic'] ?? 'assets/default-avatar.jpg',
            'comment' => nl2br(htmlspecialchars($new_comment['comment'])),
            'created_at' => time_elapsed_string($new_comment['created_at'])
        ]
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Veritabanı hatası: ' . $e->getMessage()]);
}

// Zaman formatlama fonksiyonu
function time_elapsed_string($datetime, $full = false) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $diff->w = floor($diff->d / 7);
    $diff->d -= $diff->w * 7;

    $string = [
        'y' => 'yıl',
        'm' => 'ay',
        'w' => 'hafta',
        'd' => 'gün',
        'h' => 'saat',
        'i' => 'dakika',
        's' => 'saniye',
    ];
    
    foreach ($string as $k => &$v) {
        if ($diff->$k) {
            $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? '' : '');
        } else {
            unset($string[$k]);
        }
    }

    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . ' önce' : 'az önce';
}
?>