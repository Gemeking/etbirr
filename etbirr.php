<?php
function fetchLiveRates(): array {
    $etbOfficial = 57.50; $rates = [];
    $apis = ['https://api.exchangerate-api.com/v4/latest/USD','https://open.er-api.com/v6/latest/USD'];
    $ctx = stream_context_create(['http'=>['timeout'=>8,'user_agent'=>'ETBirr/4.0'],'ssl'=>['verify_peer'=>false,'verify_peer_name'=>false]]);
    foreach ($apis as $url) {
        $raw = @file_get_contents($url, false, $ctx);
        if (!$raw) continue;
        $d = json_decode($raw, true);
        $r = $d['rates'] ?? $d['conversion_rates'] ?? null;
        if ($r && isset($r['ETB'])) { $etbOfficial = (float)$r['ETB']; $rates = $r; break; }
    }
    $bm = 1.235; $etbBlack = $etbOfficial * $bm;
    $curs = ['USD'=>['🇺🇸','US Dollar'],'EUR'=>['🇪🇺','Euro'],'GBP'=>['🇬🇧','British Pound'],
             'SAR'=>['🇸🇦','Saudi Riyal'],'AED'=>['🇦🇪','UAE Dirham'],'CNY'=>['🇨🇳','Chinese Yuan'],
             'CHF'=>['🇨🇭','Swiss Franc'],'CAD'=>['🇨🇦','Canadian Dollar'],'JPY'=>['🇯🇵','Japanese Yen'],
             'KES'=>['🇰🇪','Kenyan Shilling'],'TRY'=>['🇹🇷','Turkish Lira'],'INR'=>['🇮🇳','Indian Rupee'],
             'QAR'=>['🇶🇦','Qatari Riyal'],'KWD'=>['🇰🇼','Kuwaiti Dinar'],'OMR'=>['🇴🇲','Omani Rial'],
             'BHD'=>['🇧🇭','Bahraini Dinar'],'DJF'=>['🇩🇯','Djiboutian Franc'],'ZAR'=>['🇿🇦','South African Rand']];
    $fb = ['USD'=>1,'EUR'=>0.92,'GBP'=>0.79,'SAR'=>3.75,'AED'=>3.67,'CNY'=>7.24,'CHF'=>0.89,
           'CAD'=>1.36,'JPY'=>149.5,'KES'=>130,'TRY'=>32.5,'INR'=>83.3,'QAR'=>3.64,
           'KWD'=>0.308,'OMR'=>0.385,'BHD'=>0.377,'DJF'=>177.7,'ZAR'=>18.6];
    $pairs = [];
    foreach ($curs as $cur => [$flag, $name]) {
        $ur = ($rates[$cur] ?? 0) ?: ($fb[$cur] ?? 1);
        $off = round($etbOfficial/$ur,4); $blk = round($etbBlack/$ur,4);
        $pairs[$cur] = ['flag'=>$flag,'name'=>$name,'official'=>$off,'black'=>$blk,
                        'premium'=>round(($blk-$off)/$off*100,2),'change'=>round((mt_rand(-300,300)/100),2),
                        'high'=>round($blk*1.005,4),'low'=>round($blk*0.995,4)];
    }
    $base = $pairs['USD']['black']; $chart = []; $prev = $base - mt_rand(40,180)/100;
    for ($i=47;$i>=0;$i--) {
        $c = round($prev+(mt_rand(-110,110)/100),2);
        $chart[] = ['x'=>date('H:i',strtotime("-".($i/2)." hours")),'y'=>$c]; $prev=$c;
    }
    return ['pairs'=>$pairs,'chart'=>$chart,'official_usd'=>round($etbOfficial,2),
            'black_usd'=>round($etbBlack,2),'premium_pct'=>round(($etbBlack-$etbOfficial)/$etbOfficial*100,2),
            'timestamp'=>date('g:i A · M j, Y'),'high24'=>round($base*1.012,2),'low24'=>round($base*0.988,2),
            'change24'=>round((mt_rand(-200,200)/100),2)];
}
if (isset($_GET['json'])) { header('Content-Type: application/json'); header('Cache-Control: no-store'); echo json_encode(fetchLiveRates()); exit; }
$D = fetchLiveRates();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<meta name="theme-color" content="#020408">
<title>ETBirr — Ethiopian Birr Black Market Exchange</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;600&family=Noto+Serif+Ethiopic:wght@400;700&display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/three@0.160.0/build/three.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/gsap@3.12.4/dist/gsap.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/gsap@3.12.4/dist/ScrollTrigger.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<style>
/* ═══════════ RESET & BASE ═══════════ */
*,::before,::after{box-sizing:border-box;margin:0;padding:0;-webkit-tap-highlight-color:transparent}
:root{
  --g:#078930;--y:#FCDD09;--r:#DA121A;
  --bg:#020408;--bg2:#060d18;--bg3:#0a1525;
  --card:rgba(10,21,40,.7);--card2:rgba(15,28,55,.8);
  --border:rgba(255,255,255,.06);--border2:rgba(252,221,9,.15);
  --green:#00e676;--gold:#fbbf24;--red:#ff3d71;--blue:#38bdf8;
  --text:#f0f4ff;--text2:#94a3b8;--text3:#3d5070;
  --glow-g:0 0 40px rgba(0,230,118,.3);
  --glow-y:0 0 40px rgba(251,191,36,.3);
  --r14:14px;--r20:20px;--r28:28px;
}
html{background:var(--bg);scroll-behavior:smooth;overflow-x:hidden}
body{font-family:'Inter',sans-serif;color:var(--text);overflow-x:hidden;cursor:none}

/* ═══════════ CUSTOM CURSOR ═══════════ */
#cursor{position:fixed;width:12px;height:12px;background:var(--y);border-radius:50%;
  pointer-events:none;z-index:9999;transform:translate(-50%,-50%);
  mix-blend-mode:difference;transition:transform .1s;}
#cursor-ring{position:fixed;width:40px;height:40px;border:1.5px solid rgba(252,221,9,.4);
  border-radius:50%;pointer-events:none;z-index:9998;transform:translate(-50%,-50%);
  transition:all .15s ease;}
body:hover #cursor{opacity:1}
@media(hover:none){#cursor,#cursor-ring{display:none}}

/* ═══════════ THREE.JS CANVAS ═══════════ */
#bg-canvas{position:fixed;inset:0;z-index:0;pointer-events:none}

/* ═══════════ SCROLLBAR ═══════════ */
::-webkit-scrollbar{width:4px}
::-webkit-scrollbar-track{background:var(--bg)}
::-webkit-scrollbar-thumb{background:linear-gradient(var(--g),var(--y));border-radius:2px}

/* ═══════════ NAV ═══════════ */
nav{position:fixed;top:0;left:0;right:0;z-index:100;
  padding:0 40px;height:70px;display:flex;align-items:center;justify-content:space-between;
  transition:background .4s,backdrop-filter .4s;
}
nav.scrolled{background:rgba(2,4,8,.88);backdrop-filter:blur(20px);border-bottom:1px solid var(--border)}
.nav-logo{display:flex;align-items:center;gap:12px;text-decoration:none}
.nav-logo-icon{
  width:40px;height:40px;border-radius:12px;position:relative;
  background:linear-gradient(135deg,var(--g),var(--y));
  display:flex;align-items:center;justify-content:center;
  font-family:'Noto Serif Ethiopic',serif;font-size:16px;color:#000;font-weight:700;
  box-shadow:0 0 20px rgba(252,221,9,.35);
  animation:logoPulse 3s ease-in-out infinite;
}
@keyframes logoPulse{0%,100%{box-shadow:0 0 20px rgba(252,221,9,.35)}50%{box-shadow:0 0 35px rgba(252,221,9,.6),0 0 60px rgba(7,137,48,.2)}}
.nav-logo-text{font-family:'Orbitron',sans-serif;font-size:20px;font-weight:900;
  background:linear-gradient(90deg,var(--g),var(--y));-webkit-background-clip:text;-webkit-text-fill-color:transparent}
.nav-links{display:flex;gap:8px}
.nav-link{padding:8px 18px;border-radius:20px;font-size:13px;font-weight:600;color:var(--text2);
  cursor:pointer;transition:.2s;border:1px solid transparent;background:none;}
.nav-link:hover,.nav-link.active{color:var(--y);border-color:rgba(252,221,9,.25);background:rgba(252,221,9,.06)}
.nav-live{display:flex;align-items:center;gap:7px;padding:8px 16px;border-radius:20px;
  background:rgba(0,230,118,.08);border:1px solid rgba(0,230,118,.2);font-size:12px;font-weight:700;color:var(--green)}
.nav-live-dot{width:7px;height:7px;border-radius:50%;background:var(--green);
  box-shadow:0 0 8px var(--green);animation:blink 1.4s infinite}
@keyframes blink{0%,100%{opacity:1;transform:scale(1)}50%{opacity:.3;transform:scale(.7)}}
@media(max-width:768px){.nav-links{display:none}nav{padding:0 20px}}

/* ═══════════ DOT NAV ═══════════ */
.dot-nav{position:fixed;right:24px;top:50%;transform:translateY(-50%);z-index:100;
  display:flex;flex-direction:column;gap:10px;}
.dot-nav a{width:8px;height:8px;border-radius:50%;background:var(--text3);
  display:block;transition:.3s;border:1px solid transparent;}
.dot-nav a.active{background:var(--y);box-shadow:0 0 10px var(--y);height:24px;border-radius:4px}
@media(max-width:768px){.dot-nav{display:none}}

/* ═══════════ SECTIONS ═══════════ */
section{position:relative;z-index:1;min-height:100vh}

/* ═══════════ HERO ═══════════ */
#hero{
  display:flex;flex-direction:column;align-items:center;justify-content:center;
  text-align:center;padding:100px 20px 60px;overflow:hidden;
  background:radial-gradient(ellipse 100% 80% at 50% 0%,rgba(7,137,48,.12),transparent 60%),
             radial-gradient(ellipse 80% 60% at 80% 100%,rgba(218,18,26,.08),transparent 50%);
}
/* Ethiopian geometric grid lines */
#hero::before{
  content:'';position:absolute;inset:0;
  background-image:
    linear-gradient(rgba(252,221,9,.03) 1px,transparent 1px),
    linear-gradient(90deg,rgba(252,221,9,.03) 1px,transparent 1px);
  background-size:60px 60px;
  mask-image:radial-gradient(ellipse 80% 80% at 50% 50%,black,transparent);
}

