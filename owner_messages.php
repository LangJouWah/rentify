<?php
include 'auth.php';
include 'db_connect.php';

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Retrieve token from cookie
$token = $_COOKIE['jwt_token'] ?? '';
$user = get_user_from_token($token);
if (!$user || $user['role'] !== 'owner') {
    echo '<p class="text-red-400">Unauthorized. Please <a href="login.php" class="text-teal-400 hover:underline">log in</a> as an owner.</p>';
    exit;
}

// Get owner_id
$sql_owner = "SELECT owner_id FROM Owners WHERE user_id = ?";
$stmt_owner = $conn->prepare($sql_owner);
$stmt_owner->bind_param("i", $user['user_id']);
$stmt_owner->execute();
$owner_result = $stmt_owner->get_result();
if ($owner_result->num_rows === 0) {
    echo '<p class="text-red-400">Owner profile not found. Please contact support.</p>';
    exit;
}
$owner_id = $owner_result->fetch_assoc()['owner_id'];
$stmt_owner->close();

/*/ MARK ALL MESSAGES AS READ WHEN OWNER VISITS THE MESSAGES PAGE
$sql_mark_read = "UPDATE Messages m 
                 JOIN Cars c ON m.car_id = c.car_id 
                 SET m.is_read = 1 
                 WHERE c.owner_id = ? AND m.receiver_id = ? AND m.is_read = 0";
$stmt_mark_read = $conn->prepare($sql_mark_read);
if ($stmt_mark_read) {
    $stmt_mark_read->bind_param("ii", $owner_id, $user['user_id']);
    $stmt_mark_read->execute();
    $stmt_mark_read->close();
} else {
    error_log("Error preparing mark read query: " . $conn->error);
}
*/
// Get unread count for badge
$unread_count_sql = "SELECT COUNT(*) as unread_count 
                    FROM Messages m
                    JOIN Cars c ON m.car_id = c.car_id
                    WHERE c.owner_id = ? AND m.receiver_id = ? AND m.is_read = 0";
$stmt_unread = $conn->prepare($unread_count_sql);
$stmt_unread->bind_param("ii", $owner_id, $user['user_id']);
$stmt_unread->execute();
$unread_result = $stmt_unread->get_result();
$unread_count = $unread_result->fetch_assoc()['unread_count'] ?? 0;
$stmt_unread->close();

// Get selected conversation from URL
$selected_car_id = $_GET['car_id'] ?? null;
$selected_customer_id = $_GET['customer_id'] ?? null;

// Fetch conversations for the owner - CORRECTED QUERY
$sql_conversations = "
    SELECT 
        c.car_id,
        c.brand,
        c.model,
        c.image,
        u.user_id as customer_id,
        u.name as customer_name,
        latest_msg.message as last_message,
        latest_msg.created_at as last_message_time,
        COALESCE(unread.unread_count, 0) as unread_count
    FROM Cars c
    JOIN (
        -- Get the latest message for each car-customer combination
        SELECT m1.car_id, m1.sender_id, m1.message, m1.created_at
        FROM Messages m1
        INNER JOIN (
            SELECT car_id, sender_id, MAX(created_at) as max_created
            FROM Messages
            GROUP BY car_id, sender_id
        ) m2 ON m1.car_id = m2.car_id AND m1.sender_id = m2.sender_id AND m1.created_at = m2.max_created
    ) latest_msg ON c.car_id = latest_msg.car_id
    JOIN Users u ON latest_msg.sender_id = u.user_id
    LEFT JOIN (
        -- Count unread messages per car-customer combination
        SELECT car_id, sender_id, COUNT(*) as unread_count
        FROM Messages 
        WHERE receiver_id = ? AND is_read = 0
        GROUP BY car_id, sender_id
    ) unread ON c.car_id = unread.car_id AND u.user_id = unread.sender_id
    WHERE c.owner_id = ? 
    AND u.user_id != ?
    ORDER BY latest_msg.created_at DESC";

$stmt_conv = $conn->prepare($sql_conversations);
if ($stmt_conv) {
    $stmt_conv->bind_param("iii", $user['user_id'], $owner_id, $user['user_id']);
    $stmt_conv->execute();
    $conversations_result = $stmt_conv->get_result();
    $conversations = [];
    while ($row = $conversations_result->fetch_assoc()) {
        $conversations[] = $row;
    }
    $stmt_conv->close();
} else {
    error_log("Error preparing conversations query: " . $conn->error);
    $conversations = [];
}

