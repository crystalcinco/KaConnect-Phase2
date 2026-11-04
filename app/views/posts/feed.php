<?php
$title = 'Newsfeed';
require __DIR__ . '/../partials/header.php';
$me = auth_user();
$filter = $filter ?? 'all';
?>
<main id="main-content" class="page-grid">
<section class="feed-column">
  <div class="welcome-row">
    <div>
      <p class="eyebrow">MONDAY MOOD? ANY DAY MOOD.</p>
      <h1>Your little corner of the internet.</h1>
      <p class="welcome-copy">Catch up, share a thought, and see what your KaConnect community is up to.</p>
    </div>
    <div class="user-chip">👋 Hi, <?= e(explode(' ', trim($me['full_name']))[0]) ?>!</div>
  </div>

  <form class="searchbar" method="get" role="search">
    <input type="hidden" name="page" value="feed">
    <span class="search-icon">⌕</span>
    <input name="q" value="<?= e($search) ?>" placeholder="Search posts or people..." aria-label="Search posts or people">
    <button type="submit">Search</button>
    <?php if ($search !== ''): ?>
      <a class="clear-search" href="index.php?page=feed">Clear</a>
    <?php endif; ?>
  </form>

  <!-- Post Composer Card -->
  <section class="card composer">
    <div class="composer-top">
      <?= avatar_markup($me) ?>
      <div>
        <strong>Share something with your community</strong>
        <small>Your voice belongs here.</small>
      </div>
    </div>

    <form method="post" action="index.php?page=post_create" enctype="multipart/form-data" class="composer-form" id="composer-form">
      <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
      <input type="hidden" name="image_cropped" id="composer-image-cropped" value="">

      <textarea name="content" id="composer-content" rows="3" maxlength="2000" placeholder="What's happening today? Share an update, idea, or little win..." required></textarea>

      <!-- Attached Image Preview & Cropper Trigger -->
      <div id="composer-preview-wrap" class="attached-preview-wrap" style="display: none;">
        <div class="attached-preview-card">
          <img id="composer-preview-img" src="" alt="Post image preview">
          <div class="attached-preview-actions">
            <button type="button" class="mini" id="composer-crop-btn">✂ Crop Image</button>
            <button type="button" class="mini danger-text" id="composer-remove-img-btn">✕ Remove</button>
          </div>
        </div>
      </div>

      <div class="form-row">
        <label class="file-label">
          <span>▧</span> Add a photo
          <input type="file" name="image" id="composer-file-input" accept="image/jpeg,image/png,image/gif,image/webp">
        </label>
        <span class="composer-hint">JPG, PNG, WEBP · Max 5MB</span>
        <button class="primary" type="submit" id="composer-submit-btn">
          <span class="btn-text">Share post</span> <span>↗</span>
        </button>
      </div>
    </form>
  </section>

  <!-- Feed Filter Tabs (All Posts vs Following) -->
  <div class="feed-tabs-row">
    <div class="feed-tabs" role="tablist">
      <a href="index.php?page=feed&filter=all<?= $search !== '' ? '&q=' . urlencode($search) : '' ?>" class="feed-tab <?= $filter === 'all' ? 'active' : '' ?>">
        All Posts
      </a>
      <a href="index.php?page=feed&filter=following<?= $search !== '' ? '&q=' . urlencode($search) : '' ?>" class="feed-tab <?= $filter === 'following' ? 'active' : '' ?>">
        Following <?= isset($followingCount) ? '(' . (int)$followingCount . ')' : '' ?>
      </a>
    </div>
    <span class="feed-count"><?= count($posts) ?> <?= count($posts) === 1 ? 'post' : 'posts' ?></span>
  </div>

  <?php if ($search !== ''): ?>
  <section class="card user-search-results">
    <div class="section-title">
      <h2>People</h2>
      <span><?= count($userResults) ?> found</span>
    </div>
    <?php if (!$userResults): ?>
      <p class="muted">No matching people found.</p>
    <?php else: ?>
      <div class="user-result-list">
        <?php foreach ($userResults as $person): ?>
          <div class="user-result-row">
            <a class="user-result" href="index.php?page=profile&id=<?= (int)$person['id'] ?>">
              <?= avatar_markup($person, 'avatar small') ?>
              <span>
                <strong><?= e($person['full_name']) ?></strong>
                <small class="muted">@<?= e($person['username']) ?></small>
              </span>
            </a>
            <?php if ((int)$person['id'] !== (int)$me['id']): ?>
              <?php $isFol = $this->follows->isFollowing((int)$me['id'], (int)$person['id']); ?>
              <form method="post" action="index.php?page=follow_toggle" class="follow-form" data-user-id="<?= (int)$person['id'] ?>">
                <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="user_id" value="<?= (int)$person['id'] ?>">
                <button type="submit" class="button mini follow-btn <?= $isFol ? 'secondary' : 'primary' ?>">
                  <?= $isFol ? 'Following' : 'Follow' ?>
                </button>
              </form>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>
  <?php endif; ?>

  <div id="posts-stream">
    <?php if (!$posts): ?>
      <div class="card empty" id="empty-feed-card">
        <div class="empty-icon">✳</div>
        <h3><?= $search !== '' ? 'No matches just yet' : ($filter === 'following' ? 'No posts from people you follow' : 'It’s a little quiet here') ?></h3>
        <p>
          <?php if ($search !== ''): ?>
            Try a different keyword or search for a person’s name.
          <?php elseif ($filter === 'following'): ?>
            Follow classmates and friends to see their latest updates here!
          <?php else: ?>
            Be the first to share a thought with the community.
          <?php endif; ?>
        </p>
        <a class="text-link" href="index.php?page=feed">View all community posts</a>
      </div>
    <?php else: ?>
      <?php foreach ($posts as $post): ?>
        <?php require __DIR__ . '/../partials/post_card.php'; ?>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</section>

