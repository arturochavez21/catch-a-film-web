<?php
// ===== Registro de clics del catálogo de renta (lo llama index.php con sendBeacon) =====
require_once __DIR__ . '/lib.php';
header('Content-Type: application/json');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit('{}'); }

$in = json_decode((string)file_get_contents('php://input', false, null, 0, 4000), true);
$kind = is_array($in) ? ($in['k'] ?? '') : '';
if (!in_array($kind, ['uno', 'carrito', 'agregar'], true)) { http_response_code(400); exit('{}'); }

// Solo se registran equipos que existen en el catálogo (se guarda también el nombre,
// para que las métricas sigan legibles si luego se borra el producto)
$cat = renta_load();
$names = [];
foreach ($cat['productos'] as $p) $names[$p['id']] = $p['nombre'];
$ids = [];
foreach ((array)($in['ids'] ?? []) as $id) {
    if (is_string($id) && isset($names[$id]) && !in_array($id, $ids, true)) $ids[] = $id;
    if (count($ids) >= 40) break;
}
if (!$ids) { http_response_code(400); exit('{}'); }

if (!renta_rate_ok()) { http_response_code(429); exit('{}'); }

renta_log_event([
    't'   => time(),
    'k'   => $kind,
    'ids' => $ids,
    'n'   => array_map(fn($i) => $names[$i], $ids),
    'd'   => max(0, min(365, (int)($in['d'] ?? 0))),
    'tot' => max(0, min(10000000, (int)($in['tot'] ?? 0))),
]);
echo '{"ok":true}';
