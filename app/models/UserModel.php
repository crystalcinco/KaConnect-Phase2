<?php
declare(strict_types=1);

class UserModel {
    public function __construct(private PDO $db) {}

    public function create(string $username, string $passwordHash, string $fullName, string $bio = '', ?string $profileImage = null, ?string $coverImage = null): int {
        $s = $this->db->prepare('INSERT INTO users (username, password, full_name, bio, profile_image, cover_image) VALUES (?, ?, ?, ?, ?, ?)');
        $s->execute([$username, $passwordHash, $fullName, $bio, $profileImage, $coverImage]);
        return (int)$this->db->lastInsertId();
    }

    public function findByUsername(string $username): ?array {
        $s = $this->db->prepare('SELECT * FROM users WHERE username = ? LIMIT 1');
        $s->execute([$username]);
        return $s->fetch() ?: null;
    }

    public function findById(int $id): ?array {
        $s = $this->db->prepare('SELECT id, username, full_name, bio, profile_image, cover_image, created_at FROM users WHERE id = ? LIMIT 1');
        $s->execute([$id]);
        return $s->fetch() ?: null;
    }

    public function update(int $id, string $username, string $fullName, string $bio, ?string $profileImage = null, ?string $coverImage = null, bool $removeCover = false): bool {
        $fields = ['username = ?', 'full_name = ?', 'bio = ?'];
        $params = [$username, $fullName, $bio];

        if ($profileImage !== null) {
            $fields[] = 'profile_image = ?';
            $params[] = $profileImage;
        }

        if ($coverImage !== null) {
            $fields[] = 'cover_image = ?';
            $params[] = $coverImage;
        } elseif ($removeCover) {
            $fields[] = 'cover_image = NULL';
        }

        $params[] = $id;
        $sql = 'UPDATE users SET ' . implode(', ', $fields) . ' WHERE id = ?';
        $s = $this->db->prepare($sql);
        return $s->execute($params);
    }

    public function usernameTaken(string $username, int $exceptId = 0): bool {
        $s = $this->db->prepare('SELECT COUNT(*) FROM users WHERE username = ? AND id <> ?');
        $s->execute([$username, $exceptId]);
        return (int)$s->fetchColumn() > 0;
    }

    public function search(string $term): array {
        $like = '%' . $term . '%';
        $s = $this->db->prepare('SELECT id, username, full_name, bio, profile_image, cover_image FROM users WHERE username LIKE ? OR full_name LIKE ? ORDER BY full_name LIMIT 20');
        $s->execute([$like, $like]);
        return $s->fetchAll();
    }

    public function allUsersExcept(int $exceptUserId, int $limit = 20): array {
        $s = $this->db->prepare('SELECT id, username, full_name, bio, profile_image, cover_image FROM users WHERE id <> ? ORDER BY created_at DESC LIMIT ' . (int)$limit);
        $s->execute([$exceptUserId]);
        return $s->fetchAll();
    }
}
