<?php
// ===== Núcleo compartido de las galerías =====
require_once __DIR__ . '/config.php';

// --- Sesión segura ---
function boot_session() {
    if (session_status() === PHP_SESSION_ACTIVE) return;
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
          || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    session_set_cookie_params([
        'lifetime' => 0, 'path' => '/', 'httponly' => true,
        'samesite' => 'Lax', 'secure' => $https,
    ]);
    session_name('catchgal');
    session_start();
}

function csrf_token() {
    boot_session();
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
    return $_SESSION['csrf'];
}
function csrf_ok() {
    boot_session();
    return isset($_POST['csrf'], $_SESSION['csrf'])
        && hash_equals($_SESSION['csrf'], $_POST['csrf']);
}

// --- Validaciones ---
function valid_slug($s) { return is_string($s) && preg_match('/^[a-z0-9][a-z0-9-]{1,60}$/', $s); }
function slugify($t) {
    $t = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $t);
    $t = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $t));
    $t = trim($t, '-');
    return substr($t, 0, 60) ?: 'galeria';
}
function gal_dir($slug)   { return DATA_DIR . '/galerias/' . $slug; }
function orig_dir($slug)  { return gal_dir($slug) . '/originals'; }
function cache_dir($slug) { return gal_dir($slug) . '/cache'; }
function zip_path($slug)  { return gal_dir($slug) . '/' . $slug . '.zip'; }

