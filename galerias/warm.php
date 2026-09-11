<?php
// ===== Pre-genera las miniaturas de una galería, por lotes =====
// Se llama varias veces desde el panel (fetch) hasta terminar. Cada llamada
// genera unas cuantas miniaturas de forma SECUENCIAL (sin saturar el servidor)
// y devuelve el progreso. Al final persiste las dimensiones para el mosaico.
require_once __DIR__ . '/lib.php';
boot_session();
header('Content-Type: application/json; charset=utf-8');

function jexit($arr, $code = 200) { http_response_code($code); echo json_encode($arr); exit; }

if (!admin_authed()) jexit(['error' => 'No autorizado'], 403);
$tok = $_SERVER['HTTP_X_CSRF'] ?? ($_POST['csrf'] ?? ($_GET['csrf'] ?? ''));
if (empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $tok)) jexit(['error' => 'Sesión expirada'], 403);

$slug = $_GET['g'] ?? ($_POST['g'] ?? '');
if (!valid_slug($slug) || !load_gallery($slug)) jexit(['error' => 'Galería inválida'], 400);

$photos = gallery_photos($slug);
$total  = count($photos);

@set_time_limit(0);
$deadline  = microtime(true) + 12; // ~12s por llamada; el resto sigue en la próxima
$generated = 0;

foreach ($photos as $f) {
    $orig  = orig_dir($slug) . '/' . $f;
    $thumb = cache_dir($slug) . '/thumb/' . preg_replace('/\.png$/i', '.jpg', $f);
    if (is_file($thumb) && @filemtime($thumb) >= @filemtime($orig)) continue; // ya está
    if (microtime(true) > $deadline) break;                                    // sigue luego
    @make_variant($orig, $thumb, THUMB_MAX, THUMB_QUALITY);
    $generated++;
}

// Cuántas miniaturas están listas de verdad
$ready = 0;
foreach ($photos as $f) {
    if (is_file(cache_dir($slug) . '/thumb/' . preg_replace('/\.png$/i', '.jpg', $f))) $ready++;
}

// Persiste las dimensiones (desde las miniaturas ya generadas) para el mosaico
gallery_dims($slug, $photos);

jexit(['ok' => true, 'ready' => $ready, 'total' => $total, 'generated' => $generated, 'complete' => ($ready >= $total)]);
