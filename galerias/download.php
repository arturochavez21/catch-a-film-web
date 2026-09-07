<?php
// ===== Descarga del ARCHIVO ORIGINAL intacto (una foto) =====
require_once __DIR__ . '/lib.php';
boot_session();

$slug = $_GET['g'] ?? '';
$file = basename($_GET['f'] ?? '');
$meta = load_gallery($slug);

if (!$meta || is_expired($meta)) { http_response_code(404); exit('No disponible'); }
if (!client_authed($slug))       { http_response_code(403); exit('No autorizado'); }
if (!preg_match('/\.(jpe?g|png)$/i', $file)) { http_response_code(400); exit; }

$orig = orig_dir($slug) . '/' . $file;
if (!is_file($orig)) { http_response_code(404); exit('No encontrado'); }

// Se envía el original SIN tocar (color y calidad idénticos)
stream_file($orig, $file, false);
