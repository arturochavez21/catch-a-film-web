<?php
// ===== Panel de administración de galerías (solo Arturo) =====
require_once __DIR__ . '/lib.php';
boot_session();
security_headers();

function admin_page($body, $title = 'Admin') {
    echo "<!doctype html><html lang=es><head><meta charset=utf8>";
    echo "<meta name=viewport content='width=device-width,initial-scale=1'>";
    echo "<meta name=robots content='noindex,nofollow'>";
    echo "<title>" . h($title) . " · Galerías</title>";
    echo "<link rel=stylesheet href='/galerias/assets/gallery.css'></head><body class='admin'>";
    echo $body . "</body></html>";
    exit;
}
function rrmdir($dir) {
    if (!is_dir($dir)) return;
    foreach (scandir($dir) as $f) {
        if ($f === '.' || $f === '..') continue;
        $p = "$dir/$f";
        is_dir($p) ? rrmdir($p) : @unlink($p);
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
        <form method=post class='pwform'>
          <input type=hidden name=csrf value='$tok'>
          <input type=password name=pw placeholder='Nueva contraseña (mín. 8)' autofocus required>
          <button class='btn' type=submit>Crear</button>
        </form></div>", 'Configurar');
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
        <form method=post class='pwform'>
          <input type=hidden name=csrf value='$tok'>
          <input type=password name=pw placeholder='Contraseña de admin' autofocus required>
          <button class='btn' type=submit>Entrar</button>
        </form></div>", 'Entrar');
}

// ---- acciones (autenticado) ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_ok()) {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $title = trim($_POST['title'] ?? '');
        $pw    = $_POST['password'] ?? '';
        $months= max(0, (int)($_POST['months'] ?? DEFAULT_EXPIRY_MONTHS));
        if ($title === '' || strlen($pw) < 4) {
            $err = 'Pon un título y una contraseña (mín. 4).';
        } else {
            $slug = slugify($title); $base = $slug; $n = 2;
            while (load_gallery($slug)) { $slug = $base . '-' . $n; $n++; }
            @mkdir(orig_dir($slug), 0775, true);
            save_gallery($slug, [
                'title'     => $title,
                'pass_hash' => password_hash($pw, PASSWORD_DEFAULT),
                'created'   => time(),
                'expires'   => $months > 0 ? strtotime("+$months months") : 0,
            ]);
            $msg = "Galería creada. Sube las fotos por File Manager a: data/galerias/$slug/originals/  y luego dale «Preparar descarga».";
        }
    }
    elseif ($action === 'zip' && valid_slug($_POST['slug'] ?? '')) {
        $slug = $_POST['slug'];
        $photos = gallery_photos($slug);
        if (!$photos) { $err = 'No hay fotos en esa galería todavía.'; }
        else {
            @set_time_limit(0);
            $zip = new ZipArchive();
            if ($zip->open(zip_path($slug), ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
                foreach ($photos as $f) {
                    $zip->addFile(orig_dir($slug) . '/' . $f, $f);
                    $zip->setCompressionName($f, ZipArchive::CM_STORE); // sin recomprimir (rápido)
                }
                $zip->close();
                $m = load_gallery($slug);
                $m['count'] = count($photos);
                $m['size']  = dir_size(orig_dir($slug));
                $m['zipped']= time();
                save_gallery($slug, $m);
                $msg = "Descarga completa lista (" . count($photos) . " fotos).";
            } else { $err = 'No se pudo crear el zip.'; }
        }
    }
    elseif ($action === 'edit' && valid_slug($_POST['slug'] ?? '')) {
        $slug = $_POST['slug']; $m = load_gallery($slug);
        if ($m) {
            if (trim($_POST['title'] ?? '') !== '') $m['title'] = trim($_POST['title']);
            if (($_POST['password'] ?? '') !== '')  $m['pass_hash'] = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $months = $_POST['months'] ?? '';
            if ($months !== '') $m['expires'] = (int)$months > 0 ? strtotime('+' . (int)$months . ' months') : 0;
            save_gallery($slug, $m);
            $msg = 'Galería actualizada.';
        }
    }
    elseif ($action === 'delete' && valid_slug($_POST['slug'] ?? '')) {
        rrmdir(gal_dir($_POST['slug']));
        $msg = 'Galería eliminada.';
    }
    elseif ($action === 'logout') {
        $_SESSION['admin'] = false; header('Location: /galerias/admin.php'); exit;
    }
}

