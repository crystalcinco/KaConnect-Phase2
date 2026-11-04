<?php
/** @var array $post */
/** @var array|null $me */
$me = $me ?? auth_user();
$myId = $me ? (int)$me['id'] : 0;
$postId = (int)$post['id'];
$authorId = (int)$post['user_id'];
$isOwner = ($authorId === $myId);
$liked = !empty($post['liked']);
$likeCount = (int)($post['like_count'] ?? 0);
$comments = $post['comments'] ?? [];
$isFollowingAuthor = !empty($post['author_is_following']);
?>
<article class="post card" id="post-<?= $postId ?>" data-post-id="<?= $postId ?>">
  <div class="post-head">
    <a href="index.php?page=profile&id=<?= $authorId ?>" class="avatar-link" title="View <?= e($post['full_name']) ?>'s profile">
      <?= avatar_markup(['full_name' => $post['full_name'], 'profile_image' => $post['profile_image'] ?? null], 'avatar small') ?>
    </a>

    <div class="post-author-wrap">
      <a class="post-author" href="index.php?page=profile&id=<?= $authorId ?>"><?= e($post['full_name']) ?></a>
      <div class="muted">@<?= e($post['username']) ?> <span>·</span> <?= e(friendly_date($post['created_at'])) ?></div>
    </div>

    <?php if (!$isOwner && $myId > 0): ?>
      <form method="post" action="index.php?page=follow_toggle" class="follow-form post-follow-form" data-user-id="<?= $authorId ?>">
        <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="user_id" value="<?= $authorId ?>">
        <button type="submit" class="button mini follow-btn <?= $isFollowingAuthor ? 'secondary' : 'primary' ?>">
          <?= $isFollowingAuthor ? 'Following' : 'Follow' ?>
        </button>
      </form>
    <?php endif; ?>

    <?php if ($isOwner): ?>
      <div class="post-owner-menu">
        <a class="post-more" href="index.php?page=post_edit&id=<?= $postId ?>" aria-label="Edit your post">•••</a>
      </div>
    <?php endif; ?>
  </div>

  <div class="post-content"><?= nl2br(e($post['content'])) ?></div>

  <?php if (!empty($post['image'])): ?>
    <img class="post-image" src="<?= e($post['image']) ?>" alt="Photo shared by <?= e($post['full_name']) ?>" loading="lazy">
  <?php endif; ?>

  <div class="post-actions">
    <form method="post" action="index.php?page=like" class="like-form" data-post-id="<?= $postId ?>">
      <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
      <input type="hidden" name="post_id" value="<?= $postId ?>">
      <button class="action <?= $liked ? 'liked' : '' ?>" type="submit" aria-label="<?= $liked ? 'Unlike' : 'Like' ?> post">
        <span class="like-icon"><?= $liked ? '♥' : '♡' ?></span> Like <b class="like-count"><?= $likeCount ?></b>
      </button>
    </form>
    
    <a class="action comment-jump" href="#comments-<?= $postId ?>">
      <span>▤</span> Comment <b class="comment-count-badge"><?= count($comments) ?></b>
    </a>

    <?php if ($isOwner): ?>
      <form method="post" action="index.php?page=post_delete" class="post-delete-form" onsubmit="return confirm('Are you sure you want to delete this post?')">
        <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="id" value="<?= $postId ?>">
        <button class="action danger-text" type="submit">Delete</button>
      </form>
    <?php endif; ?>
  </div>

  <div class="comments" id="comments-<?= $postId ?>">
    <h4>Conversation <span class="comment-count-header"><?= count($comments) ?></span></h4>
    
    <div class="comments-list">
      <?php foreach ($comments as $comment): ?>
        <div class="comment" id="comment-<?= (int)$comment['id'] ?>" data-comment-id="<?= (int)$comment['id'] ?>">
          <div class="comment-body">
            <a href="index.php?page=profile&id=<?= (int)$comment['user_id'] ?>" class="comment-author-link">
              <strong><?= e($comment['full_name']) ?></strong>
            </a>
            <span class="muted"> @<?= e($comment['username']) ?></span>
            <p><?= nl2br(e($comment['content'])) ?></p>
          </div>
          <?php if ((int)$comment['user_id'] === $myId): ?>
            <div class="comment-tools">
              <form method="post" action="index.php?page=comment_edit" class="comment-edit-form">
                <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="id" value="<?= (int)$comment['id'] ?>">
                <input name="content" value="<?= e($comment['content']) ?>" maxlength="1000" aria-label="Edit comment">
                <button class="mini" type="submit">Save</button>
              </form>
              <form method="post" action="index.php?page=comment_delete" class="comment-delete-form" onsubmit="return confirm('Delete this comment?')">
                <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="id" value="<?= (int)$comment['id'] ?>">
                <button class="mini danger-text" type="submit">Delete</button>
              </form>
            </div>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>

    <form class="comment-form dynamic-comment-form" method="post" action="index.php?page=comment_create" data-post-id="<?= $postId ?>">
      <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
      <input type="hidden" name="post_id" value="<?= $postId ?>">
      <input name="content" maxlength="1000" placeholder="Add something thoughtful..." aria-label="Write a comment" required autocomplete="off">
      <button type="submit" aria-label="Send comment">Send ↗</button>
    </form>
  </div>
</article>
