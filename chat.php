<?php
include 'auth.php';
include 'db_connect.php';

$token = $_COOKIE['jwt_token'] ?? '';
$user = get_user_from_token($token);
if (!$user) {
    header('Location: login.php');
    exit;
}
$is_owner = $user['role'] === 'owner';
$car_id = $_GET['car_id'] ?? null;
if (!$car_id) {
    echo '<p class="text-red-400">No car specified.</p>';
    exit;
}

// Fetch car details and owner_id
$stmt = $conn->prepare("SELECT c.owner_id, c.brand, c.model, c.image, u.user_id as owner_user_id 
                       FROM cars c 
                       JOIN owners o ON c.owner_id = o.owner_id 
                       JOIN users u ON o.user_id = u.user_id 
                       WHERE c.car_id = ?");
$stmt->bind_param('i', $car_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    echo '<p class="text-red-400">Car not found.</p>';
    exit;
}
$car = $result->fetch_assoc();
$owner_id = $car['owner_user_id'];
$car_name = htmlspecialchars($car['brand'] . ' ' . $car['model']);
$car_image = $car['image'] ?: 'Uploads/cars/placeholder.jpg';
$stmt->close();

// Determine sender/receiver
if ($is_owner) {
    $other_user_id = $_GET['customer_id'] ?? null; // Customer ID from query param
    if (!$other_user_id) {
        echo '<p class="text-red-400">No customer specified.</p>';
        exit;
    }
} else {
    $other_user_id = $owner_id; // Customer is chatting with the owner
}
$current_user_id = $user['user_id'];

// Fetch other user's name for display
$stmt = $conn->prepare("SELECT name FROM users WHERE user_id = ?");
$stmt->bind_param('i', $other_user_id);
$stmt->execute();
$result = $stmt->get_result();
$other_user_name = $result->num_rows > 0 ? $result->fetch_assoc()['name'] : 'Unknown';
$stmt->close();

// Get conversation ID for this chat
$conv_stmt = $conn->prepare("SELECT conversation_id FROM conversations 
                           WHERE car_id = ? AND customer_id = ? AND owner_id = ?");
if ($is_owner) {
    $conv_stmt->bind_param('iii', $car_id, $other_user_id, $current_user_id);
} else {
    $conv_stmt->bind_param('iii', $car_id, $current_user_id, $other_user_id);
}
$conv_stmt->execute();
$conv_result = $conv_stmt->get_result();
$conversation_id = $conv_result->num_rows > 0 ? $conv_result->fetch_assoc()['conversation_id'] : null;
$conv_stmt->close();

// Get initial messages
$messages = [];
if ($conversation_id) {
    $messages_sql = "SELECT m.*, u.name as sender_name 
                     FROM messages m 
                     JOIN users u ON m.sender_id = u.user_id 
                     WHERE m.conversation_id = ? 
                     ORDER BY m.timestamp ASC";
    $stmt_msg = $conn->prepare($messages_sql);
    $stmt_msg->bind_param('i', $conversation_id);
    $stmt_msg->execute();
    $messages = $stmt_msg->get_result();
    $stmt_msg->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat - Rentify</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        function toggleProfileDropdown() {
            document.getElementById('profileDropdown').classList.toggle('hidden');
        }
        
        function scrollToBottom() {
            const messagesContainer = document.getElementById('messagesContainer');
            if (messagesContainer) {
                messagesContainer.scrollTop = messagesContainer.scrollHeight;
            }
        }
        
        function formatTime(timestamp) {
            const date = new Date(timestamp);
            const now = new Date();
            const diffMs = now - date;
            const diffMins = Math.floor(diffMs / 60000);
            const diffHours = Math.floor(diffMs / 3600000);
            const diffDays = Math.floor(diffMs / 86400000);
            
            if (diffMins < 1) return 'Just now';
            if (diffMins < 60) return `${diffMins}m ago`;
            if (diffHours < 24) return `${diffHours}h ago`;
            if (diffDays < 7) return `${diffDays}d ago`;
            return date.toLocaleDateString();
        }
        
        // Auto-refresh messages every 3 seconds
        setInterval(() => {
            getMessages();
        }, 3000);
        
        // Scroll to bottom when page loads
        document.addEventListener('DOMContentLoaded', function() {
            scrollToBottom();
        });
    </script>
</head>
<body class="bg-gray-800 font-sans text-gray-100">
    <header class="bg-teal-600 text-gray-100 sticky top-0 z-50">
        <nav class="container mx-auto px-4 py-4 flex justify-between items-center">
            <h1 class="text-2xl font-bold">Rentify</h1>
            <div class="flex items-center space-x-4">
                <div class="flex items-center space-x-4">
                    <a href="<?php echo $is_owner ? 'owner_dashboard.php' : 'dashboard.php'; ?>" class="p-2 text-gray-100 hover:bg-teal-700 rounded-lg transition">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                        </svg>
                    </a>
                    
                    <div class="relative">
                        <button onclick="toggleProfileDropdown()" class="flex items-center space-x-2">
                            <span class="text-gray-100"><?php echo htmlspecialchars($user['name'] ?? 'User'); ?></span>
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                        </button>
                        <div id="profileDropdown" class="hidden absolute right-0 mt-2 w-48 bg-gray-900 border border-gray-700 rounded-lg shadow-lg">
                            <a href="profile.php" class="block px-4 py-2 text-gray-100 hover:bg-teal-600">Account Settings</a>
                            <a href="messages.php" class="block px-4 py-2 text-gray-100 hover:bg-teal-600">Messages</a>
                            <a href="booking_history.php" class="block px-4 py-2 text-gray-100 hover:bg-teal-600">Booking History</a>
                            <?php if ($is_owner): ?>
                                <a href="owner_cars.php" class="block px-4 py-2 text-gray-100 hover:bg-teal-600">My Cars</a>
                            <?php else: ?>
                                <a href="wishlist.php" class="block px-4 py-2 text-gray-100 hover:bg-teal-600">Wishlist</a>
                            <?php endif; ?>
                            <a href="logout.php" class="block px-4 py-2 text-gray-100 hover:bg-teal-600">Log Out</a>
                        </div>
                    </div>
                </div>
            </div>
        </nav>
    </header>
    
    <main class="container mx-auto px-4 py-8">
        <div class="max-w-4xl mx-auto">
            <!-- Page Header -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold mb-2">Chat</h1>
                <p class="text-gray-400">Communicating about <?php echo $car_name; ?></p>
            </div>

            <div class="bg-gray-900 rounded-lg border border-gray-700 overflow-hidden">
                <!-- Conversation Header -->
                <div class="p-4 border-b border-gray-700 bg-gray-800">
                    <div class="flex items-center space-x-3">
                        <img src="<?php echo $car_image; ?>" 
                             alt="Car Image" class="w-12 h-12 object-cover rounded-lg">
                        <div>
                            <h3 class="font-semibold">
                                <?php echo $car_name; ?>
                            </h3>
                            <p class="text-gray-400 text-sm">
                                <?php echo $is_owner ? 'Customer' : 'Owner'; ?>: <?php echo htmlspecialchars($other_user_name); ?>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Messages Container -->
                <div id="messagesContainer" class="flex-1 overflow-y-auto p-4 space-y-4" style="max-height: 400px;">
                    <?php if ($messages && $messages->num_rows > 0): ?>
                        <?php while ($msg = $messages->fetch_assoc()): ?>
                            <div class="flex <?php echo $msg['sender_id'] == $current_user_id ? 'justify-end' : 'justify-start'; ?>">
                                <div class="max-w-xs lg:max-w-md px-4 py-2 rounded-lg <?php echo $msg['sender_id'] == $current_user_id ? 'bg-teal-600 text-white' : 'bg-gray-700 text-gray-100'; ?>">
                                    <?php if ($msg['sender_id'] != $current_user_id): ?>
                                        <p class="text-xs font-semibold text-teal-300 mb-1">
                                            <?php echo htmlspecialchars($msg['sender_name']); ?>
                                        </p>
                                    <?php endif; ?>
                                    <p class="text-sm"><?php echo htmlspecialchars($msg['message']); ?></p>
                                    <p class="text-xs opacity-75 mt-1 text-right">
                                        <?php
                                        // Server-side timestamp formatting
                                        $timestamp = strtotime($msg['timestamp']);
                                        $now = time();
                                        $diff = $now - $timestamp;
                                        
                                        if ($diff < 60) {
                                            echo 'Just now';
                                        } elseif ($diff < 3600) {
                                            echo floor($diff / 60) . 'm ago';
                                        } elseif ($diff < 86400) {
                                            echo floor($diff / 3600) . 'h ago';
                                        } elseif ($diff < 604800) {
                                            echo floor($diff / 86400) . 'd ago';
                                        } else {
                                            echo date('M j, Y', $timestamp);
                                        }
                                        ?>
                                    </p>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="text-center py-12">
                            <div class="text-gray-400 text-6xl mb-4">💭</div>
                            <h3 class="text-xl font-semibold text-gray-300 mb-2">No messages yet</h3>
                            <p class="text-gray-500">Start the conversation by sending a message below.</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Message Input -->
                <div class="p-4 border-t border-gray-700">
                    <form id="chat-form" class="flex space-x-2">
                        <input type="text" id="message" name="message" placeholder="Type your message..." 
                               class="flex-1 p-3 border border-gray-700 bg-gray-800 text-gray-100 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600"
                               required>
                        <button type="submit" class="bg-teal-600 hover:bg-teal-700 text-gray-100 px-6 py-3 rounded-lg transition">
                            Send
                        </button>
                    </form>
                    <div id="typing-indicator" class="text-gray-400 text-sm mt-2"></div>
                </div>
            </div>
        </div>
    </main>
    
    <footer class="bg-gray-900 text-gray-100 text-center py-4 mt-12">
        <p>&copy; 2025 Rentify. All rights reserved.</p>
        <div class="mt-2">
            <a href="https://rentify.com/terms" class="text-gray-400 hover:text-gray-200 mx-2">Terms of Service</a>
            <a href="https://rentify.com/privacy" class="text-gray-400 hover:text-gray-200 mx-2">Privacy Policy</a>
        </div>
    </footer>

    <script>
        const carId = <?php echo $car_id; ?>;
        const currentUserId = <?php echo $current_user_id; ?>;
        const otherUserId = <?php echo $other_user_id; ?>;
        const isOwner = <?php echo $is_owner ? 'true' : 'false'; ?>;

        // Send message
        document.getElementById('chat-form').addEventListener('submit', (e) => {
            e.preventDefault();
            const messageInput = document.getElementById('message');
            const message = messageInput.value.trim();
            
            if (message) {
                sendMessage(message);
                messageInput.value = '';
            }
        });

        // Typing indicator
        let typingTimeout;
        document.getElementById('message').addEventListener('input', () => {
            setTyping(true);
            clearTimeout(typingTimeout);
            typingTimeout = setTimeout(() => setTyping(false), 2000);
        });

        function sendMessage(content) {
            const formData = new FormData();
            formData.append('car_id', carId);
            formData.append('sender_id', currentUserId);
            formData.append('receiver_id', otherUserId);
            formData.append('message', content);
            formData.append('type', 'text');

            fetch('send_message.php', { method: 'POST', body: formData })
                .then(res => res.text())
                .then(() => {
                    getMessages();
                    setTyping(false);
                });
        }

        function getMessages() {
            fetch(`get_messages.php?car_id=${carId}&current_user_id=${currentUserId}&other_user_id=${otherUserId}&is_owner=${isOwner}`)
                .then(res => res.json())
                .then(messages => {
                    const messagesContainer = document.getElementById('messagesContainer');
                    
                    if (messages.length === 0) {
                        messagesContainer.innerHTML = `
                            <div class="text-center py-12">
                                <div class="text-gray-400 text-6xl mb-4">💭</div>
                                <h3 class="text-xl font-semibold text-gray-300 mb-2">No messages yet</h3>
                                <p class="text-gray-500">Start the conversation by sending a message below.</p>
                            </div>
                        `;
                    } else {
                        let html = '';
                        messages.forEach(msg => {
                            const isSender = msg.sender_id == currentUserId;
                            html += `
                                <div class="flex ${isSender ? 'justify-end' : 'justify-start'}">
                                    <div class="max-w-xs lg:max-w-md px-4 py-2 rounded-lg ${isSender ? 'bg-teal-600 text-white' : 'bg-gray-700 text-gray-100'}">
                                        ${!isSender ? `
                                            <p class="text-xs font-semibold text-teal-300 mb-1">
                                                ${msg.sender_name}
                                            </p>
                                        ` : ''}
                                        <p class="text-sm">${msg.message}</p>
                                        <p class="text-xs opacity-75 mt-1 text-right">
                                            ${formatTime(msg.timestamp)}
                                        </p>
                                    </div>
                                </div>
                            `;
                        });
                        messagesContainer.innerHTML = html;
                    }
                    scrollToBottom();
                });
        }

        function setTyping(isTyping) {
            fetch(`set_typing.php?car_id=${carId}&user_id=${currentUserId}&is_typing=${isTyping ? 1 : 0}`);
        }

        function getTyping() {
            fetch(`get_typing.php?car_id=${carId}&user_id=${otherUserId}`)
                .then(res => res.json())
                .then(data => {
                    document.getElementById('typing-indicator').textContent = data.is_typing ? 'Typing...' : '';
                });
        }

        // Poll for typing every 2 seconds
        setInterval(getTyping, 2000);
    </script>
</body>
</html>