// ---- dashboard ----
$tok = csrf_token();
$gals = all_galleries();
$rows = '';
foreach ($gals as $g) {
    $slug = $g['slug'];
    $photos = gallery_photos($slug);
    $size = dir_size(orig_dir($slug));
    $exp  = !empty($g['expires']) ? date('d/m/Y', (int)$g['expires']) : 'sin límite';
    $expired = is_expired($g);
    $hasZip  = is_file(zip_path($slug));
    $st = $expired ? "<span class='pill bad'>expirada</span>"
                   : "<span class='pill ok'>activa</span>";
    $zipst = $hasZip ? "<span class='pill ok'>zip listo</span>"
                     : "<span class='pill warn'>sin zip</span>";
    $rows .= "<tr>
        <td><b>" . h($g['title']) . "</b><br><span class='muted'>/galerias/" . h($slug) . "</span></td>
        <td>" . count($photos) . " fotos<br><span class='muted'>" . human_bytes($size) . "</span></td>
        <td>$st<br><span class='muted'>hasta $exp</span></td>
        <td>$zipst</td>
        <td class='acts'>
          <a class='mini' href='/galerias/" . h($slug) . "' target='_blank'>Ver</a>
          <form method=post onsubmit=\"return confirm('¿Preparar la descarga completa? Puede tardar en galerías grandes.')\">
            <input type=hidden name=csrf value='$tok'><input type=hidden name=action value=zip>
            <input type=hidden name=slug value='" . h($slug) . "'>
            <button class='mini'>Preparar descarga</button>
          </form>
          <details><summary class='mini'>Editar</summary>
            <form method=post class='editf'>
              <input type=hidden name=csrf value='$tok'><input type=hidden name=action value=edit>
              <input type=hidden name=slug value='" . h($slug) . "'>
              <input name=title placeholder='Nuevo título'>
              <input name=password placeholder='Nueva contraseña'>
              <input name=months type=number min=0 placeholder='Meses (0=sin límite)'>
              <button class='mini'>Guardar</button>
            </form>
          </details>
          <form method=post onsubmit=\"return confirm('¿Borrar la galería y TODAS sus fotos? No se puede deshacer.')\">
            <input type=hidden name=csrf value='$tok'><input type=hidden name=action value=delete>
            <input type=hidden name=slug value='" . h($slug) . "'>
            <button class='mini danger'>Borrar</button>
          </form>
        </td></tr>";
}
if (!$rows) $rows = "<tr><td colspan=5 class='muted'>Aún no hay galerías. Crea la primera abajo.</td></tr>";

$engine = extension_loaded('imagick') ? 'Imagick (color fiel)' : 'GD';
admin_page("<div class='admin-wrap'>
   <header class='ahead'>
     <div class='logo'>CAT<b>CH</b> <span class='muted'>· Galerías</span></div>
     <form method=post><input type=hidden name=csrf value='$tok'><input type=hidden name=action value=logout><button class='mini'>Salir</button></form>
   </header>
   " . ($msg ? "<div class='note ok'>" . h($msg) . "</div>" : "") . "
   " . ($err ? "<div class='note bad'>" . h($err) . "</div>" : "") . "
   <table class='gtable'><thead><tr><th>Galería</th><th>Fotos</th><th>Estado</th><th>Descarga</th><th></th></tr></thead><tbody>$rows</tbody></table>

   <h2>Nueva galería</h2>
   <form method=post class='newf'>
     <input type=hidden name=csrf value='$tok'><input type=hidden name=action value=create>
     <input name=title placeholder='Título (ej. Boda Ana & David)' required>
     <input name=password placeholder='Contraseña para el cliente' required>
     <input name=months type=number min=0 value='" . DEFAULT_EXPIRY_MONTHS . "' title='Meses disponible (0 = sin límite)'>
     <button class='btn' type=submit>Crear galería</button>
   </form>
   <p class='muted small'>Motor de imagen: $engine · Tras crear, sube las fotos por File Manager a <code>data/galerias/&lt;slug&gt;/originals/</code> y dale «Preparar descarga».</p>
 </div>", 'Panel de galerías');
