<?php
// ===== Selector de fotos del portafolio (destacar en teaser / ocultar) =====
// Reutiliza el login de admin de las galerías (misma contraseña).
require_once __DIR__ . '/../galerias/lib.php';
boot_session();
security_headers();

$FOTOS_DIR = realpath(__DIR__ . '/../fotos');
$CONFIG    = __DIR__ . '/../fotos-config.json';

function cat_of($f) {
    $u = strtoupper($f);
    if (str_starts_with($u,'BARBERINI')||str_starts_with($u,'FIGHT')||str_starts_with($u,'TRUEMAKEUP')) return 'eventos';
    if (str_starts_with($u,'RECORD-GUACAMOLE')||str_starts_with($u,'RECORD_GUACAMOLE')) return 'corporativo';
    if (str_starts_with($u,'POST-ICELAND')||str_starts_with($u,'POSTS-NYC')||str_starts_with($u,'JAPOND')) return 'viajes';
    if (str_starts_with($u,'LOUIS')||str_starts_with($u,'TOBIKO')||str_starts_with($u,'DSC')||str_starts_with($u,'LM')||str_starts_with($u,'LA')) return 'gastronomia';
    return 'eventos';
}
function load_cfg($p){ if(!is_file($p))return ['featured'=>[],'hidden'=>[]]; $j=json_decode(file_get_contents($p),true); return is_array($j)?['featured'=>$j['featured']??[],'hidden'=>$j['hidden']??[]]:['featured'=>[],'hidden'=>[]]; }

$photos = [];
foreach (glob($FOTOS_DIR . '/*.jpg') ?: [] as $p) $photos[] = basename($p);
sort($photos);
$exists = array_flip($photos);

// login (reusa admin de galerías)
if (!admin_is_setup() || !admin_authed()) {
    echo "<!doctype html><meta charset=utf-8><meta name=viewport content='width=device-width,initial-scale=1'>";
    echo "<link rel=stylesheet href='/galerias/assets/gallery.css'><body class='admin'><div class='center'>";
    echo "<div class='logo'>CAT<b>CH</b></div><h1>Selector de fotos</h1>";
    echo "<p class='muted'>Entra con tu contraseña de administrador (la misma de las galerías) y regresa a esta página.</p>";
    echo "<a class='btn' href='/galerias/admin.php'>Ir a iniciar sesión</a></div></body>";
    exit;
}

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_ok()) {
    $feat = array_values(array_filter(array_map('trim', explode(',', $_POST['featured'] ?? '')), fn($f)=>$f!=='' && isset($exists[$f])));
    $hid  = array_values(array_filter(array_map('trim', explode(',', $_POST['hidden'] ?? '')),   fn($f)=>$f!=='' && isset($exists[$f])));
    file_put_contents($CONFIG, json_encode(['featured'=>$feat,'hidden'=>$hid], JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE), LOCK_EX);
    $msg = 'Guardado: ' . count($feat) . ' destacadas · ' . count($hid) . ' ocultas.';
}

