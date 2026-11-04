<?php
require __DIR__ . '/../partials/header.php';
$me = auth_user();
$myId = (int)current_user_id();
?>
<main id="main-content" class="container narrow">

<div class="card">
  <div class="section-title">
    <div>
      <p class="eyebrow">CONNECTIONS</p>
      <h1><?= e($title) ?></h1>
    </div>
    <a href="index.php?page=profile&id=<?= (int)$user['id'] ?>" class="text-link">← Back to Profile</a>
  </div>

  <div class="connections-list">
    <?php if (empty($list)): ?>
      <div class="empty">
        <div class="empty-icon">👥</div>
        <h3>No users found</h3>
        <p class="muted">There are no users to show here yet.</p>
      </div>
    <?php else: ?>
      <?php foreach ($list as $person): ?>
        <?php $pId = (int)$person['id']; ?>
        <div class="connection-row">
          <a href="index.php?page=profile&id=<?= $pId ?>" class="connection-user">
            <?= avatar_markup($person, 'avatar small') ?>
            <div>
              <strong><?= e($person['full_name']) ?></strong>
              <small class="muted">@<?= e($person['username']) ?></small>
              <?php if (!empty($person['bio'])): ?>
                <p class="connection-bio muted"><?= e(mb_strimwidth($person['bio'], 0, 70, '...')) ?></p>
              <?php endif; ?>
            </div>
          </a>

          <?php if ($pId !== $myId): ?>
            <?php $isFol = $this->follows->isFollowing($myId, $pId); ?>
            <form method="post" action="index.php?page=follow_toggle" class="follow-form" data-user-id="<?= $pId ?>">
              <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
              <input type="hidden" name="user_id" value="<?= $pId ?>">
              <button type="submit" class="button mini follow-btn <?= $isFol ? 'secondary' : 'primary' ?>">
                <?= $isFol ? 'Following' : 'Follow' ?>
              </button>
            </form>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>

</main>
<?php require __DIR__ . '/../partials/footer.php'; ?>
