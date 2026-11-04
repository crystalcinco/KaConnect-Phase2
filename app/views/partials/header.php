<?php $authUser = auth_user(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="theme-color" content="#315c50">
<meta name="description" content="KaConnect — a friendly space to share ideas and stay connected.">
<meta name="csrf-token" content="<?= e(csrf_token()) ?>">
<title><?= e($title ?? 'KaConnect') ?> | KaConnect</title>
<script>
  (function() {
    try {
      var saved = localStorage.getItem('kaconnect_theme');
      var prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
      if (saved === 'dark' || (!saved && prefersDark)) {
        document.documentElement.setAttribute('data-theme', 'dark');
        document.documentElement.classList.add('dark-theme');
      } else {
        document.documentElement.removeAttribute('data-theme');
        document.documentElement.classList.remove('dark-theme');
      }
    } catch (e) {}
  })();

  function toggleTheme() {
    var isDark = document.documentElement.getAttribute('data-theme') === 'dark' || document.documentElement.classList.contains('dark-theme');
    var next = isDark ? 'light' : 'dark';
    if (next === 'dark') {
      document.documentElement.setAttribute('data-theme', 'dark');
      document.documentElement.classList.add('dark-theme');
      if (document.body) {
        document.body.setAttribute('data-theme', 'dark');
        document.body.classList.add('dark-theme');
      }
    } else {
      document.documentElement.removeAttribute('data-theme');
      document.documentElement.classList.remove('dark-theme');
      if (document.body) {
        document.body.removeAttribute('data-theme');
        document.body.classList.remove('dark-theme');
      }
    }
    try { localStorage.setItem('kaconnect_theme', next); } catch(e){}
    var btn = document.getElementById('theme-toggle');
    if (btn) {
      var icon = btn.querySelector('.theme-icon');
      if (icon) icon.textContent = (next === 'dark') ? '☀️' : '🌙';
      btn.setAttribute('title', (next === 'dark') ? 'Switch to light mode' : 'Switch to dark mode');
      btn.setAttribute('aria-label', (next === 'dark') ? 'Switch to light mode' : 'Switch to dark mode');
    }
  }
</script>
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&family=Manrope:wght@500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/style.css?v=4.2">
<link rel="stylesheet" href="assets/phase2.css?v=4.2">
<link rel="stylesheet" href="assets/phase3.css?v=4.2">
</head>
<body data-user-id="<?= $authUser ? (int)$authUser['id'] : '' ?>">
<a class="skip-link" href="#main-content">Skip to main content</a>
<header class="site-header"><div class="header-inner">
<a class="brand" href="index.php<?= $authUser ? '?page=feed' : '?page=login' ?>">
  <span class="brand-mark">K</span>
  <span>Ka<span class="brand-accent">Connect</span><small>YOUR CAMPUS. YOUR COMMUNITY.</small></span>
</a>

<div class="header-actions">
  <button type="button" class="theme-toggle" id="theme-toggle" onclick="toggleTheme()" aria-label="Toggle dark mode" title="Toggle dark mode">
    <span class="theme-icon">🌙</span>
  </button>

  <?php if ($authUser): ?>
  <nav class="main-nav" aria-label="Main navigation">
    <a href="index.php?page=feed" class="<?= ($title ?? '') === 'Newsfeed' ? 'active' : '' ?>">Home</a>
    <a href="index.php?page=members" class="<?= ($title ?? '') === 'Explore Community' ? 'active' : '' ?>">Find People</a>
    <a href="index.php?page=messages" class="<?= ($title ?? '') === 'Direct Messages' ? 'active' : '' ?> nav-msg-link">
      Messages <span id="msg-badge" class="badge-pill" style="display:none">0</span>
    </a>

    <div class="notif-wrapper">
      <button type="button" class="nav-notif-btn <?= ($title ?? '') === 'Notifications' ? 'active' : '' ?>" id="notif-bell-btn" aria-label="Notifications" aria-expanded="false">
        <span>🔔</span> <span class="notif-nav-label">Alerts</span>
        <span id="notif-badge" class="badge-pill" style="display:none">0</span>
      </button>

      <div class="notif-dropdown" id="notif-dropdown" aria-hidden="true">
        <div class="notif-dropdown-header">
          <strong>Notifications</strong>
          <button type="button" id="notif-mark-all" class="notif-mark-btn">Mark all read</button>
        </div>
        <div class="notif-dropdown-list" id="notif-dropdown-list">
          <div class="notif-empty">No notifications yet</div>
        </div>
        <a href="index.php?page=notifications" class="notif-dropdown-footer">See all notifications →</a>
      </div>
    </div>

    <a href="index.php?page=profile" class="<?= (!empty($_GET['page']) && $_GET['page'] === 'profile' && empty($_GET['id']) && empty($_GET['username'])) ? 'active' : '' ?>">My Profile</a>
    <a href="index.php?page=profile_edit" class="<?= ($title ?? '') === 'Edit Profile' ? 'active' : '' ?>">Settings</a>

    <form method="post" action="index.php?page=logout" class="logout-form">
      <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
      <button type="submit" class="nav-logout">Log out</button>
    </form>
  </nav>
  <?php endif; ?>
</div>

</div></header>

<div id="toast-container" class="toast-container" aria-live="polite"></div>

<?php if ($message = flash('success')): ?><div class="alert success" role="status"><span>✓</span><?= e($message) ?></div><?php endif; ?>
<?php if ($message = flash('error')): ?><div class="alert error" role="alert"><span>!</span><?= e($message) ?></div><?php endif; ?>