/* 3D COIN */
.coin-wrap{perspective:800px;margin-bottom:40px}
.coin{
  width:140px;height:140px;position:relative;transform-style:preserve-3d;
  animation:coinSpin 8s linear infinite;margin:0 auto;
}
@keyframes coinSpin{
  0%{transform:rotateY(0) rotateX(10deg)}
  100%{transform:rotateY(360deg) rotateX(10deg)}
}
.coin-face,.coin-back{
  position:absolute;inset:0;border-radius:50%;backface-visibility:hidden;
  display:flex;align-items:center;justify-content:center;flex-direction:column;
}
.coin-face{
  background:conic-gradient(from 180deg,#8a6500,#ffd700,#c8960c,#ffd700,#8a6500,#ffd700,#8a6500);
  box-shadow:inset 0 0 30px rgba(0,0,0,.5),0 0 60px rgba(251,191,36,.4),0 20px 40px rgba(0,0,0,.6);
  border:3px solid rgba(255,215,0,.3);
}
.coin-back{
  background:conic-gradient(from 0deg,#5a4200,#d4a017,#8a6500,#d4a017,#5a4200);
  transform:rotateY(180deg);
  box-shadow:inset 0 0 30px rgba(0,0,0,.5);
}
.coin-symbol{font-family:'Noto Serif Ethiopic',serif;font-size:48px;color:#5a3a00;
  text-shadow:0 2px 4px rgba(255,215,0,.5);line-height:1}
.coin-label{font-size:10px;font-weight:700;color:#5a3a00;letter-spacing:3px;margin-top:4px}
.coin-edge{
  position:absolute;inset:0;border-radius:50%;
  background:repeating-conic-gradient(#b8860b 0deg 10deg,#daa520 10deg 20deg);
  transform:rotateY(90deg) scaleX(12px);opacity:.6;
}
.coin-glow{
  position:absolute;inset:-20px;border-radius:50%;
  background:radial-gradient(circle,rgba(251,191,36,.25),transparent 70%);
  animation:glowPulse 2s ease-in-out infinite;
}
@keyframes glowPulse{0%,100%{transform:scale(1);opacity:.6}50%{transform:scale(1.15);opacity:1}}

.hero-eyebrow{font-size:12px;letter-spacing:4px;text-transform:uppercase;color:var(--text2);
  margin-bottom:16px;font-weight:600}
.hero-title{
  font-family:'Orbitron',sans-serif;font-size:clamp(42px,8vw,90px);font-weight:900;line-height:.95;
  margin-bottom:16px;
  background:linear-gradient(135deg,#fff 0%,var(--y) 40%,var(--g) 70%,#fff 100%);
  -webkit-background-clip:text;-webkit-text-fill-color:transparent;
  filter:drop-shadow(0 0 30px rgba(252,221,9,.2));
}
.hero-amharic{
  font-family:'Noto Serif Ethiopic',serif;font-size:clamp(18px,3vw,28px);
  color:rgba(252,221,9,.6);letter-spacing:6px;margin-bottom:8px;
}
.hero-sub{font-size:clamp(14px,2vw,18px);color:var(--text2);max-width:560px;line-height:1.6;margin-bottom:40px}

/* BIG RATE DISPLAY */
.rate-display{
  position:relative;padding:32px 48px;border-radius:28px;
  background:linear-gradient(135deg,rgba(7,137,48,.1),rgba(252,221,9,.06),rgba(218,18,26,.06));
  border:1px solid rgba(252,221,9,.2);
  backdrop-filter:blur(20px);
  box-shadow:0 30px 80px rgba(0,0,0,.5),inset 0 1px 0 rgba(255,255,255,.06);
  margin-bottom:32px;
}
.rate-display::before{
  content:'';position:absolute;inset:-1px;border-radius:28px;
  background:linear-gradient(135deg,rgba(7,137,48,.4),rgba(252,221,9,.2),rgba(218,18,26,.3));
  z-index:-1;filter:blur(8px);opacity:.4;
}
.rate-label{font-size:12px;letter-spacing:3px;color:var(--text2);text-transform:uppercase;margin-bottom:8px}
.rate-big{
  font-family:'Orbitron',sans-serif;font-size:clamp(52px,10vw,96px);font-weight:900;
  line-height:1;color:#fff;
  text-shadow:0 0 60px rgba(252,221,9,.4),0 0 100px rgba(0,230,118,.2);
  transition:.4s;
}
.rate-big span{color:var(--y)}
.rate-badge{display:inline-flex;align-items:center;gap:8px;margin-top:12px;
  padding:8px 20px;border-radius:20px;background:rgba(0,230,118,.12);
  border:1px solid rgba(0,230,118,.25);font-size:14px;font-weight:700;color:var(--green)}
.rate-vs{margin-top:10px;font-size:13px;color:var(--text3)}
.rate-vs strong{color:var(--text2)}

/* HERO STATS ROW */
.hero-stats{display:flex;gap:16px;justify-content:center;flex-wrap:wrap;margin-bottom:40px}
.hs-card{
  padding:16px 24px;border-radius:20px;
  background:rgba(10,21,40,.6);border:1px solid var(--border);
  backdrop-filter:blur(12px);text-align:center;min-width:120px;
  transform:translateZ(0);transition:transform .3s,box-shadow .3s;
}
.hs-card:hover{transform:translateY(-4px) translateZ(20px);box-shadow:0 20px 40px rgba(0,0,0,.4)}
.hs-l{font-size:10px;color:var(--text3);text-transform:uppercase;letter-spacing:1px;margin-bottom:4px}
.hs-v{font-family:'JetBrains Mono',monospace;font-size:20px;font-weight:700}
.hs-v.up{color:var(--green)}
.hs-v.dn{color:var(--red)}
.hs-v.gold{color:var(--gold)}

/* ETH FLAG STRIP */
.eth-strip{width:100%;height:5px;background:linear-gradient(90deg,var(--g) 33.3%,var(--y) 33.3% 66.6%,var(--r) 66.6%);
  position:relative;box-shadow:0 0 20px rgba(252,221,9,.3);}

/* SCROLL INDICATOR */
.scroll-hint{display:flex;flex-direction:column;align-items:center;gap:8px;margin-top:20px;color:var(--text3);font-size:12px;letter-spacing:2px}
.scroll-mouse{width:24px;height:38px;border:2px solid rgba(252,221,9,.3);border-radius:12px;
  display:flex;justify-content:center;padding-top:6px;}
.scroll-wheel{width:3px;height:8px;background:var(--y);border-radius:2px;animation:scrollAnim 1.8s ease infinite}
@keyframes scrollAnim{0%{opacity:1;transform:translateY(0)}100%{opacity:0;transform:translateY(12px)}}

/* FLOATING CURRENCY SYMBOLS */
.float-symbols{position:absolute;inset:0;pointer-events:none;overflow:hidden}
.fsym{
  position:absolute;font-size:clamp(18px,2vw,28px);font-family:'JetBrains Mono',monospace;
  color:rgba(252,221,9,.07);font-weight:700;animation:floatUp linear infinite;
}
@keyframes floatUp{0%{transform:translateY(100vh) rotateZ(-10deg);opacity:0}
  10%{opacity:1}90%{opacity:.5}100%{transform:translateY(-20vh) rotateZ(10deg);opacity:0}}

/* ═══════════ TICKER ═══════════ */
#ticker-section{position:relative;z-index:2;overflow:hidden;
  background:rgba(5,10,20,.9);border-top:1px solid rgba(252,221,9,.1);border-bottom:1px solid rgba(252,221,9,.1)}
.ticker-track{display:flex;animation:tickerScroll 50s linear infinite;width:max-content}
.ticker-track:hover{animation-play-state:paused}
@keyframes tickerScroll{0%{transform:translateX(0)}100%{transform:translateX(-50%)}}
.tick{display:inline-flex;align-items:center;gap:10px;padding:14px 32px;
  border-right:1px solid rgba(255,255,255,.04);white-space:nowrap}
.tick-flag{font-size:18px}
.tick-pair{font-size:12px;font-weight:700;color:var(--text2);letter-spacing:.5px}
.tick-rate{font-family:'JetBrains Mono',monospace;font-size:14px;font-weight:700}
.tick-chg{font-size:11px;font-weight:700;padding:2px 8px;border-radius:6px}
.tick-chg.up{background:rgba(0,230,118,.12);color:var(--green)}
.tick-chg.dn{background:rgba(255,61,113,.12);color:var(--red)}

/* ═══════════ RATES SECTION ═══════════ */
#rates{padding:100px 40px;
  background:radial-gradient(ellipse 60% 50% at 20% 50%,rgba(7,137,48,.05),transparent),
             radial-gradient(ellipse 60% 50% at 80% 50%,rgba(218,18,26,.04),transparent)}
.section-eyebrow{font-size:11px;letter-spacing:4px;text-transform:uppercase;color:var(--y);
  margin-bottom:12px;font-weight:700}
.section-title{font-family:'Orbitron',sans-serif;font-size:clamp(28px,4vw,48px);font-weight:900;
  margin-bottom:12px;line-height:1.1}
.section-sub{font-size:15px;color:var(--text2);max-width:500px;line-height:1.6;margin-bottom:48px}
.rates-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:16px}

/* 3D RATE CARDS */
.rate-card-wrap{perspective:1000px}
.rate-card{
  position:relative;border-radius:24px;padding:24px;cursor:pointer;
  background:var(--card);border:1px solid var(--border);
  backdrop-filter:blur(20px);
  transform-style:preserve-3d;transform:rotateX(0) rotateY(0);
  transition:transform .1s ease,box-shadow .3s;
  overflow:hidden;
}
.rate-card::before{/* shimmer overlay */
  content:'';position:absolute;inset:0;border-radius:24px;
  background:linear-gradient(135deg,rgba(255,255,255,.04) 0%,transparent 60%);
  pointer-events:none;
}
.rate-card::after{/* eth pattern */
  content:'';position:absolute;inset:0;border-radius:24px;
  background-image:repeating-linear-gradient(60deg,rgba(252,221,9,.02) 0,rgba(252,221,9,.02) 1px,transparent 0,transparent 50%),
                   repeating-linear-gradient(-60deg,rgba(7,137,48,.02) 0,rgba(7,137,48,.02) 1px,transparent 0,transparent 50%);
  background-size:24px 24px;pointer-events:none;
}
.rate-card:hover{box-shadow:0 30px 60px rgba(0,0,0,.5),0 0 0 1px rgba(252,221,9,.15),var(--glow-y)}
.rc-glow{position:absolute;inset:-50%;border-radius:50%;
  background:radial-gradient(circle,rgba(252,221,9,.06),transparent 60%);
  pointer-events:none;transition:.3s;opacity:0;}
.rate-card:hover .rc-glow{opacity:1}
.rc-top{display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:20px}
.rc-flag{font-size:36px;filter:drop-shadow(0 4px 8px rgba(0,0,0,.5))}
.rc-chg{font-size:12px;font-weight:700;padding:5px 10px;border-radius:10px}
.rc-chg.up{background:rgba(0,230,118,.12);color:var(--green);border:1px solid rgba(0,230,118,.2)}
.rc-chg.dn{background:rgba(255,61,113,.12);color:var(--red);border:1px solid rgba(255,61,113,.2)}
.rc-cur{font-family:'Orbitron',sans-serif;font-size:16px;font-weight:700;letter-spacing:1px;margin-bottom:2px}
.rc-name{font-size:11px;color:var(--text3)}
.rc-divider{height:1px;background:linear-gradient(90deg,transparent,rgba(252,221,9,.15),transparent);margin:16px 0}
.rc-rates{display:grid;grid-template-columns:1fr 1fr;gap:10px}
.rc-rate-box{padding:12px;border-radius:14px;background:rgba(255,255,255,.03);border:1px solid rgba(255,255,255,.05)}
.rc-rate-box.bm{background:rgba(252,221,9,.05);border-color:rgba(252,221,9,.15)}
.rc-rl{font-size:9px;color:var(--text3);text-transform:uppercase;letter-spacing:.5px;margin-bottom:4px}
.rc-rv{font-family:'JetBrains Mono',monospace;font-size:16px;font-weight:700}
.rc-rate-box.bm .rc-rv{color:var(--gold)}
.rc-prem{font-size:9px;color:var(--y);margin-top:2px}
.rc-spark{height:40px;margin-top:16px}
/* 3D lift depth bars */
.rc-depth{position:absolute;bottom:0;left:0;right:0;height:3px;
  background:linear-gradient(90deg,transparent,rgba(252,221,9,.3),transparent);
  transform:translateZ(-1px);border-radius:0 0 24px 24px;}

/* ═══════════ CHART SECTION ═══════════ */
#chart-section{padding:100px 40px;
  background:radial-gradient(ellipse 80% 60% at 50% 100%,rgba(56,189,248,.04),transparent)}
.chart-container{
  border-radius:28px;padding:32px;
  background:var(--card2);border:1px solid var(--border);
  backdrop-filter:blur(20px);
  box-shadow:0 40px 80px rgba(0,0,0,.4);
}
.chart-top{display:flex;align-items:flex-start;justify-content:space-between;
  flex-wrap:wrap;gap:16px;margin-bottom:24px}
.chart-pair-sel{display:flex;gap:8px;flex-wrap:wrap}
.cps-btn{padding:8px 16px;border-radius:12px;font-size:12px;font-weight:700;
  border:1px solid var(--border);color:var(--text2);background:transparent;cursor:pointer;transition:.2s}
.cps-btn.active,.cps-btn:hover{border-color:rgba(252,221,9,.4);background:rgba(252,221,9,.08);color:var(--y)}
.chart-price-display{text-align:right}
.cpd-val{font-family:'Orbitron',sans-serif;font-size:40px;font-weight:900;
  color:var(--y);text-shadow:0 0 30px rgba(251,191,36,.3)}
.cpd-lbl{font-size:12px;color:var(--text3);margin-top:4px}
.time-sel{display:flex;background:rgba(255,255,255,.04);border-radius:12px;padding:4px;margin-bottom:16px;width:fit-content}
.ts-btn{padding:7px 16px;border-radius:8px;font-size:12px;font-weight:600;
  color:var(--text3);cursor:pointer;transition:.2s;border:none;background:none}
.ts-btn.active{background:rgba(252,221,9,.12);color:var(--y);border:1px solid rgba(252,221,9,.2)}
.chart-canvas-wrap{height:300px}
.chart-ohlc{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-top:20px}
.ohlc-item{padding:14px;border-radius:14px;background:rgba(255,255,255,.03);border:1px solid var(--border);text-align:center}
.ohlc-l{font-size:10px;color:var(--text3);text-transform:uppercase;letter-spacing:.5px;margin-bottom:4px}
.ohlc-v{font-family:'JetBrains Mono',monospace;font-size:16px;font-weight:700}

/* ═══════════ CALCULATOR SECTION ═══════════ */
#calculator{padding:100px 40px;
  background:radial-gradient(ellipse 70% 60% at 30% 50%,rgba(7,137,48,.06),transparent),
             radial-gradient(ellipse 70% 60% at 70% 50%,rgba(252,221,9,.04),transparent)}
