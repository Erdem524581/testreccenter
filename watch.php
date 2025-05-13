<?php
session_start();
require_once 'db_config.php';
require_once 'functions.php';

$video_id = $_GET['id'] ?? header("Location: index.php");

// Video bilgilerini çek
$stmt = $pdo->prepare("SELECT v.*, u.username, u.profile_pic as channel_pic, u.channel_name 
                      FROM videos v JOIN users u ON v.user_id = u.id 
                      WHERE v.id = ?");
$stmt->execute([$video_id]);
$video = $stmt->fetch();

if(!$video) {
    header("Location: index.php");
    exit;
}

// Görüntülenme sayısını artır
$pdo->prepare("UPDATE videos SET views = views + 1 WHERE id = ?")->execute([$video_id]);

// Beğeni sistemi
$user_rating = 0;
if(isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT rating FROM video_ratings WHERE user_id = ? AND video_id = ?");
    $stmt->execute([$_SESSION['user_id'], $video_id]);
    $rating = $stmt->fetch();
    $user_rating = $rating ? $rating['rating'] : 0;
}

// Beğeni sayıları
$stmt = $pdo->prepare("SELECT 
    SUM(CASE WHEN rating = 1 THEN 1 ELSE 0 END) as likes,
    SUM(CASE WHEN rating = -1 THEN 1 ELSE 0 END) as dislikes
    FROM video_ratings WHERE video_id = ?");
$stmt->execute([$video_id]);
$ratings = $stmt->fetch();
$likes = $ratings['likes'] ?? 0;
$dislikes = $ratings['dislikes'] ?? 0;

// Yorumları çek
$stmt = $pdo->prepare("SELECT c.*, u.username, u.profile_pic 
                      FROM comments c JOIN users u ON c.user_id = u.id 
                      WHERE c.video_id = ? AND c.parent_id IS NULL 
                      ORDER BY c.created_at DESC");
$stmt->execute([$video_id]);
$comments = $stmt->fetchAll();

// Yanıtları çekmek için fonksiyon
function getReplies($pdo, $comment_id) {
    $stmt = $pdo->prepare("SELECT c.*, u.username, u.profile_pic 
                          FROM comments c JOIN users u ON c.user_id = u.id 
                          WHERE c.parent_id = ? ORDER BY c.created_at ASC");
    $stmt->execute([$comment_id]);
    return $stmt->fetchAll();
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($video['title']) ?> - VideoPaylaş</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
    <style>
        :root {
            --primary: #6750A4;
            --on-primary: #FFFFFF;
            --primary-container: #EADDFF;
            --on-primary-container: #21005D;
            --secondary: #625B71;
            --on-secondary: #FFFFFF;
            --secondary-container: #E8DEF8;
            --on-secondary-container: #1D192B;
            --surface: #FFFBFE;
            --surface-dim: #DED8E1;
            --surface-bright: #FFFBFE;
            --surface-container-lowest: #FFFFFF;
            --surface-container-low: #F7F2FA;
            --surface-container: #F3EDF7;
            --surface-container-high: #ECE6F0;
            --surface-container-highest: #E6E0E9;
            --on-surface: #1C1B1F;
            --on-surface-variant: #49454F;
            --outline: #79747E;
            --error: #B3261E;
            --on-error: #FFFFFF;
            --error-container: #F9DEDC;
            --on-error-container: #410E0B;
            --elevation-1: 0 1px 3px 1px rgba(0, 0, 0, 0.15), 0 1px 2px rgba(0, 0, 0, 0.3);
            --elevation-2: 0 2px 6px 2px rgba(0, 0, 0, 0.15), 0 1px 2px rgba(0, 0, 0, 0.3);
            --shape-corner-xs: 4px;
            --shape-corner-s: 8px;
            --shape-corner-m: 12px;
            --shape-corner-l: 16px;
            --shape-corner-xl: 28px;
            --transition-standard: cubic-bezier(0.2, 0, 0, 1);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Roboto', sans-serif;
        }
        
        body {
            background-color: var(--surface);
            color: var(--on-surface);
        }
        
        header {
            background-color: var(--surface-container-high);
            padding: 16px;
            box-shadow: var(--elevation-1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .logo {
            font-size: 1.5rem;
            font-weight: bold;
            color: var(--primary);
        }
        
        nav a {
            margin-left: 16px;
            text-decoration: none;
            color: var(--on-surface);
            font-weight: 500;
            padding: 8px 12px;
            border-radius: var(--shape-corner-m);
            transition: background-color 0.3s var(--transition-standard);
        }
        
        nav a:hover {
            background-color: var(--primary-container);
            color: var(--primary);
        }
        
        /* Video İzleme Sayfası Stilleri */
        .watch-container {
            max-width: 1440px;
            margin: 24px auto;
            padding: 0 16px;
            display: grid;
            grid-template-columns: 1fr;
            gap: 24px;
        }
        
        @media (min-width: 992px) {
            .watch-container {
                grid-template-columns: minmax(0, 2fr) minmax(0, 1fr);
            }
        }
        
        .video-player-container {
            background: var(--surface-container-low);
            border-radius: var(--shape-corner-l);
            box-shadow: var(--elevation-1);
            overflow: hidden;
            transition: box-shadow 0.3s var(--transition-standard);
        }

        .video-player-container:hover {
            box-shadow: var(--elevation-2);
        }
        
        .video-player {
            width: 100%;
            aspect-ratio: 16/9;
            background: #000;
        }
        
        .video-info {
            padding: 16px;
        }
        
        .video-title {
            font-size: 1.5rem;
            font-weight: 500;
            margin-bottom: 8px;
            color: var(--on-surface);
        }
        
        .video-meta {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 16px;
            color: var(--on-surface-variant);
            font-size: 14px;
        }
        
        .video-actions {
            display: flex;
            gap: 12px;
            margin-bottom: 16px;
        }
        
        .action-btn {
            display: flex;
            align-items: center;
            gap: 8px;
            background: var(--surface-container-high);
            border: none;
            border-radius: var(--shape-corner-xl);
            padding: 10px 16px;
            cursor: pointer;
            transition: all 0.3s var(--transition-standard);
            color: var(--on-surface);
            font-weight: 500;
        }
        
        .action-btn:hover {
            background: var(--surface-container);
        }
        
        .action-btn.active {
            background: var(--primary-container);
            color: var(--primary);
        }
        
        .video-description {
            background: var(--surface-container-high);
            padding: 16px;
            border-radius: var(--shape-corner-m);
            margin-bottom: 16px;
            color: var(--on-surface);
            line-height: 1.6;
        }
        
        /* Yorum Sistemi */
        .comments-section {
            background: var(--surface-container-low);
            border-radius: var(--shape-corner-l);
            box-shadow: var(--elevation-1);
            padding: 16px;
            transition: box-shadow 0.3s var(--transition-standard);
        }

        .comments-section:hover {
            box-shadow: var(--elevation-2);
        }
        
        .comment-form {
            margin-bottom: 24px;
        }
        
        .comment-textarea {
            width: 100%;
            padding: 16px;
            border: 1px solid var(--outline);
            border-radius: var(--shape-corner-m);
            resize: vertical;
            min-height: 120px;
            margin-bottom: 12px;
            background: var(--surface-container-high);
            color: var(--on-surface);
            font-size: 16px;
            transition: all 0.3s var(--transition-standard);
        }

        .comment-textarea:focus {
            outline: none;
            border-color: var(--primary);
            background: var(--surface-container);
        }
        
        .comment-submit {
            background: var(--primary);
            color: var(--on-primary);
            border: none;
            border-radius: var(--shape-corner-xl);
            padding: 12px 24px;
            cursor: pointer;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.1px;
            transition: all 0.3s var(--transition-standard);
        }
        
        .comment-submit:hover {
            box-shadow: var(--elevation-1);
        }
        
        .comments-list {
            margin-top: 24px;
        }
        
        .comment {
            display: flex;
            gap: 16px;
            margin-bottom: 16px;
            padding-bottom: 16px;
            border-bottom: 1px solid var(--outline);
        }
        
        .comment:last-child {
            border-bottom: none;
        }
        
        .comment-avatar {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            object-fit: cover;
            background: var(--secondary-container);
        }
        
        .comment-content {
            flex: 1;
        }
        
        .comment-header {
            display: flex;
            align-items: center;
            margin-bottom: 8px;
            gap: 8px;
        }
        
        .comment-author {
            font-weight: 500;
            color: var(--on-surface);
        }
        
        .comment-time {
            color: var(--on-surface-variant);
            font-size: 12px;
        }
        
        .comment-text {
            margin-bottom: 8px;
            color: var(--on-surface);
            line-height: 1.5;
        }
        
        .comment-actions {
            display: flex;
            gap: 16px;
        }
        
        .comment-action {
            background: none;
            border: none;
            color: var(--on-surface-variant);
            cursor: pointer;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 4px;
            padding: 4px 8px;
            border-radius: var(--shape-corner-m);
            transition: background-color 0.3s var(--transition-standard);
        }

        .comment-action:hover {
            background: var(--surface-container-high);
        }
        
        .replies {
            margin-top: 16px;
            padding-left: 16px;
            border-left: 2px solid var(--outline);
        }
        
        .reply-form {
            margin-top: 16px;
            display: none;
        }

        .reply-form textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid var(--outline);
            border-radius: var(--shape-corner-m);
            resize: vertical;
            min-height: 80px;
            margin-bottom: 8px;
            background: var(--surface-container-high);
            color: var(--on-surface);
        }

        .reply-form button {
            padding: 8px 16px;
            margin-right: 8px;
            border: none;
            border-radius: var(--shape-corner-m);
            cursor: pointer;
            font-weight: 500;
        }

        .reply-form button:first-child {
            background: var(--primary);
            color: var(--on-primary);
        }

        .reply-form button:last-child {
            background: var(--surface-container-high);
            color: var(--on-surface);
        }
        
        /* Yan Bilgi */
        .sidebar {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }
        
        .sidebar h3 {
            font-size: 18px;
            font-weight: 500;
            color: var(--on-surface);
            margin-bottom: 8px;
        }
        
        .related-video {
            display: flex;
            gap: 12px;
            background: var(--surface-container-low);
            border-radius: var(--shape-corner-m);
            box-shadow: var(--elevation-1);
            overflow: hidden;
            transition: all 0.3s var(--transition-standard);
            text-decoration: none;
            color: inherit;
        }

        .related-video:hover {
            box-shadow: var(--elevation-2);
            background: var(--surface-container);
        }
        
        .related-thumbnail {
            width: 160px;
            height: 90px;
            object-fit: cover;
            background: var(--surface-container-high);
        }
        
        .related-info {
            padding: 12px;
            flex: 1;
        }
        
        .related-title {
            font-weight: 500;
            margin-bottom: 4px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            color: var(--on-surface);
        }
        
        .related-author {
            font-size: 13px;
            color: var(--on-surface-variant);
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="watch-container">
        <div class="main-content">
            <div class="video-player-container">
                <div class="video-player">
                    <video width="100%" controls>
                        <source src="<?= $video['file_path'] ?>" type="video/mp4">
                        Tarayıcınız video oynatmayı desteklemiyor.
                    </video>
                </div>
                
                <div class="video-info">
                    <h1 class="video-title"><?= htmlspecialchars($video['title']) ?></h1>
                    
                    <div class="video-meta">
                        <span><?= number_format($video['views']) ?> görüntüleme</span>
                        <span>•</span>
                        <span><?= date('d.m.Y', strtotime($video['uploaded_at'])) ?></span>
                    </div>
                    
                    <div class="video-actions">
                        <button class="action-btn like-btn <?= $user_rating == 1 ? 'active' : '' ?>" 
                                onclick="rateVideo(1, <?= $video['id'] ?>)">
                            <span class="material-symbols-outlined">thumb_up</span>
                            <span class="like-count"><?= $likes ?></span>
                        </button>
                        <button class="action-btn dislike-btn <?= $user_rating == -1 ? 'active' : '' ?>" 
                                onclick="rateVideo(-1, <?= $video['id'] ?>)">
                            <span class="material-symbols-outlined">thumb_down</span>
                            <span class="dislike-count"><?= $dislikes ?></span>
                        </button>
                    </div>
                    
                    <div class="video-description">
                        <?= nl2br(htmlspecialchars($video['description'] ?? 'Açıklama bulunamadı')) ?>
                    </div>
                </div>
            </div>
            
            <div class="comments-section">
                <h2>Yorumlar</h2>
                
                <?php if(isset($_SESSION['user_id'])): ?>
                <form class="comment-form" id="commentForm">
                    <textarea class="comment-textarea" name="comment" placeholder="Yorumunuzu yazın..." required></textarea>
                    <button type="submit" class="comment-submit">
                        <span class="material-symbols-outlined">send</span>
                        Yorum Yap
                    </button>
                </form>
                <?php else: ?>
                <p>Yorum yapmak için <a href="login.php">giriş yapın</a>.</p>
                <?php endif; ?>
                
                <div class="comments-list" id="commentsList">
                    <?php foreach($comments as $comment): ?>
                        <div class="comment" data-commentid="<?= $comment['id'] ?>">
                            <img src="<?= $comment['profile_pic'] ?? 'assets/default-avatar.jpg' ?>" 
                                 class="comment-avatar"
                                 alt="<?= htmlspecialchars($comment['username']) ?>">
                            <div class="comment-content">
                                <div class="comment-header">
                                    <span class="comment-author"><?= htmlspecialchars($comment['username']) ?></span>
                                    <span class="comment-time"><?= time_elapsed_string($comment['created_at']) ?></span>
                                </div>
                                <div class="comment-text"><?= nl2br(htmlspecialchars($comment['comment'])) ?></div>
                                
                                <?php if(isset($_SESSION['user_id'])): ?>
                                <div class="comment-actions">
                                    <button class="comment-action" onclick="toggleReplyForm(this)">
                                        <span class="material-symbols-outlined">reply</span>
                                        Yanıtla
                                    </button>
                                </div>
                                
                                <div class="reply-form">
                                    <textarea placeholder="Yanıtınızı yazın..."></textarea>
                                    <button onclick="postReply(<?= $comment['id'] ?>, <?= $video['id'] ?>)">
                                        <span class="material-symbols-outlined">send</span>
                                        Gönder
                                    </button>
                                    <button onclick="cancelReply(this)">
                                        <span class="material-symbols-outlined">close</span>
                                        İptal
                                    </button>
                                </div>
                                <?php endif; ?>
                                
                                <?php $replies = getReplies($pdo, $comment['id']); ?>
                                <?php if(!empty($replies)): ?>
                                    <div class="replies">
                                        <?php foreach($replies as $reply): ?>
                                            <div class="comment">
                                                <img src="<?= $reply['profile_pic'] ?? 'assets/default-avatar.jpg' ?>" 
                                                     class="comment-avatar"
                                                     alt="<?= htmlspecialchars($reply['username']) ?>">
                                                <div class="comment-content">
                                                    <div class="comment-header">
                                                        <span class="comment-author"><?= htmlspecialchars($reply['username']) ?></span>
                                                        <span class="comment-time"><?= time_elapsed_string($reply['created_at']) ?></span>
                                                    </div>
                                                    <div class="comment-text"><?= nl2br(htmlspecialchars($reply['comment'])) ?></div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        
        <div class="sidebar">
            <h3>Önerilen Videolar</h3>
            <?php
            $stmt = $pdo->prepare("SELECT v.id, v.title, v.thumbnail_path, u.username 
                                  FROM videos v JOIN users u ON v.user_id = u.id 
                                  WHERE v.id != ? ORDER BY RAND() LIMIT 5");
            $stmt->execute([$video_id]);
            $related_videos = $stmt->fetchAll();
            
            foreach($related_videos as $related): ?>
                <a href="watch.php?id=<?= $related['id'] ?>" class="related-video">
                    <img src="<?= $related['thumbnail_path'] ?: 'uploads/thumbnails/placeholder.jpg' ?>" 
                         class="related-thumbnail"
                         alt="<?= htmlspecialchars($related['title']) ?>">
                    <div class="related-info">
                        <div class="related-title"><?= htmlspecialchars($related['title']) ?></div>
                        <div class="related-author"><?= htmlspecialchars($related['username']) ?></div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <script>
    // Beğeni sistemi
    function rateVideo(rating, videoId) {
        if(!<?= isset($_SESSION['user_id']) ? 'true' : 'false' ?>) {
            alert('Lütfen giriş yapın!');
            return;
        }
        
        fetch('ajax/rate_video.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `video_id=${videoId}&rating=${rating}`
        })
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                document.querySelector('.like-count').textContent = data.likes;
                document.querySelector('.dislike-count').textContent = data.dislikes;
                
                document.querySelector('.like-btn').classList.toggle('active', data.user_rating === 1);
                document.querySelector('.dislike-btn').classList.toggle('active', data.user_rating === -1);
            }
        });
    }
    
    // Yorum gönderme
    document.getElementById('commentForm')?.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        formData.append('video_id', <?= $video['id'] ?>);
        
        fetch('ajax/post_comment.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                this.reset();
                location.reload();
            }
        });
    });
    
    // Yanıt formunu aç/kapa
    function toggleReplyForm(button) {
        const replyForm = button.closest('.comment-actions').nextElementSibling;
        replyForm.style.display = replyForm.style.display === 'block' ? 'none' : 'block';
    }
    
    // Yanıt gönderme
    function postReply(commentId, videoId) {
        const textarea = document.querySelector(`.comment[data-commentid="${commentId}"] .reply-form textarea`);
        const comment = textarea.value.trim();
        
        if(comment) {
            fetch('ajax/post_comment.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `video_id=${videoId}&comment=${encodeURIComponent(comment)}&parent_id=${commentId}`
            })
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    location.reload();
                }
            });
        }
    }
    
    // Yanıt iptal
    function cancelReply(button) {
        button.closest('.reply-form').style.display = 'none';
    }
    </script>
</body>
</html>