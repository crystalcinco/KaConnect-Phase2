<?php
declare(strict_types=1);

class CommentModel {
    public function __construct(private PDO $db) {}

    public function byPost(int $postId): array {
        $s = $this->db->prepare('SELECT c.*, u.username, u.full_name, u.profile_image 
            FROM comments c 
            JOIN users u ON u.id = c.user_id 
            WHERE c.post_id = ? 
            ORDER BY c.created_at ASC, c.id ASC');
        $s->execute([$postId]);
        return $s->fetchAll();
    }

    public function findById(int $id): ?array {
        $s = $this->db->prepare('SELECT c.*, u.username, u.full_name, u.profile_image 
            FROM comments c 
            JOIN users u ON u.id = c.user_id 
            WHERE c.id = ? LIMIT 1');
        $s->execute([$id]);
        return $s->fetch() ?: null;
    }

    public function countByPost(int $postId): int {
        $s = $this->db->prepare('SELECT COUNT(*) FROM comments WHERE post_id = ?');
        $s->execute([$postId]);
        return (int)$s->fetchColumn();
    }

    public function create(int $postId, int $userId, string $content): int {
        $s = $this->db->prepare('INSERT INTO comments (post_id, user_id, content) VALUES (?, ?, ?)');
        $s->execute([$postId, $userId, $content]);
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, int $userId, string $content): bool {
        $s = $this->db->prepare('UPDATE comments SET content = ? WHERE id = ? AND user_id = ?');
        $s->execute([$content, $id, $userId]);
        if ($s->rowCount() === 1) return true;
        $check = $this->db->prepare('SELECT 1 FROM comments WHERE id = ? AND user_id = ? AND content = ? LIMIT 1');
        $check->execute([$id, $userId, $content]);
        return (bool)$check->fetchColumn();
    }

    public function delete(int $id, int $userId): bool {
        $s = $this->db->prepare('DELETE FROM comments WHERE id = ? AND user_id = ?');
        $s->execute([$id, $userId]);
        return $s->rowCount() === 1;
    }
}
