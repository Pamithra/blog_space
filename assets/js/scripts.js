// use BASE_URL variable defined by PHP header
document.addEventListener('DOMContentLoaded', function(){
  document.querySelectorAll('.like-btn').forEach(function(b){
    b.addEventListener('click', function(e){
      e.preventDefault();
      var id = this.dataset.postId || this.getAttribute('data-post-id'), csrf = this.dataset.csrf || this.getAttribute('data-csrf'), self = this;
      if (!id) { console.error('Like button missing data-post-id'); return; }
      if (!csrf) { console.error('Like button missing data-csrf'); return; }
      fetch(BASE_URL + 'like.php', {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:'id='+encodeURIComponent(id)+'&_csrf='+encodeURIComponent(csrf)})
        .then(function(r){ return r.json(); })
        .then(function(j){
          if (j.ok){
            var cnt = self.querySelector('.count');
            if (cnt) cnt.textContent = j.count;
            self.classList.toggle('liked', j.liked);
          } else {
            console.error('Like error', j);
            alert(j.error || 'Error');
          }
        }).catch(function(err){ console.error('Fetch error', err); alert('Network error'); });
    });
  });

  var nf = document.getElementById('newsletter-form');
  if (nf) nf.addEventListener('submit', function(e){ e.preventDefault(); var email=this.querySelector('input[name=email]').value; fetch(BASE_URL + 'newsletter_subscribe.php', {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:'email='+encodeURIComponent(email)+'&_csrf='+encodeURIComponent(this.querySelector('input[name=_csrf]').value)}).then(r=>r.json()).then(j=>{ alert(j.message); if (j.ok) this.reset(); }); });
});
