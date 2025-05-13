<?php
session_start();
require_once 'db_config.php';

// Sunucu ayarları
ini_set('upload_max_filesize', '1024M');
ini_set('post_max_size', '1024M');
ini_set('max_execution_time', 3600);
ini_set('max_input_time', 3600);

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$error = '';
$success = '';

// Optimizasyon ayarları
$allowed_ext = ['mp4', 'webm', 'mov'];
$max_size = 1024 * 1024 * 1024; // 1GB

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $user_id = $_SESSION['user_id'];
    $video_file = $_FILES['video_file'];
    
    // Dosya kontrolü
    $ext = strtolower(pathinfo($video_file['name'], PATHINFO_EXTENSION));
    
    if ($video_file['size'] > $max_size) {
        $error = "Dosya boyutu 1GB'ı geçemez!";
    } elseif (!in_array($ext, $allowed_ext)) {
        $error = "Sadece MP4, WebM veya MOV formatları kabul edilir!";
    } else {
        $upload_dir = 'uploads/';
        if (!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);
        
        $file_name = uniqid() . '.' . $ext;
        $file_path = $upload_dir . $file_name;
        
        if (move_uploaded_file($video_file['tmp_name'], $file_path)) {
            try {
                $stmt = $pdo->prepare("INSERT INTO videos (user_id, title, description, file_path) VALUES (?, ?, ?, ?)");
                $stmt->execute([$user_id, $title, $description, $file_path]);
                $success = "Video başarıyla yüklendi!";
            } catch (PDOException $e) {
                $error = "Veritabanı hatası: " . $e->getMessage();
                @unlink($file_path);
            }
        } else {
            $error = "Yükleme sırasında hata oluştu!";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Video Yükle | VideoPaylaş</title>
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
            --md-sys-color-error: #B3261E;
            --md-sys-color-on-error: #FFFFFF;
            --md-sys-color-outline: #79747E;
            --md-sys-elevation-level-1: 0 1px 3px 1px rgba(0,0,0,0.15), 0 1px 2px rgba(0,0,0,0.3);
            --md-sys-elevation-level-2: 0 2px 6px 2px rgba(0,0,0,0.15), 0 1px 2px rgba(0,0,0,0.3);
            --md-sys-state-hover: rgba(103, 80, 164, 0.08);
            --md-sys-state-focus: rgba(103, 80, 164, 0.12);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Roboto', sans-serif;
            background-color: var(--md-sys-color-surface);
            color: var(--md-sys-color-on-surface);
        }

        .container {
            max-width: 800px;
            margin: 40px auto;
            padding: 24px;
            background-color: var(--md-sys-color-surface);
            border-radius: 28px;
            box-shadow: var(--md-sys-elevation-level-1);
        }

        h1 {
            font-size: 1.5rem;
            font-weight: 500;
            margin-bottom: 24px;
            text-align: center;
            color: var(--md-sys-color-on-surface);
        }

        .alert {
            padding: 16px;
            border-radius: 12px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .alert-error {
            background-color: rgba(179, 38, 30, 0.1);
            color: var(--md-sys-color-error);
        }

        .alert-success {
            background-color: rgba(0, 170, 0, 0.1);
            color: #00aa00;
        }

        .alert .material-icons {
            font-size: 1.5rem;
        }

        .upload-form {
            display: flex;
            flex-direction: column;
            gap: 24px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        label {
            font-weight: 500;
            font-size: 0.875rem;
            color: var(--md-sys-color-on-surface);
        }

        input[type="text"],
        textarea {
            width: 100%;
            padding: 16px;
            border: 2px solid var(--md-sys-color-outline);
            border-radius: 12px;
            font-size: 1rem;
            background-color: transparent;
            color: var(--md-sys-color-on-surface);
            transition: all 0.2s;
        }

        input[type="text"]:focus,
        textarea:focus {
            outline: none;
            border-color: var(--md-sys-color-primary);
            caret-color: var(--md-sys-color-primary);
        }

        textarea {
            min-height: 120px;
            resize: vertical;
        }

        .file-input-wrapper {
            position: relative;
            overflow: hidden;
            width: 100%;
        }

        .file-input-wrapper input[type="file"] {
            position: absolute;
            font-size: 100px;
            opacity: 0;
            right: 0;
            top: 0;
            cursor: pointer;
            height: 100%;
            width: 100%;
        }

        .file-input-label {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
            border: 2px dashed var(--md-sys-color-outline);
            border-radius: 12px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
            background-color: var(--md-sys-color-surface-variant);
        }

        .file-input-label:hover {
            border-color: var(--md-sys-color-primary);
            background-color: var(--md-sys-state-hover);
        }

        .file-input-label .material-icons {
            font-size: 48px;
            color: var(--md-sys-color-primary);
            margin-bottom: 16px;
        }

        .file-input-label span {
            font-size: 1rem;
            color: var(--md-sys-color-on-surface);
        }

        .file-input-label .file-name {
            margin-top: 12px;
            font-size: 0.875rem;
            color: var(--md-sys-color-outline);
        }

        .btn {
            width: 100%;
            padding: 16px;
            background-color: var(--md-sys-color-primary);
            color: var(--md-sys-color-on-primary);
            border: none;
            border-radius: 100px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            box-shadow: var(--md-sys-elevation-level-1);
        }

        .btn:hover {
            box-shadow: var(--md-sys-elevation-level-2);
        }

        .btn:disabled {
            background-color: var(--md-sys-color-outline);
            cursor: not-allowed;
        }

        .progress-container {
            display: none;
            margin-top: 16px;
        }

        .progress-bar {
            height: 8px;
            background-color: var(--md-sys-color-surface-variant);
            border-radius: 4px;
            overflow: hidden;
        }

        .progress {
            height: 100%;
            background-color: var(--md-sys-color-primary);
            width: 0%;
            transition: width 0.3s;
        }

        .progress-text {
            margin-top: 8px;
            font-size: 0.875rem;
            color: var(--md-sys-color-outline);
            text-align: center;
        }

        @media (max-width: 768px) {
            .container {
                margin: 20px;
                padding: 20px;
                border-radius: 24px;
            }
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="container">
        <h1>Video Yükle</h1>
        
        <?php if ($error): ?>
            <div class="alert alert-error">
                <span class="material-icons">error</span>
                <span><?= htmlspecialchars($error) ?></span>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success">
                <span class="material-icons">check_circle</span>
                <span><?= htmlspecialchars($success) ?></span>
            </div>
        <?php endif; ?>
        
        <form class="upload-form" method="POST" enctype="multipart/form-data" id="uploadForm">
            <div class="form-group">
                <label for="title">Video Başlığı</label>
                <input type="text" id="title" name="title" placeholder="Videonuz için bir başlık girin" required>
            </div>
            
            <div class="form-group">
                <label for="description">Açıklama</label>
                <textarea id="description" name="description" placeholder="Videonuz hakkında bir açıklama yazın"></textarea>
            </div>
            
            <div class="form-group">
                <label>Video Dosyası (MP4, WebM, MOV - Max 1GB)</label>
                <div class="file-input-wrapper">
                    <label class="file-input-label" id="fileLabel">
                        <span class="material-icons">upload</span>
                        <span>Dosya seçmek için tıklayın veya sürükleyip bırakın</span>
                        <span class="file-name" id="fileName">Hiçbir dosya seçilmedi</span>
                    </label>
                    <input type="file" id="video_file" name="video_file" accept="video/*" required>
                </div>
            </div>
            
            <div class="progress-container" id="progressContainer">
                <div class="progress-bar">
                    <div class="progress" id="progressBar"></div>
                </div>
                <p class="progress-text" id="progressText">Yükleniyor: 0%</p>
            </div>
            
            <button type="submit" class="btn" id="submitBtn">
                <span class="material-icons">upload</span>
                <span id="btnText">Video Yükle</span>
            </button>
        </form>
    </div>

    <script>
        // Dosya seçildiğinde ismi göster
        document.getElementById('video_file').addEventListener('change', function(e) {
            const fileName = e.target.files[0] ? e.target.files[0].name : 'Hiçbir dosya seçilmedi';
            document.getElementById('fileName').textContent = fileName;
        });
        
        // AJAX ile yükleme ve ilerleme çubuğu
        document.getElementById('uploadForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const form = e.target;
            const formData = new FormData(form);
            const progressContainer = document.getElementById('progressContainer');
            const progressBar = document.getElementById('progressBar');
            const progressText = document.getElementById('progressText');
            const submitBtn = document.getElementById('submitBtn');
            const btnText = document.getElementById('btnText');
            
            progressContainer.style.display = 'block';
            submitBtn.disabled = true;
            btnText.textContent = 'Yükleniyor...';
            
            const xhr = new XMLHttpRequest();
            xhr.open('POST', form.action, true);
            
            xhr.upload.onprogress = function(e) {
                if (e.lengthComputable) {
                    const percent = Math.round((e.loaded / e.total) * 100);
                    progressBar.style.width = percent + '%';
                    progressText.textContent = 'Yükleniyor: ' + percent + '%';
                }
            };
            
            xhr.onload = function() {
                if (xhr.status == 200) {
                    window.location.reload();
                } else {
                    alert('Yükleme sırasında bir hata oluştu: ' + xhr.statusText);
                    submitBtn.disabled = false;
                    btnText.textContent = 'Video Yükle';
                }
            };
            
            xhr.send(formData);
        });

        // Sürükle bırak desteği
        const fileLabel = document.getElementById('fileLabel');
        const fileInput = document.getElementById('video_file');

        fileLabel.addEventListener('dragover', (e) => {
            e.preventDefault();
            fileLabel.style.borderColor = 'var(--md-sys-color-primary)';
            fileLabel.style.backgroundColor = 'var(--md-sys-state-hover)';
        });

        fileLabel.addEventListener('dragleave', () => {
            fileLabel.style.borderColor = 'var(--md-sys-color-outline)';
            fileLabel.style.backgroundColor = 'var(--md-sys-color-surface-variant)';
        });

        fileLabel.addEventListener('drop', (e) => {
            e.preventDefault();
            fileLabel.style.borderColor = 'var(--md-sys-color-outline)';
            fileLabel.style.backgroundColor = 'var(--md-sys-color-surface-variant)';
            
            if (e.dataTransfer.files.length) {
                fileInput.files = e.dataTransfer.files;
                document.getElementById('fileName').textContent = e.dataTransfer.files[0].name;
            }
        });
    </script>
</body>
</html>