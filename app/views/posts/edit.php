<?php $title='Edit Post'; require __DIR__.'/../partials/header.php'; ?>
<main id="main-content" class="container narrow"><section class="card"><p class="eyebrow">POST MANAGEMENT</p><h1>Edit Post</h1>
<?php if ($message=flash('error')): ?><div class="alert error" role="alert"><?= e($message) ?></div><?php endif; ?>
<form method="post" action="index.php?page=post_edit&id=<?= (int)$post['id'] ?>" enctype="multipart/form-data"><input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="id" value="<?= (int)$post['id'] ?>"><label>Content<textarea name="content" rows="7" maxlength="2000" required><?= e($post['content']) ?></textarea></label>
<?php if (!empty($post['image'])): ?><div class="current-image"><p class="muted">Current image</p><img class="post-image" src="<?= e($post['image']) ?>" alt="Current post image"><label><input type="checkbox" name="remove_image" value="1"> Remove current image</label></div><?php endif; ?>
<label>Replace image (optional)<input type="file" name="image" accept="image/jpeg,image/png,image/gif,image/webp"></label><button class="primary" type="submit">Save Changes</button> <a class="button secondary" href="index.php?page=feed">Cancel</a></form></section></main>
<?php require __DIR__.'/../partials/footer.php'; ?>