<aside class="sidebar">
  <div class="card side-card profile-mini">
    <p class="eyebrow">YOUR SPACE</p>
    <?= avatar_markup($me, 'sidebar-avatar') ?>
    <h3><?= e($me['full_name']) ?></h3>
    <p class="muted">@<?= e($me['username']) ?></p>

    <div class="mini-profile-stats">
      <a href="index.php?page=profile&tab=followers" class="stat-pill">
        <strong><?= isset($followerCount) ? (int)$followerCount : 0 ?></strong>
        <small>Followers</small>
      </a>
      <a href="index.php?page=profile&tab=following" class="stat-pill">
        <strong><?= isset($followingCount) ? (int)$followingCount : 0 ?></strong>
        <small>Following</small>
      </a>
    </div>

    <a class="side-profile-link" href="index.php?page=profile">View my profile →</a>
  </div>

  <div class="card side-card quick-links-card">
    <p class="eyebrow">CONNECT</p>
    <a href="index.php?page=members" class="quick-side-btn">
      <span>👥</span> Explore Community
    </a>
    <a href="index.php?page=messages" class="quick-side-btn">
      <span>✉</span> Direct Messages
    </a>
    <a href="index.php?page=notifications" class="quick-side-btn">
      <span>🔔</span> Notifications
    </a>
  </div>

  <div class="card side-card discover-card">
    <div class="discover-header">
      <p class="eyebrow">WHO TO FOLLOW</p>
      <a href="index.php?page=members" class="text-link mini-link">Find People →</a>
    </div>
    <?php if (!empty($suggestedUsers)): ?>
      <div class="discover-list">
        <?php foreach ($suggestedUsers as $suggested): ?>
          <div class="discover-item">
            <a href="index.php?page=profile&id=<?= (int)$suggested['id'] ?>" class="discover-user" title="View <?= e($suggested['full_name']) ?>'s profile">
              <?= avatar_markup($suggested, 'avatar small') ?>
              <div class="discover-info">
                <strong class="discover-name"><?= e($suggested['full_name']) ?></strong>
                <small class="muted">@<?= e($suggested['username']) ?></small>
              </div>
            </a>
            <div class="discover-btns">
              <a href="index.php?page=profile&id=<?= (int)$suggested['id'] ?>" class="button mini secondary discover-profile-btn" title="View profile">Profile</a>
              <form method="post" action="index.php?page=follow_toggle" class="follow-form" data-user-id="<?= (int)$suggested['id'] ?>">
                <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="user_id" value="<?= (int)$suggested['id'] ?>">
                <button type="submit" class="button mini follow-btn <?= $suggested['is_following'] ? 'secondary' : 'primary' ?>">
                  <?= $suggested['is_following'] ? 'Following' : 'Follow' ?>
                </button>
              </form>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <div class="discover-empty">
        <p class="muted" style="margin: 6px 0 10px; font-size: .83rem; line-height: 1.5;">No other classmates registered yet.</p>
        <div style="background: var(--mint); padding: 10px 12px; border-radius: 10px; font-size: .78rem; line-height: 1.6;">
          💡 <strong>Tip to test:</strong> Open an incognito/private window, <a href="index.php?page=register" target="_blank" class="text-link">register Account B</a>, and they will immediately appear here so you can view their profile and follow them!
        </div>
      </div>
    <?php endif; ?>
  </div>

  <div class="card side-card">
    <p class="eyebrow">A GOOD COMMUNITY STARTS WITH US</p>
    <h3>Little reminders</h3>
    <ul class="community-tips">
      <li>Be respectful of different opinions.</li>
      <li>Share things that bring value or joy.</li>
      <li>Think before posting personal details.</li>
    </ul>
  </div>

  <div class="sidebar-note">Made for classmates, conversations, and everyday moments. <span>✿</span></div>
</aside>
</main>
<?php require __DIR__ . '/../partials/footer.php'; ?>
