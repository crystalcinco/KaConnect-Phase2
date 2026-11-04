<?php
declare(strict_types=1);

class NotificationController {
    public function __construct(
        private NotificationModel $notifications,
        private MessageModel $messages
    ) {}

    public function index(): void {
        require_auth();
        $myId = (int)current_user_id();
        $items = $this->notifications->forUser($myId, 50);
        $title = 'Notifications';
        require __DIR__ . '/../views/notifications/index.php';
    }

    public function poll(): void {
        require_auth();
        $myId = (int)current_user_id();

        $unreadCount = $this->notifications->unreadCount($myId);
        $unreadMessages = $this->messages->totalUnreadCount($myId);
        $items = $this->notifications->forUser($myId, 8);

        $formatted = array_map(function($n) {
            $actorName = $n['actor_name'] ?: 'Someone';
            $link = 'index.php?page=feed';
            if ($n['type'] === 'like' || $n['type'] === 'comment') {
                $link = 'index.php?page=feed#post-' . (int)$n['entity_id'];
            } elseif ($n['type'] === 'follow') {
                $link = 'index.php?page=profile&id=' . (int)$n['actor_id'];
            } elseif ($n['type'] === 'message') {
                $link = 'index.php?page=messages&user_id=' . (int)$n['actor_id'];
            }

            return [
                'id' => (int)$n['id'],
                'type' => $n['type'],
                'message' => $n['message'],
                'actor_name' => $actorName,
                'actor_username' => $n['actor_username'],
                'actor_image' => $n['actor_image'],
                'is_read' => (bool)$n['is_read'],
                'time_ago' => time_ago($n['created_at']),
                'link' => $link
            ];
        }, $items);

        json_response([
            'success' => true,
            'unread_count' => $unreadCount,
            'unread_messages' => $unreadMessages,
            'notifications' => $formatted
        ]);
    }

    public function markRead(): void {
        require_auth();
        require_post();
        verify_csrf();

        $myId = (int)current_user_id();
        $id = (int)($_POST['id'] ?? 0);

        if ($id > 0) {
            $this->notifications->markAsRead($id, $myId);
        } else {
            $this->notifications->markAllAsRead($myId);
        }

        if (is_ajax()) {
            json_response([
                'success' => true,
                'unread_count' => $this->notifications->unreadCount($myId)
            ]);
        }

        flash('success', 'Notifications marked as read.');
        redirect('index.php?page=notifications');
    }
}
