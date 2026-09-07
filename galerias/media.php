<?php
// ===== Sirve las versiones "web/thumb" (protegidas por contraseña) =====
// Genera la variante la primera vez y la cachea. NO afecta al original.
require_once __DIR__ . '/lib.php';
boot_session();

$slug = $_GET['g'] ?? '';
$size = ($_GET['s'] ?? 'thumb') === 'web' ? 'web' : 'thumb';
$file = basename($_GET['f'] ?? '');

$meta = load_gallery($slug);
if (!$meta) { http_response_code(404); exit; }
// El admin puede ver miniaturas aunque la galería esté expirada; el cliente no.
if (!admin_authed()) {
    if (is_expired($meta))      { http_response_code(404); exit; }
    if (!client_authed($slug))  { http_response_code(403); exit('No autorizado'); }
}
if (!preg_match('/\.(jpe?g|png)$/i', $file)) { http_response_code(400); exit; }

$orig = orig_dir($slug) . '/' . $file;
if (!is_file($orig)) { http_response_code(404); exit; }

$cache = cache_dir($slug) . '/' . $size . '/' . preg_replace('/\.(png)$/i', '.jpg', $file);
if (!preg_match('/\.jpe?g$/i', $cache)) $cache .= '.jpg';

// Genera si falta o si el original cambió
if (!is_file($cache) || filemtime($cache) < filemtime($orig)) {
    $max = $size === 'web' ? WEB_MAX : THUMB_MAX;
    $q   = $size === 'web' ? WEB_QUALITY : THUMB_QUALITY;
    if (!make_variant($orig, $cache, $max, $q)) { http_response_code(500); exit; }
}

// Caché del navegador (privada) + ETag
$etag = '"' . md5($cache . filemtime($cache) . filesize($cache)) . '"';
header('Cache-Control: private, max-age=2592000');
header('ETag: ' . $etag);
if (($_SERVER['HTTP_IF_NONE_MATCH'] ?? '') === $etag) { http_response_code(304); exit; }

stream_file($cache, null, true);
