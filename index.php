<?php
session_start();
require_once 'db_config.php';

// Videoları veritabanından çek
$stmt = $pdo->query("SELECT v.*, u.username FROM videos v JOIN users u ON v.user_id = u.id ORDER BY uploaded_at DESC LIMIT 12");
$videos = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VideoPaylaş - Ana Sayfa</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <style>
        :root {
            --md-sys-color-primary: #6750A4;
            --md-sys-color-on-primary: #FFFFFF;
            --md-sys-color-primary-container: #EADDFF;
            --md-sys-color-on-primary-container: #21005D;
            --md-sys-color-secondary: #625B71;
            --md-sys-color-on-secondary: #FFFFFF;
            --md-sys-color-secondary-container: #E8DEF8;
            --md-sys-color-on-secondary-container: #1D192B;
            --md-sys-color-surface: #FFFBFE;
            --md-sys-color-surface-variant: #E7E0EC;
            --md-sys-color-on-surface: #1C1B1F;
            --md-sys-color-outline: #79747E;
            --md-sys-color-shadow: #000000;
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

        header {
            background-color: var(--md-sys-color-surface);
            padding: 1rem;
            box-shadow: 0 1px 2px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .logo {
            font-size: 1.5rem;
            font-weight: 500;
            color: var(--md-sys-color-primary);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .logo .material-icons {
            font-size: 1.8rem;
        }

        .video-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
            padding: 1.5rem;
        }

        .video-card {
            background: var(--md-sys-color-surface);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .video-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 6px 12px rgba(0,0,0,0.1);
        }

        .video-thumbnail-container {
            position: relative;
            padding-top: 56.25%; /* 16:9 aspect ratio */
            overflow: hidden;
        }

        .video-thumbnail {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s;
        }

        .video-card:hover .video-thumbnail {
            transform: scale(1.03);
        }

        .video-info {
            padding: 1rem;
        }

        .video-title {
            font-weight: 500;
            margin-bottom: 0.5rem;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            font-size: 1rem;
            line-height: 1.4;
        }

        .video-uploader {
            font-size: 0.875rem;
            color: var(--md-sys-color-secondary);
            display: flex;
            align-items: center;
            gap: 6px;
            margin-bottom: 4px;
        }

        .video-uploader a {
            color: var(--md-sys-color-secondary);
            text-decoration: none;
            transition: color 0.2s;
        }

        .video-uploader a:hover {
            color: var(--md-sys-color-primary);
        }

        .video-stats {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.75rem;
            color: var(--md-sys-color-outline);
        }

        .video-stats span {
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .material-icons {
            font-size: 1rem;
        }

        @media (max-width: 600px) {
            .video-grid {
                grid-template-columns: 1fr;
                padding: 1rem;
            }
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    
    <main>
        <div class="video-grid">
            <?php foreach($videos as $video): ?>
                <div class="video-card">
                    <a href="watch.php?id=<?= $video['id'] ?>">
                        <div class="video-thumbnail-container">
                            <img src="<?= $video['thumbnail_path'] ?: 'uploads/thumbnails/placeholder.jpg' ?>" 
                                 alt="<?= htmlspecialchars($video['title']) ?>" 
                                 class="video-thumbnail">
                        </div>
                    </a>
                    <div class="video-info">
                        <div class="video-title"><?= htmlspecialchars($video['title']) ?></div>
                        <div class="video-uploader">
                            <span class="material-icons">account_circle</span>
                            <a href="profile.php?id=<?= $video['user_id'] ?>">@<?= htmlspecialchars($video['username']) ?></a>
                        </div>
                        <div class="video-stats">
                            <span>
                                <span class="material-icons">visibility</span>
                                <?= number_format($video['views']) ?>
                            </span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </main>
</body>
</html>