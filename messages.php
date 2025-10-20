<?php
include 'auth.php';
include 'db_connect.php';

// Retrieve token from cookie
$token = $_COOKIE['jwt_token'] ?? '';
$user = get_user_from_token($token);
if (!$user || $user['role'] !== 'customer') {
    header('Location: login.php');
    exit;
}

// Get complete user data from database
$user_sql = "SELECT * FROM users WHERE user_id = ?";
$stmt_user = $conn->prepare($user_sql);
$stmt_user->bind_param("i", $user['user_id']);
$stmt_user->execute();
$user_data = $stmt_user->get_result()->fetch_assoc();
$stmt_user->close();

// Handle conversation selection
$selected_conversation_id = $_GET['conversation_id'] ?? '';
$car_id = $_GET['car_id'] ?? '';

// Start new conversation if car_id is provided but no conversation exists
if ($car_id && !$selected_conversation_id) {
    // Get owner_id from car
    $car_sql = "SELECT owner_id FROM cars WHERE car_id = ?";
    $stmt_car = $conn->prepare($car_sql);
    $stmt_car->bind_param("i", $car_id);
    $stmt_car->execute();
    $car_result = $stmt_car->get_result();
    
    if ($car_result->num_rows > 0) {
        $car_data = $car_result->fetch_assoc();
        $owner_id = $car_data['owner_id'];
        
        // Check if conversation already exists
        $check_conv_sql = "SELECT conversation_id FROM conversations 
                          WHERE customer_id = ? AND owner_id = ? AND car_id = ?";
        $stmt_check = $conn->prepare($check_conv_sql);
        $stmt_check->bind_param("iii", $user['user_id'], $owner_id, $car_id);
        $stmt_check->execute();
        $existing_conv = $stmt_check->get_result()->fetch_assoc();
        $stmt_check->close();
        
        if ($existing_conv) {
            $selected_conversation_id = $existing_conv['conversation_id'];
        } else {
            // Create new conversation
            $new_conv_sql = "INSERT INTO conversations (customer_id, owner_id, car_id) VALUES (?, ?, ?)";
            $stmt_new = $conn->prepare($new_conv_sql);
            $stmt_new->bind_param("iii", $user['user_id'], $owner_id, $car_id);
            if ($stmt_new->execute()) {
                $selected_conversation_id = $stmt_new->insert_id;
            }
            $stmt_new->close();
        }
    }
    $stmt_car->close();
}

// Handle sending new message
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message']) && $selected_conversation_id) {
    $message_content = trim($_POST['message']);
    
    if (!empty($message_content)) {
        // Get conversation details to determine receiver
        $conv_sql = "SELECT owner_id, car_id FROM conversations WHERE conversation_id = ?";
        $stmt_conv = $conn->prepare($conv_sql);
        $stmt_conv->bind_param("i", $selected_conversation_id);
        $stmt_conv->execute();
        $conv_data = $stmt_conv->get_result()->fetch_assoc();
        $stmt_conv->close();
        
        if ($conv_data) {
            $receiver_id = $conv_data['owner_id'];
            $car_id = $conv_data['car_id'];
            
            // Insert message
            $insert_sql = "INSERT INTO messages (conversation_id, sender_id, receiver_id, message, car_id) 
                          VALUES (?, ?, ?, ?, ?)";
            $stmt_insert = $conn->prepare($insert_sql);
            $stmt_insert->bind_param("iiisi", $selected_conversation_id, $user['user_id'], $receiver_id, $message_content, $car_id);
            $stmt_insert->execute();
            $stmt_insert->close();
            
            // Redirect to avoid form resubmission
            header("Location: messages.php?conversation_id=" . $selected_conversation_id);
            exit;
        }
    }
}

// Mark messages as read when conversation is opened
if ($selected_conversation_id) {
    $mark_read_sql = "UPDATE messages SET is_read = 1 
                      WHERE conversation_id = ? AND receiver_id = ? AND is_read = 0";
    $stmt_mark = $conn->prepare($mark_read_sql);
    $stmt_mark->bind_param("ii", $selected_conversation_id, $user['user_id']);
    $stmt_mark->execute();
    $stmt_mark->close();
}