// Fetch messages for selected conversation
$selected_conversation_messages = [];
$selected_car_details = null;
$selected_customer_details = null;

if ($selected_car_id && $selected_customer_id) {
    // Get car details
    $sql_car = "SELECT brand, model, image FROM Cars WHERE car_id = ?";
    $stmt_car = $conn->prepare($sql_car);
    if ($stmt_car) {
        $stmt_car->bind_param("i", $selected_car_id);
        $stmt_car->execute();
        $selected_car_details = $stmt_car->get_result()->fetch_assoc();
        $stmt_car->close();
    }

    // Get customer details
    $sql_customer = "SELECT name FROM Users WHERE user_id = ?";
    $stmt_customer = $conn->prepare($sql_customer);
    if ($stmt_customer) {
        $stmt_customer->bind_param("i", $selected_customer_id);
        $stmt_customer->execute();
        $selected_customer_details = $stmt_customer->get_result()->fetch_assoc();
        $stmt_customer->close();
    }

    // Get messages for this conversation
    $sql_messages = "
        SELECT m.*, u.name as sender_name
        FROM Messages m
        JOIN Users u ON m.sender_id = u.user_id
        WHERE m.car_id = ? AND (
            (m.sender_id = ? AND m.receiver_id = ?) OR 
            (m.sender_id = ? AND m.receiver_id = ?)
        )
        ORDER BY m.created_at ASC";

    $stmt_msgs = $conn->prepare($sql_messages);
    if ($stmt_msgs) {
        $stmt_msgs->bind_param("iiiii", $selected_car_id, $user['user_id'], $selected_customer_id, $selected_customer_id, $user['user_id']);
        $stmt_msgs->execute();
        $messages_result = $stmt_msgs->get_result();
        while ($row = $messages_result->fetch_assoc()) {
            $selected_conversation_messages[] = $row;
        }
        $stmt_msgs->close();

        // Mark messages as read when conversation is opened
        $mark_read_sql = "UPDATE Messages 
                         SET is_read = 1 
                         WHERE car_id = ? AND sender_id = ? AND receiver_id = ? AND is_read = 0";
        $stmt_mark = $conn->prepare($mark_read_sql);
        if ($stmt_mark) {
            $stmt_mark->bind_param("iii", $selected_car_id, $selected_customer_id, $user['user_id']);
            $stmt_mark->execute();
            $stmt_mark->close();
        }
    }
}
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
            const dropdown = document.getElementById('profileDropdown');
            if (dropdown) {
                dropdown.classList.toggle('hidden');
            }
        }

        // Close dropdown when clicking outside
        document.addEventListener('click', function (event) {
            const dropdown = document.getElementById('profileDropdown');
            const button = document.querySelector('button[onclick="toggleProfileDropdown()"]');
            if (dropdown && !dropdown.contains(event.target) && !button.contains(event.target)) {
                dropdown.classList.add('hidden');
            }
        });

        function formatTime(timestamp) {
            try {
                const date = new Date(timestamp);
                if (isNaN(date.getTime())) {
                    return 'Recently';
                }
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
            } catch (e) {
                console.error('Error formatting time:', e);
                return 'Recently';
            }
        }

        function scrollToBottom() {
            const messagesContainer = document.getElementById('messagesContainer');
            if (messagesContainer) {
                messagesContainer.scrollTop = messagesContainer.scrollHeight;
            }
        }

        // Auto-refresh messages every 3 seconds for active conversation
        <?php if ($selected_car_id && $selected_customer_id): ?>
            setInterval(() => {
                getMessages();
            }, 3000);
        <?php endif; ?>

        // Scroll to bottom when page loads
        document.addEventListener('DOMContentLoaded', function () {
            scrollToBottom();
        });

        function sendMessage() {
            const messageInput = document.getElementById('messageInput');
            const message = messageInput?.value.trim();

            if (message && <?php echo $selected_car_id ?: 'null'; ?> && <?php echo $selected_customer_id ?: 'null'; ?>) {
                const formData = new FormData();
                formData.append('car_id', <?php echo $selected_car_id ?: 'null'; ?>);
                formData.append('sender_id', <?php echo $user['user_id']; ?>);
                formData.append('receiver_id', <?php echo $selected_customer_id ?: 'null'; ?>);
                formData.append('message', message);
                formData.append('type', 'text');

                fetch('send_message.php', {
                    method: 'POST',
                    body: formData
                })
                    .then(res => {
                        if (!res.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return res.text();
                    })
                    .then(() => {
                        if (messageInput) {
                            messageInput.value = '';
                        }
                        getMessages();
                    })
                    .catch(error => {
                        console.error('Error sending message:', error);
                        alert('Error sending message. Please try again.');
                    });
            } else {
                if (!message) {
                    alert('Please enter a message');
                }
            }
        }

        function getMessages() {
            const carId = <?php echo $selected_car_id ?: 'null'; ?>;
            const customerId = <?php echo $selected_customer_id ?: 'null'; ?>;

            if (!carId || !customerId) return;

            fetch(`get_messages.php?car_id=${carId}&current_user_id=<?php echo $user['user_id']; ?>&other_user_id=${customerId}&is_owner=true`)
                .then(res => {
                    if (!res.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return res.json();
                })
                .then(messages => {
                    const messagesContainer = document.getElementById('messagesContainer');
                    if (!messagesContainer) return;

                    if (!messages || messages.length === 0) {
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
                            const isSender = msg.sender_id == <?php echo $user['user_id']; ?>;
                            html += `
                                <div class="flex ${isSender ? 'justify-end' : 'justify-start'}">
                                    <div class="max-w-xs lg:max-w-md px-4 py-2 rounded-lg ${isSender ? 'bg-teal-600 text-white' : 'bg-gray-700 text-gray-100'}">
                                        ${!isSender ? `
                                            <p class="text-xs font-semibold text-teal-300 mb-1">
                                                ${msg.sender_name || 'Customer'}
                                            </p>
                                        ` : ''}
                                        <p class="text-sm">${msg.message || ''}</p>
                                        <p class="text-xs opacity-75 mt-1 text-right">
                                            ${formatTime(msg.timestamp || msg.created_at)}
                                        </p>
                                    </div>
                                </div>
                            `;
                        });
                        messagesContainer.innerHTML = html;
                    }
                    scrollToBottom();
                })
                .catch(error => {
                    console.error('Error fetching messages:', error);
                });
        }

        // Handle enter key in message input
        function handleKeyPress(event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                sendMessage();
            }
        }

        // Mark conversation as read when selected
        function markConversationAsRead(carId, customerId) {
            fetch('mark_conversation_read.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `car_id=${carId}&customer_id=${customerId}&owner_id=<?php echo $user['user_id']; ?>`
            }).then(() => {
                // Remove the unread badge after marking as read
                const badge = document.querySelector(`a[href="owner_messages.php?car_id=${carId}&customer_id=${customerId}"] .bg-red-500`);
                if (badge) {
                    badge.remove();
                }
            }).catch(error => console.error('Error marking as read:', error));
        }
    </script>
