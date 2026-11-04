<?php
declare(strict_types=1);

class PostController {
    public function __construct(
        private PostModel $posts,
        private CommentModel $comments,
        private LikeModel $likes,
        private UserModel $users,
        private NotificationModel $notifications,
        private FollowModel $follows
    ) {}

    public function feed(): void {
        require_auth();
        $me = auth_user();
        $myId = (int)current_user_id();

        $search = trim((string)($_GET['q'] ?? ''));
        $filter = (string)($_GET['filter'] ?? 'all');
        $followingOnly = ($filter === 'following') ? $myId : null;

        $posts = $this->posts->all($search !== '' ? $search : null, $followingOnly);
        $userResults = $search !== '' ? $this->users->search($search) : [];

        foreach ($posts as &$item) {
            $item['comments'] = $this->comments->byPost((int)$item['id']);
            $item['liked'] = $this->likes->likedByUser((int)$item['id'], $myId);
            $item['author_is_following'] = ((int)$item['user_id'] !== $myId) ? $this->follows->isFollowing($myId, (int)$item['user_id']) : false;
        }
        unset($item);

        $followingCount = $this->follows->followingCount($myId);
        $followerCount = $this->follows->followerCount($myId);

        $suggestedUsers = $this->users->allUsersExcept($myId, 6);
        foreach ($suggestedUsers as &$su) {
            $su['is_following'] = $this->follows->isFollowing($myId, (int)$su['id']);
        }
        unset($su);

        require __DIR__ . '/../views/posts/feed.php';
    }

    public function create(): void {
        require_auth();
        require_post();
        verify_csrf();

        $content = trim((string)($_POST['content'] ?? ''));
        if ($content === '' || text_length($content) > 2000) {
            if (is_ajax()) json_response(['success' => false, 'error' => 'Post content is required and must be 2,000 characters or fewer.'], 400);
            flash('error', 'Post content is required and must be 2,000 characters or fewer.');
            redirect('index.php?page=feed');
        }

        $image = null;
        try {
            // Check for base64 cropped image first, then standard multipart upload
            $croppedData = trim((string)($_POST['image_cropped'] ?? ''));
            if ($croppedData !== '') {
                $image = upload_base64_image($croppedData, 'posts');
            } else {
                $image = upload_image($_FILES['image'] ?? [], 'posts');
            }

            $postId = $this->posts->create((int)current_user_id(), $content, $image);

            if (is_ajax()) {
                $post = $this->posts->findWithAuthor($postId);
                $post['comments'] = [];
                $post['liked'] = false;
                $me = auth_user();

                ob_start();
                require __DIR__ . '/../views/partials/post_card.php';
                $html = ob_get_clean();

                json_response([
                    'success' => true,
                    'post_id' => $postId,
                    'message' => 'Post published.',
                    'html' => $html
                ]);
            }

            flash('success', 'Post published.');
            redirect('index.php?page=feed');
        } catch (Throwable $e) {
            if ($image) delete_uploaded_image($image);
            $msg = $e instanceof RuntimeException ? $e->getMessage() : 'Unable to publish your post. Please try again.';
            if (is_ajax()) json_response(['success' => false, 'error' => $msg], 400);
            flash('error', $msg);
            redirect('index.php?page=feed');
        }
    }

    public function edit(): void {
        require_auth();
        $id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
        $post = $this->posts->find($id);

        if (!$post || (int)$post['user_id'] !== (int)current_user_id()) {
            http_response_code(403);
            exit('You cannot edit this post.');
        }

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET') {
            require __DIR__ . '/../views/posts/edit.php';
            return;
        }

        require_post();
        verify_csrf();
        $content = trim((string)($_POST['content'] ?? ''));

        if ($content === '' || text_length($content) > 2000) {
            flash('error', 'Post content is required and must be 2,000 characters or fewer.');
            redirect('index.php?page=post_edit&id=' . $id);
        }

        $newImage = null;
        try {
            $croppedData = trim((string)($_POST['image_cropped'] ?? ''));
            if ($croppedData !== '') {
                $newImage = upload_base64_image($croppedData, 'posts');
            } else {
                $newImage = upload_image($_FILES['image'] ?? [], 'posts');
            }

            $remove = isset($_POST['remove_image']) && $_POST['remove_image'] === '1';
            $ok = $this->posts->update($id, (int)current_user_id(), $content, $newImage, $remove);

            if ($newImage || $remove) {
                delete_uploaded_image($post['image']);
            }

            flash($ok ? 'success' : 'error', $ok ? 'Post updated.' : 'No changes were saved.');
            redirect('index.php?page=feed');
        } catch (Throwable $e) {
            if ($newImage) delete_uploaded_image($newImage);
            flash('error', $e instanceof RuntimeException ? $e->getMessage() : 'Unable to update post.');
            redirect('index.php?page=post_edit&id=' . $id);
        }
    }

    public function delete(): void {
        require_auth();
        require_post();
        verify_csrf();

        $id = (int)($_POST['id'] ?? 0);
        $existing = $this->posts->find($id);

        if (!$existing || (int)$existing['user_id'] !== (int)current_user_id()) {
            if (is_ajax()) json_response(['success' => false, 'error' => 'You cannot delete this post.'], 403);
            http_response_code(403);
            exit('You cannot delete this post.');
        }

        $ok = $this->posts->delete($id, (int)current_user_id());
        if ($ok) delete_uploaded_image($existing['image']);

        if (is_ajax()) {
            json_response(['success' => $ok, 'id' => $id, 'message' => $ok ? 'Post deleted.' : 'Post could not be deleted.']);
        }

        flash($ok ? 'success' : 'error', $ok ? 'Post deleted.' : 'Post could not be deleted.');
        redirect('index.php?page=feed');
    }

    public function like(): void {
        require_auth();
        require_post();
        verify_csrf();

        $id = (int)($_POST['post_id'] ?? 0);
        $post = $id > 0 ? $this->posts->find($id) : null;

        if (!$post) {
            if (is_ajax()) json_response(['success' => false, 'error' => 'Post not found.'], 404);
            redirect(safe_return_url());
        }

        $myId = (int)current_user_id();
        $me = auth_user();
        $nowLiked = $this->likes->toggle($id, $myId);
        $likeCount = $this->likes->count($id);

        // If post author is someone else and post was liked, create notification
        if ($nowLiked && (int)$post['user_id'] !== $myId) {
            $this->notifications->create(
                (int)$post['user_id'],
                $myId,
                'like',
                $id,
                $me['full_name'] . ' liked your post.'
            );
        }

        if (is_ajax()) {
            json_response([
                'success' => true,
                'liked' => $nowLiked,
                'like_count' => $likeCount
            ]);
        }

        redirect(safe_return_url());
    }
}