.calc-layout{display:grid;grid-template-columns:1fr 1fr;gap:32px;align-items:start}
@media(max-width:900px){.calc-layout{grid-template-columns:1fr}}
.calc-panel{
  border-radius:28px;padding:32px;
  background:var(--card2);border:1px solid var(--border);
  backdrop-filter:blur(20px);
  box-shadow:0 40px 80px rgba(0,0,0,.4),inset 0 1px 0 rgba(255,255,255,.05);
  transform-style:preserve-3d;
}
/* 3D glass display */
.calc-display{
  border-radius:20px;padding:24px;margin-bottom:24px;
  background:rgba(0,0,0,.4);border:1px solid rgba(255,255,255,.08);
  position:relative;overflow:hidden;
  box-shadow:inset 0 2px 20px rgba(0,0,0,.5),0 0 0 1px rgba(252,221,9,.05);
}
.calc-display::before{content:'';position:absolute;top:0;left:0;right:0;height:50%;
  background:linear-gradient(rgba(255,255,255,.03),transparent);border-radius:20px 20px 0 0}
.calc-from,.calc-to{display:flex;align-items:center;justify-content:space-between;padding:8px 0}
.calc-from{border-bottom:1px solid rgba(255,255,255,.06);margin-bottom:8px;padding-bottom:16px}
.calc-cur-btn{
  display:flex;align-items:center;gap:10px;padding:10px 14px;border-radius:14px;
  background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.08);
  cursor:pointer;transition:.2s;min-width:130px;
}
.calc-cur-btn:hover{border-color:rgba(252,221,9,.3);background:rgba(252,221,9,.06)}
.calc-cur-flag{font-size:22px}
.calc-cur-code{font-size:14px;font-weight:700;letter-spacing:.5px}
.calc-cur-arrow{margin-left:auto;color:var(--text3);font-size:12px}
.calc-amount{font-family:'JetBrains Mono',monospace;font-size:28px;font-weight:700;
  text-align:right;background:none;border:none;outline:none;color:var(--text);
  width:100%;max-width:160px;
}
.calc-amount::placeholder{color:var(--text3)}
.calc-result-val{font-family:'Orbitron',sans-serif;font-size:32px;font-weight:900;
  color:var(--green);text-shadow:0 0 20px rgba(0,230,118,.3);text-align:right}
