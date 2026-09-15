<?php
// ===== Renta de equipo — núcleo (catálogo, fotos, métricas) =====
// Reutiliza la sesión, el CSRF y el login de admin de las galerías (misma contraseña).
require_once __DIR__ . '/../galerias/lib.php';

const RENTA_WA       = '523325480568';   // WhatsApp de Catch en formato internacional
const RENTA_FOTO_MAX = 1000;             // borde largo de la foto de producto
const RENTA_ICONOS   = ['camara' => 'Cámara', 'lente' => 'Lente', 'audio' => 'Audio',
                        'estabilizador' => 'Estabilizador', 'luz' => 'Iluminación', 'accesorio' => 'Accesorio'];

date_default_timezone_set('America/Mexico_City'); // métricas por día en hora de Guadalajara

// Catálogo editado + métricas: fuera de Git y bloqueado por .htaccess
define('RENTA_DATA',  __DIR__ . '/data');
// Fotos subidas desde el panel: fuera de Git, servidas públicamente
define('RENTA_FOTOS', __DIR__ . '/fotos');

// ---------- Catálogo ----------
// Si el panel nunca ha guardado, se usa el catálogo inicial que viaja en Git.
function renta_load() {
    $f = RENTA_DATA . '/catalogo.json';
    if (!is_file($f)) $f = __DIR__ . '/catalogo-inicial.json';
    $c = json_decode((string)@file_get_contents($f), true);
    if (!is_array($c)) $c = [];
    $cats = [];
    foreach ($c['categorias'] ?? [] as $k) {
        if (!is_array($k) || empty($k['id'])) continue;
        $cats[] = ['id' => (string)$k['id'], 'nombre' => (string)($k['nombre'] ?? $k['id']),
                   'desc' => (string)($k['desc'] ?? ''),
                   'icono' => isset(RENTA_ICONOS[$k['icono'] ?? '']) ? $k['icono'] : 'accesorio'];
    }
    $prods = [];
    foreach ($c['productos'] ?? [] as $p) {
        if (!is_array($p) || empty($p['id'])) continue;
        $prods[] = ['id' => (string)$p['id'], 'nombre' => (string)($p['nombre'] ?? ''),
                    'cat' => (string)($p['cat'] ?? ''), 'detalle' => (string)($p['detalle'] ?? ''),
                    'precio' => max(0, (int)($p['precio'] ?? 0)), 'unidades' => max(1, (int)($p['unidades'] ?? 1)),
                    'disponible' => (bool)($p['disponible'] ?? true), 'foto' => (string)($p['foto'] ?? '')];
    }
    return ['categorias' => $cats, 'productos' => $prods, 'actualizado' => (int)($c['actualizado'] ?? 0)];
}

