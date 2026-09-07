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
