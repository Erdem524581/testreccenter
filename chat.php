<?php
session_start();
require_once 'db_config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Genel sohbet odasını oluştur (eğer yoksa)
$stmt = $pdo->prepare("SELECT id FROM chat_rooms WHERE name = 'Genel Sohbet'");
$stmt->execute();
$general_room = $stmt->fetch();

if (!$general_room) {
    $pdo->prepare("INSERT INTO chat_rooms (name) VALUES ('Genel Sohbet')")->execute();
    $general_room_id = $pdo->lastInsertId();
} else {
    $general_room_id = $general_room['id'];
}

// Kullanıcıyı genel sohbete ekle (eğer değilse)
$stmt = $pdo->prepare("SELECT * FROM chat_participants WHERE room_id = ? AND user_id = ?");
$stmt->execute([$general_room_id, $_SESSION['user_id']]);
if ($stmt->rowCount() == 0) {
    $pdo->prepare("INSERT INTO chat_participants (room_id, user_id) VALUES (?, ?)")
        ->execute([$general_room_id, $_SESSION['user_id']]);
}

// Kullanıcının katıldığı odaları getir
$stmt = $pdo->prepare("
    SELECT cr.id, cr.name 
    FROM chat_rooms cr
    JOIN chat_participants cp ON cr.id = cp.room_id
    WHERE cp.user_id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$rooms = $stmt->fetchAll();

// Aktif oda (varsayılan: Genel Sohbet)
$active_room = $general_room_id;
if (isset($_GET['room']) && is_numeric($_GET['room'])) {
    // Kullanıcının bu odaya erişimi var mı kontrol et
    $stmt = $pdo->prepare("SELECT * FROM chat_participants WHERE room_id = ? AND user_id = ?");
    $stmt->execute([$_GET['room'], $_SESSION['user_id']]);
    if ($stmt->rowCount() > 0) {
        $active_room = $_GET['room'];
    }
}

// Oda mesajlarını getir
$stmt = $pdo->prepare("
    SELECT cm.*, u.username, u.profile_pic 
    FROM chat_messages cm
    JOIN users u ON cm.user_id = u.id
    WHERE cm.room_id = ?
    ORDER BY cm.sent_at DESC
    LIMIT 50
");
$stmt->execute([$active_room]);
$messages = array_reverse($stmt->fetchAll());

// Oda bilgilerini getir
$stmt = $pdo->prepare("SELECT * FROM chat_rooms WHERE id = ?");
$stmt->execute([$active_room]);
$room_info = $stmt->fetch();

// Oda katılımcılarını getir
$stmt = $pdo->prepare("
    SELECT u.id, u.username, u.profile_pic 
    FROM users u
    JOIN chat_participants cp ON u.id = cp.user_id
    WHERE cp.room_id = ?
");
$stmt->execute([$active_room]);
$participants = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sohbet - RecCenter</title>
    <style>
        :root {
            --primary: #4361ee;
            --dark: #1a1a1a;
            --light: #f8f9fa;
            --accent: #ff4757;
        }
        
        .chat-container {
            max-width: 1200px;
            margin: 30px auto;
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 20px;
            height: calc(100vh - 100px);
        }
        
        .rooms-sidebar {
            background: white;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            padding: 20px;
            overflow-y: auto;
        }
        
        .rooms-title {
            font-size: 1.2rem;
            margin-bottom: 20px;
            color: var(--dark);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .room-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .room-item {
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 10px;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .room-item:hover {
            background-color: #f0f0f0;
        }
        
        .room-item.active {
            background-color: var(--primary);
            color: white;
        }
        
        .new-room-btn {
            background: var(--accent);
            color: white;
            border: none;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }
        
        .chat-main {
            display: grid;
            grid-template-rows: auto 1fr auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .chat-header {
            padding: 15px 20px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .room-name {
            font-size: 1.2rem;
            font-weight: 600;
        }
        
        .participants-count {
            color: #666;
            font-size: 0.9rem;
        }
        
        .messages-container {
            padding: 20px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        
        .message {
            display: flex;
            gap: 10px;
            max-width: 80%;
        }
        
        .message.self {
            align-self: flex-end;
            flex-direction: row-reverse;
        }
        
        .message-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }
        
        .message-content {
            background: #f0f0f0;
            padding: 10px 15px;
            border-radius: 18px;
        }
        
        .message.self .message-content {
            background: var(--primary);
            color: white;
        }
        
        .message-info {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 5px;
        }
        
        .message-sender {
            font-weight: 600;
            font-size: 0.9rem;
        }
        
        .message-time {
            font-size: 0.8rem;
            color: #999;
        }
        
        .message.self .message-time {
            color: rgba(255,255,255,0.7);
        }
        
        .chat-input-container {
            padding: 15px 20px;
            border-top: 1px solid #eee;
            display: flex;
            gap: 10px;
        }
        
        .chat-input {
            flex: 1;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 25px;
            font-family: 'Poppins', sans-serif;
            resize: none;
        }
        
        .send-btn {
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 50%;
            width: 45px;
            height: 45px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }
        
        .participants-sidebar {
            background: white;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            padding: 20px;
            overflow-y: auto;
        }
        
        .participants-title {
            font-size: 1.2rem;
            margin-bottom: 20px;
            color: var(--dark);
        }
        
        .participant-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .participant-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        
        .participant-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }
        
        @media (max-width: 992px) {
            .chat-container {
                grid-template-columns: 1fr;
                height: auto;
            }
            
            .participants-sidebar {
                display: none;
            }
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="chat-container">
        <div class="rooms-sidebar">
            <div class="rooms-title">
                <span>Sohbet Odaları</span>
                <button class="new-room-btn" id="newRoomBtn">+</button>
            </div>
            <ul class="room-list">
                <?php foreach($rooms as $room): ?>
                    <li class="room-item <?= $room['id'] == $active_room ? 'active' : '' ?>" 
                        onclick="window.location.href='chat.php?room=<?= $room['id'] ?>'">
                        <i class="fas fa-comments"></i>
                        <?= htmlspecialchars($room['name']) ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        
        <div class="chat-main">
            <div class="chat-header">
                <div>
                    <div class="room-name"><?= htmlspecialchars($room_info['name']) ?></div>
                    <div class="participants-count">
                        <?= count($participants) ?> katılımcı
                    </div>
                </div>
                <button id="inviteBtn">
                    <i class="fas fa-user-plus"></i> Davet Et
                </button>
            </div>
            
            <div class="messages-container" id="messagesContainer">
                <?php foreach($messages as $message): ?>
                    <div class="message <?= $message['user_id'] == $_SESSION['user_id'] ? 'self' : '' ?>">
                        <img src="<?= $message['profile_pic'] ?: 'assets/default-avatar.jpg' ?>" 
                             class="message-avatar"
                             alt="<?= htmlspecialchars($message['username']) ?>">
                        <div class="message-content">
                            <div class="message-info">
                                <span class="message-sender"><?= htmlspecialchars($message['username']) ?></span>
                                <span class="message-time">
                                    <?= date('H:i', strtotime($message['sent_at'])) ?>
                                </span>
                            </div>
                            <p><?= htmlspecialchars($message['message']) ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="chat-input-container">
                <textarea class="chat-input" id="messageInput" placeholder="Mesajınızı yazın..."></textarea>
                <button class="send-btn" id="sendBtn">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </div>
        </div>
        
        <div class="participants-sidebar">
            <div class="participants-title">Katılımcılar</div>
            <ul class="participant-list">
                <?php foreach($participants as $participant): ?>
                    <li class="participant-item">
                        <img src="<?= $participant['profile_pic'] ?: 'assets/default-avatar.jpg' ?>" 
                             class="participant-avatar"
                             alt="<?= htmlspecialchars($participant['username']) ?>">
                        <span><?= htmlspecialchars($participant['username']) ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>

    <script>
        // Mesaj gönderme
        document.getElementById('sendBtn').addEventListener('click', sendMessage);
        document.getElementById('messageInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        });

        function sendMessage() {
            const messageInput = document.getElementById('messageInput');
            const message = messageInput.value.trim();
            
            if (message === '') return;
            
            fetch('ajax/send_message.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `room_id=<?= $active_room ?>&message=${encodeURIComponent(message)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    messageInput.value = '';
                    // Yeni mesajı ekrana ekle
                    addMessageToChat(data.message);
                    // Mesaj kutusunu en alta kaydır
                    scrollToBottom();
                }
            });
        }

        function addMessageToChat(message) {
            const messagesContainer = document.getElementById('messagesContainer');
            const isSelf = message.user_id == <?= $_SESSION['user_id'] ?>;
            
            const messageDiv = document.createElement('div');
            messageDiv.className = `message ${isSelf ? 'self' : ''}`;
            messageDiv.innerHTML = `
                <img src="${message.profile_pic || 'assets/default-avatar.jpg'}" 
                     class="message-avatar"
                     alt="${message.username}">
                <div class="message-content">
                    <div class="message-info">
                        <span class="message-sender">${message.username}</span>
                        <span class="message-time">
                            ${new Date(message.sent_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}
                        </span>
                    </div>
                    <p>${message.message}</p>
                </div>
            `;
            
            messagesContainer.appendChild(messageDiv);
        }

        function scrollToBottom() {
            const messagesContainer = document.getElementById('messagesContainer');
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        }

        // Sayfa yüklendiğinde en alta kaydır
        window.addEventListener('load', scrollToBottom);

        // Yeni oda oluşturma
        document.getElementById('newRoomBtn').addEventListener('click', function() {
            const roomName = prompt('Yeni oda adını girin:');
            if (roomName && roomName.trim() !== '') {
                fetch('ajax/create_room.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `room_name=${encodeURIComponent(roomName)}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        window.location.href = `chat.php?room=${data.room_id}`;
                    }
                });
            }
        });

        // Kullanıcı davet etme
        document.getElementById('inviteBtn').addEventListener('click', function() {
            const username = prompt('Davet etmek istediğiniz kullanıcı adını girin:');
            if (username && username.trim() !== '') {
                fetch('ajax/invite_to_room.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `room_id=<?= $active_room ?>&username=${encodeURIComponent(username)}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(`${username} kullanıcısı odaya davet edildi!`);
                        location.reload();
                    } else {
                        alert(data.error || 'Bir hata oluştu');
                    }
                });
            }
        });

        // Gerçek zamanlı mesaj güncellemesi (Polling)
        setInterval(() => {
            fetch(`ajax/get_messages.php?room_id=<?= $active_room ?>&last_id=<?= !empty($messages) ? end($messages)['id'] : 0 ?>`)
            .then(response => response.json())
            .then(messages => {
                if (messages.length > 0) {
                    messages.forEach(message => {
                        addMessageToChat(message);
                    });
                    scrollToBottom();
                }
            });
        }, 3000); // 3 saniyede bir kontrol
    </script>
</body>
</html>