function renta_save($c) {
    @mkdir(RENTA_DATA, 0775, true);
    $c['actualizado'] = time();
    $file = RENTA_DATA . '/catalogo.json';
    $tmp  = $file . '.' . bin2hex(random_bytes(4)) . '.tmp';
    file_put_contents($tmp, json_encode($c, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX);
    rename($tmp, $file); // escritura atómica: nunca queda un catálogo a medias
}

function renta_find(&$list, $id) {
    foreach ($list as $i => $x) if ($x['id'] === $id) return $i;
    return -1;
}

// Quita acentos a mano: iconv//TRANSLIT cambia según el servidor ("iluminaci-on" en macOS)
function renta_slug($text, $fallback) {
    $t = strtr(mb_strtolower((string)$text, 'UTF-8'), ['á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ü'=>'u','ñ'=>'n','à'=>'a','è'=>'e','ì'=>'i','ò'=>'o','ù'=>'u']);
    $t = trim(preg_replace('/[^a-z0-9]+/', '-', $t), '-');
    return substr($t, 0, 60) ?: $fallback;
}

function renta_unique_id($list, $text, $fallback) {
    $base = renta_slug($text, $fallback);
    $id = $base; $n = 2;
    while (renta_find($list, $id) >= 0) { $id = $base . '-' . $n; $n++; }
    return $id;
}

function renta_money($n) { return '$' . number_format((int)$n, 0, '.', ','); }

// Ícono de línea por categoría: se usa mientras un equipo no tiene foto
function renta_icon($name) {
    $paths = [
        'camara' => '<rect x="7" y="20" width="50" height="32" rx="6"/><path d="M21 20l4-7h14l4 7"/><circle cx="32" cy="36" r="10"/><circle cx="32" cy="36" r="4.5"/><circle cx="48" cy="27" r="1.4"/>',
        'lente' => '<circle cx="32" cy="32" r="23"/><circle cx="32" cy="32" r="16"/><circle cx="32" cy="32" r="8"/><path d="M26 26a8.5 8.5 0 0 1 7-2.6"/>',
        'audio' => '<rect x="24" y="7" width="16" height="30" rx="8"/><path d="M15 29a17 17 0 0 0 34 0"/><path d="M32 46v11M23 57h18"/><path d="M24 17h6M24 24h6"/>',
        'estabilizador' => '<rect x="28" y="40" width="8" height="18" rx="3"/><path d="M32 40v-8h15V13"/><rect x="11" y="9" width="28" height="15" rx="3"/><circle cx="21" cy="16.5" r="4"/><path d="M47 13h-8"/>',
        'luz' => '<rect x="12" y="8" width="40" height="28" rx="3"/><path d="M22 8v28M32 8v28M42 8v28"/><path d="M32 36v22M20 58l12-8 12 8"/>',
        'accesorio' => '<rect x="23" y="7" width="18" height="9" rx="2"/><path d="M32 16L14 58M32 16l18 42M32 16v34"/><path d="M41 11h8"/>',
    ];
    return '<svg viewBox="0 0 64 64" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
        . ($paths[$name] ?? $paths['accesorio']) . '</svg>';
}

function renta_foto_url($foto) {
    return renta_valid_foto($foto) ? '/renta/fotos/' . $foto : '';
}
function renta_valid_foto($foto) {
    return is_string($foto) && preg_match('/^[a-z0-9][a-z0-9-]{0,80}\.(jpg|png)$/', $foto)
        && is_file(RENTA_FOTOS . '/' . $foto);
}
function renta_delete_foto($foto) {
    if (renta_valid_foto($foto)) @unlink(RENTA_FOTOS . '/' . $foto);
}

// ---------- Fotos de producto ----------
// Redimensiona a RENTA_FOTO_MAX. PNG/WebP conservan la transparencia (fotos sin fondo);
// JPEG se guarda como JPEG progresivo. Devuelve el nombre del archivo o null.
function renta_save_upload($tmp, $id) {
    $info = @getimagesize($tmp);
    if (!$info || !in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP], true)) return null;
    @mkdir(RENTA_FOTOS, 0775, true);
    $base  = RENTA_FOTOS . '/' . substr($id, 0, 70) . '-' . bin2hex(random_bytes(3));
    $alpha = $info[2] !== IMAGETYPE_JPEG;

    if (extension_loaded('imagick')) {
        try {
            $im = new Imagick($tmp);
            if (method_exists($im, 'autoOrientImage')) $im->autoOrientImage();
            $w = $im->getImageWidth(); $hh = $im->getImageHeight();
            $s = RENTA_FOTO_MAX / max($w, $hh);
            if ($s < 1) $im->resizeImage((int)round($w * $s), (int)round($hh * $s), Imagick::FILTER_LANCZOS, 1);
            $icc = $im->getImageProfiles('icc', true);
            $im->stripImage();
            if (!empty($icc['icc'])) $im->profileImage('icc', $icc['icc']);
            if ($alpha) { $im->setImageFormat('png'); $dst = $base . '.png'; }
            else {
                $im->setImageFormat('jpeg'); $im->setImageCompressionQuality(84);
                $im->setInterlaceScheme(Imagick::INTERLACE_PLANE); $dst = $base . '.jpg';
            }
            $im->writeImage($dst); $im->clear(); $im->destroy();
            return basename($dst);
        } catch (Throwable $e) { /* cae a GD */ }
    }

    $src = match ($info[2]) {
        IMAGETYPE_JPEG => @imagecreatefromjpeg($tmp),
        IMAGETYPE_PNG  => @imagecreatefrompng($tmp),
        IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($tmp) : false,
        default => false,
    };
    if (!$src) return null;
    if ($info[2] === IMAGETYPE_JPEG && function_exists('exif_read_data')) {
        $o = (@exif_read_data($tmp))['Orientation'] ?? 1;
        if ($o == 3) $src = imagerotate($src, 180, 0);
        elseif ($o == 6) $src = imagerotate($src, -90, 0);
        elseif ($o == 8) $src = imagerotate($src, 90, 0);
    }
    $w = imagesx($src); $hh = imagesy($src);
    $s = min(1, RENTA_FOTO_MAX / max($w, $hh));
    $nw = max(1, (int)round($w * $s)); $nh = max(1, (int)round($hh * $s));
    $dstImg = imagecreatetruecolor($nw, $nh);
    if ($alpha) {
        imagealphablending($dstImg, false); imagesavealpha($dstImg, true);
        imagefill($dstImg, 0, 0, imagecolorallocatealpha($dstImg, 0, 0, 0, 127));
    }
    imagecopyresampled($dstImg, $src, 0, 0, 0, 0, $nw, $nh, $w, $hh);
    if ($alpha) { $dst = $base . '.png'; imagepng($dstImg, $dst, 7); }
    else { imageinterlace($dstImg, true); $dst = $base . '.jpg'; imagejpeg($dstImg, $dst, 84); }
    imagedestroy($src); imagedestroy($dstImg);
    return basename($dst);
}

