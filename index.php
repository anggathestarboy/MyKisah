<?php
require 'db.php';
session_start();

// Redirect ke login jika belum login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$me = $_SESSION['user_id'];

// Update last seen
$pdo->prepare("UPDATE users SET last_seen = NOW() WHERE id=?")
    ->execute([$_SESSION['user_id']]);

// Ambil data user yang login
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$me]);
$current_user = $stmt->fetch();

/* ========= LOGOUT ========= */
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: login.php");
    exit;
}

/* ========= CREATE / GET CHAT ========= */
$chat_id = null;
$other_user = null;

if (isset($_GET['with'])) {
    $other = $_GET['with'];

    // Ambil data user yang diajak chat
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$other]);
    $other_user = $stmt->fetch();

    $stmt = $pdo->prepare("
        SELECT * FROM chats
        WHERE (user_one=? AND user_two=?)
           OR (user_one=? AND user_two=?)
    ");
    $stmt->execute([$me,$other,$other,$me]);
    $chat = $stmt->fetch();

    if (!$chat) {
        $pdo->prepare("
            INSERT INTO chats (user_one,user_two,created_at)
            VALUES (?,?,NOW())
        ")->execute([$me,$other]);
        $chat_id = $pdo->lastInsertId();
    } else {
        $chat_id = $chat['id'];
    }
}

/* ========= SEND MESSAGE (AJAX) ========= */
if (isset($_POST['send'])) {
    $message = trim($_POST['message']);
    if (!empty($message)) {
        $pdo->prepare("
            INSERT INTO messages (chat_id,sender_id,message,created_at)
            VALUES (?,?,?,NOW())
        ")->execute([
            $_POST['chat_id'],
            $me,
            htmlspecialchars($message)
        ]);
        
        // Update last seen lagi setelah mengirim pesan
        $pdo->prepare("UPDATE users SET last_seen = NOW() WHERE id=?")
            ->execute([$me]);
    }
    exit;
}

