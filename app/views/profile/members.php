<?php
$title = 'Explore Community';
require __DIR__ . '/../partials/header.php';
$me = auth_user();
$myId = (int)current_user_id();
?>
<main id="main-content" class="container">

<div class="card members-shell">
  <div class="members-header">
    <div>
      <p class="eyebrow">DISCOVER CLASSMATES</p>
      <h1>Explore Community</h1>
      <p class="muted">Find classmates, view their profiles, and build your KaConnect network.</p>
    </div>

    <form class="searchbar members-search" method="get" role="search">
      <input type="hidden" name="page" value="members">
      <span class="search-icon">⌕</span>
      <input name="q" value="<?= e($q ?? '') ?>" placeholder="Search by name or @username..." aria-label="Search members">
      <button type="submit">Search</button>
      <?php if (!empty($q)): ?>
        <a class="clear-search" href="index.php?page=members">Clear</a>
      <?php endif; ?>
    </form>
  </div>

  <div class="members-grid">
    <?php if (empty($members)): ?>
      <div class="card empty" style="grid-column: 1 / -1;">
        <div class="empty-icon">👥</div>
        <h3>No members found</h3>
        <p class="muted"><?= !empty($q) ? 'Try searching for a different keyword or name.' : 'Be the first to invite your classmates!' ?></p>
        <?php if (!empty($q)): ?>
          <a class="button primary" href="index.php?page=members">View All Members</a>
        <?php endif; ?>
      </div>
    <?php else: ?>
      <?php foreach ($members as $person): ?>
        <?php
          $pId = (int)$person['id'];
          $isMe = ($pId === $myId);
          $coverBg = !empty($person['cover_image'])
            ? 'style="background-image: url(\'' . e($person['cover_image']) . '\'); background-size: cover; background-position: center;"'
            : '';
        ?>
        <article class="member-card card" id="member-card-<?= $pId ?>">
          <div class="member-cover" <?= $coverBg ?>></div>
          <div class="member-body">
            <a href="index.php?page=profile&id=<?= $pId ?>" class="member-avatar-wrap" title="View profile">
              <?= avatar_markup($person, 'profile-avatar') ?>
            </a>
            
            <a href="index.php?page=profile&id=<?= $pId ?>" class="member-name-link">
              <h3><?= e($person['full_name']) ?></h3>
            </a>
            <p class="muted member-handle">@<?= e($person['username']) ?></p>

            <?php if (!empty($person['bio'])): ?>
              <p class="member-bio"><?= e(mb_strimwidth($person['bio'], 0, 95, '...')) ?></p>
            <?php else: ?>
              <p class="member-bio muted">No bio shared yet.</p>
            <?php endif; ?>

            <div class="member-stats">
              <span><strong><?= (int)($person['follower_count'] ?? 0) ?></strong> followers</span>
              <span>·</span>
              <span><strong><?= (int)($person['following_count'] ?? 0) ?></strong> following</span>
            </div>

            <div class="member-actions">
              <a href="index.php?page=profile&id=<?= $pId ?>" class="button secondary mini">View Profile</a>
              <?php if (!$isMe): ?>
                <form method="post" action="index.php?page=follow_toggle" class="follow-form" data-user-id="<?= $pId ?>">
                  <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                  <input type="hidden" name="user_id" value="<?= $pId ?>">
                  <button type="submit" class="button mini follow-btn <?= !empty($person['is_following']) ? 'secondary' : 'primary' ?>">
                    <?= !empty($person['is_following']) ? 'Following' : 'Follow' ?>
                  </button>
                </form>
                <a href="index.php?page=messages&user_id=<?= $pId ?>" class="button mini secondary" title="Send direct message">✉</a>
              <?php endif; ?>
            </div>
          </div>
        </article>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>

</main>
<?php require __DIR__ . '/../partials/footer.php'; ?>
