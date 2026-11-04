<?php
declare(strict_types=1);

class NotificationModel {
    public function __construct(private PDO $db) {}

    public function create(int $userId, int $actorId, string $type, ?int $entityId, string $message): int {
        // Do not notify self
        if ($userId === $actorId) return 0;

        $s = $this->db->prepare('INSERT INTO notifications (user_id, actor_id, type, entity_id, message) VALUES (?, ?, ?, ?, ?)');
        $s->execute([$userId, $actorId, $type, $entityId, $message]);
        return (int)$this->db->lastInsertId();
    }

    public function forUser(int $userId, int $limit = 30): array {
        $s = $this->db->prepare('SELECT n.*, u.username AS actor_username, u.full_name AS actor_name, u.profile_image AS actor_image 
            FROM notifications n 
            JOIN users u ON u.id = n.actor_id 
            WHERE n.user_id = ? 
            ORDER BY n.created_at DESC, n.id DESC 
            LIMIT ' . (int)$limit);
        $s->execute([$userId]);
        return $s->fetchAll();
    }

    public function unreadCount(int $userId): int {
        $s = $this->db->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0');
        $s->execute([$userId]);
        return (int)$s->fetchColumn();
    }

    public function markAsRead(int $id, int $userId): bool {
        $s = $this->db->prepare('UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?');
        return $s->execute([$id, $userId]);
    }

    public function markAllAsRead(int $userId): bool {
        $s = $this->db->prepare('UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0');
        return $s->execute([$userId]);
    }
}
