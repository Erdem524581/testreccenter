<?php
session_start();
require_once 'db_config.php';

if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Get current user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

$error = '';
$success = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $channel_name = $_POST['channel_name'] ?? '';
    $channel_description = $_POST['channel_description'] ?? '';
    
    try {
        // Handle profile picture upload
        $profile_pic = $user['profile_pic'];
        if(isset($_FILES['profile_pic']) && $_FILES['profile_pic']['size'] > 0) {
            $file = $_FILES['profile_pic'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png'];
            
            if(in_array($ext, $allowed)) {
                $filename = uniqid() . '.' . $ext;
                $upload_path = 'uploads/profile_pics/' . $filename;
                
                if(move_uploaded_file($file['tmp_name'], $upload_path)) {
                    // Delete old profile pic if it exists
                    if($profile_pic && file_exists($profile_pic)) {
                        unlink($profile_pic);
                    }
                    $profile_pic = $upload_path;
                }
            } else {
                $error = 'Sadece JPG, JPEG veya PNG formatında resim yükleyebilirsiniz.';
            }
        }
        
        // Update user data
        $stmt = $pdo->prepare("UPDATE users SET 
                              channel_name = ?, 
                              channel_description = ?, 
                              profile_pic = ? 
                              WHERE id = ?");
        $stmt->execute([$channel_name, $channel_description, $profile_pic, $user_id]);
        
        $success = 'Profil başarıyla güncellendi!';
        
        // Refresh user data
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        $_SESSION['profile_pic'] = $user['profile_pic'];
        
    } catch(PDOException $e) {
        $error = 'Bir hata oluştu: ' . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profili Düzenle - <?= htmlspecialchars($user['username']) ?></title>
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
            padding: 16px;
        }

        .edit-profile-container {
            max-width: 800px;
            margin: 0 auto;
            background-color: var(--md-sys-color-surface);
            border-radius: 28px;
            box-shadow: var(--md-sys-elevation-level-1);
            padding: 24px;
        }

        h1 {
            font-size: 1.5rem;
            font-weight: 500;
            margin-bottom: 24px;
            color: var(--md-sys-color-on-surface);
        }

        .alert {
            padding: 12px 16px;
            border-radius: 12px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 8px;
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
            font-size: 1.2rem;
        }

        .profile-pic-section {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-bottom: 24px;
        }

        .profile-pic-preview {
            width: 120px;
            height: 120px;
            border-radius: 60px;
            object-fit: cover;
            border: 2px solid var(--md-sys-color-outline);
            margin-bottom: 16px;
        }

        .file-input-wrapper {
            position: relative;
        }

        .file-input-label {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 16px;
            background-color: var(--md-sys-color-primary);
            color: var(--md-sys-color-on-primary);
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
        }

        .file-input-label:hover {
            box-shadow: 0 2px 6px rgba(0,0,0,0.15);
        }

        .file-input {
            position: absolute;
            left: 0;
            top: 0;
            opacity: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
        }

        .form-group {
            margin-bottom: 16px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-size: 0.875rem;
            font-weight: 500;
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

        .submit-btn {
            width: 100%;
            padding: 16px;
            background-color: var(--md-sys-color-primary);
            color: var(--md-sys-color-on-primary);
            border: none;
            border-radius: 100px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            margin-top: 16px;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            box-shadow: var(--md-sys-elevation-level-1);
        }

        .submit-btn:hover {
            box-shadow: var(--md-sys-elevation-level-2);
        }

        @media (max-width: 768px) {
            .edit-profile-container {
                padding: 16px;
                border-radius: 24px;
            }
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="edit-profile-container">
        <h1>Profili Düzenle</h1>
        
        <?php if($error): ?>
            <div class="alert alert-error">
                <span class="material-icons">error</span>
                <span><?= $error ?></span>
            </div>
        <?php endif; ?>
        
        <?php if($success): ?>
            <div class="alert alert-success">
                <span class="material-icons">check_circle</span>
                <span><?= $success ?></span>
            </div>
        <?php endif; ?>
        
        <form method="POST" enctype="multipart/form-data">
            <div class="profile-pic-section">
                <img src="<?= $user['profile_pic'] ?? 'assets/default_profile.png' ?>" 
                     class="profile-pic-preview" 
                     id="profilePicPreview">
                <div class="file-input-wrapper">
                    <label class="file-input-label">
                        <span class="material-icons">photo_camera</span>
                        <span>Fotoğraf Değiştir</span>
                        <input type="file" name="profile_pic" id="profilePicInput" accept="image/*" class="file-input">
                    </label>
                </div>
            </div>
            
            <div class="form-group">
                <label for="channel_name">Kanal Adı</label>
                <input type="text" id="channel_name" name="channel_name" 
                       value="<?= htmlspecialchars($user['channel_name'] ?? '') ?>">
            </div>
            
            <div class="form-group">
                <label for="channel_description">Kanal Açıklaması</label>
                <textarea id="channel_description" name="channel_description"><?= 
                    htmlspecialchars($user['channel_description'] ?? '') 
                ?></textarea>
            </div>
            
            <button type="submit" class="submit-btn">
                <span class="material-icons">save</span>
                <span>Değişiklikleri Kaydet</span>
            </button>
        </form>
    </div>

    <script>
    // Profile picture preview
    document.getElementById('profilePicInput').addEventListener('change', function(e) {
        const file = e.target.files[0];
        if(file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('profilePicPreview').src = e.target.result;
            }
            reader.readAsDataURL(file);
        }
    });
    </script>
</body>
</html>