// --- Meta de la galería (gallery.json) ---
function load_gallery($slug) {
    if (!valid_slug($slug)) return null;
    $f = gal_dir($slug) . '/gallery.json';
    if (!is_file($f)) return null;
    $m = json_decode(file_get_contents($f), true);
    return is_array($m) ? $m : null;
}
function save_gallery($slug, $meta) {
    @mkdir(gal_dir($slug), 0775, true);
    file_put_contents(gal_dir($slug) . '/gallery.json',
        json_encode($meta, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
}
function all_galleries() {
    $base = DATA_DIR . '/galerias';
    $out = [];
    foreach (glob($base . '/*', GLOB_ONLYDIR) ?: [] as $d) {
        $slug = basename($d);
        $m = load_gallery($slug);
        if ($m) { $m['slug'] = $slug; $out[] = $m; }
    }
    usort($out, fn($a, $b) => ($b['created'] ?? 0) <=> ($a['created'] ?? 0));
    return $out;
}

// Fotos (originales) ordenadas por nombre
function gallery_photos($slug) {
    $files = [];
    foreach (glob(orig_dir($slug) . '/*') ?: [] as $p) {
        if (is_file($p) && preg_match('/\.(jpe?g|png)$/i', $p)) $files[] = basename($p);
    }
    natcasesort($files);
    return array_values($files);
}

function is_expired($meta) {
    return !empty($meta['expires']) && time() > (int)$meta['expires'];
}

// --- Autenticación de cliente por galería ---
function client_authed($slug) {
    boot_session();
    return !empty($_SESSION['gal'][$slug]);
}
function client_login($slug, $password) {
    $m = load_gallery($slug);
    if (!$m || empty($m['pass_hash'])) return false;
    if (password_verify($password, $m['pass_hash'])) {
        boot_session();
        $_SESSION['gal'][$slug] = true;
        return true;
    }
    return false;
}

// --- Autenticación de administrador (Arturo) ---
function admin_hash_file() { return DATA_DIR . '/admin.hash'; }
function admin_is_setup()  { return is_file(admin_hash_file()); }
function admin_set_password($pw) {
    @mkdir(DATA_DIR, 0775, true);
    file_put_contents(admin_hash_file(), password_hash($pw, PASSWORD_DEFAULT), LOCK_EX);
}
function admin_authed() { boot_session(); return !empty($_SESSION['admin']); }
function admin_login($pw) {
    if (!admin_is_setup()) return false;
    if (password_verify($pw, file_get_contents(admin_hash_file()))) {
        boot_session(); $_SESSION['admin'] = true; return true;
    }
    return false;
}

// --- Utilidades ---
function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function human_bytes($b) {
    $u = ['B','KB','MB','GB','TB']; $i = 0;
    while ($b >= 1024 && $i < 4) { $b /= 1024; $i++; }
    return round($b, $b < 10 && $i > 0 ? 1 : 0) . ' ' . $u[$i];
}
function dir_size($dir) {
    $s = 0;
    foreach (glob($dir . '/*') ?: [] as $p) $s += is_file($p) ? filesize($p) : 0;
    return $s;
}
function security_headers() {
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: no-referrer');
    header('X-Frame-Options: SAMEORIGIN');
}

// --- Envío de archivos en streaming (sin cargar todo a memoria) ---
function stream_file($path, $download_name = null, $inline = false) {
    if (!is_file($path)) { http_response_code(404); exit('No encontrado'); }
    $size = filesize($path);
    $mime = mime_type($path);
    header('Content-Type: ' . $mime);
    $disp = $inline ? 'inline' : 'attachment';
    if ($download_name) {
        header("Content-Disposition: $disp; filename=\"" . basename($download_name) . '"');
    }
    header('Content-Length: ' . $size);
    header('X-Content-Type-Options: nosniff');
    while (ob_get_level()) ob_end_clean();
    $fp = fopen($path, 'rb');
    if ($fp) { while (!feof($fp)) { echo fread($fp, 1 << 20); flush(); } fclose($fp); }
    exit;
}
function mime_type($path) {
    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    return match ($ext) {
        'jpg', 'jpeg' => 'image/jpeg',
        'png'  => 'image/png',
        'zip'  => 'application/zip',
        default => 'application/octet-stream',
    };
}

// ===== Procesamiento de imágenes (versiones web) — CONSERVA EL COLOR (ICC) =====
// La descarga siempre es el ORIGINAL intacto; esto solo crea versiones para VER.
function make_variant($src, $dst, $maxEdge, $quality) {
    @mkdir(dirname($dst), 0775, true);
    if (extension_loaded('imagick')) {
        try {
            $im = new Imagick($src);
            if (method_exists($im, 'autoOrientImage')) $im->autoOrientImage();
            $w = $im->getImageWidth(); $hh = $im->getImageHeight();
            $scale = $maxEdge / max($w, $hh);
            if ($scale < 1) {
                $im->resizeImage((int)round($w*$scale), (int)round($hh*$scale), Imagick::FILTER_LANCZOS, 1);
            }
            // Conservar el perfil ICC para que el navegador muestre el color fiel:
            $icc = $im->getImageProfiles('icc', true);
            $im->stripImage();                       // quita metadata pesada (EXIF, etc.)
            if (!empty($icc['icc'])) $im->profileImage('icc', $icc['icc']); // re-inyecta el color
            $im->setImageFormat('jpeg');
            $im->setImageCompressionQuality($quality);
            $im->setImageProperty('jpeg:sampling-factor', '4:2:0');
            $im->writeImage($dst);
            $im->clear(); $im->destroy();
            return true;
        } catch (Throwable $e) { /* cae a GD */ }
    }
    return make_variant_gd($src, $dst, $maxEdge, $quality);
}
function make_variant_gd($src, $dst, $maxEdge, $quality) {
    $info = @getimagesize($src);
    if (!$info) return false;
    [$w, $h] = $info;
    $img = match ($info[2]) {
        IMAGETYPE_JPEG => @imagecreatefromjpeg($src),
        IMAGETYPE_PNG  => @imagecreatefrompng($src),
        default => null,
    };
    if (!$img) return false;
    // Orientación EXIF (GD no la aplica sola)
    if ($info[2] === IMAGETYPE_JPEG && function_exists('exif_read_data')) {
        $ex = @exif_read_data($src);
        $o = $ex['Orientation'] ?? 1;
        if ($o == 3) $img = imagerotate($img, 180, 0);
        elseif ($o == 6) { $img = imagerotate($img, -90, 0); [$w,$h]=[$h,$w]; }
        elseif ($o == 8) { $img = imagerotate($img, 90, 0);  [$w,$h]=[$h,$w]; }
    }
    $scale = $maxEdge / max($w, $h);
    $nw = $scale < 1 ? (int)round($w*$scale) : $w;
    $nh = $scale < 1 ? (int)round($h*$scale) : $h;
    $dstImg = imagecreatetruecolor($nw, $nh);
    imagecopyresampled($dstImg, $img, 0,0,0,0, $nw,$nh, $w,$h);
    imagejpeg($dstImg, $dst, $quality);
    imagedestroy($img); imagedestroy($dstImg);
    return true;
}
