<?php
session_start();
require_once 'db_config.php';

$error = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    
    if($user) {
        // Ban kontrolü
        $banStmt = $pdo->prepare("SELECT b.*, u.username as banned_by 
                                FROM banned_users b
                                JOIN users u ON b.banned_by = u.id
                                WHERE b.user_id = ?");
        $banStmt->execute([$user['id']]);
        $ban = $banStmt->fetch();
        
        if($ban) {
            $_SESSION['ban_info'] = [
                'reason' => $ban['reason'],
                'banned_at' => $ban['banned_at'],
                'banned_by' => $ban['banned_by']
            ];
            header("Location: banned.php");
            exit();
        }
        
        if(password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            header("Location: index.php");
            exit();
        }
    }
    
    $error = "Kullanıcı adı veya şifre hatalı!";
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giriş Yap - VideoPaylaş</title>
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
            --md-sys-state-drag: rgba(103, 80, 164, 0.16);
            --md-sys-shape-corner-extra-small: 4px;
            --md-sys-shape-corner-small: 8px;
            --md-sys-shape-corner-medium: 12px;
            --md-sys-shape-corner-large: 16px;
            --md-sys-shape-corner-extra-large: 28px;
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

        .login-container {
            width: 100%;
            max-width: 400px;
            background-color: var(--md-sys-color-surface);
            border-radius: var(--md-sys-shape-corner-extra-large);
            box-shadow: var(--md-sys-elevation-level-1);
            padding: 24px;
            text-align: center;
            transition: all 0.3s;
        }

        .login-container:hover {
            box-shadow: var(--md-sys-elevation-level-2);
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
            color: var(--md-sys-color-on-surface);
        }

        .error {
            background-color: rgba(179, 38, 30, 0.1);
            color: var(--md-sys-color-error);
            padding: 12px 16px;
            border-radius: var(--md-sys-shape-corner-medium);
            margin-bottom: 16px;
            font-size: 0.875rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            border-left: 4px solid var(--md-sys-color-error);
        }

        .form-group {
            margin-bottom: 16px;
            text-align: left;
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
            color: var(--md-sys-color-on-surface-variant);
        }

        input {
            width: 100%;
            padding: 16px 16px 16px 48px;
            border: 1px solid var(--md-sys-color-outline);
            border-radius: var(--md-sys-shape-corner-medium);
            font-size: 1rem;
            transition: all 0.2s;
            background-color: transparent;
            color: var(--md-sys-color-on-surface);
        }

        input:focus {
            outline: none;
            border-color: var(--md-sys-color-primary);
            caret-color: var(--md-sys-color-primary);
            box-shadow: 0 0 0 1px var(--md-sys-color-primary);
        }

        .submit-btn {
            width: 100%;
            padding: 16px;
            background-color: var(--md-sys-color-primary);
            color: var(--md-sys-color-on-primary);
            border: none;
            border-radius: var(--md-sys-shape-corner-extra-large);
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            margin-top: 8px;
            transition: all 0.2s;
            box-shadow: var(--md-sys-elevation-level-1);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .submit-btn:hover {
            box-shadow: var(--md-sys-elevation-level-2);
            background-color: var(--md-sys-color-inverse-primary);
        }

        .submit-btn .material-icons {
            font-size: 1.2rem;
            position: static;
            transform: none;
        }

        .register-link {
            margin-top: 24px;
            font-size: 0.875rem;
            color: var(--md-sys-color-outline);
        }

        .register-link a {
            color: var(--md-sys-color-primary);
            text-decoration: none;
            font-weight: 500;
            transition: all 0.2s;
        }

        .register-link a:hover {
            text-decoration: underline;
        }

        @media (max-width: 480px) {
            .login-container {
                padding: 20px;
                border-radius: var(--md-sys-shape-corner-large);
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo">
            <img src="assets/logo/logo.png" alt="VideoPaylaş">
        </div>
        
        <h1>Giriş Yap</h1>
        
        <?php if($error): ?>
            <div class="error">
                <span class="material-icons">error</span>
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label for="username">Kullanıcı Adı</label>
                <div class="input-container">
                    <span class="material-icons">person</span>
                    <input type="text" id="username" name="username" required>
                </div>
            </div>
            
            <div class="form-group">
                <label for="password">Şifre</label>
                <div class="input-container">
                    <span class="material-icons">lock</span>
                    <input type="password" id="password" name="password" required>
                </div>
            </div>
            
            <button type="submit" class="submit-btn">
                <span class="material-icons">login</span>
                Giriş Yap
            </button>
            
            <div class="register-link">
                Hesabınız yok mu? <a href="register.php">Kaydolun</a>
            </div>
        </form>
    </div>
</body>
</html>