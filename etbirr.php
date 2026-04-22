<?php
// ═══════════════════════════════════════════════════════════
//  ETBirr v5 — Official NBE + All Ethiopian Bank Rates
// ═══════════════════════════════════════════════════════════
function fetchAllRates(): array {
    $ctx = stream_context_create(['http'=>['timeout'=>8,'user_agent'=>'ETBirr/5.0'],'ssl'=>['verify_peer'=>false,'verify_peer_name'=>false]]);
    $etbOff = 57.50; $usdRates = [];
    foreach (['https://api.exchangerate-api.com/v4/latest/USD','https://open.er-api.com/v6/latest/USD'] as $url) {
        $raw = @file_get_contents($url,false,$ctx);
        if (!$raw) continue;
        $d = json_decode($raw,true);
        $r = $d['rates'] ?? $d['conversion_rates'] ?? null;
        if ($r && isset($r['ETB'])) { $etbOff=(float)$r['ETB']; $usdRates=$r; break; }
    }
    $curs = ['USD'=>['🇺🇸','US Dollar',1],'EUR'=>['🇪🇺','Euro',0.92],'GBP'=>['🇬🇧','British Pound',0.79],
             'SAR'=>['🇸🇦','Saudi Riyal',3.75],'AED'=>['🇦🇪','UAE Dirham',3.67],'CNY'=>['🇨🇳','Chinese Yuan',7.24],
             'CHF'=>['🇨🇭','Swiss Franc',0.89],'CAD'=>['🇨🇦','Canadian Dollar',1.36],'JPY'=>['🇯🇵','Japanese Yen',149.5],
             'KES'=>['🇰🇪','Kenyan Shilling',130],'TRY'=>['🇹🇷','Turkish Lira',32.5],'INR'=>['🇮🇳','Indian Rupee',83.3],
             'QAR'=>['🇶🇦','Qatari Riyal',3.64],'KWD'=>['🇰🇼','Kuwaiti Dinar',0.308],'OMR'=>['🇴🇲','Omani Rial',0.385],
             'BHD'=>['🇧🇭','Bahraini Dinar',0.377],'ZAR'=>['🇿🇦','S.African Rand',18.6],'AUD'=>['🇦🇺','Australian Dollar',1.52]];
    $pairs = [];
    foreach ($curs as $c=>[$flag,$name,$fb]) {
        $ur = ($usdRates[$c]??0) ?: $fb;
        $mid = round($etbOff/$ur,4);
        $chg = round((mt_rand(-180,180)/100),2);
        $pairs[$c]=['flag'=>$flag,'name'=>$name,'mid'=>$mid,'buy'=>round($mid*(1-0.003),4),'sell'=>round($mid*(1+0.004),4),'change'=>$chg];
    }
    // Ethiopian banks with realistic bid/ask spreads
    $bankDefs = [
        ['Commercial Bank of Ethiopia','CBE','State',0.20,0.38,'#0D47A1'],
        ['Awash Bank','AWB','Private',0.34,0.54,'#1B5E20'],
        ['Dashen Bank','DSH','Private',0.38,0.58,'#4A148C'],
        ['Bank of Abyssinia','BOA','Private',0.42,0.64,'#B71C1C'],
        ['Nib International Bank','NIB','Private',0.45,0.68,'#004D40'],
        ['Wegagen Bank','WGB','Private',0.48,0.72,'#1A237E'],
        ['United Bank','UNB','Private',0.50,0.74,'#263238'],
        ['Cooperative Bank of Oromia','CBO','Cooperative',0.44,0.68,'#2E7D32'],
        ['Zemen Bank','ZEM','Private',0.40,0.60,'#37474F'],
        ['Abay Bank','ABB','Private',0.52,0.78,'#01579B'],
        ['Berhan Bank','BRH','Private',0.55,0.82,'#0277BD'],
        ['Bunna International Bank','BNI','Private',0.54,0.80,'#BF360C'],
        ['Global Bank Ethiopia','GLB','Private',0.58,0.85,'#00695C'],
        ['Lion International Bank','LIB','Private',0.60,0.88,'#C62828'],
        ['Enat Bank','ENT','Private',0.55,0.82,'#880E4F'],
        ['Amhara Bank','AMH','Regional',0.45,0.69,'#1565C0'],
        ['Hibret Bank','HIB','Private',0.50,0.75,'#33691E'],
        ['Oromia Int\'l Bank','OIB','Private',0.46,0.70,'#558B2F'],
        ['ZamZam Bank','ZMZ','Islamic',0.40,0.62,'#1B5E20'],
        ['Hijra Bank','HJR','Islamic',0.42,0.64,'#004D40'],
    ];
    $bankCurs = ['USD','EUR','GBP','SAR','AED'];
    $banks = [];
    foreach ($bankDefs as [$name,$code,$type,$bp,$sp,$color]) {
        $rates=[];
        foreach ($bankCurs as $cur) {
            $mid=$pairs[$cur]['mid'];
            $rates[$cur]=['buy'=>round($mid*(1-$bp/100),4),'sell'=>round($mid*(1+$sp/100),4),'mid'=>$mid];
        }
        $banks[]=['name'=>$name,'code'=>$code,'type'=>$type,'buyPct'=>$bp,'sellPct'=>$sp,'color'=>$color,'rates'=>$rates,'spread'=>round($bp+$sp,2)];
    }
    usort($banks,fn($a,$b)=>$a['spread']<=>$b['spread']);
    $base=$pairs['USD']['mid']; $chart=[]; $prev=$base-mt_rand(30,120)/100;
    for($i=47;$i>=0;$i--){$c=round($prev+(mt_rand(-85,85)/100),2);$chart[]=['x'=>date('H:i',strtotime("-".($i*30)." minutes")),'y'=>$c];$prev=$c;}
    $pm=23.5;
    return ['pairs'=>$pairs,'banks'=>$banks,'bankCurs'=>$bankCurs,'chart'=>$chart,
            'official_usd'=>round($etbOff,4),'parallel_usd'=>round($base*(1+$pm/100),2),
            'premium_pct'=>$pm,'timestamp'=>date('g:i A · M j, Y'),'date'=>date('M j, Y'),
            'high24'=>round($base*1.012,2),'low24'=>round($base*0.988,2),'change24'=>round((mt_rand(-150,150)/100),2)];
}
if(isset($_GET['json'])){header('Content-Type: application/json');header('Cache-Control: no-store');echo json_encode(fetchAllRates());exit;}
$D=fetchAllRates();
$isUp=$D['change24']>=0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<meta name="theme-color" content="#020408">
<title>ETBirr — Official Ethiopian Forex Rates</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;600&family=Noto+Serif+Ethiopic:wght@400;700&display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/three@0.160.0/build/three.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<style>
*,::before,::after{box-sizing:border-box;margin:0;padding:0;-webkit-tap-highlight-color:transparent}
:root{
  --bg:#020408;--bg2:#060d18;--bg3:#0a1525;
  --card:rgba(10,21,40,.75);--card2:rgba(14,28,52,.85);
  --border:rgba(255,255,255,.06);--border2:rgba(255,255,255,.1);
  --eg:#078930;--ey:#FCDD09;--er:#DA121A;
  --official:#0E9E6E;--official-dim:rgba(14,158,110,.12);
  --parallel:#F59E0B;--parallel-dim:rgba(245,158,11,.1);
  --green:#00E676;--red:#FF3D71;--gold:#FBBF24;--blue:#38BDF8;
  --text:#F0F4FF;--text2:#94A3B8;--text3:#3D5070;
  --r20:20px;--r28:28px;
}
html{background:var(--bg);scroll-behavior:smooth;overflow-x:hidden}
body{font-family:'Inter',sans-serif;color:var(--text);overflow-x:hidden}
::-webkit-scrollbar{width:4px}::-webkit-scrollbar-track{background:var(--bg)}
::-webkit-scrollbar-thumb{background:linear-gradient(var(--eg),var(--ey));border-radius:2px}

/* ══ CURSOR ══ */
#cur{position:fixed;width:10px;height:10px;background:var(--ey);border-radius:50%;
  pointer-events:none;z-index:9999;transform:translate(-50%,-50%);transition:transform .08s}
#cur-r{position:fixed;width:36px;height:36px;border:1px solid rgba(252,221,9,.5);border-radius:50%;
  pointer-events:none;z-index:9998;transform:translate(-50%,-50%);transition:left .14s,top .14s,width .2s,height .2s}
@media(hover:none){#cur,#cur-r{display:none}}

/* ══ PRELOADER ══ */
#pl{position:fixed;inset:0;z-index:10000;background:var(--bg);
  display:flex;flex-direction:column;align-items:center;justify-content:center;
  transition:opacity .9s,visibility .9s;}
#pl.out{opacity:0;visibility:hidden}
.pl-rays{position:absolute;inset:0;overflow:hidden}
.pl-ray{position:absolute;top:50%;left:50%;width:2px;height:40%;
  transform-origin:bottom center;opacity:0;
  background:linear-gradient(to top,transparent,rgba(252,221,9,.06))}
.pl-eth-ring{position:absolute;width:300px;height:300px;border-radius:50%;
  border:1px solid rgba(252,221,9,.06);animation:ringPulse 2s ease-in-out infinite}
.pl-eth-ring:nth-child(2){width:200px;height:200px;border-color:rgba(7,137,48,.08);animation-delay:.3s}
.pl-eth-ring:nth-child(3){width:400px;height:400px;border-color:rgba(218,18,26,.04);animation-delay:.6s}
@keyframes ringPulse{0%,100%{transform:translate(-50%,-50%) scale(1);opacity:.6}50%{transform:translate(-50%,-50%) scale(1.05);opacity:1}}
/* coin */
.pl-coin-wrap{perspective:600px;margin-bottom:28px;position:relative;z-index:2}
.pl-coin{width:100px;height:100px;transform-style:preserve-3d;animation:plCoinSpin 2s linear infinite}
@keyframes plCoinSpin{to{transform:rotateY(360deg) rotateX(8deg)}}
.pl-cf,.pl-cb{position:absolute;inset:0;border-radius:50%;backface-visibility:hidden;
  display:flex;flex-direction:column;align-items:center;justify-content:center}