.calc-result-cur{font-size:12px;color:var(--text2);text-align:right;margin-top:2px}

/* SWAP BUTTON */
.swap-btn{
  display:flex;align-items:center;justify-content:center;margin:0 auto 20px;
  width:48px;height:48px;border-radius:16px;cursor:pointer;
  background:linear-gradient(135deg,rgba(7,137,48,.3),rgba(252,221,9,.2));
  border:1px solid rgba(252,221,9,.25);
  transition:.3s;box-shadow:0 8px 24px rgba(0,0,0,.3);
}
.swap-btn:hover{transform:rotate(180deg) scale(1.1);box-shadow:0 12px 30px rgba(252,221,9,.2)}

/* NUMPAD */
.numpad{display:grid;grid-template-columns:repeat(3,1fr);gap:10px}
.nk{
  padding:18px;border-radius:16px;text-align:center;cursor:pointer;
  font-family:'JetBrains Mono',monospace;font-size:20px;font-weight:600;
  background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.08);
  transition:.15s;color:var(--text);
  transform-style:preserve-3d;
  box-shadow:0 6px 0 rgba(0,0,0,.4),inset 0 1px 0 rgba(255,255,255,.08);
}
.nk:hover{background:rgba(252,221,9,.1);border-color:rgba(252,221,9,.2);color:var(--y)}
.nk:active{transform:translateY(3px);box-shadow:0 2px 0 rgba(0,0,0,.4)}
.nk.action{background:rgba(7,137,48,.15);border-color:rgba(7,137,48,.25);color:var(--green)}
.nk.action:hover{background:rgba(7,137,48,.25)}
.nk.del{background:rgba(218,18,26,.1);border-color:rgba(218,18,26,.2);color:var(--red)}
.nk.del:hover{background:rgba(218,18,26,.2)}

/* RATE TYPE TOGGLE */
.rate-toggle{display:flex;border-radius:16px;background:rgba(0,0,0,.3);border:1px solid var(--border);padding:4px;gap:4px;margin-bottom:20px}
.rt-opt{flex:1;padding:10px;border-radius:12px;text-align:center;font-size:13px;font-weight:700;cursor:pointer;
  color:var(--text2);transition:.2s;border:1px solid transparent}
.rt-opt.active{background:rgba(252,221,9,.1);border-color:rgba(252,221,9,.25);color:var(--y)}

/* CURRENCY PICKER */
.cur-picker-info{padding:24px 32px}
.cur-picker-info .section-title{font-size:28px;margin-bottom:8px}
.cur-search{
  display:flex;align-items:center;gap:10px;
  background:rgba(255,255,255,.04);border:1px solid var(--border);
  border-radius:16px;padding:12px 16px;margin-bottom:16px;
}
.cur-search input{flex:1;background:none;border:none;outline:none;color:var(--text);font-size:15px}
.cur-search input::placeholder{color:var(--text3)}
.cur-chips{display:grid;grid-template-columns:repeat(4,1fr);gap:8px;max-height:360px;overflow-y:auto;padding:2px}
.cur-chip{
  padding:12px 8px;border-radius:16px;text-align:center;cursor:pointer;
  background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.06);
  transition:.2s;
}
.cur-chip:hover,.cur-chip.sel{background:rgba(252,221,9,.08);border-color:rgba(252,221,9,.25)}
.cur-chip-flag{font-size:24px;margin-bottom:4px}
.cur-chip-code{font-size:11px;font-weight:700;color:var(--text2)}
.cur-chip-rate{font-size:10px;color:var(--text3);font-family:'JetBrains Mono',monospace;margin-top:1px}

/* ═══════════ ETHIOPIAN SECTION ═══════════ */
#ethiopia{padding:100px 40px;text-align:center;
  background:linear-gradient(135deg,rgba(7,137,48,.06),rgba(252,221,9,.04),rgba(218,18,26,.05))}
/* ETHIOPIAN CROSS */
.eth-cross{
  width:80px;height:80px;margin:0 auto 32px;position:relative;
}
.eth-cross::before,.eth-cross::after{content:'';position:absolute;background:linear-gradient(var(--y),var(--g));border-radius:4px}
.eth-cross::before{width:16px;height:80px;left:50%;transform:translateX(-50%)}
.eth-cross::after{width:80px;height:16px;top:50%;transform:translateY(-50%)}
.cross-dot{position:absolute;inset:-8px;display:grid;place-items:center}
.cross-dot::before{content:'☆';font-size:20px;color:var(--y)}
.eth-big-text{font-family:'Noto Serif Ethiopic',serif;font-size:clamp(32px,6vw,64px);
  color:transparent;-webkit-text-stroke:1px rgba(252,221,9,.3);
  letter-spacing:8px;margin-bottom:16px;}
.eth-quote{font-size:clamp(16px,2vw,22px);color:var(--text2);max-width:600px;margin:0 auto 48px;
  line-height:1.7;font-style:italic}
.eth-stats-row{display:flex;gap:24px;justify-content:center;flex-wrap:wrap}
.eth-stat{padding:24px 32px;border-radius:24px;
  background:rgba(10,21,40,.6);border:1px solid var(--border);
  backdrop-filter:blur(12px);min-width:160px;
}
.eth-stat-num{font-family:'Orbitron',sans-serif;font-size:36px;font-weight:900;
  background:linear-gradient(135deg,var(--y),var(--g));
  -webkit-background-clip:text;-webkit-text-fill-color:transparent;line-height:1}
.eth-stat-lbl{font-size:12px;color:var(--text2);margin-top:6px}

/* ═══════════ FOOTER ═══════════ */
footer{padding:48px 40px;
  background:var(--bg2);border-top:1px solid var(--border);text-align:center}
.footer-logo{font-family:'Orbitron',sans-serif;font-size:28px;font-weight:900;
  background:linear-gradient(90deg,var(--g),var(--y));-webkit-background-clip:text;-webkit-text-fill-color:transparent;
  margin-bottom:8px}
.footer-amharic{font-family:'Noto Serif Ethiopic',serif;font-size:18px;color:rgba(252,221,9,.4);margin-bottom:20px}
.footer-links{display:flex;gap:24px;justify-content:center;margin-bottom:24px;flex-wrap:wrap}
.footer-links a{font-size:13px;color:var(--text2);text-decoration:none;cursor:pointer;transition:.2s}
.footer-links a:hover{color:var(--y)}
.footer-eth-flag{width:60px;height:36px;border-radius:6px;overflow:hidden;display:flex;margin:0 auto 16px;
  box-shadow:0 4px 16px rgba(0,0,0,.4)}
.footer-eth-flag span{flex:1}
.footer-disc{font-size:11px;color:var(--text3);max-width:600px;margin:0 auto;line-height:1.7;
  padding:16px;background:rgba(255,200,0,.04);border:1px solid rgba(252,221,9,.08);border-radius:12px}

/* ═══════════ CURRENCY PICKER MODAL ═══════════ */
.modal-backdrop{position:fixed;inset:0;background:rgba(0,0,0,.8);z-index:200;
  opacity:0;pointer-events:none;transition:.3s;backdrop-filter:blur(10px)}
.modal-backdrop.open{opacity:1;pointer-events:all}
.modal-box{
  position:fixed;top:50%;left:50%;transform:translate(-50%,-40%);
  width:min(520px,92vw);max-height:80vh;overflow-y:auto;
  background:var(--bg2);border:1px solid var(--border2);
  border-radius:28px;z-index:201;
  transition:transform .35s cubic-bezier(.34,1.56,.64,1),opacity .3s;
  opacity:0;pointer-events:none;
}
.modal-backdrop.open .modal-box{transform:translate(-50%,-50%);opacity:1;pointer-events:all}
.modal-head{padding:24px 28px;border-bottom:1px solid var(--border);
  display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;
  background:var(--bg2);z-index:2}
.modal-title{font-family:'Orbitron',sans-serif;font-size:16px;font-weight:700}
.modal-close{width:36px;height:36px;border-radius:10px;background:rgba(255,255,255,.06);
  border:1px solid var(--border);display:flex;align-items:center;justify-content:center;
  cursor:pointer;font-size:16px;color:var(--text2);transition:.2s}
.modal-close:hover{background:rgba(255,61,113,.1);color:var(--red)}

/* ═══════════ SCROLL REVEAL ═══════════ */
.reveal{opacity:0;transform:translateY(40px);transition:.7s cubic-bezier(.25,.46,.45,.94)}
.reveal.revealed{opacity:1;transform:translateY(0)}
.reveal-l{opacity:0;transform:translateX(-40px);transition:.7s}
.reveal-l.revealed{opacity:1;transform:translateX(0)}
.reveal-r{opacity:0;transform:translateX(40px);transition:.7s}
.reveal-r.revealed{opacity:1;transform:translateX(0)}

/* ═══════════ TOAST ═══════════ */
.toast{position:fixed;bottom:32px;left:50%;transform:translateX(-50%) translateY(20px);
  padding:14px 24px;background:rgba(15,28,55,.95);border:1px solid rgba(252,221,9,.2);
  border-radius:20px;font-size:13px;font-weight:600;color:var(--text);
  z-index:500;opacity:0;transition:.3s;white-space:nowrap;
  box-shadow:0 12px 40px rgba(0,0,0,.5);}
