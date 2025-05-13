<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VideoPaylaş</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <style>
        :root {
            --md-sys-color-primary: #6750A4;
            --md-sys-color-on-primary: #FFFFFF;
            --md-sys-color-primary-container: #EADDFF;
            --md-sys-color-secondary: #625B71;
            --md-sys-color-on-secondary: #FFFFFF;
            --md-sys-color-surface: #FFFBFE;
            --md-sys-color-on-surface: #1C1B1F;
            --md-sys-color-surface-variant: #E7E0EC;
            --md-sys-color-outline: #79747E;
            --md-sys-color-shadow: #000000;
            --md-sys-elevation-level-1: 0 1px 3px 1px rgba(0,0,0,0.15), 0 1px 2px rgba(0,0,0,0.3);
            --md-sys-elevation-level-2: 0 2px 6px 2px rgba(0,0,0,0.15), 0 1px 2px rgba(0,0,0,0.3);
            --md-sys-state-hover: rgba(103, 80, 164, 0.08);
            --md-sys-state-focus: rgba(103, 80, 164, 0.12);
            --md-sys-state-drag: rgba(103, 80, 164, 0.16);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Roboto', sans-serif;
            color: var(--md-sys-color-on-surface);
        }

        header {
            background-color: var(--md-sys-color-surface);
            box-shadow: var(--md-sys-elevation-level-1);
            position: sticky;
            top: 0;
            z-index: 1000;
            padding: 0 16px;
        }

        .header-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1400px;
            margin: 0 auto;
            height: 64px;
        }

        .logo-container {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .logo-img {
            height: 40px;
            width: auto;
            transition: transform 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .logo-img:hover {
            transform: scale(1.05);
        }

        .logo-text {
            font-size: 1.25rem;
            font-weight: 500;
            color: var(--md-sys-color-primary);
        }

        nav {
            display: flex;
            align-items: center;
            gap: 4px;
        }

        nav a {
            color: var(--md-sys-color-on-surface);
            text-decoration: none;
            font-weight: 500;
            font-size: 0.875rem;
            padding: 12px 16px;
            border-radius: 20px;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        nav a::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: var(--md-sys-color-primary);
            opacity: 0;
            transition: opacity 0.2s;
            border-radius: inherit;
        }

        nav a:hover::before {
            opacity: 0.08;
        }

        nav a:focus::before {
            opacity: 0.12;
        }

        nav a.active {
            color: var(--md-sys-color-primary);
        }

        nav a.active::after {
            content: '';
            position: absolute;
            bottom: 4px;
            left: 16px;
            right: 16px;
            height: 2px;
            background-color: var(--md-sys-color-primary);
            border-radius: 1px;
        }

        nav a.cta-button {
            background-color: var(--md-sys-color-primary);
            color: var(--md-sys-color-on-primary) !important;
            padding: 10px 24px;
            box-shadow: var(--md-sys-elevation-level-1);
        }

        nav a.cta-button:hover {
            box-shadow: var(--md-sys-elevation-level-2);
            background-color: var(--md-sys-color-primary);
        }

        nav a.cta-button::before {
            background-color: var(--md-sys-color-on-primary);
        }

        .material-icons {
            font-size: 1.125rem;
        }

        .mobile-menu-btn {
            display: none;
            background: none;
            border: none;
            color: var(--md-sys-color-on-surface);
            cursor: pointer;
            padding: 12px;
            border-radius: 50%;
            transition: background-color 0.2s;
        }

        .mobile-menu-btn:hover {
            background-color: var(--md-sys-state-hover);
        }

        @media (max-width: 768px) {
            .header-container {
                height: 56px;
            }

            .logo-img {
                height: 32px;
            }

            .mobile-menu-btn {
                display: block;
            }

            nav {
                display: none;
                position: absolute;
                top: 56px;
                left: 0;
                right: 0;
                background-color: var(--md-sys-color-surface);
                flex-direction: column;
                padding: 8px 0;
                box-shadow: var(--md-sys-elevation-level-2);
                z-index: 999;
            }

            nav.active {
                display: flex;
            }

            nav a {
                border-radius: 0;
                padding: 16px;
                justify-content: flex-start;
            }

            nav a::before {
                border-radius: 0;
            }

            nav a.active::after {
                left: 0;
                right: 0;
                bottom: 0;
                height: 3px;
            }

            nav a.cta-button {
                margin: 8px 16px;
                border-radius: 20px;
                justify-content: center;
            }

            nav a.cta-button::before {
                border-radius: 20px;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="header-container">
            <div class="logo-container">
                <a href="index.php" class="logo-link">
                    <img src="assets/logo/logo32.png" alt="VideoPaylaş" class="logo-img">
                </a>
            </div>
            
            <button class="mobile-menu-btn" id="mobileMenuBtn">
                <span class="material-icons">menu</span>
            </button>
            
            <nav id="mainNav">
                <?php if(isset($_SESSION['user_id'])): ?>
                    <a href="upload.php">
                        <span class="material-icons">upload</span>
                        <span>Yükle</span>
                    </a>
                    <a href="posts.php">
                        <span class="material-icons">newspaper</span>
                        <span>Gönderiler</span>
                    </a>
                    <a href="profile.php?id=<?= $_SESSION['user_id'] ?>">
                        <span class="material-icons">account_circle</span>
                        <span>Profil</span>
                    </a>
                    <a href="profile_settings.php">
                        <span class="material-icons">settings</span>
                        <span>Ayarlar</span>
                    </a>
                    <a href="logout.php" class="cta-button">
                        <span class="material-icons">logout</span>
                        <span>Çıkış</span>
                    </a>
                <?php else: ?>
                    <a href="index.php">
                        <span class="material-icons">home</span>
                        <span>Ana Sayfa</span>
                    </a>
                    <a href="not.html">
                        <span class="material-icons">info</span>
                        <span>Not</span>
                    </a>
                    <a href="login.php">
                        <span class="material-icons">login</span>
                        <span>Giriş</span>
                    </a>
                    <a href="register.php" class="cta-button">
                        <span class="material-icons">person_add</span>
                        <span>Kaydol</span>
                    </a>
                    <a href="">
                        <span class="material-icons">emergency_home</span>
                        <span>Sitemiz betaya geçti!</span>
                    </a>
                <?php endif; ?>
            </nav>
        </div>
    </header>

    <script>
        // Mobil menü toggle
        document.getElementById('mobileMenuBtn').addEventListener('click', function() {
            const nav = document.getElementById('mainNav');
            nav.classList.toggle('active');
            this.querySelector('.material-icons').textContent = 
                nav.classList.contains('active') ? 'close' : 'menu';
        });
        
        // Aktif sayfayı vurgula
        const currentPage = location.pathname.split('/').pop().split('?')[0];
        document.querySelectorAll('nav a').forEach(link => {
            const linkPage = link.getAttribute('href').split('?')[0];
            if (linkPage === currentPage) {
                link.classList.add('active');
            }
        });
    </script>