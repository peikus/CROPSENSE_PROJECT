<?php
// technician_live_com.php - FULLY INTEGRATED TEMPLATE VERSION
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'technician') {
    header("Location: technician_login.php"); exit;
}
require_once 'database/database.php';

$user_id = $_SESSION['user_id'];
$full_name = $_SESSION['full_name'] ?? 'Technician';
?>
<!DOCTYPE html>
<html lang="zxx" class="light">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="x-ua-compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Live Chat • Technician | RiceGuard AI</title>
    
    <link rel="stylesheet" type="text/css" href="assets/css/bootstrap.min.css" />
    <link rel="stylesheet" type="text/css" href="assets/vendors/css/vendors.min.css" />
    <link rel="stylesheet" type="text/css" href="assets/css/theme.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        /* IMMEDIATE DARK MODE - NO FLASH */
        html.dark { 
            color-scheme: dark; 
            background-color: #0f172a !important; 
        }
        html.light { 
            color-scheme: light; 
            background-color: #f8fafc !important; 
        }
        
        .chat-container { flex: 1 1 auto; overflow-y: auto; }
        .bubble { max-width: 75%; padding: 12px 16px; border-radius: 18px; position: relative; }
        .three-dots {
            position: absolute; right: 12px; top: 12px; cursor: pointer;
            color: #64748b; padding: 4px; border-radius: 4px;
        }
        .three-dots:hover { background-color: rgba(0,0,0,0.1); }

        .dropdown-menu {
            display: none; position: absolute; right: 0; top: 40px;
            background: white; border: 1px solid #e5e7eb; border-radius: 8px;
            box-shadow: 0 10px 15px rgba(0,0,0,0.1); z-index: 9999; width: 130px; padding: 6px 0;
        }
        .dark .dropdown-menu { background: #1e2937; border-color: #334155; }
        .dropdown-item { padding: 8px 16px; width: 100%; text-align: left; border: none; background: none; font-size: 14px; }
        .dropdown-item:hover { background-color: #f8fafc; }
        .dark .dropdown-item:hover { background-color: #334155; }
        .edit { color: #1e40af; }
        .delete { color: #dc2626; }

        /* FIXED HEIGHT - TOP CONTENT ALWAYS VISIBLE */
        .nxl-content { padding-top: 0 !important; }
        .chat-outer-wrapper {
            min-height: calc(100vh - 120px);
            height: calc(100vh - 120px);
            max-width: 1280px;
            margin: 0 auto;
            padding-top: 8px;
        }
        
        /* Ensure no overflow hiding */
        body { overflow-x: hidden; }
        .nxl-content > div { height: 100%; }
    </style>
</head>
<body>

      <!-- Navigation (unchanged) -->
        <nav class="nxl-navigation">
            <div class="navbar-wrapper">
                
                <div class="navbar-content">
                    <ul class="nxl-navbar">
                        <li class="nxl-item nxl-caption"><label>Navigation</label></li>
                     
                        <li class="nxl-item active">
                            <a href="technician_dashboard.php" class="nxl-link">
                                <span class="nxl-micon"><i class="feather-airplay"></i></span>
                                <span class="nxl-mtext">Dashboard</span>
                            </a>
                        </li>
                        <li class="nxl-item nxl-hasmenu">
                            <a href="javascript:void(0);" class="nxl-link">
                                <span class="nxl-micon"><i class="feather-file-text"></i></span>
                                <span class="nxl-mtext">Proposal</span>
                                <span class="nxl-arrow"><i class="feather-chevron-right"></i></span>
                            </a>
                            <ul class="nxl-submenu">
                                <li class="nxl-item"><a class="nxl-link" href="technician_proposal.php">All Proposals</a></li>
                                <li class="nxl-item"><a class="nxl-link" href="technician_create_proposal.php">Create Proposal</a></li>
                            </ul>
                        </li>
                    
                        <li class="nxl-item">
                            <a href="technician_history_access.php" class="nxl-link">
                                <span class="nxl-micon"><i class="feather-clock"></i></span>
                                <span class="nxl-mtext">Farmers Record</span>
                            </a>
                        </li>

                        <li class="nxl-item">
                            <a href="technician_live_com.php" class="nxl-link">
                                <span class="nxl-micon"><i class="feather-message-square"></i></span>
                                <span class="nxl-mtext">Messenger</span>
                            </a>
                        </li>

                        <li class="nxl-item">
                            <a href="technician_announcement.php" class="nxl-link">
                                <span class="nxl-micon"><i class="feather-volume-2"></i></span>
                                <span class="nxl-mtext">Announcements</span>
                            </a>
                        </li>
                    </ul>              
                </div>
            </div>
        </nav>





<!-- Header - FIXED Notifications & Profile (Using your improved version) -->
<header class="nxl-header">
    <div class="header-wrapper">
        <div class="header-left d-flex align-items-center gap-4">
            <a href="javascript:void(0);" class="nxl-head-mobile-toggler" id="mobile-collapse">
                <div class="hamburger hamburger--arrowturn">
                    <div class="hamburger-box"><div class="hamburger-inner"></div></div>
                </div>
            </a>
            <div class="nxl-navigation-toggle">
                <a href="javascript:void(0);" id="menu-mini-button"><i class="feather-align-left"></i></a>
                <a href="javascript:void(0);" id="menu-expend-button" style="display: none"><i class="feather-arrow-right"></i></a>
            </div>
        </div>

        <div class="header-right ms-auto">
            <div class="d-flex align-items-center gap-3">
                <!-- Search -->
                
                   <div class="nxl-h-item dark-light-theme">
                        <a href="javascript:void(0);" class="nxl-head-link me-0 dark-button">
                            <i class="feather-moon"></i>
                        </a>
                        <a href="javascript:void(0);" class="nxl-head-link me-0 light-button" style="display: none">
                            <i class="feather-sun"></i>
                        </a>
                    </div>

                <!-- Notifications -->
                

               
            </div>
        </div>
    </div>
</header>

    <!-- MAIN CHAT AREA -->
    <!-- MAIN CHAT AREA (EXACTLY LIKE FARMER VERSION) -->
    <div class="nxl-container">
        <div id="chat-wrapper" class="chat-outer-wrapper bg-slate-100 dark:bg-slate-900 p-4">
            <div class="max-w-6xl mx-auto bg-white dark:bg-slate-900 h-full flex flex-col rounded-3xl shadow-2xl overflow-hidden">

            <!-- Technician top bar (NOW FULLY VISIBLE - FIXED Z-INDEX & PADDING) -->
                <div class="border-b px-6 py-5 flex items-center justify-between bg-white dark:bg-slate-900 relative z-20 shadow-sm">
                    <div class="flex items-center gap-4">
                        <div class="w-11 h-11 bg-emerald-600 text-white rounded-2xl flex items-center justify-center text-2xl flex-shrink-0">🔧</div>
                        <div>
                            <h1 class="font-semibold text-slate-900 dark:text-white text-lg leading-tight"><?= htmlspecialchars($full_name) ?></h1>
                            <p class="text-sm text-emerald-600 font-medium">Technician</p>
                        </div>
                    </div>
                </div>

                <div class="flex flex-1 overflow-hidden h-full">
                    <!-- Sidebar -->
                    <div class="w-80 border-r bg-slate-50 dark:bg-slate-800 overflow-y-auto flex-shrink-0">
                        <div class="p-4 border-b bg-white dark:bg-slate-900 sticky top-0 z-10 bg-opacity-95 backdrop-blur-sm">
                            <h3 class="font-medium text-slate-900 dark:text-white">Chats</h3>
                        </div>
                        <div id="chatList" class="p-3 flex-1"></div>
                    </div>

                    <!-- Chat Window -->
                    <div class="flex-1 flex flex-col min-h-0">
                        <div class="px-6 py-4 border-b bg-white dark:bg-slate-900 sticky top-0 z-10 bg-opacity-95 backdrop-blur-sm flex items-center gap-4">
                            <div id="chatAvatar" class="w-10 h-10 rounded-2xl flex items-center justify-center text-3xl"></div>
                            <div>
                                <h2 id="chatTitle" class="font-semibold text-lg text-slate-900 dark:text-white">Group Chat</h2>
                                <p id="chatSubtitle" class="text-sm text-slate-500 dark:text-slate-400">All users</p>
                            </div>
                        </div>

                        <div id="chatMessages" class="chat-container flex-1 p-6 space-y-6 bg-slate-50 dark:bg-slate-900 overflow-y-auto"></div>

                        <div class="p-6 border-t bg-white dark:bg-slate-900 flex-shrink-0">
                            <div class="flex gap-3">
                                <input id="msgInput" type="text" placeholder="Type your message..." 
                                    class="flex-1 border border-slate-300 dark:border-slate-600 rounded-3xl px-6 py-4 focus:outline-none focus:border-emerald-500 bg-white dark:bg-slate-800 text-slate-900 dark:text-white placeholder-slate-400">
                                <button onclick="sendMessage()" 
                                        class="bg-emerald-600 hover:bg-emerald-700 text-white px-10 rounded-3xl font-medium">Send</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

    <!-- Scripts -->
    <script src="assets/vendors/js/vendors.min.js"></script>
    <script src="assets/js/common-init.min.js"></script>
    <script src="assets/js/theme-customizer-init.min.js"></script>

    <script>
    tailwind.config = { darkMode: 'class' };

    // FIXED DARK MODE - NO MORE WHITE FLASH ON REFRESH
    function initDarkMode() {
        const savedMode = localStorage.getItem('darkMode') || 'light';
        const html = document.documentElement;
        
        if (savedMode === 'dark') {
            html.className = 'dark';
        } else {
            html.className = 'light';
        }

        const darkBtn = document.querySelector('.dark-button');
        const lightBtn = document.querySelector('.light-button');
        const wrapper = document.getElementById('chat-wrapper');
        
        if (savedMode === 'dark') {
            darkBtn.style.display = 'none';
            lightBtn.style.display = 'inline-block';
            if (wrapper) wrapper.classList.add('dark');
        } else {
            darkBtn.style.display = 'inline-block';
            lightBtn.style.display = 'none';
            if (wrapper) wrapper.classList.remove('dark');
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        // Theme toggle handlers
        document.querySelector('.dark-button').onclick = function() {
            document.documentElement.className = 'dark';
            localStorage.setItem('darkMode', 'dark');
            document.querySelector('.dark-button').style.display = 'none';
            document.querySelector('.light-button').style.display = 'inline-block';
            document.getElementById('chat-wrapper').classList.add('dark');
        };

        document.querySelector('.light-button').onclick = function() {
            document.documentElement.className = 'light';
            localStorage.setItem('darkMode', 'light');
            document.querySelector('.dark-button').style.display = 'inline-block';
            document.querySelector('.light-button').style.display = 'none';
            document.getElementById('chat-wrapper').classList.remove('dark');
        };

        initDarkMode();
    });

    // ================== CHAT LOGIC ==================
    let currentChat = 'group';
    let editingId = null;

    function loadChatList() {
        fetch('get_users_for_chat.php')
            .then(r => r.json())
            .then(users => {
                const container = document.getElementById('chatList');
                container.innerHTML = '';

                // Group Chat
                const groupDiv = document.createElement('div');
                groupDiv.className = `flex gap-3 p-4 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-2xl cursor-pointer ${currentChat === 'group' ? 'bg-emerald-100 dark:bg-emerald-900/30' : ''}`;
                groupDiv.innerHTML = `
                    <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-purple-600 text-white rounded-2xl flex items-center justify-center text-2xl">👥</div>
                    <div>
                        <p class="font-medium text-slate-900 dark:text-white">Group Chat</p>
                        <p class="text-xs text-slate-500 dark:text-slate-400">All users</p>
                    </div>
                `;
                groupDiv.onclick = () => openChat('group');
                container.appendChild(groupDiv);

                users.forEach(user => {
                    if (user.role === 'farmer') {
                        const div = document.createElement('div');
                        div.className = `flex gap-3 p-4 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-2xl cursor-pointer ${currentChat == user.id ? 'bg-emerald-100 dark:bg-emerald-900/30' : ''}`;
                        div.innerHTML = `
                            <div class="w-10 h-10 bg-amber-600 text-white rounded-2xl flex items-center justify-center text-2xl">👨‍🌾</div>
                            <div>
                                <p class="font-medium text-slate-900 dark:text-white">${user.full_name}</p>
                                <p class="text-xs text-slate-500 dark:text-slate-400">${user.role}</p>
                            </div>
                        `;
                        div.onclick = () => openChat(user.id, user.full_name);
                        container.appendChild(div);
                    }
                });
            });
    }

    function openChat(chatId, name = '') {
        currentChat = chatId;
        editingId = null;

        if (chatId === 'group') {
            document.getElementById('chatAvatar').innerHTML = '👥';
            document.getElementById('chatAvatar').className = 'w-10 h-10 bg-gradient-to-br from-blue-500 to-purple-600 text-white rounded-2xl flex items-center justify-center text-3xl';
            document.getElementById('chatTitle').textContent = 'Group Chat';
            document.getElementById('chatSubtitle').textContent = 'All users';
        } else {
            document.getElementById('chatAvatar').innerHTML = '👨‍🌾';
            document.getElementById('chatAvatar').className = 'w-10 h-10 bg-amber-600 text-white rounded-2xl flex items-center justify-center text-3xl';
            document.getElementById('chatTitle').textContent = name;
            document.getElementById('chatSubtitle').textContent = 'Private chat';
        }
        loadMessages();
        loadChatList();
    }

    function loadMessages() {
        const url = currentChat === 'group' ? 'get_group_messages.php' : `get_messages.php?to_user=${currentChat}`;
        fetch(url)
            .then(r => r.json())
            .then(messages => {
                const container = document.getElementById('chatMessages');
                container.innerHTML = '';
                if (messages.length === 0) {
                    container.innerHTML = `<p class="text-center text-slate-400 dark:text-slate-500 py-12">No messages yet. Send the first one!</p>`;
                    return;
                }
                messages.forEach(msg => {
                    const isMine = parseInt(msg.from_user_id) === <?= $user_id ?>;
                    const div = document.createElement('div');
                    div.className = `flex ${isMine ? 'justify-end' : 'justify-start'} message`;
                    div.innerHTML = `
                        <div class="bubble ${isMine ? 'bg-emerald-600 text-white' : 'bg-white dark:bg-slate-700 border border-slate-200 dark:border-slate-600 text-slate-900 dark:text-slate-100'} relative">
                            <div class="text-xs opacity-75 mb-1">${isMine ? 'You' : (msg.sender_name || 'User')}</div>
                            <p>${msg.message}</p>
                            <p class="text-[10px] mt-1 opacity-70">${msg.created_at}</p>
                            ${isMine ? `
                            <div class="three-dots" onclick="toggleDropdown(this)">
                                <i class="fas fa-ellipsis-v"></i>
                            </div>
                            <div class="dropdown-menu">
                                <button onclick="editMessage(${msg.id}, '${msg.message.replace(/'/g, "\\'")}'); event.stopImmediatePropagation();" class="dropdown-item edit">Edit</button>
                                <button onclick="deleteMessage(${msg.id}, this); event.stopImmediatePropagation();" class="dropdown-item delete">Delete</button>
                            </div>` : ''}
                        </div>
                    `;
                    container.appendChild(div);
                });
                container.scrollTop = container.scrollHeight;
            });
    }

function toggleDropdown(el) {
    const menu = el.parentElement.querySelector('.dropdown-menu');
    const isVisible = menu.style.display === 'block';
    
    // Close all menus first
    document.querySelectorAll('.dropdown-menu').forEach(m => {
        m.style.display = 'none';
    });
    
    // Show current menu only if it wasn't already visible
    if (!isVisible) {
        menu.style.display = 'block';
    }
}

// Add global click handler to close dropdowns
document.addEventListener('click', function(event) {
    if (!event.target.closest('.three-dots') && !event.target.closest('.dropdown-menu')) {
        document.querySelectorAll('.dropdown-menu').forEach(menu => {
            menu.style.display = 'none';
        });
    }
});

    function sendMessage() {
        const input = document.getElementById('msgInput');
        const text = input.value.trim();
        if (!text) return;
        let body = `message=${encodeURIComponent(text)}`;
        if (currentChat !== 'group') body += `&to_user=${currentChat}`;
        else body += '&is_group=1';
        if (editingId) body += `&edit_id=${editingId}`;

        fetch('send_message.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: body
        }).then(() => {
            input.value = '';
            editingId = null;
            loadMessages();
        });
    }

    function editMessage(id, text) {
        editingId = id;
        document.getElementById('msgInput').value = text;
        document.getElementById('msgInput').focus();
    }

    function deleteMessage(id, btn) {
        if (confirm('Delete this message?')) {
            fetch(`delete_message.php?id=${id}`)
                .then(() => btn.closest('.message').remove());
        }
    }

    // Auto refresh messages
    setInterval(() => loadMessages(), 4000);
    
    // Initialize chat
    loadChatList();
    openChat('group');
    </script>
</body>
</html>