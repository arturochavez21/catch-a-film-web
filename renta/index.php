<?php
// ===== Renta de equipo — catálogo público (catchafilmstudio.com/renta/) =====
// El catálogo se pinta en el servidor (se lee sin JS); el JS agrega fechas, carrito y WhatsApp.
require_once __DIR__ . '/lib.php';
security_headers();
header('Cache-Control: no-cache');

$C = renta_load();
$byCat = [];
foreach ($C['productos'] as $p) $byCat[$p['cat']][] = $p;
$cats = array_values(array_filter($C['categorias'], fn($k) => !empty($byCat[$k['id']])));

$P = []; $total = 0;
foreach ($cats as $k) foreach ($byCat[$k['id']] as $p) {
    $P[$p['id']] = ['n' => $p['nombre'], 'p' => $p['precio'], 'u' => $p['unidades'], 'd' => $p['disponible']];
    $total++;
}

const WA_SVG = '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M17.47 14.38c-.3-.15-1.76-.87-2.03-.97-.27-.1-.47-.15-.67.15-.2.3-.77.97-.94 1.16-.17.2-.35.22-.64.07-.3-.15-1.26-.46-2.39-1.47-.88-.79-1.48-1.76-1.65-2.06-.17-.3-.02-.46.13-.6.13-.14.3-.35.45-.52.15-.18.2-.3.3-.5.1-.2.05-.37-.03-.52-.07-.15-.67-1.61-.92-2.2-.24-.58-.49-.5-.67-.51h-.57c-.2 0-.52.07-.8.37-.27.3-1.04 1.02-1.04 2.48s1.07 2.88 1.21 3.07c.15.2 2.1 3.2 5.08 4.49.71.3 1.26.49 1.7.63.71.22 1.36.19 1.87.12.57-.09 1.76-.72 2-1.41.25-.7.25-1.29.18-1.41-.08-.13-.28-.2-.57-.35M12.05 21.79h-.01a9.87 9.87 0 0 1-5.03-1.38l-.36-.21-3.74.98 1-3.65-.24-.37a9.86 9.86 0 0 1-1.51-5.26c0-5.45 4.44-9.88 9.89-9.88 2.64 0 5.12 1.03 6.99 2.9a9.83 9.83 0 0 1 2.89 6.99c0 5.45-4.44 9.88-9.88 9.88m8.41-18.3A11.82 11.82 0 0 0 12.05 0C5.5 0 .16 5.34.16 11.89c0 2.1.55 4.14 1.59 5.95L.06 24l6.3-1.65a11.88 11.88 0 0 0 5.68 1.45h.01c6.55 0 11.89-5.34 11.89-11.89 0-3.18-1.24-6.16-3.48-8.41"/></svg>';

// "Sigma 24-70mm" no debe partirse en el guion
function nowrap_name($s) { return preg_replace('/\S*-\S*/u', '<span class="nw">$0</span>', h($s)); }
function wa_href($text) { return 'https://wa.me/' . RENTA_WA . '?text=' . rawurlencode($text); }
function wa_one($p) {
    return wa_href("¡Hola Catch! 👋 Vengo del catálogo de renta y me interesa:\n\n• {$p['nombre']} — "
        . renta_money($p['precio']) . "/día\n\n¿Está disponible?");
}
$waOperador = wa_href("¡Hola Catch! 👋 Vengo del catálogo de renta. Me interesa rentar equipo con operador, ¿me pueden cotizar?");