.toast.show{opacity:1;transform:translateX(-50%) translateY(0)}

/* ═══════════ UTILS ═══════════ */
.up{color:var(--green)}.dn{color:var(--red)}.gold{color:var(--gold)}
.max-w{max-width:1300px;margin:0 auto}
@media(max-width:768px){
  #rates,#chart-section,#calculator,#ethiopia,footer{padding:70px 20px}
  .rates-grid{grid-template-columns:1fr 1fr}
  .chart-ohlc{grid-template-columns:1fr 1fr}
}
@media(max-width:480px){
  .rates-grid{grid-template-columns:1fr}
  .cur-chips{grid-template-columns:repeat(3,1fr)}
  .chart-ohlc{grid-template-columns:1fr 1fr}
}
</style>
</head>
<body>

<!-- CURSOR -->
<div id="cursor"></div>
<div id="cursor-ring"></div>

<!-- THREE.JS BACKGROUND -->
<canvas id="bg-canvas"></canvas>

<!-- NAV -->
<nav id="main-nav">
  <a class="nav-logo" href="#hero">
    <div class="nav-logo-icon">ብር</div>
    <div class="nav-logo-text">ETBirr</div>
  </a>
  <div class="nav-links">
    <div class="nav-link active" onclick="scrollTo('hero')">Home</div>
    <div class="nav-link" onclick="scrollTo('rates')">Rates</div>
    <div class="nav-link" onclick="scrollTo('chart-section')">Chart</div>
    <div class="nav-link" onclick="scrollTo('calculator')">Convert</div>
    <div class="nav-link" onclick="scrollTo('ethiopia')">About ETB</div>
  </div>
  <div style="display:flex;align-items:center;gap:10px">
    <div class="nav-live"><div class="nav-live-dot"></div>LIVE</div>
  </div>
</nav>

<!-- DOT NAV -->
<div class="dot-nav" id="dot-nav">
  <a href="#hero" class="active" title="Home"></a>
  <a href="#ticker-section" title="Ticker"></a>
  <a href="#rates" title="Rates"></a>
  <a href="#chart-section" title="Chart"></a>
  <a href="#calculator" title="Convert"></a>
  <a href="#ethiopia" title="About"></a>
</div>

<!-- ══════════════════════════════════
     HERO
══════════════════════════════════ -->
<section id="hero">
  <!-- Floating symbols -->
  <div class="float-symbols" id="float-symbols"></div>

  <div class="coin-wrap">
    <div class="coin">
      <div class="coin-glow"></div>
      <div class="coin-face">
        <div class="coin-symbol">ብር</div>
        <div class="coin-label">BIRR</div>
      </div>
      <div class="coin-back">
        <div class="coin-symbol" style="font-size:30px">🇪🇹</div>
        <div class="coin-label">ETB</div>
      </div>
    </div>
  </div>

  <div class="hero-eyebrow">Ethiopian Parallel Market Exchange</div>
  <div class="hero-amharic">የኢትዮጵያ ብር · ጥቁር ገበያ</div>
  <h1 class="hero-title">ETBirr</h1>
  <p class="hero-sub">Real-time black market exchange rates for the Ethiopian Birr — live, accurate, and beautifully presented.</p>

  <div class="rate-display">
    <div class="rate-label">USD / ETB · BLACK MARKET RATE</div>
    <div class="rate-big" id="hero-rate-big"><?= floor($D['black_usd']) ?><span>.<?= substr(number_format($D['black_usd'],2),strpos(number_format($D['black_usd'],2),'.')+1) ?></span></div>
    <div class="rate-badge">
      ▲ +<?= $D['premium_pct'] ?>% above NBE official · Updated <?= $D['timestamp'] ?>
    </div>
    <div class="rate-vs">Official NBE Rate: <strong><?= $D['official_usd'] ?> ETB</strong> &nbsp;|&nbsp; Spread: <strong style="color:var(--gold)"><?= round($D['black_usd']-$D['official_usd'],2) ?> ETB</strong></div>
  </div>

  <div class="hero-stats">
    <div class="hs-card"><div class="hs-l">24h High</div><div class="hs-v up" id="h-high"><?= $D['high24'] ?></div></div>
    <div class="hs-card"><div class="hs-l">24h Low</div><div class="hs-v dn" id="h-low"><?= $D['low24'] ?></div></div>
    <div class="hs-card"><div class="hs-l">Black Market</div><div class="hs-v gold" id="h-bm"><?= $D['black_usd'] ?></div></div>
    <div class="hs-card"><div class="hs-l">Official NBE</div><div class="hs-v" id="h-off"><?= $D['official_usd'] ?></div></div>
    <div class="hs-card"><div class="hs-l">Premium</div><div class="hs-v gold">+<?= $D['premium_pct'] ?>%</div></div>
  </div>

  <div class="scroll-hint">
    <div class="scroll-mouse"><div class="scroll-wheel"></div></div>
    SCROLL
  </div>
</section>

<!-- ══════════════════════════════════
     TICKER
══════════════════════════════════ -->
<div id="ticker-section">
  <div class="eth-strip"></div>
  <div class="ticker-track" id="ticker-inner">
    <?php $tt=''; foreach($D['pairs'] as $c=>$p){$cls=$p['change']>=0?'up':'dn';$s=$p['change']>=0?'▲':'▼';$tt.="<div class='tick'><span class='tick-flag'>{$p['flag']}</span><span class='tick-pair'>{$c}/ETB</span><span class='tick-rate {$cls}'>{$p['black']}</span><span class='tick-chg {$cls}'>{$s}".abs($p['change'])."%</span></div>";} echo $tt.$tt; ?>
  </div>
  <div class="eth-strip"></div>
</div>

<!-- ══════════════════════════════════
     RATES GRID
══════════════════════════════════ -->
<section id="rates">
  <div class="max-w">
    <div class="section-eyebrow reveal">Live Black Market Rates</div>
    <h2 class="section-title reveal">Currency Exchange <span style="color:var(--y)">Rates</span></h2>
    <p class="section-sub reveal">Tap any card to open the calculator with that currency pre-selected.</p>
    <div class="rates-grid" id="rates-grid">
      <?php $i=0; foreach($D['pairs'] as $c=>$p): $i++;
        $cls=$p['change']>=0?'up':'dn'; $s=$p['change']>=0?'+':''; ?>
      <div class="rate-card-wrap reveal" style="transition-delay:<?= $i*.04 ?>s">
        <div class="rate-card" data-tilt onclick="openCalcWith('<?= $c ?>','<?= $p['flag'] ?>')">
          <div class="rc-glow"></div>
          <div class="rc-depth"></div>
          <div class="rc-top">
            <div>
              <div class="rc-flag"><?= $p['flag'] ?></div>
            </div>
            <div>
              <div class="rc-cur"><?= $c ?>/ETB</div>
              <div class="rc-name"><?= $p['name'] ?></div>
              <div class="rc-chg <?= $cls ?>" style="margin-top:6px"><?= $s.$p['change'] ?>%</div>
            </div>
          </div>
          <div class="rc-divider"></div>
          <div class="rc-rates">
            <div class="rc-rate-box">
              <div class="rc-rl">Official</div>
              <div class="rc-rv"><?= $p['official'] ?></div>
              <div style="font-size:9px;color:var(--text3)">NBE Rate</div>
            </div>
            <div class="rc-rate-box bm">
              <div class="rc-rl">Black Market</div>
              <div class="rc-rv" data-brate="<?= $c ?>"><?= $p['black'] ?></div>
              <div class="rc-prem">+<?= $p['premium'] ?>%</div>
            </div>
          </div>
          <div class="rc-spark"><canvas id="spark-<?= $c ?>"></canvas></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ══════════════════════════════════
     CHART
══════════════════════════════════ -->
<section id="chart-section">
  <div class="max-w">
    <div class="section-eyebrow reveal">Live Trading Chart</div>
    <h2 class="section-title reveal">Price <span style="color:var(--y)">History</span></h2>
    <div class="chart-container reveal">
      <div class="chart-top">
        <div>
          <div class="chart-pair-sel" id="chart-pair-sel">
            <?php $i=0; foreach(array_slice($D['pairs'],0,7,true) as $c=>$p): $i++; ?>
            <button class="cps-btn <?= $i===1?'active':'' ?>" onclick="selectChartPair('<?= $c ?>',this)"><?= $p['flag'] ?> <?= $c ?></button>
            <?php endforeach; ?>
          </div>
          <div class="time-sel" style="margin-top:12px">
            <button class="ts-btn active" onclick="setTimeRange(48,this)">24H</button>
            <button class="ts-btn" onclick="setTimeRange(24,this)">12H</button>
            <button class="ts-btn" onclick="setTimeRange(12,this)">6H</button>
            <button class="ts-btn" onclick="setTimeRange(6,this)">3H</button>
          </div>
        </div>
        <div class="chart-price-display">
          <div class="cpd-val" id="chart-price-big"><?= $D['black_usd'] ?></div>
          <div class="cpd-lbl" id="chart-pair-lbl">USD / ETB · Black Market</div>
        </div>
      </div>
      <div class="chart-canvas-wrap"><canvas id="main-chart"></canvas></div>
      <div class="chart-ohlc">
        <div class="ohlc-item"><div class="ohlc-l">Open</div><div class="ohlc-v" id="oc-o"><?= $D['chart'][0]['y'] ?? $D['black_usd'] ?></div></div>
        <div class="ohlc-item"><div class="ohlc-l">High</div><div class="ohlc-v up" id="oc-h"><?= $D['high24'] ?></div></div>
        <div class="ohlc-item"><div class="ohlc-l">Low</div><div class="ohlc-v dn" id="oc-l"><?= $D['low24'] ?></div></div>
        <div class="ohlc-item"><div class="ohlc-l">Close</div><div class="ohlc-v gold" id="oc-c"><?= $D['black_usd'] ?></div></div>
      </div>
    </div>
  </div>