</head>

<body class="bg-gray-800 font-sans text-gray-100">
    <header class="bg-teal-600 text-gray-100 sticky top-0 z-50">
        <nav class="container mx-auto px-4 py-4 flex justify-between items-center">
            <h1 class="text-2xl font-bold">Rentify</h1>
            <div class="flex items-center space-x-4">
                <div class="flex items-center space-x-4">
                    <a href="owner_dashboard.php" class="p-2 text-gray-100 hover:bg-teal-700 rounded-lg transition"
                        title="Dashboard">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                        </svg>
                    </a>
                    <a href="owner_messages.php"
                        class="relative p-2 text-gray-100 hover:bg-teal-700 rounded-lg transition" title="Messages">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                        </svg>
                        <?php if ($unread_count > 0): ?>
                            <span
                                class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center">
                                <?php echo $unread_count > 9 ? '9+' : $unread_count; ?>
                            </span>
                        <?php endif; ?>
                    </a>

                    <div class="relative">
                        <button onclick="toggleProfileDropdown()"
                            class="flex items-center space-x-2 focus:outline-none">
                            <span class="text-gray-100"><?php echo htmlspecialchars($user['name'] ?? 'User'); ?></span>
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>
                        <div id="profileDropdown"
                            class="hidden absolute right-0 mt-2 w-48 bg-gray-900 border border-gray-700 rounded-lg shadow-lg z-50">
                            <a href="profile.php"
                                class="block px-4 py-2 text-gray-100 hover:bg-teal-600 transition">Account Settings</a>
                            <a href="owner_messages.php"
                                class="block px-4 py-2 text-gray-100 hover:bg-teal-600 transition">Messages</a>
                            <a href="owner_cars.php"
                                class="block px-4 py-2 text-gray-100 hover:bg-teal-600 transition">My Cars</a>
                            <a href="bookings.php"
                                class="block px-4 py-2 text-gray-100 hover:bg-teal-600 transition">Bookings</a>
                            <a href="logout.php" class="block px-4 py-2 text-gray-100 hover:bg-teal-600 transition">Log
                                Out</a>
                        </div>
                    </div>
                </div>
            </div>
        </nav>
    </header>

    <main class="container mx-auto px-4 py-8">
        <div class="max-w-6xl mx-auto">
            <!-- Page Header -->
             <!-- <div class="mb-8">
                <h1 class="text-3xl font-bold mb-2">Messages</h1>
                <p class="text-gray-400">Communicate with customers about bookings and inquiries</p>
                <?php if ($unread_count > 0): ?>
                    <div class="mt-4 p-3 bg-teal-900 border border-teal-700 rounded-lg">
                        <p class="text-teal-300">
                            ✅ All messages have been marked as read
                        </p>
                    </div>
                <?php endif; ?> 
            </div>
            -->

            <!-- Messenger Style Layout -->
            <div class="bg-gray-900 rounded-lg border border-gray-700 overflow-hidden shadow-xl">
                <div class="flex flex-col md:flex-row h-[600px]">
                    <!-- Conversations List -->
                    <div class="md:w-1/3 border-r border-gray-700 flex flex-col">
                        <div class="p-4 border-b border-gray-700 bg-gray-800">
                            <h2 class="text-lg font-semibold">Conversations</h2>
                            <p class="text-sm text-gray-400 mt-1">
                                <?php
                                $unread_conversations_count = 0;
                                foreach ($conversations as $conv) {
                                    if ($conv['unread_count'] > 0) {
                                        $unread_conversations_count++;
                                    }
                                }
                                echo count($conversations) . ' conversation(s)';
                                if ($unread_conversations_count > 0) {
                                    echo ' • ' . $unread_conversations_count . ' with unread messages';
                                }
                                ?>
                            </p>
                        </div>
                        <div class="flex-1 overflow-y-auto">
                            <?php if (empty($conversations)): ?>
                                <div class="text-center py-12">
                                    <div class="text-gray-400 text-6xl mb-4">💬</div>
                                    <h3 class="text-xl font-semibold text-gray-300 mb-2">No conversations yet</h3>
                                    <p class="text-gray-500 mb-6">Messages from customers will appear here.</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($conversations as $conv): ?>
                                    <a href="owner_messages.php?car_id=<?php echo $conv['car_id']; ?>&customer_id=<?php echo $conv['customer_id']; ?>"
                                        class="block p-4 border-b border-gray-700 hover:bg-gray-800 transition <?php echo ($selected_car_id == $conv['car_id'] && $selected_customer_id == $conv['customer_id']) ? 'bg-gray-800' : ''; ?>"
                                        onclick="markConversationAsRead(<?php echo $conv['car_id']; ?>, <?php echo $conv['customer_id']; ?>)">
                                        <div class="flex items-start space-x-3">
                                            <div
                                                class="w-12 h-12 bg-teal-600 rounded-lg flex items-center justify-center flex-shrink-0">
                                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                                                </svg>
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <div class="flex justify-between items-start">
                                                    <h3 class="font-semibold text-gray-100 truncate">
                                                        <?php echo htmlspecialchars($conv['brand'] . ' ' . $conv['model']); ?>
                                                    </h3>
                                                    <?php if ($conv['unread_count'] > 0): ?>
                                                        <span
                                                            class="bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center flex-shrink-0 ml-2">
                                                            <?php echo $conv['unread_count'] > 9 ? '9+' : $conv['unread_count']; ?>
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                                <p class="text-gray-400 text-sm truncate">
                                                    <?php echo htmlspecialchars($conv['customer_name']); ?>
                                                </p>
                                                <?php if ($conv['last_message']): ?>
                                                    <p class="text-gray-500 text-sm truncate mt-1">
                                                        <?php echo htmlspecialchars($conv['last_message']); ?>
                                                    </p>
                                                    <p class="text-gray-600 text-xs mt-1">
                                                        <?php if ($conv['last_message_time']): ?>
                                                            <script>
                                                                document.write(formatTime(new Date("<?php echo $conv['last_message_time']; ?>")));
                                                            </script>
                                                        <?php else: ?>
                                                            No date
                                                        <?php endif; ?>
                                                    </p>
                                                <?php else: ?>
                                                    <p class="text-gray-500 text-sm italic mt-1">No messages yet</p>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </a>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Messages Area -->
                    <div class="md:w-2/3 flex flex-col">
                        <?php if ($selected_car_id && $selected_customer_id && $selected_car_details && $selected_customer_details): ?>
                            <!-- Conversation Header -->
                            <div class="p-4 border-b border-gray-700 bg-gray-800">
                                <div class="flex items-center space-x-3">
                                    <div class="w-10 h-10 bg-teal-600 rounded-lg flex items-center justify-center">
                                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                        </svg>
                                    </div>
                                    <div>
                                        <h3 class="font-semibold text-gray-100">
                                            <?php echo htmlspecialchars($selected_customer_details['name'] ?? 'Customer'); ?>
                                        </h3>
                                        <p class="text-gray-400 text-sm">
                                            <?php echo htmlspecialchars($selected_car_details['brand'] . ' ' . $selected_car_details['model']); ?>
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Messages Container -->
                            <div id="messagesContainer" class="flex-1 overflow-y-auto p-4 space-y-4 bg-gray-900">
                                <?php if (!empty($selected_conversation_messages)): ?>
                                    <?php foreach ($selected_conversation_messages as $msg): ?>
                                        <div
                                            class="flex <?php echo $msg['sender_id'] == $user['user_id'] ? 'justify-end' : 'justify-start'; ?>">
                                            <div
                                                class="max-w-xs lg:max-w-md px-4 py-2 rounded-lg <?php echo $msg['sender_id'] == $user['user_id'] ? 'bg-teal-600 text-white' : 'bg-gray-700 text-gray-100'; ?>">
                                                <?php if ($msg['sender_id'] != $user['user_id']): ?>
                                                    <p class="text-xs font-semibold text-teal-300 mb-1">
                                                        <?php echo htmlspecialchars($msg['sender_name']); ?>
                                                    </p>
                                                <?php endif; ?>
                                                <p class="text-sm"><?php echo htmlspecialchars($msg['message']); ?></p>
                                                <p class="text-xs opacity-75 mt-1 text-right">
                                                    <script>
                                                        document.write(formatTime(new Date("<?php echo $msg['created_at']; ?>")));
                                                    </script>
                                                </p>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="text-center py-12">
                                        <div class="text-gray-400 text-6xl mb-4">💭</div>
                                        <h3 class="text-xl font-semibold text-gray-300 mb-2">No messages yet</h3>
                                        <p class="text-gray-500">Start the conversation by sending a message below.</p>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Message Input -->
                            <div class="p-4 border-t border-gray-700 bg-gray-800">
                                <div class="flex space-x-2">
                                    <input type="text" id="messageInput" placeholder="Type your message..."
                                        class="flex-1 p-3 border border-gray-700 bg-gray-900 text-gray-100 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600 focus:border-transparent"
                                        onkeypress="handleKeyPress(event)" required>
                                    <button onclick="sendMessage()"
                                        class="bg-teal-600 hover:bg-teal-700 text-gray-100 px-6 py-3 rounded-lg transition focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2 focus:ring-offset-gray-800">
                                        Send
                                    </button>
                                </div>
                            </div>
                        <?php else: ?>
                            <!-- No Conversation Selected -->
                            <div class="flex-1 flex items-center justify-center bg-gray-900">
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