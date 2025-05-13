<?php
session_start();
require_once 'db_config.php';

if(!isset($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$video_id = $_GET['id'];

// Videoyu veritabanından çek
$stmt = $pdo->prepare("SELECT v.*, u.username FROM videos v JOIN users u ON v.user_id = u.id WHERE v.id = ?");
$stmt->execute([$video_id]);
$video = $stmt->fetch();

if(!$video) {
    header("Location: index.php");
    exit();
}

// Görüntüleme sayısını artır
$pdo->prepare("UPDATE videos SET views = views + 1 WHERE id = ?")->execute([$video_id]);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($video['title']) ?> - VideoPaylaş</title>
    <style>
        .video-container {
            max-width: 800px;
            margin: 2rem auto;
            background: white;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            padding: 1rem;
        }
        .video-player {
            width: 100%;
            height: 450px;
            background: #000;
        }
        .video-info {
            margin-top: 1rem;
        }
        .video-title {
            font-size: 1.5rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
        .video-meta {
            color: #666;
            margin-bottom: 1rem;
        }
        .video-description {
            background: #f5f5f5;
            padding: 1rem;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="video-container">
        <div class="video-player">
            <video width="100%" height="100%" controls>
                <source src="<?= $video['file_path'] ?>" type="video/mp4">
                Tarayıcınız video oynatmayı desteklemiyor.
            </video>
        </div>
        
        <div class="video-info">
            <h1 class="video-title"><?= htmlspecialchars($video['title']) ?></h1>
            <div class="video-meta">
                <?= $video['views'] ?> görüntüleme • <?= $video['username'] ?> • <?= date('d.m.Y', strtotime($video['uploaded_at'])) ?>
            </div>
            
            <div class="video-description">
                <?= nl2br(htmlspecialchars($video['description'])) ?>
            </div>
        </div>
    </div>
</body>
</html>