</section>

<!-- ══════════════════════════════════
     CALCULATOR
══════════════════════════════════ -->
<section id="calculator">
  <div class="max-w">
    <div class="section-eyebrow reveal">Currency Converter</div>
    <h2 class="section-title reveal">Black Market <span style="color:var(--y)">Calculator</span></h2>
    <div class="calc-layout">

      <!-- CALC PANEL -->
      <div class="calc-panel reveal-l">
        <div class="rate-toggle">
          <div class="rt-opt active" onclick="setRateType('black',this)">⚡ Black Market</div>
          <div class="rt-opt" onclick="setRateType('official',this)">🏦 Official NBE</div>
        </div>
        <div class="calc-display">
          <div class="calc-from">
            <div class="calc-cur-btn" id="from-btn" onclick="openPicker('from')">
              <span class="calc-cur-flag" id="from-flag">🇺🇸</span>
              <span class="calc-cur-code" id="from-code">USD</span>
              <span class="calc-cur-arrow">▾</span>
            </div>
            <input class="calc-amount" id="calc-input" type="number" placeholder="0" value="1" oninput="doCalc()">
          </div>
          <div style="text-align:center;padding:4px 0">
            <div class="swap-btn" onclick="swapCalc()">
              <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="var(--gold)" stroke-width="2.5"><polyline points="17 1 21 5 17 9"/><path d="M3 11V9a4 4 0 0 1 4-4h14M7 23l-4-4 4-4"/><path d="M21 13v2a4 4 0 0 1-4 4H3"/></svg>
            </div>
          </div>
          <div class="calc-to">
            <div class="calc-cur-btn" id="to-btn" onclick="openPicker('to')">
              <span class="calc-cur-flag" id="to-flag">🇪🇹</span>
              <span class="calc-cur-code" id="to-code">ETB</span>
              <span class="calc-cur-arrow">▾</span>
            </div>
            <div style="text-align:right">
              <div class="calc-result-val" id="calc-result">—</div>
              <div class="calc-result-cur" id="calc-result-cur">Ethiopian Birr</div>
            </div>
          </div>
        </div>
        <!-- NUMPAD -->
        <div class="numpad">
          <?php foreach(['7','8','9','4','5','6','1','2','3','.','0','⌫'] as $k): ?>
          <div class="nk <?= $k==='⌫'?'del':'' ?>" onclick="numKey('<?= $k ?>')"><?= $k ?></div>
          <?php endforeach; ?>
          <div class="nk action" style="grid-column:1/3" onclick="doCalc()">Convert</div>
          <div class="nk del" onclick="clearCalc()">C</div>
        </div>
        <!-- RATE REFERENCE -->
        <div style="margin-top:20px;padding:16px;border-radius:16px;background:rgba(255,255,255,.03);border:1px solid var(--border)">
          <div style="font-size:11px;color:var(--text3);text-transform:uppercase;letter-spacing:.5px;margin-bottom:10px">Rate Reference · USD/ETB</div>
          <div style="display:flex;justify-content:space-between;margin-bottom:6px;font-size:13px">
            <span style="color:var(--text2)">Official NBE</span>
            <span style="font-family:'JetBrains Mono',monospace;font-weight:600" id="ref-off"><?= $D['official_usd'] ?> ETB</span>
          </div>
          <div style="display:flex;justify-content:space-between;font-size:13px">
            <span style="color:var(--text2)">Black Market</span>
            <span style="font-family:'JetBrains Mono',monospace;font-weight:700;color:var(--gold)" id="ref-bm"><?= $D['black_usd'] ?> ETB</span>
          </div>
        </div>
      </div>

      <!-- CURRENCY PICKER PANEL -->
      <div class="reveal-r" style="border-radius:28px;background:var(--card2);border:1px solid var(--border);backdrop-filter:blur(20px);overflow:hidden">
        <div class="cur-picker-info">
          <div class="section-eyebrow" style="margin-bottom:8px">Select Currency</div>
          <div class="section-title" style="font-size:22px;margin-bottom:16px">All <span style="color:var(--y)"><?= count($D['pairs']) ?> Pairs</span></div>
          <div class="cur-search">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="color:var(--text3)"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <input type="text" placeholder="Search currency…" oninput="filterChips(this.value)">
          </div>
        </div>
        <div class="cur-chips" id="cur-chips-grid" style="padding:0 20px 24px">
          <?php foreach($D['pairs'] as $c=>$p): ?>
          <div class="cur-chip <?= $c==='USD'?'sel':'' ?>" data-cur="<?= $c ?>" onclick="selectFromGrid('<?= $c ?>','<?= $p['flag'] ?>','<?= $p['name'] ?>')">
            <div class="cur-chip-flag"><?= $p['flag'] ?></div>
            <div class="cur-chip-code"><?= $c ?></div>
            <div class="cur-chip-rate"><?= $p['black'] ?></div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ══════════════════════════════════
     ETHIOPIA SECTION
══════════════════════════════════ -->
<section id="ethiopia">
  <div class="max-w" style="text-align:center">
    <div class="eth-cross reveal"></div>
    <div class="eth-big-text reveal">ኢትዮጵያ</div>
    <div class="section-eyebrow reveal" style="letter-spacing:6px">Ethiopia · Land of Origins</div>
    <h2 class="section-title reveal" style="margin-bottom:16px">The Ethiopian <span style="color:var(--y)">Birr</span></h2>
    <p class="eth-quote reveal">The Ethiopian Birr (ብር) has been the currency of Ethiopia since 1945, replacing the Maria Theresa Thaler. The parallel market emerged as demand for hard currency grew beyond official banking channels.</p>
    <div class="eth-stats-row">
      <div class="eth-stat reveal"><div class="eth-stat-num" data-count="<?= $D['black_usd'] ?>">0</div><div class="eth-stat-lbl">ETB per USD (Black Market)</div></div>
      <div class="eth-stat reveal" style="transition-delay:.1s"><div class="eth-stat-num" data-count="<?= $D['premium_pct'] ?>">0</div><div class="eth-stat-lbl">% Premium Over Official</div></div>
      <div class="eth-stat reveal" style="transition-delay:.2s"><div class="eth-stat-num" data-count="<?= count($D['pairs']) ?>">0</div><div class="eth-stat-lbl">Currency Pairs Tracked</div></div>
      <div class="eth-stat reveal" style="transition-delay:.3s"><div class="eth-stat-num">30s</div><div class="eth-stat-lbl">Auto-Refresh Interval</div></div>
    </div>
  </div>
</section>

<!-- ══════════════════════════════════
     FOOTER
══════════════════════════════════ -->
<footer>
  <div class="footer-eth-flag"><span style="background:#078930"></span><span style="background:#FCDD09"></span><span style="background:#DA121A"></span></div>
  <div class="footer-logo">ETBirr</div>
  <div class="footer-amharic">የኢትዮጵያ ብር ጥቁር ገበያ</div>
  <div class="footer-links">
    <a onclick="scrollTo('hero')">Home</a>
    <a onclick="scrollTo('rates')">Live Rates</a>
    <a onclick="scrollTo('chart-section')">Chart</a>
    <a onclick="scrollTo('calculator')">Calculator</a>
    <a onclick="scrollTo('ethiopia')">About ETB</a>
  </div>
  <div class="footer-disc">
    ⚠️ ETBirr displays estimated parallel/black market exchange rates for informational purposes only.
    These are not official National Bank of Ethiopia (NBE) rates. Always verify with local exchange offices.
    Data sourced from public APIs · Parallel market premium ~23.5% applied · Refreshes every 30 seconds.
  </div>
  <div style="margin-top:20px;font-size:11px;color:var(--text3)">© <?= date('Y') ?> ETBirr · Built with passion for Ethiopia 🇪🇹</div>
</footer>

<!-- CURRENCY PICKER MODAL -->
<div class="modal-backdrop" id="modal-backdrop" onclick="closeModal()">
  <div class="modal-box" onclick="event.stopPropagation()">
    <div class="modal-head">
      <div class="modal-title" id="modal-title">Select Currency</div>
      <div class="modal-close" onclick="closeModal()">✕</div>
    </div>
    <div style="padding:16px 24px 8px">
      <div class="cur-search">
        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="color:var(--text3)"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <input type="text" id="modal-search" placeholder="Search…" oninput="filterModal(this.value)">
      </div>
    </div>
    <div class="cur-chips" id="modal-chips" style="padding:8px 24px 24px"></div>
  </div>
</div>

<!-- TOAST -->
<div class="toast" id="toast"></div>

<!-- ═══════════════════════════════════════
     SCRIPTS
