<?php
// ===== Configuración de las galerías de cliente (Catch a Film Studio) =====
// Este archivo NO guarda contraseñas de clientes ni del admin (esas viven en /data,
// fuera de Git). Aquí solo van ajustes generales.

const SITE_NAME   = 'Catch a Film Studio';
const SITE_URL    = 'https://catchafilmstudio.com';

// Tamaños de las versiones "web" (solo para VER en la galería; la descarga es el original intacto)
const WEB_MAX     = 2048;  // borde largo de la imagen grande (lightbox)
const THUMB_MAX   = 640;   // borde largo de la miniatura del mosaico
const WEB_QUALITY = 88;    // calidad JPEG de la versión web
const THUMB_QUALITY = 80;

// Caducidad por defecto de una galería (meses)
const DEFAULT_EXPIRY_MONTHS = 3;

// Carpeta de datos (galerías, caché, hash de admin). Debe quedar protegida por .htaccess.
define('DATA_DIR', __DIR__ . '/data');
