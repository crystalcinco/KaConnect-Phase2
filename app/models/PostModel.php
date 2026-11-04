<?php
declare(strict_types=1);

class PostModel {
    public function __construct(private PDO $db) {}

    public function create(int $userId, string $content, ?string $image): int {
        $s = $this->db->prepare('INSERT INTO posts (user_id, content, image) VALUES (?, ?, ?)');
        $s->execute([$userId, $content, $image]);
        return (int)$this->db->lastInsertId();
    }

    public function all(?string $search = null, ?int $followingOfUserId = null): array {
        $sql = 'SELECT p.*, u.username, u.full_name, u.profile_image, 
                (SELECT COUNT(*) FROM likes l WHERE l.post_id = p.id) AS like_count 
                FROM posts p 
                JOIN users u ON u.id = p.user_id';
        $params = [];
        $where = [];

        if ($search !== null && $search !== '') {
            $like = '%' . $search . '%';
            $where[] = '(p.content LIKE ? OR u.full_name LIKE ? OR u.username LIKE ?)';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        if ($followingOfUserId !== null && $followingOfUserId > 0) {
            $where[] = '(p.user_id IN (SELECT following_id FROM follows WHERE follower_id = ?) OR p.user_id = ?)';
            $params[] = $followingOfUserId;
            $params[] = $followingOfUserId;
        }

        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $sql .= ' ORDER BY p.created_at DESC, p.id DESC';

        if ($params) {
            $s = $this->db->prepare($sql);
            $s->execute($params);
            return $s->fetchAll();
        }

        return $this->db->query($sql)->fetchAll();
    }

    public function find(int $id): ?array {
        $s = $this->db->prepare('SELECT * FROM posts WHERE id = ? LIMIT 1');
        $s->execute([$id]);
        return $s->fetch() ?: null;
    }

    public function findWithAuthor(int $id): ?array {
        $s = $this->db->prepare('SELECT p.*, u.username, u.full_name, u.profile_image, 
            (SELECT COUNT(*) FROM likes l WHERE l.post_id = p.id) AS like_count 
            FROM posts p 
            JOIN users u ON u.id = p.user_id 
            WHERE p.id = ? LIMIT 1');
        $s->execute([$id]);
        return $s->fetch() ?: null;
    }

    public function update(int $id, int $userId, string $content, ?string $image = null, bool $removeImage = false): bool {
        if ($image !== null) {
            $s = $this->db->prepare('UPDATE posts SET content = ?, image = ? WHERE id = ? AND user_id = ?');
            $params = [$content, $image, $id, $userId];
        } elseif ($removeImage) {
            $s = $this->db->prepare('UPDATE posts SET content = ?, image = NULL WHERE id = ? AND user_id = ?');
            $params = [$content, $id, $userId];
        } else {
            $s = $this->db->prepare('UPDATE posts SET content = ? WHERE id = ? AND user_id = ?');
            $params = [$content, $id, $userId];
        }
        $s->execute($params);
        return true;
    }

    public function delete(int $id, int $userId): bool {
        $s = $this->db->prepare('DELETE FROM posts WHERE id = ? AND user_id = ?');
        return $s->execute([$id, $userId]);
    }

    public function userPosts(int $userId): array {
        $s = $this->db->prepare('SELECT p.*, u.username, u.full_name, u.profile_image,
            (SELECT COUNT(*) FROM likes l WHERE l.post_id = p.id) AS like_count 
            FROM posts p 
            JOIN users u ON u.id = p.user_id 
            WHERE p.user_id = ? 
            ORDER BY p.created_at DESC, p.id DESC');
        $s->execute([$userId]);
        return $s->fetchAll();
    }
}
