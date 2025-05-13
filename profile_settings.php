?php
// Session başlat
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Veritabanı bağlantısı
require_once 'db_config.php';

// Kullanıcı giriş kontrolü
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Kullanıcı bilgilerini çek
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    header("Location: login.php");
    exit();
}

// Hata ve başarı mesajları
$error = '';
$success = '';

// Form gönderildiğinde
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // XSS koruması
    $username = htmlspecialchars(trim($_POST['username'] ?? ''));
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $profile_pic = $_FILES['profile_pic'] ?? null;

    try {
        // 1. Kullanıcı adı güncelleme
        if (!empty($username) && $username !== $user['username']) {
            // Kullanıcı adı kontrolü
            $check_stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
            $check_stmt->execute([$username, $user_id]);
            
            if ($check_stmt->fetch()) {
                $error = "Bu kullanıcı adı zaten alınmış!";
            } else {
                $update_stmt = $pdo->prepare("UPDATE users SET username = ? WHERE id = ?");
                $update_stmt->execute([$username, $user_id]);
                $_SESSION['username'] = $username;
                $success = "Kullanıcı adı güncellendi!";
            }
        }

        // 2. Şifre güncelleme
        if (!empty($current_password)) {
            if (!password_verify($current_password, $user['password'])) {
                $error .= (empty($error) ? "" : "<br>") . "Mevcut şifre hatalı!";
            } elseif (strlen($new_password) < 6) {
                $error .= (empty($error) ? "" : "<br>") . "Yeni şifre en az 6 karakter olmalıdır!";
            } else {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $update_stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                $update_stmt->execute([$hashed_password, $user_id]);
                $success .= (empty($success) ? "" : "<br>") . "Şifre güncellendi!";
            }
        }

        // 3. Profil fotoğrafı güncelleme
        if ($profile_pic && $profile_pic['error'] === UPLOAD_ERR_OK) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            $max_size = 2 * 1024 * 1024; // 2MB
            
            if (!in_array($profile_pic['type'], $allowed_types)) {
                $error .= (empty($error) ? "" : "<br>") . "Sadece JPG, PNG veya GIF formatında resim yükleyebilirsiniz!";
            } elseif ($profile_pic['size'] > $max_size) {
                $error .= (empty($error) ? "" : "<br>") . "Dosya boyutu 2MB'ı geçemez!";
            } else {
                $upload_dir = 'uploads/profile_pics/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                $ext = pathinfo($profile_pic['name'], PATHINFO_EXTENSION);
                $new_filename = uniqid() . '.' . $ext;
                $file_path = $upload_dir . $new_filename;
                
                if (move_uploaded_file($profile_pic['tmp_name'], $file_path)) {
                    // Eski resmi sil
                    if (!empty($user['profile_pic']) && file_exists($user['profile_pic'])) {
                        unlink($user['profile_pic']);
                    }
                    
                    $update_stmt = $pdo->prepare("UPDATE users SET profile_pic = ? WHERE id = ?");
                    $update_stmt->execute([$file_path, $user_id]);
                    $_SESSION['profile_pic'] = $file_path;
                    $success .= (empty($success) ? "" : "<br>") . "Profil fotoğrafı güncellendi!";
                } else {
                    $error .= (empty($error) ? "" : "<br>") . "Profil fotoğrafı yüklenirken hata oluştu!";
                }
            }
        }

        // Başarılıysa kullanıcı bilgilerini yenile
        if (empty($error)) {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();
        }
    } catch (PDOException $e) {
        $error = "Veritabanı hatası: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Ayarları</title>
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
            --success: #2E7D32;
            --on-success: #FFFFFF;
            --success-container: #C8E6C9;
            --on-success-container: #1B5E20;
            --elevation-1: 0 1px 3px 1px rgba(0, 0, 0, 0.15), 0 1px 2px rgba(0, 0, 0, 0.3);
            --elevation-2: 0 2px 6px 2px rgba(0, 0, 0, 0.15), 0 1px 2px rgba(0, 0, 0, 0.3);
            --shape-corner-xs: 4px;
            --shape-corner-s: 8px;
            --shape-corner-m: 12px;
            --shape-corner-l: 16px;
            --shape-corner-xl: 28px;
            --transition-standard: cubic-bezier(0.2, 0, 0, 1);
        }

        body {
            font-family: 'Roboto', sans-serif;
            background-color: var(--surface);
            color: var(--on-surface);
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .settings-container {
            max-width: 840px;
            margin: 24px auto;
            padding: 0 24px;
            flex: 1;
        }

        .settings-card {
            background: var(--surface-container-low);
            border-radius: var(--shape-corner-xl);
            padding: 24px;
            box-shadow: var(--elevation-1);
            transition: box-shadow 0.3s var(--transition-standard);
        }

        .settings-card:hover {
            box-shadow: var(--elevation-2);
        }

        h1 {
            font-size: 22px;
            font-weight: 500;
            margin: 0 0 32px 0;
            color: var(--on-surface);
            text-align: center;
        }

        .profile-pic-section {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-bottom: 32px;
            gap: 16px;
        }

        .profile-pic {
            width: 120px;
            height: 120px;
            border-radius: var(--shape-corner-xl);
            object-fit: cover;
            background-color: var(--secondary-container);
            border: 3px solid var(--primary-container);
        }

        .file-input-wrapper {
            position: relative;
        }

        .file-input-label {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            background: var(--primary);
            color: var(--on-primary);
            border-radius: var(--shape-corner-xl);
            cursor: pointer;
            transition: all 0.3s var(--transition-standard);
            font-weight: 500;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.1px;
        }

        .file-input-label:hover {
            background: var(--primary);
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.3), 0 1px 3px 1px rgba(0, 0, 0, 0.15);
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
            margin-bottom: 24px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            font-size: 14px;
            color: var(--on-surface-variant);
        }

        .input-container {
            position: relative;
            background: var(--surface-container-high);
            border-radius: var(--shape-corner-m);
            padding: 8px 16px;
            transition: all 0.3s var(--transition-standard);
        }

        .input-container:focus-within {
            background: var(--surface-container);
        }

        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 8px 0;
            border: none;
            background: transparent;
            font-size: 16px;
            color: var(--on-surface);
            outline: none;
        }

        input::placeholder {
            color: var(--outline);
        }

        .btn {
            background: var(--primary);
            color: var(--on-primary);
            border: none;
            padding: 16px 24px;
            border-radius: var(--shape-corner-xl);
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s var(--transition-standard);
            width: 100%;
            text-transform: uppercase;
            letter-spacing: 0.1px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn:hover {
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.3), 0 1px 3px 1px rgba(0, 0, 0, 0.15);
        }

        .btn:active {
            opacity: 0.9;
        }

        .alert {
            padding: 16px;
            border-radius: var(--shape-corner-m);
            margin-bottom: 24px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .alert-error {
            background: var(--error-container);
            color: var(--on-error-container);
        }

        .alert-success {
            background: var(--success-container);
            color: var(--on-success-container);
        }

        .password-field {
            position: relative;
        }

        .password-toggle {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: var(--outline);
        }

        @media (max-width: 600px) {
            .settings-container {
                padding: 0 16px;
            }
            
            .settings-card {
                padding: 16px;
            }
            
            .profile-pic {
                width: 100px;
                height: 100px;
            }
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="settings-container">
        <div class="settings-card">
            <h1>Profil Ayarları</h1>
            
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <span class="material-symbols-outlined">error</span>
                    <?= $error ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <span class="material-symbols-outlined">check_circle</span>
                    <?= $success ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" enctype="multipart/form-data">
                <!-- Profil Fotoğrafı -->
                <div class="profile-pic-section">
                    <img 
                        src="<?= htmlspecialchars($user['profile_pic'] ?? 'assets/default-avatar.jpg') ?>" 
                        class="profile-pic" 
                        id="profilePicPreview"
                        onerror="this.src='assets/default-avatar.jpg'"
                    >
                    <div class="file-input-wrapper">
                        <label class="file-input-label">
                            <span class="material-symbols-outlined">photo_camera</span>
                            Fotoğraf Değiştir
                            <input 
                                type="file" 
                                class="file-input" 
                                name="profile_pic" 
                                id="profilePicInput"
                                accept="image/*"
                            >
                        </label>
                    </div>
                </div>
                
                <!-- Kullanıcı Adı -->
                <div class="form-group">
                    <label for="username">Kullanıcı Adı</label>
                    <div class="input-container">
                        <input 
                            type="text" 
                            id="username" 
                            name="username" 
                            value="<?= htmlspecialchars($user['username']) ?>" 
                            required
                            placeholder="Kullanıcı adınızı girin"
                        >
                    </div>
                </div>
                
                <!-- Şifre Değiştirme -->
                <div class="form-group">
                    <label>Şifre Değiştir (Opsiyonel)</label>
                    
                    <div class="password-field">
                        <div class="input-container">
                            <input 
                                type="password" 
                                name="current_password" 
                                placeholder="Mevcut şifreniz"
                                id="currentPassword"
                            >
                            <span class="material-symbols-outlined password-toggle" onclick="togglePassword('currentPassword')">visibility</span>
                        </div>
                    </div>
                    
                    <div class="password-field" style="margin-top: 16px;">
                        <div class="input-container">
                            <input 
                                type="password" 
                                name="new_password" 
                                placeholder="Yeni şifre (en az 6 karakter)"
                                id="newPassword"
                            >
                            <span class="material-symbols-outlined password-toggle" onclick="togglePassword('newPassword')">visibility</span>
                        </div>
                    </div>
                </div>
                
                <button type="submit" class="btn">
                    <span class="material-symbols-outlined">save</span>
                    Değişiklikleri Kaydet
                </button>
            </form>
        </div>
    </div>

    <script>
        // Profil fotoğrafı önizleme
        document.getElementById('profilePicInput').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('profilePicPreview').src = e.target.result;
                }
                reader.readAsDataURL(file);
            }
        });
        
        // Şifre göster/gizle
        function togglePassword(id) {
            const input = document.getElementById(id);
            const icon = input.parentElement.querySelector('.password-toggle');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.textContent = 'visibility_off';
            } else {
                input.type = 'password';
                icon.textContent = 'visibility';
            }
        }
    </script>
