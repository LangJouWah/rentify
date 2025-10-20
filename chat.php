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
// To this:
$stmt = $conn->prepare("SELECT c.owner_id, c.brand, c.model, u.user_id as owner_user_id 
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat - Rentify</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="css/chat.css">
    <script src="https://cdn.jsdelivr.net/npm/emoji-picker-element@1/index.js" type="module"></script>
    <style>
        #chat-box {
            border: 1px solid #4a5568;
        }
        .message {
            margin-bottom: 1rem;
            padding: 0.75rem;
            border-radius: 0.5rem;
        }
        .sent {
            background-color: #2b6cb0;
            align-self: flex-end;
        }
        .received {
            background-color: #2d3748;
            align-self: flex-start;
        }
        .file-link {
            color: #63b3ed;
        }
        #emoji-picker {
            position: absolute;
            bottom: 60px;
        }
    </style>
</head>
<body class="bg-gray-800 font-sans text-gray-100">
    <header class="bg-teal-600 text-gray-100 sticky top-0 z-50">
        <nav class="container mx-auto px-4 py-4 flex justify-between items-center">
            <h1 class="text-2xl font-bold">Rentify</h1>
            <div class="space-x-4">
                <?php if ($is_owner): ?>
                    <a href="owner_dashboard.php" class="hover:underline">Dashboard</a>
                    <a href="owner_cars.php" class="hover:underline">My Cars</a>
                <?php else: ?>
                    <a href="customer_dashboard.php" class="hover:underline">Dashboard</a>
                <?php endif; ?>
                <a href="logout.php" class="hover:underline">Log Out</a>
            </div>
        </nav>
    </header>

    <main class="container mx-auto px-4 py-8">
        <div class="bg-gray-900 p-6 rounded-lg shadow border border-gray-700">
            <h3 class="text-xl font-semibold mb-4">Chat about <?php echo $car_name; ?> with <?php echo htmlspecialchars($other_user_name); ?></h3>
            <div id="chat-box" class="h-96 overflow-y-auto mb-4 p-4 bg-gray-800 rounded-lg"></div>
            <div id="typing-indicator" class="text-gray-400 mb-2"></div>
            <form id="chat-form" class="flex space-x-2">
                <input type="text" id="message" placeholder="Type a message..." class="flex-grow p-3 border border-gray-700 bg-gray-900 text-gray-100 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600">
                <button type="button" id="emoji-btn">😀</button>
                <input type="file" id="file-upload" accept="image/*,application/pdf,.doc,.docx" class="hidden">
                <button type="button" id="file-btn">📎</button>
                <button type="submit" class="bg-teal-600 text-gray-100 p-3 rounded-lg hover:bg-teal-700">Send</button>
            </form>
            <emoji-picker id="emoji-picker" class="hidden"></emoji-picker>
        </div>
    </main>

    <footer class="bg-gray-900 text-gray-100 text-center py-4">
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

        // Emoji picker setup
        const picker = document.querySelector('emoji-picker');
        document.getElementById('emoji-btn').addEventListener('click', () => {
            picker.classList.toggle('hidden');
        });
        picker.addEventListener('emoji-click', event => {
            document.getElementById('message').value += event.detail.unicode;
            picker.classList.add('hidden');
        });

        // File upload button
        document.getElementById('file-btn').addEventListener('click', () => {
            document.getElementById('file-upload').click();
        });

        // Send message/form submit
        document.getElementById('chat-form').addEventListener('submit', (e) => {
            e.preventDefault();
            const message = document.getElementById('message').value.trim();
            if (message) {
                sendMessage(message, 'text');
            }
            document.getElementById('message').value = '';
        });

        // File upload change
        document.getElementById('file-upload').addEventListener('change', (e) => {
            const file = e.target.files[0];
            if (file) {
                uploadFile(file);
            }
        });

        // Typing indicator
        let typingTimeout;
        document.getElementById('message').addEventListener('input', () => {
            setTyping(true);
            clearTimeout(typingTimeout);
            typingTimeout = setTimeout(() => setTyping(false), 2000);
        });

        // Poll for messages and typing every 3 seconds
        setInterval(getMessages, 3000);
        setInterval(getTyping, 3000);
        getMessages(); // Initial load

        function sendMessage(content, type) {
            const formData = new FormData();
            formData.append('car_id', carId);
            formData.append('sender_id', currentUserId);
            formData.append('receiver_id', otherUserId);
            formData.append('message', content);
            formData.append('type', type);

            fetch('send_message.php', { method: 'POST', body: formData })
                .then(res => res.text())
                .then(() => getMessages());
        }

        function uploadFile(file) {
            const formData = new FormData();
            formData.append('file', file);
            formData.append('car_id', carId);
            formData.append('sender_id', currentUserId);
            formData.append('receiver_id', otherUserId);

            fetch('upload_file.php', { method: 'POST', body: formData })
                .then(res => res.text())
                .then(path => {
                    if (path) sendMessage(path, 'file');
                });
        }

        function getMessages() {
    fetch(`get_messages.php?car_id=${carId}&current_user_id=${currentUserId}&other_user_id=${otherUserId}&is_owner=${isOwner}`)
        .then(res => res.json())
        .then(messages => {
            const chatBox = document.getElementById('chat-box');
            chatBox.innerHTML = '';
            messages.forEach(msg => {
                const messageContainer = document.createElement('div');
                messageContainer.classList.add('mb-3', msg.sender_id == currentUserId ? 'text-right' : 'text-left');
                
                const nameDiv = document.createElement('div');
                nameDiv.classList.add('text-xs', 'text-gray-400', 'mb-1', 'px-2');
                nameDiv.textContent = msg.sender_name;
                
                const messageDiv = document.createElement('div');
                messageDiv.classList.add('message', 'inline-block', 'max-w-xs', 'lg:max-w-md', msg.sender_id == currentUserId ? 'sent' : 'received');
                
                if (msg.type === 'text') {
                    messageDiv.textContent = msg.message;
                } else {
                    messageDiv.innerHTML = `<a href="${msg.file_path}" target="_blank" class="file-link">📎 View File</a>`;
                }
                
                messageContainer.appendChild(nameDiv);
                messageContainer.appendChild(messageDiv);
                chatBox.appendChild(messageContainer);
            });
            chatBox.scrollTop = chatBox.scrollHeight;
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
    </script>
    <script src="js/chat.js"></script>
</body>
</html>
