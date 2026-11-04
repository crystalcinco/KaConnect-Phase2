<?php
/** @var array $comment */
/** @var array|null $me */
$me = $me ?? auth_user();
$myId = $me ? (int)$me['id'] : 0;
$commentId = (int)$comment['id'];
$isCommentOwner = ((int)$comment['user_id'] === $myId);
?>
<div class="comment" id="comment-<?= $commentId ?>" data-comment-id="<?= $commentId ?>">
  <div class="comment-body">
    <strong><?= e($comment['full_name']) ?></strong>
    <span class="muted"> @<?= e($comment['username']) ?></span>
    <p><?= nl2br(e($comment['content'])) ?></p>
  </div>
  <?php if ($isCommentOwner): ?>
    <div class="comment-tools">
      <form method="post" action="index.php?page=comment_edit" class="comment-edit-form">
        <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="id" value="<?= $commentId ?>">
        <input name="content" value="<?= e($comment['content']) ?>" maxlength="1000" aria-label="Edit comment">
        <button class="mini" type="submit">Save</button>
      </form>
      <form method="post" action="index.php?page=comment_delete" class="comment-delete-form" onsubmit="return confirm('Delete this comment?')">
        <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="id" value="<?= $commentId ?>">
        <button class="mini danger-text" type="submit">Delete</button>
      </form>
    </div>
  <?php endif; ?>
</div>
