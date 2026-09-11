// ===== Cargador de fotos (drag & drop) con progreso por foto =====
(function () {
  var dz = document.getElementById('dz');
  if (!dz) return;
  var slug = dz.dataset.slug, csrf = dz.dataset.csrf;
  var input = document.getElementById('fileInput');
  var prog = document.getElementById('dzProg'), bar = document.getElementById('dzBar'), txt = document.getElementById('dzTxt');
  var grid = document.getElementById('pgrid'), countEl = document.getElementById('pgCount'), empty = document.getElementById('pgEmpty');

  var queue = [], total = 0, done = 0, failed = 0, running = 0, curBytes = 0;
  var CONC = 4; // subidas en paralelo

  ['dragenter', 'dragover'].forEach(function (e) {
    dz.addEventListener(e, function (ev) { ev.preventDefault(); dz.classList.add('over'); });
  });
  dz.addEventListener('dragleave', function (ev) { if (!dz.contains(ev.relatedTarget)) dz.classList.remove('over'); });
  dz.addEventListener('drop', function (ev) { ev.preventDefault(); dz.classList.remove('over'); add(ev.dataTransfer.files); });
  input.addEventListener('change', function () { add(input.files); input.value = ''; });

  function toolsHTML(name) {
    var safe = name.replace(/'/g, '&#39;');
    return "<div class='pg-tools'>" +
      "<form method='post' class='pg-cover'><input type='hidden' name='csrf' value='" + csrf + "'><input type='hidden' name='action' value='setcover'>" +
      "<input type='hidden' name='slug' value='" + slug + "'><input type='hidden' name='file' value='" + safe + "'><button title='Hacer portada'>&#9734;</button></form>" +
      "<form method='post' class='pg-del' onsubmit=\"return confirm('¿Borrar esta foto?')\"><input type='hidden' name='csrf' value='" + csrf + "'><input type='hidden' name='action' value='delphoto'>" +
      "<input type='hidden' name='slug' value='" + slug + "'><input type='hidden' name='file' value='" + safe + "'><button title='Borrar'>&times;</button></form></div>";
  }

  function add(files) {
    var imgs = Array.prototype.filter.call(files, function (f) { return /image\/(jpeg|png)/.test(f.type); });
    if (!imgs.length) return;
    if (empty) empty.hidden = true;
    imgs.forEach(function (f) {
      var tile = document.createElement('div');
      tile.className = 'pg-item up';
      var url = URL.createObjectURL(f);
      tile.innerHTML = "<img src='" + url + "'><div class='pg-up'><span class='pg-bar'><i></i></span></div>";
      grid.appendChild(tile);
      queue.push({ file: f, tile: tile, i: tile.querySelector('.pg-bar i'), url: url });
      total++;
    });
    prog.hidden = false; update(); refreshCount();
    for (var k = running; k < CONC; k++) next();
  }

  function next() {
    if (!queue.length) { if (running === 0) finish(); return; }
    var it = queue.shift(); running++;
    var fd = new FormData();
    fd.append('file', it.file); fd.append('slug', slug); fd.append('csrf', csrf);
    var xhr = new XMLHttpRequest();
    xhr.open('POST', '/galerias/upload.php');
    xhr.setRequestHeader('X-CSRF', csrf);
    xhr.upload.onprogress = function (e) {
      if (e.lengthComputable) { it.i.style.width = Math.round(e.loaded / e.total * 100) + '%'; }
    };
    xhr.onload = function () {
      running--;
      var r = {}; try { r = JSON.parse(xhr.responseText); } catch (e) {}
      if (r.ok) {
        done++;
        it.tile.classList.remove('up'); it.tile.dataset.f = r.name;
        var up = it.tile.querySelector('.pg-up'); if (up) up.remove();
        it.tile.insertAdjacentHTML('beforeend', toolsHTML(r.name));
      } else {
        failed++; it.tile.classList.add('err');
        it.tile.querySelector('.pg-up').innerHTML = "<span class='pg-erm'>Error, reintenta</span>";
      }
      update(); refreshCount(); next();
    };
    xhr.onerror = function () { running--; failed++; it.tile.classList.add('err'); update(); next(); };
    xhr.send(fd);
  }

  function update() {
    var frac = total ? (done + failed) / total : 0;
    bar.style.width = Math.round(frac * 100) + '%';
    var pend = total - done - failed;
    txt.textContent = 'Subiendo… ' + done + ' de ' + total + ' listas'
      + (failed ? ' · ' + failed + ' con error' : '')
      + (pend > 0 ? ' · ' + pend + ' en cola' : '');
  }
  function finish() {
    txt.textContent = done + ' fotos subidas' + (failed ? ' · ' + failed + ' con error (reintenta)' : '')
      + '. No olvides «Preparar descarga (zip)».';
    bar.style.width = '100%';
    setTimeout(function () { total = done = failed = 0; }, 200);
  }
  function refreshCount() { if (countEl) countEl.textContent = '(' + grid.querySelectorAll('.pg-item:not(.up)').length + ')'; }
})();

// ===== Selección múltiple para borrar varias fotos a la vez =====
(function () {
  var grid = document.getElementById('pgrid');
  var toggle = document.getElementById('selToggle');
  if (!grid || !toggle) return;
  var bulk = document.getElementById('pgBulk'), cnt = document.getElementById('pgSelCount'),
      delBtn = document.getElementById('bulkDel'), cancel = document.getElementById('selCancel'),
      form = document.getElementById('bulkDelForm');

  function selected() { return Array.prototype.slice.call(grid.querySelectorAll('.pg-item.sel')); }
  function refresh() {
    var n = selected().length;
    cnt.textContent = n + (n === 1 ? ' seleccionada' : ' seleccionadas');
    delBtn.disabled = !n;
  }
  function setMode(on) {
    grid.classList.toggle('selecting', on);
    bulk.hidden = !on;
    toggle.textContent = on ? 'Listo' : 'Seleccionar varias';
    toggle.classList.toggle('danger', false);
    if (!on) selected().forEach(function (t) { t.classList.remove('sel'); });
    refresh();
  }
  toggle.addEventListener('click', function () { setMode(!grid.classList.contains('selecting')); });
  cancel.addEventListener('click', function () { setMode(false); });

  // en modo selección, un clic sobre la foto la marca/desmarca (bloquea los botones de la foto)
  grid.addEventListener('click', function (e) {
    if (!grid.classList.contains('selecting')) return;
    var it = e.target.closest('.pg-item');
    if (!it || it.classList.contains('up') || it.classList.contains('err')) return;
    e.preventDefault(); e.stopPropagation();
    it.classList.toggle('sel');
    refresh();
  }, true);

  delBtn.addEventListener('click', function () {
    var items = selected();
    if (!items.length) return;
    if (!confirm('¿Borrar ' + items.length + (items.length === 1 ? ' foto seleccionada?' : ' fotos seleccionadas?'))) return;
    Array.prototype.slice.call(form.querySelectorAll('input[name="files[]"]')).forEach(function (i) { i.remove(); });
    items.forEach(function (t) {
      var f = t.dataset.f; if (!f) return;
      var i = document.createElement('input');
      i.type = 'hidden'; i.name = 'files[]'; i.value = f;
      form.appendChild(i);
    });
    form.submit();
  });
})();

// ===== Generar link: copiar link + contraseña al portapapeles =====
(function () {
  var cl = document.getElementById('copyLink');
  if (!cl) return;
  var note = document.getElementById('copiedNote');
  cl.addEventListener('click', function () {
    var url = cl.dataset.url, pass = cl.dataset.pass, title = cl.dataset.title, exp = cl.dataset.exp;
    var msg = 'Hola 👋 Aquí está tu galería de fotos de Catch a Film Studio:\n\n'
      + '📸 ' + title + '\n'
      + '🔗 ' + url + '\n'
      + (pass ? '🔒 Contraseña: ' + pass + '\n' : '')
      + (exp ? '\nDisponible hasta el ' + exp + '.\n' : '\n')
      + '¡Que las disfrutes!';
    copyText(msg);
  });
  function showBox(msg) {
    if (!note) { alert(msg); return; }
    note.hidden = false;
    note.innerHTML = "<div class='cn-status' id='cnStatus'>Copiando…</div>";
    var ta = document.createElement('textarea');
    ta.className = 'copy-manual'; ta.readOnly = true; ta.value = msg;
    note.appendChild(ta); ta.focus(); ta.select();
    note._ta = ta;
  }
  function status(t) { var s = document.getElementById('cnStatus'); if (s) s.textContent = t; }
  function copyText(msg) {
    showBox(msg); // siempre visible para copiar a mano si hace falta
    if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(msg).then(
        function () { status('✓ Copiado al portapapeles — pégalo en WhatsApp o correo y envíalo al cliente.'); },
        function () { legacy(msg); }
      );
    } else { legacy(msg); }
  }
  function legacy(msg) {
    var ta = note && note._ta, ok = false;
    if (ta) { ta.focus(); ta.select(); try { ok = document.execCommand('copy'); } catch (e) {} }
    status(ok ? '✓ Copiado al portapapeles — pégalo y envíalo al cliente.'
              : '☝ Selecciona el texto de arriba y cópialo con Ctrl/Cmd + C.');
  }
})();
