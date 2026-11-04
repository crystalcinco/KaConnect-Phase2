<?php $title='Register'; require __DIR__.'/../partials/header.php'; ?>
<main id="main-content" class="auth-shell"><section class="auth-card"><div class="auth-icon">K</div><p class="eyebrow" style="text-align:center">WELCOME TO KACONNECT</p><h1>Find your people.</h1><p class="muted">Create your KaConnect account and join the conversation.</p>
<?php if (!empty($errors)): ?><div class="alert error" role="alert"><?php foreach ($errors as $error): ?><div><?= e($error) ?></div><?php endforeach; ?></div><?php endif; ?>
<form method="post" action="index.php?page=register"><input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>"><label>Full name<input type="text" name="full_name" placeholder="How should we call you?" value="<?= e($_POST['full_name']??'') ?>" maxlength="100" autocomplete="name" required></label>
<label>Username<input type="text" name="username" placeholder="3–30 letters, numbers, or _" value="<?= e($_POST['username']??'') ?>" maxlength="30" pattern="[A-Za-z0-9_]{3,30}" autocomplete="username" required></label>
<label>Password<input type="password" name="password" placeholder="At least 8 characters" minlength="8" autocomplete="new-password" required></label>
<label>Confirm password<input type="password" name="password_confirmation" placeholder="Enter your password again" minlength="8" autocomplete="new-password" required></label>
<button class="primary full" type="submit">Create account <span>→</span></button></form><p class="switch-link">Already part of KaConnect? <a href="index.php?page=login">Log in here</a></p></section></main>
<?php require __DIR__.'/../partials/footer.php'; ?>
