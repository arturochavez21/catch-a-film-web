<?php
// ===== Panel de administración de galerías (solo Arturo) =====
require_once __DIR__ . '/lib.php';
boot_session();
security_headers();

function admin_page($body, $title = 'Admin', $withJs = false) {
    echo "<!doctype html><html lang=es><head><meta charset=utf8>";
    echo "<meta name=viewport content='width=device-width,initial-scale=1'>";
    echo "<meta name=robots content='noindex,nofollow'>";
    echo "<title>" . h($title) . " · Galerías</title>";
    echo "<link rel=stylesheet href='/galerias/assets/gallery.css?v=" . asset_ver('assets/gallery.css') . "'></head><body class='admin'>";
    echo $body;
    if ($withJs) echo "<script src='/galerias/assets/admin.js?v=" . asset_ver('assets/admin.js') . "'></script>";
    echo "</body></html>";
    exit;
}
function rrmdir($dir) {
    if (!is_dir($dir)) return;
    foreach (scandir($dir) as $f) {
        if ($f === '.' || $f === '..') continue;
        $p = "$dir/$f"; is_dir($p) ? rrmdir($p) : @unlink($p);
    }
    @rmdir($dir);
}

$msg = ''; $err = '';

// ---- primera vez: crear contraseña de admin ----
if (!admin_is_setup()) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_ok()) {
        $p = $_POST['pw'] ?? '';
        if (strlen($p) < 8) $err = 'Usa al menos 8 caracteres.';
        else { admin_set_password($p); boot_session(); $_SESSION['admin'] = true;
               header('Location: /galerias/admin.php'); exit; }
    }
    $tok = csrf_token();
    admin_page("<div class='center'>
        <div class='logo'>CAT<b>CH</b></div><h1>Configura tu acceso</h1>
        <p class='muted'>Crea la contraseña de administrador (la usarás para gestionar las galerías).</p>
        " . ($err ? "<p class='err'>" . h($err) . "</p>" : "") . "
        <form method=post class='pwform'><input type=hidden name=csrf value='$tok'>
          <input type=password name=pw placeholder='Nueva contraseña (mín. 8)' autofocus required>
          <button class='btn' type=submit>Crear</button></form></div>", 'Configurar');
}

// ---- login admin ----
if (!admin_authed()) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_ok()) {
        usleep(400000);
        if (admin_login($_POST['pw'] ?? '')) { header('Location: /galerias/admin.php'); exit; }
        $err = 'Contraseña incorrecta.';
    }
    $tok = csrf_token();
    admin_page("<div class='center'>
        <div class='logo'>CAT<b>CH</b></div><h1>Administrar galerías</h1>
        " . ($err ? "<p class='err'>" . h($err) . "</p>" : "") . "
        <form method=post class='pwform'><input type=hidden name=csrf value='$tok'>
          <input type=password name=pw placeholder='Contraseña de admin' autofocus required>
          <button class='btn' type=submit>Entrar</button></form></div>", 'Entrar');
}

