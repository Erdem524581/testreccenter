<?php
session_start();
require_once 'db_config.php';
require_once 'functions.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Gönderi paylaşma
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['post_content'])) {
    $content = trim($_POST['post_content']);
    $user_id = $_SESSION['user_id'];
    
    if (!empty($content)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO posts (user_id, content) VALUES (?, ?)");
            $stmt->execute([$user_id, $content]);
            header("Location: posts.php"); // Yenile
            exit();
        } catch (PDOException $e) {
            $error = "Gönderi paylaşılırken hata oluştu: " . $e->getMessage();
        }
    }
}

// Tüm gönderileri çek
$stmt = $pdo->prepare("
    SELECT p.*, u.username, u.profile_pic 
    FROM posts p
    JOIN users u ON p.user_id = u.id
    ORDER BY p.created_at DESC
");
$stmt->execute();
$posts = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gönderiler | VideoPaylaş</title>
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
            --like: #B3261E;
            --on-like: #FFFFFF;
            --like-container: #F9DEDC;
            --dislike: #5C5C5C;
            --on-dislike: #FFFFFF;
            --dislike-container: #E8E8E8;
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
        }

        body {
            font-family: 'Roboto', sans-serif;
            background-color: var(--surface);
            color: var(--on-surface);
        }

        .container {
            max-width: 800px;
            margin: 24px auto;
            padding: 0 16px;
        }

        /* Gönderi Formu */
        .post-form {
            background: var(--surface-container-low);
            border-radius: var(--shape-corner-xl);
            padding: 24px;
            box-shadow: var(--elevation-1);
            margin-bottom: 24px;
            transition: box-shadow 0.3s var(--transition-standard);
        }

        .post-form:hover {
            box-shadow: var(--elevation-2);
        }

        .post-input {
            width: 100%;
            min-height: 120px;
            border: none;
            border-radius: var(--shape-corner-m);
            padding: 16px;
            font-size: 16px;
            resize: vertical;
            margin-bottom: 16px;
            background: var(--surface-container-high);
            color: var(--on-surface);
            transition: all 0.3s var(--transition-standard);
        }

        .post-input:focus {
            outline: none;
            background: var(--surface-container);
        }

        .post-input::placeholder {
            color: var(--outline);
        }

        .post-submit {
            background: var(--primary);
            color: var(--on-primary);
            border: none;
            padding: 12px 24px;
            border-radius: var(--shape-corner-xl);
            cursor: pointer;
            font-weight: 500;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.1px;
            transition: all 0.3s var(--transition-standard);
            float: right;
        }

        .post-submit:hover {
            box-shadow: var(--elevation-1);
        }

        /* Gönderi Kartları */
        .post {
            background: var(--surface-container-low);
            border-radius: var(--shape-corner-xl);
            padding: 24px;
            margin-bottom: 24px;
            box-shadow: var(--elevation-1);
            transition: box-shadow 0.3s var(--transition-standard);
        }

        .post:hover {
            box-shadow: var(--elevation-2);
        }

        .post-header {
            display: flex;
            align-items: center;
            margin-bottom: 16px;
            gap: 16px;
        }

        .post-avatar {
            width: 48px;
            height: 48px;
            border-radius: var(--shape-corner-xl);
            object-fit: cover;
            background: var(--secondary-container);
        }

        .post-user-info {
            flex: 1;
        }

        .post-user {
            font-weight: 500;
            color: var(--on-surface);
        }

        .post-time {
            font-size: 12px;
            color: var(--on-surface-variant);
            margin-top: 4px;
        }

        .post-content {
            margin-bottom: 16px;
            line-height: 1.6;
            color: var(--on-surface);
            white-space: pre-line;
        }

        .post-actions {
            display: flex;
            gap: 16px;
            border-top: 1px solid var(--outline);
            padding-top: 16px;
        }

        .post-action {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            padding: 8px 12px;
            border-radius: var(--shape-corner-m);
            transition: all 0.3s var(--transition-standard);
        }

        .post-action:hover {
            background: var(--surface-container-high);
        }

        .post-action.like {
            color: var(--on-surface-variant);
        }

        .post-action.like.active {
            background: var(--like-container);
            color: var(--like);
        }

        .post-action.dislike {
            color: var(--on-surface-variant);
        }

        .post-action.dislike.active {
            background: var(--dislike-container);
            color: var(--dislike);
        }

        .post-action .material-symbols-outlined {
            font-size: 20px;
        }

        /* Yorum Sistemi */
        .comments {
            margin-top: 16px;
            padding-left: 16px;
            border-left: 2px solid var(--outline);
            display: none;
        }

        .comment-form {
            margin-top: 16px;
            display: flex;
            gap: 12px;
        }

        .comment-input {
            flex: 1;
            border: 1px solid var(--outline);
            border-radius: var(--shape-corner-xl);
            padding: 12px 16px;
            background: var(--surface-container-high);
            color: var(--on-surface);
            transition: all 0.3s var(--transition-standard);
        }

        .comment-input:focus {
            outline: none;
            border-color: var(--primary);
            background: var(--surface-container);
        }

        .comment-submit {
            background: var(--primary);
            color: var(--on-primary);
            border: none;
            border-radius: var(--shape-corner-xl);
            padding: 12px 16px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s var(--transition-standard);
        }

        .comment-submit:hover {
            box-shadow: var(--elevation-1);
        }

        /* Responsive */
        @media (max-width: 600px) {
            .container {
                padding: 0 12px;
            }
            
            .post-form, .post {
                padding: 16px;
                border-radius: var(--shape-corner-l);
            }
            
            .post-avatar {
                width: 40px;
                height: 40px;
            }
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="container">
        <!-- Gönderi Paylaşma Formu -->
        <form class="post-form" method="POST">
            <textarea 
                class="post-input" 
                name="post_content" 
                placeholder="Neler paylaşmak istersiniz?"
                required
            ></textarea>
            <button type="submit" class="post-submit">
                <span class="material-symbols-outlined">send</span>
                Paylaş
            </button>
            <div style="clear: both;"></div>
        </form>
        
        <!-- Gönderi Listesi -->
        <?php foreach ($posts as $post): ?>
            <div class="post" data-post-id="<?= $post['id'] ?>">
                <div class="post-header">
                    <img 
                        src="<?= $post['profile_pic'] ?? 'assets/default-avatar.jpg' ?>" 
                        class="post-avatar"
                        alt="<?= htmlspecialchars($post['username']) ?>"
                        onerror="this.src='assets/default-avatar.jpg'"
                    >
                    <div class="post-user-info">
                        <div class="post-user"><?= htmlspecialchars($post['username']) ?></div>
                        <div class="post-time"><?= time_elapsed_string($post['created_at']) ?></div>
                    </div>
                </div>
                
                <div class="post-content">
                    <?= nl2br(htmlspecialchars($post['content'])) ?>
                </div>
                
                <div class="post-actions">
                    <div class="post-action like" onclick="likePost(<?= $post['id'] ?>)">
                        <span class="material-symbols-outlined">thumb_up</span>
                        <span class="like-count">0</span>
                    </div>
                    <div class="post-action dislike" onclick="dislikePost(<?= $post['id'] ?>)">
                        <span class="material-symbols-outlined">thumb_down</span>
                        <span class="dislike-count">0</span>
                    </div>
                    <div class="post-action" onclick="toggleComments(this)">
                        <span class="material-symbols-outlined">comment</span>
                        <span>Yorum</span>
                    </div>
                </div>
                
                <div class="comments">
                    <!-- Yorumlar buraya gelecek -->
                    <div class="comment-form">
                        <input 
                            type="text" 
                            class="comment-input" 
                            placeholder="Yorum yazın..."
                        >
                        <button type="button" class="comment-submit">
                            <span class="material-symbols-outlined">send</span>
                        </button>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <script>
        // Beğeni sistemi
        async function likePost(postId) {
            const response = await fetch('ajax/like_post.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `post_id=${postId}&action=like`
            });
            const data = await response.json();
            
            if (data.success) {
                const postElement = document.querySelector(`.post[data-post-id="${postId}"]`);
                postElement.querySelector('.like-count').textContent = data.likes;
                postElement.querySelector('.dislike-count').textContent = data.dislikes;
                
                postElement.querySelector('.like').classList.toggle('active', data.user_rating === 1);
                postElement.querySelector('.dislike').classList.toggle('active', data.user_rating === -1);
            }
        }
        
        // Dislike sistemi
        async function dislikePost(postId) {
            const response = await fetch('ajax/like_post.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `post_id=${postId}&action=dislike`
            });
            const data = await response.json();
            
            if (data.success) {
                const postElement = document.querySelector(`.post[data-post-id="${postId}"]`);
                postElement.querySelector('.dislike-count').textContent = data.dislikes;
                postElement.querySelector('.like-count').textContent = data.likes;
                
                postElement.querySelector('.dislike').classList.toggle('active', data.user_rating === -1);
                postElement.querySelector('.like').classList.toggle('active', data.user_rating === 1);
            }
        }
        
        // Yorumları aç/kapa
        function toggleComments(button) {
            const commentsSection = button.closest('.post-actions').nextElementSibling;
            commentsSection.style.display = commentsSection.style.display === 'block' ? 'none' : 'block';
        }
    </script>
</body>
</html>