// ===== Carga de miniaturas con límite de concurrencia =====
// Evita que, con cientos de fotos, HTTP/2 pida todas las miniaturas a la vez y
// sature el servidor. Solo carga las cercanas a la pantalla, máx. 5 a la vez.
(function () {
  var imgs = Array.prototype.slice.call(document.querySelectorAll('img[data-src]'));
  if (!imgs.length) return;
  var MAX = 5, active = 0, queue = [];
  function pump() { while (active < MAX && queue.length) load(queue.shift()); }
  function load(img) {
    if (!img.getAttribute('data-src')) return;
    active++;
    img.onload = img.onerror = function () { img.onload = img.onerror = null; active--; pump(); };
    img.src = img.getAttribute('data-src');
    img.removeAttribute('data-src');
  }
  function enqueue(img) { if (img.getAttribute('data-src')) { queue.push(img); pump(); } }
  if ('IntersectionObserver' in window) {
    var io = new IntersectionObserver(function (ents) {
      ents.forEach(function (e) { if (e.isIntersecting) { io.unobserve(e.target); enqueue(e.target); } });
    }, { rootMargin: '700px 0px' });
    imgs.forEach(function (i) { io.observe(i); });
  } else {
    imgs.forEach(enqueue);
  }
})();

// ===== Lightbox de la galería (ver en grande + navegar + descargar) =====
(function () {
  var tiles = Array.prototype.slice.call(document.querySelectorAll('.tile'));
  var lb = document.getElementById('lb');
  if (!lb || !tiles.length) return;
  var img = document.getElementById('lbImg'),
      dl = document.getElementById('lbDl'),
      i = 0;

  function fullOf(t) { return t.getAttribute('data-full'); }
  function dlOf(t) { var a = t.querySelector('.tdl'); return a ? a.getAttribute('href') : '#'; }

  function show(n) {
    i = (n + tiles.length) % tiles.length;
    img.src = fullOf(tiles[i]);
    dl.href = dlOf(tiles[i]);
  }
  function open(n) { show(n); lb.classList.add('open'); document.body.style.overflow = 'hidden'; }
  function close() { lb.classList.remove('open'); img.src = ''; document.body.style.overflow = ''; }

  tiles.forEach(function (t, n) {
    t.addEventListener('click', function (e) {
      if (e.target.closest('.tdl')) return; // el botón de descarga hace lo suyo
      open(n);
    });
  });
  document.getElementById('lbClose').addEventListener('click', close);
  document.getElementById('lbPrev').addEventListener('click', function (e) { e.stopPropagation(); show(i - 1); });
  document.getElementById('lbNext').addEventListener('click', function (e) { e.stopPropagation(); show(i + 1); });
  lb.addEventListener('click', function (e) { if (e.target === lb) close(); });
  document.addEventListener('keydown', function (e) {
    if (!lb.classList.contains('open')) return;
    if (e.key === 'Escape') close();
    else if (e.key === 'ArrowLeft') show(i - 1);
    else if (e.key === 'ArrowRight') show(i + 1);
  });
})();
