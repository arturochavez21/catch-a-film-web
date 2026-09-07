<?php
// ===== Vista de galería para el cliente =====
require_once __DIR__ . '/lib.php';
boot_session();
security_headers();

$slug = $_GET['g'] ?? '';
$meta = load_gallery($slug);

function page($title, $body, $slug = '') {
    $t = h($title);
    echo "<!doctype html><html lang=es><head><meta charset=utf8>";
    echo "<meta name=viewport content='width=device-width,initial-scale=1'>";
    echo "<meta name=robots content='noindex,nofollow'>";
    echo "<title>$t · " . h(SITE_NAME) . "</title>";
    echo "<link rel=stylesheet href='/galerias/assets/gallery.css?v=" . asset_ver('assets/gallery.css') . "'></head><body>";
    echo $body;
    if ($slug) echo "<script src='/galerias/assets/gallery.js?v=" . asset_ver('assets/gallery.js') . "'></script>";
    echo "</body></html>";
    exit;
}

if (!$meta) {
    http_response_code(404);
    page('Galería no encontrada',
        "<div class='center'><div class='logo'>CAT<b>CH</b></div>
         <h1>Esta galería no existe</h1>
         <p class='muted'>Verifica el enlace que te compartimos.</p></div>");
}

if (is_expired($meta)) {
    page('Galería expirada',
        "<div class='center'><div class='logo'>CAT<b>CH</b></div>
         <h1>Esta galería ya no está disponible</h1>
         <p class='muted'>El periodo de descarga terminó. Si necesitas tus fotos de nuevo,
         escríbenos por WhatsApp y con gusto la reactivamos.</p>
         <a class='btn' href='" . h(SITE_URL) . "'>Ir al sitio</a></div>");
}

// ---- login por contraseña ----
$error = '';
if (!client_authed($slug)) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!csrf_ok()) {
            $error = 'Sesión expirada, intenta de nuevo.';
        } else {
            usleep(400000); // pequeño freno anti fuerza bruta
            if (client_login($slug, $_POST['password'] ?? '')) {
                header('Location: /galerias/' . rawurlencode($slug));
                exit;
            }
            $error = 'Contraseña incorrecta.';
        }
    }
    $tok = csrf_token();
    $title = h($meta['title'] ?? 'Galería');
    $err = $error ? "<p class='err'>" . h($error) . "</p>" : '';
    $hasCover = gallery_cover($slug, $meta) !== null;
    $heroStyle = $hasCover ? " style=\"background-image:url('/galerias/cover.php?g=$slug')\"" : '';
    $cls = $hasCover ? 'login-hero has-cover' : 'login-hero';
    page($meta['title'] ?? 'Galería',
        "<div class='$cls'$heroStyle>
           <div class='center'>
             <div class='logo'>CAT<b>CH</b><small>A FILM STUDIO</small></div>
             <div class='eyebrow'>Galería privada</div>
             <h1>$title</h1>
             <p class='muted'>Ingresa la contraseña que te compartimos para ver y descargar tus fotos.</p>
             <form method=post class='pwform'>
               <input type=hidden name=csrf value='$tok'>
               <input type=password name=password placeholder='Contraseña' autofocus required>
               <button class='btn' type=submit>Entrar</button>
             </form>
             $err
           </div>
         </div>", $slug);
}

// ---- galería autenticada ----
$photos = gallery_photos($slug);
$count  = count($photos);
$title  = h($meta['title'] ?? 'Galería');
$hasZip = is_file(zip_path($slug));
$expTxt = !empty($meta['expires'])
    ? "Disponible hasta el " . date('d/m/Y', (int)$meta['expires'])
    : '';

$tiles = '';
foreach ($photos as $i => $f) {
    $ef = rawurlencode($f);
    $tiles .= "<figure class='tile' data-full='/galerias/media.php?g=$slug&s=web&f=$ef'>
        <img loading='lazy' src='/galerias/media.php?g=$slug&s=thumb&f=$ef' alt=''>
        <a class='tdl' href='/galerias/download.php?g=$slug&f=$ef' title='Descargar' download>&#8595;</a>
      </figure>";
}

$zipBtn = $hasZip
    ? "<a class='btn' href='/galerias/zip.php?g=$slug'>&#8595; Descargar todo</a>"
    : "<span class='btn ghost' title='Aún preparando la descarga completa'>Descarga completa: en preparación</span>";

page($meta['title'] ?? 'Galería',
    "<header class='ghead'>
        <div class='logo'>CAT<b>CH</b><small>A FILM STUDIO</small></div>
        <div class='eyebrow'>Galería privada</div>
        <h1>$title</h1>
        <div class='gmeta'>
           <span>$count fotos</span>" .
           ($expTxt ? "<span>·</span><span>" . h($expTxt) . "</span>" : "") .
        "</div>
        <div class='gactions'>$zipBtn</div>
     </header>
     <main class='masonry'>$tiles</main>
     <footer class='gfoot'>© " . date('Y') . " " . h(SITE_NAME) . " · <a href='" . h(SITE_URL) . "'>catchafilmstudio.com</a></footer>
     <div class='lightbox' id='lb'>
        <button class='lb-close' id='lbClose' aria-label='Cerrar'>&times;</button>
        <button class='lb-nav prev' id='lbPrev' aria-label='Anterior'>&#8249;</button>
        <img id='lbImg' alt=''>
        <a class='lb-dl' id='lbDl' href='#' download>&#8595; Descargar esta foto</a>
        <button class='lb-nav next' id='lbNext' aria-label='Siguiente'>&#8250;</button>
     </div>", $slug);
