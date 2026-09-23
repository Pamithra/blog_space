/**
 * BlogSpace - Client-Side Interactive JavaScript
 * Handles themes, mobile drawer, likes, toasts, social sharing, and AJAX interactions.
 */

// Global Toast Notification Helper
function showToast(message, type = 'info', duration = 3500) {
  let container = document.getElementById('toast-container');
  if (!container) {
    container = document.createElement('div');
    container.id = 'toast-container';
    document.body.appendChild(container);
  }

  const toast = document.createElement('div');
  toast.className = `toast toast-${type}`;
  
  let icon = 'ℹ️';
  if (type === 'success') icon = '✅';
  if (type === 'error') icon = '⚠️';

  toast.innerHTML = `<span>${icon}</span> <div>${message}</div>`;
  container.appendChild(toast);

  setTimeout(() => {
    toast.style.animation = 'toastFadeOut 0.3s forwards';
    setTimeout(() => toast.remove(), 300);
  }, duration);
}

document.addEventListener('DOMContentLoaded', () => {
  // --------------------------------------------------------------------------
  // 1. Theme Management (Dark / Light Mode)
  // --------------------------------------------------------------------------
  const themeToggleBtns = document.querySelectorAll('.btn-theme-toggle');
  const currentTheme = localStorage.getItem('blogspace_theme') || 'dark';
  document.documentElement.setAttribute('data-theme', currentTheme);

  function updateThemeIcons(theme) {
    themeToggleBtns.forEach(btn => {
      btn.innerHTML = theme === 'light' 
        ? '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path></svg>' // Moon
        : '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="5"></circle><line x1="12" y1="1" x2="12" y2="3"></line><line x1="12" y1="21" x2="12" y2="23"></line><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line><line x1="1" y1="12" x2="3" y2="12"></line><line x1="21" y1="12" x2="23" y2="12"></line><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line></svg>'; // Sun
    });
  }
  updateThemeIcons(currentTheme);

  themeToggleBtns.forEach(btn => {
    btn.addEventListener('click', () => {
      const activeTheme = document.documentElement.getAttribute('data-theme');
      const nextTheme = activeTheme === 'light' ? 'dark' : 'light';
      document.documentElement.setAttribute('data-theme', nextTheme);
      localStorage.setItem('blogspace_theme', nextTheme);
      updateThemeIcons(nextTheme);
    });
  });

  // --------------------------------------------------------------------------
  // 2. Mobile Drawer Navigation
  // --------------------------------------------------------------------------
  const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
  const drawer = document.querySelector('.mobile-drawer');
  const overlay = document.querySelector('.drawer-overlay');
  const closeDrawerBtn = document.querySelector('.drawer-close-btn');

  function openDrawer() {
    if (drawer) drawer.classList.add('active');
    if (overlay) overlay.classList.add('active');
  }

  function closeDrawer() {
    if (drawer) drawer.classList.remove('active');
    if (overlay) overlay.classList.remove('active');
  }

  if (mobileMenuBtn) mobileMenuBtn.addEventListener('click', openDrawer);
  if (closeDrawerBtn) closeDrawerBtn.addEventListener('click', closeDrawer);
  if (overlay) overlay.addEventListener('click', closeDrawer);

  // --------------------------------------------------------------------------
  // 3. Like Button (Single Unified Asynchronous Fetch Handler)
  // --------------------------------------------------------------------------
  document.querySelectorAll('.like-btn').forEach(btn => {
    btn.addEventListener('click', function(e) {
      e.preventDefault();
      const postId = this.dataset.postId || this.getAttribute('data-post-id');
      const csrf = this.dataset.csrf || this.getAttribute('data-csrf');
      const countSpan = this.querySelector('.count');

      if (!postId || !csrf) {
        showToast('Action could not be verified.', 'error');
        return;
      }

      fetch(BASE_URL + 'like.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'id=' + encodeURIComponent(postId) + '&_csrf=' + encodeURIComponent(csrf)
      })
      .then(res => res.json())
      .then(data => {
        if (data.ok) {
          if (countSpan) countSpan.textContent = data.count;
          this.classList.toggle('liked', data.liked);
          showToast(data.liked ? 'Added to your liked posts!' : 'Post unliked', 'info');
        } else {
          showToast(data.error || 'Please log in to like this post.', 'error');
        }
      })
      .catch(err => {
        console.error('Like error:', err);
        showToast('Network connection failed.', 'error');
      });
    });
  });

  // --------------------------------------------------------------------------
  // 4. Social Sharing & Clipboard Copy Link
  // --------------------------------------------------------------------------
  document.querySelectorAll('[data-share]').forEach(btn => {
    btn.addEventListener('click', e => {
      e.preventDefault();
      const type = btn.getAttribute('data-share');
      const url = encodeURIComponent(window.location.href);
      const title = encodeURIComponent(document.title);

      if (type === 'copy') {
        if (navigator.clipboard) {
          navigator.clipboard.writeText(window.location.href)
            .then(() => showToast('Link copied to clipboard!', 'success'))
            .catch(() => showToast('Could not copy link.', 'error'));
        } else {
          showToast('Clipboard access not supported.', 'error');
        }
      } else if (type === 'twitter') {
        window.open(`https://twitter.com/intent/tweet?url=${url}&text=${title}`, '_blank', 'width=600,height=400');
      } else if (type === 'linkedin') {
        window.open(`https://www.linkedin.com/sharing/share-offsite/?url=${url}`, '_blank', 'width=600,height=500');
      }
    });
  });

  // --------------------------------------------------------------------------
  // 5. Newsletter Subscription Handler
  // --------------------------------------------------------------------------
  const newsletterForm = document.getElementById('newsletter-form');
  if (newsletterForm) {
    newsletterForm.addEventListener('submit', function(e) {
      e.preventDefault();
      const emailInput = this.querySelector('input[name=email]');
      const csrfInput = this.querySelector('input[name=_csrf]');
      
      if (!emailInput || !emailInput.value) return;

      fetch(BASE_URL + 'newsletter_subscribe.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'email=' + encodeURIComponent(emailInput.value) + '&_csrf=' + encodeURIComponent(csrfInput ? csrfInput.value : '')
      })
      .then(res => res.json())
      .then(data => {
        showToast(data.message, data.ok ? 'success' : 'error');
        if (data.ok) newsletterForm.reset();
      })
      .catch(err => {
        console.error('Newsletter error:', err);
        showToast('Subscription request failed.', 'error');
      });
    });
  }

  // --------------------------------------------------------------------------
  // 6. Automatically convert PHP Flash Session message to Toast
  // --------------------------------------------------------------------------
  const flashDataEl = document.getElementById('php-flash-data');
  if (flashDataEl) {
    const msg = flashDataEl.getAttribute('data-message');
    const type = flashDataEl.getAttribute('data-type') || 'info';
    if (msg) {
      showToast(msg, type);
    }
  }
});
