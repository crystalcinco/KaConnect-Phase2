<?php
declare(strict_types=1);

class FollowModel {
    public function __construct(private PDO $db) {}

    public function toggle(int $followerId, int $followingId): bool {
        if ($followerId === $followingId) return false;

        $s = $this->db->prepare('SELECT id FROM follows WHERE follower_id = ? AND following_id = ? LIMIT 1');
        $s->execute([$followerId, $followingId]);
        $id = $s->fetchColumn();

        if ($id) {
            $del = $this->db->prepare('DELETE FROM follows WHERE id = ?');
            $del->execute([(int)$id]);
            return false; // now unfollowed
        }

        $ins = $this->db->prepare('INSERT IGNORE INTO follows (follower_id, following_id) VALUES (?, ?)');
        $ins->execute([$followerId, $followingId]);
        return true; // now following
    }

    public function isFollowing(int $followerId, int $followingId): bool {
        if ($followerId === $followingId) return false;
        $s = $this->db->prepare('SELECT 1 FROM follows WHERE follower_id = ? AND following_id = ? LIMIT 1');
        $s->execute([$followerId, $followingId]);
        return (bool)$s->fetchColumn();
    }

    public function followerCount(int $userId): int {
        $s = $this->db->prepare('SELECT COUNT(*) FROM follows WHERE following_id = ?');
        $s->execute([$userId]);
        return (int)$s->fetchColumn();
    }

    public function followingCount(int $userId): int {
        $s = $this->db->prepare('SELECT COUNT(*) FROM follows WHERE follower_id = ?');
        $s->execute([$userId]);
        return (int)$s->fetchColumn();
    }

    public function getFollowers(int $userId): array {
        $s = $this->db->prepare('SELECT u.id, u.username, u.full_name, u.bio, u.profile_image 
            FROM follows f 
            JOIN users u ON u.id = f.follower_id 
            WHERE f.following_id = ? 
            ORDER BY f.created_at DESC');
        $s->execute([$userId]);
        return $s->fetchAll();
    }

    public function getFollowing(int $userId): array {
        $s = $this->db->prepare('SELECT u.id, u.username, u.full_name, u.bio, u.profile_image 
            FROM follows f 
            JOIN users u ON u.id = f.following_id 
            WHERE f.follower_id = ? 
            ORDER BY f.created_at DESC');
        $s->execute([$userId]);
        return $s->fetchAll();
    }

    public function getFollowingIds(int $userId): array {
        $s = $this->db->prepare('SELECT following_id FROM follows WHERE follower_id = ?');
        $s->execute([$userId]);
        return $s->fetchAll(PDO::FETCH_COLUMN);
    }
}
