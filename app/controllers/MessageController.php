<?php
declare(strict_types=1);

class MessageController {
    public function __construct(
        private MessageModel $messages,
        private UserModel $users,
        private NotificationModel $notifications
    ) {}

    public function index(): void {
        require_auth();
        $myId = (int)current_user_id();
        $conversations = $this->messages->getConversations($myId);

        $selectedUserId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
        if ($selectedUserId === 0 && !empty($conversations)) {
            $selectedUserId = (int)$conversations[0]['id'];
        }

        $selectedUser = null;
        $chatMessages = [];
        if ($selectedUserId > 0 && $selectedUserId !== $myId) {
            $selectedUser = $this->users->findById($selectedUserId);
            if ($selectedUser) {
                $this->messages->markAsRead($selectedUserId, $myId);
                $chatMessages = $this->messages->getMessagesBetween($myId, $selectedUserId);
            }
        }

        // Available users for "New Chat" search or quick start
        $allContacts = $this->users->allUsersExcept($myId, 30);

        $title = 'Direct Messages';
        require __DIR__ . '/../views/messages/index.php';
    }

    public function send(): void {
        require_auth();
        require_post();
        verify_csrf();

        $myId = (int)current_user_id();
        $receiverId = (int)($_POST['receiver_id'] ?? 0);
        $messageText = trim((string)($_POST['message'] ?? ''));

        if ($receiverId < 1 || $receiverId === $myId || $messageText === '' || text_length($messageText) > 2000) {
            $err = 'Message cannot be empty and must be 2,000 characters or fewer.';
            if (is_ajax()) json_response(['success' => false, 'error' => $err], 400);
            flash('error', $err);
            redirect('index.php?page=messages' . ($receiverId > 0 ? '&user_id=' . $receiverId : ''));
        }

        $recipient = $this->users->findById($receiverId);
        if (!$recipient) {
            if (is_ajax()) json_response(['success' => false, 'error' => 'Recipient not found.'], 404);
            flash('error', 'Recipient not found.');
            redirect('index.php?page=messages');
        }

        $msgId = $this->messages->send($myId, $receiverId, $messageText);
        $me = auth_user();

        // Send notification to recipient
        $preview = mb_strimwidth($messageText, 0, 45, '...');
        $this->notifications->create(
            $receiverId,
            $myId,
            'message',
            $msgId,
            $me['full_name'] . ' sent you a message: "' . $preview . '"'
        );

        if (is_ajax()) {
            json_response([
                'success' => true,
                'message_id' => $msgId,
                'sender_id' => $myId,
                'receiver_id' => $receiverId,
                'message' => $messageText,
                'created_at' => date('M j, Y · g:i A'),
                'time_ago' => 'Just now'
            ]);
        }

        redirect('index.php?page=messages&user_id=' . $receiverId);
    }

    public function poll(): void {
        require_auth();
        $myId = (int)current_user_id();
        $otherUserId = (int)($_GET['user_id'] ?? 0);
        $afterId = (int)($_GET['after_id'] ?? 0);

        if ($otherUserId < 1 || $otherUserId === $myId) {
            json_response(['success' => false, 'error' => 'Invalid chat user.'], 400);
        }

        $newMessages = $this->messages->getMessagesBetween($myId, $otherUserId, $afterId);

        // If any of the new messages were sent to me, mark them as read
        if (!empty($newMessages)) {
            $this->messages->markAsRead($otherUserId, $myId);
        }

        $formatted = array_map(function($m) use ($myId) {
            return [
                'id' => (int)$m['id'],
                'sender_id' => (int)$m['sender_id'],
                'receiver_id' => (int)$m['receiver_id'],
                'message' => $m['message'],
                'is_me' => ((int)$m['sender_id'] === $myId),
                'created_at' => friendly_date($m['created_at']),
                'time_ago' => time_ago($m['created_at'])
            ];
        }, $newMessages);

        json_response([
            'success' => true,
            'messages' => $formatted
        ]);
    }
}