$cfg = load_cfg($CONFIG);
$featSet = array_flip($cfg['featured']); $hidSet = array_flip($cfg['hidden']);
$tok = csrf_token();
$CATL = ['eventos'=>'Eventos','gastronomia'=>'Gastronomía','viajes'=>'Viajes','corporativo'=>'Corporativo'];
?><!doctype html><html lang=es><head><meta charset=utf-8>
<meta name=viewport content='width=device-width,initial-scale=1'><meta name=robots content='noindex,nofollow'>
<title>Selector de fotos · Catch</title>
<link rel=stylesheet href='/galerias/assets/gallery.css'>
<style>
.pkwrap{max-width:1300px;margin:0 auto;padding:1.5rem clamp(1rem,4vw,2rem) 6rem}
.pkbar{position:sticky;top:0;z-index:20;background:rgba(8,9,10,.92);backdrop-filter:blur(10px);display:flex;flex-wrap:wrap;gap:1rem;align-items:center;justify-content:space-between;padding:1rem 0;border-bottom:1px solid var(--line);margin-bottom:1rem}
.pkfilters{display:flex;flex-wrap:wrap;gap:.3rem 1.3rem}
.pkfilters button{background:none;border:none;color:var(--muted);font-weight:600;font-size:.76rem;letter-spacing:.1em;text-transform:uppercase;cursor:pointer;padding:.4rem 0}
.pkfilters button.on{color:var(--accent)}
.pkcount{color:var(--muted);font-size:.85rem}
.pkgrid{display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:8px}
.pk{position:relative;aspect-ratio:1;border-radius:6px;overflow:hidden;background:var(--bg2);border:2px solid transparent}
.pk img{width:100%;height:100%;object-fit:cover;display:block;transition:opacity .2s}
.pk.star-on{border-color:var(--accent)}
.pk.hide-on img{opacity:.28;filter:grayscale(1)}
.pk .badge{position:absolute;top:6px;left:6px;font-size:.55rem;letter-spacing:.06em;text-transform:uppercase;background:rgba(8,9,10,.7);color:#fff;padding:.12rem .4rem;border-radius:20px}
.pk .pkb{position:absolute;bottom:0;left:0;right:0;display:flex;gap:2px;padding:5px;background:linear-gradient(0deg,rgba(8,9,10,.85),transparent)}
.pk .pkb button{flex:1;border:none;border-radius:5px;padding:.35rem;font-size:.64rem;font-weight:700;letter-spacing:.04em;text-transform:uppercase;cursor:pointer;background:rgba(255,255,255,.14);color:#fff}
.pk .pkb .s.on{background:var(--accent);color:#fff}
.pk .pkb .hb.on{background:#c33;color:#fff}
.pk.star-on .badge.b-star{background:var(--accent)}
.savebar{position:fixed;bottom:1rem;left:50%;transform:translateX(-50%);z-index:50;display:flex;gap:1rem;align-items:center;background:rgba(11,8,9,.95);border:1px solid var(--line);border-radius:40px;padding:.5rem .5rem .5rem 1.3rem;box-shadow:0 12px 40px rgba(0,0,0,.5)}
.savebar .muted{font-size:.82rem}
</style></head><body class='admin'>
<div class="pkwrap">
  <div class="pkbar">
    <div class="logo">CAT<b>CH</b> <span class="muted">· Selector de fotos</span></div>
    <div class="pkcount"><b id="cFeat">0</b> destacadas · <b id="cHide">0</b> ocultas</div>
  </div>
  <?php if($msg): ?><div class="note ok"><?=h($msg)?></div><?php endif; ?>
  <p class="muted small">Marca <b>★ Destacar</b> las fotos que quieres en el <b>teaser</b> del home (máx. 12 se muestran). Usa <b>Ocultar</b> para que una foto no aparezca en el portafolio. Al terminar, <b>Guardar</b>.</p>
  <div class="pkfilters" id="pkFilters">
    <button class="on" data-cat="all">Todas</button>
    <?php foreach($CATL as $k=>$v): ?><button data-cat="<?=$k?>"><?=h($v)?></button><?php endforeach; ?>
    <button data-cat="__star">★ Destacadas</button>
    <button data-cat="__hide">Ocultas</button>
  </div>
  <div class="pkgrid" id="pkGrid">
    <?php foreach($photos as $f): $c=cat_of($f); $st=isset($featSet[$f]); $hd=isset($hidSet[$f]); ?>
      <div class="pk<?=$st?' star-on':''?><?=$hd?' hide-on':''?>" data-f="<?=h($f)?>" data-cat="<?=$c?>">
        <img loading="lazy" src="/fotos/<?=rawurlencode($f)?>" alt="">
        <span class="badge"><?=h($CATL[$c])?></span>
        <div class="pkb">
          <button type="button" class="s<?=$st?' on':''?>">★</button>
          <button type="button" class="hb<?=$hd?' on':''?>">Ocultar</button>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>
<div class="savebar">
  <span class="muted" id="saveHint">Sin cambios sin guardar</span>
  <form method="post" id="saveForm"><input type="hidden" name="csrf" value="<?=$tok?>">
    <input type="hidden" name="featured" id="fFeat"><input type="hidden" name="hidden" id="fHide">
    <button class="btn" type="submit">Guardar</button>
  </form>
</div>
<script>
(function(){
  var grid=document.getElementById('pkGrid');
  function counts(){ var s=grid.querySelectorAll('.pk.star-on').length, hd=grid.querySelectorAll('.pk.hide-on').length;
    document.getElementById('cFeat').textContent=s; document.getElementById('cHide').textContent=hd; }
  counts();
  grid.addEventListener('click',function(e){
    var b=e.target.closest('button'); if(!b)return; var pk=b.closest('.pk');
    if(b.classList.contains('s')){ pk.classList.toggle('star-on'); b.classList.toggle('on'); if(pk.classList.contains('star-on')&&pk.classList.contains('hide-on')){pk.classList.remove('hide-on');pk.querySelector('.hb').classList.remove('on');} }
    else if(b.classList.contains('hb')){ pk.classList.toggle('hide-on'); b.classList.toggle('on'); if(pk.classList.contains('hide-on')&&pk.classList.contains('star-on')){pk.classList.remove('star-on');pk.querySelector('.s').classList.remove('on');} }
    counts(); document.getElementById('saveHint').textContent='Cambios sin guardar';
  });
  // filtros
  document.getElementById('pkFilters').addEventListener('click',function(e){
    var b=e.target.closest('button'); if(!b)return; this.querySelectorAll('button').forEach(function(x){x.classList.remove('on')}); b.classList.add('on');
    var c=b.getAttribute('data-cat');
    grid.querySelectorAll('.pk').forEach(function(pk){
      var show = c==='all' || (c==='__star'&&pk.classList.contains('star-on')) || (c==='__hide'&&pk.classList.contains('hide-on')) || pk.getAttribute('data-cat')===c;
      pk.style.display=show?'':'none';
    });
  });
  // guardar
  document.getElementById('saveForm').addEventListener('submit',function(){
    var feat=[],hid=[];
    grid.querySelectorAll('.pk').forEach(function(pk){ var f=pk.getAttribute('data-f'); if(pk.classList.contains('star-on'))feat.push(f); if(pk.classList.contains('hide-on'))hid.push(f); });
    document.getElementById('fFeat').value=feat.join(','); document.getElementById('fHide').value=hid.join(',');
  });
})();
</script></body></html>
