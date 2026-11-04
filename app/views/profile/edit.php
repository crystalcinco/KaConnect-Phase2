<?php
$title = 'Edit Profile';
require __DIR__ . '/../partials/header.php';
?>
<main id="main-content" class="container narrow">
<section class="card">
  <p class="eyebrow">PROFILE SETTINGS</p>
  <h1>Edit Profile</h1>

  <?php if (!empty($errors)): ?>
    <div class="alert error" role="alert">
      <?php foreach ($errors as $error): ?>
        <div><?= e($error) ?></div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <form method="post" action="index.php?page=profile_edit" enctype="multipart/form-data" id="profile-edit-form">
    <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
    <input type="hidden" name="profile_image_cropped" id="avatar-cropped-data" value="">
    <input type="hidden" name="cover_image_cropped" id="cover-cropped-data" value="">

    <!-- Cover Photo Section -->
    <fieldset class="edit-fieldset" id="cover-edit-section">
      <legend>Cover Photo</legend>
      <div class="cover-preview-box" id="cover-preview-box" style="<?= !empty($user['cover_image']) ? 'background-image: url(\'' . e($user['cover_image']) . '\');' : '' ?>">
        <?php if (empty($user['cover_image'])): ?>
          <span class="cover-placeholder-text">Default Gradient Banner</span>
        <?php endif; ?>
      </div>

      <div class="form-row-stacked">
        <label for="cover_image_input">Choose New Cover Photo</label>
        <input type="file" name="cover_image" id="cover_image_input" accept="image/jpeg,image/png,image/gif,image/webp" data-crop-aspect="2.67" data-target-hidden="cover-cropped-data" data-target-preview="cover-preview-box" data-is-banner="true">
        <div class="upload-note">JPG, PNG, WEBP · Recommended: 1200×450px · Maximum 5 MB</div>

        <div class="crop-btn-row" id="cover-crop-row" style="display: none;">
          <button type="button" class="button mini secondary" id="crop-cover-btn">✂ Crop Cover Photo</button>
        </div>

        <?php if (!empty($user['cover_image'])): ?>
          <label class="checkbox-label">
            <input type="checkbox" name="remove_cover" value="1">
            <span>Remove current cover photo (restore default gradient)</span>
          </label>
        <?php endif; ?>
      </div>
    </fieldset>

    <!-- Profile Avatar Section -->
    <fieldset class="edit-fieldset">
      <legend>Profile Picture</legend>
      <div class="avatar-edit-row">
        <div class="profile-edit-avatar" id="avatar-preview-wrap">
          <?= avatar_markup($user, 'sidebar-avatar') ?>
        </div>
        <div class="avatar-edit-inputs">
          <label for="profile_image_input">Upload New Picture</label>
          <input type="file" name="profile_image" id="profile_image_input" accept="image/jpeg,image/png,image/gif,image/webp" data-crop-aspect="1" data-target-hidden="avatar-cropped-data" data-target-preview="avatar-preview-wrap">
          <div class="upload-note">JPG, PNG, GIF, or WEBP · 1:1 Aspect ratio · Maximum 5 MB</div>

          <div class="crop-btn-row" id="avatar-crop-row" style="display: none;">
            <button type="button" class="button mini secondary" id="crop-avatar-btn">✂ Crop Avatar</button>
          </div>
        </div>
      </div>
    </fieldset>

    <!-- Basic Information -->
    <fieldset class="edit-fieldset">
      <legend>Personal Information</legend>
      <label for="full_name_input">Full Name</label>
      <input type="text" id="full_name_input" name="full_name" maxlength="100" value="<?= e($_POST['full_name'] ?? $user['full_name']) ?>" required>

      <label for="username_input">Username</label>
      <input type="text" id="username_input" name="username" maxlength="30" pattern="[A-Za-z0-9_]{3,30}" value="<?= e($_POST['username'] ?? $user['username']) ?>" required>
      <small class="muted">3–30 characters, letters, numbers, and underscores only.</small>

      <label for="bio_input">Bio</label>
      <textarea id="bio_input" name="bio" rows="4" maxlength="500"><?= e($_POST['bio'] ?? $user['bio']) ?></textarea>
    </fieldset>

    <div class="form-actions-row">
      <button class="primary" type="submit">Save Changes</button>
      <a class="button secondary" href="index.php?page=profile">Cancel</a>
    </div>
  </form>
</section>
</main>
<?php require __DIR__ . '/../partials/footer.php'; ?>
