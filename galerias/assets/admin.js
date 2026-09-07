// ===== Cargador de fotos (drag & drop) del panel =====
(function () {
  var dz = document.getElementById('dz');
  if (!dz) return;
  var slug = dz.dataset.slug, csrf = dz.dataset.csrf;
  var input = document.getElementById('fileInput');
  var prog = document.getElementById('dzProg'), bar = document.getElementById('dzBar'), txt = document.getElementById('dzTxt');
  var grid = document.getElementById('pgrid'), countEl = document.getElementById('pgCount'), empty = document.getElementById('pgEmpty');

  var queue = [], total = 0, done = 0, failed = 0, running = 0, CONC = 3;

  ['dragenter', 'dragover'].forEach(function (e) {
    dz.addEventListener(e, function (ev) { ev.preventDefault(); dz.classList.add('over'); });
  });
  ['dragleave', 'drop'].forEach(function (e) {
    dz.addEventListener(e, function (ev) { ev.preventDefault(); if (e === 'drop' || ev.target === dz) dz.classList.remove('over'); });
  });
  dz.addEventListener('drop', function (ev) { add(ev.dataTransfer.files); });
  input.addEventListener('change', function () { add(input.files); input.value = ''; });

  function add(files) {
    var imgs = Array.prototype.filter.call(files, function (f) { return /image\/(jpeg|png)/.test(f.type); });
    if (!imgs.length) return;
    imgs.forEach(function (f) { queue.push(f); total++; });
    prog.hidden = false;
    update();
    for (var i = running; i < CONC; i++) next();
  }

  function next() {
    if (!queue.length) { if (running === 0) finish(); return; }
    var file = queue.shift(); running++;
    var fd = new FormData();
    fd.append('file', file); fd.append('slug', slug); fd.append('csrf', csrf);
    var xhr = new XMLHttpRequest();
    xhr.open('POST', '/galerias/upload.php');
    xhr.setRequestHeader('X-CSRF', csrf);
    xhr.upload.onprogress = function (e) { if (e.lengthComputable) { cur = e.loaded / e.total; update(); } };
    xhr.onload = function () {
      running--; cur = 0;
      var r = {}; try { r = JSON.parse(xhr.responseText); } catch (e) {}
      if (r.ok) { done++; addThumb(r.name); }
      else { failed++; }
      update(); next();
    };
    xhr.onerror = function () { running--; failed++; cur = 0; update(); next(); };
    xhr.send(fd);
  }

  var cur = 0;
  function update() {
    var frac = total ? Math.min(1, (done + failed + cur) / total) : 0;
    bar.style.width = Math.round(frac * 100) + '%';
    var pend = total - done - failed;
    txt.textContent = 'Subiendo… ' + done + ' de ' + total + ' listas'
      + (failed ? ' · ' + failed + ' con error' : '')
      + (pend > 0 ? ' · ' + pend + ' en cola' : '');
  }
  function finish() {
    txt.textContent = done + ' fotos subidas' + (failed ? ' · ' + failed + ' con error (reintenta)' : '') + '. Recuerda «Preparar descarga».';
    bar.style.width = '100%';
    setTimeout(function () { queue = []; total = done = failed = 0; }, 100);
  }

  function addThumb(name) {
    if (empty) empty.hidden = true;
    var ef = encodeURIComponent(name);
    var div = document.createElement('div');
    div.className = 'pg-item'; div.dataset.f = name;
    div.innerHTML =
      "<img loading='lazy' src='/galerias/media.php?g=" + slug + "&s=thumb&f=" + ef + "'>" +
      "<form method='post' class='pg-del' onsubmit=\"return confirm('¿Borrar esta foto?')\">" +
      "<input type='hidden' name='csrf' value='" + csrf + "'><input type='hidden' name='action' value='delphoto'>" +
      "<input type='hidden' name='slug' value='" + slug + "'><input type='hidden' name='file' value='" + name.replace(/'/g, '&#39;') + "'>" +
      "<button title='Borrar'>&times;</button></form>";
    grid.appendChild(div);
    if (countEl) countEl.textContent = '(' + grid.children.length + ')';
  }
})();
