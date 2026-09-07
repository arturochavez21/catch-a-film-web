<?php
// ===== Descarga completa: sirve el ZIP pre-generado (originales intactos) =====
require_once __DIR__ . '/lib.php';
boot_session();

$slug = $_GET['g'] ?? '';
$meta = load_gallery($slug);

if (!$meta || is_expired($meta)) { http_response_code(404); exit('No disponible'); }
if (!client_authed($slug))       { http_response_code(403); exit('No autorizado'); }

$zip = zip_path($slug);
if (!is_file($zip)) {
    http_response_code(409);
    exit('La descarga completa aún se está preparando. Intenta en unos minutos.');
}

$name = slugify($meta['title'] ?? $slug) . '.zip';
stream_file($zip, $name, false);