// ---- acciones (autenticado) ----
$goto = ''; // para volver a la vista de galería tras una acción
if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_ok()) {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $title = trim($_POST['title'] ?? '');
        $pw    = $_POST['password'] ?? '';
        $months= max(0, (int)($_POST['months'] ?? DEFAULT_EXPIRY_MONTHS));
        if ($title === '' || strlen($pw) < 4) { $err = 'Pon un título y una contraseña (mín. 4).'; }
        else {
            $slug = slugify($title); $base = $slug; $n = 2;
            while (load_gallery($slug)) { $slug = $base . '-' . $n; $n++; }
            @mkdir(orig_dir($slug), 0775, true);
            save_gallery($slug, ['title'=>$title,'pass_hash'=>password_hash($pw,PASSWORD_DEFAULT),'pass_plain'=>$pw,
                'created'=>time(),'expires'=>$months>0?strtotime("+$months months"):0]);
            header('Location: /galerias/admin.php?g=' . rawurlencode($slug)); exit; // va directo a subir fotos
        }
    }
    elseif ($action === 'zip' && valid_slug($_POST['slug'] ?? '')) {
        $slug = $_POST['slug']; $goto = $slug;
        $photos = gallery_photos($slug);
        if (!$photos) { $err = 'No hay fotos en esa galería todavía.'; }
        else {
            @set_time_limit(0);
            $zip = new ZipArchive();
            if ($zip->open(zip_path($slug), ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
                foreach ($photos as $f) { $zip->addFile(orig_dir($slug).'/'.$f, $f); $zip->setCompressionName($f, ZipArchive::CM_STORE); }
                $zip->close();
                $m = load_gallery($slug); $m['count']=count($photos); $m['size']=dir_size(orig_dir($slug)); $m['zipped']=time();
                save_gallery($slug, $m);
                $msg = "Descarga completa lista (" . count($photos) . " fotos).";
            } else { $err = 'No se pudo crear el zip.'; }
        }
    }
    elseif ($action === 'edit' && valid_slug($_POST['slug'] ?? '')) {
        $slug = $_POST['slug']; $goto = $slug; $m = load_gallery($slug);
        if ($m) {
            if (trim($_POST['title'] ?? '') !== '') $m['title'] = trim($_POST['title']);
            if (($_POST['password'] ?? '') !== '') { $m['pass_hash'] = password_hash($_POST['password'], PASSWORD_DEFAULT); $m['pass_plain'] = $_POST['password']; }
            if (($_POST['months'] ?? '') !== '')     $m['expires'] = (int)$_POST['months']>0 ? strtotime('+'.(int)$_POST['months'].' months') : 0;
            save_gallery($slug, $m); $msg = 'Galería actualizada.';
        }
    }
    elseif ($action === 'delphoto' && valid_slug($_POST['slug'] ?? '')) {
        $slug = $_POST['slug']; $goto = $slug; $f = basename($_POST['file'] ?? '');
        if (preg_match('/\.(jpe?g|png)$/i', $f)) {
            @unlink(orig_dir($slug).'/'.$f);
            @unlink(cache_dir($slug).'/thumb/'.preg_replace('/\.(png)$/i','.jpg',$f));
            @unlink(cache_dir($slug).'/web/'.preg_replace('/\.(png)$/i','.jpg',$f));
            if (is_file(zip_path($slug))) @unlink(zip_path($slug));
            $msg = 'Foto eliminada.';
        }
    }
    elseif ($action === 'setcover' && valid_slug($_POST['slug'] ?? '')) {
        $slug = $_POST['slug']; $goto = $slug; $f = basename($_POST['file'] ?? '');
        $m = load_gallery($slug);
        if ($m && in_array($f, gallery_photos($slug), true)) { $m['cover'] = $f; save_gallery($slug, $m); $msg = 'Portada actualizada.'; }
    }
    elseif ($action === 'delete' && valid_slug($_POST['slug'] ?? '')) {
        rrmdir(gal_dir($_POST['slug'])); $msg = 'Galería eliminada.';
    }
    elseif ($action === 'logout') { $_SESSION['admin'] = false; header('Location: /galerias/admin.php'); exit; }
}

$tok = csrf_token();