/* ========= FETCH MESSAGE (AJAX) ========= */
if (isset($_GET['fetch'])) {
    $stmt = $pdo->prepare("
        SELECT m.*, u.name, u.avatar 
        FROM messages m
        JOIN users u ON u.id = m.sender_id
        WHERE m.chat_id=?
        ORDER BY m.created_at ASC
    ");
    $stmt->execute([$_GET['chat_id']]);
    echo json_encode($stmt->fetchAll());
    exit;
}

/* ========= FETCH UNREAD COUNT (AJAX) ========= */
if (isset($_GET['unread_count'])) {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as unread 
        FROM messages m
        JOIN chats c ON c.id = m.chat_id
        WHERE (c.user_one = ? OR c.user_two = ?)
        AND m.sender_id != ?
        AND m.is_read = 0
    ");
    $stmt->execute([$me, $me, $me]);
    $result = $stmt->fetch();
    echo $result['unread'];
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat - My Kisah</title>
    <!-- Tailwind CSS via CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', system-ui, sans-serif;
            background-color: #f5f5f5;
            padding-top: 64px; /* Untuk mengkompensasi header fixed */
        }
        
        /* Fixed Header */
        header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
        }
        
        /* Main Container */
        .main-container {
            margin-top: 0;
        }
        
        .chat-container {
            height: calc(100vh - 64px); /* Tinggi viewport dikurangi tinggi header */
        }
        
        @media (max-width: 768px) {
            body {
                padding-top: 56px;
            }
            .chat-container {
                height: calc(100vh - 56px);
            }
        }
        
        .messages-container {
            height: calc(100% - 80px);
            padding-bottom: 120px;
        }
        
        .avatar-default {
            background: linear-gradient(135deg, #0088cc 0%, #34b7f1 100%);
            color: white;
        }
        
        .message-sender {
            background-color: #0088cc;
            color: white;
            border-radius: 18px 18px 4px 18px;
        }
        
        .message-receiver {
            background-color: white;
            color: #333;
            border-radius: 18px 18px 18px 4px;
            box-shadow: 0 1px 2px rgba(0,0,0,0.1);
        }
        
        .online-dot {
            width: 8px;
            height: 8px;
            background-color: #10B981;
            border-radius: 50%;
            position: absolute;
            bottom: 2px;
            right: 2px;
            border: 2px solid white;
        }
        
        .sidebar {
            transition: transform 0.3s ease;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                position: fixed;
                left: 0;
                top: 56px; /* Mulai dari bawah header */
                height: calc(100vh - 56px);
                z-index: 50;
                transform: translateX(-100%);
                width: 85%;
                max-width: 320px;
            }
            .sidebar.active {
                transform: translateX(0);
            }
            .overlay {
                position: fixed;
                top: 56px; /* Mulai dari bawah header */
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0,0,0,0.5);
                z-index: 40;
                display: none;
            }
            .overlay.active {
                display: block;
            }
        }
        
        .message-input-fixed {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: white;
            border-top: 1px solid #e5e7eb;
            z-index: 10;
            padding: 12px 16px;
            height: 76px;
            box-sizing: border-box;
        }
        
        @media (min-width: 769px) {
            .message-input-fixed {
                position: sticky;
                bottom: 0;
                background: white;
                border-top: 1px solid #e5e7eb;
                z-index: 10;
                padding: 12px 16px;
                height: 76px;
                box-sizing: border-box;
            }
        }
        
        .messages-container-with-input {
            padding-bottom: 150px;
        }
        
        .avatar-img {
            display: block;
        }
        
        .avatar-fallback {
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .scroll-margin-bottom {
            scroll-margin-bottom: 100px;
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Header/Navbar - FIXED -->
    <header class="bg-white shadow-sm border-b border-gray-200 fixed top-0 left-0 right-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <!-- Left: Hamburger menu (mobile) -->
                <div class="flex items-center md:hidden">
                    <button id="menuToggle" class="p-2 rounded-md text-gray-600 hover:text-gray-900 hover:bg-gray-100">
                        <i class="fas fa-bars text-xl"></i>
                    </button>
                </div>

                <!-- Center: Logo/Title -->
                <div class="flex-1 flex justify-center md:justify-start">
                    <div class="flex items-center">
                        <i class="fas fa-comments text-blue-500 text-xl mr-2"></i>
                        <h1 class="text-lg font-bold text-gray-900">My Kisah</h1>
                    </div>
                </div>

                <!-- Right: User info and notifications -->
                <div class="flex items-center space-x-4">
                    <!-- Notification Bell with Badge -->
                   
                    
                    <!-- User Avatar and Menu -->
                    <div class="relative">
                        <button id="userMenuBtn" class="flex items-center space-x-2 focus:outline-none">
                            <div class="relative">
                                <?php if (!empty($current_user['avatar'])): ?>
                                    <img src="uploads/<?= htmlspecialchars($current_user['avatar']) ?>" 
                                         alt="<?= htmlspecialchars($current_user['name']) ?>" 
                                         class="w-10 h-10 rounded-full object-cover border-2 border-blue-500 avatar-img"
                                         onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                    <div class="w-10 h-10 rounded-full avatar-default flex items-center justify-center font-semibold avatar-fallback" style="display: none;">
                                        <?= strtoupper(substr($current_user['name'], 0, 1)) ?>
                                    </div>
                                <?php else: ?>
                                    <div class="w-10 h-10 rounded-full avatar-default flex items-center justify-center font-semibold avatar-fallback">
                                        <?= strtoupper(substr($current_user['name'], 0, 1)) ?>
                                    </div>
                                <?php endif; ?>
                                <div class="online-dot"></div>
                            </div>
                            <span class="hidden md:inline text-gray-700 font-medium"><?= htmlspecialchars($current_user['name']) ?></span>
                            <i class="fas fa-chevron-down text-gray-500 hidden md:inline"></i>
                        </button>
                        
                        <!-- Dropdown Menu -->
                        <div id="userMenu" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg py-1 z-50 border border-gray-200">
                            <a href="profile.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">
                                <i class="fas fa-user mr-2"></i>Profile
                            </a>
                            <a href="?logout" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">
                                <i class="fas fa-sign-out-alt mr-2"></i>Logout
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Container -->
    <div class="main-container max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
        <div class="flex chat-container">
            <!-- Overlay for mobile sidebar -->
            <div id="overlay" class="overlay"></div>

            <!-- Sidebar (User List) -->
            <div id="sidebar" class="sidebar w-full md:w-80 bg-white border-r border-gray-200 flex flex-col">
                <!-- Sidebar Header -->
                <div class="p-4 border-b border-gray-200">
                    <div class="flex justify-between items-center">
                        <h2 class="text-lg font-semibold text-gray-800">Contacts</h2>
                        <button id="closeSidebar" class="md:hidden text-gray-600 hover:text-gray-900">
                            <i class="fas fa-times text-xl"></i>
                        </button>
                    </div>
                    <div class="mt-2 relative">
                        <input type="text" 
                               id="searchContacts" 
                               placeholder="Search contacts..." 
                               class="w-full px-4 py-2 pl-10 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                    </div>
                </div>

                <!-- User List -->
                <div class="flex-1 overflow-y-auto">
                    <?php
                    $users = $pdo->query("
                        SELECT id, name, avatar, bio, last_seen 
                        FROM users 
                        WHERE id != $me
                        ORDER BY 
                            CASE 
                                WHEN TIMESTAMPDIFF(MINUTE, last_seen, NOW()) < 5 THEN 1
                                ELSE 2
                            END,
                            name ASC
                    ");

                    foreach ($users as $u):
                        // Cek apakah online (last_seen dalam 5 menit)
                        $is_online = false;
                        $last_seen_text = 'Never';
                        
                        if (!empty($u['last_seen'])) {
                            $last_seen_time = strtotime($u['last_seen']);
                            $is_online = ($last_seen_time > (time() - 300));
                            $time_diff = time() - $last_seen_time;
                            
                            if ($time_diff < 60) {
                                $last_seen_text = 'Just now';
                            } elseif ($time_diff < 3600) {
                                $last_seen_text = floor($time_diff / 60) . ' min ago';
                            } elseif ($time_diff < 86400) {
                                $last_seen_text = floor($time_diff / 3600) . ' hour' . (floor($time_diff / 3600) > 1 ? 's' : '') . ' ago';
                            } else {
                                $last_seen_text = date('M d, Y', $last_seen_time);
                            }
                        }
                    ?>
                    <a href="?with=<?= $u['id'] ?>" 
                       class="flex items-center p-3 border-b border-gray-100 hover:bg-blue-50 transition duration-200 user-item <?= isset($other_user) && isset($other_user['id']) && $other_user['id'] == $u['id'] ? 'bg-blue-50' : '' ?>">
                        <div class="relative flex-shrink-0">
                            <?php if (!empty($u['avatar'])): ?>
                                <img src="uploads/<?= htmlspecialchars($u['avatar']) ?>" 
                                     alt="<?= htmlspecialchars($u['name']) ?>" 
                                     class="w-12 h-12 rounded-full object-cover avatar-img"
                                     onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                <div class="w-12 h-12 rounded-full avatar-default flex items-center justify-center font-semibold text-lg avatar-fallback" style="display: none;">
                                    <?= strtoupper(substr($u['name'], 0, 1)) ?>
                                </div>
                            <?php else: ?>
                                <div class="w-12 h-12 rounded-full avatar-default flex items-center justify-center font-semibold text-lg avatar-fallback">
                                    <?= strtoupper(substr($u['name'], 0, 1)) ?>
                                </div>
                            <?php endif; ?>
                            <?php if ($is_online): ?>
                                <div class="online-dot"></div>
                            <?php endif; ?>
                        </div>
                        <div class="ml-3 flex-1">
                            <div class="flex justify-between items-start">
                                <h3 class="font-medium text-gray-900"><?= htmlspecialchars($u['name']) ?></h3>
                                <span class="text-xs text-gray-500"><?= $last_seen_text ?></span>
                            </div>
                            <p class="text-sm text-gray-600 truncate"><?= htmlspecialchars($u['bio'] ?? 'No bio') ?></p>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>

                <!-- Sidebar Footer -->
                <div class="p-4 border-t border-gray-200">
                    <div class="text-center">
                        <p class="text-sm text-gray-600">Total: <?= $users->rowCount() ?> contacts</p>
                    </div>
                </div>
            </div>

            <!-- Main Chat Area -->
            <div class="flex-1 flex flex-col bg-white rounded-lg shadow-sm border border-gray-200">
                <?php if (isset($chat_id) && $other_user): ?>
                <!-- Chat Header -->
                <div class="p-4 border-b border-gray-200 flex items-center justify-between">
                    <div class="flex items-center">
                        <a href="index.php" class="mr-3 md:hidden text-gray-600 hover:text-gray-900">
                            <i class="fas fa-arrow-left text-xl"></i>
                        </a>
                        <div class="relative">
                            <?php if (!empty($other_user['avatar'])): ?>
                                <img src="uploads/<?= htmlspecialchars($other_user['avatar']) ?>" 
                                     alt="<?= htmlspecialchars($other_user['name']) ?>" 
                                     class="w-10 h-10 rounded-full object-cover avatar-img"
                                     onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                <div class="w-10 h-10 rounded-full avatar-default flex items-center justify-center font-semibold avatar-fallback" style="display: none;">
                                    <?= strtoupper(substr($other_user['name'], 0, 1)) ?>
                                </div>
                            <?php else: ?>
                                <div class="w-10 h-10 rounded-full avatar-default flex items-center justify-center font-semibold avatar-fallback">
                                    <?= strtoupper(substr($other_user['name'], 0, 1)) ?>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($other_user['last_seen']) && strtotime($other_user['last_seen']) > (time() - 300)): ?>
                                <div class="online-dot"></div>
                            <?php endif; ?>
                        </div>
                        <div class="ml-3">
                            <h2 class="font-semibold text-gray-900"><?= htmlspecialchars($other_user['name']) ?></h2>
                            <p class="text-sm text-gray-600" id="userStatus">
                                <?php 
                                if (!empty($other_user['last_seen'])) {
                                    echo (strtotime($other_user['last_seen']) > (time() - 300)) 
                                        ? 'Online' 
                                        : 'Last seen ' . date('H:i', strtotime($other_user['last_seen']));
                                } else {
                                    echo 'Never seen';
                                }
                                ?>
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center space-x-2">
                        <button class="p-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-full">
                            <i class="fas fa-ellipsis-v"></i>
                        </button>
                    </div>
                </div>

                <!-- Messages Container -->
                <div id="messagesContainer" class="flex-1 overflow-y-auto p-4 bg-gray-50 messages-container scroll-margin-bottom">
                    <div id="chatMessages" class="space-y-4 pb-4">
                        <!-- Messages will be loaded here -->
                    </div>
                    <!-- Empty spacer div to ensure last message is visible -->
                    <div class="h-24"></div>
                </div>

                <!-- Message Input - FIXED POSITION -->
                <div class="message-input-fixed">
                    <div class="flex items-center h-full">
                       
                        <div class="flex-1 mx-2">
                            <input type="text" 
                                   id="messageInput" 
                                   placeholder="Type a message..." 
                                   class="w-full px-4 py-3 border border-gray-300 rounded-full focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                   onkeypress="if(event.keyCode==13) sendMessage()">
                        </div>
                        <button onclick="sendMessage()" 
                                class="p-3 bg-blue-500 text-white rounded-full hover:bg-blue-600 transition duration-200">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </div>
                </div>

                <script>
                const chatId = <?= $chat_id ?>;
                const currentUserId = <?= $me ?>;
                const otherUserId = <?= $other_user['id'] ?>;
                let lastMessageId = 0;

                // Function to handle avatar display with fallback
                function displayAvatar(avatarUrl, userName, elementId, size = 'w-6 h-6') {
                    if (avatarUrl) {
                        return `<img src="uploads/${avatarUrl}" 
                                     alt="${userName}" 
                                     class="${size} rounded-full ${elementId}"
                                     onerror="this.style.display='none'; document.querySelector('.${elementId}-fallback').style.display='flex';">
                               <div class="${size} rounded-full avatar-default flex items-center justify-center text-xs font-semibold ${elementId}-fallback" style="display: none;">
                                    ${userName.charAt(0).toUpperCase()}
                               </div>`;
                    } else {
                        return `<div class="${size} rounded-full avatar-default flex items-center justify-center text-xs font-semibold">
                                    ${userName.charAt(0).toUpperCase()}
                               </div>`;
                    }
                }

                // Load messages with better scroll handling
                function loadMessages() {
                    fetch('?fetch=1&chat_id=' + chatId)
                        .then(r => r.json())
                        .then(messages => {
                            const container = document.getElementById('chatMessages');
                            let html = '';
                            
                            messages.forEach(msg => {
                                if (msg.id > lastMessageId) {
                                    lastMessageId = msg.id;
                                }
                                
                                const isSender = msg.sender_id == currentUserId;
                                const time = new Date(msg.created_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
                                const avatarElement = displayAvatar(msg.avatar, msg.name, 'msg-avatar-' + msg.id);
                                
                                html += `
                                <div class="flex ${isSender ? 'justify-end' : 'justify-start'}">
                                    <div class="max-w-xs lg:max-w-md">
                                        <div class="flex items-end ${isSender ? 'flex-row-reverse' : ''} mb-1">
                                            <div class="${isSender ? 'ml-2' : 'mr-2'} relative">
                                                ${avatarElement}
                                            </div>
                                            <div class="${isSender ? 'message-sender' : 'message-receiver'} px-4 py-2">
                                                <p>${msg.message}</p>
                                            </div>
                                        </div>
                                        <div class="text-xs text-gray-500 ${isSender ? 'text-right' : 'text-left'}">
                                            ${time}
                                        </div>
                                    </div>
                                </div>
                                `;
                            });
                            
                            container.innerHTML = html;
                           
                        });
                }

                // Improved scroll function
                
                // Send message
                function sendMessage() {
                    const input = document.getElementById('messageInput');
                    const message = input.value.trim();
                    
                    if (message === '') return;
                    
                    // Show sending indicator
                    const container = document.getElementById('chatMessages');
                    const time = new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
                    
                    container.innerHTML += `
                    <div class="flex justify-end">
                        <div class="max-w-xs lg:max-w-md">
                            <div class="flex items-end flex-row-reverse mb-1">
                                <div class="message-sender px-4 py-2 opacity-80">
                                    <p>${message}</p>
                                </div>
                            </div>
                            <div class="text-xs text-gray-500 text-right">
                                ${time} <i class="fas fa-clock ml-1"></i>
                            </div>
                        </div>
                    </div>
                    `;
                    
                    fetch('', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                        body: 'send=1&chat_id=' + chatId + '&message=' + encodeURIComponent(message)
                    }).then(() => {
                        input.value = '';
                        loadMessages();
                    });
                }

                // Update user status
                function updateUserStatus() {
                    fetch('?fetch=1&chat_id=' + chatId)
                        .then(r => r.json())
                        .then(messages => {
                            const lastMessage = messages[messages.length - 1];
                            if (lastMessage && lastMessage.sender_id == otherUserId) {
                                const time = new Date(lastMessage.created_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
                                document.getElementById('userStatus').textContent = `Last seen ${time}`;
                            }
                        });
                }

                // Auto-refresh messages every 2 seconds
               // Auto-refresh setiap 10 detik (opsional)
setInterval(loadMessages, 10000);
                
                // Auto-refresh user status every 30 seconds
                setInterval(updateUserStatus, 30000);
                
                // Load initial messages
                loadMessages();

                // Focus input when clicking on message area (for mobile)
                document.getElementById('messagesContainer').addEventListener('click', function() {
                    document.getElementById('messageInput').focus();
                });

                // Adjust scroll on window resize (for mobile keyboard)
                window.addEventListener('resize', function() {
                    setTimeout(scrollToBottom, 100);
                });
                </script>

                <?php else: ?>
                <!-- No chat selected state -->
                <div class="flex-1 flex flex-col items-center justify-center p-8 text-center messages-container-with-input">
                    <div class="w-32 h-32 bg-gradient-to-r from-blue-100 to-blue-50 rounded-full flex items-center justify-center mb-6">
                        <i class="fas fa-comments text-blue-400 text-5xl"></i>
                    </div>
                    <h3 class="text-2xl font-semibold text-gray-800 mb-2">Welcome to My Kisah Chat</h3>
                    <p class="text-gray-600 mb-6 max-w-md">
                        Memulai koneksi dengan semua orang, klik tombol "Start Chat" dibawah atau gunakan sidebar di pojok kiri atas untuk memulai chat dengan sesama pengguna
                    </p>
                    
                    <!-- START CHAT Button -->
                    <button id="startChatBtn" 
                            class="mb-8 px-8 py-3 bg-blue-500 text-white font-medium rounded-full hover:bg-blue-600 transition duration-200 shadow-md">
                        <i class="fas fa-comment-dots mr-2"></i>
                        START CHAT
                    </button>
                    
                    <div class="grid grid-cols-2 gap-4 max-w-sm">
                        <div class="bg-blue-50 p-4 rounded-lg">
                            <i class="fas fa-user-friends text-blue-500 text-xl mb-2"></i>
                            <h4 class="font-medium text-gray-800">Friends</h4>
                            <p class="text-sm text-gray-600">Chat with your friends</p>
                        </div>
                        <div class="bg-green-50 p-4 rounded-lg">
                            <i class="fas fa-shield-alt text-green-500 text-xl mb-2"></i>
                            <h4 class="font-medium text-gray-800">Secure</h4>
                            <p class="text-sm text-gray-600">End-to-end encrypted</p>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- JavaScript -->
    <script>
        // Mobile menu toggle
        document.getElementById('menuToggle').addEventListener('click', function() {
            document.getElementById('sidebar').classList.add('active');
            document.getElementById('overlay').classList.add('active');
        });

        // Close sidebar button
        document.getElementById('closeSidebar').addEventListener('click', function() {
            document.getElementById('sidebar').classList.remove('active');
            document.getElementById('overlay').classList.remove('active');
        });

        // START CHAT Button - Buka sidebar
        document.getElementById('startChatBtn')?.addEventListener('click', function() {
            document.getElementById('sidebar').classList.add('active');
            document.getElementById('overlay').classList.add('active');
        });

        // Overlay click to close sidebar
        document.getElementById('overlay').addEventListener('click', function() {
            this.classList.remove('active');
            document.getElementById('sidebar').classList.remove('active');
        });

        // User menu toggle
        document.getElementById('userMenuBtn').addEventListener('click', function() {
            document.getElementById('userMenu').classList.toggle('hidden');
        });

        // Close user menu when clicking outside
        document.addEventListener('click', function(event) {
            const userMenu = document.getElementById('userMenu');
            const userMenuBtn = document.getElementById('userMenuBtn');
            if (userMenu && !userMenu.contains(event.target) && !userMenuBtn.contains(event.target)) {
                userMenu.classList.add('hidden');
            }
        });

        // Search contacts
        document.getElementById('searchContacts').addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const userItems = document.querySelectorAll('.user-item');
            
            userItems.forEach(item => {
                const name = item.querySelector('h3').textContent.toLowerCase();
                const bio = item.querySelector('p').textContent.toLowerCase();
                
                if (name.includes(searchTerm) || bio.includes(searchTerm)) {
                    item.style.display = 'flex';
                } else {
                    item.style.display = 'none';
                }
            });
        });

        // Update online count
        function updateOnlineCount() {
            const onlineDots = document.querySelectorAll('.online-dot');
            const onlineCount = document.getElementById('onlineCount');
            if (onlineCount) {
                onlineCount.textContent = `${onlineDots.length} online`;
            }
        }
        
        // Update unread count
        function updateUnreadCount() {
            fetch('?unread_count=1')
                .then(r => r.text())
                .then(count => {
                    const badge = document.getElementById('unreadBadge');
                    if (parseInt(count) > 0) {
                        badge.textContent = count > 9 ? '9+' : count;
                        badge.classList.remove('hidden');
                    } else {
                        badge.classList.add('hidden');
                    }
                });
        }

        // Initial updates
        updateOnlineCount();
        updateUnreadCount();
        
        // Update unread count every 10 seconds
        setInterval(updateUnreadCount, 10000);

        // Notification bell
        document.getElementById('notificationBtn').addEventListener('click', function() {
            // Mark all as read or show notifications
            alert('You have unread messages!');
        });

        // Handle avatar image errors
        function handleAvatarError(imgElement) {
            imgElement.style.display = 'none';
            const fallback = imgElement.nextElementSibling;
            if (fallback && fallback.classList.contains('avatar-fallback')) {
                fallback.style.display = 'flex';
            }
        }

        // Initialize avatar error handlers
        document.querySelectorAll('.avatar-img').forEach(img => {
            img.addEventListener('error', function() {
                handleAvatarError(this);
            });
        });
        
        // Ensure proper scroll behavior with fixed header
        window.addEventListener('load', function() {
            // Adjust any scroll calculations if needed
            console.log('Page loaded with fixed header');
        });
    </script>
</body>
</html>