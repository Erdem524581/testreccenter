<?php
session_start();
require_once 'db_config.php';

$requested_user_id = $_GET['id'] ?? header("Location: index.php");

// Kullanıcı bilgilerini çek
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$requested_user_id]);
$requested_user = $stmt->fetch();

if(!$requested_user) {
    header("Location: index.php");
    exit;
}

// Videoları çek
$stmt = $pdo->prepare("SELECT * FROM videos WHERE user_id = ? ORDER BY uploaded_at DESC");
$stmt->execute([$requested_user_id]);
$videos = $stmt->fetchAll();

// Abone sayısını çek
$stmt = $pdo->prepare("SELECT COUNT(*) FROM subscriptions WHERE channel_id = ?");
$stmt->execute([$requested_user_id]);
$subscriber_count = $stmt->fetchColumn();

// Giriş yapan kullanıcı bu kanala abone mi?
$is_subscribed = false;
if(isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT id FROM subscriptions WHERE subscriber_id = ? AND channel_id = ?");
    $stmt->execute([$_SESSION['user_id'], $requested_user_id]);
    $is_subscribed = (bool)$stmt->fetch();
}

// Abone ol/abonelikten çık işlemi
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['toggle_subscription'])) {
    if(!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit;
    }
    
    if($is_subscribed) {
        // Abonelikten çık
        $stmt = $pdo->prepare("DELETE FROM subscriptions WHERE subscriber_id = ? AND channel_id = ?");
        $stmt->execute([$_SESSION['user_id'], $requested_user_id]);
    } else {
        // Abone ol
        $stmt = $pdo->prepare("INSERT INTO subscriptions (subscriber_id, channel_id) VALUES (?, ?)");
        $stmt->execute([$_SESSION['user_id'], $requested_user_id]);
    }
    
    header("Location: profile.php?id=".$requested_user_id);
    exit;
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($requested_user['username']) ?> - Profil</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <style>
        :root {
            --md-sys-color-primary: #6750A4;
            --md-sys-color-on-primary: #FFFFFF;
            --md-sys-color-primary-container: #EADDFF;
            --md-sys-color-on-primary-container: #21005D;
            --md-sys-color-secondary: #625B71;
            --md-sys-color-on-secondary: #FFFFFF;
            --md-sys-color-surface: #FFFBFE;
            --md-sys-color-on-surface: #1C1B1F;
            --md-sys-color-surface-variant: #E7E0EC;
            --md-sys-color-outline: #79747E;
            --md-sys-elevation-level-1: 0 1px 3px 1px rgba(0,0,0,0.15), 0 1px 2px rgba(0,0,0,0.3);
            --md-sys-elevation-level-2: 0 2px 6px 2px rgba(0,0,0,0.15), 0 1px 2px rgba(0,0,0,0.3);
            --md-sys-state-hover: rgba(103, 80, 164, 0.08);
            --md-sys-state-pressed: rgba(103, 80, 164, 0.12);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Roboto', sans-serif;
        }

        body {
            background-color: var(--md-sys-color-surface);
            color: var(--md-sys-color-on-surface);
        }

        .profile-container {
            max-width: 1200px;
            margin: 24px auto;
            padding: 0 16px;
        }

        .profile-header {
            display: flex;
            align-items: center;
            background-color: var(--md-sys-color-surface);
            padding: 24px;
            border-radius: 28px;
            box-shadow: var(--md-sys-elevation-level-1);
            margin-bottom: 24px;
            gap: 24px;
        }

        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 60px;
            object-fit: cover;
            border: 2px solid var(--md-sys-color-outline);
            transition: transform 0.3s;
        }

        .profile-avatar:hover {
            transform: scale(1.05);
        }

        .profile-info {
            flex: 1;
        }

        .profile-name {
            font-size: 1.5rem;
            font-weight: 500;
            margin-bottom: 8px;
            color: var(--md-sys-color-on-surface);
        }

        .profile-username {
            font-size: 1rem;
            color: var(--md-sys-color-outline);
            margin-bottom: 16px;
        }

        .profile-stats {
            display: flex;
            gap: 24px;
            margin-bottom: 16px;
        }

        .stat-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            cursor: pointer;
            padding: 8px 12px;
            border-radius: 8px;
            transition: all 0.2s;
        }

        .stat-item:hover {
            background-color: var(--md-sys-state-hover);
        }

        .stat-item:active {
            background-color: var(--md-sys-state-pressed);
        }

        .stat-value {
            font-size: 1.2rem;
            font-weight: 500;
            color: var(--md-sys-color-on-surface);
        }

        .stat-label {
            font-size: 0.875rem;
            color: var(--md-sys-color-outline);
        }

        .profile-bio {
            margin-top: 16px;
            line-height: 1.6;
            color: var(--md-sys-color-on-surface);
        }

        .profile-actions {
            display: flex;
            gap: 12px;
            margin-top: 16px;
        }

        .profile-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            border: none;
            border-radius: 100px;
            font-size: 0.875rem;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.2s;
            box-shadow: var(--md-sys-elevation-level-1);
        }

        .edit-btn {
            background-color: var(--md-sys-color-primary);
            color: var(--md-sys-color-on-primary);
        }

        .subscribe-btn {
            background-color: var(--md-sys-color-primary);
            color: var(--md-sys-color-on-primary);
        }

        .unsubscribe-btn {
            background-color: var(--md-sys-color-surface-variant);
            color: var(--md-sys-color-on-surface);
        }

        .profile-btn:hover {
            box-shadow: var(--md-sys-elevation-level-2);
            opacity: 0.9;
        }

        .profile-btn:active {
            opacity: 0.8;
        }

        .section-title {
            font-size: 1.25rem;
            font-weight: 500;
            margin-bottom: 16px;
            color: var(--md-sys-color-on-surface);
            padding-left: 8px;
        }

        .video-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 16px;
        }

        .video-card {
            background-color: var(--md-sys-color-surface);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: var(--md-sys-elevation-level-1);
            transition: all 0.2s;
        }

        .video-card:hover {
            box-shadow: var(--md-sys-elevation-level-2);
            transform: translateY(-4px);
        }

        .video-thumbnail {
            width: 100%;
            aspect-ratio: 16/9;
            object-fit: cover;
            transition: transform 0.3s;
        }

        .video-card:hover .video-thumbnail {
            transform: scale(1.03);
        }

        .video-info {
            padding: 12px;
        }

        .video-title {
            font-weight: 500;
            margin-bottom: 8px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            color: var(--md-sys-color-on-surface);
        }

        .video-meta {
            font-size: 0.875rem;
            color: var(--md-sys-color-outline);
            display: flex;
            gap: 8px;
        }

        .no-videos {
            padding: 24px;
            text-align: center;
            color: var(--md-sys-color-outline);
            grid-column: 1 / -1;
        }

        /* Responsive Tasarım */
        @media (max-width: 768px) {
            .profile-header {
                flex-direction: column;
                text-align: center;
                padding: 16px;
            }
            
            .profile-avatar {
                width: 80px;
                height: 80px;
            }
            
            .profile-stats {
                justify-content: center;
            }
            
            .profile-actions {
                justify-content: center;
            }
            
            .video-grid {
                grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
            }
        }

        @media (max-width: 480px) {
            .profile-stats {
                gap: 12px;
            }
            
            .profile-actions {
                flex-direction: column;
                gap: 8px;
            }
            
            .profile-btn {
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="profile-container">
        <div class="profile-header">
            <img src="<?= $requested_user['profile_pic'] ?? 'assets/default-avatar.jpg' ?>" 
                 class="profile-avatar"
                 alt="<?= htmlspecialchars($requested_user['username']) ?>"
                 onerror="this.src='assets/default-avatar.jpg'">
            <div class="profile-info">
                <h1 class="profile-name">
                    <?= htmlspecialchars($requested_user['channel_name'] ?? $requested_user['username']) ?>
                </h1>
                <p class="profile-username">@<?= htmlspecialchars($requested_user['username']) ?></p>
                
                <div class="profile-stats">
                    <div class="stat-item" onclick="window.location.href='profile.php?id=<?= $requested_user_id ?>#videos'">
                        <div class="stat-value"><?= count($videos) ?></div>
                        <div class="stat-label">Video</div>
                    </div>
                    <div class="stat-item" onclick="window.location.href='subscribers.php?id=<?= $requested_user_id ?>'">
                        <div class="stat-value"><?= $subscriber_count ?></div>
                        <div class="stat-label">Abone</div>
                    </div>
                </div>
                
                <?php if(!empty($requested_user['channel_description'])): ?>
                    <div class="profile-bio">
                        <?= nl2br(htmlspecialchars($requested_user['channel_description'])) ?>
                    </div>
                <?php endif; ?>
                
                <div class="profile-actions">
                    <?php if(isset($_SESSION['user_id']) && $_SESSION['user_id'] == $requested_user_id): ?>
                        <a href="edit_profile.php" class="profile-btn edit-btn">
                            <span class="material-icons">edit</span>
                            <span>Profili Düzenle</span>
                        </a>
                    <?php elseif(isset($_SESSION['user_id'])): ?>
                        <form method="POST" style="display: inline;">
                            <button type="submit" name="toggle_subscription" class="profile-btn <?= $is_subscribed ? 'unsubscribe-btn' : 'subscribe-btn' ?>">
                                <span class="material-icons"><?= $is_subscribed ? 'notifications_off' : 'notifications' ?></span>
                                <span><?= $is_subscribed ? 'Abonelikten Çık' : 'Abone Ol' ?></span>
                            </button>
                        </form>
                    <?php else: ?>
                        <a href="login.php" class="profile-btn subscribe-btn">
                            <span class="material-icons">notifications</span>
                            <span>Abone Ol</span>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <h2 class="section-title" id="videos">Videolar</h2>
        <div class="video-grid">
            <?php if(count($videos) > 0): ?>
                <?php foreach($videos as $video): ?>
                    <a href="watch.php?id=<?= $video['id'] ?>" class="video-card">
                        <img src="<?= $video['thumbnail_path'] ?: 'uploads/thumbnails/placeholder.jpg' ?>" 
                             class="video-thumbnail"
                             alt="<?= htmlspecialchars($video['title']) ?>"
                             onerror="this.src='uploads/thumbnails/placeholder.jpg'">
                        <div class="video-info">
                            <div class="video-title"><?= htmlspecialchars($video['title']) ?></div>
                            <div class="video-meta">
                                <span><?= $video['views'] ?> görüntüleme</span>
                                <span>•</span>
                                <span><?= date('d.m.Y', strtotime($video['uploaded_at'])) ?></span>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-videos">Henüz video yüklenmedi.</div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>