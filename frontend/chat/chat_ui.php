<?php
session_start();
require_once '../../backend/config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$request_id = $_GET['request_id'] ?? null;
$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];

if (!$request_id) {
    echo "Invalid Request.";
    exit();
}

// Ensure the chat only exists if Request is accepted and user is assigned
$stmt = $pdo->prepare("SELECT driver_id, mechanic_id, status FROM requests WHERE id = ?");
$stmt->execute([$request_id]);
$req = $stmt->fetch();

if (!$req) {
    echo "Request not found.";
    exit();
}

if ($req['status'] === 'Pending' || !$req['mechanic_id']) {
    echo "Chat is unavailable. Waiting for a mechanic to be assigned.";
    exit();
}

$is_authorized = false;
if ($user_role === 'user' && $req['driver_id'] == $user_id) $is_authorized = true;
if ($user_role === 'mechanic') {
    $stmtM = $pdo->prepare("SELECT id FROM mechanics WHERE user_id = ?");
    $stmtM->execute([$user_id]);
    $m = $stmtM->fetch();
    if ($m && $req['mechanic_id'] == $m['id']) $is_authorized = true;
}

if (!$is_authorized) {
    echo "Unauthorized to view this chat.";
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Service Chat - Roadside Assistance</title>
    <style>
        body { font-family: 'Inter', sans-serif; background: #e2e8f0; margin: 0; padding: 0; display:flex; justify-content:center; align-items:center; height:100vh; }
        .chat-container { width: 100%; max-width: 600px; background: white; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); display:flex; flex-direction:column; height: 85vh; overflow:hidden;}
        
        .chat-header { background: #1e293b; color: white; padding: 15px 20px; display: flex; justify-content: space-between; align-items: center; }
        .chat-header h3 { margin: 0; font-size: 1.1rem; }
        .back-btn { color: #cbd5e1; text-decoration: none; font-size: 0.9rem; }
        .back-btn:hover { color: white; }
        
        .chat-box { flex: 1; padding: 20px; overflow-y: auto; display:flex; flex-direction:column; gap: 15px; background: #f8fafc;}
        
        .message { padding: 10px 15px; border-radius: 12px; max-width: 75%; line-height: 1.5; font-size: 0.95rem; position: relative; }
        
        .sent { align-self: flex-end; background-color: #2563eb; color: white; border-bottom-right-radius: 0; }
        .received { align-self: flex-start; background-color: #e5e7eb; color: black; border-bottom-left-radius: 0; }
        
        .msg-sender { font-size: 0.75rem; font-weight: bold; margin-bottom: 4px; display:block; }
        .sent .msg-sender { color: #bfdbfe; }
        .received .msg-sender { color: #475569; }
        
        .msg-time { font-size: 0.7rem; margin-top: 5px; opacity: 0.8; display:block; text-align:right;}
        
        .chat-input-area { padding: 15px; border-top: 1px solid #e2e8f0; display:flex; gap: 10px; background:white; align-items: center;}
        .chat-input-area input { flex:1; padding: 12px 15px; border: 1px solid #cbd5e1; border-radius: 20px; outline:none; font-size: 0.95rem;}
        .chat-input-area input:focus { border-color: #3b82f6; }
        .chat-input-area button { background: #3b82f6; color: white; border: none; padding: 12px 20px; border-radius: 20px; cursor: pointer; font-weight:bold; transition: 0.2s;}
        .chat-input-area button:hover { background: #2563eb; }
    </style>
</head>
<body>

    <div class="chat-container">
        <div class="chat-header">
            <h3>Order #<?php echo htmlspecialchars($request_id); ?> Chat</h3>
            <a href="index.php" class="back-btn">Go Back</a>
        </div>
        
        <div class="chat-box" id="chat-box">
            <!-- Messages fetched via AJAX will be inserted here -->
        </div>

        <div class="chat-input-area">
            <input type="text" id="message" placeholder="Type your message..." autocomplete="off">
            <button onclick="sendMessage()" id="sendBtn">Send</button>
        </div>
    </div>

    <script>
        const reqId = <?php echo json_encode($request_id); ?>;
        const chatBox = document.getElementById('chat-box');
        const messageInput = document.getElementById('message');
        const sendBtn = document.getElementById('sendBtn');
        let lastMsgCount = 0;

        function fetchMessages() {
            fetch(`../../backend/chat/fetch_messages.php?request_id=${reqId}`)
            .then(res => res.json())
            .then(data => {
                if(data.status === 'success') {
                    if(data.messages.length > lastMsgCount) {
                        renderMessages(data.messages);
                        lastMsgCount = data.messages.length;
                        chatBox.scrollTop = chatBox.scrollHeight;
                    }
                }
            })
            .catch(err => console.error("Error fetching messages:", err));
        }

        function renderMessages(messages) {
            chatBox.innerHTML = '';
            messages.forEach(msg => {
                const div = document.createElement('div');
                div.className = 'message ' + (msg.is_mine ? 'sent' : 'received');
                
                div.innerHTML = `
                    <span class="msg-sender">${msg.is_mine ? 'You' : msg.sender}</span>
                    ${msg.message}
                    <span class="msg-time">${msg.time}</span>
                `;
                chatBox.appendChild(div);
            });
        }

        function sendMessage() {
            const msg = messageInput.value.trim();
            if(!msg) return;

            // disable input during send
            sendBtn.disabled = true;

            fetch("../../backend/chat/send_message.php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: new URLSearchParams({ request_id: reqId, message: msg })
            })
            .then(res => res.json())
            .then(data => {
                if(data.status === 'success') {
                    messageInput.value = '';
                    fetchMessages(); // instantly show it
                } else {
                    alert("Error: " + data.message);
                }
            })
            .catch(err => console.error("Error sending message:", err))
            .finally(() => {
                sendBtn.disabled = false;
                messageInput.focus();
            });
        }

        // Send on Enter key
        messageInput.addEventListener('keypress', function (e) {
            if (e.key === 'Enter') {
                sendMessage();
            }
        });

        // Initial fetch
        fetchMessages();
        
        // Polling every 2 seconds matching the prompt req
        setInterval(fetchMessages, 2000);
    </script>
</body>
</html>
