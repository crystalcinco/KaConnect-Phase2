<footer class="footer">
  <div class="footer-brand">KaConnect <span>·</span> Made for sharing, learning, and connecting.</div>
  <small>Web Systems and Technologies · Student Project</small>
</footer>

<!-- Interactive Image Cropper Modal -->
<div id="cropper-modal" class="cropper-modal" aria-hidden="true" role="dialog" aria-labelledby="cropper-title">
  <div class="cropper-dialog card">
    <div class="cropper-head">
      <h3 id="cropper-title">Adjust & Crop Image</h3>
      <button type="button" class="cropper-close" id="cropper-close-btn" aria-label="Close cropper">✕</button>
    </div>
    <div class="cropper-body">
      <div class="cropper-stage" id="cropper-stage">
        <canvas id="cropper-canvas" width="600" height="400"></canvas>
        <div class="cropper-mask" id="cropper-mask"></div>
      </div>
      <div class="cropper-controls">
        <span class="cropper-zoom-label">Zoom</span>
        <button type="button" class="mini cropper-zoom-btn" id="cropper-zoom-out">−</button>
        <input type="range" id="cropper-zoom-slider" min="0.5" max="3" step="0.05" value="1" aria-label="Zoom level">
        <button type="button" class="mini cropper-zoom-btn" id="cropper-zoom-in">+</button>
        <button type="button" class="mini" id="cropper-rotate-btn" title="Rotate 90 degrees">↻ Rotate</button>
      </div>
      <small class="muted cropper-hint">Drag image to position inside the guide box. Use the slider to zoom.</small>
    </div>
    <div class="cropper-footer">
      <button type="button" class="button secondary" id="cropper-cancel-btn">Cancel</button>
      <button type="button" class="button primary" id="cropper-apply-btn">Apply Crop</button>
    </div>
  </div>
</div>

<script src="assets/app.js?v=4.2"></script>
</body>
</html>
