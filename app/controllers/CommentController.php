<?php
declare(strict_types=1);

class CommentController {
    public function __construct(
        private CommentModel $comments,
        private PostModel $posts,
        private NotificationModel $notifications
    ) {}

    public function create(): void {
        require_auth();
        require_post();
        verify_csrf();

        $postId = (int)($_POST['post_id'] ?? 0);
        $content = trim((string)($_POST['content'] ?? ''));
        $myId = (int)current_user_id();
        $me = auth_user();

        if ($postId < 1 || $content === '' || text_length($content) > 1000) {
            $msg = 'Comments must contain 1–1,000 characters.';
            if (is_ajax()) json_response(['success' => false, 'error' => $msg], 400);
            flash('error', $msg);
            redirect(safe_return_url());
        }

        $post = $this->posts->find($postId);
        if (!$post) {
            $msg = 'That post is no longer available.';
            if (is_ajax()) json_response(['success' => false, 'error' => $msg], 404);
            flash('error', $msg);
            redirect(safe_return_url());
        }

        try {
            $commentId = $this->comments->create($postId, $myId, $content);
            $comment = $this->comments->findById($commentId);
            $commentCount = $this->comments->countByPost($postId);

            // Notify post owner if not self
            if ((int)$post['user_id'] !== $myId) {
                $preview = mb_strimwidth($content, 0, 45, '...');
                $this->notifications->create(
                    (int)$post['user_id'],
                    $myId,
                    'comment',
                    $postId,
                    $me['full_name'] . ' commented: "' . $preview . '"'
                );
            }

            if (is_ajax()) {
                ob_start();
                require __DIR__ . '/../views/partials/comment_item.php';
                $html = ob_get_clean();

                json_response([
                    'success' => true,
                    'comment_id' => $commentId,
                    'comment_count' => $commentCount,
                    'html' => $html,
                    'message' => 'Comment added.'
                ]);
            }

            flash('success', 'Comment added.');
        } catch (PDOException $e) {
            if (is_ajax()) json_response(['success' => false, 'error' => 'Unable to save comment.'], 500);
            flash('error', 'That post is no longer available.');
        }

        redirect(safe_return_url());
    }

    public function edit(): void {
        require_auth();
        require_post();
        verify_csrf();

        $id = (int)($_POST['id'] ?? 0);
        $content = trim((string)($_POST['content'] ?? ''));
        $myId = (int)current_user_id();

        if ($content === '' || text_length($content) > 1000) {
            $msg = 'Comments must contain 1–1,000 characters.';
            if (is_ajax()) json_response(['success' => false, 'error' => $msg], 400);
            flash('error', $msg);
            redirect(safe_return_url());
        }

        $ok = $this->comments->update($id, $myId, $content);
        if (is_ajax()) {
            json_response([
                'success' => $ok,
                'id' => $id,
                'content' => $content,
                'error' => $ok ? null : 'Comment not found or you do not own it.'
            ], $ok ? 200 : 400);
        }

        flash($ok ? 'success' : 'error', $ok ? 'Comment updated.' : 'Comment not found or you do not own it.');
        redirect(safe_return_url());
    }

    public function delete(): void {
        require_auth();
        require_post();
        verify_csrf();

        $id = (int)($_POST['id'] ?? 0);
        $myId = (int)current_user_id();
        $ok = $this->comments->delete($id, $myId);

        if (is_ajax()) {
            json_response([
                'success' => $ok,
                'id' => $id,
                'message' => $ok ? 'Comment deleted.' : 'Comment not found or you do not own it.'
            ], $ok ? 200 : 400);
        }

        flash($ok ? 'success' : 'error', $ok ? 'Comment deleted.' : 'Comment not found or you do not own it.');
        redirect(safe_return_url());
    }
}