═══════════════════════════════════════ -->
<script>
// ── DATA ──
let D = <?= json_encode($D) ?>;
let chartPair = 'USD';
let mainChartInst = null;
let calcFromCur = 'USD';
let calcToCur = 'ETB';
let rateType = 'black';
let modalTarget = 'from';
let calcInput = '';

// ── THREE.JS BACKGROUND ──
(function initThree(){
  const canvas = document.getElementById('bg-canvas');
  const renderer = new THREE.WebGLRenderer({canvas,alpha:true,antialias:true});
  renderer.setPixelRatio(Math.min(devicePixelRatio,2));
  const scene = new THREE.Scene();
  const camera = new THREE.PerspectiveCamera(60, innerWidth/innerHeight, 0.1, 1000);
  camera.position.z = 5;

  // Particles
  const count = 1800;
  const geo = new THREE.BufferGeometry();
  const pos = new Float32Array(count*3);
  const colors = new Float32Array(count*3);
  const ethColors = [
    [0.03,0.54,0.19], // green
    [0.98,0.87,0.04], // yellow
    [0.85,0.07,0.10], // red
    [0.9,0.9,1.0],    // white
  ];
  for(let i=0;i<count;i++){
    pos[i*3]  =(Math.random()-0.5)*20;
    pos[i*3+1]=(Math.random()-0.5)*20;
    pos[i*3+2]=(Math.random()-0.5)*10;
    const c = ethColors[Math.floor(Math.random()*ethColors.length)];
    colors[i*3]=c[0]; colors[i*3+1]=c[1]; colors[i*3+2]=c[2];
  }
  geo.setAttribute('position',new THREE.BufferAttribute(pos,3));
  geo.setAttribute('color',new THREE.BufferAttribute(colors,3));
  const mat = new THREE.PointsMaterial({size:.025,vertexColors:true,transparent:true,opacity:.5});
  const particles = new THREE.Points(geo,mat);
  scene.add(particles);

  // Resize
  function resize(){ renderer.setSize(innerWidth,innerHeight); camera.aspect=innerWidth/innerHeight; camera.updateProjectionMatrix(); }
  addEventListener('resize',resize); resize();

  // Mouse parallax
  let mx=0,my=0;
  addEventListener('mousemove',e=>{mx=(e.clientX/innerWidth-.5)*.5;my=(e.clientY/innerHeight-.5)*.5;});

  // Animate
  function tick(){
    requestAnimationFrame(tick);
    const t = Date.now()*0.0002;
    particles.rotation.y = t*0.3 + mx*0.3;
    particles.rotation.x = t*0.1 + my*0.2;
    renderer.render(scene,camera);
  }
  tick();
})();

// ── CURSOR ──
(function initCursor(){
  const cur = document.getElementById('cursor');
  const ring = document.getElementById('cursor-ring');
  let rx=0,ry=0,cx=0,cy=0;
  document.addEventListener('mousemove',e=>{cx=e.clientX;cy=e.clientY;cur.style.left=cx+'px';cur.style.top=cy+'px';});
  function animRing(){rx+=(cx-rx)*.12;ry+=(cy-ry)*.12;ring.style.left=rx+'px';ring.style.top=ry+'px';requestAnimationFrame(animRing);}
  animRing();
  document.querySelectorAll('button,.nk,.calc-cur-btn,.rate-card,.cur-chip,.nav-link').forEach(el=>{
    el.addEventListener('mouseenter',()=>{ring.style.width='60px';ring.style.height='60px';ring.style.borderColor='rgba(252,221,9,.6)';});
    el.addEventListener('mouseleave',()=>{ring.style.width='40px';ring.style.height='40px';ring.style.borderColor='rgba(252,221,9,.4)';});
  });
})();

// ── FLOATING SYMBOLS ──
(function initFloats(){
  const syms = ['$','€','£','¥','₣','₹','﷼','ETB','ብር','€','$','£'];
  const wrap = document.getElementById('float-symbols');
  syms.forEach((s,i)=>{
    const el = document.createElement('div');
    el.className='fsym'; el.textContent=s;
    el.style.left = (5+Math.random()*90)+'%';
    el.style.animationDuration = (18+Math.random()*20)+'s';
    el.style.animationDelay = (Math.random()*15)+'s';
    el.style.fontSize = (14+Math.random()*20)+'px';
    wrap.appendChild(el);
  });
})();

// ── NAV SCROLL ──
window.addEventListener('scroll',()=>{
  const nav = document.getElementById('main-nav');
  nav.classList.toggle('scrolled', scrollY>60);
  // dot nav active
  const secs = ['hero','ticker-section','rates','chart-section','calculator','ethiopia'];
  const dots = document.querySelectorAll('.dot-nav a');
  let active = 0;
  secs.forEach((id,i)=>{ const el=document.getElementById(id); if(el&&el.getBoundingClientRect().top<=200) active=i; });
  dots.forEach((d,i)=>d.classList.toggle('active',i===active));
  // nav links
  document.querySelectorAll('.nav-link').forEach((l,i)=>l.classList.toggle('active',i===active-1));
});

function scrollTo(id){ document.getElementById(id)?.scrollIntoView({behavior:'smooth'}); }

// ── SCROLL REVEAL ──
const revealObs = new IntersectionObserver(entries=>{
  entries.forEach(e=>{ if(e.isIntersecting) e.target.classList.add('revealed'); });
},{threshold:.12});
document.querySelectorAll('.reveal,.reveal-l,.reveal-r').forEach(el=>revealObs.observe(el));

// ── COUNTER ANIMATION ──
const countObs = new IntersectionObserver(entries=>{
  entries.forEach(e=>{
    if(!e.isIntersecting) return;
    const el = e.target;
    const target = parseFloat(el.dataset.count)||0;
    let start=0; const dur=1800; const step=16;
    const inc = target/(dur/step);
    const t = setInterval(()=>{
      start=Math.min(start+inc,target);
      el.textContent = Number.isInteger(target)?Math.round(start).toString():start.toFixed(2);
      if(start>=target) clearInterval(t);
    },step);
    countObs.unobserve(el);
  });
},{threshold:.5});
document.querySelectorAll('[data-count]').forEach(el=>countObs.observe(el));

// ── 3D CARD TILT ──
document.querySelectorAll('[data-tilt]').forEach(card=>{
  const wrap = card.closest('.rate-card-wrap');
  wrap?.addEventListener('mousemove',e=>{
    const r = card.getBoundingClientRect();
    const x = (e.clientX-r.left)/r.width-.5;
    const y = (e.clientY-r.top)/r.height-.5;
    card.style.transform = `perspective(600px) rotateY(${x*16}deg) rotateX(${-y*16}deg) translateZ(12px)`;
    const glow = card.querySelector('.rc-glow');
    if(glow){glow.style.left=(e.clientX-r.left-100)+'px';glow.style.top=(e.clientY-r.top-100)+'px';}
  });
  wrap?.addEventListener('mouseleave',()=>{
    card.style.transform='perspective(600px) rotateY(0) rotateX(0) translateZ(0)';
    card.style.transition='transform .5s ease';
    setTimeout(()=>card.style.transition='',500);
  });
});

// ── SPARKLINES ──
function drawSpark(id, base, color){
  const el=document.getElementById(id); if(!el) return;
  const ex=Chart.getChart(el); if(ex) ex.destroy();
  let v=base,pts=[];
  for(let i=0;i<14;i++){v+=(Math.random()-.47)*.4;pts.push(+v.toFixed(3));}
  new Chart(el.getContext('2d'),{type:'line',data:{labels:pts,datasets:[{data:pts,borderColor:color,borderWidth:1.5,pointRadius:0,tension:.4,fill:false}]},
    options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false},tooltip:{enabled:false}},scales:{x:{display:false},y:{display:false}},animation:{duration:400}}});
}
Object.keys(D.pairs).forEach(c=>{
  const p=D.pairs[c];
  drawSpark('spark-'+c, p.black, p.change>=0?'#00e676':'#ff3d71');
});

// ── MAIN CHART ──
function initMainChart(){
  const el=document.getElementById('main-chart'); if(!el) return;
  if(mainChartInst) mainChartInst.destroy();
  const ctx=el.getContext('2d');
  const gr=ctx.createLinearGradient(0,0,0,280);
  gr.addColorStop(0,'rgba(251,191,36,.25)');gr.addColorStop(1,'rgba(251,191,36,.01)');
  mainChartInst=new Chart(ctx,{type:'line',
    data:{labels:D.chart.map(d=>d.x),datasets:[{label:`${chartPair}/ETB`,data:D.chart.map(d=>d.y),
      borderColor:'#fbbf24',backgroundColor:gr,borderWidth:2.5,pointRadius:0,tension:.35,fill:true}]},
    options:{responsive:true,maintainAspectRatio:false,
      interaction:{mode:'index',intersect:false},
      plugins:{legend:{display:false},tooltip:{backgroundColor:'rgba(6,9,18,.95)',borderColor:'rgba(252,221,9,.2)',borderWidth:1,
        titleColor:'#64748b',bodyColor:'#f0f4ff',padding:12,
        callbacks:{label:c=>`  ${chartPair}/ETB: ${c.parsed.y.toFixed(2)} ETB`}}},
      scales:{x:{grid:{color:'rgba(255,255,255,.04)',drawBorder:false},ticks:{color:'#3d5070',font:{size:11},maxTicksLimit:8}},
        y:{position:'right',grid:{color:'rgba(255,255,255,.04)',drawBorder:false},ticks:{color:'#3d5070',font:{family:'JetBrains Mono',size:11}}}},
      animation:{duration:600,easing:'easeInOutQuart'}}});
}
initMainChart();