?><!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<title>Renta de equipo de filmación en Guadalajara · Catch a Film Studio</title>
<meta name="description" content="Renta cámaras Sony, lentes, audio DJI y estabilizadores en Guadalajara. Elige tus fechas, arma tu carrito y confirma por WhatsApp.">
<link rel="canonical" href="https://catchafilmstudio.com/renta/">
<meta property="og:title" content="Renta de equipo · Catch a Film Studio">
<meta property="og:description" content="Cámaras, lentes, audio y estabilizadores en renta en Guadalajara.">
<meta property="og:url" content="https://catchafilmstudio.com/renta/">
<meta name="theme-color" content="#0B0809">
<link rel="icon" href="/favicon.svg" type="image/svg+xml">
<link rel="icon" href="/favicon-32.png" sizes="32x32" type="image/png">
<link rel="apple-touch-icon" href="/apple-touch-icon.png">
<link rel="preload" href="/fonts/impact.woff2" as="font" type="font/woff2" crossorigin>
<script>document.documentElement.classList.add('js')</script>
<style>
@font-face{font-family:'CatchDisplay';src:url('/fonts/impact.woff2') format('woff2');font-display:swap}
@font-face{font-family:'Mont';src:url('/fonts/montserrat-400.woff2') format('woff2');font-weight:400 500;font-display:swap}
@font-face{font-family:'Mont';src:url('/fonts/montserrat-700.woff2') format('woff2');font-weight:600 700;font-display:swap}
:root{
  --display:'CatchDisplay','Impact','Arial Narrow Bold',sans-serif;
  --body:'Mont',system-ui,sans-serif;
  --bg:#0B0809; --bg2:#171015; --bg3:#20161B;
  --line:rgba(222,205,212,.12); --text:#ECE7EA; --muted:#A3949A;
  --accent:#BE4257; --accent2:#7A2137; --tint:190,66,87; --on-accent:#ffffff;
  --glow:radial-gradient(closest-side, rgba(190,66,87,.22), rgba(122,33,55,.05), transparent);
  --navh:68px; --r:16px; --ease:cubic-bezier(.16,1,.3,1);
}
*{box-sizing:border-box}
html{-webkit-text-size-adjust:100%;scroll-behavior:smooth;scroll-padding-top:136px;background:var(--bg)}
html.lock{overflow:hidden}
body{margin:0;color:var(--text);font-family:var(--body);font-weight:500;line-height:1.55;overflow-x:hidden;-webkit-font-smoothing:antialiased}
h1,h2,h3{font-family:var(--display);font-weight:400;text-transform:uppercase;line-height:.92;margin:0;text-wrap:balance}
a{color:inherit;text-decoration:none}
button{font:inherit;color:inherit}
img{max-width:100%}
.wrap{width:min(1200px,100% - 2 * clamp(1rem,4vw,2.4rem));margin-inline:auto}
.grad{background:linear-gradient(115deg,var(--accent),#E2677B 55%,var(--accent2));-webkit-background-clip:text;background-clip:text;color:transparent}
.eyebrow{display:inline-flex;align-items:center;gap:.6rem;font-weight:700;font-size:.7rem;letter-spacing:.28em;text-transform:uppercase;color:var(--muted)}
.eyebrow::before{content:"";width:26px;height:2px;background:linear-gradient(90deg,var(--accent),var(--accent2))}
.btn{display:inline-flex;align-items:center;justify-content:center;gap:.55rem;font-weight:700;font-size:.76rem;letter-spacing:.12em;text-transform:uppercase;padding:.85rem 1.45rem;border-radius:40px;cursor:pointer;border:1px solid transparent;transition:transform .25s,box-shadow .3s,color .2s,background .3s,border-color .2s}
.btn-accent{background:linear-gradient(115deg,var(--accent),var(--accent2));color:var(--on-accent);box-shadow:0 8px 30px -8px rgba(var(--tint),.6)}
.btn-accent:hover{transform:translateY(-2px);box-shadow:0 14px 34px -10px rgba(var(--tint),.8)}
.btn-ghost{border-color:var(--line);color:var(--text)}
.btn-ghost:hover{border-color:var(--accent);color:#fff}
.btn svg{width:18px;height:18px;flex:none}

/* nav */
nav{position:fixed;inset:0 0 auto;z-index:100;display:flex;align-items:center;justify-content:space-between;gap:1rem;padding:.9rem clamp(1rem,4vw,2.4rem);background:rgba(11,8,9,.78);-webkit-backdrop-filter:blur(12px);backdrop-filter:blur(12px);border-bottom:1px solid var(--line)}
.logo{font-family:var(--display);font-size:1.5rem;letter-spacing:.02em;line-height:.78;white-space:nowrap}
.logo small{display:block;font-family:var(--body);font-weight:700;font-size:.44rem;letter-spacing:.42em;color:var(--muted);margin-top:3px}
.logo b{font-weight:400;color:var(--text)}
.nav-r{display:flex;align-items:center;gap:clamp(.8rem,3vw,2rem)}
.nav-links{display:flex;gap:1.6rem}
.nav-links a{font-size:.74rem;letter-spacing:.14em;text-transform:uppercase;font-weight:600;color:var(--muted);transition:color .2s}
.nav-links a:hover{color:var(--text)}
@media(max-width:820px){.nav-links{display:none}}
.cartbtn{display:inline-flex;align-items:center;gap:.6rem;padding:.55rem .6rem .55rem 1rem;border-radius:40px;border:1px solid var(--line);background:var(--bg2);cursor:pointer;font-weight:700;font-size:.72rem;letter-spacing:.12em;text-transform:uppercase;transition:border-color .2s}
.cartbtn:hover{border-color:rgba(var(--tint),.6)}
.cartbtn svg{width:17px;height:17px}
.cnt{min-width:1.55rem;height:1.55rem;padding:0 .4rem;border-radius:20px;display:grid;place-items:center;font-size:.72rem;letter-spacing:0;background:var(--bg3);color:var(--muted);transition:background .3s,color .3s}
.cnt.on{background:linear-gradient(115deg,var(--accent),var(--accent2));color:#fff}
.bump{animation:bump .45s var(--ease)}
@keyframes bump{40%{transform:scale(1.14)}}
html:not(.js) .cartbtn{display:none}

/* hero */
.hero{position:relative;padding:calc(var(--navh) + clamp(3rem,8vw,6rem)) 0 clamp(3rem,6vw,4.5rem);overflow:hidden}
.hero-glow{position:absolute;width:min(95vw,980px);aspect-ratio:1;right:-22%;top:-36%;background:var(--glow);pointer-events:none}
.grid-bg{position:absolute;inset:0;opacity:.45;pointer-events:none;background:linear-gradient(var(--line) 1px,transparent 1px),linear-gradient(90deg,var(--line) 1px,transparent 1px);background-size:66px 66px;-webkit-mask-image:radial-gradient(70% 70% at 70% 30%,#000,transparent);mask-image:radial-gradient(70% 70% at 70% 30%,#000,transparent)}
.hero .wrap{position:relative;display:grid;grid-template-columns:minmax(0,1.3fr) minmax(0,.9fr);gap:clamp(2rem,5vw,4.5rem);align-items:end}
@media(max-width:920px){.hero .wrap{grid-template-columns:1fr}}
.hero h1{font-size:clamp(3.2rem,8.6vw,7.4rem);margin:1.3rem 0 1.4rem}
.lead{max-width:46ch;font-size:clamp(1rem,1.6vw,1.15rem);color:var(--muted);margin:0}
.facts{display:flex;flex-wrap:wrap;gap:.5rem;list-style:none;margin:1.9rem 0 0;padding:0}
.facts li{display:inline-flex;align-items:center;gap:.5rem;font-size:.7rem;letter-spacing:.1em;text-transform:uppercase;font-weight:700;border:1px solid var(--line);border-radius:40px;padding:.5rem .9rem;background:rgba(23,16,21,.7)}
.facts li::before{content:"";width:6px;height:6px;border-radius:50%;background:var(--accent)}

.when{position:relative;background:linear-gradient(180deg,rgba(32,22,27,.92),rgba(23,16,21,.92));border:1px solid var(--line);border-radius:22px;padding:clamp(1.25rem,3vw,1.8rem);box-shadow:0 40px 90px -40px rgba(0,0,0,.8)}
.when h2{font-size:clamp(1.9rem,3.4vw,2.5rem);margin:.8rem 0 1.2rem}
.dates{display:grid;grid-template-columns:1fr 1fr;gap:.6rem}
.field{display:flex;flex-direction:column;gap:.4rem;min-width:0;font-size:.64rem;letter-spacing:.16em;text-transform:uppercase;font-weight:700;color:var(--muted)}
.field input{-webkit-appearance:none;appearance:none;display:block;width:100%;min-width:0;min-height:50px;padding:.75rem .85rem;border-radius:12px;border:1px solid var(--line);background:var(--bg);color:var(--text);font:600 .95rem/1.2 var(--body);letter-spacing:0;text-transform:none;color-scheme:dark}
.field input:focus{outline:none;border-color:var(--accent);box-shadow:0 0 0 3px rgba(var(--tint),.25)}
@media(max-width:420px){.field input{font-size:.84rem;padding:.7rem .55rem}}
.nw{white-space:nowrap}
.days{display:flex;align-items:flex-end;justify-content:space-between;gap:1rem;margin-top:1.1rem;padding-top:1.1rem;border-top:1px dashed var(--line)}
.days-l{font-size:.84rem;color:var(--muted);line-height:1.35}
.days-v{font-family:var(--display);font-size:2.2rem;line-height:.9;white-space:nowrap}
.when .btn{width:100%;margin-top:1.2rem}
html:not(.js) .when{display:none}

/* barra de categorías */
.catbar{position:sticky;top:var(--navh);z-index:60;background:rgba(11,8,9,.9);-webkit-backdrop-filter:blur(12px);backdrop-filter:blur(12px);border-block:1px solid var(--line)}
.catbar .wrap{display:flex;gap:.45rem;overflow-x:auto;scrollbar-width:none;padding:.7rem 0}
.catbar .wrap::-webkit-scrollbar{display:none}
.chip{flex:none;display:inline-flex;align-items:center;gap:.5rem;padding:.55rem 1rem;border-radius:40px;border:1px solid var(--line);font-size:.72rem;font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:var(--muted);white-space:nowrap;transition:color .2s,border-color .2s,background .3s}
.chip small{font-size:.68rem;letter-spacing:0;opacity:.75}
.chip:hover{color:var(--text);border-color:rgba(var(--tint),.5)}
.chip.on{background:linear-gradient(115deg,var(--accent),var(--accent2));border-color:transparent;color:#fff}

/* catálogo */
.cat{padding-top:clamp(3rem,6vw,4.6rem)}
.cat-h{display:flex;flex-wrap:wrap;align-items:flex-end;justify-content:space-between;gap:.7rem 2rem;margin-bottom:1.6rem}
.cat-n{display:block;font-weight:700;font-size:.7rem;letter-spacing:.28em;color:var(--accent);margin-bottom:.7rem}
.cat h2{font-size:clamp(2.6rem,6.4vw,4.4rem)}
.cat-d{color:var(--muted);max-width:42ch;margin:0;font-size:.95rem}
.grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(250px,1fr));gap:clamp(.7rem,1.6vw,1.1rem)}
@media(max-width:620px){.grid{grid-template-columns:1fr 1fr}}

.item{position:relative;display:flex;flex-direction:column;min-width:0;background:var(--bg2);border:1px solid var(--line);border-radius:var(--r);overflow:hidden;transition:border-color .25s,transform .45s var(--ease),box-shadow .45s var(--ease)}
.item:hover,.js .item.rv.in:hover{border-color:rgba(var(--tint),.45);transform:translateY(-3px);box-shadow:0 24px 50px -28px rgba(var(--tint),.6)}
.item.in-cart{border-color:var(--accent)}
.item-img{position:relative;aspect-ratio:4/3;display:grid;place-items:center;overflow:hidden;background:radial-gradient(75% 85% at 50% 42%,#2A1C23,var(--bg2))}
.item-img img{width:100%;height:100%;display:block;transition:transform .6s var(--ease)}
.item-img img.cut{object-fit:contain;padding:9%;filter:drop-shadow(0 18px 22px rgba(0,0,0,.55))}
.item-img img.full{object-fit:cover}
.item:hover .item-img img{transform:scale(1.04)}
.ph{width:38%;max-width:118px;color:rgba(var(--tint),.62)}
.ph svg{display:block;width:100%;height:auto}
.badge{position:absolute;top:.65rem;left:.65rem;padding:.28rem .6rem;border-radius:30px;font-size:.6rem;font-weight:700;letter-spacing:.1em;text-transform:uppercase;background:linear-gradient(115deg,var(--accent),var(--accent2));color:#fff;opacity:0;transform:translateY(-6px);transition:opacity .3s,transform .3s var(--ease)}
.item.in-cart .badge{opacity:1;transform:none}
.item.na .badge{opacity:1;transform:none;background:rgba(11,8,9,.85);color:var(--muted);border:1px solid var(--line)}
.item.na .item-img>*:not(.badge){opacity:.35;filter:grayscale(1)}
.item.na:hover,.js .item.na.rv.in:hover{transform:none;box-shadow:none;border-color:var(--line)}
.item-b{display:flex;flex-direction:column;flex:1;gap:.3rem;padding:1rem 1.05rem 1.05rem}
.item h3{font-family:var(--body);font-weight:700;text-transform:none;font-size:1rem;line-height:1.25}
.det{margin:0;color:var(--muted);font-size:.8rem;line-height:1.45}
.item-f{margin-top:auto;padding-top:.9rem;display:flex;flex-direction:column;gap:.8rem}
.price{display:flex;flex-wrap:wrap;align-items:baseline;column-gap:.35rem}
.price b{font-family:var(--display);font-weight:400;font-size:1.8rem;line-height:1;letter-spacing:.01em}
.price span{font-size:.68rem;color:var(--muted);font-weight:700;letter-spacing:.08em;text-transform:uppercase}
.ptot{flex-basis:100%;font-size:.74rem;color:#E0899A;min-height:0;margin-top:.2rem}
.ptot:empty{display:none}
.acts{display:flex;gap:.45rem}
.add{flex:1;min-width:0;display:inline-flex;align-items:center;justify-content:center;gap:.35rem;min-height:42px;padding:.5rem .8rem;border-radius:40px;border:1px solid rgba(var(--tint),.6);background:transparent;cursor:pointer;font-weight:700;font-size:.7rem;letter-spacing:.1em;text-transform:uppercase;white-space:nowrap;transition:background .25s,border-color .25s}
.add:hover{background:rgba(var(--tint),.16)}
.item.in-cart .add{background:linear-gradient(115deg,var(--accent),var(--accent2));border-color:transparent;color:#fff}
.add:disabled{border-color:var(--line);color:var(--muted);cursor:not-allowed;background:none}
html:not(.js) .add{display:none}
.wa1{flex:none;width:42px;height:42px;border-radius:50%;display:grid;place-items:center;border:1px solid var(--line);color:var(--muted);transition:color .2s,border-color .2s,background .2s}
.wa1:hover{color:#fff;border-color:#25D366;background:rgba(37,211,102,.16)}
.wa1 svg{width:17px;height:17px}
@media(max-width:620px){
  .item-b{padding:.8rem .75rem .8rem}
  .item h3{font-size:.88rem}
  .det{font-size:.72rem}
  .price b{font-size:1.45rem}
  .add{font-size:.62rem;letter-spacing:.06em;padding:.45rem .5rem;min-height:40px}
  .wa1{width:40px;height:40px}
}

/* cómo rentar */
.how{padding:clamp(5rem,10vw,8rem) 0 clamp(3.5rem,7vw,5.5rem)}
.how-h{display:flex;flex-wrap:wrap;align-items:flex-end;justify-content:space-between;gap:1rem 2rem;margin-bottom:2.2rem}
.how h2{font-size:clamp(2.6rem,6.4vw,4.6rem);margin-top:1rem}
.steps{list-style:none;margin:0;padding:0;display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:1px;background:var(--line);border:1px solid var(--line);border-radius:20px;overflow:hidden}
@media(max-width:940px){.steps{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media(max-width:560px){.steps{grid-template-columns:1fr}}
.steps li{background:var(--bg);padding:1.6rem 1.35rem 1.7rem}
.steps .n{font-family:var(--display);font-size:2.8rem;line-height:1;color:transparent;-webkit-text-stroke:1px rgba(var(--tint),.9)}
.steps h3{font-family:var(--body);font-weight:700;text-transform:none;font-size:1.02rem;line-height:1.3;margin:1rem 0 .45rem}
.steps p{margin:0;color:var(--muted);font-size:.88rem}
.notes{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:1rem;margin-top:1rem}
@media(max-width:720px){.notes{grid-template-columns:1fr}}
.note{display:flex;flex-direction:column;align-items:flex-start;gap:.55rem;padding:1.5rem;border:1px solid var(--line);border-radius:20px;background:var(--bg2)}
.note h3{font-size:1.75rem}
.note p{margin:0;color:var(--muted);font-size:.92rem}
.note .btn{margin-top:.5rem}

footer{padding:2.6rem 0 2.4rem;border-top:1px solid var(--line)}
.foot{display:flex;flex-wrap:wrap;gap:1rem 2rem;justify-content:space-between;align-items:center;color:var(--muted);font-size:.82rem}
.foot a:hover{color:var(--text)}

/* carrito */
.scrim{position:fixed;inset:0;z-index:180;background:rgba(5,3,4,.62);-webkit-backdrop-filter:blur(3px);backdrop-filter:blur(3px);opacity:0;pointer-events:none;transition:opacity .35s}
.scrim.open{opacity:1;pointer-events:auto}
.drawer{position:fixed;top:0;right:0;bottom:0;z-index:190;width:min(440px,100%);display:flex;flex-direction:column;background:var(--bg);border-left:1px solid var(--line);transform:translateX(102%);visibility:hidden;transition:transform .5s var(--ease),visibility 0s .5s}
.drawer.open{transform:none;visibility:visible;transition:transform .5s var(--ease),visibility 0s}
.dr-h{display:flex;align-items:center;justify-content:space-between;gap:1rem;padding:1.05rem 1.3rem;border-bottom:1px solid var(--line)}
.dr-h h2{font-size:1.9rem}
.dr-h h2 small{font-family:var(--body);font-size:.8rem;color:var(--muted);letter-spacing:.04em;margin-left:.3rem}
.x{width:42px;height:42px;border-radius:50%;border:1px solid var(--line);background:none;cursor:pointer;color:var(--muted);font-size:1.4rem;line-height:1;transition:color .2s,border-color .2s}
.x:hover{color:#fff;border-color:var(--accent)}
.x:focus{outline:none}
.x:focus-visible,.add:focus-visible,.wa1:focus-visible,.chip:focus-visible,.btn:focus-visible,.cartbtn:focus-visible{outline:2px solid var(--accent);outline-offset:3px}
.dr-b{flex:1;overflow-y:auto;overscroll-behavior:contain;padding:1.2rem 1.3rem}
.dr-sec{margin:0 0 .6rem;font-size:.64rem;font-weight:700;letter-spacing:.18em;text-transform:uppercase;color:var(--muted)}
.dr-dates{margin-bottom:1.5rem}
.dr-dates .days{margin-top:.8rem;padding-top:.8rem}
.dr-dates .days-v{font-size:1.7rem}
.lines{list-style:none;margin:0;padding:0}
.line{display:grid;grid-template-columns:54px minmax(0,1fr) auto;gap:.8rem;align-items:center;padding:.75rem 0;border-bottom:1px solid var(--line)}
.th{width:54px;height:54px;border-radius:10px;display:grid;place-items:center;overflow:hidden;background:radial-gradient(75% 85% at 50% 42%,#2A1C23,var(--bg2))}
.th img{width:100%;height:100%;object-fit:contain;padding:5px}
.th img.full{object-fit:cover;padding:0}
.th svg{width:58%;color:rgba(var(--tint),.7)}
.nm{font-weight:700;font-size:.9rem;line-height:1.25}
.pr{font-size:.78rem;color:var(--muted)}
.rt{display:flex;flex-direction:column;align-items:flex-end;gap:.35rem}
.qty{display:inline-flex;align-items:center;border:1px solid var(--line);border-radius:30px}
.qty button{width:30px;height:30px;border:none;background:none;cursor:pointer;font-size:1rem}
.qty span{min-width:1.2rem;text-align:center;font-size:.85rem;font-weight:700}
.rm{border:none;background:none;padding:.2rem 0;cursor:pointer;color:var(--muted);font-size:.74rem;text-decoration:underline;text-underline-offset:3px}
.rm:hover{color:#fff}
.empty{text-align:center;color:var(--muted);padding:2.4rem 1rem 1rem}
.empty .ph{margin:0 auto 1rem;width:64px}
.empty .btn{margin-top:1.2rem}
.op{display:flex;gap:.75rem;align-items:flex-start;margin-top:1.1rem;padding:.95rem 1rem;border:1px solid var(--line);border-radius:12px;cursor:pointer;font-size:.9rem;font-weight:600}
.op:has(input:checked){border-color:rgba(var(--tint),.7);background:rgba(var(--tint),.08)}
.op input{flex:none;width:18px;height:18px;margin:.15rem 0 0;accent-color:var(--accent)}
.op small{display:block;font-weight:500;color:var(--muted);font-size:.78rem}
.dr-f{padding:1rem 1.3rem calc(1.1rem + env(safe-area-inset-bottom));border-top:1px solid var(--line);background:var(--bg2)}
.sum{display:grid;grid-template-columns:1fr auto;gap:.2rem .8rem;font-size:.86rem;color:var(--muted)}
.sum dd,.sum dt{margin:0}
.sum dd{text-align:right;color:var(--text);font-weight:600}
.sum .tl{align-self:end;color:var(--text);font-weight:700;padding-top:.5rem}
.sum .tv{font-family:var(--display);font-weight:400;font-size:2.2rem;line-height:1;padding-top:.5rem}
.dr-f .btn{width:100%;margin-top:.95rem;padding:1rem}
.fine{margin:.7rem 0 0;font-size:.74rem;line-height:1.45;color:var(--muted);text-align:center}

.fab{position:fixed;left:50%;bottom:calc(1rem + env(safe-area-inset-bottom));z-index:120;display:flex;align-items:center;gap:.8rem;padding:.5rem .5rem .5rem 1.2rem;border:none;border-radius:50px;cursor:pointer;white-space:nowrap;background:linear-gradient(115deg,var(--accent),var(--accent2));color:#fff;font-weight:700;font-size:.74rem;letter-spacing:.1em;text-transform:uppercase;box-shadow:0 18px 40px -12px rgba(var(--tint),.75),0 0 0 1px rgba(255,255,255,.08) inset;transform:translate(-50%,180%);transition:transform .5s var(--ease)}
.fab.show{transform:translate(-50%,0)}
.fab i{font-style:normal;padding:.5rem .85rem;border-radius:40px;background:rgba(0,0,0,.28);letter-spacing:.02em}
.toast{position:fixed;left:50%;bottom:calc(5rem + env(safe-area-inset-bottom));z-index:130;max-width:calc(100% - 2rem);padding:.6rem 1rem;border-radius:30px;background:var(--text);color:var(--bg);font-size:.8rem;font-weight:700;text-align:center;opacity:0;transform:translate(-50%,10px);pointer-events:none;transition:opacity .3s,transform .3s var(--ease)}
.toast.show{opacity:1;transform:translate(-50%,0)}

.js .rv{opacity:0;transform:translateY(26px);transition:opacity .9s var(--ease),transform .9s var(--ease)}
.js .rv.in{opacity:1;transform:none}
@media (prefers-reduced-motion:reduce){*,*::before,*::after{animation:none!important;transition-duration:.01ms!important;scroll-behavior:auto!important}.js .rv{opacity:1;transform:none}}
</style>
</head>
<body>

<nav id="nav">
  <a class="logo" href="/" aria-label="Catch a Film Studio — inicio">CAT<b>CH</b><small>A FILM STUDIO</small></a>
  <div class="nav-r">
    <div class="nav-links"><a href="#catalogo">Catálogo</a><a href="#como">Cómo rentar</a><a href="/">Estudio</a></div>
    <button class="cartbtn" type="button" data-open-cart aria-label="Abrir carrito">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 4h2.2l2.1 10.2a2 2 0 0 0 2 1.6h7.6a2 2 0 0 0 2-1.5L20.5 8H6.1"/><circle cx="10" cy="20" r="1.3"/><circle cx="17" cy="20" r="1.3"/></svg>
      Carrito <span class="cnt">0</span>
    </button>
  </div>
</nav>

<main>
<header class="hero">
  <div class="hero-glow"></div><div class="grid-bg"></div>
  <div class="wrap">
    <div>
      <span class="eyebrow">Renta de equipo · Guadalajara</span>
      <h1>Renta el equipo<br><span class="grad">para tu rodaje</span></h1>
      <p class="lead">Cámaras, lentes, audio y estabilizadores listos para tu producción. Elige tus fechas, arma tu carrito y confírmalo por WhatsApp.</p>
      <ul class="facts">
        <li><?= $total ?> equipos</li>
        <li>Recoges en oficina</li>
        <li>Pagas al recoger</li>
      </ul>
    </div>
    <div class="when">
      <span class="eyebrow">Paso 1</span>
      <h2>¿Cuándo lo necesitas?</h2>
      <div class="dates">
        <label class="field">Recoges<input class="in-rec" type="date" autocomplete="off"></label>
        <label class="field">Devuelves<input class="in-dev" type="date" autocomplete="off"></label>
      </div>
      <div class="days">
        <span class="days-l">Elige cuándo recoges<br>y cuándo devuelves.</span>
        <span class="days-v">—</span>
      </div>
      <a class="btn btn-accent" href="#catalogo">Ver catálogo ↓</a>
    </div>
  </div>
</header>

<div class="catbar" id="catalogo">
  <div class="wrap" id="chips">
    <?php foreach ($cats as $k): ?>
      <a class="chip" href="#cat-<?= h($k['id']) ?>"><?= h($k['nombre']) ?> <small><?= count($byCat[$k['id']]) ?></small></a>
    <?php endforeach; ?>
  </div>
</div>

<div class="wrap">
<?php foreach ($cats as $i => $k): ?>
  <section class="cat" id="cat-<?= h($k['id']) ?>">
    <div class="cat-h rv">
      <div>
        <span class="cat-n"><?= sprintf('%02d', $i + 1) ?> — <?= count($byCat[$k['id']]) ?> <?= count($byCat[$k['id']]) === 1 ? 'equipo' : 'equipos' ?></span>
        <h2><?= h($k['nombre']) ?></h2>
      </div>
      <?php if ($k['desc'] !== ''): ?><p class="cat-d"><?= h($k['desc']) ?></p><?php endif; ?>
    </div>
    <div class="grid">
      <?php foreach ($byCat[$k['id']] as $p): $foto = renta_foto_url($p['foto']); ?>
      <article class="item<?= $p['disponible'] ? '' : ' na' ?>" data-id="<?= h($p['id']) ?>">
        <div class="item-img">
          <?php if ($foto): ?>
            <img class="<?= str_ends_with($foto, '.png') ? 'cut' : 'full' ?>" src="<?= h($foto) ?>" alt="<?= h($p['nombre']) ?>" loading="lazy" decoding="async" width="1000" height="750">
          <?php else: ?>
            <div class="ph"><?= renta_icon($k['icono']) ?></div>
          <?php endif; ?>
          <span class="badge"><?= $p['disponible'] ? '✓ En carrito' : 'No disponible' ?></span>
        </div>
        <div class="item-b">
          <h3><?= nowrap_name($p['nombre']) ?></h3>
          <?php if ($p['detalle'] !== ''): ?><p class="det"><?= h($p['detalle']) ?></p><?php endif; ?>
          <div class="item-f">
            <div class="price"><b><?= renta_money($p['precio']) ?></b><span>MXN / día</span><em class="ptot"></em></div>
            <div class="acts">
              <?php if ($p['disponible']): ?>
                <button class="add" type="button" aria-pressed="false">+ Agregar</button>
              <?php else: ?>
                <button class="add" type="button" disabled>No disponible</button>
              <?php endif; ?>
              <a class="wa1" href="<?= h(wa_one($p)) ?>" target="_blank" rel="noopener" aria-label="<?= h(($p['disponible'] ? 'Rentar solo ' : 'Preguntar por ') . $p['nombre']) ?> por WhatsApp" title="<?= $p['disponible'] ? 'Rentar solo este por WhatsApp' : 'Preguntar cuándo se libera' ?>"><?= WA_SVG ?></a>
            </div>
          </div>
        </div>
      </article>
      <?php endforeach; ?>
    </div>
  </section>
<?php endforeach; ?>
</div>

<section class="how" id="como">
  <div class="wrap">
    <div class="how-h rv">
      <div>
        <span class="eyebrow">Cómo rentar</span>
        <h2>Cuatro pasos<br>y a grabar</h2>
      </div>
    </div>
    <ol class="steps rv">
      <li><span class="n">01</span><h3>Arma tu carrito</h3><p>Elige el equipo y tus fechas. Ves el total estimado al momento.</p></li>
      <li><span class="n">02</span><h3>Confírmalo por WhatsApp</h3><p>Te respondemos con la disponibilidad y, si aplica, tu descuento.</p></li>
      <li><span class="n">03</span><h3>Manda tus documentos</h3><p>Copia de INE o pasaporte y un comprobante de domicilio no mayor a 3 meses.</p></li>
      <li><span class="n">04</span><h3>Recoge y paga</h3><p>En nuestra oficina, en el horario que acordemos. La renta se liquida al entregarte el equipo.</p></li>
    </ol>
    <div class="notes">
      <div class="note rv">
        <span class="eyebrow">Descuentos</span>
        <h3>Entre más días, mejor precio</h3>
        <p>Aplicamos descuento según el equipo y los días que rentes. Pregúntanos al confirmar tu carrito.</p>
      </div>
      <div class="note rv">
        <span class="eyebrow">Operador</span>
        <h3>¿Necesitas quien lo opere?</h3>
        <p>Podemos mandar a alguien del equipo. El costo varía según el servicio.</p>
        <a class="btn btn-ghost" href="<?= h($waOperador) ?>" target="_blank" rel="noopener"><?= WA_SVG ?> Cotizar con operador</a>
      </div>
    </div>
  </div>
</section>
</main>

<footer>
  <div class="wrap foot">
    <a class="logo" href="/">CAT<b>CH</b><small>A FILM STUDIO</small></a>
    <span>Renta de equipo en Guadalajara · <a href="/">catchafilmstudio.com</a></span>
  </div>
</footer>

<div class="scrim" id="scrim" data-close-cart></div>
<aside class="drawer" id="drawer" role="dialog" aria-modal="true" aria-labelledby="drTitle" aria-hidden="true">
  <div class="dr-h">
    <h2 id="drTitle">Tu renta <small id="drCount"></small></h2>
    <button class="x" type="button" data-close-cart aria-label="Cerrar carrito">×</button>
  </div>
  <div class="dr-b">
    <div class="dr-dates">
      <p class="dr-sec">Fechas</p>
      <div class="dates">
        <label class="field">Recoges<input class="in-rec" type="date" autocomplete="off"></label>
        <label class="field">Devuelves<input class="in-dev" type="date" autocomplete="off"></label>
      </div>
      <div class="days">
        <span class="days-l">Elige cuándo recoges<br>y cuándo devuelves.</span>
        <span class="days-v">—</span>
      </div>
    </div>
    <p class="dr-sec" id="eqTitle">Equipo</p>
    <ul class="lines" id="lines"></ul>
    <div class="empty" id="empty">
      <div class="ph"><?= renta_icon('camara') ?></div>
      <p>Tu carrito está vacío.<br>Agrega equipo del catálogo.</p>
      <button class="btn btn-ghost" type="button" data-close-cart>Ver catálogo</button>
    </div>
    <label class="op" id="opBox"><input type="checkbox" id="op"><span>También necesito operador<small>El costo varía según el servicio; se cotiza aparte.</small></span></label>
  </div>
  <div class="dr-f" id="drF">
    <dl class="sum">
      <dt>Por día</dt><dd id="sDia">$0</dd>
      <dt>Días</dt><dd id="sDias">Por definir</dd>
      <dt class="tl" id="sLbl">Total por día</dt><dd class="tv" id="sTot">$0</dd>
    </dl>
    <a class="btn btn-accent" id="send" href="https://wa.me/<?= RENTA_WA ?>" target="_blank" rel="noopener"><?= WA_SVG ?> Enviar por WhatsApp</a>
    <p class="fine">Total estimado antes de descuento. La renta se liquida al recoger el equipo.</p>
  </div>
</aside>

<button class="fab" id="fab" type="button" data-open-cart>Ver carrito <i id="fabInfo">0</i></button>
<div class="toast" id="toast" role="status" aria-live="polite"></div>

<script>
(function(){
  var P = <?= json_encode((object)$P, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS) ?>;
  var WA = '<?= RENTA_WA ?>', KEY = 'catch_renta_v1';
  var $ = function(s){ return document.querySelector(s); };
  var $$ = function(s){ return Array.prototype.slice.call(document.querySelectorAll(s)); };
  var html = document.documentElement;

  // ---------- estado (se recuerda en este navegador) ----------
  var S = { items:{}, rec:'', dev:'', op:false };
  try {
    var sv = JSON.parse(localStorage.getItem(KEY) || 'null');
    if (sv && typeof sv === 'object') {
      S.rec = sv.rec || ''; S.dev = sv.dev || ''; S.op = !!sv.op;
      for (var k in (sv.items || {})) if (P[k] && P[k].d) S.items[k] = Math.max(1, Math.min(P[k].u, sv.items[k] | 0));
    }
  } catch (e) {}
  function save(){ try { localStorage.setItem(KEY, JSON.stringify(S)); } catch (e) {} }

  function money(n){ return '$' + Math.round(n).toLocaleString('es-MX'); }
  function iso(d){ return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0'); }
  function parse(v){ var m = /^(\d{4})-(\d{2})-(\d{2})$/.exec(v || ''); return m ? new Date(+m[1], m[2] - 1, +m[3]) : null; }
  function fdate(v){ var d = parse(v); return d ? d.toLocaleDateString('es-MX', { weekday:'short', day:'numeric', month:'short' }) : ''; }
  function plural(n){ return n + (n === 1 ? ' día' : ' días'); }
  function esc(s){ return String(s).replace(/[&<>"']/g, function(c){ return { '&':'&amp;', '<':'&lt;', '>':'&gt;', '"':'&quot;', "'":'&#39;' }[c]; }); }
  var today = iso(new Date());
  if (S.rec && S.rec < today) { S.rec = ''; S.dev = ''; }

  // Renta por día: recoges el 18 y devuelves el 19 = 1 día. Mismo día = 1 día.
  function days(){ var a = parse(S.rec), b = parse(S.dev); return (a && b) ? Math.max(1, Math.round((b - a) / 864e5)) : 0; }
  function perDay(){ var t = 0; for (var id in S.items) t += P[id].p * S.items[id]; return t; }
  function count(){ var c = 0; for (var id in S.items) c += S.items[id]; return c; }

  // ---------- fechas (hero y carrito comparten estado) ----------
  var recs = $$('.in-rec'), devs = $$('.in-dev');
  recs.forEach(function(i){ i.addEventListener('change', function(){
    S.rec = i.value;
    if (S.rec && (!S.dev || S.dev < S.rec)) { var d = parse(S.rec); d.setDate(d.getDate() + 1); S.dev = iso(d); }
    update();
  }); });
  devs.forEach(function(i){ i.addEventListener('change', function(){
    S.dev = i.value;
    if (S.dev && S.rec && S.dev < S.rec) S.dev = S.rec;
    update();
  }); });

  // ---------- WhatsApp ----------
  function waUrl(items, op){
    var n = days(), pd = 0, L = ['¡Hola Catch! 👋 Vengo del catálogo de renta y me interesa:', ''];
    Object.keys(items).forEach(function(id){
      var p = P[id], q = items[id]; pd += p.p * q;
      L.push('• ' + p.n + (q > 1 ? ' ×' + q : '') + ' — ' + money(p.p * q) + '/día');
    });
    L.push('');
    if (n) {
      L.push('📅 Recojo: ' + fdate(S.rec));
      L.push('📅 Devuelvo: ' + fdate(S.dev) + ' (' + plural(n) + ')');
      L.push('💰 Total estimado: ' + money(pd * n) + ' MXN');
    } else {
      L.push('📅 Fechas: por definir');
      L.push('💰 Total por día: ' + money(pd) + ' MXN');
    }
    if (op) L.push('🎬 También necesito operador.');
    L.push('', '¿Está disponible?');
    return 'https://wa.me/' + WA + '?text=' + encodeURIComponent(L.join('\n'));
  }
  function track(k, ids, tot){
    try {
      var body = JSON.stringify({ k:k, ids:ids, d:days(), tot:Math.round(tot) });
      if (navigator.sendBeacon) navigator.sendBeacon('/renta/click.php', new Blob([body], { type:'text/plain' }));
      else fetch('/renta/click.php', { method:'POST', body:body, keepalive:true });
    } catch (e) {}
  }

  // ---------- pintar ----------
  var drawer = $('#drawer'), scrim = $('#scrim'), fab = $('#fab'), toastEl = $('#toast'), toastT;
  function thumbOf(id){
    var card = document.querySelector('.item[data-id="' + (window.CSS && CSS.escape ? CSS.escape(id) : id) + '"]');
    var m = card && card.querySelector('.item-img img, .item-img .ph svg');
    return m ? m.outerHTML : '';
  }
  function update(){
    save();
    var n = days(), pd = perDay(), c = count(), ids = Object.keys(S.items);
    recs.forEach(function(i){ i.min = today; i.value = S.rec; });
    devs.forEach(function(i){ i.min = S.rec || today; i.value = S.dev; });
    $$('.days-v').forEach(function(el){ el.textContent = n ? plural(n) : '—'; });
    $$('.days-l').forEach(function(el){ el.innerHTML = n ? esc(fdate(S.rec)) + ' →<br>' + esc(fdate(S.dev)) : 'Elige cuándo recoges<br>y cuándo devuelves.'; });

    $$('.item').forEach(function(card){
      var id = card.getAttribute('data-id'), p = P[id], inC = !!S.items[id];
      card.classList.toggle('in-cart', inC);
      var b = card.querySelector('.add');
      if (b && !b.disabled) { b.textContent = inC ? '✓ En carrito' : '+ Agregar'; b.setAttribute('aria-pressed', inC ? 'true' : 'false'); }
      var t = card.querySelector('.ptot');
      if (t && p) t.textContent = n > 1 ? money(p.p * n) + ' por ' + plural(n) : '';
    });

    $$('.cnt').forEach(function(el){ el.textContent = c; el.classList.toggle('on', c > 0); });
    $('#drCount').textContent = c ? '(' + c + ')' : '';
    $('#fabInfo').textContent = c + ' · ' + money(n ? pd * n : pd) + (n ? '' : '/día');
    fab.classList.toggle('show', c > 0 && !drawer.classList.contains('open'));

    $('#lines').innerHTML = ids.map(function(id){
      var p = P[id], q = S.items[id];
      return '<li class="line"><div class="th">' + thumbOf(id) + '</div>' +
        '<div><div class="nm">' + esc(p.n) + '</div><div class="pr">' + money(p.p) + ' / día' + (q > 1 ? ' × ' + q : '') + '</div></div>' +
        '<div class="rt">' + (p.u > 1 ? '<div class="qty"><button type="button" data-q="-1" data-id="' + esc(id) + '" aria-label="Menos">−</button><span>' + q + '</span><button type="button" data-q="1" data-id="' + esc(id) + '" aria-label="Más">+</button></div>' : '') +
        '<button type="button" class="rm" data-rm="' + esc(id) + '">Quitar</button></div></li>';
    }).join('');
    $('#lines').hidden = !ids.length; $('#eqTitle').hidden = !ids.length;
    $('#empty').hidden = ids.length > 0; $('#opBox').hidden = !ids.length; $('#drF').hidden = !ids.length;
    $('#op').checked = S.op;
    $('#sDia').textContent = money(pd);
    $('#sDias').textContent = n ? plural(n) : 'Por definir';
    $('#sLbl').textContent = n ? 'Total estimado' : 'Total por día';
    $('#sTot').textContent = money(n ? pd * n : pd);
  }
  function toast(msg){
    toastEl.textContent = msg; toastEl.classList.add('show');
    clearTimeout(toastT); toastT = setTimeout(function(){ toastEl.classList.remove('show'); }, 1800);
  }
  function bump(){ $$('.cartbtn .cnt').forEach(function(el){ el.classList.remove('bump'); void el.offsetWidth; el.classList.add('bump'); }); }

  var lastFocus = null;
  function openCart(){
    lastFocus = document.activeElement;
    drawer.classList.add('open'); scrim.classList.add('open'); drawer.setAttribute('aria-hidden', 'false');
    html.classList.add('lock'); update();
    setTimeout(function(){ drawer.querySelector('.x').focus(); }, 60);
  }
  function closeCart(){
    drawer.classList.remove('open'); scrim.classList.remove('open'); drawer.setAttribute('aria-hidden', 'true');
    html.classList.remove('lock'); update();
    if (lastFocus && lastFocus.focus) lastFocus.focus();
  }

  // ---------- eventos ----------
  document.addEventListener('click', function(e){
    var t = e.target, el;
    if ((el = t.closest('.add'))) {
      var id = el.closest('.item').getAttribute('data-id');
      if (!P[id] || !P[id].d) return;
      if (S.items[id]) delete S.items[id];
      else { S.items[id] = 1; toast(P[id].n + ' agregado al carrito'); bump(); track('agregar', [id], P[id].p * (days() || 1)); }
      update(); return;
    }
    if ((el = t.closest('[data-q]'))) {
      var qid = el.getAttribute('data-id');
      S.items[qid] = Math.max(1, Math.min(P[qid].u, (S.items[qid] || 1) + (+el.getAttribute('data-q'))));
      update(); return;
    }
    if ((el = t.closest('[data-rm]'))) { delete S.items[el.getAttribute('data-rm')]; update(); return; }
    if (t.closest('[data-open-cart]')) { openCart(); return; }
    if (t.closest('[data-close-cart]')) { closeCart(); return; }
    if ((el = t.closest('.wa1'))) {
      var wid = el.closest('.item').getAttribute('data-id'), one = {}; one[wid] = 1;
      el.href = waUrl(one, false);                       // el enlace abre con las fechas elegidas
      track('uno', [wid], P[wid].p * (days() || 1)); return;
    }
    if ((el = t.closest('#send'))) {
      if (!count()) { e.preventDefault(); return; }
      el.href = waUrl(S.items, S.op);
      track('carrito', Object.keys(S.items), perDay() * (days() || 1));
    }
  });
  $('#op').addEventListener('change', function(){ S.op = this.checked; save(); });
  document.addEventListener('keydown', function(e){ if (e.key === 'Escape' && drawer.classList.contains('open')) closeCart(); });

  // ---------- medidas de la barra fija ----------
  var nav = $('#nav'), bar = $('.catbar');
  function measure(){
    html.style.setProperty('--navh', nav.offsetHeight + 'px');
    html.style.scrollPaddingTop = (nav.offsetHeight + bar.offsetHeight + 8) + 'px';
  }
  measure(); addEventListener('resize', measure);

  // ---------- categoría activa + apariciones ----------
  if ('IntersectionObserver' in window) {
    var chips = $$('.chip'), scroller = $('#chips');
    var spy = new IntersectionObserver(function(es){
      es.forEach(function(x){
        if (!x.isIntersecting) return;
        chips.forEach(function(c){ c.classList.toggle('on', c.getAttribute('href') === '#' + x.target.id); });
        var on = $('.chip.on');
        if (on) scroller.scrollTo({ left: on.offsetLeft - scroller.clientWidth / 2 + on.offsetWidth / 2, behavior:'smooth' });
      });
    }, { rootMargin:'-35% 0px -60% 0px' });
    $$('.cat').forEach(function(s){ spy.observe(s); });

    var rv = new IntersectionObserver(function(es){
      es.forEach(function(x){ if (x.isIntersecting) { x.target.classList.add('in'); rv.unobserve(x.target); } });
    }, { rootMargin:'0px 0px -8% 0px' });
    $$('.rv').forEach(function(el){ rv.observe(el); });
  } else {
    $$('.rv').forEach(function(el){ el.classList.add('in'); });
  }

  update();
})();
</script>
</body>
</html>
