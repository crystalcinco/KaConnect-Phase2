<?php
$title = 'Notifications';
require __DIR__ . '/../partials/header.php';
$me = auth_user();
?>
<main id="main-content" class="container narrow">

<div class="card">
  <div class="section-title">
    <div>
      <p class="eyebrow">ACTIVITY & UPDATES</p>
      <h1>Notifications</h1>
    </div>
    <form method="post" action="index.php?page=notifications_read" class="mark-all-form">
      <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
      <button type="submit" class="button secondary mini">Mark all as read</button>
    </form>
  </div>

  <div class="notifications-full-list">
    <?php if (empty($items)): ?>
      <div class="empty">
        <div class="empty-icon">🔔</div>
        <h3>No notifications yet</h3>
        <p class="muted">When classmates like your posts, comment, follow you, or message you, updates will show up here.</p>
        <a class="button primary" href="index.php?page=feed">Back to Newsfeed</a>
      </div>
    <?php else: ?>
      <?php foreach ($items as $item): ?>
        <?php
          $link = 'index.php?page=feed';
          $icon = '🔔';
          if ($item['type'] === 'like') {
            $link = 'index.php?page=feed#post-' . (int)$item['entity_id'];
            $icon = '♥';
          } elseif ($item['type'] === 'comment') {
            $link = 'index.php?page=feed#post-' . (int)$item['entity_id'];
            $icon = '💬';
          } elseif ($item['type'] === 'follow') {
            $link = 'index.php?page=profile&id=' . (int)$item['actor_id'];
            $icon = '👤';
          } elseif ($item['type'] === 'message') {
            $link = 'index.php?page=messages&user_id=' . (int)$item['actor_id'];
            $icon = '✉';
          }
          $isUnread = !(bool)$item['is_read'];
        ?>
        <a href="<?= e($link) ?>" class="notif-card-item <?= $isUnread ? 'unread' : '' ?>">
          <div class="notif-actor-avatar">
            <?= avatar_markup(['full_name' => $item['actor_name'], 'profile_image' => $item['actor_image']], 'avatar small') ?>
            <span class="notif-type-badge notif-type-<?= e($item['type']) ?>"><?= $icon ?></span>
          </div>
          <div class="notif-card-content">
            <p class="notif-card-msg"><?= e($item['message']) ?></p>
            <small class="muted notif-card-time"><?= time_ago($item['created_at']) ?></small>
          </div>
          <?php if ($isUnread): ?>
            <span class="unread-dot" title="Unread">●</span>
          <?php endif; ?>
        </a>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>

</main>
<?php require __DIR__ . '/../partials/footer.php'; ?>