</body>
</html>
<?php
// [Önceki PHP kodları...]

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // [Diğer form kontrolleri...]

    // Profil fotoğrafı yükleme işlemi
    if (isset($_FILES['profile_pic']) {
        $profile_pic = $_FILES['profile_pic'];
        
        // Hata kontrolü
        if ($profile_pic['error'] === UPLOAD_ERR_OK) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $max_size = 5 * 1024 * 1024; // 5MB
            
            // Dosya tipi kontrolü
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime_type = $finfo->file($profile_pic['tmp_name']);
            
            if (!in_array($mime_type, $allowed_types)) {
                $error = "Sadece JPG, PNG, GIF veya WebP formatında resim yükleyebilirsiniz!";
            } 
            // Dosya boyutu kontrolü
            elseif ($profile_pic['size'] > $max_size) {
                $error = "Dosya boyutu 5MB'ı geçemez!";
            } 
            else {
                $upload_dir = 'uploads/profile_pics/';
                
                // Klasör yoksa oluştur
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                // Yeni dosya adı oluştur (userid_timestamp.extension)
                $ext = pathinfo($profile_pic['name'], PATHINFO_EXTENSION);
                $new_filename = $user_id . '_' . time() . '.' . $ext;
                $file_path = $upload_dir . $new_filename;
                
                // Dosyayı taşı
                if (move_uploaded_file($profile_pic['tmp_name'], $file_path)) {
                    // Eski resmi sil (varsa)
                    if (!empty($user['profile_pic']) && file_exists($user['profile_pic']) && $user['profile_pic'] !== 'assets/default-avatar.jpg') {
                        unlink($user['profile_pic']);
                    }
                    
                    // Veritabanını güncelle
                    $update_stmt = $pdo->prepare("UPDATE users SET profile_pic = ? WHERE id = ?");
                    $update_stmt->execute([$file_path, $user_id]);
                    $success = "Profil fotoğrafı başarıyla güncellendi!";
                } else {
                    $error = "Dosya yüklenirken bir hata oluştu!";
                }
            }
        } elseif ($profile_pic['error'] !== UPLOAD_ERR_NO_FILE) {
            // Hata mesajlarını daha anlaşılır hale getir
            $upload_errors = [
                UPLOAD_ERR_INI_SIZE => 'Dosya boyutu sunucu limitini aşıyor',
                UPLOAD_ERR_FORM_SIZE => 'Dosya boyutu form limitini aşıyor',
                UPLOAD_ERR_PARTIAL => 'Dosya yalnızca kısmen yüklendi',
                UPLOAD_ERR_NO_TMP_DIR => 'Geçici klasör bulunamadı',
                UPLOAD_ERR_CANT_WRITE => 'Diske yazılamadı',
                UPLOAD_ERR_EXTENSION => 'Bir PHP eklentisi dosya yüklemeyi durdurdu'
            ];
            $error = $upload_errors[$profile_pic['error']] ?? 'Bilinmeyen bir hata oluştu';
        }
    }
    
    // [Diğer güncelleme işlemleri...]
}
?>