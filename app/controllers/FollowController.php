<?php
declare(strict_types=1);

class FollowController {
    public function __construct(
        private FollowModel $follows,
        private UserModel $users,
        private NotificationModel $notifications
    ) {}

    public function toggle(): void {
        require_auth();
        require_post();
        verify_csrf();

        $myId = (int)current_user_id();
        $targetId = (int)($_POST['user_id'] ?? 0);

        if ($targetId < 1 || $targetId === $myId) {
            if (is_ajax()) json_response(['success' => false, 'error' => 'Invalid user.'], 400);
            redirect(safe_return_url());
        }

        $targetUser = $this->users->findById($targetId);
        if (!$targetUser) {
            if (is_ajax()) json_response(['success' => false, 'error' => 'User not found.'], 404);
            redirect(safe_return_url());
        }

        $isNowFollowing = $this->follows->toggle($myId, $targetId);
        $followerCount = $this->follows->followerCount($targetId);

        if ($isNowFollowing) {
            $me = auth_user();
            $this->notifications->create(
                $targetId,
                $myId,
                'follow',
                $myId,
                $me['full_name'] . ' started following you.'
            );
        }

        if (is_ajax()) {
            json_response([
                'success' => true,
                'is_following' => $isNowFollowing,
                'follower_count' => $followerCount,
                'button_text' => $isNowFollowing ? 'Following' : 'Follow',
                'message' => $isNowFollowing ? 'You are now following ' . $targetUser['full_name'] : 'Unfollowed ' . $targetUser['full_name']
            ]);
        }

        flash('success', $isNowFollowing ? 'You are now following ' . $targetUser['full_name'] : 'Unfollowed ' . $targetUser['full_name']);
        redirect(safe_return_url());
    }

    public function followers(): void {
        require_auth();
        $id = (int)($_GET['id'] ?? current_user_id());
        $user = $this->users->findById($id);
        if (!$user) { http_response_code(404); exit('User not found.'); }
        $list = $this->follows->getFollowers($id);
        $title = 'Followers of ' . $user['full_name'];
        require __DIR__ . '/../views/profile/connections.php';
    }

    public function following(): void {
        require_auth();
        $id = (int)($_GET['id'] ?? current_user_id());
        $user = $this->users->findById($id);
        if (!$user) { http_response_code(404); exit('User not found.'); }
        $list = $this->follows->getFollowing($id);
        $title = 'Following by ' . $user['full_name'];
        require __DIR__ . '/../views/profile/connections.php';
    }
}
