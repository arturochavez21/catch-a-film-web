# Renta de equipo — catchafilmstudio.com/renta/

Catálogo público de equipo en renta con fechas, carrito y envío por WhatsApp, más un panel
para editar el catálogo y ver métricas de clics. PHP sin base de datos (JSON en el servidor).

## URLs

| URL | Qué es |
|---|---|
| `/renta/` | Catálogo público (`index.php`) |
| `/renta/admin.php` | Panel: catálogo, categorías y métricas. **Misma contraseña que `/galerias/admin.php`** |
| `/renta/click.php` | Lo llama la página (sendBeacon) para registrar clics. Solo POST |

## Archivos

| Archivo | Va en Git | Qué hace |
|---|---|---|
| `lib.php` | sí | Núcleo: carga/guarda catálogo, fotos (Imagick → GD), métricas, límite por IP. Reutiliza sesión/CSRF/login de `../galerias/lib.php` |
| `index.php` | sí | Página pública. El catálogo se pinta en el servidor; el JS agrega fechas, carrito (localStorage `catch_renta_v1`) y WhatsApp |
| `admin.php` | sí | Panel. Usa `/galerias/assets/gallery.css` + estilos propios |
| `click.php` | sí | Registra `uno` (WhatsApp de un equipo), `carrito` (WhatsApp del carrito) y `agregar` (agregó al carrito) |
| `catalogo-inicial.json` | sí | Catálogo con el que arranca. **Solo se usa mientras el panel nunca haya guardado** |
| `data/catalogo.json` | **no** | Catálogo editado desde el panel (manda sobre el inicial) |
| `data/clics.jsonl` | **no** | Un evento por línea: `{t, k, ids, n, d, tot}` |
| `data/limite.json` | **no** | Contador del límite por IP (40 eventos / 10 min) |
| `fotos/*.jpg|png` | **no** | Fotos subidas desde el panel (nombre único por subida → caché de un año) |

`data/` está bloqueada por `.htaccess`; `fotos/` es pública pero no ejecuta nada.

## Reglas del negocio (en la página)

- WhatsApp: `523325480568` (constante `RENTA_WA` en `lib.php`).
- Precio por día. Días = diferencia entre “Recoges” y “Devuelves” (mismo día o día siguiente = 1 día).
- La renta se liquida al entregar el equipo. Requisitos: copia de INE o pasaporte + comprobante de domicilio ≤ 3 meses.
- Descuento según equipo y días (no se calcula: se pregunta). Operador: se cotiza aparte (checkbox en el carrito).

## Fotos

Mejor **PNG sin fondo** (se muestra flotando, `object-fit: contain`). JPG también sirve (a sangre, `cover`).
Se redimensionan a 1000 px en el borde largo. Si el servidor rechaza fotos grandes, subir
`upload_max_filesize` / `post_max_size` en el PHP de Hostinger.

## Probar en local

```bash
php -S 127.0.0.1:8765 -t web-catch-a-film/deploy
# http://127.0.0.1:8765/renta/  ·  /renta/admin.php
```
Para el panel hace falta `galerias/data/admin.hash` (se crea en la 1ª visita a `/galerias/admin.php`).
Borrar al terminar lo que se haya creado en `renta/data/`, `renta/fotos/` y ese hash.
