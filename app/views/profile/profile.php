<?php
$title = $user['full_name'] . ' Profile';
require __DIR__ . '/../partials/header.php';
$me = auth_user();
$myId = (int)current_user_id();
$userId = (int)$user['id'];
$isMe = ($userId === $myId);
$coverStyle = !empty($user['cover_image']) ? 'style="background-image: url(\'' . e($user['cover_image']) . '\'); background-size: cover; background-position: center;"' : '';
?>
<main id="main-content" class="container narrow">

<?php if (!$isMe): ?>
  <div class="profile-nav-back" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
    <a href="index.php?page=feed" class="text-link">← Back to Newsfeed</a>
    <a href="index.php?page=members" class="text-link">Find more classmates →</a>
  </div>
<?php endif; ?>

<section class="profile-hero card">
  <div class="cover <?= !empty($user['cover_image']) ? 'has-cover-image' : '' ?>" <?= $coverStyle ?>>
    <?php if ($isMe): ?>
      <a href="index.php?page=profile_edit#cover-edit-section" class="cover-change-pill" title="Update cover photo">
        📷 Change Cover
      </a>
    <?php endif; ?>
  </div>

  <div class="profile-body">
    <?php if (!empty($user['profile_image'])): ?>
      <img class="profile-avatar avatar-photo" src="<?= e($user['profile_image']) ?>" alt="<?= e($user['full_name']) ?> profile picture">
    <?php else: ?>
      <div class="profile-avatar"><?= e(strtoupper(substr($user['full_name'], 0, 1))) ?></div>
    <?php endif; ?>

    <h1><?= e($user['full_name']) ?></h1>
    <p class="muted">@<?= e($user['username']) ?></p>

    <div class="profile-metrics">
      <a href="index.php?page=followers&id=<?= $userId ?>" class="metric-link">
        <strong id="profile-follower-count"><?= (int)$followerCount ?></strong>
        <span>Followers</span>
      </a>
      <a href="index.php?page=following&id=<?= $userId ?>" class="metric-link">
        <strong><?= (int)$followingCount ?></strong>
        <span>Following</span>
      </a>
      <div class="metric-link non-clickable">
        <strong><?= count($userPosts) ?></strong>
        <span>Posts</span>
      </div>
    </div>

    <p class="bio"><?= nl2br(e($user['bio'] ?: 'No bio yet.')) ?></p>

    <div class="profile-actions-bar">
      <?php if ($isMe): ?>
        <a class="button primary" href="index.php?page=profile_edit">Edit Profile</a>
      <?php else: ?>
        <form method="post" action="index.php?page=follow_toggle" class="follow-form" data-user-id="<?= $userId ?>">
          <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
          <input type="hidden" name="user_id" value="<?= $userId ?>">
          <button type="submit" class="button follow-btn <?= $isFollowing ? 'secondary' : 'primary' ?>">
            <?= $isFollowing ? 'Following' : 'Follow' ?>
          </button>
        </form>

        <a class="button secondary" href="index.php?page=messages&user_id=<?= $userId ?>">
          <span>✉</span> Direct Message
        </a>
      <?php endif; ?>
    </div>
  </div>
</section>

<!-- User Posts -->
<section class="card">
  <div class="section-title">
    <h2>Posts by <?= e($user['full_name']) ?></h2>
    <span><?= count($userPosts) ?></span>
  </div>

  <?php if (!$userPosts): ?>
    <div class="empty">
      <div class="empty-icon">✳</div>
      <h3>No posts published yet</h3>
      <p><?= $isMe ? 'Share your first thought with the community!' : 'This user has not shared any posts yet.' ?></p>
      <?php if ($isMe): ?>
        <a class="button primary" href="index.php?page=feed">Write a post</a>
      <?php endif; ?>
    </div>
  <?php else: ?>
    <?php foreach ($userPosts as $post): ?>
      <article class="profile-post" id="post-<?= (int)$post['id'] ?>">
        <div class="muted"><?= friendly_date($post['created_at']) ?> <span>·</span> <?= (int)$post['like_count'] ?> likes</div>
        <p><?= nl2br(e($post['content'])) ?></p>
        <?php if (!empty($post['image'])): ?>
          <img class="post-image" src="<?= e($post['image']) ?>" alt="Post image" loading="lazy">
        <?php endif; ?>
        <?php if ($isMe): ?>
          <div class="profile-post-tools">
            <a class="text-link" href="index.php?page=post_edit&id=<?= (int)$post['id'] ?>">Edit</a>
          </div>
        <?php endif; ?>
      </article>
    <?php endforeach; ?>
  <?php endif; ?>
</section>

</main>
<?php require __DIR__ . '/../partials/footer.php'; ?>
