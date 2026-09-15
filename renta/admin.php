<?php
// ===== Panel de renta: catálogo + métricas (solo Arturo) =====
// Entra con la MISMA contraseña de admin de las galerías.
require_once __DIR__ . '/lib.php';
boot_session();
security_headers();
header('Cache-Control: no-store');

const SELF = '/renta/admin.php';

function page_top($title) {
    $v = asset_ver('assets/gallery.css');
    echo "<!doctype html><html lang=es><head><meta charset=utf-8>"
       . "<meta name=viewport content='width=device-width,initial-scale=1'><meta name=robots content='noindex,nofollow'>"
       . "<title>" . h($title) . " · Renta Catch</title>"
       . "<link rel=stylesheet href='/galerias/assets/gallery.css?v=$v'>"
       . "<style>" . ADMIN_CSS . "</style></head><body class='admin'>";
}
function back($url, $msg = '', $bad = false) {
    if ($msg !== '') $_SESSION['renta_flash'] = [$msg, $bad];
    header('Location: ' . $url);
    exit;
}

const ADMIN_CSS = <<<'CSS'
body.admin{color-scheme:dark}
.rwrap{max-width:1100px;margin:0 auto;padding:0 clamp(1rem,4vw,2rem) 5rem}
.rbar{position:sticky;top:0;z-index:30;display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:.8rem 1.4rem;padding:.85rem 0;margin-bottom:1.8rem;background:rgba(8,9,10,.92);-webkit-backdrop-filter:blur(10px);backdrop-filter:blur(10px);border-bottom:1px solid var(--line)}
.pillnav{display:flex;gap:.25rem;padding:.25rem;border:1px solid var(--line);border-radius:40px}
.pillnav a{padding:.45rem 1rem;border-radius:40px;font-size:.78rem;font-weight:700;letter-spacing:.04em;color:var(--muted);white-space:nowrap}
.pillnav a:hover{color:var(--text)}
.pillnav a.on{background:linear-gradient(115deg,var(--accent),var(--accent2));color:#fff}
.rbar-r{display:flex;gap:.4rem;align-items:center}
.rbar-r form,.racts form,.row form{margin:0}
.rhead{display:flex;flex-wrap:wrap;align-items:flex-end;justify-content:space-between;gap:1rem;margin-bottom:1.2rem}
.rhead h1{margin:0}
.rhead p{margin:.45rem 0 0}
.racts{display:flex;flex-wrap:wrap;gap:.4rem;align-items:center}
.btn.small,.pbtns .btn{margin-top:0}
.back{display:inline-block;margin-bottom:1rem}

/* catálogo */
.rcat{margin-top:2.2rem;scroll-margin-top:90px}
.rcat-h{display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:.6rem 1rem;margin-bottom:.7rem}
.admin .rcat-h h2{margin:0;font-size:1.8rem}
.rcat-h h2 span{font-family:var(--body);font-size:.8rem;color:var(--muted);letter-spacing:.04em;margin-left:.4rem}
.rows{border:1px solid var(--line);border-radius:12px;overflow:hidden}
.row{display:grid;grid-template-columns:56px minmax(0,1fr) 100px 132px auto;gap:.9rem;align-items:center;padding:.7rem .9rem;border-top:1px solid var(--line);scroll-margin-top:90px}
.row:first-child{border-top:0}
.row:target{background:rgba(190,66,87,.08)}
.row.off .rth,.row.off .rinfo{opacity:.45}
.rth{width:56px;height:56px;border-radius:9px;display:grid;place-items:center;overflow:hidden;background:radial-gradient(75% 85% at 50% 42%,#2A1C23,#171015)}
.rth img{width:100%;height:100%;object-fit:contain;padding:4px}
.rth img.full{object-fit:cover;padding:0}
.rth svg{width:58%;color:rgba(190,66,87,.75)}
.rinfo b{display:block;font-size:.95rem;line-height:1.3}
.rinfo span{display:block;font-size:.8rem;line-height:1.35;margin-top:.1rem}
.rprice{font-weight:700;white-space:nowrap}
.rprice small{color:var(--muted);font-weight:500}
.rprice em{display:block;font-style:normal;font-size:.72rem;color:var(--muted);font-weight:500}
.tog{border:none;cursor:pointer;font-weight:700;padding:.4rem .7rem;font-size:.66rem}
.tog:hover{filter:brightness(1.25)}
.mini.ico{min-width:32px;text-align:center;padding:.4rem .5rem}
.mini:disabled{opacity:.35;cursor:default}
.warn-box{border:1px solid rgba(220,180,60,.4);background:rgba(220,180,60,.08);border-radius:10px;padding:.8rem 1rem;margin-top:1rem;font-size:.88rem}
@media(max-width:760px){
  .row{grid-template-columns:52px minmax(0,1fr) auto;grid-template-areas:"th info price" "tog tog acts";row-gap:.6rem}
  .rth{grid-area:th;width:52px;height:52px}.rinfo{grid-area:info}.rprice{grid-area:price;text-align:right}
  .rtog{grid-area:tog}.row .racts{grid-area:acts;justify-content:flex-end}
}

/* formulario */
.pform{display:grid;grid-template-columns:minmax(0,1.35fr) minmax(0,1fr);gap:1rem}
@media(max-width:760px){.pform{grid-template-columns:1fr}}
.fbox{display:flex;flex-direction:column;gap:1rem;padding:1.3rem;border:1px solid var(--line);border-radius:14px;background:var(--bg2)}
.fl{display:flex;flex-direction:column;gap:.4rem;font-size:.7rem;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:var(--muted)}
.fl input,.fl select,.fl textarea{width:100%;padding:.7rem .85rem;border-radius:9px;border:1px solid var(--line);background:var(--bg);color:var(--text);font:500 .95rem/1.4 var(--body);letter-spacing:0;text-transform:none}
.fl textarea{resize:vertical;min-height:74px}
.fl input:focus,.fl select:focus,.fl textarea:focus{outline:none;border-color:var(--accent)}
.fl input[type=file]{padding:.55rem;font-size:.85rem}
.two{display:grid;grid-template-columns:1fr 1fr;gap:.8rem}
.hint{margin:-.35rem 0 0;font-size:.8rem;color:var(--muted);line-height:1.45}
.chk{display:flex;gap:.6rem;align-items:center;font-size:.92rem;cursor:pointer}
.chk input{width:18px;height:18px;accent-color:var(--accent)}
.pprev{aspect-ratio:4/3;border-radius:12px;display:grid;place-items:center;overflow:hidden;background:radial-gradient(75% 85% at 50% 42%,#2A1C23,#171015)}
.pprev img{width:100%;height:100%;object-fit:contain;padding:8%}
.pprev img.full{object-fit:cover;padding:0}
.pprev svg{width:34%;color:rgba(190,66,87,.7)}
.pbtns{grid-column:1/-1;display:flex;flex-wrap:wrap;gap:.6rem;align-items:center}

/* categorías */
.catrow{display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1.6fr) 150px auto;gap:.6rem;align-items:end;padding:.9rem;border-top:1px solid var(--line)}
.catrow:first-child{border-top:0}
.catrow .fl input,.catrow .fl select{padding:.55rem .7rem;font-size:.88rem}
@media(max-width:760px){.catrow{grid-template-columns:1fr 1fr}.catrow .fl.wide{grid-column:1/-1}}

/* métricas */
.kpis{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:.8rem;margin-bottom:.8rem}
@media(max-width:860px){.kpis{grid-template-columns:repeat(2,minmax(0,1fr))}}
.kpi{padding:1.1rem 1.2rem;border:1px solid var(--line);border-radius:14px;background:var(--bg2);min-width:0}
.kpi span{display:block;font-size:.8rem;color:var(--muted)}
.kpi b{display:block;margin:.35rem 0 .15rem;font-size:2rem;font-weight:700;line-height:1.1;letter-spacing:-.01em}
.kpi.hero b{font-size:3.2rem;line-height:1}
.kpi small{color:var(--muted);font-size:.74rem;line-height:1.35;display:block}
@media(max-width:560px){.kpi{padding:1rem}.kpi b{font-size:1.55rem}.kpi.hero b{font-size:2.6rem}}
.cgrid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:.8rem}
@media(max-width:860px){.cgrid{grid-template-columns:1fr}}
.card2{min-width:0;padding:1.2rem 1.2rem 1rem;border:1px solid var(--line);border-radius:14px;background:var(--bg2)}
.card2.wide{grid-column:1/-1}
.admin .card2 h2{margin:0;font-family:var(--body);text-transform:none;font-weight:700;font-size:1rem}
.card2 .sub{margin:.15rem 0 1rem;font-size:.8rem;color:var(--muted)}
.chart{position:relative;min-height:200px}
.chart svg{display:block;overflow:visible;touch-action:pan-y}
.chart .ax{fill:#8C9A96;font-size:11px;font-variant-numeric:tabular-nums}
.chart .val{fill:#E6ECEA;font-size:12px;font-weight:700}
.chart .bar{outline:none}
.chart .bar:hover path,.chart .bar:focus path{filter:brightness(1.3)}
.chart svg:focus{outline:none}
.chart svg:focus-visible,.chart .bar:focus-visible rect{outline:2px solid rgba(190,66,87,.7);outline-offset:3px}
.tip{position:absolute;z-index:5;pointer-events:none;padding:.45rem .65rem;border-radius:8px;background:#E6ECEA;color:#08090A;font-size:.76rem;line-height:1.3;white-space:nowrap;box-shadow:0 8px 24px rgba(0,0,0,.45)}
.tip b{display:block;font-size:.92rem}
.tip span{color:#4B5654}
.split{display:grid;grid-template-columns:1fr 1fr;gap:.7rem}
.split div{padding:1rem;border:1px solid var(--line);border-radius:12px}
.split b{display:block;font-size:2rem;line-height:1.1}
.split span{display:block;font-size:.82rem;color:var(--muted)}
.split em{display:block;margin-top:.25rem;font-style:normal;font-size:.8rem;color:var(--text)}
.topl{list-style:none;margin:0;padding:0;display:grid;gap:.6rem}
.topl li{display:grid;grid-template-columns:minmax(0,230px) minmax(0,1fr) 3rem;gap:.9rem;align-items:center;font-size:.88rem;line-height:1.3}
.topl .tb i{display:block;height:12px;min-width:3px;border-radius:0 4px 4px 0;background:var(--accent)}
.topl .tv{text-align:right;font-weight:700;font-variant-numeric:tabular-nums}
@media(max-width:560px){.topl li{grid-template-columns:minmax(0,1fr) 3rem;row-gap:.3rem}.topl .tb{grid-column:1/-1;order:3}}
.empty2{padding:2.2rem 1rem;text-align:center;color:var(--muted);font-size:.9rem}
details.tbl summary{cursor:pointer;color:var(--muted);font-size:.8rem;margin-top:.7rem}
details.tbl summary:hover{color:var(--text)}
.tscroll{overflow-x:auto}
.dtable{width:100%;border-collapse:collapse;font-size:.82rem;margin-top:.6rem;font-variant-numeric:tabular-nums}
.dtable th,.dtable td{text-align:left;padding:.5rem .55rem;border-bottom:1px solid var(--line);vertical-align:top}
.dtable th{color:var(--muted);font-weight:600;font-size:.68rem;letter-spacing:.08em;text-transform:uppercase;white-space:nowrap}
.dtable td.n{text-align:right;white-space:nowrap}
CSS;

// ================= Acceso =================
if (!admin_is_setup()) {
    page_top('Configurar');
    echo "<div class='center'><div class='logo'>CAT<b>CH</b></div><h1>Configura tu acceso</h1>"
       . "<p class='muted'>Primero crea tu contraseña de administrador en el panel de galerías; luego regresa aquí.</p>"
       . "<a class='btn' href='/galerias/admin.php'>Crear contraseña</a></div></body></html>";
    exit;
}
if (!admin_authed()) {
    $err = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_ok()) {
        usleep(400000);
        if (admin_login($_POST['pw'] ?? '')) back(SELF);
        $err = 'Contraseña incorrecta.';
    }
    $tok = csrf_token();
    page_top('Entrar');
    echo "<div class='center'><div class='logo'>CAT<b>CH</b><small>A FILM STUDIO</small></div>"
       . "<div class='eyebrow'>Renta de equipo</div><h1>Panel de renta</h1>"
       . "<p class='muted'>Entra con tu contraseña de administrador (la misma de las galerías).</p>"
       . ($err ? "<p class='err'>" . h($err) . "</p>" : '')
       . "<form method=post class='pwform'><input type=hidden name=csrf value='$tok'>"
       . "<input type=password name=pw placeholder='Contraseña de admin' autocomplete='current-password' autofocus required>"
       . "<button class='btn' type=submit>Entrar</button></form></div></body></html>";
    exit;
}

// ================= Acciones =================
$C = renta_load();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Si la foto excede post_max_size, PHP vacía $_POST: avisar eso y no "sesión expirada"
    if (empty($_POST) && (int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
        back($_SERVER['REQUEST_URI'] ?? SELF, 'La foto pesa más de lo que acepta el servidor. Prueba con una de menos de 8 MB.', true);
    }
    if (!csrf_ok()) back(SELF, 'La sesión expiró. Intenta de nuevo.', true);
    $a  = $_POST['action'] ?? '';
    $id = (string)($_POST['id'] ?? '');
    $P  = &$C['productos'];
    $K  = &$C['categorias'];

    if ($a === 'logout') {
        unset($_SESSION['admin']);
        back(SELF);
    }

    if ($a === 'save') {
        $isNew = $id === '';
        $i     = $isNew ? -1 : renta_find($P, $id);
        if (!$isNew && $i < 0) back(SELF, 'Ese equipo ya no existe.', true);
        $cat    = (string)($_POST['cat'] ?? '');
        $nombre = trim(mb_substr((string)($_POST['nombre'] ?? ''), 0, 90));
        $retry  = SELF . ($isNew ? '?new=1&cat=' . rawurlencode($cat) : '?edit=' . rawurlencode($id));
        if ($nombre === '') back($retry, 'Ponle nombre al equipo.', true);
        if (renta_find($K, $cat) < 0) back($retry, 'Elige una categoría.', true);

        $p = $isNew ? ['id' => renta_unique_id($P, $nombre, 'equipo'), 'foto' => ''] : $P[$i];
        $p['nombre']     = $nombre;
        $p['cat']        = $cat;
        $p['detalle']    = trim(mb_substr((string)($_POST['detalle'] ?? ''), 0, 300));
        $p['precio']     = max(0, min(1000000, (int)preg_replace('/\D/', '', (string)($_POST['precio'] ?? '0'))));
        $p['unidades']   = max(1, min(99, (int)($_POST['unidades'] ?? 1)));
        $p['disponible'] = !empty($_POST['disponible']);

        $f = $_FILES['foto'] ?? null;
        if ($f && ($f['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            if (in_array($f['error'], [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true))
                back($retry, 'La foto pesa más de lo que acepta el servidor. Prueba con una de menos de 8 MB.', true);
            if ($f['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($f['tmp_name']))
                back($retry, 'No se pudo subir la foto. Intenta de nuevo.', true);
            $nueva = renta_save_upload($f['tmp_name'], $p['id']);
            if (!$nueva) back($retry, 'Ese archivo no es una imagen válida. Usa JPG, PNG o WebP.', true);
            renta_delete_foto($p['foto']);
            $p['foto'] = $nueva;
        } elseif (!empty($_POST['quitar_foto'])) {
            renta_delete_foto($p['foto']);
            $p['foto'] = '';
        }

        // Nuevo (o cambió de categoría): va al final de su categoría
        if (!$isNew && $P[$i]['cat'] === $cat) {
            $P[$i] = $p;
        } else {
            if (!$isNew) array_splice($P, $i, 1);
            $pos = count($P);
            foreach ($P as $k => $x) if ($x['cat'] === $cat) $pos = $k + 1;
            array_splice($P, $pos, 0, [$p]);
        }
        renta_save($C);
        back(SELF . '#p-' . $p['id'], ($isNew ? 'Agregado: ' : 'Guardado: ') . $nombre);
    }

    if ($a === 'toggle' && ($i = renta_find($P, $id)) >= 0) {
        $P[$i]['disponible'] = !$P[$i]['disponible'];
        renta_save($C);
        back(SELF . '#p-' . $id, $P[$i]['nombre'] . ($P[$i]['disponible'] ? ' ya aparece como disponible.' : ' ahora aparece como no disponible.'));
    }

    if ($a === 'move' && ($i = renta_find($P, $id)) >= 0) {
        $dir = (int)($_POST['dir'] ?? 1) < 0 ? -1 : 1;
        $j = $i + $dir;
        while ($j >= 0 && $j < count($P) && $P[$j]['cat'] !== $P[$i]['cat']) $j += $dir;
        if ($j >= 0 && $j < count($P)) { [$P[$i], $P[$j]] = [$P[$j], $P[$i]]; renta_save($C); }
        back(SELF . '#p-' . $id);
    }

    if ($a === 'delete' && ($i = renta_find($P, $id)) >= 0) {
        $nombre = $P[$i]['nombre'];
        renta_delete_foto($P[$i]['foto']);
        array_splice($P, $i, 1);
        renta_save($C);
        back(SELF, 'Eliminado: ' . $nombre);
    }

    if ($a === 'cat_save') {
        $nombre = trim(mb_substr((string)($_POST['nombre'] ?? ''), 0, 40));
        if ($nombre === '') back(SELF . '?cats=1', 'Ponle nombre a la categoría.', true);
        $icono = isset(RENTA_ICONOS[$_POST['icono'] ?? '']) ? $_POST['icono'] : 'accesorio';
        $desc  = trim(mb_substr((string)($_POST['desc'] ?? ''), 0, 200));
        if ($id === '') {
            $K[] = ['id' => renta_unique_id($K, $nombre, 'categoria'), 'nombre' => $nombre, 'desc' => $desc, 'icono' => $icono];
        } elseif (($i = renta_find($K, $id)) >= 0) {
            $K[$i] = ['id' => $id, 'nombre' => $nombre, 'desc' => $desc, 'icono' => $icono];
        }
        renta_save($C);
        back(SELF . '?cats=1', 'Categoría guardada: ' . $nombre);
    }

    if ($a === 'cat_move' && ($i = renta_find($K, $id)) >= 0) {
        $j = $i + ((int)($_POST['dir'] ?? 1) < 0 ? -1 : 1);
        if ($j >= 0 && $j < count($K)) { [$K[$i], $K[$j]] = [$K[$j], $K[$i]]; renta_save($C); }
        back(SELF . '?cats=1');
    }

    if ($a === 'cat_delete' && ($i = renta_find($K, $id)) >= 0) {
        foreach ($P as $x) if ($x['cat'] === $id) back(SELF . '?cats=1', 'Primero mueve o borra los equipos de esa categoría.', true);
        $nombre = $K[$i]['nombre'];
        array_splice($K, $i, 1);
        renta_save($C);
        back(SELF . '?cats=1', 'Categoría eliminada: ' . $nombre);
    }

    back(SELF);
}

// ================= Vistas =================
$flash = $_SESSION['renta_flash'] ?? null;
unset($_SESSION['renta_flash']);
$tok = csrf_token();
$csrf = "<input type=hidden name=csrf value='$tok'>";

$view = isset($_GET['edit']) || isset($_GET['new']) ? 'form' : (isset($_GET['cats']) ? 'cats' : (($_GET['v'] ?? '') === 'metricas' ? 'metricas' : 'catalogo'));
$catName = [];
foreach ($C['categorias'] as $k) $catName[$k['id']] = $k;

function thumb_html($p, $icono) {
    $u = renta_foto_url($p['foto'] ?? '');
    if ($u) return "<img class='" . (str_ends_with($u, '.png') ? 'cut' : 'full') . "' src='" . h($u) . "' alt='' loading='lazy'>";
    return renta_icon($icono);
}

page_top(['catalogo' => 'Catálogo', 'form' => 'Equipo', 'cats' => 'Categorías', 'metricas' => 'Métricas'][$view]);
?>
<div class="rwrap">
  <header class="rbar">
    <a class="logo" href="<?= SELF ?>">CAT<b>CH</b><small>RENTA · PANEL</small></a>
    <nav class="pillnav">
      <a class="<?= $view !== 'metricas' ? 'on' : '' ?>" href="<?= SELF ?>">Catálogo</a>
      <a class="<?= $view === 'metricas' ? 'on' : '' ?>" href="<?= SELF ?>?v=metricas">Métricas</a>
    </nav>
    <div class="rbar-r">
      <a class="mini" href="/renta/" target="_blank" rel="noopener">Ver página ↗</a>
      <form method="post"><?= $csrf ?><button class="mini" name="action" value="logout">Salir</button></form>
    </div>
  </header>

  <?php if ($flash): ?><div class="note <?= $flash[1] ? 'bad' : 'ok' ?>"><?= h($flash[0]) ?></div><?php endif; ?>

<?php if ($view === 'catalogo'):
    $byCat = [];
    foreach ($C['productos'] as $p) $byCat[$p['cat']][] = $p;
    $huerfanos = array_filter($C['productos'], fn($p) => !isset($catName[$p['cat']]));
    $nDisp = count(array_filter($C['productos'], fn($p) => $p['disponible']));
?>
  <div class="rhead">
    <div>
      <h1>Catálogo</h1>
      <p class="muted small"><?= count($C['productos']) ?> equipos · <?= $nDisp ?> disponibles ·
        <?= $C['actualizado'] ? 'Última edición: ' . date('j/m/Y H:i', $C['actualizado']) : 'Aún sin editar (se muestra el catálogo inicial)' ?></p>
    </div>
    <div class="racts">
      <a class="mini" href="?cats=1">Categorías</a>
      <a class="btn small" href="?new=1">+ Agregar equipo</a>
    </div>
  </div>

  <?php foreach ($C['categorias'] as $k): $list = $byCat[$k['id']] ?? []; ?>
  <section class="rcat" id="cat-<?= h($k['id']) ?>">
    <div class="rcat-h">
      <h2><?= h($k['nombre']) ?><span><?= count($list) ?> <?= count($list) === 1 ? 'equipo' : 'equipos' ?></span></h2>
      <a class="mini" href="?new=1&amp;cat=<?= rawurlencode($k['id']) ?>">+ Agregar en <?= h($k['nombre']) ?></a>
    </div>
    <?php if (!$list): ?>
      <p class="muted small">Sin equipos: esta categoría no se muestra en la página.</p>
    <?php else: ?>
    <div class="rows">
      <?php foreach ($list as $n => $p): ?>
      <div class="row<?= $p['disponible'] ? '' : ' off' ?>" id="p-<?= h($p['id']) ?>">
        <div class="rth"><?= thumb_html($p, $k['icono']) ?></div>
        <div class="rinfo"><b><?= h($p['nombre']) ?></b><?php if ($p['detalle'] !== ''): ?><span class="muted"><?= h($p['detalle']) ?></span><?php endif; ?></div>
        <div class="rprice"><?= renta_money($p['precio']) ?><small> /día</small><?php if ($p['unidades'] > 1): ?><em><?= $p['unidades'] ?> unidades</em><?php endif; ?></div>
        <form class="rtog" method="post"><?= $csrf ?><input type="hidden" name="id" value="<?= h($p['id']) ?>">
          <button class="pill tog <?= $p['disponible'] ? 'ok' : 'bad' ?>" name="action" value="toggle" title="Cambiar disponibilidad"><?= $p['disponible'] ? '● Disponible' : '○ No disponible' ?></button></form>
        <div class="racts">
          <form method="post"><?= $csrf ?><input type="hidden" name="id" value="<?= h($p['id']) ?>"><input type="hidden" name="dir" value="-1">
            <button class="mini ico" name="action" value="move" aria-label="Subir" <?= $n === 0 ? 'disabled' : '' ?>>↑</button></form>
          <form method="post"><?= $csrf ?><input type="hidden" name="id" value="<?= h($p['id']) ?>"><input type="hidden" name="dir" value="1">
            <button class="mini ico" name="action" value="move" aria-label="Bajar" <?= $n === count($list) - 1 ? 'disabled' : '' ?>>↓</button></form>
          <a class="mini" href="?edit=<?= rawurlencode($p['id']) ?>">Editar</a>
          <form method="post" onsubmit="return confirm('¿Borrar <?= h(addslashes($p['nombre'])) ?> del catálogo?')"><?= $csrf ?><input type="hidden" name="id" value="<?= h($p['id']) ?>">
            <button class="mini danger" name="action" value="delete">Borrar</button></form>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </section>
  <?php endforeach; ?>

  <?php if ($huerfanos): ?>
  <div class="warn-box"><b>Equipos sin categoría (no se ven en la página):</b>
    <?php foreach ($huerfanos as $p): ?> <a class="mini" href="?edit=<?= rawurlencode($p['id']) ?>"><?= h($p['nombre']) ?></a><?php endforeach; ?>
  </div>
  <?php endif; ?>

<?php elseif ($view === 'form'):
    $editId = (string)($_GET['edit'] ?? '');
    $i = $editId !== '' ? renta_find($C['productos'], $editId) : -1;
    $isNew = $i < 0;
    $p = $isNew ? ['id' => '', 'nombre' => '', 'cat' => (string)($_GET['cat'] ?? ''), 'detalle' => '', 'precio' => '', 'unidades' => 1, 'disponible' => true, 'foto' => '']
                : $C['productos'][$i];
    if ($p['cat'] === '' || !isset($catName[$p['cat']])) $p['cat'] = $C['categorias'][0]['id'] ?? '';
    $ico = $catName[$p['cat']]['icono'] ?? 'accesorio';
?>
  <a class="mini back" href="<?= SELF ?><?= $isNew ? '' : '#p-' . h($p['id']) ?>">← Volver al catálogo</a>
  <div class="rhead"><div><h1><?= $isNew ? 'Agregar equipo' : 'Editar equipo' ?></h1>
    <?php if (!$isNew): ?><p class="muted small"><?= h($p['nombre']) ?></p><?php endif; ?></div></div>
  <?php if (!$C['categorias']): ?>
    <div class="warn-box">Primero crea una categoría. <a class="mini" href="?cats=1">Ir a categorías</a></div>
  <?php else: ?>
  <form class="pform" method="post" enctype="multipart/form-data" action="<?= SELF ?>">
    <?= $csrf ?><input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?= h($p['id']) ?>">
    <div class="fbox">
      <label class="fl">Nombre<input name="nombre" required maxlength="90" value="<?= h($p['nombre']) ?>" placeholder="Ej. Sony FX3" <?= $isNew ? 'autofocus' : '' ?>></label>
      <label class="fl">Categoría
        <select name="cat" id="fCat">
          <?php foreach ($C['categorias'] as $k): ?><option value="<?= h($k['id']) ?>" data-ico="<?= h($k['icono']) ?>" <?= $k['id'] === $p['cat'] ? 'selected' : '' ?>><?= h($k['nombre']) ?></option><?php endforeach; ?>
        </select></label>
      <label class="fl">Qué incluye / detalle<textarea name="detalle" rows="2" maxlength="300" placeholder="Ej. Incluye 2 pilas, SD 128 GB y cargador"><?= h($p['detalle']) ?></textarea></label>
      <div class="two">
        <label class="fl">Precio por día (MXN)<input name="precio" type="number" inputmode="numeric" min="0" step="1" required value="<?= h($p['precio']) ?>" placeholder="1500"></label>
        <label class="fl">Unidades<input name="unidades" type="number" inputmode="numeric" min="1" max="99" value="<?= (int)$p['unidades'] ?>"></label>
      </div>
      <p class="hint">Si tienes más de una pieza (por ejemplo 3 memorias), el cliente puede elegir cuántas en su carrito.</p>
      <label class="chk"><input type="checkbox" name="disponible" value="1" <?= $p['disponible'] ? 'checked' : '' ?>> Disponible para rentar</label>
      <p class="hint">Si lo desmarcas, sigue en la página con la etiqueta “No disponible” y no se puede agregar al carrito.</p>
    </div>
    <div class="fbox">
      <div class="pprev" id="pPrev"><?= thumb_html($p, $ico) ?></div>
      <label class="fl">Foto<input type="file" name="foto" id="fFoto" accept="image/jpeg,image/png,image/webp"></label>
      <p class="hint">Queda mejor en <b>PNG sin fondo</b>: el equipo “flota” sobre la tarjeta. Un JPG también sirve (se muestra a sangre). Se ajusta sola a 1000 px.</p>
      <?php if (renta_foto_url($p['foto'])): ?><label class="chk"><input type="checkbox" name="quitar_foto" value="1"> Quitar la foto actual</label><?php endif; ?>
    </div>
    <div class="pbtns">
      <button class="btn" type="submit"><?= $isNew ? 'Agregar al catálogo' : 'Guardar cambios' ?></button>
      <a class="mini" href="<?= SELF ?>">Cancelar</a>
    </div>
  </form>
  <script>
  (function(){
    var input = document.getElementById('fFoto'), prev = document.getElementById('pPrev');
    input.addEventListener('change', function(){
      var f = input.files && input.files[0]; if (!f) return;
      var img = document.createElement('img');
      img.className = /png|webp/.test(f.type) ? 'cut' : 'full';
      img.src = URL.createObjectURL(f); img.alt = '';
      prev.innerHTML = ''; prev.appendChild(img);
    });
  })();
  </script>
  <?php endif; ?>

<?php elseif ($view === 'cats'):
    $cuenta = [];
    foreach ($C['productos'] as $p) $cuenta[$p['cat']] = ($cuenta[$p['cat']] ?? 0) + 1;
?>
  <a class="mini back" href="<?= SELF ?>">← Volver al catálogo</a>
  <div class="rhead"><div><h1>Categorías</h1><p class="muted small">El orden de aquí es el orden de la página. Una categoría sin equipos no se muestra.</p></div></div>
  <div class="rows">
    <?php foreach ($C['categorias'] as $n => $k): $cnt = $cuenta[$k['id']] ?? 0; ?>
    <div class="catrow">
      <form method="post" id="c-<?= h($k['id']) ?>"><?= $csrf ?><input type="hidden" name="action" value="cat_save"><input type="hidden" name="id" value="<?= h($k['id']) ?>"></form>
      <label class="fl">Nombre<input form="c-<?= h($k['id']) ?>" name="nombre" required maxlength="40" value="<?= h($k['nombre']) ?>"></label>
      <label class="fl wide">Descripción<input form="c-<?= h($k['id']) ?>" name="desc" maxlength="200" value="<?= h($k['desc']) ?>"></label>
      <label class="fl">Ícono sin foto
        <select form="c-<?= h($k['id']) ?>" name="icono"><?php foreach (RENTA_ICONOS as $ik => $iv): ?><option value="<?= $ik ?>" <?= $ik === $k['icono'] ? 'selected' : '' ?>><?= h($iv) ?></option><?php endforeach; ?></select></label>
      <div class="racts">
        <button class="mini" form="c-<?= h($k['id']) ?>" type="submit">Guardar</button>
        <form method="post"><?= $csrf ?><input type="hidden" name="id" value="<?= h($k['id']) ?>"><input type="hidden" name="dir" value="-1"><button class="mini ico" name="action" value="cat_move" aria-label="Subir" <?= $n === 0 ? 'disabled' : '' ?>>↑</button></form>
        <form method="post"><?= $csrf ?><input type="hidden" name="id" value="<?= h($k['id']) ?>"><input type="hidden" name="dir" value="1"><button class="mini ico" name="action" value="cat_move" aria-label="Bajar" <?= $n === count($C['categorias']) - 1 ? 'disabled' : '' ?>>↓</button></form>
        <form method="post" onsubmit="return confirm('¿Borrar la categoría <?= h(addslashes($k['nombre'])) ?>?')"><?= $csrf ?><input type="hidden" name="id" value="<?= h($k['id']) ?>">
          <button class="mini danger" name="action" value="cat_delete" <?= $cnt ? 'disabled title="Tiene ' . $cnt . ' equipos"' : '' ?>>Borrar</button></form>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <h2>Nueva categoría</h2>
  <form class="rows" method="post"><?= $csrf ?><input type="hidden" name="action" value="cat_save"><input type="hidden" name="id" value="">
    <div class="catrow">
      <label class="fl">Nombre<input name="nombre" required maxlength="40" placeholder="Ej. Iluminación"></label>
      <label class="fl wide">Descripción<input name="desc" maxlength="200" placeholder="Una línea que describa la categoría"></label>
      <label class="fl">Ícono sin foto<select name="icono"><?php foreach (RENTA_ICONOS as $ik => $iv): ?><option value="<?= $ik ?>"><?= h($iv) ?></option><?php endforeach; ?></select></label>
      <div class="racts"><button class="btn small" type="submit">Crear</button></div>
    </div>
  </form>

<?php else: // ================= métricas =================
    $period = (int)($_GET['p'] ?? 30);
    if (!in_array($period, [7, 30, 90], true)) $period = 30;
    $from = strtotime('today') - ($period - 1) * 86400;
    $MES = ['ene','feb','mar','abr','may','jun','jul','ago','sep','oct','nov','dic'];
    $DIA = ['lun','mar','mié','jue','vie','sáb','dom'];

    $daily = [];
    for ($d = 0; $d < $period; $d++) {
        $ts = strtotime("+$d day", $from);
        $daily[date('Y-m-d', $ts)] = ['l' => date('j', $ts) . ' ' . $MES[date('n', $ts) - 1],
            'f' => $DIA[date('N', $ts) - 1] . ' ' . date('j', $ts) . ' ' . $MES[date('n', $ts) - 1], 'v' => 0];
    }
    $week = array_fill(0, 7, 0);
    $top = []; $added = 0; $one = 0; $cart = 0; $valor = 0; $dSum = 0; $dN = 0; $recent = []; $all = 0;
    $current = [];
    foreach ($C['productos'] as $p) $current[$p['id']] = $p['nombre'];

    foreach (renta_read_events() as $e) {
        $all++;
        $t = (int)$e['t'];
        if ($t < $from) continue;
        if ($e['k'] === 'agregar') { $added++; continue; }
        if ($e['k'] !== 'uno' && $e['k'] !== 'carrito') continue;
        $key = date('Y-m-d', $t);
        if (isset($daily[$key])) $daily[$key]['v']++;
        $week[(int)date('N', $t) - 1]++;
        $e['k'] === 'uno' ? $one++ : $cart++;
        $valor += (int)($e['tot'] ?? 0);
        if (($e['d'] ?? 0) > 0) { $dSum += (int)$e['d']; $dN++; }
        foreach ((array)($e['ids'] ?? []) as $j => $pid) {
            $top[$pid]['n'] = $current[$pid] ?? ($e['n'][$j] ?? $pid);
            $top[$pid]['c'] = ($top[$pid]['c'] ?? 0) + 1;
        }
        $recent[] = $e;
    }
    $req = $one + $cart;
    uasort($top, fn($a, $b) => $b['c'] <=> $a['c']);
    $top = array_slice($top, 0, 10, true);
    $recent = array_slice(array_reverse($recent), 0, 15);
    $maxTop = $top ? max(array_column($top, 'c')) : 1;
    $pct = fn($n) => $req ? round($n / $req * 100) : 0;
?>
  <div class="rhead">
    <div>
      <h1>Métricas</h1>
      <p class="muted small">Se cuenta cada vez que alguien abre WhatsApp desde el catálogo o agrega un equipo al carrito.</p>
    </div>
    <nav class="pillnav" aria-label="Periodo">
      <?php foreach ([7, 30, 90] as $opt): ?><a class="<?= $opt === $period ? 'on' : '' ?>" href="?v=metricas&amp;p=<?= $opt ?>"><?= $opt ?> días</a><?php endforeach; ?>
    </nav>
  </div>

  <div class="kpis">
    <div class="kpi hero"><span>Solicitudes por WhatsApp</span><b><?= number_format($req) ?></b><small>últimos <?= $period ?> días</small></div>
    <div class="kpi"><span>Valor cotizado</span><b><?= renta_money($valor) ?></b><small>suma de los totales estimados que pidieron</small></div>
    <div class="kpi"><span>Agregados al carrito</span><b><?= number_format($added) ?></b><small>equipos que alguien puso en su carrito</small></div>
    <div class="kpi"><span>Días por renta</span><b><?= $dN ? number_format($dSum / $dN, 1) : '—' ?></b><small>promedio en solicitudes con fechas</small></div>
  </div>

  <?php if (!$req && !$added): ?>
    <div class="card2"><div class="empty2">Aún no hay actividad en este periodo.<br>Aparecerá en cuanto la gente use el catálogo<?= $all ? ' (hay ' . $all . ' registros más antiguos)' : '' ?>.</div></div>
  <?php else: ?>
  <div class="cgrid">
    <section class="card2 wide">
      <h2>Solicitudes por día</h2><p class="sub">Aperturas de WhatsApp · últimos <?= $period ?> días</p>
      <div class="chart" id="chDaily"></div>
      <details class="tbl"><summary>Ver en tabla</summary><div class="tscroll"><table class="dtable">
        <tr><th>Día</th><th>Solicitudes</th></tr>
        <?php foreach (array_reverse($daily) as $row): ?><tr><td><?= h($row['f']) ?></td><td class="n"><?= $row['v'] ?></td></tr><?php endforeach; ?>
      </table></div></details>
    </section>

    <section class="card2">
      <h2>Por día de la semana</h2><p class="sub">Qué días escriben más</p>
      <div class="chart" id="chWeek"></div>
      <details class="tbl"><summary>Ver en tabla</summary><table class="dtable">
        <tr><th>Día</th><th>Solicitudes</th></tr>
        <?php foreach ($DIA as $n => $dn): ?><tr><td><?= $dn ?></td><td class="n"><?= $week[$n] ?></td></tr><?php endforeach; ?>
      </table></details>
    </section>

    <section class="card2">
      <h2>Cómo piden</h2><p class="sub">Un solo equipo o carrito completo</p>
      <div class="split">
        <div><span>Solo un equipo</span><b><?= number_format($one) ?></b><em><?= $pct($one) ?>% de las solicitudes</em></div>
        <div><span>Carrito</span><b><?= number_format($cart) ?></b><em><?= $pct($cart) ?>% de las solicitudes</em></div>
      </div>
    </section>

    <section class="card2 wide">
      <h2>Equipos más pedidos</h2><p class="sub">Veces que aparecen en una solicitud por WhatsApp</p>
      <?php if (!$top): ?><div class="empty2">Sin solicitudes en este periodo.</div><?php else: ?>
      <ol class="topl">
        <?php foreach ($top as $t): ?>
        <li><span class="tn"><?= h($t['n']) ?></span><span class="tb"><i style="width:<?= max(1, round($t['c'] / $maxTop * 100)) ?>%"></i></span><span class="tv"><?= $t['c'] ?></span></li>
        <?php endforeach; ?>
      </ol>
      <?php endif; ?>
    </section>

    <section class="card2 wide">
      <h2>Solicitudes recientes</h2><p class="sub">Lo que pidieron (la conversación sigue en tu WhatsApp)</p>
      <?php if (!$recent): ?><div class="empty2">Sin solicitudes en este periodo.</div><?php else: ?>
      <div class="tscroll"><table class="dtable">
        <tr><th>Fecha</th><th>Tipo</th><th>Equipo</th><th>Días</th><th>Total est.</th></tr>
        <?php foreach ($recent as $e): ?>
        <tr>
          <td><?= $DIA[date('N', $e['t']) - 1] . ' ' . date('j', $e['t']) . ' ' . $MES[date('n', $e['t']) - 1] . ' · ' . date('H:i', $e['t']) ?></td>
          <td><?= $e['k'] === 'carrito' ? 'Carrito' : 'Un equipo' ?></td>
          <td><?= h(implode(', ', (array)($e['n'] ?? []))) ?></td>
          <td class="n"><?= ($e['d'] ?? 0) ? (int)$e['d'] : '—' ?></td>
          <td class="n"><?= renta_money($e['tot'] ?? 0) ?><?= ($e['d'] ?? 0) ? '' : '/día' ?></td>
        </tr>
        <?php endforeach; ?>
      </table></div>
      <?php endif; ?>
    </section>
  </div>

  <script>
  (function(){
    var DAILY = <?= json_encode(array_values($daily), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?>;
    var WEEK = <?= json_encode($week) ?>, WEEKL = <?= json_encode($DIA, JSON_UNESCAPED_UNICODE) ?>;
    var AC = '#BE4257', GRID = 'rgba(200,214,210,.12)', SURF = '#111417', NS = 'http://www.w3.org/2000/svg';

    function el(n, a, parent){ var e = document.createElementNS(NS, n); for (var k in a) e.setAttribute(k, a[k]); if (parent) parent.appendChild(e); return e; }
    function txt(parent, a, s){ var t = el('text', a, parent); t.textContent = s; return t; }
    function nice(max){
      if (max <= 4) return { top: Math.max(1, max), step: 1 };
      var raw = max / 4, mag = Math.pow(10, Math.floor(Math.log10(raw))), step = [1, 2, 5, 10].map(function(x){ return x * mag; }).filter(function(x){ return x >= raw; })[0];
      return { top: Math.ceil(max / step) * step, step: step };
    }
    function label(v){ return v + (v === 1 ? ' solicitud' : ' solicitudes'); }
    function tipFor(host){
      var t = document.createElement('div'); t.className = 'tip'; t.hidden = true;
      t.appendChild(document.createElement('b')); t.appendChild(document.createElement('span'));
      host.appendChild(t); return t;
    }
    function tipAt(t, x, y, value, name, W){
      t.firstChild.textContent = value; t.lastChild.textContent = name; t.hidden = false;
      var w = t.offsetWidth, h = t.offsetHeight;
      t.style.left = Math.max(0, Math.min(W - w, x - w / 2)) + 'px';
      t.style.top = Math.max(-6, y - h - 12) + 'px';
    }
    function colPath(x, y, w, h, r){
      r = Math.min(r, w / 2, h);
      return 'M' + x + ',' + (y + h) + 'V' + (y + r) + 'A' + r + ',' + r + ' 0 0 1 ' + (x + r) + ',' + y +
             'H' + (x + w - r) + 'A' + r + ',' + r + ' 0 0 1 ' + (x + w) + ',' + (y + r) + 'V' + (y + h) + 'Z';
    }

    function lineChart(host, pts){
      host.innerHTML = '';
      var W = host.clientWidth || 600, H = 220, L = 34, R = 12, T = 12, B = 28, pw = W - L - R, ph = H - T - B, last = pts.length - 1;
      var n = nice(Math.max.apply(null, pts.map(function(p){ return p.v; })));
      var X = function(i){ return L + (last ? i * pw / last : pw / 2); }, Y = function(v){ return T + ph - v / n.top * ph; };
      var svg = el('svg', { width: W, height: H, viewBox: '0 0 ' + W + ' ' + H, role: 'img', 'aria-label': 'Solicitudes por día', tabindex: 0 }, host);
      for (var t = 0; t <= n.top; t += n.step) {
        el('line', { x1: L, x2: W - R, y1: Y(t), y2: Y(t), stroke: GRID, 'stroke-width': 1 }, svg);
        txt(svg, { x: L - 8, y: Y(t) + 4, 'text-anchor': 'end', 'class': 'ax' }, t);
      }
      [0, Math.round(last / 2), last].forEach(function(i, k){
        txt(svg, { x: X(i), y: H - 8, 'text-anchor': k === 0 ? 'start' : (k === 2 ? 'end' : 'middle'), 'class': 'ax' }, pts[i].l);
      });
      var d = pts.map(function(p, i){ return (i ? 'L' : 'M') + X(i).toFixed(1) + ',' + Y(p.v).toFixed(1); }).join('');
      el('path', { d: d + 'L' + X(last).toFixed(1) + ',' + Y(0) + 'L' + X(0).toFixed(1) + ',' + Y(0) + 'Z', fill: AC, 'fill-opacity': .1 }, svg);
      el('path', { d: d, fill: 'none', stroke: AC, 'stroke-width': 2, 'stroke-linejoin': 'round', 'stroke-linecap': 'round' }, svg);
      el('circle', { cx: X(last), cy: Y(pts[last].v), r: 4, fill: AC, stroke: SURF, 'stroke-width': 2 }, svg);

      var cross = el('line', { y1: T, y2: T + ph, stroke: 'rgba(230,236,234,.4)', 'stroke-width': 1, visibility: 'hidden' }, svg);
      var dot = el('circle', { r: 4, fill: AC, stroke: SURF, 'stroke-width': 2, visibility: 'hidden' }, svg);
      var hit = el('rect', { x: L, y: 0, width: pw, height: T + ph, fill: 'transparent' }, svg);
      var tip = tipFor(host), cur = last;
      function show(i){
        cur = i; var x = X(i), y = Y(pts[i].v);
        cross.setAttribute('x1', x); cross.setAttribute('x2', x); cross.setAttribute('visibility', 'visible');
        dot.setAttribute('cx', x); dot.setAttribute('cy', y); dot.setAttribute('visibility', 'visible');
        tipAt(tip, x, y, label(pts[i].v), pts[i].f, W);
      }
      function hide(){ cross.setAttribute('visibility', 'hidden'); dot.setAttribute('visibility', 'hidden'); tip.hidden = true; }
      hit.addEventListener('pointermove', function(e){
        var r = svg.getBoundingClientRect(), px = (e.clientX - r.left) * W / r.width;
        show(Math.max(0, Math.min(last, Math.round((px - L) / pw * last))));
      });
      hit.addEventListener('pointerleave', hide);
      svg.addEventListener('focus', function(){ show(cur); });
      svg.addEventListener('blur', hide);
      svg.addEventListener('keydown', function(e){
        if (e.key === 'ArrowLeft') { show(Math.max(0, cur - 1)); e.preventDefault(); }
        if (e.key === 'ArrowRight') { show(Math.min(last, cur + 1)); e.preventDefault(); }
      });
    }

    function colChart(host, vals, names){
      host.innerHTML = '';
      var W = host.clientWidth || 400, H = 210, L = 6, R = 6, T = 24, B = 26, pw = W - L - R, ph = H - T - B;
      var band = pw / vals.length, bw = Math.min(24, band * .56), max = Math.max.apply(null, vals), top = Math.max(1, max), mi = vals.indexOf(max);
      var svg = el('svg', { width: W, height: H, viewBox: '0 0 ' + W + ' ' + H, role: 'img', 'aria-label': 'Solicitudes por día de la semana' }, host);
      el('line', { x1: L, x2: W - R, y1: T + ph, y2: T + ph, stroke: GRID, 'stroke-width': 1 }, svg);
      var tip = tipFor(host);
      vals.forEach(function(v, i){
        var h = v / top * ph, x = L + band * i + (band - bw) / 2, y = T + ph - h, cx = L + band * i + band / 2;
        var g = el('g', { 'class': 'bar', tabindex: 0, 'aria-label': names[i] + ': ' + label(v) }, svg);
        el('rect', { x: L + band * i, y: 0, width: band, height: T + ph, fill: 'transparent' }, g);
        if (h > 0) el('path', { d: colPath(x, y, bw, h, 4), fill: AC }, g);
        if (i === mi && max > 0) txt(svg, { x: cx, y: y - 7, 'text-anchor': 'middle', 'class': 'val' }, v);
        txt(svg, { x: cx, y: H - 8, 'text-anchor': 'middle', 'class': 'ax' }, names[i]);
        var on = function(){ tipAt(tip, cx, y, label(v), names[i], W); }, off = function(){ tip.hidden = true; };
        g.addEventListener('pointerenter', on); g.addEventListener('pointerleave', off);
        g.addEventListener('focus', on); g.addEventListener('blur', off);
      });
    }

    function draw(){
      var a = document.getElementById('chDaily'), b = document.getElementById('chWeek');
      if (a) lineChart(a, DAILY);
      if (b) colChart(b, WEEK, WEEKL);
    }
    draw();
    var rt; addEventListener('resize', function(){ clearTimeout(rt); rt = setTimeout(draw, 150); });
  })();
  </script>
  <?php endif; ?>
<?php endif; ?>
</div>
</body></html>