// ============ VISTA DE UNA GALERÍA (subir fotos, gestionar) ============
$gv = $_GET['g'] ?? '';
if (valid_slug($gv) && ($gm = load_gallery($gv))) {
    $photos = gallery_photos($gv);
    $size = dir_size(orig_dir($gv));
    $hasZip = is_file(zip_path($gv));
    $exp = !empty($gm['expires']) ? date('d/m/Y', (int)$gm['expires']) : 'sin límite';
    $passPlain = $gm['pass_plain'] ?? '';
    $link = SITE_URL . '/galerias/' . $gv;
    $expData = !empty($gm['expires']) ? date('d/m/Y', (int)$gm['expires']) : '';
    $cover = gallery_cover($gv, $gm);
    $grid = '';
    foreach ($photos as $f) {
        $ef = rawurlencode($f);
        $isC = ($f === $cover);
        $grid .= "<div class='pg-item" . ($isC ? ' is-cover' : '') . "' data-f='" . h($f) . "'>
            <img loading='lazy' src='/galerias/media.php?g=$gv&s=thumb&f=$ef'>
            " . ($isC ? "<span class='pg-badge'>&#9733; Portada</span>" : "") . "
            <div class='pg-tools'>
              <form method=post class='pg-cover'><input type=hidden name=csrf value='$tok'><input type=hidden name=action value=setcover>
                <input type=hidden name=slug value='$gv'><input type=hidden name=file value='" . h($f) . "'>
                <button title='Hacer portada'>" . ($isC ? '&#9733;' : '&#9734;') . "</button></form>
              <form method=post class='pg-del' onsubmit=\"return confirm('¿Borrar esta foto?')\"><input type=hidden name=csrf value='$tok'><input type=hidden name=action value=delphoto>
                <input type=hidden name=slug value='$gv'><input type=hidden name=file value='" . h($f) . "'>
                <button title='Borrar'>&times;</button></form>
            </div></div>";
    }
    $zipLine = $hasZip
        ? "<span class='pill ok'>zip listo</span> <span class='muted small'>Se recomienda re-armarlo si agregas o quitas fotos.</span>"
        : "<span class='pill warn'>sin zip</span> <span class='muted small'>Arma el zip cuando termines de subir.</span>";

    admin_page("<div class='admin-wrap'>
        <header class='ahead'>
          <div><a class='mini' href='/galerias/admin.php'>&larr; Todas las galerías</a></div>
          <form method=post><input type=hidden name=csrf value='$tok'><input type=hidden name=action value=logout><button class='mini'>Salir</button></form>
        </header>
        " . ($msg ? "<div class='note ok'>".h($msg)."</div>" : "") . ($err ? "<div class='note bad'>".h($err)."</div>" : "") . "
        <div class='gv-head'>
          <div><div class='eyebrow'>Galería</div><h1>" . h($gm['title']) . "</h1>
            <p class='muted small'>" . count($photos) . " fotos · " . human_bytes($size) . " · disponible hasta $exp" .
              ($passPlain ? " · contraseña: <b>" . h($passPlain) . "</b>" : "") . " ·
            <a target=_blank href='/galerias/" . h($gv) . "'>ver como cliente ↗</a></p></div>
        </div>

        <details class='editbox'>
          <summary>&#9881; Editar galería (título, contraseña, vigencia)</summary>
          <form method=post class='editf2'>
            <input type=hidden name=csrf value='$tok'><input type=hidden name=action value=edit><input type=hidden name=slug value='$gv'>
            <label>Título<input name=title value='" . h($gm['title']) . "'></label>
            <label>Contraseña<input name=password placeholder='dejar vacío para no cambiarla'></label>
            <label>Vigencia (meses · 0 = sin límite)<input name=months type=number min=0 placeholder='ej. 3'></label>
            <button class='btn small'>Guardar cambios</button>
          </form>
        </details>

        <div class='dropzone' id='dz' data-slug='$gv' data-csrf='$tok'>
          <div class='dz-inner'>
            <div class='dz-ico'>&#8593;</div>
            <p><b>Arrastra tus fotos aquí</b> o <label class='dz-btn'>selecciónalas<input type=file id='fileInput' accept='image/jpeg,image/png' multiple hidden></label></p>
            <p class='muted small'>JPG o PNG · se suben en su calidad original</p>
          </div>
          <div class='dz-progress' id='dzProg' hidden><div class='bar'><span id='dzBar'></span></div><span id='dzTxt' class='muted small'></span></div>
        </div>

        <div class='zipbar'>
          <button class='btn small' id='copyLink' data-url='" . h($link) . "' data-pass='" . h($passPlain) . "' data-title='" . h($gm['title']) . "' data-exp='" . h($expData) . "'>&#128279; Generar link (copiar link + contraseña)</button>
          <form method=post onsubmit=\"return confirm('¿Preparar la descarga completa (zip)? Puede tardar en galerías grandes.')\">
            <input type=hidden name=csrf value='$tok'><input type=hidden name=action value=zip><input type=hidden name=slug value='$gv'>
            <button class='btn small ghost2'>&#8595; Preparar descarga (zip)</button>
          </form>
          <div>$zipLine</div>
        </div>
        <div class='copied-note' id='copiedNote' hidden></div>

        <h2>Fotos <span class='muted' id='pgCount'>(" . count($photos) . ")</span></h2>
        <div class='pgrid' id='pgrid'>$grid</div>
        <p class='muted small' id='pgEmpty'" . ($photos ? " hidden" : "") . ">Aún no hay fotos. Arrastra algunas arriba para empezar.</p>
     </div>", 'Galería: ' . ($gm['title'] ?? ''), true);
}

// ============ LISTA DE GALERÍAS ============
$gals = all_galleries();
$rows = '';
foreach ($gals as $g) {
    $slug = $g['slug'];
    $n = count(gallery_photos($slug));
    $size = dir_size(orig_dir($slug));
    $exp = !empty($g['expires']) ? date('d/m/Y', (int)$g['expires']) : 'sin límite';
    $st = is_expired($g) ? "<span class='pill bad'>expirada</span>" : "<span class='pill ok'>activa</span>";
    $zipst = is_file(zip_path($slug)) ? "<span class='pill ok'>zip</span>" : "<span class='pill warn'>sin zip</span>";
    $rows .= "<tr>
        <td><a href='/galerias/admin.php?g=" . h($slug) . "'><b>" . h($g['title']) . "</b></a><br><span class='muted small'>/galerias/" . h($slug) . "</span></td>
        <td>$n fotos<br><span class='muted small'>" . human_bytes($size) . "</span></td>
        <td>$st<br><span class='muted small'>hasta $exp</span></td>
        <td>$zipst</td>
        <td class='acts'>
          <a class='mini' href='/galerias/admin.php?g=" . h($slug) . "'>Gestionar / subir</a>
          <a class='mini' href='/galerias/" . h($slug) . "' target='_blank'>Ver</a>
          <form method=post onsubmit=\"return confirm('¿Borrar la galería y TODAS sus fotos?')\">
            <input type=hidden name=csrf value='$tok'><input type=hidden name=action value=delete><input type=hidden name=slug value='" . h($slug) . "'>
            <button class='mini danger'>Borrar</button></form>
        </td></tr>";
}
if (!$rows) $rows = "<tr><td colspan=5 class='muted'>Aún no hay galerías. Crea la primera abajo.</td></tr>";
$engine = extension_loaded('imagick') ? 'Imagick (color fiel)' : 'GD';

admin_page("<div class='admin-wrap'>
   <header class='ahead'>
     <div class='logo'>CAT<b>CH</b> <span class='muted'>· Galerías</span></div>
     <form method=post><input type=hidden name=csrf value='$tok'><input type=hidden name=action value=logout><button class='mini'>Salir</button></form>
   </header>
   " . ($msg ? "<div class='note ok'>".h($msg)."</div>" : "") . ($err ? "<div class='note bad'>".h($err)."</div>" : "") . "
   <table class='gtable'><thead><tr><th>Galería</th><th>Fotos</th><th>Estado</th><th>Descarga</th><th></th></tr></thead><tbody>$rows</tbody></table>
   <h2>Nueva galería</h2>
   <form method=post class='newf'>
     <input type=hidden name=csrf value='$tok'><input type=hidden name=action value=create>
     <input name=title placeholder='Título (ej. Boda Ana & David)' required>
     <input name=password placeholder='Contraseña para el cliente' required>
     <input name=months type=number min=0 value='" . DEFAULT_EXPIRY_MONTHS . "' title='Meses disponible (0 = sin límite)'>
     <button class='btn' type=submit>Crear y subir fotos</button>
   </form>
   <p class='muted small'>Motor de imagen: $engine · Al crear una galería entrarás directo a subir las fotos (arrastrar y soltar).</p>
 </div>", 'Panel de galerías');
