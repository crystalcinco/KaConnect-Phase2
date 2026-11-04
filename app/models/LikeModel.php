<?php
declare(strict_types=1);

class LikeModel {
    public function __construct(private PDO $db) {}

    public function toggle(int $postId, int $userId): bool {
        $s = $this->db->prepare('SELECT id FROM likes WHERE post_id = ? AND user_id = ? LIMIT 1');
        $s->execute([$postId, $userId]);
        $id = $s->fetchColumn();
        if ($id) {
            $d = $this->db->prepare('DELETE FROM likes WHERE id = ? AND user_id = ?');
            $d->execute([(int)$id, $userId]);
            return false; // unliked
        }
        $i = $this->db->prepare('INSERT IGNORE INTO likes (post_id, user_id) VALUES (?, ?)');
        $i->execute([$postId, $userId]);
        return true; // liked
    }

    public function likedByUser(int $postId, int $userId): bool {
        $s = $this->db->prepare('SELECT 1 FROM likes WHERE post_id = ? AND user_id = ? LIMIT 1');
        $s->execute([$postId, $userId]);
        return (bool)$s->fetchColumn();
    }

    public function count(int $postId): int {
        $s = $this->db->prepare('SELECT COUNT(*) FROM likes WHERE post_id = ?');
        $s->execute([$postId]);
        return (int)$s->fetchColumn();
    }
}
