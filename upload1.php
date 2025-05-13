<?php
session_start();
require_once 'db_config.php';

// Hata mesajlarını göster
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Yükleme başarı durumu
$upload_success = false;
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['video'])) {
    try {
        // Yükleme ayarları
        $upload_dir = 'uploads/videos/';
        $thumbnail_dir = 'uploads/thumbnails/';
        $allowed_types = ['video/mp4', 'video/webm'];
        $max_size = 500 * 1024 * 1024; // 500MB

        // Klasörleri oluştur
        if (!file_exists($upload_dir)) mkdir($upload_dir, 0755, true);
        if (!file_exists($thumbnail_dir)) mkdir($thumbnail_dir, 0755, true);

        $video = $_FILES['video'];

        // Hata kontrolü
        if ($video['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Dosya yükleme hatası: ' . $video['error']);
        }

        // Dosya tipi kontrolü
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime_type = $finfo->file($video['tmp_name']);

        if (!in_array($mime_type, $allowed_types)) {
            throw new Exception('Sadece MP4 ve WebM formatları destekleniyor');
        }

        // Dosya boyutu kontrolü
        if ($video['size'] > $max_size) {
            throw new Exception('Dosya boyutu 500MB sınırını aşıyor');
        }

        // Benzersiz dosya adı oluştur
        $file_ext = pathinfo($video['name'], PATHINFO_EXTENSION);
        $file_name = uniqid('video_') . '.' . $file_ext;
        $file_path = $upload_dir . $file_name;

        // Dosyayı taşı
        if (!move_uploaded_file($video['tmp_name'], $file_path)) {
            throw new Exception('Dosya taşınamadı');
        }

        // Thumbnail varsayılan resmi
        $thumbnail_path = $thumbnail_dir . 'default_thumbnail.jpg';

        // Veritabanına ekle
        $stmt = $pdo->prepare("INSERT INTO videos (user_id, title, description, file_path, thumbnail_path) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([
            $_SESSION['user_id'],
            $_POST['title'] ?? 'Başlıksız Video',
            $_POST['description'] ?? '',
            $file_path,
            $thumbnail_path
        ]);

        $upload_success = true;
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Video Yükle</title>
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .upload-form {
            background: #f5f5f5;
            padding: 20px;
            border-radius: 8px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }
        input[type="text"], textarea, input[type="file"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        button {
            background: #6750A4;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
        }
        .message {
            padding: 10px;
            margin: 10px 0;
            border-radius: 4px;
        }
        .success {
            background: #d4edda;
            color: #155724;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
        }
    </style>
</head>
<body>
    <h1>Video Yükle</h1>
    
    <?php if ($upload_success): ?>
        <div class="message success">
            Video başarıyla yüklendi! <a href="index.php">Ana sayfaya dön</a>
        </div>
    <?php elseif (!empty($error_message)): ?>
        <div class="message error">
            <strong>Başarısız!</strong> <?= htmlspecialchars($error_message) ?>
        </div>
    <?php endif; ?>

    <form class="upload-form" method="POST" enctype="multipart/form-data">
        <div class="form-group">
            <label for="title">Video Başlığı</label>
            <input type="text" id="title" name="title" required>
        </div>
        
        <div class="form-group">
            <label for="description">Açıklama</label>
            <textarea id="description" name="description" rows="4"></textarea>
        </div>
        
        <div class="form-group">
            <label for="video">Video Dosyası (MP4/WebM, max 500MB)</label>
            <input type="file" id="video" name="video" accept="video/mp4,video/webm" required>
        </div>
        
        <button type="submit">Yükle</button>
    </form>
</body>
</html>