/**
 * KaConnect — Interactive Frontend Core
 * Handles:
 * 1. Dark Mode Toggle & Persistence
 * 2. AJAX Post Creation, Liking, and Dynamic Comments
 * 3. Real-time Notifications Polling & Toast Alerts
 * 4. Follow/Unfollow Dynamic Toggle
 * 5. Direct Messaging Live Thread & Polling
 * 6. File Validation & Interactive Canvas Image Cropper
 */

document.addEventListener('DOMContentLoaded', () => {
  const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
  const currentUserId = document.body.dataset.userId ? parseInt(document.body.dataset.userId, 10) : null;

  /* ==========================================================================
     1. TOAST NOTIFICATIONS HELPER
     ========================================================================== */
  const toastContainer = document.getElementById('toast-container');
  function showToast(title, message, icon = '🔔', link = null, duration = 4500) {
    if (!toastContainer) return;
    const toast = document.createElement('div');
    toast.className = 'toast';
    toast.innerHTML = `
      <div class="toast-icon">${icon}</div>
      <div class="toast-text">
        ${title ? `<strong>${escapeHtml(title)}</strong>` : ''}
        <span>${escapeHtml(message)}</span>
      </div>
    `;

    if (link) {
      toast.addEventListener('click', () => { window.location.href = link; });
    }

    toastContainer.appendChild(toast);

    setTimeout(() => {
      toast.classList.add('toast-leave');
      setTimeout(() => toast.remove(), 250);
    }, duration);
  }

  function escapeHtml(str) {
    if (!str) return '';
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
  }

  /* ==========================================================================
     2. DARK MODE TOGGLE
     ========================================================================== */
  const themeToggle = document.getElementById('theme-toggle');
  const updateThemeIcon = (isDark) => {
    if (!themeToggle) return;
    const icon = themeToggle.querySelector('.theme-icon');
    if (icon) icon.textContent = isDark ? '☀️' : '🌙';
    themeToggle.setAttribute('title', isDark ? 'Switch to light mode' : 'Switch to dark mode');
    themeToggle.setAttribute('aria-label', isDark ? 'Switch to light mode' : 'Switch to dark mode');
  };

  const isCurrentDark = document.documentElement.getAttribute('data-theme') === 'dark' || document.documentElement.classList.contains('dark-theme');
  updateThemeIcon(isCurrentDark);

  if (themeToggle && !themeToggle.onclick) {
    themeToggle.addEventListener('click', () => {
      if (typeof window.toggleTheme === 'function') {
        window.toggleTheme();
      } else {
        const isDark = document.documentElement.getAttribute('data-theme') === 'dark' || document.documentElement.classList.contains('dark-theme');
        const next = isDark ? 'light' : 'dark';
        if (next === 'dark') {
          document.documentElement.setAttribute('data-theme', 'dark');
          document.documentElement.classList.add('dark-theme');
        } else {
          document.documentElement.removeAttribute('data-theme');
          document.documentElement.classList.remove('dark-theme');
        }
        try { localStorage.setItem('kaconnect_theme', next); } catch (e) {}
        updateThemeIcon(next === 'dark');
      }
    });
  }

  /* ==========================================================================
     3. CHARACTER COUNTERS
     ========================================================================== */
  document.querySelectorAll('textarea[maxlength]').forEach((area) => {
    if (area.parentElement.querySelector('.character-count')) return;
    const counter = document.createElement('small');
    counter.className = 'muted character-count';
    counter.setAttribute('aria-live', 'polite');
    area.insertAdjacentElement('afterend', counter);
    const update = () => {
      counter.textContent = `${area.value.length} / ${area.maxLength} characters`;
      counter.classList.toggle('near-limit', area.maxLength - area.value.length < 80);
    };
    area.addEventListener('input', update);
    update();
  });

  /* ==========================================================================
     4. FILE VALIDATION
     ========================================================================== */
  const ALLOWED_TYPES = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
  const MAX_FILE_SIZE = 5 * 1024 * 1024; // 5 MB

  function validateImageFile(file) {
    if (!file) return { valid: false, error: 'No file selected.' };
    if (!ALLOWED_TYPES.includes(file.type)) {
      return { valid: false, error: 'Only JPG, PNG, GIF, and WEBP images are allowed.' };
    }
    if (file.size > MAX_FILE_SIZE) {
      const sizeMb = (file.size / (1024 * 1024)).toFixed(1);
      return { valid: false, error: `Image is too large (${sizeMb} MB). Maximum allowed is 5 MB.` };
    }
    return { valid: true };
  }

  // Generic file input listener for file validation feedback
  document.querySelectorAll('input[type="file"]').forEach((input) => {
    input.addEventListener('change', () => {
      const file = input.files && input.files[0];
      if (!file) return;

      const check = validateImageFile(file);
      if (!check.valid) {
        showToast('Invalid File', check.error, '⚠️');
        input.value = '';
        return;
      }

      let note = input.parentElement.querySelector('.file-name');
      if (!note) {
        note = document.createElement('small');
        note.className = 'file-name muted';
        input.parentElement.appendChild(note);
      }
      note.textContent = `Selected: ${file.name} (${(file.size / 1024).toFixed(0)} KB)`;
    });
  });

  /* ==========================================================================
     5. INTERACTIVE CANVAS IMAGE CROPPER
     ========================================================================== */
  const cropperModal = document.getElementById('cropper-modal');
  const cropperCanvas = document.getElementById('cropper-canvas');
  const cropperMask = document.getElementById('cropper-mask');
  const cropperStage = document.getElementById('cropper-stage');
  const zoomSlider = document.getElementById('cropper-zoom-slider');
  const zoomInBtn = document.getElementById('cropper-zoom-in');
  const zoomOutBtn = document.getElementById('cropper-zoom-out');
  const rotateBtn = document.getElementById('cropper-rotate-btn');
  const applyCropBtn = document.getElementById('cropper-apply-btn');
  const cancelCropBtn = document.getElementById('cropper-cancel-btn');
  const closeCropBtn = document.getElementById('cropper-close-btn');

  let cropperState = {
    image: null,
    aspectRatio: 1, // width / height
    isCircle: false,
    zoom: 1,
    rotation: 0,
    offsetX: 0,
    offsetY: 0,
    isDragging: false,
    startX: 0,
    startY: 0,
    onApply: null
  };

  function openCropper(imageSrc, aspectRatio = 1, isCircle = false, onApplyCallback) {
    if (!cropperModal || !cropperCanvas) return;
    const img = new Image();
    img.crossOrigin = 'anonymous';
    img.onload = () => {
      cropperState.image = img;
      cropperState.aspectRatio = aspectRatio;
      cropperState.isCircle = isCircle;
      cropperState.zoom = 1;
      cropperState.rotation = 0;
      cropperState.offsetX = 0;
      cropperState.offsetY = 0;
      cropperState.onApply = onApplyCallback;

      if (zoomSlider) zoomSlider.value = 1;
      cropperModal.classList.add('show');
      cropperModal.setAttribute('aria-hidden', 'false');

      updateCropperMask();
      renderCropperCanvas();
    };
    img.src = imageSrc;
  }

  function closeCropper() {
    if (!cropperModal) return;
    cropperModal.classList.remove('show');
    cropperModal.setAttribute('aria-hidden', 'true');
    cropperState.image = null;
    cropperState.onApply = null;
  }

  function updateCropperMask() {
    if (!cropperMask || !cropperStage) return;
    const stageW = cropperStage.clientWidth || 500;
    const stageH = cropperStage.clientHeight || 340;
    let maskW, maskH;

    if (stageW / stageH > cropperState.aspectRatio) {
      maskH = stageH * 0.84;
      maskW = maskH * cropperState.aspectRatio;
    } else {
      maskW = stageW * 0.88;
      maskH = maskW / cropperState.aspectRatio;
    }

    cropperMask.style.width = `${maskW}px`;
    cropperMask.style.height = `${maskH}px`;
    cropperMask.style.left = `${(stageW - maskW) / 2}px`;
    cropperMask.style.top = `${(stageH - maskH) / 2}px`;
    cropperMask.classList.toggle('circle', cropperState.isCircle);

    cropperCanvas.width = stageW;
    cropperCanvas.height = stageH;
  }

  function renderCropperCanvas() {
    if (!cropperCanvas || !cropperState.image) return;
    const ctx = cropperCanvas.getContext('2d');
    const width = cropperCanvas.width;
    const height = cropperCanvas.height;
    ctx.clearRect(0, 0, width, height);

    ctx.save();
    ctx.translate(width / 2 + cropperState.offsetX, height / 2 + cropperState.offsetY);
    ctx.rotate((cropperState.rotation * Math.PI) / 180);
    ctx.scale(cropperState.zoom, cropperState.zoom);

    const img = cropperState.image;
    // Fit image to canvas view initially
    const scale = Math.max(width / img.naturalWidth, height / img.naturalHeight) * 0.8;
    const drawW = img.naturalWidth * scale;
    const drawH = img.naturalHeight * scale;

    ctx.drawImage(img, -drawW / 2, -drawH / 2, drawW, drawH);
    ctx.restore();
  }

  if (cropperStage) {
    cropperStage.addEventListener('mousedown', (e) => {
      cropperState.isDragging = true;
      cropperState.startX = e.clientX - cropperState.offsetX;
      cropperState.startY = e.clientY - cropperState.offsetY;
    });

    window.addEventListener('mousemove', (e) => {
      if (!cropperState.isDragging) return;
      cropperState.offsetX = e.clientX - cropperState.startX;
      cropperState.offsetY = e.clientY - cropperState.startY;
      renderCropperCanvas();
    });

    window.addEventListener('mouseup', () => { cropperState.isDragging = false; });

    // Touch support for mobile dragging
    cropperStage.addEventListener('touchstart', (e) => {
      if (e.touches.length === 1) {
        cropperState.isDragging = true;
        cropperState.startX = e.touches[0].clientX - cropperState.offsetX;
        cropperState.startY = e.touches[0].clientY - cropperState.offsetY;
      }
    }, { passive: true });

    cropperStage.addEventListener('touchmove', (e) => {
      if (cropperState.isDragging && e.touches.length === 1) {
        cropperState.offsetX = e.touches[0].clientX - cropperState.startX;
        cropperState.offsetY = e.touches[0].clientY - cropperState.startY;
        renderCropperCanvas();
      }
    }, { passive: true });

    cropperStage.addEventListener('touchend', () => { cropperState.isDragging = false; });
  }

  if (zoomSlider) {
    zoomSlider.addEventListener('input', () => {
      cropperState.zoom = parseFloat(zoomSlider.value);
      renderCropperCanvas();
    });
  }

  if (zoomInBtn) {
    zoomInBtn.addEventListener('click', () => {
      cropperState.zoom = Math.min(3, cropperState.zoom + 0.15);
      if (zoomSlider) zoomSlider.value = cropperState.zoom;
      renderCropperCanvas();
    });
  }

  if (zoomOutBtn) {
    zoomOutBtn.addEventListener('click', () => {
      cropperState.zoom = Math.max(0.4, cropperState.zoom - 0.15);
      if (zoomSlider) zoomSlider.value = cropperState.zoom;
      renderCropperCanvas();
    });
  }

  if (rotateBtn) {
    rotateBtn.addEventListener('click', () => {
      cropperState.rotation = (cropperState.rotation + 90) % 360;
      renderCropperCanvas();
    });
  }

  if (closeCropBtn) closeCropBtn.addEventListener('click', closeCropper);
  if (cancelCropBtn) cancelCropBtn.addEventListener('click', closeCropper);

  if (applyCropBtn) {
    applyCropBtn.addEventListener('click', () => {
      if (!cropperCanvas || !cropperMask) return;
      const maskRect = cropperMask.getBoundingClientRect();
      const stageRect = cropperStage.getBoundingClientRect();

      const cropX = maskRect.left - stageRect.left;
      const cropY = maskRect.top - stageRect.top;
      const cropW = maskRect.width;
      const cropH = maskRect.height;

      // Create destination canvas with crop dimensions
      const outCanvas = document.createElement('canvas');
      const targetW = cropperState.aspectRatio >= 2 ? 1200 : 600;
      const targetH = Math.round(targetW / cropperState.aspectRatio);
      outCanvas.width = targetW;
      outCanvas.height = targetH;

      const outCtx = outCanvas.getContext('2d');
      outCtx.drawImage(cropperCanvas, cropX, cropY, cropW, cropH, 0, 0, targetW, targetH);

      const croppedDataUrl = outCanvas.toDataURL('image/jpeg', 0.9);
      if (typeof cropperState.onApply === 'function') {
        cropperState.onApply(croppedDataUrl);
      }
      closeCropper();
    });
  }

  /* -------------------------------------------------------------------------
     Post Composer Cropper & Image Preview Integration
     ------------------------------------------------------------------------- */
  const composerFileInput = document.getElementById('composer-file-input');
  const composerPreviewWrap = document.getElementById('composer-preview-wrap');
  const composerPreviewImg = document.getElementById('composer-preview-img');
  const composerCropBtn = document.getElementById('composer-crop-btn');
  const composerRemoveBtn = document.getElementById('composer-remove-img-btn');
  const composerCroppedHidden = document.getElementById('composer-image-cropped');

  let composerRawImageSrc = null;

  if (composerFileInput && composerPreviewWrap && composerPreviewImg) {
    composerFileInput.addEventListener('change', () => {
      const file = composerFileInput.files && composerFileInput.files[0];
      if (!file) return;

      const check = validateImageFile(file);
      if (!check.valid) {
        showToast('Invalid Image', check.error, '⚠️');
        composerFileInput.value = '';
        return;
      }

      const reader = new FileReader();
      reader.onload = (e) => {
        composerRawImageSrc = e.target.result;
        composerPreviewImg.src = composerRawImageSrc;
        composerPreviewWrap.style.display = 'block';
        if (composerCroppedHidden) composerCroppedHidden.value = '';
      };
      reader.readAsDataURL(file);
    });

    if (composerCropBtn) {
      composerCropBtn.addEventListener('click', () => {
        if (!composerRawImageSrc) return;
        openCropper(composerRawImageSrc, 16 / 10, false, (croppedUrl) => {
          composerPreviewImg.src = croppedUrl;
          if (composerCroppedHidden) composerCroppedHidden.value = croppedUrl;
          showToast('Image Cropped', 'Your photo has been adjusted.', '✂');
        });
      });
    }

    if (composerRemoveBtn) {
      composerRemoveBtn.addEventListener('click', () => {
        composerFileInput.value = '';
        if (composerCroppedHidden) composerCroppedHidden.value = '';
        composerRawImageSrc = null;
        composerPreviewWrap.style.display = 'none';
        composerPreviewImg.src = '';
      });
    }
  }

  /* -------------------------------------------------------------------------
     Profile Edit Avatar & Cover Cropper Integration
     ------------------------------------------------------------------------- */
  const avatarInput = document.getElementById('profile_image_input');
  const avatarCroppedHidden = document.getElementById('avatar-cropped-data');
  const avatarCropRow = document.getElementById('avatar-crop-row');
  const cropAvatarBtn = document.getElementById('crop-avatar-btn');
  const avatarPreviewWrap = document.getElementById('avatar-preview-wrap');
  let avatarRawSrc = null;

  if (avatarInput) {
    avatarInput.addEventListener('change', () => {
      const file = avatarInput.files && avatarInput.files[0];
      if (!file) return;

      const check = validateImageFile(file);
      if (!check.valid) {
        showToast('Invalid Avatar', check.error, '⚠️');
        avatarInput.value = '';
        return;
      }

      const reader = new FileReader();
      reader.onload = (e) => {
        avatarRawSrc = e.target.result;
        if (avatarCropRow) avatarCropRow.style.display = 'block';
        openCropper(avatarRawSrc, 1, true, (croppedUrl) => {
          if (avatarCroppedHidden) avatarCroppedHidden.value = croppedUrl;
          if (avatarPreviewWrap) {
            avatarPreviewWrap.innerHTML = `<img class="sidebar-avatar avatar-photo" src="${croppedUrl}" alt="Avatar preview">`;
          }
        });
      };
      reader.readAsDataURL(file);
    });

    if (cropAvatarBtn) {
      cropAvatarBtn.addEventListener('click', () => {
        if (!avatarRawSrc) return;
        openCropper(avatarRawSrc, 1, true, (croppedUrl) => {
          if (avatarCroppedHidden) avatarCroppedHidden.value = croppedUrl;
          if (avatarPreviewWrap) {
            avatarPreviewWrap.innerHTML = `<img class="sidebar-avatar avatar-photo" src="${croppedUrl}" alt="Avatar preview">`;
          }
        });
      });
    }
  }

  const coverInput = document.getElementById('cover_image_input');
  const coverCroppedHidden = document.getElementById('cover-cropped-data');
  const coverCropRow = document.getElementById('cover-crop-row');
  const cropCoverBtn = document.getElementById('crop-cover-btn');
  const coverPreviewBox = document.getElementById('cover-preview-box');
  let coverRawSrc = null;

  if (coverInput) {
    coverInput.addEventListener('change', () => {
      const file = coverInput.files && coverInput.files[0];
      if (!file) return;

      const check = validateImageFile(file);
      if (!check.valid) {
        showToast('Invalid Cover Photo', check.error, '⚠️');
        coverInput.value = '';
        return;
      }

      const reader = new FileReader();
      reader.onload = (e) => {
        coverRawSrc = e.target.result;
        if (coverCropRow) coverCropRow.style.display = 'block';
        openCropper(coverRawSrc, 2.67, false, (croppedUrl) => {
          if (coverCroppedHidden) coverCroppedHidden.value = croppedUrl;
          if (coverPreviewBox) {
            coverPreviewBox.style.backgroundImage = `url('${croppedUrl}')`;
            coverPreviewBox.innerHTML = '';
          }
        });
      };
      reader.readAsDataURL(file);
    });

    if (cropCoverBtn) {
      cropCoverBtn.addEventListener('click', () => {
        if (!coverRawSrc) return;
        openCropper(coverRawSrc, 2.67, false, (croppedUrl) => {
          if (coverCroppedHidden) coverCroppedHidden.value = croppedUrl;
          if (coverPreviewBox) {
            coverPreviewBox.style.backgroundImage = `url('${croppedUrl}')`;
            coverPreviewBox.innerHTML = '';
          }
        });
      });
    }
  }

  /* ==========================================================================
     6. AJAX-BASED POST CREATION
     ========================================================================== */
  const composerForm = document.getElementById('composer-form');
  const postsStream = document.getElementById('posts-stream');
  const emptyFeedCard = document.getElementById('empty-feed-card');
  const composerSubmitBtn = document.getElementById('composer-submit-btn');

  if (composerForm && postsStream) {
    composerForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      const contentInput = document.getElementById('composer-content');
      if (!contentInput || !contentInput.value.trim()) return;

      const submitBtnText = composerSubmitBtn ? composerSubmitBtn.querySelector('.btn-text') : null;
      const originalText = submitBtnText ? submitBtnText.textContent : 'Share post';

      try {
        if (composerSubmitBtn) composerSubmitBtn.disabled = true;
        if (submitBtnText) submitBtnText.textContent = 'Posting...';

        const formData = new FormData(composerForm);

        const response = await fetch('index.php?page=post_create', {
          method: 'POST',
          headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
          },
          body: formData
        });

        const data = await response.json();

        if (response.ok && data.success && data.html) {
          // Prepend newly created post
          const temp = document.createElement('div');
          temp.innerHTML = data.html.trim();
          const newPost = temp.firstElementChild;

          if (emptyFeedCard) emptyFeedCard.remove();
          postsStream.prepend(newPost);

          // Reset composer
          composerForm.reset();
          if (composerCroppedHidden) composerCroppedHidden.value = '';
          if (composerPreviewWrap) composerPreviewWrap.style.display = 'none';
          if (composerPreviewImg) composerPreviewImg.src = '';
          composerRawImageSrc = null;

          // Trigger textarea input event to reset char counter
          contentInput.dispatchEvent(new Event('input'));

          showToast('Post Shared', 'Your update is now live on the feed.', '✓');
        } else {
          showToast('Failed to Post', data.error || 'Unable to publish your post.', '✕');
        }
      } catch (err) {
        showToast('Error', 'Network error. Please try again.', '⚠️');
      } finally {
        if (composerSubmitBtn) composerSubmitBtn.disabled = false;
        if (submitBtnText) submitBtnText.textContent = originalText;
      }
    });
  }

  /* ==========================================================================
     7. DYNAMIC LIKES
     ========================================================================== */
  document.addEventListener('submit', async (e) => {
    const form = e.target.closest('.like-form');
    if (!form) return;

    e.preventDefault();
    const btn = form.querySelector('button');
    const countEl = form.querySelector('.like-count');
    const iconEl = form.querySelector('.like-icon');

    try {
      const formData = new FormData(form);
      const response = await fetch('index.php?page=like', {
        method: 'POST',
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          'Accept': 'application/json'
        },
        body: formData
      });

      const data = await response.json();
      if (response.ok && data.success) {
        if (btn) btn.classList.toggle('liked', data.liked);
        if (iconEl) iconEl.textContent = data.liked ? '♥' : '♡';
        if (countEl) countEl.textContent = data.like_count;
      }
    } catch (err) {
      // Fallback: submit normally if fetch fails
      form.submit();
    }
  });

  /* ==========================================================================
     8. DYNAMIC COMMENTS (CREATE, EDIT, DELETE)
     ========================================================================== */
  // Create comment
  document.addEventListener('submit', async (e) => {
    const form = e.target.closest('.dynamic-comment-form');
    if (!form) return;

    e.preventDefault();
    const input = form.querySelector('input[name="content"]');
    if (!input || !input.value.trim()) return;

    const postCard = form.closest('.post');
    const commentsList = postCard ? postCard.querySelector('.comments-list') : null;
    const countHeader = postCard ? postCard.querySelector('.comment-count-header') : null;
    const countBadge = postCard ? postCard.querySelector('.comment-count-badge') : null;

    try {
      const formData = new FormData(form);
      const response = await fetch('index.php?page=comment_create', {
        method: 'POST',
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          'Accept': 'application/json'
        },
        body: formData
      });

      const data = await response.json();
      if (response.ok && data.success && data.html) {
        const temp = document.createElement('div');
        temp.innerHTML = data.html.trim();
        const newComment = temp.firstElementChild;

        if (commentsList) commentsList.appendChild(newComment);
        if (countHeader) countHeader.textContent = data.comment_count;
        if (countBadge) countBadge.textContent = data.comment_count;

        input.value = '';
      } else {
        showToast('Comment Error', data.error || 'Failed to post comment.', '⚠️');
      }
    } catch (err) {
      showToast('Error', 'Unable to post comment.', '⚠️');
    }
  });

  // Edit comment
  document.addEventListener('submit', async (e) => {
    const form = e.target.closest('.comment-edit-form');
    if (!form) return;

    e.preventDefault();
    const commentEl = form.closest('.comment');
    const input = form.querySelector('input[name="content"]');
    if (!input || !input.value.trim()) return;

    try {
      const formData = new FormData(form);
      const response = await fetch('index.php?page=comment_edit', {
        method: 'POST',
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          'Accept': 'application/json'
        },
        body: formData
      });

      const data = await response.json();
      if (response.ok && data.success) {
        const bodyP = commentEl ? commentEl.querySelector('.comment-body p') : null;
        if (bodyP) bodyP.textContent = data.content;
        showToast('Updated', 'Comment updated.', '✓', null, 2500);
      } else {
        showToast('Error', data.error || 'Unable to update comment.', '⚠️');
      }
    } catch (err) {
      showToast('Error', 'Network error while editing comment.', '⚠️');
    }
  });

  // Delete comment
  document.addEventListener('submit', async (e) => {
    const form = e.target.closest('.comment-delete-form');
    if (!form) return;

    e.preventDefault();
    if (!confirm('Delete this comment?')) return;

    const commentEl = form.closest('.comment');
    const postCard = form.closest('.post');
    const countHeader = postCard ? postCard.querySelector('.comment-count-header') : null;
    const countBadge = postCard ? postCard.querySelector('.comment-count-badge') : null;

    try {
      const formData = new FormData(form);
      const response = await fetch('index.php?page=comment_delete', {
        method: 'POST',
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          'Accept': 'application/json'
        },
        body: formData
      });

      const data = await response.json();
      if (response.ok && data.success) {
        if (commentEl) {
          commentEl.style.transition = 'opacity .2s, transform .2s';
          commentEl.style.opacity = '0';
          commentEl.style.transform = 'translateY(-6px)';
          setTimeout(() => {
            commentEl.remove();
            if (countHeader) {
              const cur = Math.max(0, parseInt(countHeader.textContent, 10) - 1);
              countHeader.textContent = cur;
              if (countBadge) countBadge.textContent = cur;
            }
          }, 200);
        }
      }
    } catch (err) {
      showToast('Error', 'Could not delete comment.', '⚠️');
    }
  });

  /* ==========================================================================
     9. FOLLOW / UNFOLLOW DYNAMIC TOGGLE
     ========================================================================== */
  document.addEventListener('submit', async (e) => {
    const form = e.target.closest('.follow-form');
    if (!form) return;

    e.preventDefault();
    const btn = form.querySelector('.follow-btn');
    const followerCountEl = document.getElementById('profile-follower-count');

    try {
      const formData = new FormData(form);
      const response = await fetch('index.php?page=follow_toggle', {
        method: 'POST',
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          'Accept': 'application/json'
        },
        body: formData
      });

      const data = await response.json();
      if (response.ok && data.success) {
        if (btn) {
          btn.textContent = data.button_text;
          btn.classList.toggle('primary', !data.is_following);
          btn.classList.toggle('secondary', data.is_following);
        }
        if (followerCountEl && typeof data.follower_count === 'number') {
          followerCountEl.textContent = data.follower_count;
        }
        showToast('Connections', data.message, data.is_following ? '👤+' : '👤-');
      }
    } catch (err) {
      form.submit();
    }
  });

  /* ==========================================================================
     10. REAL-TIME NOTIFICATIONS POLLING & DROPDOWN
     ========================================================================== */
  const notifBellBtn = document.getElementById('notif-bell-btn');
  const notifDropdown = document.getElementById('notif-dropdown');
  const notifBadge = document.getElementById('notif-badge');
  const msgBadge = document.getElementById('msg-badge');
  const notifList = document.getElementById('notif-dropdown-list');
  const notifMarkAll = document.getElementById('notif-mark-all');

  let seenNotificationIds = new Set();
  let initialPollDone = false;

  // Toggle notification dropdown
  if (notifBellBtn && notifDropdown) {
    notifBellBtn.addEventListener('click', (e) => {
      e.stopPropagation();
      const isExpanded = notifDropdown.classList.toggle('show');
      notifBellBtn.setAttribute('aria-expanded', isExpanded ? 'true' : 'false');
    });

    document.addEventListener('click', (e) => {
      if (!notifDropdown.contains(e.target) && !notifBellBtn.contains(e.target)) {
        notifDropdown.classList.remove('show');
        notifBellBtn.setAttribute('aria-expanded', 'false');
      }
    });
  }

  // Mark all notifications read
  if (notifMarkAll) {
    notifMarkAll.addEventListener('click', async () => {
      try {
        const formData = new FormData();
        formData.append('_csrf', csrfToken);
        const res = await fetch('index.php?page=notifications_read', {
          method: 'POST',
          headers: { 'X-Requested-With': 'XMLHttpRequest' },
          body: formData
        });
        const data = await res.json();
        if (res.ok && data.success) {
          if (notifBadge) notifBadge.style.display = 'none';
          document.querySelectorAll('.notif-item.unread').forEach(el => el.classList.remove('unread'));
        }
      } catch (e) {}
    });
  }

  async function pollNotifications() {
    if (!currentUserId) return;
    try {
      const res = await fetch('index.php?page=notifications_poll', {
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
      });
      if (!res.ok) return;

      const data = await res.json();
      if (!data.success) return;

      // Update notification badge
      if (notifBadge) {
        if (data.unread_count > 0) {
          notifBadge.textContent = data.unread_count;
          notifBadge.style.display = 'inline-block';
        } else {
          notifBadge.style.display = 'none';
        }
      }

      // Update message badge
      if (msgBadge) {
        if (data.unread_messages > 0) {
          msgBadge.textContent = data.unread_messages;
          msgBadge.style.display = 'inline-block';
        } else {
          msgBadge.style.display = 'none';
        }
      }

      // Render dropdown items
      if (notifList && Array.isArray(data.notifications)) {
        if (data.notifications.length === 0) {
          notifList.innerHTML = '<div class="notif-empty">No notifications yet</div>';
        } else {
          notifList.innerHTML = data.notifications.map(n => `
            <a href="${escapeHtml(n.link)}" class="notif-item ${n.is_read ? '' : 'unread'}">
              <div class="notif-item-body">
                <p>${escapeHtml(n.message)}</p>
                <small class="muted">${escapeHtml(n.time_ago)}</small>
              </div>
            </a>
          `).join('');
        }

        // Check for new notifications to fire real-time toast
        if (initialPollDone) {
          data.notifications.forEach(n => {
            if (!n.is_read && !seenNotificationIds.has(n.id)) {
              let icon = '🔔';
              if (n.type === 'like') icon = '♥';
              if (n.type === 'comment') icon = '💬';
              if (n.type === 'follow') icon = '👤+';
              if (n.type === 'message') icon = '✉';

              showToast('KaConnect Alert', n.message, icon, n.link);
            }
          });
        }

        data.notifications.forEach(n => seenNotificationIds.add(n.id));
        initialPollDone = true;
      }
    } catch (e) {
      // Background poll silently fails on network drop
    }
  }

  // Poll notifications every 6 seconds if logged in
  if (currentUserId) {
    pollNotifications();
    setInterval(pollNotifications, 6000);
  }

  /* ==========================================================================
     11. DIRECT MESSAGING (CHAT LIVE THREAD & POLLING)
     ========================================================================== */
  const chatMessages = document.getElementById('chat-messages');
  const chatComposer = document.getElementById('chat-composer-form');
  const chatMessageInput = document.getElementById('chat-message-input');
  const chatFilterInput = document.getElementById('chat-filter-input');
  const newChatToggleBtn = document.getElementById('new-chat-toggle-btn');
  const newChatPicker = document.getElementById('new-chat-picker');
  const closePickerBtn = document.getElementById('close-picker-btn');

  function scrollChatToBottom(smooth = false) {
    if (!chatMessages) return;
    chatMessages.scrollTo({
      top: chatMessages.scrollHeight,
      behavior: smooth ? 'smooth' : 'auto'
    });
  }

  if (chatMessages) {
    scrollChatToBottom(false);

    const otherUserId = parseInt(chatMessages.dataset.userId || '0', 10);
    let lastMsgId = parseInt(chatMessages.dataset.lastId || '0', 10);

    // Send message via AJAX
    if (chatComposer && chatMessageInput) {
      chatComposer.addEventListener('submit', async (e) => {
        e.preventDefault();
        const text = chatMessageInput.value.trim();
        if (!text) return;

        try {
          const formData = new FormData(chatComposer);
          chatMessageInput.value = '';

          const res = await fetch('index.php?page=message_send', {
            method: 'POST',
            headers: {
              'X-Requested-With': 'XMLHttpRequest',
              'Accept': 'application/json'
            },
            body: formData
          });

          const data = await res.json();
          if (res.ok && data.success) {
            lastMsgId = Math.max(lastMsgId, data.message_id);

            // Append bubble
            const bubbleWrap = document.createElement('div');
            bubbleWrap.className = 'msg-bubble-wrap msg-outgoing';
            bubbleWrap.setAttribute('data-msg-id', data.message_id);
            bubbleWrap.innerHTML = `
              <div class="msg-bubble">
                <div class="msg-text">${escapeHtml(data.message).replace(/\n/g, '<br>')}</div>
                <div class="msg-meta muted">${escapeHtml(data.created_at)}</div>
              </div>
            `;
            chatMessages.appendChild(bubbleWrap);
            scrollChatToBottom(true);
          } else {
            showToast('Message Error', data.error || 'Failed to send message.', '⚠️');
          }
        } catch (err) {
          showToast('Error', 'Unable to send message.', '⚠️');
        }
      });
    }

    // Live chat polling every 3 seconds for active conversation
    if (otherUserId > 0) {
      setInterval(async () => {
        try {
          const res = await fetch(`index.php?page=messages_poll&user_id=${otherUserId}&after_id=${lastMsgId}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
          });
          if (!res.ok) return;

          const data = await res.json();
          if (data.success && Array.isArray(data.messages) && data.messages.length > 0) {
            data.messages.forEach(msg => {
              if (msg.id > lastMsgId) {
                lastMsgId = msg.id;
                const bubbleWrap = document.createElement('div');
                bubbleWrap.className = `msg-bubble-wrap ${msg.is_me ? 'msg-outgoing' : 'msg-incoming'}`;
                bubbleWrap.setAttribute('data-msg-id', msg.id);
                bubbleWrap.innerHTML = `
                  <div class="msg-bubble">
                    <div class="msg-text">${escapeHtml(msg.message).replace(/\n/g, '<br>')}</div>
                    <div class="msg-meta muted">${escapeHtml(msg.created_at)}</div>
                  </div>
                `;
                chatMessages.appendChild(bubbleWrap);
              }
            });
            scrollChatToBottom(true);
          }
        } catch (e) {}
      }, 3000);
    }
  }

  // Conversation list search filter
  if (chatFilterInput) {
    chatFilterInput.addEventListener('input', () => {
      const q = chatFilterInput.value.toLowerCase().trim();
      document.querySelectorAll('#conversations-list .conv-item').forEach(item => {
        const name = item.dataset.name || '';
        item.style.display = (!q || name.includes(q)) ? 'flex' : 'none';
      });
    });
  }

  // New Chat Contacts Picker Toggle
  if (newChatToggleBtn && newChatPicker) {
    newChatToggleBtn.addEventListener('click', () => {
      const isVisible = newChatPicker.style.display !== 'none';
      newChatPicker.style.display = isVisible ? 'none' : 'block';
    });
    if (closePickerBtn) {
      closePickerBtn.addEventListener('click', () => {
        newChatPicker.style.display = 'none';
      });
    }
  }
});
