<?php
// ===== Recibe UNA foto (arrastrar y soltar desde el panel) =====
require_once __DIR__ . '/lib.php';
boot_session();
header('Content-Type: application/json; charset=utf-8');

function jexit($arr, $code = 200) { http_response_code($code); echo json_encode($arr); exit; }

if (!admin_authed()) jexit(['error' => 'No autorizado'], 403);

// CSRF por header o campo
$tok = $_SERVER['HTTP_X_CSRF'] ?? ($_POST['csrf'] ?? '');
if (empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $tok)) jexit(['error' => 'Sesión expirada'], 403);

$slug = $_POST['slug'] ?? '';
if (!valid_slug($slug) || !load_gallery($slug)) jexit(['error' => 'Galería inválida'], 400);

if (empty($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    $codes = [
        UPLOAD_ERR_INI_SIZE => 'La foto excede el tamaño permitido por el servidor.',
        UPLOAD_ERR_FORM_SIZE => 'Foto muy grande.',
        UPLOAD_ERR_PARTIAL => 'La subida se interrumpió.',
        UPLOAD_ERR_NO_FILE => 'No llegó ningún archivo.',
    ];
    jexit(['error' => $codes[$_FILES['file']['error'] ?? -1] ?? 'Error al subir.'], 400);
}

$tmp = $_FILES['file']['tmp_name'];
$info = @getimagesize($tmp);
if (!$info || !in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG], true)) {
    jexit(['error' => 'Solo se aceptan JPG o PNG.'], 400);
}

// Nombre seguro + único
$orig = $_FILES['file']['name'];
$ext  = $info[2] === IMAGETYPE_PNG ? 'png' : 'jpg';
$base = pathinfo($orig, PATHINFO_FILENAME);
$base = preg_replace('/[^A-Za-z0-9._-]+/', '-', $base);
$base = trim($base, '.-') ?: 'foto';
$dir  = orig_dir($slug);
@mkdir($dir, 0775, true);
$name = "$base.$ext"; $n = 2;
while (is_file("$dir/$name")) { $name = "$base-$n.$ext"; $n++; }

if (!move_uploaded_file($tmp, "$dir/$name")) {
    // fallback (algunos entornos)
    if (!rename($tmp, "$dir/$name")) jexit(['error' => 'No se pudo guardar. Revisa permisos de la carpeta.'], 500);
}
@chmod("$dir/$name", 0644);

// La galería cambió → invalida el zip anterior
$m = load_gallery($slug);
$m['count'] = count(gallery_photos($slug));
if (is_file(zip_path($slug))) { @unlink(zip_path($slug)); unset($m['zipped']); }
save_gallery($slug, $m);

jexit(['ok' => true, 'name' => $name]);