// Get user's conversations
$conversations_sql = "SELECT c.conversation_id, c.car_id, car.brand, car.model, car.image,
                      owner.user_id as owner_id, owner.name as owner_name,
                      (SELECT message FROM messages WHERE conversation_id = c.conversation_id ORDER BY timestamp DESC LIMIT 1) as last_message,
                      (SELECT timestamp FROM messages WHERE conversation_id = c.conversation_id ORDER BY timestamp DESC LIMIT 1) as last_message_time,
                      COUNT(CASE WHEN m.is_read = 0 AND m.receiver_id = ? THEN 1 END) as unread_count
                      FROM conversations c
                      JOIN cars car ON c.car_id = car.car_id
                      JOIN owners o ON car.owner_id = o.owner_id
                      JOIN users owner ON o.user_id = owner.user_id
                      LEFT JOIN messages m ON c.conversation_id = m.conversation_id
                      WHERE c.customer_id = ?
                      GROUP BY c.conversation_id
                      ORDER BY last_message_time DESC";
$stmt_conv = $conn->prepare($conversations_sql);
$stmt_conv->bind_param("ii", $user['user_id'], $user['user_id']);
$stmt_conv->execute();
$conversations = $stmt_conv->get_result();
$stmt_conv->close();

// Get messages for selected conversation
$messages = [];
if ($selected_conversation_id) {
    $messages_sql = "SELECT m.*, u.name as sender_name, u.user_id as sender_user_id
                     FROM messages m
                     JOIN users u ON m.sender_id = u.user_id
                     WHERE m.conversation_id = ?
                     ORDER BY m.timestamp ASC";
    $stmt_msg = $conn->prepare($messages_sql);
    $stmt_msg->bind_param("i", $selected_conversation_id);
    $stmt_msg->execute();
    $messages = $stmt_msg->get_result();
    $stmt_msg->close();
    
    // Get conversation details for header
    $conv_detail_sql = "SELECT car.brand, car.model, car.image, u.name as owner_name
                       FROM conversations c
                       JOIN cars car ON c.car_id = car.car_id
                       JOIN owners o ON car.owner_id = o.owner_id
                       JOIN users u ON o.user_id = u.user_id
                       WHERE c.conversation_id = ?";
    $stmt_conv_detail = $conn->prepare($conv_detail_sql);
    $stmt_conv_detail->bind_param("i", $selected_conversation_id);
    $stmt_conv_detail->execute();
    $conversation_details = $stmt_conv_detail->get_result()->fetch_assoc();
    $stmt_conv_detail->close();
}

// Get unread count for badge
$unread_count_sql = "SELECT COUNT(*) as unread_count 
                    FROM messages m
                    JOIN conversations c ON m.conversation_id = c.conversation_id
                    WHERE c.customer_id = ? AND m.receiver_id = ? AND m.is_read = 0";