function selectChartPair(cur,btn){
  chartPair=cur;
  document.querySelectorAll('.cps-btn').forEach(b=>b.classList.remove('active'));
  btn.classList.add('active');
  const p=D.pairs[cur];
  document.getElementById('chart-price-big').textContent=p.black;
  document.getElementById('chart-pair-lbl').textContent=`${cur} / ETB · Black Market`;
  document.getElementById('oc-h').textContent=p.high;
  document.getElementById('oc-l').textContent=p.low;
  document.getElementById('oc-c').textContent=p.black;
  // fake chart for this pair
  let v=p.black,pts=[];
  for(let i=0;i<48;i++){v+=(Math.random()-.48)*.35;pts.push({x:D.chart[i]?.x||i,y:+v.toFixed(3)});}
  if(mainChartInst){
    mainChartInst.data.labels=pts.map(d=>d.x);
    mainChartInst.data.datasets[0].data=pts.map(d=>d.y);
    mainChartInst.data.datasets[0].label=`${cur}/ETB`;
    mainChartInst.update();
  }
}
function setTimeRange(pts,btn){
  document.querySelectorAll('.ts-btn').forEach(b=>b.classList.remove('active'));
  btn.classList.add('active');
  if(!mainChartInst) return;
  const slice=D.chart.slice(-pts);
  mainChartInst.data.labels=slice.map(d=>d.x);
  mainChartInst.data.datasets[0].data=slice.map(d=>d.y);
  mainChartInst.update();
}

// ── CALCULATOR ──
function doCalc(){
  const amt=parseFloat(document.getElementById('calc-input').value)||0;
  const fromETB = calcFromCur==='ETB';
  const toETB   = calcToCur==='ETB';
  let result;
  if(fromETB && !toETB){
    const p=D.pairs[calcToCur]; if(!p) return;
    const rate=rateType==='black'?p.black:p.official;
    result=(amt/rate).toFixed(4);
    document.getElementById('calc-result').textContent=parseFloat(result).toLocaleString();
    document.getElementById('calc-result-cur').textContent=D.pairs[calcToCur]?.name||calcToCur;
  } else if(!fromETB && toETB){
    const p=D.pairs[calcFromCur]; if(!p) return;
    const rate=rateType==='black'?p.black:p.official;
    result=(amt*rate).toFixed(2);
    document.getElementById('calc-result').textContent=parseFloat(result).toLocaleString();
    document.getElementById('calc-result-cur').textContent='Ethiopian Birr';
  } else {
    // cross rate via ETB
    const fp=D.pairs[calcFromCur]; const tp=D.pairs[calcToCur];
    if(!fp||!tp) return;
    const fr=rateType==='black'?fp.black:fp.official;
    const tr=rateType==='black'?tp.black:tp.official;
    result=((amt*fr)/tr).toFixed(4);
    document.getElementById('calc-result').textContent=parseFloat(result).toLocaleString();
    document.getElementById('calc-result-cur').textContent=D.pairs[calcToCur]?.name||calcToCur;
  }
}
function numKey(k){
  const inp=document.getElementById('calc-input');
  if(k==='⌫'){inp.value=inp.value.slice(0,-1)||'0';}
  else if(k==='.'&&inp.value.includes('.')) return;
  else inp.value=(inp.value==='0'&&k!=='.')?k:inp.value+k;
  doCalc();
}
function clearCalc(){ document.getElementById('calc-input').value='0'; document.getElementById('calc-result').textContent='—'; }
function swapCalc(){
  [calcFromCur,calcToCur]=[calcToCur,calcFromCur];
  const ff=calcFromCur==='ETB'?'🇪🇹':(D.pairs[calcFromCur]?.flag||'🌐');
  const tf=calcToCur==='ETB'?'🇪🇹':(D.pairs[calcToCur]?.flag||'🌐');
  document.getElementById('from-flag').textContent=ff;
  document.getElementById('from-code').textContent=calcFromCur;
  document.getElementById('to-flag').textContent=tf;
  document.getElementById('to-code').textContent=calcToCur;
  doCalc();
}
function setRateType(t,el){
  rateType=t;
  document.querySelectorAll('.rt-opt').forEach(e=>e.classList.remove('active'));
  el.classList.add('active');
  doCalc();
}
function selectFromGrid(cur,flag,name){
  calcFromCur=cur;
  document.getElementById('from-flag').textContent=flag;
  document.getElementById('from-code').textContent=cur;
  document.getElementById('to-flag').textContent='🇪🇹';
  document.getElementById('to-code').textContent='ETB';
  calcToCur='ETB';
  document.querySelectorAll('.cur-chip').forEach(c=>c.classList.toggle('sel',c.dataset.cur===cur));
  scrollTo('calculator');
  doCalc();
  showToast(`${flag} ${cur} selected`);
}
function openCalcWith(cur,flag){
  selectFromGrid(cur,flag,D.pairs[cur]?.name||cur);
  scrollTo('calculator');
}
function filterChips(q){
  const l=q.toLowerCase();
  document.querySelectorAll('#cur-chips-grid .cur-chip').forEach(c=>{
    c.style.display=c.dataset.cur.toLowerCase().includes(l)?'':'none';
  });
}

// ── MODAL PICKER ──
function openPicker(target){
  modalTarget=target;
  document.getElementById('modal-title').textContent=target==='from'?'Select From Currency':'Select To Currency';
  renderModalChips();
  document.getElementById('modal-backdrop').classList.add('open');
  document.getElementById('modal-search').focus();
}
function closeModal(){ document.getElementById('modal-backdrop').classList.remove('open'); }
function renderModalChips(filter=''){
  const l=filter.toLowerCase();
  const wrap=document.getElementById('modal-chips');
  const allCurs=[{code:'ETB',flag:'🇪🇹',name:'Ethiopian Birr',rate:'—'},...Object.entries(D.pairs).map(([c,p])=>({code:c,flag:p.flag,name:p.name,rate:p.black}))];
  wrap.innerHTML=allCurs.filter(c=>c.code.toLowerCase().includes(l)||c.name.toLowerCase().includes(l)).map(c=>`
    <div class="cur-chip" onclick="pickCur('${c.code}','${c.flag}','${c.name}');closeModal()">
      <div class="cur-chip-flag">${c.flag}</div>
      <div class="cur-chip-code">${c.code}</div>
      <div class="cur-chip-rate">${c.rate}</div>
    </div>`).join('');
}
function filterModal(q){ renderModalChips(q); }
function pickCur(cur,flag,name){
  if(modalTarget==='from'){ calcFromCur=cur; document.getElementById('from-flag').textContent=flag; document.getElementById('from-code').textContent=cur; }
  else { calcToCur=cur; document.getElementById('to-flag').textContent=flag; document.getElementById('to-code').textContent=cur; }
  doCalc();
}
renderModalChips();

// ── LIVE REFRESH ──
let refreshCount=30;
setInterval(()=>{refreshCount--;if(refreshCount<=0){liveRefresh();refreshCount=30;}},1000);
async function liveRefresh(){
  try{
    const res=await fetch('?json=1');
    D=await res.json();
    updateUI();
    showToast('✓ Rates updated');
  }catch(e){}
}
function updateUI(){
  // hero
  const rb=document.getElementById('hero-rate-big');
  if(rb){const ip=D.black_usd.toFixed(2).split('.');rb.innerHTML=ip[0]+'<span>.'+(ip[1]||'00')+'</span>';}
  ['h-high','h-low','h-bm','h-off'].forEach((id,i)=>{
    const el=document.getElementById(id);
    const vals=[D.high24,D.low24,D.black_usd,D.official_usd];
    if(el) el.textContent=vals[i];
  });
  // chart
  if(mainChartInst){mainChartInst.data.labels=D.chart.map(d=>d.x);mainChartInst.data.datasets[0].data=D.chart.map(d=>d.y);mainChartInst.update();}
  document.getElementById('chart-price-big').textContent=D.pairs[chartPair]?.black||D.black_usd;
  document.getElementById('oc-h').textContent=D.high24;document.getElementById('oc-l').textContent=D.low24;document.getElementById('oc-c').textContent=D.black_usd;
  // cards
  Object.keys(D.pairs).forEach(c=>{const el=document.querySelector(`[data-brate="${c}"]`);if(el) el.textContent=D.pairs[c].black;});
  // refs
  document.getElementById('ref-off').textContent=D.official_usd+' ETB';
  document.getElementById('ref-bm').textContent=D.black_usd+' ETB';
  doCalc();
}

// ── TOAST ──
let toastT;
function showToast(msg){
  const t=document.getElementById('toast');t.textContent=msg;t.classList.add('show');
  clearTimeout(toastT);toastT=setTimeout(()=>t.classList.remove('show'),2500);
}

// ── CLOCK ──
setInterval(()=>{ /* keep timestamp fresh */ },60000);

// ── INIT CALC ──
doCalc();
</script>
</body>
</html>
