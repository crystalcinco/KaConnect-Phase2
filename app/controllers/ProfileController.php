<?php
declare(strict_types=1);

class ProfileController {
    public function __construct(
        private UserModel $users,
        private PostModel $posts,
        private FollowModel $follows,
        private NotificationModel $notifications
    ) {}

    public function members(): void {
        require_auth();
        $myId = (int)current_user_id();
        $q = trim((string)($_GET['q'] ?? ''));

        if ($q !== '') {
            $members = $this->users->search($q);
        } else {
            $members = $this->users->allUsersExcept($myId, 50);
        }

        foreach ($members as &$m) {
            $m['follower_count'] = $this->follows->followerCount((int)$m['id']);
            $m['following_count'] = $this->follows->followingCount((int)$m['id']);
            $m['is_following'] = $this->follows->isFollowing($myId, (int)$m['id']);
        }
        unset($m);

        $title = 'Explore Community';
        require __DIR__ . '/../views/profile/members.php';
    }

    public function show(): void {
        require_auth();
        $myId = (int)current_user_id();

        $user = null;
        if (!empty($_GET['username'])) {
            $user = $this->users->findByUsername(trim((string)$_GET['username']));
        } elseif (!empty($_GET['u'])) {
            $user = $this->users->findByUsername(trim((string)$_GET['u']));
        } elseif (!empty($_GET['id'])) {
            $id = (int)$_GET['id'];
            if ($id > 0) {
                $user = $this->users->findById($id);
            }
        } elseif (!empty($_GET['user_id'])) {
            $id = (int)$_GET['user_id'];
            if ($id > 0) {
                $user = $this->users->findById($id);
            }
        } else {
            $user = $this->users->findById($myId);
        }

        if (!$user) {
            http_response_code(404);
            $title = 'User Not Found';
            require __DIR__ . '/../views/partials/header.php';
            ?>
            <main id="main-content" class="container narrow">
              <div class="card empty">
                <div class="empty-icon">👤</div>
                <h2>User Not Found</h2>
                <p class="muted">The requested profile does not exist or may have been removed.</p>
                <div style="margin-top: 18px; display: flex; gap: 10px; justify-content: center; flex-wrap: wrap;">
                  <a class="button primary" href="index.php?page=members">Browse Community Members</a>
                  <a class="button secondary" href="index.php?page=feed">Back to Newsfeed</a>
                </div>
              </div>
            </main>
            <?php
            require __DIR__ . '/../views/partials/footer.php';
            exit;
        }

        $id = (int)$user['id'];
        $userPosts = $this->posts->userPosts($id);
        $followerCount = $this->follows->followerCount($id);
        $followingCount = $this->follows->followingCount($id);
        $isFollowing = ($id !== $myId) ? $this->follows->isFollowing($myId, $id) : false;

        $tab = (string)($_GET['tab'] ?? '');
        $tabUsers = [];
        if ($tab === 'followers') {
            $tabUsers = $this->follows->getFollowers($id);
        } elseif ($tab === 'following') {
            $tabUsers = $this->follows->getFollowing($id);
        }

        require __DIR__ . '/../views/profile/profile.php';
    }

    public function edit(): void {
        require_auth();
        $myId = (int)current_user_id();
        $user = $this->users->findById($myId);
        if (!$user) {
            http_response_code(404);
            exit('User not found.');
        }

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET') {
            $errors = [];
            require __DIR__ . '/../views/profile/edit.php';
            return;
        }

        require_post();
        verify_csrf();

        $username = trim((string)($_POST['username'] ?? ''));
        $fullName = trim((string)($_POST['full_name'] ?? ''));
        $bio = trim((string)($_POST['bio'] ?? ''));
        $removeCover = isset($_POST['remove_cover']) && $_POST['remove_cover'] === '1';
        $removeAvatar = isset($_POST['remove_avatar']) && $_POST['remove_avatar'] === '1';
        $errors = [];

        if (!preg_match('/^[A-Za-z0-9_]{3,30}$/', $username)) {
            $errors[] = 'Username must be 3–30 characters using letters, numbers, or underscore.';
        }
        if (text_length($fullName) < 2 || text_length($fullName) > 100) {
            $errors[] = 'Full name must be between 2 and 100 characters.';
        }
        if (text_length($bio) > 500) {
            $errors[] = 'Bio must be 500 characters or fewer.';
        }
        if ($this->users->usernameTaken($username, (int)$user['id'])) {
            $errors[] = 'Username is already taken.';
        }

        // Profile Avatar upload (cropped base64 or multipart)
        $profileImage = null;
        try {
            $croppedAvatar = trim((string)($_POST['profile_image_cropped'] ?? ''));
            if ($croppedAvatar !== '') {
                $profileImage = upload_base64_image($croppedAvatar, 'profiles');
            } else {
                $profileImage = upload_image($_FILES['profile_image'] ?? [], 'profiles');
            }
        } catch (Throwable $e) {
            $errors[] = $e->getMessage();
        }

        // Cover Photo upload (cropped base64 or multipart)
        $coverImage = null;
        try {
            $croppedCover = trim((string)($_POST['cover_image_cropped'] ?? ''));
            if ($croppedCover !== '') {
                $coverImage = upload_base64_image($croppedCover, 'covers');
            } else {
                $coverImage = upload_image($_FILES['cover_image'] ?? [], 'covers');
            }
        } catch (Throwable $e) {
            $errors[] = $e->getMessage();
        }

        if (!$errors) {
            try {
                $avatarToSave = $removeAvatar ? '' : $profileImage;
                $ok = $this->users->update(
                    (int)$user['id'],
                    $username,
                    $fullName,
                    $bio,
                    $avatarToSave,
                    $coverImage,
                    $removeCover
                );

                if ($ok) {
                    if ($profileImage && !empty($user['profile_image'])) {
                        delete_uploaded_image($user['profile_image']);
                    }
                    if ($coverImage && !empty($user['cover_image'])) {
                        delete_uploaded_image($user['cover_image']);
                    } elseif ($removeCover && !empty($user['cover_image'])) {
                        delete_uploaded_image($user['cover_image']);
                    }

                    $_SESSION['user'] = $this->users->findById((int)$user['id']);
                    flash('success', 'Profile updated successfully.');
                    redirect('index.php?page=profile');
                }
                $errors[] = 'No profile changes were saved.';
            } catch (PDOException $e) {
                $errors[] = ((string)$e->getCode() === '23000') ? 'Username is already taken.' : 'Unable to update profile.';
            }
        }

        if ($profileImage) delete_uploaded_image($profileImage);
        if ($coverImage) delete_uploaded_image($coverImage);
        require __DIR__ . '/../views/profile/edit.php';
    }
}
