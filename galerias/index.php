<?php
// Landing genérico: NO lista galerías (son privadas, por enlace).
require_once __DIR__ . '/lib.php';
security_headers();
header('Content-Type: text/html; charset=utf-8');
?><!doctype html><html lang=es><head><meta charset=utf8>
<meta name=viewport content='width=device-width,initial-scale=1'>
<meta name=robots content='noindex,nofollow'>
<title>Galerías privadas · <?=h(SITE_NAME)?></title>
<link rel=stylesheet href='/galerias/assets/gallery.css?v=<?=asset_ver('assets/gallery.css')?>'></head><body>
<div class='center'>
  <div class='logo'>CAT<b>CH</b><small>A FILM STUDIO</small></div>
  <div class='eyebrow'>Galerías privadas</div>
  <h1>Tus fotos, en privado</h1>
  <p class='muted'>Accede con el enlace y la contraseña que te compartimos.
     Si no lo tienes a la mano, escríbenos por WhatsApp.</p>
  <a class='btn' href='<?=h(SITE_URL)?>'>Ir al sitio</a>
</div></body></html>
