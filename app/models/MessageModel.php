<?php
declare(strict_types=1);

class MessageModel {
    public function __construct(private PDO $db) {}

    public function send(int $senderId, int $receiverId, string $message): int {
        if ($senderId === $receiverId || trim($message) === '') return 0;
        $s = $this->db->prepare('INSERT INTO messages (sender_id, receiver_id, message) VALUES (?, ?, ?)');
        $s->execute([$senderId, $receiverId, trim($message)]);
        return (int)$this->db->lastInsertId();
    }

    public function getConversations(int $userId): array {
        // Query to find distinct chat partners with their last message and unread count
        $sql = 'SELECT 
                    u.id, 
                    u.username, 
                    u.full_name, 
                    u.profile_image,
                    last_msg.message AS last_message,
                    last_msg.created_at AS last_message_at,
                    last_msg.sender_id AS last_sender_id,
                    (SELECT COUNT(*) FROM messages m_unread 
                     WHERE m_unread.sender_id = u.id AND m_unread.receiver_id = ? AND m_unread.is_read = 0) AS unread_count
                FROM users u
                JOIN (
                    SELECT 
                        CASE WHEN sender_id = ? THEN receiver_id ELSE sender_id END AS partner_id,
                        MAX(id) AS max_id
                    FROM messages
                    WHERE sender_id = ? OR receiver_id = ?
                    GROUP BY partner_id
                ) m_group ON m_group.partner_id = u.id
                JOIN messages last_msg ON last_msg.id = m_group.max_id
                ORDER BY last_msg.id DESC';

        $s = $this->db->prepare($sql);
        $s->execute([$userId, $userId, $userId, $userId]);
        return $s->fetchAll();
    }

    public function getMessagesBetween(int $userId1, int $userId2, int $afterId = 0): array {
        if ($afterId > 0) {
            $s = $this->db->prepare('SELECT m.*, u.username AS sender_username, u.full_name AS sender_name, u.profile_image AS sender_image
                FROM messages m
                JOIN users u ON u.id = m.sender_id
                WHERE ((m.sender_id = ? AND m.receiver_id = ?) OR (m.sender_id = ? AND m.receiver_id = ?))
                  AND m.id > ?
                ORDER BY m.created_at ASC, m.id ASC');
            $s->execute([$userId1, $userId2, $userId2, $userId1, $afterId]);
            return $s->fetchAll();
        }

        $s = $this->db->prepare('SELECT m.*, u.username AS sender_username, u.full_name AS sender_name, u.profile_image AS sender_image
            FROM messages m
            JOIN users u ON u.id = m.sender_id
            WHERE (m.sender_id = ? AND m.receiver_id = ?) OR (m.sender_id = ? AND m.receiver_id = ?)
            ORDER BY m.created_at ASC, m.id ASC');
        $s->execute([$userId1, $userId2, $userId2, $userId1]);
        return $s->fetchAll();
    }

    public function markAsRead(int $senderId, int $receiverId): void {
        $s = $this->db->prepare('UPDATE messages SET is_read = 1 WHERE sender_id = ? AND receiver_id = ? AND is_read = 0');
        $s->execute([$senderId, $receiverId]);
    }

    public function totalUnreadCount(int $userId): int {
        $s = $this->db->prepare('SELECT COUNT(*) FROM messages WHERE receiver_id = ? AND is_read = 0');
        $s->execute([$userId]);
        return (int)$s->fetchColumn();
    }
}
