<?php $title = 'Login'; require __DIR__ . '/../partials/header.php'; ?>
<main id="main-content" class="auth-shell"><section class="auth-card"><div class="auth-icon">K</div><p class="eyebrow" style="text-align:center">WELCOME TO KACONNECT</p><h1>Welcome back!</h1><p class="muted">Your community is just a login away.</p>
<?php if (!empty($errors)): ?><div class="alert error"><?php foreach ($errors as $error): ?><div><?= e($error) ?></div><?php endforeach; ?></div><?php endif; ?>
<form method="post" action="index.php?page=login"><input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>"><label>Username<input type="text" name="username" placeholder="Enter your username" value="<?= e($_POST['username'] ?? '') ?>" autocomplete="username" required></label>
<label>Password<input type="password" name="password" placeholder="Enter your password" autocomplete="current-password" required></label>
<button class="primary full" type="submit">Log in <span>→</span></button></form>
<p class="switch-link">Don’t have an account? <a href="index.php?page=register">Create an account</a></p></section></main>
<?php require __DIR__ . '/../partials/footer.php'; ?>