$stmt_unread = $conn->prepare($unread_count_sql);
$stmt_unread->bind_param("ii", $user['user_id'], $user['user_id']);
$stmt_unread->execute();
$unread_result = $stmt_unread->get_result();
$unread_count = $unread_result->fetch_assoc()['unread_count'] ?? 0;
$stmt_unread->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages - Rentify</title>
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
    
    // Auto-refresh messages every 5 seconds
    let refreshInterval;
    
    function startAutoRefresh() {
        const urlParams = new URLSearchParams(window.location.search);
        const conversationId = urlParams.get('conversation_id');
        if (conversationId) {
            refreshInterval = setInterval(() => {
                refreshMessages(conversationId);
            }, 5000);
        }
    }
    
    function refreshMessages(conversationId) {
        // Get conversation details to determine user IDs
        fetch(`get_conversation_details.php?conversation_id=${conversationId}`)
            .then(response => response.json())
            .then(convData => {
                if (convData.success) {
                    // Use your existing get_messages.php API
                    return fetch(`get_messages.php?car_id=${convData.car_id}&current_user_id=<?php echo $user['user_id']; ?>&other_user_id=${convData.other_user_id}&is_owner=false`);
                }
                throw new Error('Failed to get conversation details');
            })
            .then(response => response.json())
            .then(messages => {
                updateMessagesDisplay(messages);
            })
            .catch(error => {
                console.error('Error refreshing messages:', error);
            });
    }
    
    function updateMessagesDisplay(messages) {
        const messagesContainer = document.getElementById('messagesContainer');
        if (!messagesContainer) return;
        
        let html = '';
        
        if (messages.length === 0) {
            html = `
                <div class="text-center py-12">
                    <div class="text-gray-400 text-6xl mb-4">💭</div>
                    <h3 class="text-xl font-semibold text-gray-300 mb-2">No messages yet</h3>
                    <p class="text-gray-500">Start the conversation by sending a message below.</p>
                </div>
            `;
        } else {
            messages.forEach(msg => {
                const isSender = msg.sender_id == <?php echo $user['user_id']; ?>;
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
        }
        
        messagesContainer.innerHTML = html;
        scrollToBottom();
    }
    
    // Start auto-refresh when page loads
    document.addEventListener('DOMContentLoaded', function() {
        scrollToBottom();
        startAutoRefresh();
    });
    
    // Stop auto-refresh when leaving the page
    window.addEventListener('beforeunload', function() {
        if (refreshInterval) {
            clearInterval(refreshInterval);
        }
    });
</script>
</head>
<body class="bg-gray-800 font-sans text-gray-100">
    <header class="bg-teal-600 text-gray-100 sticky top-0 z-50">
        <nav class="container mx-auto px-4 py-4 flex justify-between items-center">
            <h1 class="text-2xl font-bold">Rentify</h1>
            <div class="flex items-center space-x-4">
                <div class="flex items-center space-x-4">
                    <a href="dashboard.php" class="p-2 text-gray-100 hover:bg-teal-700 rounded-lg transition">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                        </svg>
                    </a>
                    <a href="messages.php" class="relative p-2 text-gray-100 hover:bg-teal-700 rounded-lg transition">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
                        </svg>
                        <?php if ($unread_count > 0): ?>
                            <span class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center">
                                <?php echo $unread_count; ?>
                            </span>
                        <?php endif; ?>
                    </a>
                    
                    <div class="relative">
                        <button onclick="toggleProfileDropdown()" class="flex items-center space-x-2">
                            <span class="text-gray-100"><?php echo htmlspecialchars($user_data['name'] ?? 'User'); ?></span>
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                        </button>
                        <div id="profileDropdown" class="hidden absolute right-0 mt-2 w-48 bg-gray-900 border border-gray-700 rounded-lg shadow-lg">
                            <a href="profile.php" class="block px-4 py-2 text-gray-100 hover:bg-teal-600">Account Settings</a>
                            <a href="messages.php" class="block px-4 py-2 text-gray-100 hover:bg-teal-600">Messages</a>
                            <a href="booking_history.php" class="block px-4 py-2 text-gray-100 hover:bg-teal-600">Booking History</a>
                            <a href="wishlist.php" class="block px-4 py-2 text-gray-100 hover:bg-teal-600">Wishlist</a>
                            <a href="logout.php" class="block px-4 py-2 text-gray-100 hover:bg-teal-600">Log Out</a>
                        </div>
                    </div>
                </div>
            </div>
        </nav>
    </header>
    
    <main class="container mx-auto px-4 py-8">
        <div class="max-w-6xl mx-auto">
            <!-- Page Header -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold mb-2">Messages</h1>
                <p class="text-gray-400">Communicate with car owners about your bookings and inquiries</p>
            </div>

            <div class="bg-gray-900 rounded-lg border border-gray-700 overflow-hidden">
                <div class="flex flex-col md:flex-row h-[600px]">
                    <!-- Conversations List -->
                    <div class="md:w-1/3 border-r border-gray-700">
                        <div class="p-4 border-b border-gray-700">
                            <h2 class="text-lg font-semibold">Conversations</h2>
                        </div>
                        <div class="overflow-y-auto h-full">
                            <?php if ($conversations && $conversations->num_rows > 0): ?>
                                <?php while ($conv = $conversations->fetch_assoc()): ?>
                                    <a href="messages.php?conversation_id=<?php echo $conv['conversation_id']; ?>" 
                                       class="block p-4 border-b border-gray-700 hover:bg-gray-800 transition <?php echo $selected_conversation_id == $conv['conversation_id'] ? 'bg-gray-800' : ''; ?>">
                                        <div class="flex items-start space-x-3">
                                            <img src="<?php echo $conv['image'] ?: 'Uploads/cars/placeholder.jpg'; ?>" 
                                                 alt="Car Image" class="w-12 h-12 object-cover rounded-lg">
                                            <div class="flex-1 min-w-0">
                                                <div class="flex justify-between items-start">
                                                    <h3 class="font-semibold truncate">
                                                        <?php echo htmlspecialchars($conv['brand'] . ' ' . $conv['model']); ?>
                                                    </h3>
                                                    <?php if ($conv['unread_count'] > 0): ?>
                                                        <span class="bg-red-500 text-white text-xs rounded-full px-2 py-1">
                                                            <?php echo $conv['unread_count']; ?>
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                                <p class="text-gray-400 text-sm truncate">
                                                    <?php echo htmlspecialchars($conv['owner_name']); ?>
                                                </p>
                                                <?php if ($conv['last_message']): ?>
                                                    <p class="text-gray-500 text-sm truncate mt-1">
                                                        <?php echo htmlspecialchars($conv['last_message']); ?>
                                                    </p>
                                                    <p class="text-gray-600 text-xs mt-1">
                                                        <?php 
                                                        if ($conv['last_message_time']) {
                                                            echo '<script>document.write(formatTime(new Date("' . $conv['last_message_time'] . '")))</script>';
                                                        }
                                                        ?>
                                                    </p>
                                                <?php else: ?>
                                                    <p class="text-gray-500 text-sm italic mt-1">No messages yet</p>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </a>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <div class="text-center py-12">
                                    <div class="text-gray-400 text-6xl mb-4">💬</div>
                                    <h3 class="text-xl font-semibold text-gray-300 mb-2">No conversations yet</h3>
                                    <p class="text-gray-500 mb-6">Start a conversation by contacting a car owner.</p>
                                    <a href="dashboard.php" class="bg-teal-600 hover:bg-teal-700 text-gray-100 px-6 py-3 rounded-lg transition">
                                        Browse Cars
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Messages Area -->
                    <div class="md:w-2/3 flex flex-col">
                        <?php if ($selected_conversation_id && $conversation_details): ?>
                            <!-- Conversation Header -->
                            <div class="p-4 border-b border-gray-700 bg-gray-800">
                                <div class="flex items-center space-x-3">
                                    <img src="<?php echo $conversation_details['image'] ?: 'Uploads/cars/placeholder.jpg'; ?>" 
                                         alt="Car Image" class="w-10 h-10 object-cover rounded-lg">
                                    <div>
                                        <h3 class="font-semibold">
                                            <?php echo htmlspecialchars($conversation_details['brand'] . ' ' . $conversation_details['model']); ?>
                                        </h3>
                                        <p class="text-gray-400 text-sm">
                                            Owner: <?php echo htmlspecialchars($conversation_details['owner_name']); ?>
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Messages Container -->
                            <div id="messagesContainer" class="flex-1 overflow-y-auto p-4 space-y-4">
                                <?php if ($messages && $messages->num_rows > 0): ?>
                                    <?php while ($msg = $messages->fetch_assoc()): ?>
                                        <div class="flex <?php echo $msg['sender_id'] == $user['user_id'] ? 'justify-end' : 'justify-start'; ?>">
                                            <div class="max-w-xs lg:max-w-md px-4 py-2 rounded-lg <?php echo $msg['sender_id'] == $user['user_id'] ? 'bg-teal-600 text-white' : 'bg-gray-700 text-gray-100'; ?>">
                                                <?php if ($msg['sender_id'] != $user['user_id']): ?>
                                                    <p class="text-xs font-semibold text-teal-300 mb-1">
                                                        <?php echo htmlspecialchars($msg['sender_name']); ?>
                                                    </p>
                                                <?php endif; ?>
                                                <p class="text-sm"><?php echo htmlspecialchars($msg['message']); ?></p>
                                                <p class="text-xs opacity-75 mt-1 text-right">
                                                    <script>
                                                        document.write(formatTime(new Date("<?php echo $msg['timestamp']; ?>")));
                                                    </script>
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
                                <form method="POST" class="flex space-x-2">
                                    <input type="text" name="message" placeholder="Type your message..." 
                                           class="flex-1 p-3 border border-gray-700 bg-gray-800 text-gray-100 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600"
                                           required>
                                    <button type="submit" class="bg-teal-600 hover:bg-teal-700 text-gray-100 px-6 py-3 rounded-lg transition">
                                        Send
                                    </button>
                                </form>
                            </div>
                        <?php else: ?>
                            <!-- No Conversation Selected -->
                            <div class="flex-1 flex items-center justify-center">
                                <div class="text-center">
                                    <div class="text-gray-400 text-6xl mb-4">📱</div>
                                    <h3 class="text-xl font-semibold text-gray-300 mb-2">Select a conversation</h3>
                                    <p class="text-gray-500">Choose a conversation from the list to start messaging.</p>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
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
</body>
</html>