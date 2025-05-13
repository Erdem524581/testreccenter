<?php
session_start();
require_once 'db_config.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Validasyonlar
    if (empty($username) || empty($email) || empty($password)) {
        $error = 'Tüm alanlar zorunludur!';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Geçersiz email formatı!';
    } elseif (strlen($password) < 6) {
        $error = 'Şifre en az 6 karakter olmalıdır!';
    } elseif ($password !== $confirm_password) {
        $error = 'Şifreler eşleşmiyor!';
    } else {
        try {
            // Kullanıcı adı ve email kontrolü
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            
            if ($stmt->rowCount() > 0) {
                $error = 'Bu kullanıcı adı veya email zaten kullanımda!';
            } else {
                // Kullanıcıyı kaydet
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password, channel_name, profile_pic) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([
                    $username,
                    $email,
                    $hashed_password,
                    $username,
                    'assets/default-avatar.jpg'
                ]);

                // Oturumu başlat
                $_SESSION['user_id'] = $pdo->lastInsertId();
                $_SESSION['username'] = $username;
                $_SESSION['profile_pic'] = 'assets/default-avatar.jpg';
                
                $success = 'Kayıt başarılı! Yönlendiriliyorsunuz...';
                header("Refresh: 2; url=profile.php?id=".$_SESSION['user_id']);
            }
        } catch (PDOException $e) {
            $error = 'Veritabanı hatası: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kayıt Ol - VideoPaylaş</title>
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
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 16px;
        }

        .register-container {
            width: 100%;
            max-width: 450px;
            background-color: var(--md-sys-color-surface);
            border-radius: 28px;
            box-shadow: var(--md-sys-elevation-level-1);
            padding: 24px;
        }

        .logo {
            margin-bottom: 24px;
            display: flex;
            justify-content: center;
        }

        .logo img {
            height: 48px;
        }

        h1 {
            font-size: 1.5rem;
            font-weight: 500;
            margin-bottom: 24px;
            text-align: center;
            color: var(--md-sys-color-on-surface);
        }

        .error, .success {
            padding: 12px 16px;
            border-radius: 12px;
            margin-bottom: 16px;
            font-size: 0.875rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .error {
            background-color: rgba(179, 38, 30, 0.1);
            color: var(--md-sys-color-error);
        }

        .success {
            background-color: rgba(0, 170, 0, 0.1);
            color: #00aa00;
        }

        .error .material-icons, .success .material-icons {
            font-size: 1rem;
        }

        .form-group {
            margin-bottom: 16px;
        }

        .input-container {
            position: relative;
        }

        .input-container .material-icons {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--md-sys-color-outline);
            font-size: 1.2rem;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-size: 0.875rem;
            font-weight: 500;
            color: var(--md-sys-color-on-surface);
        }

        input {
            width: 100%;
            padding: 16px 16px 16px 48px;
            border: 2px solid var(--md-sys-color-outline);
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.2s;
            background-color: transparent;
            color: var(--md-sys-color-on-surface);
        }

        input:focus {
            outline: none;
            border-color: var(--md-sys-color-primary);
            caret-color: var(--md-sys-color-primary);
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
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .submit-btn:hover {
            box-shadow: 0 2px 6px rgba(0,0,0,0.15);
        }

        .submit-btn:active {
            transform: translateY(1px);
        }

        .login-link {
            margin-top: 24px;
            font-size: 0.875rem;
            text-align: center;
            color: var(--md-sys-color-outline);
        }

        .login-link a {
            color: var(--md-sys-color-primary);
            text-decoration: none;
            font-weight: 500;
        }

        .password-strength {
            margin-top: 8px;
            height: 4px;
            background-color: var(--md-sys-color-surface-variant);
            border-radius: 2px;
            overflow: hidden;
        }

        .password-strength-bar {
            height: 100%;
            width: 0%;
            background-color: var(--md-sys-color-error);
            transition: width 0.3s, background-color 0.3s;
        }

        @media (max-width: 480px) {
            .register-container {
                padding: 20px;
                border-radius: 24px;
            }
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="logo">
            <img src="assets/logo/logo.png" alt="VideoPaylaş">
        </div>
        
        <h1>Hesap Oluştur</h1>
        
        <?php if ($error): ?>
            <div class="error">
                <span class="material-icons">error</span>
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success">
                <span class="material-icons">check_circle</span>
                <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="register.php">
            <div class="form-group">
                <label for="username">Kullanıcı Adı</label>
                <div class="input-container">
                    <span class="material-icons">person</span>
                    <input type="text" id="username" name="username" required 
                           value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '' ?>">
                </div>
            </div>
            
            <div class="form-group">
                <label for="email">Email Adresi</label>
                <div class="input-container">
                    <span class="material-icons">email</span>
                    <input type="email" id="email" name="email" required
                           value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
                </div>
            </div>
            
            <div class="form-group">
                <label for="password">Şifre</label>
                <div class="input-container">
                    <span class="material-icons">lock</span>
                    <input type="password" id="password" name="password" required>
                </div>
                <div class="password-strength">
                    <div class="password-strength-bar" id="passwordStrengthBar"></div>
                </div>
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Şifreyi Onayla</label>
                <div class="input-container">
                    <span class="material-icons">lock_reset</span>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>
            </div>
            
            <button type="submit" class="submit-btn">Kayıt Ol</button>
            
            <div class="login-link">
                Zaten hesabınız var mı? <a href="login.php">Giriş yapın</a>
            </div>
        </form>
    </div>

    <script>
        // Şifre güçlülük göstergesi
        document.getElementById('password').addEventListener('input', function(e) {
            const password = e.target.value;
            const strengthBar = document.getElementById('passwordStrengthBar');
            let strength = 0;
            
            if (password.length > 0) strength += 20;
            if (password.length >= 6) strength += 20;
            if (password.match(/[A-Z]/)) strength += 20;
            if (password.match(/[0-9]/)) strength += 20;
            if (password.match(/[^A-Za-z0-9]/)) strength += 20;
            
            strengthBar.style.width = strength + '%';
            
            if (strength < 40) {
                strengthBar.style.backgroundColor = 'var(--md-sys-color-error)';
            } else if (strength < 80) {
                strengthBar.style.backgroundColor = '#FFA000';
            } else {
                strengthBar.style.backgroundColor = '#4CAF50';
            }
        });
    </script>
</body>
</html>