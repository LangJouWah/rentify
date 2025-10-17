<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat - Rentify</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="css/chat.css">
    <script src="https://cdn.jsdelivr.net/npm/emoji-picker-element@1/index.js" type="module"></script> <!-- Emoji picker -->
</head>
<style>
    /* Chat-specific styles */
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
<body class="bg-gray-800 font-sans text-gray-100">
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

    // Fetch owner_id from car
    $stmt = $conn->prepare("SELECT owner_id FROM Cars WHERE car_id = ?");
    $stmt->bind_param('i', $car_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $car = $result->fetch_assoc();
    $owner_id = $car['owner_id'];
    $stmt->close();

    // Determine sender/receiver
    if ($is_owner) {
        // Owner chatting with customer - but for simplicity, assume query param has other_user_id
        $other_user_id = $_GET['customer_id'] ?? null;  // Adjust if needed
    } else {
        $other_user_id = $owner_id;
    }
    $current_user_id = $user['user_id'];

    // Get or create conversation
    $stmt = $conn->prepare("SELECT conversation_id FROM Conversations WHERE customer_id = ? AND owner_id = ? AND car_id = ?");
    $customer_id = $is_owner ? $other_user_id : $current_user_id;
    $owner_id_for_convo = $is_owner ? $current_user_id : $other_user_id;
    $stmt->bind_param('iii', $customer_id, $owner_id_for_convo, $car_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows == 0) {
        $insert_stmt = $conn->prepare("INSERT INTO Conversations (customer_id, owner_id, car_id) VALUES (?, ?, ?)");
        $insert_stmt->bind_param('iii', $customer_id, $owner_id_for_convo, $car_id);
        $insert_stmt->execute();
        $conversation_id = $insert_stmt->insert_id;
        $insert_stmt->close();
    } else {
        $convo = $result->fetch_assoc();
        $conversation_id = $convo['conversation_id'];
    }
    $stmt->close();
    ?>

    <header class="bg-teal-600 text-gray-100 sticky top-0 z-50">
        <!-- Similar to dashboard nav -->
    </header>

    <main class="container mx-auto px-4 py-8">
        <div class="bg-gray-900 p-6 rounded-lg shadow border border-gray-700">
            <h3 class="text-xl font-semibold mb-4">Chat with <?php echo $is_owner ? 'Customer' : 'Owner'; ?></h3>
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

    <script>
        const conversationId = <?php echo $conversation_id; ?>;
        const currentUserId = <?php echo $current_user_id; ?>;
        const otherUserId = <?php echo $other_user_id; ?>;

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
getMessages();  // Initial load

function sendMessage(content, type) {
    const formData = new FormData();
    formData.append('conversation_id', conversationId);
    formData.append('sender_id', currentUserId);
    formData.append('receiver_id', otherUserId);
    formData.append('message', content);
    formData.append('type', type);

    fetch('ajax/send_message.php', { method: 'POST', body: formData })
        .then(res => res.text())
        .then(() => getMessages());
}

function uploadFile(file) {
    const formData = new FormData();
    formData.append('file', file);
    formData.append('conversation_id', conversationId);
    formData.append('sender_id', currentUserId);
    formData.append('receiver_id', otherUserId);

    fetch('ajax/upload_file.php', { method: 'POST', body: formData })
        .then(res => res.text())
        .then(path => {
            if (path) sendMessage(path, 'file');
        });
}

function getMessages() {
    fetch(`ajax/get_messages.php?conversation_id=${conversationId}&user_id=${currentUserId}`)
        .then(res => res.json())
        .then(messages => {
            const chatBox = document.getElementById('chat-box');
            chatBox.innerHTML = '';
            messages.forEach(msg => {
                const div = document.createElement('div');
                div.classList.add('message', msg.sender_id == currentUserId ? 'sent' : 'received');
                if (msg.type === 'text') {
                    div.textContent = msg.message;
                } else {
                    div.innerHTML = `<a href="${msg.file_path}" target="_blank" class="file-link">View File</a>`;
                }
                chatBox.appendChild(div);
            });
            chatBox.scrollTop = chatBox.scrollHeight;
        });
}

function setTyping(isTyping) {
    fetch(`ajax/set_typing.php?conversation_id=${conversationId}&user_id=${currentUserId}&is_typing=${isTyping ? 1 : 0}`);
}

function getTyping() {
    fetch(`ajax/get_typing.php?conversation_id=${conversationId}&user_id=${otherUserId}`)
        .then(res => res.json())
        .then(data => {
            document.getElementById('typing-indicator').textContent = data.is_typing ? 'Typing...' : '';
        });
}
    </script>
    <script src="js/chat.js"></script>
</body>
</html>