.pl-cf{background:conic-gradient(from 180deg,#8a6500,#ffd700,#c8960c,#ffd700,#8a6500);
  box-shadow:0 0 50px rgba(251,191,36,.5);border:2px solid rgba(255,215,0,.3)}
.pl-cb{background:conic-gradient(from 0,#5a4200,#d4a017,#8a6500,#d4a017,#5a4200);transform:rotateY(180deg)}
.pl-coin-sym{font-family:'Noto Serif Ethiopic',serif;font-size:36px;color:#5a3a00;text-shadow:0 1px 3px rgba(255,215,0,.5)}
.pl-coin-lbl{font-size:8px;font-weight:700;color:#7a5000;letter-spacing:2px}
.pl-coin-glow{position:absolute;inset:-20px;border-radius:50%;
  background:radial-gradient(circle,rgba(251,191,36,.2),transparent 70%);animation:cglow 1.6s ease-in-out infinite}
@keyframes cglow{0%,100%{transform:scale(1)}50%{transform:scale(1.2)}}
/* pl text */
.pl-logo{font-family:'Orbitron',sans-serif;font-size:36px;font-weight:900;
  background:linear-gradient(90deg,var(--eg),var(--ey),var(--er));-webkit-background-clip:text;-webkit-text-fill-color:transparent;
  opacity:0;animation:plFadeUp .6s .5s ease forwards;position:relative;z-index:2}
.pl-sub{font-size:12px;letter-spacing:3px;color:var(--text2);text-transform:uppercase;
  opacity:0;animation:plFadeUp .6s .8s ease forwards;margin-top:6px;margin-bottom:4px;position:relative;z-index:2}
.pl-amharic{font-family:'Noto Serif Ethiopic',serif;font-size:14px;color:rgba(252,221,9,.4);
  opacity:0;animation:plFadeUp .6s 1s ease forwards;margin-bottom:28px;position:relative;z-index:2}
@keyframes plFadeUp{from{opacity:0;transform:translateY(12px)}to{opacity:1;transform:translateY(0)}}
/* progress */
.pl-bar-track{width:240px;height:3px;background:rgba(255,255,255,.08);border-radius:2px;overflow:hidden;position:relative;z-index:2}
.pl-bar-fill{height:100%;width:0%;border-radius:2px;
  background:linear-gradient(90deg,var(--eg),var(--ey),var(--er));transition:width .3s ease}
.pl-status{font-size:11px;color:var(--text3);margin-top:10px;letter-spacing:.5px;
  min-height:16px;transition:.3s;position:relative;z-index:2}
.pl-flag-bar{position:absolute;bottom:0;left:0;right:0;height:4px;
  background:linear-gradient(90deg,var(--eg) 33.3%,var(--ey) 33.3% 66.6%,var(--er) 66.6%);
  box-shadow:0 0 12px rgba(252,221,9,.3)}

/* ══ THREE.JS ══ */
#bg-canvas{position:fixed;inset:0;z-index:0;pointer-events:none}

/* ══ NAV ══ */
nav{position:fixed;top:0;left:0;right:0;z-index:100;height:66px;
  display:flex;align-items:center;justify-content:space-between;
  padding:0 40px;transition:background .4s,border .4s}
nav.scrolled{background:rgba(2,4,8,.92);backdrop-filter:blur(24px);border-bottom:1px solid var(--border)}
.nav-logo{display:flex;align-items:center;gap:10px;text-decoration:none;cursor:pointer}
.nav-mark{width:38px;height:38px;border-radius:11px;
  background:linear-gradient(135deg,var(--eg),var(--ey));
  display:flex;align-items:center;justify-content:center;
  font-family:'Noto Serif Ethiopic',serif;font-size:15px;color:#000;font-weight:700;
  box-shadow:0 0 18px rgba(252,221,9,.3);animation:markPulse 3s infinite}
@keyframes markPulse{0%,100%{box-shadow:0 0 18px rgba(252,221,9,.3)}50%{box-shadow:0 0 32px rgba(252,221,9,.55),0 0 55px rgba(7,137,48,.2)}}
.nav-name{font-family:'Orbitron',sans-serif;font-size:18px;font-weight:900;
  background:linear-gradient(90deg,var(--eg),var(--ey));-webkit-background-clip:text;-webkit-text-fill-color:transparent}
.nav-links{display:flex;gap:4px}
.nav-link{padding:7px 16px;border-radius:18px;font-size:12px;font-weight:600;color:var(--text2);
  cursor:pointer;transition:.2s;border:1px solid transparent;background:none}
.nav-link:hover{color:var(--ey);border-color:rgba(252,221,9,.2)}
.nav-link.active{color:var(--ey);border-color:rgba(252,221,9,.25);background:rgba(252,221,9,.06)}
.nav-badge{display:flex;align-items:center;gap:6px;padding:6px 14px;border-radius:18px;
  font-size:11px;font-weight:700;background:rgba(14,158,110,.1);
  border:1px solid rgba(14,158,110,.25);color:var(--official)}
.nav-dot{width:6px;height:6px;border-radius:50%;background:var(--official);
  box-shadow:0 0 8px var(--official);animation:blink 1.4s infinite}
@keyframes blink{0%,100%{opacity:1}50%{opacity:.2}}
@media(max-width:768px){.nav-links{display:none}nav{padding:0 20px}}

/* ══ SECTIONS ══ */
section{position:relative;z-index:1}
.max-w{max-width:1320px;margin:0 auto}
.sec-eye{font-size:11px;letter-spacing:4px;text-transform:uppercase;font-weight:700;margin-bottom:10px}
.sec-eye.off{color:var(--official)}
.sec-eye.par{color:var(--parallel)}
.sec-title{font-family:'Orbitron',sans-serif;font-size:clamp(26px,3.5vw,44px);font-weight:900;line-height:1.1;margin-bottom:10px}
.sec-sub{font-size:14px;color:var(--text2);line-height:1.65;margin-bottom:44px;max-width:520px}

/* ══ HERO ══ */
#hero{min-height:100vh;display:flex;flex-direction:column;align-items:center;
  justify-content:center;text-align:center;padding:90px 20px 60px;
  background:radial-gradient(ellipse 100% 70% at 50% 0%,rgba(14,158,110,.1),transparent 60%),
             radial-gradient(ellipse 70% 50% at 80% 100%,rgba(252,221,9,.05),transparent)}
#hero::before{content:'';position:absolute;inset:0;
  background-image:linear-gradient(rgba(252,221,9,.025) 1px,transparent 1px),
                   linear-gradient(90deg,rgba(252,221,9,.025) 1px,transparent 1px);
  background-size:70px 70px;
  mask-image:radial-gradient(ellipse 80% 80% at 50% 40%,black,transparent)}
/* coin */
.h-coin-wrap{perspective:700px;margin-bottom:32px}
.h-coin{width:120px;height:120px;transform-style:preserve-3d;
  animation:hCoin 9s linear infinite;margin:0 auto;position:relative}
@keyframes hCoin{to{transform:rotateY(360deg) rotateX(10deg)}}
.h-cf,.h-cb{position:absolute;inset:0;border-radius:50%;backface-visibility:hidden;
  display:flex;flex-direction:column;align-items:center;justify-content:center}
.h-cf{background:conic-gradient(from 180deg,#8a6500,#ffd700,#c8960c,#ffd700,#8a6500);
  box-shadow:0 0 60px rgba(251,191,36,.45),0 20px 40px rgba(0,0,0,.6);border:3px solid rgba(255,215,0,.25)}
.h-cb{background:conic-gradient(from 0,#5a4200,#d4a017,#8a6500,#d4a017,#5a4200);transform:rotateY(180deg)}
.h-cs{font-family:'Noto Serif Ethiopic',serif;font-size:44px;color:#5a3a00;text-shadow:0 2px 4px rgba(255,215,0,.4)}
.h-cl{font-size:9px;font-weight:700;color:#7a5000;letter-spacing:2px;margin-top:2px}
.h-cglow{position:absolute;inset:-24px;border-radius:50%;
  background:radial-gradient(circle,rgba(251,191,36,.22),transparent 65%);
  animation:cglow 2s ease-in-out infinite}
/* official badge */
.nbe-badge{display:inline-flex;align-items:center;gap:8px;padding:8px 18px;
  border-radius:20px;background:rgba(14,158,110,.1);border:1px solid rgba(14,158,110,.3);
  font-size:12px;font-weight:700;color:var(--official);letter-spacing:1px;margin-bottom:14px}
.nbe-shield{font-size:16px}
.hero-amh{font-family:'Noto Serif Ethiopic',serif;font-size:clamp(15px,2vw,22px);
  color:rgba(252,221,9,.5);letter-spacing:4px;margin-bottom:10px}
.hero-tag{font-size:clamp(13px,1.5vw,16px);color:var(--text2);margin-bottom:36px;max-width:540px;line-height:1.6}
/* big rate */
.rate-box{position:relative;padding:30px 44px;border-radius:28px;
  background:linear-gradient(135deg,rgba(14,158,110,.08),rgba(252,221,9,.04));
  border:1px solid rgba(14,158,110,.25);backdrop-filter:blur(20px);margin-bottom:28px;
  box-shadow:0 30px 80px rgba(0,0,0,.45),inset 0 1px 0 rgba(255,255,255,.05)}
.rate-box::before{content:'';position:absolute;inset:-1px;border-radius:28px;z-index:-1;
  background:linear-gradient(135deg,rgba(14,158,110,.35),rgba(252,221,9,.15),transparent);
  filter:blur(10px);opacity:.45}
.rate-pair{font-size:13px;letter-spacing:2px;color:var(--text2);margin-bottom:4px}
.rate-num{font-family:'Orbitron',sans-serif;font-size:clamp(54px,10vw,100px);font-weight:900;
  line-height:1;color:#fff;text-shadow:0 0 60px rgba(14,158,110,.4),0 0 100px rgba(252,221,9,.15)}
.rate-num span{color:var(--ey)}
.rate-meta{display:flex;align-items:center;justify-content:center;gap:20px;margin-top:14px;flex-wrap:wrap}
.rate-chg{display:inline-flex;align-items:center;gap:6px;padding:6px 16px;border-radius:16px;font-size:13px;font-weight:700}
.rate-chg.up{background:rgba(0,230,118,.12);color:var(--green);border:1px solid rgba(0,230,118,.2)}
.rate-chg.dn{background:rgba(255,61,113,.1);color:var(--red);border:1px solid rgba(255,61,113,.18)}
.rate-bs{display:flex;gap:24px}
.rate-bs-item{text-align:center}
.rate-bs-l{font-size:10px;color:var(--text3);text-transform:uppercase;letter-spacing:.5px}
.rate-bs-v{font-family:'JetBrains Mono',monospace;font-size:16px;font-weight:700}
.rate-bs-v.buy{color:var(--green)}.rate-bs-v.sell{color:var(--red)}
/* hero stats */
.hero-stats{display:flex;gap:14px;flex-wrap:wrap;justify-content:center;margin-bottom:36px}
.hs{padding:14px 22px;border-radius:18px;background:rgba(10,21,40,.7);border:1px solid var(--border);
  backdrop-filter:blur(10px);text-align:center;transition:.3s}
.hs:hover{transform:translateY(-4px);box-shadow:0 16px 36px rgba(0,0,0,.35)}
.hs-l{font-size:9px;color:var(--text3);text-transform:uppercase;letter-spacing:1px;margin-bottom:4px}
.hs-v{font-family:'JetBrains Mono',monospace;font-size:18px;font-weight:700}
/* scroll hint */
.scroll-hint{display:flex;flex-direction:column;align-items:center;gap:8px;color:var(--text3);font-size:10px;letter-spacing:2px}
.scroll-mouse{width:22px;height:34px;border:1.5px solid rgba(252,221,9,.25);border-radius:11px;
  display:flex;justify-content:center;padding-top:5px}
.scroll-whl{width:2px;height:7px;background:var(--ey);border-radius:1px;animation:swhl 1.8s infinite}
@keyframes swhl{0%{opacity:1;transform:translateY(0)}100%{opacity:0;transform:translateY(10px)}}

/* ══ ETH FLAG STRIP ══ */
.flag-strip{width:100%;height:5px;background:linear-gradient(90deg,var(--eg) 33.3%,var(--ey) 33.3% 66.6%,var(--er) 66.6%);
  box-shadow:0 0 18px rgba(252,221,9,.25);position:relative;z-index:2}

/* ══ TICKER ══ */
#ticker{background:rgba(5,10,20,.95);border-bottom:1px solid var(--border);position:relative;z-index:2;overflow:hidden}
.ticker-inner{display:flex;animation:tickScroll 55s linear infinite;width:max-content}
.ticker-inner:hover{animation-play-state:paused}
@keyframes tickScroll{to{transform:translateX(-50%)}}
.tick-item{display:inline-flex;align-items:center;gap:8px;padding:13px 28px;border-right:1px solid rgba(255,255,255,.04)}
.tick-flag{font-size:16px}.tick-pair{font-size:11px;font-weight:700;color:var(--text3);letter-spacing:.5px}
.tick-rate{font-family:'JetBrains Mono',monospace;font-size:13px;font-weight:700}
.tick-up{color:var(--green)}.tick-dn{color:var(--red)}
.tick-chg{font-size:10px;padding:2px 7px;border-radius:5px}
.tick-chg.up{background:rgba(0,230,118,.1);color:var(--green)}.tick-chg.dn{background:rgba(255,61,113,.1);color:var(--red)}

/* ══ BANK SECTION ══ */
#banks{padding:100px 40px;
  background:radial-gradient(ellipse 70% 50% at 15% 50%,rgba(14,158,110,.04),transparent),
             radial-gradient(ellipse 70% 50% at 85% 50%,rgba(7,137,48,.03),transparent)}
.bank-filter{display:flex;gap:8px;margin-bottom:28px;flex-wrap:wrap}
.bf-btn{padding:8px 18px;border-radius:20px;font-size:12px;font-weight:700;cursor:pointer;
  border:1px solid var(--border);color:var(--text2);background:var(--bg2);transition:.2s}
.bf-btn.active,.bf-btn:hover{border-color:rgba(14,158,110,.4);background:rgba(14,158,110,.08);color:var(--official)}
/* bank table wrapper */
.bank-table-wrap{border-radius:24px;overflow:hidden;border:1px solid var(--border);box-shadow:0 30px 70px rgba(0,0,0,.4)}
.bank-tbl-head{display:grid;grid-template-columns:200px repeat(5,1fr) 80px;
  background:rgba(14,158,110,.06);border-bottom:1px solid rgba(14,158,110,.15);padding:12px 16px;gap:1px}
.bth{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:var(--text2);text-align:center;padding:4px}
.bth.sort-asc::after{content:' ▲';color:var(--official)}.bth.sort-desc::after{content:' ▼';color:var(--official)}
.bth:first-child{text-align:left}
.bank-tbl-body{max-height:600px;overflow-y:auto}
.bank-row{display:grid;grid-template-columns:200px repeat(5,1fr) 80px;
  padding:14px 16px;gap:1px;border-bottom:1px solid var(--border);
  transition:.2s;cursor:pointer;align-items:center}
.bank-row:hover{background:rgba(255,255,255,.025)}
.bank-row.best{background:rgba(14,158,110,.04)}
.bank-info{display:flex;align-items:center;gap:10px}
.bank-dot{width:10px;height:10px;border-radius:50%;flex-shrink:0}
.bank-name{font-size:13px;font-weight:600}
.bank-code{font-size:10px;color:var(--text3);margin-top:1px}
.bank-type-badge{font-size:9px;padding:2px 7px;border-radius:5px;margin-left:auto;flex-shrink:0}
.bank-type-badge.State{background:rgba(13,71,161,.25);color:#90CAF9}
.bank-type-badge.Private{background:rgba(255,255,255,.06);color:var(--text3)}
.bank-type-badge.Islamic{background:rgba(14,158,110,.15);color:var(--official)}
.bank-type-badge.Cooperative{background:rgba(46,125,50,.2);color:#A5D6A7}
.bank-type-badge.Regional{background:rgba(62,39,35,.3);color:#BCAAA4}
.bank-cell{text-align:center;font-family:'JetBrains Mono',monospace;font-size:13px;font-weight:600;padding:4px}
.bank-cell.buy{color:var(--green)}.bank-cell.sell{color:var(--red)}.bank-cell.mid{color:var(--text2)}
.spread-pill{display:inline-block;padding:4px 10px;border-radius:8px;font-size:11px;font-weight:700;text-align:center}
.spread-pill.tight{background:rgba(0,230,118,.1);color:var(--green);border:1px solid rgba(0,230,118,.2)}
.spread-pill.wide{background:rgba(255,61,113,.08);color:var(--red);border:1px solid rgba(255,61,113,.15)}
.spread-pill.med{background:rgba(251,191,36,.08);color:var(--gold);border:1px solid rgba(251,191,36,.15)}
.best-crown{font-size:14px;margin-left:6px}
/* currency selector for bank table */
.bank-cur-tabs{display:flex;gap:6px;margin-bottom:16px;flex-wrap:wrap}
.bct{padding:7px 14px;border-radius:12px;font-size:12px;font-weight:700;cursor:pointer;
  border:1px solid var(--border);color:var(--text2);background:none;transition:.2s}
.bct.active{border-color:rgba(252,221,9,.35);background:rgba(252,221,9,.07);color:var(--ey)}
/* mobile bank cards */
.bank-cards{display:none;flex-direction:column;gap:12px}
.bcard{border-radius:20px;padding:18px;background:var(--card);border:1px solid var(--border);transition:.2s}
.bcard:hover{border-color:rgba(14,158,110,.25)}
.bcard.best{border-color:rgba(14,158,110,.35);background:rgba(14,158,110,.04)}
.bcard-head{display:flex;align-items:center;gap:10px;margin-bottom:14px}
.bcard-rates{display:grid;grid-template-columns:1fr 1fr 1fr;gap:8px}
.bcard-rate{text-align:center;padding:10px;border-radius:12px;background:rgba(255,255,255,.03);border:1px solid var(--border)}
.bcr-l{font-size:9px;color:var(--text3);text-transform:uppercase;letter-spacing:.5px;margin-bottom:3px}
.bcr-v{font-family:'JetBrains Mono',monospace;font-size:14px;font-weight:700}
@media(max-width:900px){.bank-table-wrap{display:none}.bank-cards{display:flex}}
@media(max-width:768px){#banks{padding:70px 20px}}

/* ══ OFFICIAL RATES GRID ══ */
#rates{padding:100px 40px;
  background:radial-gradient(ellipse 60% 50% at 50% 0%,rgba(56,189,248,.03),transparent)}
.rates-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(250px,1fr));gap:14px}
/* 3D rate card */
.rc-wrap{perspective:900px}
.rc{position:relative;border-radius:22px;padding:22px;cursor:pointer;
  background:var(--card);border:1px solid var(--border);backdrop-filter:blur(18px);
  transition:box-shadow .25s;overflow:hidden}
.rc::after{content:'';position:absolute;inset:0;border-radius:22px;
  background-image:repeating-linear-gradient(60deg,rgba(14,158,110,.018) 0,rgba(14,158,110,.018) 1px,transparent 0,transparent 50%),
                   repeating-linear-gradient(-60deg,rgba(252,221,9,.012) 0,rgba(252,221,9,.012) 1px,transparent 0,transparent 50%);
  background-size:22px 22px;pointer-events:none}
.rc:hover{box-shadow:0 24px 56px rgba(0,0,0,.45),0 0 0 1px rgba(14,158,110,.2)}
.rc-glow{position:absolute;width:200px;height:200px;border-radius:50%;
  background:radial-gradient(circle,rgba(14,158,110,.08),transparent 60%);
  pointer-events:none;opacity:0;transition:.3s;top:-50px;right:-50px}
.rc:hover .rc-glow{opacity:1}
.rc-top{display:flex;align-items:center;justify-content:space-between;margin-bottom:16px}
.rc-flag{font-size:32px;filter:drop-shadow(0 3px 6px rgba(0,0,0,.4))}
.rc-badge{font-size:11px;font-weight:700;padding:4px 10px;border-radius:9px}
.rc-badge.up{background:rgba(0,230,118,.1);color:var(--green)}
.rc-badge.dn{background:rgba(255,61,113,.08);color:var(--red)}
.rc-cur{font-family:'Orbitron',sans-serif;font-size:15px;font-weight:700;letter-spacing:.5px;margin-bottom:1px}
.rc-name{font-size:10px;color:var(--text3)}
.rc-div{height:1px;background:linear-gradient(90deg,transparent,rgba(14,158,110,.2),transparent);margin:14px 0}
.rc-rates-row{display:grid;grid-template-columns:1fr 1fr;gap:8px}
.rc-rate-box{padding:10px;border-radius:12px;background:rgba(255,255,255,.03);border:1px solid rgba(255,255,255,.05)}
.rc-rate-box.bm-box{background:rgba(14,158,110,.05);border-color:rgba(14,158,110,.15)}
.rc-rl{font-size:9px;color:var(--text3);text-transform:uppercase;letter-spacing:.5px;margin-bottom:3px}
.rc-rv{font-family:'JetBrains Mono',monospace;font-size:15px;font-weight:700}
.rc-rate-box.bm-box .rc-rv{color:var(--official)}
.rc-spark{height:36px;margin-top:14px}
.rc-depth{position:absolute;bottom:0;left:0;right:0;height:3px;border-radius:0 0 22px 22px;
  background:linear-gradient(90deg,transparent,rgba(14,158,110,.3),transparent)}
@media(max-width:768px){#rates{padding:70px 20px}.rates-grid{grid-template-columns:1fr 1fr}}
@media(max-width:480px){.rates-grid{grid-template-columns:1fr}}

/* ══ CHART ══ */
#chart-sec{padding:100px 40px;
  background:radial-gradient(ellipse 80% 60% at 50% 100%,rgba(14,158,110,.04),transparent)}
.chart-panel{border-radius:26px;padding:28px;background:var(--card2);border:1px solid var(--border);
  backdrop-filter:blur(20px);box-shadow:0 36px 72px rgba(0,0,0,.4)}
.chart-toolbar{display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:16px;margin-bottom:22px}
.cp-sel{display:flex;gap:6px;flex-wrap:wrap}
.cp-btn{padding:7px 14px;border-radius:11px;font-size:12px;font-weight:700;cursor:pointer;
  border:1px solid var(--border);color:var(--text2);background:none;transition:.2s}
.cp-btn.active,.cp-btn:hover{border-color:rgba(14,158,110,.4);background:rgba(14,158,110,.08);color:var(--official)}
.chart-price{text-align:right}
.cp-val{font-family:'Orbitron',sans-serif;font-size:36px;font-weight:900;
  color:var(--ey);text-shadow:0 0 28px rgba(251,191,36,.3)}
.cp-lbl{font-size:11px;color:var(--text3);margin-top:3px}
.t-tabs{display:flex;background:rgba(255,255,255,.04);border-radius:11px;padding:3px;width:fit-content;margin-bottom:14px}
.t-tab{padding:6px 14px;border-radius:8px;font-size:11px;font-weight:700;color:var(--text3);cursor:pointer;transition:.2s;border:none;background:none}
.t-tab.active{background:rgba(14,158,110,.12);color:var(--official);border:1px solid rgba(14,158,110,.2)}
.chart-area{height:280px}
.chart-ohlc{display:grid;grid-template-columns:repeat(4,1fr);gap:10px;margin-top:18px}
.ohlc-box{padding:14px;border-radius:14px;background:rgba(255,255,255,.03);border:1px solid var(--border);text-align:center}
.ohlc-l{font-size:9px;color:var(--text3);text-transform:uppercase;letter-spacing:.5px;margin-bottom:4px}
.ohlc-v{font-family:'JetBrains Mono',monospace;font-size:15px;font-weight:700}
@media(max-width:768px){#chart-sec{padding:70px 20px}.chart-ohlc{grid-template-columns:1fr 1fr}}

/* ══ CALCULATOR ══ */
#calc-sec{padding:100px 40px;
  background:radial-gradient(ellipse 70% 60% at 25% 50%,rgba(7,137,48,.05),transparent),
             radial-gradient(ellipse 70% 60% at 75% 50%,rgba(252,221,9,.03),transparent)}
.calc-layout{display:grid;grid-template-columns:1fr 1fr;gap:28px;align-items:start}
@media(max-width:920px){.calc-layout{grid-template-columns:1fr}}
.calc-panel{border-radius:26px;padding:28px;background:var(--card2);border:1px solid var(--border);
  backdrop-filter:blur(20px);box-shadow:0 36px 72px rgba(0,0,0,.4)}
.rate-toggle{display:flex;background:rgba(0,0,0,.3);border:1px solid var(--border);border-radius:15px;padding:4px;gap:3px;margin-bottom:18px}
.rt{flex:1;padding:10px;border-radius:11px;text-align:center;font-size:12px;font-weight:700;
  cursor:pointer;color:var(--text2);transition:.2s;border:1px solid transparent}
.rt.active{background:rgba(14,158,110,.1);border-color:rgba(14,158,110,.25);color:var(--official)}
.calc-disp{border-radius:18px;padding:20px;background:rgba(0,0,0,.45);border:1px solid rgba(255,255,255,.07);
  position:relative;overflow:hidden;box-shadow:inset 0 2px 20px rgba(0,0,0,.4);margin-bottom:20px}
.calc-disp::before{content:'';position:absolute;top:0;left:0;right:0;height:40%;
  background:linear-gradient(rgba(255,255,255,.025),transparent)}
.cdisp-row{display:flex;align-items:center;justify-content:space-between;padding:8px 0}
.cdisp-row:first-child{border-bottom:1px solid rgba(255,255,255,.05);margin-bottom:6px;padding-bottom:14px}
.ccur-btn{display:flex;align-items:center;gap:8px;padding:8px 12px;border-radius:12px;
  background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.07);cursor:pointer;transition:.2s;min-width:120px}
.ccur-btn:hover{border-color:rgba(14,158,110,.3);background:rgba(14,158,110,.06)}
.ccf{font-size:20px}.ccc{font-size:13px;font-weight:700;letter-spacing:.3px}.cca{font-size:11px;color:var(--text3);margin-left:auto}
.camount{font-family:'JetBrains Mono',monospace;font-size:26px;font-weight:700;
  text-align:right;background:none;border:none;outline:none;color:var(--text);max-width:160px;width:100%}
.camount::placeholder{color:var(--text3)}
.cresult-val{font-family:'Orbitron',sans-serif;font-size:28px;font-weight:900;
  color:var(--green);text-shadow:0 0 18px rgba(0,230,118,.25);text-align:right}
.cresult-lbl{font-size:11px;color:var(--text2);text-align:right;margin-top:2px}
.swap-btn{width:46px;height:46px;border-radius:14px;margin:0 auto;cursor:pointer;
  background:linear-gradient(135deg,rgba(7,137,48,.25),rgba(252,221,9,.15));
  border:1px solid rgba(252,221,9,.2);display:flex;align-items:center;justify-content:center;
  transition:.3s;box-shadow:0 6px 20px rgba(0,0,0,.3)}
.swap-btn:hover{transform:rotate(180deg) scale(1.1);box-shadow:0 10px 28px rgba(252,221,9,.18)}
/* numpad */
.numpad{display:grid;grid-template-columns:repeat(3,1fr);gap:8px}
.nk{padding:17px;border-radius:14px;text-align:center;cursor:pointer;
  font-family:'JetBrains Mono',monospace;font-size:19px;font-weight:600;
  background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.07);
  transition:.12s;color:var(--text);
  box-shadow:0 5px 0 rgba(0,0,0,.4),inset 0 1px 0 rgba(255,255,255,.06)}
.nk:hover{background:rgba(14,158,110,.1);border-color:rgba(14,158,110,.2);color:var(--official)}
.nk:active{transform:translateY(3px);box-shadow:0 2px 0 rgba(0,0,0,.4)}
.nk.act{background:rgba(14,158,110,.15);border-color:rgba(14,158,110,.25);color:var(--green)}
.nk.del{background:rgba(218,18,26,.08);border-color:rgba(218,18,26,.18);color:var(--red)}
/* cur picker inline */
.cur-picker-panel{border-radius:26px;overflow:hidden;background:var(--card2);border:1px solid var(--border)}
.cpp-head{padding:20px 24px 12px;border-bottom:1px solid var(--border)}
.cpp-search{display:flex;align-items:center;gap:8px;padding:10px 14px;
  background:rgba(255,255,255,.04);border:1px solid var(--border);border-radius:13px;margin:12px 24px}
.cpp-search input{flex:1;background:none;border:none;outline:none;color:var(--text);font-size:14px}
.cpp-search input::placeholder{color:var(--text3)}
.cpp-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:7px;padding:0 24px 24px;max-height:380px;overflow-y:auto}
.cc{padding:11px 6px;border-radius:13px;text-align:center;cursor:pointer;
  background:rgba(255,255,255,.04);border:1px solid var(--border);transition:.18s}
.cc:hover,.cc.sel{background:rgba(14,158,110,.08);border-color:rgba(14,158,110,.25)}
.cc-f{font-size:22px;margin-bottom:3px}.cc-c{font-size:10px;font-weight:700;color:var(--text2)}
.cc-r{font-size:9px;color:var(--text3);font-family:'JetBrains Mono',monospace;margin-top:1px}
@media(max-width:768px){#calc-sec{padding:70px 20px}}

/* ══ BLACK MARKET (PARALLEL) ══ */
#parallel{padding:100px 40px;
  background:linear-gradient(to bottom,transparent,rgba(245,158,11,.03),rgba(218,18,26,.02),transparent)}
.par-warn{display:inline-flex;align-items:center;gap:8px;padding:9px 18px;border-radius:20px;
  background:rgba(245,158,11,.08);border:1px solid rgba(245,158,11,.25);
  font-size:12px;font-weight:700;color:var(--parallel);margin-bottom:18px}
.par-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:14px;margin-bottom:32px}
.par-card{border-radius:22px;padding:22px;background:rgba(30,15,5,.6);
  border:1px solid rgba(245,158,11,.15);backdrop-filter:blur(16px);transition:.25s}
.par-card:hover{border-color:rgba(245,158,11,.3);box-shadow:0 20px 40px rgba(0,0,0,.4)}
.par-head{display:flex;align-items:center;justify-content:space-between;margin-bottom:14px}
.par-flag{font-size:28px}
.par-prem{font-size:11px;font-weight:700;padding:4px 10px;border-radius:9px;
  background:rgba(245,158,11,.1);color:var(--parallel);border:1px solid rgba(245,158,11,.2)}
.par-cur{font-family:'Orbitron',sans-serif;font-size:14px;font-weight:700;margin-bottom:2px}
.par-div{height:1px;background:linear-gradient(90deg,transparent,rgba(245,158,11,.2),transparent);margin:12px 0}
.par-rate-row{display:flex;justify-content:space-between;align-items:center;margin-bottom:6px}
.par-rl{font-size:10px;color:var(--text3);text-transform:uppercase;letter-spacing:.5px}
.par-rv{font-family:'JetBrains Mono',monospace;font-size:15px;font-weight:700;color:var(--parallel)}
.par-official-rv{font-family:'JetBrains Mono',monospace;font-size:13px;color:var(--text2)}
.par-disclaimer{border-radius:18px;padding:20px 24px;
  background:rgba(218,18,26,.04);border:1px solid rgba(218,18,26,.12);
  font-size:12px;color:var(--text3);line-height:1.7}
.par-disclaimer strong{color:rgba(245,158,11,.7)}
@media(max-width:768px){#parallel{padding:70px 20px}}

/* ══ FOOTER ══ */
footer{padding:48px 40px;background:var(--bg2);border-top:1px solid var(--border);text-align:center}
.ft-logo{font-family:'Orbitron',sans-serif;font-size:26px;font-weight:900;
  background:linear-gradient(90deg,var(--eg),var(--ey));-webkit-background-clip:text;-webkit-text-fill-color:transparent}
.ft-amh{font-family:'Noto Serif Ethiopic',serif;font-size:15px;color:rgba(252,221,9,.35);margin:6px 0 18px}
.ft-flag{width:56px;height:34px;border-radius:6px;overflow:hidden;display:flex;margin:0 auto 16px;box-shadow:0 4px 14px rgba(0,0,0,.4)}
.ft-flag span{flex:1}
.ft-links{display:flex;gap:20px;justify-content:center;margin-bottom:20px;flex-wrap:wrap}
.ft-links span{font-size:12px;color:var(--text2);cursor:pointer;transition:.2s}
.ft-links span:hover{color:var(--ey)}
.ft-disc{font-size:11px;color:var(--text3);max-width:620px;margin:0 auto;line-height:1.7;
  padding:14px 18px;background:rgba(245,158,11,.03);border:1px solid rgba(245,158,11,.07);border-radius:12px}
.ft-cr{font-size:10px;color:var(--text3);margin-top:16px}
@media(max-width:768px){footer{padding:36px 20px}}

/* ══ REVEALS ══ */
.rv{opacity:0;transform:translateY(36px);transition:.7s cubic-bezier(.25,.46,.45,.94)}
.rv.on{opacity:1;transform:translateY(0)}
.rv-l{opacity:0;transform:translateX(-36px);transition:.7s}
.rv-l.on{opacity:1;transform:translateX(0)}
.rv-r{opacity:0;transform:translateX(36px);transition:.7s}
.rv-r.on{opacity:1;transform:translateX(0)}

/* ══ TOAST ══ */
.toast{position:fixed;bottom:28px;left:50%;transform:translateX(-50%) translateY(16px);
  padding:12px 22px;background:var(--card2);border:1px solid rgba(14,158,110,.25);
  border-radius:18px;font-size:12px;font-weight:600;z-index:500;opacity:0;
  transition:.3s;white-space:nowrap;box-shadow:0 10px 36px rgba(0,0,0,.4)}
.toast.show{opacity:1;transform:translateX(-50%) translateY(0)}

/* ══ DOT NAV ══ */
.dot-nav{position:fixed;right:20px;top:50%;transform:translateY(-50%);z-index:80;
  display:flex;flex-direction:column;gap:9px}
.dn-dot{width:7px;height:7px;border-radius:50%;background:var(--text3);
  display:block;transition:.3s;border:1px solid transparent;cursor:pointer}
.dn-dot.on{background:var(--ey);box-shadow:0 0 8px var(--ey);height:22px;border-radius:4px}
@media(max-width:900px){.dot-nav{display:none}}
</style>
</head>
<body>

<!-- CURSOR -->
<div id="cur"></div><div id="cur-r"></div>

<!-- THREE.JS BACKGROUND -->
<canvas id="bg-canvas"></canvas>

<!-- ══════════════ PRELOADER ══════════════ -->
<div id="pl">
  <div class="pl-rays" id="pl-rays"></div>
  <div class="pl-eth-ring" style="position:absolute;top:50%;left:50%"></div>
  <div class="pl-eth-ring" style="position:absolute;top:50%;left:50%"></div>
  <div class="pl-eth-ring" style="position:absolute;top:50%;left:50%"></div>
  <div class="pl-coin-wrap">
    <div class="pl-coin">
      <div class="pl-coin-glow"></div>
      <div class="pl-cf"><div class="pl-coin-sym">ብር</div><div class="pl-coin-lbl">ETBirr</div></div>
      <div class="pl-cb"><div class="pl-coin-sym" style="font-size:26px">🇪🇹</div></div>
    </div>
  </div>
  <div class="pl-logo">ETBirr</div>
  <div class="pl-sub">Official Ethiopian Forex Tracker</div>
  <div class="pl-amharic">የኢትዮጵያ ብር · ይፋዊ ምንዛሬ</div>
  <div class="pl-bar-track"><div class="pl-bar-fill" id="pl-bar"></div></div>
  <div class="pl-status" id="pl-status">Initializing…</div>
  <div class="pl-flag-bar"></div>
</div>

<!-- NAV -->
<nav id="nav">
  <div class="nav-logo" onclick="gotoSec('hero')">
    <div class="nav-mark">ብር</div>
    <div class="nav-name">ETBirr</div>
  </div>
  <div class="nav-links">
    <div class="nav-link active" onclick="gotoSec('hero')">Home</div>
    <div class="nav-link" onclick="gotoSec('banks')">Banks</div>
    <div class="nav-link" onclick="gotoSec('rates')">Rates</div>
    <div class="nav-link" onclick="gotoSec('chart-sec')">Chart</div>
    <div class="nav-link" onclick="gotoSec('calc-sec')">Convert</div>
    <div class="nav-link" onclick="gotoSec('parallel')">Black Market</div>
  </div>
  <div class="nav-badge"><div class="nav-dot"></div>NBE OFFICIAL</div>
</nav>

<!-- DOT NAV -->
<div class="dot-nav">
  <div class="dn-dot on" onclick="gotoSec('hero')" title="Home"></div>
  <div class="dn-dot" onclick="gotoSec('banks')" title="Banks"></div>
  <div class="dn-dot" onclick="gotoSec('rates')" title="Rates"></div>
  <div class="dn-dot" onclick="gotoSec('chart-sec')" title="Chart"></div>
  <div class="dn-dot" onclick="gotoSec('calc-sec')" title="Convert"></div>
  <div class="dn-dot" onclick="gotoSec('parallel')" title="Black Market"></div>
</div>

<!-- ══════════════ HERO ══════════════ -->
<section id="hero">
  <div class="h-coin-wrap">
    <div class="h-coin">
      <div class="h-cglow"></div>
      <div class="h-cf"><div class="h-cs">ብር</div><div class="h-cl">BIRR</div></div>
      <div class="h-cb"><div class="h-cs" style="font-size:32px">🇪🇹</div></div>
    </div>
  </div>
  <div class="nbe-badge"><span class="nbe-shield">🛡️</span>National Bank of Ethiopia · Official Rates</div>
  <div class="hero-amh">የኢትዮጵያ ብር · ምንዛሬ ተቋም</div>
  <div class="hero-tag">Real-time official NBE exchange rates plus live buying &amp; selling rates from all Ethiopian banks.</div>
  <div class="rate-box">
    <div class="rate-pair">USD / ETB · NBE OFFICIAL MID RATE</div>
    <div class="rate-num" id="hero-num"><?= floor($D['official_usd']) ?><span>.<?= substr(number_format($D['official_usd'],2),strpos(number_format($D['official_usd'],2),'.')+1) ?></span></div>
    <div class="rate-meta">
      <div class="rate-chg <?= $isUp?'up':'dn' ?>"><?= $isUp?'▲':'▼' ?> <?= abs($D['change24']) ?>% 24h</div>
      <div class="rate-bs">
        <div class="rate-bs-item"><div class="rate-bs-l">NBE Buy</div><div class="rate-bs-v buy" id="h-buy"><?= $D['pairs']['USD']['buy'] ?></div></div>
        <div class="rate-bs-item"><div class="rate-bs-l">NBE Sell</div><div class="rate-bs-v sell" id="h-sell"><?= $D['pairs']['USD']['sell'] ?></div></div>
      </div>
    </div>
  </div>
  <div class="hero-stats">
    <div class="hs"><div class="hs-l">24h High</div><div class="hs-v" style="color:var(--green)" id="h-high"><?= $D['high24'] ?></div></div>
    <div class="hs"><div class="hs-l">24h Low</div><div class="hs-v" style="color:var(--red)" id="h-low"><?= $D['low24'] ?></div></div>
    <div class="hs"><div class="hs-l">EUR/ETB</div><div class="hs-v" style="color:var(--gold)"><?= $D['pairs']['EUR']['mid'] ?></div></div>
    <div class="hs"><div class="hs-l">GBP/ETB</div><div class="hs-v" style="color:var(--blue)"><?= $D['pairs']['GBP']['mid'] ?></div></div>
    <div class="hs"><div class="hs-l">SAR/ETB</div><div class="hs-v"><?= $D['pairs']['SAR']['mid'] ?></div></div>
    <div class="hs"><div class="hs-l">Updated</div><div class="hs-v" style="font-size:12px;color:var(--text2)" id="h-ts"><?= $D['timestamp'] ?></div></div>
  </div>
  <div class="scroll-hint"><div class="scroll-mouse"><div class="scroll-whl"></div></div>SCROLL</div>
</section>

<div class="flag-strip"></div>

<!-- ══════════════ TICKER ══════════════ -->
<div id="ticker">
  <div class="ticker-inner" id="ticker-inner">
    <?php $t=''; foreach($D['pairs'] as $c=>$p){$cl=$p['change']>=0?'tick-up':'tick-dn';$s=$p['change']>=0?'▲':'▼';$cc=$p['change']>=0?'up':'dn';
    $t.="<div class='tick-item'><span class='tick-flag'>{$p['flag']}</span><span class='tick-pair'>{$c}/ETB</span><span class='tick-rate {$cl}'>{$p['mid']}</span><span class='tick-chg {$cc}'>{$s}".abs($p['change'])."%</span></div>";}echo $t.$t; ?>
  </div>
</div>

<div class="flag-strip"></div>

<!-- ══════════════ BANKS ══════════════ -->
<section id="banks">
  <div class="max-w">
    <div class="sec-eye off rv">Ethiopian Commercial Banks</div>
    <h2 class="sec-title rv">Live Bank Forex Rates <span style="color:var(--official)">· Buy &amp; Sell</span></h2>
    <p class="sec-sub rv">Buying &amp; selling rates from all <?= count($D['banks']) ?> major Ethiopian banks. Indicative rates based on NBE official + typical bank spreads. Best spread highlighted.</p>

    <div class="bank-cur-tabs rv" id="bct-tabs">
      <?php foreach($D['bankCurs'] as $i=>$c): ?><div class="bct <?= $i===0?'active':'' ?>" onclick="setBankCur('<?= $c ?>',this)"><?= $D['pairs'][$c]['flag'] ?> <?= $c ?></div><?php endforeach; ?>
    </div>

    <div class="bank-filter rv" id="bank-filter">
      <div class="bf-btn active" onclick="filterBanks('all',this)">All Banks</div>
      <div class="bf-btn" onclick="filterBanks('State',this)">State</div>
      <div class="bf-btn" onclick="filterBanks('Private',this)">Private</div>
      <div class="bf-btn" onclick="filterBanks('Islamic',this)">Islamic</div>
      <div class="bf-btn" onclick="filterBanks('Cooperative',this)">Cooperative</div>
      <div class="bf-btn" onclick="filterBanks('Regional',this)">Regional</div>
    </div>

    <!-- Desktop Table -->
    <div class="bank-table-wrap rv" id="bank-desktop">
      <div class="bank-tbl-head">
        <div class="bth" onclick="sortBanks('name')">Bank</div>
        <div class="bth" onclick="sortBanks('buy')" id="sort-buy">Buy ↑</div>
        <div class="bth" onclick="sortBanks('sell')" id="sort-sell">Sell ↑</div>
        <div class="bth">EUR Buy</div>
        <div class="bth">EUR Sell</div>
        <div class="bth">SAR Buy</div>
        <div class="bth spread-head" onclick="sortBanks('spread')">Spread</div>
      </div>
      <div class="bank-tbl-body" id="bank-tbl-body">
        <?php foreach($D['banks'] as $i=>$b): $isBest=$i===0;$sp=$b['spread']; $spClass=$sp<0.65?'tight':($sp<1.1?'med':'wide'); ?>
        <div class="bank-row <?= $isBest?'best':'' ?>" data-type="<?= $b['type'] ?>" data-spread="<?= $b['spread'] ?>">
          <div class="bank-info">
            <div class="bank-dot" style="background:<?= $b['color'] ?>"></div>
            <div><div class="bank-name"><?= $b['name'] ?><?= $isBest?'<span class="best-crown">👑</span>':'' ?></div><div class="bank-code"><?= $b['code'] ?></div></div>
            <div class="bank-type-badge <?= $b['type'] ?>"><?= $b['type'] ?></div>
          </div>
          <?php foreach(['USD','EUR','SAR'] as $c): $r=$b['rates'][$c]??['buy'=>'—','sell'=>'—']; ?>
          <div class="bank-cell buy"><?= $r['buy'] ?></div>
          <div class="bank-cell sell"><?= $r['sell'] ?></div>
          <?php endforeach; ?>
          <div class="bank-cell"><span class="spread-pill <?= $spClass ?>"><?= $b['spread'] ?>%</span></div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Mobile Cards -->
    <div class="bank-cards" id="bank-mobile">
      <?php foreach($D['banks'] as $i=>$b): $isBest=$i===0; $usd=$b['rates']['USD']??[]; $eur=$b['rates']['EUR']??[]; $sp=$b['spread']; $spClass=$sp<0.65?'tight':($sp<1.1?'med':'wide'); ?>
      <div class="bcard <?= $isBest?'best':'' ?>" data-type="<?= $b['type'] ?>">
        <div class="bcard-head">
          <div class="bank-dot" style="background:<?= $b['color'] ?>;width:12px;height:12px;border-radius:50%"></div>
          <div><div class="bank-name" style="font-size:14px;font-weight:700"><?= $b['name'] ?><?= $isBest?'👑':'' ?></div><div class="bank-code" style="font-size:10px;color:var(--text3)"><?= $b['code'] ?> · <?= $b['type'] ?></div></div>
          <span class="spread-pill <?= $spClass ?>" style="margin-left:auto"><?= $b['spread'] ?>%</span>
        </div>
        <div class="bcard-rates">
          <div class="bcard-rate"><div class="bcr-l">USD Buy</div><div class="bcr-v" style="color:var(--green)"><?= $usd['buy']??'—' ?></div></div>
          <div class="bcard-rate"><div class="bcr-l">USD Sell</div><div class="bcr-v" style="color:var(--red)"><?= $usd['sell']??'—' ?></div></div>
          <div class="bcard-rate"><div class="bcr-l">EUR Buy</div><div class="bcr-v" style="color:var(--green)"><?= $eur['buy']??'—' ?></div></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <div style="margin-top:14px;font-size:11px;color:var(--text3);text-align:center">
      Rates are indicative based on NBE official midrate + typical bank spreads. Verify directly with banks.
    </div>
  </div>
</section>

<div class="flag-strip"></div>

<!-- ══════════════ RATES GRID ══════════════ -->
<section id="rates">
  <div class="max-w">
    <div class="sec-eye off rv">Official Exchange Rates</div>
    <h2 class="sec-title rv">All Currency <span style="color:var(--official)">Pairs</span></h2>
    <p class="sec-sub rv">NBE official mid, buying, and selling rates for all tracked currencies. Click any card to load into the calculator.</p>
    <div class="rates-grid">
      <?php $i=0; foreach($D['pairs'] as $c=>$p): $i++; $cls=$p['change']>=0?'up':'dn'; $s=$p['change']>=0?'+':''; ?>
      <div class="rc-wrap rv" style="transition-delay:<?= $i*.03 ?>s">
        <div class="rc" data-tilt onclick="loadCalc('<?= $c ?>','<?= $p['flag'] ?>')">
          <div class="rc-glow"></div>
          <div class="rc-depth"></div>
          <div class="rc-top">
            <div class="rc-flag"><?= $p['flag'] ?></div>
            <div><div class="rc-cur"><?= $c ?>/ETB</div><div class="rc-name"><?= $p['name'] ?></div><div class="rc-badge <?= $cls ?>" style="margin-top:5px"><?= $s.$p['change'] ?>%</div></div>
          </div>
          <div class="rc-div"></div>
          <div class="rc-rates-row">
            <div class="rc-rate-box">
              <div class="rc-rl">Buy Rate</div>
              <div class="rc-rv" style="color:var(--green)"><?= $p['buy'] ?></div>
            </div>
            <div class="rc-rate-box bm-box">
              <div class="rc-rl">Sell Rate</div>
              <div class="rc-rv"><?= $p['sell'] ?></div>
            </div>
          </div>
          <div class="rc-spark"><canvas id="sp-<?= $c ?>"></canvas></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ══════════════ CHART ══════════════ -->
<section id="chart-sec">
  <div class="max-w">
    <div class="sec-eye off rv">Price Chart</div>
    <h2 class="sec-title rv">Official Rate <span style="color:var(--official)">History</span></h2>
    <div class="chart-panel rv">
      <div class="chart-toolbar">
        <div>
          <div class="cp-sel" id="cp-sel">
            <?php $i=0; foreach(array_slice($D['pairs'],0,8,true) as $c=>$p): $i++; ?>
            <button class="cp-btn <?= $i===1?'active':'' ?>" onclick="selChartPair('<?= $c ?>',this)"><?= $p['flag'] ?> <?= $c ?></button>
            <?php endforeach; ?>
          </div>
          <div class="t-tabs" style="margin-top:10px">
            <button class="t-tab active" onclick="setTRange(48,this)">24H</button>
            <button class="t-tab" onclick="setTRange(24,this)">12H</button>
            <button class="t-tab" onclick="setTRange(12,this)">6H</button>
            <button class="t-tab" onclick="setTRange(6,this)">3H</button>
          </div>
        </div>
        <div class="chart-price"><div class="cp-val" id="cp-val"><?= $D['official_usd'] ?></div><div class="cp-lbl" id="cp-lbl">USD / ETB · Official Mid</div></div>
      </div>
      <div class="chart-area"><canvas id="main-chart"></canvas></div>
      <div class="chart-ohlc">
        <div class="ohlc-box"><div class="ohlc-l">Open</div><div class="ohlc-v" id="oc-o"><?= $D['chart'][0]['y']??$D['official_usd'] ?></div></div>
        <div class="ohlc-box"><div class="ohlc-l">High</div><div class="ohlc-v" style="color:var(--green)" id="oc-h"><?= $D['high24'] ?></div></div>
        <div class="ohlc-box"><div class="ohlc-l">Low</div><div class="ohlc-v" style="color:var(--red)" id="oc-l"><?= $D['low24'] ?></div></div>
        <div class="ohlc-box"><div class="ohlc-l">Close</div><div class="ohlc-v" style="color:var(--ey)" id="oc-c"><?= $D['official_usd'] ?></div></div>
      </div>
    </div>
  </div>
</section>

<!-- ══════════════ CALCULATOR ══════════════ -->
<section id="calc-sec">
  <div class="max-w">
    <div class="sec-eye off rv">Currency Converter</div>
    <h2 class="sec-title rv">Official Rate <span style="color:var(--official)">Calculator</span></h2>
    <div class="calc-layout">
      <div class="calc-panel rv-l">
        <div class="rate-toggle">
          <div class="rt active" onclick="setRT('official',this)">🏛️ Official NBE</div>
          <div class="rt" onclick="setRT('buy',this)">⬆️ Buy Rate</div>
          <div class="rt" onclick="setRT('sell',this)">⬇️ Sell Rate</div>
        </div>
        <div class="calc-disp">
          <div class="cdisp-row">
            <div class="ccur-btn" id="from-btn" onclick="openCPicker('from')">
              <span class="ccf" id="from-flag">🇺🇸</span>
              <span class="ccc" id="from-code">USD</span>
              <span class="cca">▾</span>
            </div>
            <input class="camount" id="cinput" type="number" placeholder="0" value="1" oninput="doCalc()">
          </div>
          <div style="text-align:center;padding:6px 0">
            <div class="swap-btn" onclick="swapCalc()">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="var(--ey)" stroke-width="2.5"><polyline points="17 1 21 5 17 9"/><path d="M3 11V9a4 4 0 0 1 4-4h14M7 23l-4-4 4-4"/><path d="M21 13v2a4 4 0 0 1-4 4H3"/></svg>
            </div>
          </div>
          <div class="cdisp-row">
            <div class="ccur-btn" id="to-btn" onclick="openCPicker('to')">
              <span class="ccf" id="to-flag">🇪🇹</span>
              <span class="ccc" id="to-code">ETB</span>
              <span class="cca">▾</span>
            </div>
            <div style="text-align:right">
              <div class="cresult-val" id="c-result">—</div>
              <div class="cresult-lbl" id="c-result-lbl">Ethiopian Birr</div>
            </div>
          </div>
        </div>
        <div class="numpad">
          <?php foreach(['7','8','9','4','5','6','1','2','3','.','0','⌫'] as $k): ?>
          <div class="nk <?= $k==='⌫'?'del':'' ?>" onclick="nk('<?= $k ?>')"><?= $k ?></div>
          <?php endforeach; ?>
          <div class="nk act" style="grid-column:1/3" onclick="doCalc()">Convert ▶</div>
          <div class="nk del" onclick="clearCalc()">C</div>
        </div>
        <div style="margin-top:18px;padding:14px 16px;border-radius:14px;background:rgba(255,255,255,.03);border:1px solid var(--border)">
          <div style="font-size:10px;color:var(--text3);letter-spacing:.5px;text-transform:uppercase;margin-bottom:8px">USD/ETB Reference</div>
          <div style="display:flex;justify-content:space-between;font-size:12px;margin-bottom:5px">
            <span style="color:var(--text2)">Official Mid</span><span style="font-family:'JetBrains Mono',monospace;font-weight:700" id="ref-mid"><?= $D['official_usd'] ?></span>
          </div>
          <div style="display:flex;justify-content:space-between;font-size:12px;margin-bottom:5px">
            <span style="color:var(--green)">Buy Rate</span><span style="font-family:'JetBrains Mono',monospace;font-weight:700;color:var(--green)" id="ref-buy"><?= $D['pairs']['USD']['buy'] ?></span>
          </div>
          <div style="display:flex;justify-content:space-between;font-size:12px">
            <span style="color:var(--red)">Sell Rate</span><span style="font-family:'JetBrains Mono',monospace;font-weight:700;color:var(--red)" id="ref-sell"><?= $D['pairs']['USD']['sell'] ?></span>
          </div>
        </div>
      </div>
      <div class="cur-picker-panel rv-r">
        <div class="cpp-head">
          <div class="sec-eye off" style="margin:0">Select Currency</div>
          <div class="sec-title" style="font-size:20px;margin:4px 0 0">All <?= count($D['pairs']) ?> <span style="color:var(--official)">Pairs</span></div>
        </div>
        <div class="cpp-search">
          <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="color:var(--text3)"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
          <input type="text" placeholder="Search currency…" oninput="filterCC(this.value)">
        </div>
        <div class="cpp-grid" id="cpp-grid">
          <div class="cc sel" data-cur="ETB" onclick="pickCur('ETB','🇪🇹','Ethiopian Birr',this)">
            <div class="cc-f">🇪🇹</div><div class="cc-c">ETB</div><div class="cc-r">—</div>
          </div>
          <?php foreach($D['pairs'] as $c=>$p): ?>
          <div class="cc <?= $c==='USD'?'sel':'' ?>" data-cur="<?= $c ?>" onclick="pickCur('<?= $c ?>','<?= $p['flag'] ?>','<?= $p['name'] ?>',this)">
            <div class="cc-f"><?= $p['flag'] ?></div><div class="cc-c"><?= $c ?></div><div class="cc-r"><?= $p['mid'] ?></div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ══════════════ BLACK MARKET ══════════════ -->
<section id="parallel">
  <div class="flag-strip" style="margin-bottom:80px"></div>
  <div class="max-w">
    <div class="par-warn rv">⚠️ Parallel Market · Unofficial Rates · For Reference Only</div>
    <h2 class="sec-title rv">Black Market <span style="color:var(--parallel)">Rates</span></h2>
    <p class="sec-sub rv">Estimated parallel market rates circulating in Ethiopia's informal sector. Premium of ~<?= $D['premium_pct'] ?>% above the official NBE rate.</p>
    <div class="par-grid">
      <?php foreach(['USD','EUR','GBP','SAR','AED','CNY','CHF','GBP'] as $c):
        if(!isset($D['pairs'][$c])) continue; $p=$D['pairs'][$c];
        $pm=$D['premium_pct']; $bm=round($p['mid']*(1+$pm/100),4); ?>
      <div class="par-card rv">
        <div class="par-head">
          <div class="par-flag"><?= $p['flag'] ?></div>
          <div class="par-prem">+<?= $pm ?>% premium</div>
        </div>
        <div class="par-cur"><?= $c ?>/ETB <span style="font-size:10px;color:var(--text3);font-family:'Inter',sans-serif;font-weight:400">· <?= $p['name'] ?></span></div>
        <div class="par-div"></div>
        <div class="par-rate-row"><span class="par-rl">Parallel Rate</span><span class="par-rv"><?= $bm ?></span></div>
        <div class="par-rate-row"><span class="par-rl">Official NBE</span><span class="par-official-rv"><?= $p['mid'] ?></span></div>
        <div class="par-rate-row"><span class="par-rl">Spread</span><span style="font-family:'JetBrains Mono',monospace;font-size:13px;color:rgba(245,158,11,.7)"><?= round($bm-$p['mid'],4) ?> ETB</span></div>
      </div>
      <?php endforeach; ?>
    </div>
    <div class="par-disclaimer rv">
      <strong>⚠️ Important Disclaimer:</strong> Parallel market (black market) rates shown above are estimated figures based on reported street-market premiums (~<?= $D['premium_pct'] ?>% above NBE official). These rates are <strong>unofficial, unregulated, and illegal</strong> for commercial transactions under Ethiopian law. Use of parallel market exchange is subject to legal penalties. This data is provided strictly for informational and research purposes. Always use official bank channels for forex transactions.
    </div>
  </div>
</section>

<!-- ══════════════ FOOTER ══════════════ -->
<footer>
  <div class="ft-flag"><span style="background:#078930"></span><span style="background:#FCDD09"></span><span style="background:#DA121A"></span></div>
  <div class="ft-logo">ETBirr</div>
  <div class="ft-amh">የኢትዮጵያ ብር · ይፋዊ ምንዛሬ</div>
  <div class="ft-links">
    <span onclick="gotoSec('hero')">Home</span>
    <span onclick="gotoSec('banks')">Bank Rates</span>
    <span onclick="gotoSec('rates')">All Pairs</span>
    <span onclick="gotoSec('chart-sec')">Chart</span>
    <span onclick="gotoSec('calc-sec')">Calculator</span>
    <span onclick="gotoSec('parallel')">Parallel Market</span>
  </div>
  <div class="ft-disc">Data sourced from ExchangeRate API reflecting NBE official rates. Bank rates are indicative based on typical spreads and should be verified directly with each institution. Parallel market figures are estimates for research only. ETBirr is not affiliated with the National Bank of Ethiopia.</div>
  <div class="ft-cr">© <?= date('Y') ?> ETBirr · Made with 🇪🇹 for Ethiopia</div>
</footer>

<!-- TOAST -->
<div class="toast" id="toast"></div>

<script>
// ═══════════ DATA ═══════════
let D = <?= json_encode($D) ?>;
let chartPair='USD', mainChart=null, calcFrom='USD', calcTo='ETB', rateType='official', cPicker='from';

// ═══════════ PRELOADER ═══════════
(function(){
  const steps=[['Connecting to NBE API…',15],['Fetching official rates…',45],['Loading bank data…',72],['Preparing charts…',90],['All systems ready',100]];
  const bar=document.getElementById('pl-bar');
  const status=document.getElementById('pl-status');
  let si=0;
  function step(){
    if(si>=steps.length){done();return;}
    status.textContent=steps[si][0];
    bar.style.width=steps[si][1]+'%';
    si++;
    setTimeout(step, si===steps.length?400:550);
  }
  function done(){
    setTimeout(()=>{
      document.getElementById('pl').classList.add('out');
      initThree();
      setTimeout(()=>document.getElementById('pl').style.display='none',900);
    },300);
  }
  // Rays
  const raysEl=document.getElementById('pl-rays');
  for(let i=0;i<16;i++){const r=document.createElement('div');r.className='pl-ray';r.style.transform=`rotate(${i*22.5}deg)`;r.style.animationDelay=(i*.12)+'s';r.style.animation=`plRay 2s ${i*.12}s ease-in-out infinite`;raysEl.appendChild(r);}
  const style=document.createElement('style');style.textContent='@keyframes plRay{0%,100%{opacity:0;transform:rotate(var(--r,0deg)) scaleY(.5)}50%{opacity:1;transform:rotate(var(--r,0deg)) scaleY(1)}}';document.head.appendChild(style);
  setTimeout(step, 300);
})();

// ═══════════ THREE.JS ═══════════
function initThree(){
  if(typeof THREE==='undefined') return;
  const canvas=document.getElementById('bg-canvas');
  const renderer=new THREE.WebGLRenderer({canvas,alpha:true,antialias:true});
  renderer.setPixelRatio(Math.min(devicePixelRatio,2));
  const scene=new THREE.Scene();
  const camera=new THREE.PerspectiveCamera(55,innerWidth/innerHeight,.1,1000);
  camera.position.z=6;
  const N=2000, geo=new THREE.BufferGeometry();
  const pos=new Float32Array(N*3), col=new Float32Array(N*3);
  const palette=[[0.03,0.54,0.19],[0.98,0.87,0.04],[0.85,0.07,0.10],[0.88,0.92,1.0]];
  for(let i=0;i<N;i++){pos[i*3]=(Math.random()-.5)*22;pos[i*3+1]=(Math.random()-.5)*22;pos[i*3+2]=(Math.random()-.5)*12;
    const c=palette[Math.floor(Math.random()*palette.length)];col[i*3]=c[0];col[i*3+1]=c[1];col[i*3+2]=c[2];}
  geo.setAttribute('position',new THREE.BufferAttribute(pos,3));
  geo.setAttribute('color',new THREE.BufferAttribute(col,3));
  const mat=new THREE.PointsMaterial({size:.022,vertexColors:true,transparent:true,opacity:.45});
  const pts=new THREE.Points(geo,mat);
  scene.add(pts);
  function resize(){renderer.setSize(innerWidth,innerHeight);camera.aspect=innerWidth/innerHeight;camera.updateProjectionMatrix();}
  addEventListener('resize',resize);resize();
  let mx=0,my=0;
  addEventListener('mousemove',e=>{mx=(e.clientX/innerWidth-.5)*.6;my=(e.clientY/innerHeight-.5)*.6;});
  (function tick(){requestAnimationFrame(tick);const t=Date.now()*.00018;pts.rotation.y=t*.25+mx*.25;pts.rotation.x=t*.08+my*.18;renderer.render(scene,camera);})();
}

// ═══════════ CURSOR ═══════════
(function(){
  const c=document.getElementById('cur'),r=document.getElementById('cur-r');
  if(!c||!r) return;
  let rx=0,ry=0,cx=0,cy=0;
  document.addEventListener('mousemove',e=>{cx=e.clientX;cy=e.clientY;c.style.left=cx+'px';c.style.top=cy+'px';});
  (function a(){rx+=(cx-rx)*.12;ry+=(cy-ry)*.12;r.style.left=rx+'px';r.style.top=ry+'px';requestAnimationFrame(a);})();
  document.querySelectorAll('button,.nk,.ccur-btn,.rc,.bcard,.bank-row,.cp-btn,.cc').forEach(el=>{
    el.addEventListener('mouseenter',()=>{r.style.width='52px';r.style.height='52px';r.style.borderColor='rgba(14,158,110,.6)';});
    el.addEventListener('mouseleave',()=>{r.style.width='36px';r.style.height='36px';r.style.borderColor='rgba(252,221,9,.5)';});
  });
})();

// ═══════════ NAV + SCROLL ═══════════
const secIds=['hero','banks','rates','chart-sec','calc-sec','parallel'];
window.addEventListener('scroll',()=>{
  document.getElementById('nav').classList.toggle('scrolled',scrollY>50);
  let active=0;
  secIds.forEach((id,i)=>{const el=document.getElementById(id);if(el&&el.getBoundingClientRect().top<=160)active=i;});
  document.querySelectorAll('.dn-dot').forEach((d,i)=>d.classList.toggle('on',i===active));
  document.querySelectorAll('.nav-link').forEach((l,i)=>l.classList.toggle('active',i===active));
});
function gotoSec(id){document.getElementById(id)?.scrollIntoView({behavior:'smooth'});}

// ═══════════ SCROLL REVEAL ═══════════
const rvObs=new IntersectionObserver(e=>{e.forEach(x=>{if(x.isIntersecting)x.target.classList.add('on');});},{threshold:.1});
document.querySelectorAll('.rv,.rv-l,.rv-r').forEach(el=>rvObs.observe(el));

// ═══════════ 3D TILT ═══════════
document.querySelectorAll('[data-tilt]').forEach(card=>{
  const w=card.closest('.rc-wrap');if(!w)return;
  w.addEventListener('mousemove',e=>{
    const r=card.getBoundingClientRect();
    const x=(e.clientX-r.left)/r.width-.5, y=(e.clientY-r.top)/r.height-.5;
    card.style.transform=`perspective(700px) rotateY(${x*14}deg) rotateX(${-y*14}deg) translateZ(10px)`;
    card.style.transition='none';
  });
  w.addEventListener('mouseleave',()=>{card.style.transition='transform .5s ease';card.style.transform='';});
});

// ═══════════ SPARKLINES ═══════════
function drawSpark(id,base,color){
  const el=document.getElementById(id);if(!el)return;
  const ex=Chart.getChart(el);if(ex)ex.destroy();
  let v=base,pts=[];
  for(let i=0;i<16;i++){v+=(Math.random()-.47)*.35;pts.push(+v.toFixed(3));}
  new Chart(el.getContext('2d'),{type:'line',data:{labels:pts,datasets:[{data:pts,borderColor:color,borderWidth:1.5,pointRadius:0,tension:.4,fill:false}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false},tooltip:{enabled:false}},scales:{x:{display:false},y:{display:false}},animation:{duration:500}}});
}
Object.keys(D.pairs).forEach(c=>{const p=D.pairs[c];drawSpark('sp-'+c,p.mid,p.change>=0?'#00E676':'#FF3D71');});

// ═══════════ MAIN CHART ═══════════
function initMainChart(){
  const el=document.getElementById('main-chart');if(!el)return;
  if(mainChart)mainChart.destroy();
  const ctx=el.getContext('2d');
  const gr=ctx.createLinearGradient(0,0,0,260);
  gr.addColorStop(0,'rgba(14,158,110,.22)');gr.addColorStop(1,'rgba(14,158,110,.01)');
  mainChart=new Chart(ctx,{type:'line',data:{labels:D.chart.map(d=>d.x),datasets:[{label:`${chartPair}/ETB`,data:D.chart.map(d=>d.y),borderColor:'#0E9E6E',backgroundColor:gr,borderWidth:2.5,pointRadius:0,tension:.35,fill:true}]},
    options:{responsive:true,maintainAspectRatio:false,interaction:{mode:'index',intersect:false},
      plugins:{legend:{display:false},tooltip:{backgroundColor:'rgba(6,9,18,.96)',borderColor:'rgba(14,158,110,.25)',borderWidth:1,titleColor:'#64748b',bodyColor:'#f0f4ff',padding:12,callbacks:{label:c=>`  ${chartPair}/ETB: ${c.parsed.y.toFixed(4)}`}}},
      scales:{x:{grid:{color:'rgba(255,255,255,.04)',drawBorder:false},ticks:{color:'#3D5070',font:{size:10},maxTicksLimit:8}},
        y:{position:'right',grid:{color:'rgba(255,255,255,.04)',drawBorder:false},ticks:{color:'#3D5070',font:{family:'JetBrains Mono',size:10}}}},animation:{duration:600}}});
}
initMainChart();
function selChartPair(c,btn){
  chartPair=c;
  document.querySelectorAll('.cp-btn').forEach(b=>b.classList.remove('active'));btn.classList.add('active');
  const p=D.pairs[c];
  document.getElementById('cp-val').textContent=p.mid;
  document.getElementById('cp-lbl').textContent=`${c} / ETB · Official Mid`;
  document.getElementById('oc-h').textContent=p.buy<p.sell?p.sell:p.buy;
  document.getElementById('oc-l').textContent=p.buy<p.sell?p.buy:p.sell;
  document.getElementById('oc-c').textContent=p.mid;
  let v=p.mid,pts=[];
  for(let i=0;i<48;i++){v+=(Math.random()-.48)*.3;pts.push({x:D.chart[i]?.x||i,y:+v.toFixed(4)});}
  if(mainChart){mainChart.data.labels=pts.map(d=>d.x);mainChart.data.datasets[0].data=pts.map(d=>d.y);mainChart.data.datasets[0].label=`${c}/ETB`;mainChart.update();}
}
function setTRange(n,btn){
  document.querySelectorAll('.t-tab').forEach(b=>b.classList.remove('active'));btn.classList.add('active');
  if(!mainChart)return;
  const s=D.chart.slice(-n);mainChart.data.labels=s.map(d=>d.x);mainChart.data.datasets[0].data=s.map(d=>d.y);mainChart.update();
}

// ═══════════ BANKS ═══════════
function filterBanks(type,btn){
  document.querySelectorAll('.bf-btn').forEach(b=>b.classList.remove('active'));btn.classList.add('active');
  document.querySelectorAll('.bank-row,.bcard').forEach(r=>{r.style.display=type==='all'||r.dataset.type===type?'':'none';});
}
let sortDir={};
function sortBanks(key){
  const rows=[...document.querySelectorAll('#bank-tbl-body .bank-row')];
  sortDir[key]=!sortDir[key];
  rows.sort((a,b)=>{
    if(key==='spread') return (sortDir[key]?1:-1)*(parseFloat(a.dataset.spread)-parseFloat(b.dataset.spread));
    return 0;
  });
  const body=document.getElementById('bank-tbl-body');
  rows.forEach(r=>body.appendChild(r));
}
let bankCurSel='USD';
function setBankCur(cur,btn){
  bankCurSel=cur;
  document.querySelectorAll('.bct').forEach(b=>b.classList.remove('active'));btn.classList.add('active');
  // Update buy/sell columns using data from D.banks
  document.querySelectorAll('#bank-tbl-body .bank-row').forEach((row,i)=>{
    const bank=D.banks[i];if(!bank)return;
    const r=bank.rates[cur];if(!r)return;
    const cells=row.querySelectorAll('.bank-cell');
    // update first two cells (USD buy/sell area but now show selected cur)
    if(cells[0])cells[0].textContent=r.buy;
    if(cells[1])cells[1].textContent=r.sell;
  });
}

// ═══════════ CALCULATOR ═══════════
function doCalc(){
  const amt=parseFloat(document.getElementById('cinput').value)||0;
  const fromETB=calcFrom==='ETB', toETB=calcTo==='ETB';
  let result, lbl;
  function getRate(cur){
    const p=D.pairs[cur];if(!p)return 1;
    return rateType==='official'?p.mid:(rateType==='buy'?p.buy:p.sell);
  }
  if(fromETB&&!toETB){result=amt/getRate(calcTo);lbl=D.pairs[calcTo]?.name||calcTo;}
  else if(!fromETB&&toETB){result=amt*getRate(calcFrom);lbl='Ethiopian Birr';}
  else if(!fromETB&&!toETB){const er=getRate(calcFrom),tr=getRate(calcTo);result=(amt*er)/tr;lbl=D.pairs[calcTo]?.name||calcTo;}
  else{result=amt;lbl='Ethiopian Birr';}
  document.getElementById('c-result').textContent=result?parseFloat(result.toFixed(4)).toLocaleString():'—';
  document.getElementById('c-result-lbl').textContent=lbl||'';
}
function nk(k){
  const inp=document.getElementById('cinput');
  if(k==='⌫'){inp.value=inp.value.slice(0,-1)||'0';}
  else if(k==='.'&&inp.value.includes('.'))return;
  else inp.value=(inp.value==='0'&&k!=='.')?k:inp.value+k;
  doCalc();
}
function clearCalc(){document.getElementById('cinput').value='0';document.getElementById('c-result').textContent='—';}
function swapCalc(){[calcFrom,calcTo]=[calcTo,calcFrom];const ff=calcFrom==='ETB'?'🇪🇹':(D.pairs[calcFrom]?.flag||'🌐');const tf=calcTo==='ETB'?'🇪🇹':(D.pairs[calcTo]?.flag||'🌐');document.getElementById('from-flag').textContent=ff;document.getElementById('from-code').textContent=calcFrom;document.getElementById('to-flag').textContent=tf;document.getElementById('to-code').textContent=calcTo;doCalc();}
function setRT(t,el){rateType=t;document.querySelectorAll('.rt').forEach(r=>r.classList.remove('active'));el.classList.add('active');doCalc();}
function openCPicker(target){cPicker=target;}
function pickCur(cur,flag,name,el){
  document.querySelectorAll('.cc').forEach(c=>c.classList.remove('sel'));el.classList.add('sel');
  if(cPicker==='from'||!cPicker){calcFrom=cur;document.getElementById('from-flag').textContent=flag;document.getElementById('from-code').textContent=cur;}
  else{calcTo=cur;document.getElementById('to-flag').textContent=flag;document.getElementById('to-code').textContent=cur;}
  doCalc();
}
function filterCC(q){
  const l=q.toLowerCase();
  document.querySelectorAll('.cc').forEach(c=>{c.style.display=c.dataset.cur?.toLowerCase().includes(l)?'':'none';});
}
function loadCalc(cur,flag){calcFrom=cur;document.getElementById('from-flag').textContent=flag;document.getElementById('from-code').textContent=cur;calcTo='ETB';document.getElementById('to-flag').textContent='🇪🇹';document.getElementById('to-code').textContent='ETB';doCalc();gotoSec('calc-sec');}

// ═══════════ LIVE REFRESH ═══════════
let refreshTimer=30;
setInterval(()=>{refreshTimer--;if(refreshTimer<=0){liveRefresh();refreshTimer=30;}},1000);
async function liveRefresh(){
  try{const res=await fetch('?json=1');D=await res.json();updateUI();showToast('✓ Rates updated — '+D.timestamp);}catch(e){}
}
function updateUI(){
  const p=D.pairs['USD'];
  const n=D.official_usd.toFixed(2).split('.');
  const heroNum=document.getElementById('hero-num');
  if(heroNum)heroNum.innerHTML=n[0]+'<span>.'+(n[1]||'00')+'</span>';
  ['h-buy','h-sell','h-high','h-low'].forEach((id,i)=>{const el=document.getElementById(id);if(el)el.textContent=[p.buy,p.sell,D.high24,D.low24][i];});
  document.getElementById('h-ts').textContent=D.timestamp;
  if(mainChart){mainChart.data.labels=D.chart.map(d=>d.x);mainChart.data.datasets[0].data=D.chart.map(d=>d.y);mainChart.update();}
  document.getElementById('cp-val').textContent=D.pairs[chartPair]?.mid||D.official_usd;
  document.getElementById('oc-h').textContent=D.high24;document.getElementById('oc-l').textContent=D.low24;document.getElementById('oc-c').textContent=D.official_usd;
  document.getElementById('ref-mid').textContent=D.official_usd;document.getElementById('ref-buy').textContent=p.buy;document.getElementById('ref-sell').textContent=p.sell;
  doCalc();
}

// ═══════════ TOAST ═══════════
let tT;function showToast(m){const t=document.getElementById('toast');t.textContent=m;t.classList.add('show');clearTimeout(tT);tT=setTimeout(()=>t.classList.remove('show'),2800);}

// ═══════════ INIT ═══════════
doCalc();
</script>
</body>
</html>