// ---------- Métricas ----------
// Un evento por línea (JSON): {"t":ts,"k":"uno|carrito|agregar","ids":[...],"n":[nombres],"d":días,"tot":total}
function renta_clicks_file() { return RENTA_DATA . '/clics.jsonl'; }

function renta_log_event($ev) {
    @mkdir(RENTA_DATA, 0775, true);
    $f = renta_clicks_file();
    if (is_file($f) && filesize($f) > 30 * 1024 * 1024) return; // tope de seguridad
    file_put_contents($f, json_encode($ev, JSON_UNESCAPED_UNICODE) . "\n", FILE_APPEND | LOCK_EX);
}

function renta_read_events() {
    $f = renta_clicks_file();
    if (!is_file($f)) return [];
    $out = [];
    $fh = fopen($f, 'rb');
    while ($fh && ($line = fgets($fh)) !== false) {
        $e = json_decode($line, true);
        if (is_array($e) && isset($e['t'], $e['k'])) $out[] = $e;
    }
    if ($fh) fclose($fh);
    return $out;
}

// Límite simple por IP (evita que un bot infle las métricas): 40 eventos cada 10 min
function renta_rate_ok() {
    @mkdir(RENTA_DATA, 0775, true);
    $file = RENTA_DATA . '/limite.json';
    $key  = substr(hash('sha256', ($_SERVER['REMOTE_ADDR'] ?? '') . '|catch-renta'), 0, 16);
    $now  = time();
    $fh = fopen($file, 'c+');
    if (!$fh) return true;
    flock($fh, LOCK_EX);
    $map = json_decode(stream_get_contents($fh), true) ?: [];
    foreach ($map as $k => $v) if ($now - ($v[0] ?? 0) > 600) unset($map[$k]);
    $cur = $map[$key] ?? [$now, 0];
    $ok = $cur[1] < 40;
    if ($ok) $map[$key] = [$cur[0], $cur[1] + 1];
    ftruncate($fh, 0); rewind($fh); fwrite($fh, json_encode($map));
    flock($fh, LOCK_UN); fclose($fh);
    return $ok;
}
