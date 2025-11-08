</main>

<footer class="site-footer">
  <div class="container">
    © <?php echo date('Y'); ?> <?php echo htmlspecialchars(SITE_NAME); ?>
  </div>
</footer>

<script src="<?php echo BASE_URL; ?>assets/js/scripts.js"></script>

<!-- Like button script -->
<script>
document.querySelectorAll('.like-btn').forEach(btn => {
  btn.addEventListener('click', () => {
    const postId = btn.getAttribute('data-post-id');
    const csrf = btn.getAttribute('data-csrf');

    fetch('<?php echo BASE_URL; ?>like.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/x-www-form-urlencoded'},
      body: 'post_id=' + postId + '&_csrf=' + encodeURIComponent(csrf)
    })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        btn.querySelector('.count').textContent = data.total;
        btn.classList.toggle('liked', data.liked);
      } else {
        alert(data.message);
      }
    })
    .catch(console.error);
  });
});
</script>

</body>
</html>
