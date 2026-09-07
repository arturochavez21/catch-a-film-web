<?php
// ===== Portada pública de la galería (teaser detrás del login) =====
// Sirve UNA foto (tamaño web, no full-res) sin pedir contraseña, como Pixieset.
require_once __DIR__ . '/lib.php';

$slug = $_GET['g'] ?? '';
$meta = load_gallery($slug);
if (!$meta || is_expired($meta)) { http_response_code(404); exit; }

$cover = gallery_cover($slug, $meta);
if (!$cover) { http_response_code(404); exit; }

$orig  = orig_dir($slug) . '/' . $cover;
$cache = cache_dir($slug) . '/web/' . preg_replace('/\.(png)$/i', '.jpg', $cover);
if (!preg_match('/\.jpe?g$/i', $cache)) $cache .= '.jpg';
if (!is_file($cache) || filemtime($cache) < filemtime($orig)) {
    if (!make_variant($orig, $cache, WEB_MAX, WEB_QUALITY)) { http_response_code(500); exit; }
}

$etag = '"cov' . md5($cache . filemtime($cache)) . '"';
header('Cache-Control: public, max-age=86400');
header('ETag: ' . $etag);
if (($_SERVER['HTTP_IF_NONE_MATCH'] ?? '') === $etag) { http_response_code(304); exit; }
stream_file($cache, null, true);
