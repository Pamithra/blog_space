<?php
// inc/footer.php
// Main layout footer with newsletter subscription and script imports
?>
  </div><!-- /.container -->
</main><!-- /.site-main -->

<footer class="site-footer">
  <div class="container">
    <div class="footer-grid">
      <!-- Brand Column -->
      <div class="footer-brand">
        <div class="brand-logo">
          <span class="brand-badge">B</span>
          <span><?php echo htmlspecialchars(SITE_NAME); ?></span>
        </div>
        <p>A modern publishing space to read, write, and share insightful stories and ideas.</p>
      </div>

      <!-- Quick Navigation -->
      <div class="footer-col">
        <h5>Quick Links</h5>
        <ul class="footer-links">
          <li><a href="<?php echo BASE_URL; ?>index.php">Home</a></li>
          <?php if (is_logged_in()): ?>
            <li><a href="<?php echo BASE_URL; ?>posts/new.php">Write Post</a></li>
            <li><a href="<?php echo BASE_URL; ?>profile.php">My Profile</a></li>
          <?php else: ?>
            <li><a href="<?php echo BASE_URL; ?>auth/login.php">Log In</a></li>
            <li><a href="<?php echo BASE_URL; ?>auth/register.php">Create Account</a></li>
          <?php endif; ?>
        </ul>
      </div>

      <!-- Newsletter Column -->
      <div class="footer-col">
        <h5>Stay Updated</h5>
        <p style="margin-bottom:12px;font-size:13px;">Get the latest articles delivered directly to your inbox.</p>
        <form id="newsletter-form" style="display:flex;gap:6px;">
          <input type="hidden" name="_csrf" value="<?php echo csrf_token(); ?>">
          <input type="email" name="email" class="form-control" placeholder="your@email.com" required style="padding:8px 12px;font-size:13px;">
          <button type="submit" class="btn btn-primary btn-sm">Join</button>
        </form>
      </div>
    </div>

    <!-- Bottom Copyright -->
    <div class="footer-bottom">
      <p>&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars(SITE_NAME); ?>. All rights reserved.</p>
    </div>
  </div>
</footer>

<!-- Interactive Scripts -->
<script src="<?php echo BASE_URL; ?>assets/js/scripts.js?v=<?php echo filemtime(__DIR__ . '/../assets/js/scripts.js'); ?>"></script>

</body>
</html>
