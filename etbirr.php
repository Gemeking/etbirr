<?php
// ═══════════════════════════════════════════════════════════
//  ETBirr — Ethiopian Birr Black Market Exchange
//  Mobile App UI · Single PHP File
// ═══════════════════════════════════════════════════════════

function fetchLiveRates(): array {
    $etbOfficial = 57.50;
    $rates = [];

    $apis = [
        'https://api.exchangerate-api.com/v4/latest/USD',
        'https://open.er-api.com/v6/latest/USD',
    ];
    $ctx = stream_context_create([
        'http' => ['timeout' => 8, 'user_agent' => 'ETBirr-App/3.0'],
        'ssl'  => ['verify_peer' => false, 'verify_peer_name' => false],
    ]);
    foreach ($apis as $url) {
        $raw = @file_get_contents($url, false, $ctx);
        if (!$raw) continue;
        $d = json_decode($raw, true);
        $r = $d['rates'] ?? $d['conversion_rates'] ?? null;
        if ($r && isset($r['ETB'])) { $etbOfficial = (float)$r['ETB']; $rates = $r; break; }
    }

    $premium  = 1.235;
    $etbBlack = $etbOfficial * $premium;

    $currencies = [
        'USD'=>'🇺🇸','EUR'=>'🇪🇺','GBP'=>'🇬🇧','SAR'=>'🇸🇦',
        'AED'=>'🇦🇪','CNY'=>'🇨🇳','CHF'=>'🇨🇭','CAD'=>'🇨🇦',
        'JPY'=>'🇯🇵','KES'=>'🇰🇪','TRY'=>'🇹🇷','INR'=>'🇮🇳',
        'QAR'=>'🇶🇦','KWD'=>'🇰🇼','OMR'=>'🇴🇲','BHD'=>'🇧🇭',
    ];
    $names = [
        'USD'=>'US Dollar','EUR'=>'Euro','GBP'=>'British Pound','SAR'=>'Saudi Riyal',
        'AED'=>'UAE Dirham','CNY'=>'Chinese Yuan','CHF'=>'Swiss Franc','CAD'=>'Canadian Dollar',
        'JPY'=>'Japanese Yen','KES'=>'Kenyan Shilling','TRY'=>'Turkish Lira','INR'=>'Indian Rupee',
        'QAR'=>'Qatari Riyal','KWD'=>'Kuwaiti Dinar','OMR'=>'Omani Rial','BHD'=>'Bahraini Dinar',
    ];
    $fallback = [
        'USD'=>1,'EUR'=>0.92,'GBP'=>0.79,'SAR'=>3.75,'AED'=>3.67,
        'CNY'=>7.24,'CHF'=>0.89,'CAD'=>1.36,'JPY'=>149.5,'KES'=>130.0,
        'TRY'=>32.5,'INR'=>83.3,'QAR'=>3.64,'KWD'=>0.308,'OMR'=>0.385,'BHD'=>0.377,
    ];

    $pairs = [];
    foreach ($currencies as $cur => $flag) {
        $usdRate = ($rates[$cur] ?? 0) ?: ($fallback[$cur] ?? 1);
        $off   = round($etbOfficial / $usdRate, 4);
        $black = round($etbBlack   / $usdRate, 4);
        $chg   = round((mt_rand(-300, 300) / 100), 2);
        $pairs[$cur] = [
            'flag'=>$flag,'name'=>$names[$cur]??$cur,
            'official'=>$off,'black'=>$black,
            'premium'=>round(($black-$off)/$off*100,2),
            'change'=>$chg,
            'high'=>round($black*1.005,4),
            'low'=>round($black*0.995,4),
        ];
    }

    $baseRate = $pairs['USD']['black'];
    $chart = []; $prev = $baseRate - mt_rand(50,200)/100;
    for ($i=23;$i>=0;$i--) {
        $close = round($prev + (mt_rand(-120,120)/100), 2);
        $chart[] = ['x'=>date('H:i',strtotime("-{$i} hours")),'y'=>$close];
        $prev = $close;
    }

    return [
        'pairs'=>$pairs,'chart'=>$chart,
        'official_usd'=>round($etbOfficial,2),
        'black_usd'=>round($etbBlack,2),
        'premium_pct'=>round(($etbBlack-$etbOfficial)/$etbOfficial*100,2),
        'timestamp'=>date('g:i A').' · '.date('M j, Y'),
        'high24'=>round($baseRate*1.012,2),
        'low24'=>round($baseRate*0.988,2),
        'change24'=>round((mt_rand(-150,150)/100),2),
    ];
}

if (isset($_GET['json'])) {
    header('Content-Type: application/json');
    header('Cache-Control: no-store');
    echo json_encode(fetchLiveRates());
    exit;
}

$D = fetchLiveRates();
$isUp = $D['change24'] >= 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover,maximum-scale=1">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="theme-color" content="#060912">
<meta name="mobile-web-app-capable" content="yes">
<title>ETBirr</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@700;900&family=Inter:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<style>
*,::before,::after{box-sizing:border-box;margin:0;padding:0;-webkit-tap-highlight-color:transparent}
:root{
  --bg:#060912;--bg2:#0c1220;--card:#101928;--card2:#141f30;
  --border:#1a2540;--border2:#1e2d4a;
  --green:#00e676;--green-dim:rgba(0,230,118,.12);
  --red:#ff3d71;--red-dim:rgba(255,61,113,.12);
  --gold:#fbbf24;--gold-dim:rgba(251,191,36,.1);
  --blue:#3b82f6;--purple:#8b5cf6;
  --text:#f1f5f9;--text2:#94a3b8;--text3:#475569;
  --eth-g:#078930;--eth-y:#FCDD09;--eth-r:#DA121A;
  --safe-top:env(safe-area-inset-top,0px);
  --safe-bot:env(safe-area-inset-bottom,0px);
  --nav-h:68px;
  --r:20px;
}

html,body{
  height:100%;background:var(--bg);color:var(--text);
  font-family:'Inter',sans-serif;overflow:hidden;
  -webkit-font-smoothing:antialiased;
}

/* ══ SCREENS ══ */
#app{
  position:fixed;inset:0;
  display:flex;flex-direction:column;
  padding-top:var(--safe-top);
  padding-bottom:calc(var(--nav-h) + var(--safe-bot));
}
.screen{
  position:absolute;inset:0;
  padding-bottom:calc(var(--nav-h) + var(--safe-bot));
  padding-top:var(--safe-top);
  overflow-y:auto;overflow-x:hidden;
  -webkit-overflow-scrolling:touch;
  opacity:0;pointer-events:none;
  transform:translateY(16px);
  transition:opacity .25s,transform .25s;
}
.screen.active{opacity:1;pointer-events:all;transform:translateY(0)}

/* ══ STATUS BAR ══ */
.status-bar{
  position:fixed;top:0;left:0;right:0;
  height:calc(44px + var(--safe-top));
  padding-top:var(--safe-top);
  display:flex;align-items:center;justify-content:space-between;
  padding-left:20px;padding-right:20px;
  background:linear-gradient(to bottom,rgba(6,9,18,.98),transparent);
  z-index:50;pointer-events:none;
}
.sb-left{font-size:13px;font-weight:600;color:var(--text2)}
.sb-icons{display:flex;align-items:center;gap:6px}
.sb-icons svg{color:var(--text)}
.live-pill{
  display:flex;align-items:center;gap:5px;
  background:rgba(0,230,118,.12);border:1px solid rgba(0,230,118,.25);
  padding:3px 10px;border-radius:20px;
  font-size:11px;color:var(--green);font-weight:600;letter-spacing:.5px;
  pointer-events:all;
}
.live-dot{width:6px;height:6px;border-radius:50%;background:var(--green);
  box-shadow:0 0 8px var(--green);animation:blink 1.4s infinite}
@keyframes blink{0%,100%{opacity:1}50%{opacity:.2}}

/* ══ APP HEADER (per screen) ══ */
.app-header{
  padding:58px 20px 16px;
  position:sticky;top:0;z-index:40;
  background:linear-gradient(to bottom,var(--bg) 70%,transparent);
}
.app-header.no-sticky{position:relative;padding-top:56px}
.hdr-row{display:flex;align-items:center;justify-content:space-between}
.hdr-title{font-family:'Orbitron',sans-serif;font-size:13px;font-weight:700;
  letter-spacing:2px;color:var(--text2);text-transform:uppercase}
.hdr-logo{
  display:flex;align-items:center;gap:10px;
}
.hdr-logo-mark{
  width:34px;height:34px;border-radius:10px;
  background:linear-gradient(135deg,var(--eth-g),var(--eth-y));
  display:flex;align-items:center;justify-content:center;
  font-size:16px;font-weight:900;color:#000;font-family:'Orbitron',sans-serif;
  box-shadow:0 0 16px rgba(252,221,9,.3);
}
.hdr-logo-text{font-family:'Orbitron',sans-serif;font-size:18px;font-weight:900;
  background:linear-gradient(90deg,var(--eth-g),var(--eth-y));
  -webkit-background-clip:text;-webkit-text-fill-color:transparent}
.icon-btn{
  width:38px;height:38px;border-radius:12px;
  background:var(--card);border:1px solid var(--border);
  display:flex;align-items:center;justify-content:center;
  cursor:pointer;transition:.2s;
}
.icon-btn:active{transform:scale(.93)}

/* ══ BOTTOM NAV ══ */
.bottom-nav{
  position:fixed;bottom:0;left:0;right:0;
  height:calc(var(--nav-h) + var(--safe-bot));
  padding-bottom:var(--safe-bot);
  background:rgba(10,15,28,.96);
  backdrop-filter:blur(20px);-webkit-backdrop-filter:blur(20px);
  border-top:1px solid var(--border);
  display:flex;align-items:flex-start;justify-content:space-around;
  padding-top:8px;z-index:100;
}
.nav-item{
  display:flex;flex-direction:column;align-items:center;gap:4px;
  cursor:pointer;padding:4px 16px;border-radius:14px;
  transition:all .2s;flex:1;min-width:0;
  background:transparent;border:none;
}
.nav-item:active{transform:scale(.9)}
.nav-icon{width:24px;height:24px;transition:.2s}
.nav-label{font-size:10px;font-weight:600;letter-spacing:.3px;color:var(--text3);transition:.2s;text-align:center}
.nav-item.active .nav-label{color:var(--eth-y)}
.nav-item.active .nav-icon{filter:drop-shadow(0 0 6px rgba(252,221,9,.5))}
.nav-indicator{
  position:absolute;top:0;height:2px;background:var(--eth-y);
  border-radius:0 0 2px 2px;transition:left .3s cubic-bezier(.34,1.56,.64,1);
  width:40px;
}

/* ══ HOME SCREEN ══ */
.hero-card{
  margin:0 16px 16px;
  background:linear-gradient(135deg,#0d1e3a 0%,#0a1628 50%,#071020 100%);
  border:1px solid var(--border2);border-radius:24px;padding:24px;
  position:relative;overflow:hidden;
}
.hero-card::before{
  content:'';position:absolute;top:-40px;right:-40px;
  width:180px;height:180px;border-radius:50%;
  background:radial-gradient(circle,rgba(252,221,9,.08),transparent 70%);
}
.hero-card::after{
  content:'';position:absolute;bottom:-30px;left:-30px;
  width:130px;height:130px;border-radius:50%;
  background:radial-gradient(circle,rgba(7,137,48,.08),transparent 70%);
}
.hero-label{font-size:11px;color:var(--text2);letter-spacing:1px;text-transform:uppercase;margin-bottom:6px}
.hero-pair{font-size:13px;color:var(--text3);margin-bottom:4px;font-weight:500}
.hero-rate{
  font-family:'Orbitron',sans-serif;font-size:48px;font-weight:900;
  line-height:1;margin-bottom:6px;
  background:linear-gradient(135deg,#fff,var(--gold));
  -webkit-background-clip:text;-webkit-text-fill-color:transparent;
}
.hero-change{
  display:inline-flex;align-items:center;gap:5px;
  padding:5px 12px;border-radius:20px;font-size:13px;font-weight:700;
  margin-bottom:20px;
}
.hero-change.up{background:var(--green-dim);color:var(--green)}
.hero-change.dn{background:var(--red-dim);color:var(--red)}

.hero-mini-chart{height:70px;margin-bottom:16px}

.hero-stats{display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px}
.hstat{
  background:rgba(255,255,255,.04);border:1px solid var(--border);
  border-radius:14px;padding:12px 10px;text-align:center;
}
.hstat-l{font-size:10px;color:var(--text3);text-transform:uppercase;letter-spacing:.5px;margin-bottom:4px}
.hstat-v{font-family:'JetBrains Mono',monospace;font-size:14px;font-weight:700}

/* banner */
.eth-banner{
  margin:0 16px 20px;
  border-radius:18px;padding:16px 18px;
  background:linear-gradient(135deg,
    rgba(7,137,48,.15),rgba(252,221,9,.1),rgba(218,18,26,.12));
  border:1px solid rgba(252,221,9,.2);
  display:flex;align-items:center;gap:14px;
}
.eth-flag{display:flex;height:28px;width:42px;border-radius:6px;overflow:hidden;flex-shrink:0;
  box-shadow:0 2px 12px rgba(0,0,0,.4)}
.eth-flag span{flex:1}
.banner-text h3{font-size:13px;font-weight:700;margin-bottom:2px}
.banner-text p{font-size:11px;color:var(--text2);line-height:1.4}
.prem-badge{
  margin-left:auto;flex-shrink:0;
  font-family:'JetBrains Mono',monospace;font-size:15px;font-weight:700;
  color:var(--gold);background:var(--gold-dim);border:1px solid rgba(251,191,36,.25);
  padding:6px 12px;border-radius:12px;
}

/* quick pairs */
.section-head{
  display:flex;align-items:center;justify-content:space-between;
  padding:0 20px;margin-bottom:12px;
}
.sec-title{font-size:15px;font-weight:700;letter-spacing:.3px}
.sec-link{font-size:12px;color:var(--blue);font-weight:600}

.quick-pairs{display:flex;gap:10px;padding:0 16px;overflow-x:auto;
  scrollbar-width:none;-ms-overflow-style:none;margin-bottom:24px}
.quick-pairs::-webkit-scrollbar{display:none}
.qpair{
  flex-shrink:0;background:var(--card);border:1px solid var(--border);
  border-radius:18px;padding:14px 16px;min-width:130px;cursor:pointer;
  transition:.2s;
}
.qpair:active{transform:scale(.96)}
.qp-top{display:flex;align-items:center;justify-content:space-between;margin-bottom:10px}
.qp-flag{font-size:22px}
.qp-chg{font-size:11px;font-weight:700;padding:3px 7px;border-radius:8px}
.qp-chg.up{background:var(--green-dim);color:var(--green)}
.qp-chg.dn{background:var(--red-dim);color:var(--red)}
.qp-cur{font-size:12px;color:var(--text2);margin-bottom:2px}
.qp-rate{font-family:'JetBrains Mono',monospace;font-size:17px;font-weight:700}
.qp-spark{height:30px;margin-top:8px}

/* ══ MARKETS SCREEN ══ */
.search-bar{
  margin:0 16px 16px;
  background:var(--card);border:1px solid var(--border);
  border-radius:16px;padding:12px 16px;
  display:flex;align-items:center;gap:10px;
}
.search-bar input{
  flex:1;background:none;border:none;outline:none;
  color:var(--text);font-size:15px;font-family:'Inter',sans-serif;
}
.search-bar input::placeholder{color:var(--text3)}

.mkt-filter{display:flex;gap:8px;padding:0 16px;margin-bottom:16px;overflow-x:auto;scrollbar-width:none}
.mkt-filter::-webkit-scrollbar{display:none}
.filter-chip{
  flex-shrink:0;padding:7px 16px;border-radius:20px;font-size:12px;font-weight:600;
  border:1px solid var(--border);color:var(--text2);background:var(--card);cursor:pointer;
  transition:.2s;
}
.filter-chip.active{background:rgba(252,221,9,.12);border-color:rgba(252,221,9,.35);color:var(--gold)}
.filter-chip:active{transform:scale(.95)}

.pair-list{padding:0 16px;display:flex;flex-direction:column;gap:10px}
.pair-row{
  background:var(--card);border:1px solid var(--border);
  border-radius:18px;padding:14px 16px;
  display:flex;align-items:center;gap:14px;cursor:pointer;
  transition:.2s;position:relative;overflow:hidden;
}
.pair-row:active{transform:scale(.985)}
.pair-row::before{
  content:'';position:absolute;left:0;top:0;bottom:0;width:3px;
  border-radius:3px 0 0 3px;
}
.pair-row.up::before{background:var(--green)}
.pair-row.dn::before{background:var(--red)}
.pr-flag{font-size:28px;flex-shrink:0;width:42px;text-align:center}
.pr-info{flex:1;min-width:0}
.pr-cur{font-size:14px;font-weight:700;letter-spacing:.3px}
.pr-name{font-size:11px;color:var(--text3);margin-top:1px}
.pr-spark{width:60px;height:32px;flex-shrink:0}
.pr-right{text-align:right;flex-shrink:0}
.pr-rate{font-family:'JetBrains Mono',monospace;font-size:15px;font-weight:700}
.pr-off{font-size:10px;color:var(--text3);margin-top:2px}
.pr-chg{font-size:12px;font-weight:700;margin-top:3px}
.pr-chg.up{color:var(--green)}
.pr-chg.dn{color:var(--red)}
.pr-prem{
  font-size:10px;padding:2px 7px;border-radius:6px;margin-top:4px;
  background:var(--gold-dim);color:var(--gold);font-weight:600;display:inline-block;
}

/* ══ CHART SCREEN ══ */
.chart-selector{
  display:flex;gap:8px;padding:0 16px;margin-bottom:16px;overflow-x:auto;scrollbar-width:none;
}
.chart-selector::-webkit-scrollbar{display:none}
.csel-item{
  flex-shrink:0;display:flex;align-items:center;gap:7px;
  padding:10px 14px;border-radius:16px;border:1px solid var(--border);
  background:var(--card);cursor:pointer;transition:.2s;
}
.csel-item.active{border-color:rgba(252,221,9,.4);background:rgba(252,221,9,.08)}
.csel-item:active{transform:scale(.95)}
.csel-flag{font-size:18px}
.csel-info{display:flex;flex-direction:column}
.csel-cur{font-size:12px;font-weight:700;letter-spacing:.3px}
.csel-rate{font-size:10px;color:var(--text3);font-family:'JetBrains Mono',monospace}

.chart-price-card{
  margin:0 16px 16px;
  background:var(--card);border:1px solid var(--border);
  border-radius:22px;padding:20px;
}
.cpc-label{font-size:11px;color:var(--text2);letter-spacing:1px;text-transform:uppercase;margin-bottom:4px}
.cpc-rate{
  font-family:'Orbitron',sans-serif;font-size:38px;font-weight:900;
  color:#fff;margin-bottom:4px;transition:.3s;
}
.cpc-sub{font-size:12px;color:var(--text3)}

.time-tabs{display:flex;background:var(--card2);border-radius:14px;padding:4px;margin:0 16px 16px}
.ttab{flex:1;text-align:center;padding:8px;font-size:12px;font-weight:600;
  border-radius:10px;cursor:pointer;color:var(--text3);transition:.2s;border:none;background:none}
.ttab.active{background:var(--border2);color:var(--text)}

.main-chart-wrap{margin:0 16px 16px;background:var(--card);
  border:1px solid var(--border);border-radius:22px;padding:16px;height:220px}

.ohlc-grid{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin:0 16px 20px}
.ohlc-box{background:var(--card);border:1px solid var(--border);border-radius:16px;padding:14px}
.ohlc-l{font-size:10px;color:var(--text3);text-transform:uppercase;letter-spacing:.5px;margin-bottom:4px}
.ohlc-v{font-family:'JetBrains Mono',monospace;font-size:17px;font-weight:700}

/* ══ CONVERT SCREEN ══ */
.convert-card{
  margin:0 16px 16px;
  background:var(--card);border:1px solid var(--border);border-radius:24px;padding:20px;
}
.conv-label{font-size:11px;color:var(--text2);text-transform:uppercase;letter-spacing:1px;margin-bottom:8px}
.conv-row{
  display:flex;align-items:center;gap:12px;
  background:var(--bg);border:1px solid var(--border);
  border-radius:16px;padding:14px 16px;margin-bottom:14px;
}
.conv-flag{font-size:24px}
.conv-cur{font-size:14px;font-weight:700}
.conv-input{
  flex:1;text-align:right;background:none;border:none;outline:none;
  font-family:'JetBrains Mono',monospace;font-size:22px;font-weight:700;
  color:var(--text);
}
.conv-input::placeholder{color:var(--text3)}
.swap-btn{
  width:44px;height:44px;border-radius:14px;
  background:linear-gradient(135deg,rgba(7,137,48,.2),rgba(252,221,9,.1));
  border:1px solid rgba(252,221,9,.2);
  display:flex;align-items:center;justify-content:center;
  cursor:pointer;margin:0 auto 14px;transition:.2s;
}
.swap-btn:active{transform:scale(.9) rotate(180deg)}
.conv-result{
  background:linear-gradient(135deg,rgba(0,230,118,.06),rgba(251,191,36,.04));
  border:1px solid rgba(0,230,118,.15);border-radius:16px;padding:16px;
  text-align:center;
}
.conv-result-val{
  font-family:'Orbitron',sans-serif;font-size:30px;font-weight:900;
  color:var(--green);text-shadow:0 0 20px rgba(0,230,118,.3);
}
.conv-result-label{font-size:12px;color:var(--text2);margin-top:4px}
.rate-type-row{display:flex;gap:8px;margin-top:14px}
.rtype{flex:1;padding:10px;border-radius:12px;text-align:center;cursor:pointer;
  border:1px solid var(--border);background:var(--bg);font-size:12px;font-weight:600;
  color:var(--text2);transition:.2s}
.rtype.active{border-color:rgba(252,221,9,.35);background:rgba(252,221,9,.08);color:var(--gold)}

.cur-select{
  display:grid;grid-template-columns:repeat(4,1fr);gap:8px;
  margin:0 16px 20px;
}
.cur-chip{
  display:flex;flex-direction:column;align-items:center;gap:4px;
  background:var(--card);border:1px solid var(--border);border-radius:14px;
  padding:10px 6px;cursor:pointer;transition:.2s;
}
.cur-chip:active{transform:scale(.93)}
.cur-chip.sel{border-color:rgba(251,191,36,.4);background:rgba(251,191,36,.08)}
.cur-chip-flag{font-size:20px}
.cur-chip-code{font-size:10px;font-weight:700;color:var(--text2)}

/* ══ MODALS / BOTTOM SHEET ══ */
.sheet-backdrop{
  position:fixed;inset:0;background:rgba(0,0,0,.7);
  z-index:200;opacity:0;pointer-events:none;transition:opacity .3s;
}
.sheet-backdrop.open{opacity:1;pointer-events:all}
.bottom-sheet{
  position:fixed;bottom:0;left:0;right:0;
  background:var(--bg2);border-radius:24px 24px 0 0;
  border-top:1px solid var(--border);
  z-index:201;
  transform:translateY(100%);transition:transform .35s cubic-bezier(.32,0,.67,0);
  padding-bottom:calc(20px + var(--safe-bot));
  max-height:85vh;overflow-y:auto;
}
.bottom-sheet.open{transform:translateY(0);transition-timing-function:cubic-bezier(.33,1,.68,1)}
.sheet-handle{width:40px;height:4px;border-radius:2px;background:var(--border2);
  margin:12px auto 20px;flex-shrink:0}
.sheet-head{padding:0 20px 16px;border-bottom:1px solid var(--border);margin-bottom:16px}
.sheet-pair{font-family:'Orbitron',sans-serif;font-size:20px;font-weight:900;letter-spacing:1px}
.sheet-rate{font-size:14px;color:var(--text2);margin-top:4px}
.sheet-grid{display:grid;grid-template-columns:1fr 1fr;gap:10px;padding:0 16px;margin-bottom:16px}
.sg-box{background:var(--card);border:1px solid var(--border);border-radius:16px;padding:14px}
.sg-l{font-size:10px;color:var(--text3);text-transform:uppercase;letter-spacing:.5px;margin-bottom:4px}
.sg-v{font-family:'JetBrains Mono',monospace;font-size:18px;font-weight:700}
.sheet-close-btn{
  margin:0 16px;padding:16px;border-radius:16px;
  background:var(--card);border:1px solid var(--border);
  color:var(--text2);font-size:14px;font-weight:600;
  cursor:pointer;width:calc(100% - 32px);text-align:center;transition:.2s;
}
.sheet-close-btn:active{transform:scale(.97)}

/* ══ SCROLLBARS ══ */
::-webkit-scrollbar{width:0;height:0}

/* ══ PULL TO REFRESH ══ */
.ptr-indicator{
  text-align:center;padding:12px;font-size:12px;color:var(--text3);
  display:none;
}

/* ══ TOAST ══ */
.toast{
  position:fixed;bottom:calc(var(--nav-h) + var(--safe-bot) + 16px);
  left:50%;transform:translateX(-50%) translateY(20px);
  background:var(--card);border:1px solid var(--border2);
  border-radius:20px;padding:12px 20px;
  font-size:13px;font-weight:500;color:var(--text);
  z-index:300;opacity:0;transition:.3s;white-space:nowrap;
  box-shadow:0 8px 32px rgba(0,0,0,.5);
}
.toast.show{opacity:1;transform:translateX(-50%) translateY(0)}

/* ══ REFRESH SPIN ══ */
@keyframes spin{to{transform:rotate(360deg)}}
.spinning{animation:spin .7s linear infinite}
@keyframes fadeUp{from{opacity:0;transform:translateY(20px)}to{opacity:1;transform:translateY(0)}}
.fade-up{animation:fadeUp .4s ease both}

/* ══ NUMBER FLASH ══ */
@keyframes flashGreen{0%{color:var(--green)}100%{color:inherit}}
@keyframes flashRed{0%{color:var(--red)}100%{color:inherit}}
.flash-g{animation:flashGreen .5s}
.flash-r{animation:flashRed .5s}
</style>
</head>
<body>

<div id="app">

<!-- ══════════ STATUS BAR ══════════ -->
<div class="status-bar">
  <div class="sb-left" id="sb-time"><?= date('g:i A') ?></div>
  <div class="live-pill">
    <div class="live-dot"></div>
    LIVE
  </div>
  <div class="sb-icons">
    <svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24"><path d="M1 6l5 4 4-4 4 4 5-4v13H1z" opacity=".3"/><path d="M1 6l5 4 4-4 4 4 5-4" stroke="currentColor" stroke-width="2" fill="none"/></svg>
    <svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24"><rect x="2" y="7" width="18" height="11" rx="2" stroke="currentColor" stroke-width="1.5" fill="none"/><rect x="20" y="10" width="2" height="5" rx="1"/><rect x="4" y="9" width="8" height="7" rx="1" opacity=".6"/></svg>
  </div>
</div>

<!-- ════════════════════════════════════
     SCREEN 1 — HOME
════════════════════════════════════ -->
<div class="screen active" id="screen-home">
  <div class="app-header no-sticky">
    <div class="hdr-row">
      <div class="hdr-logo">
        <div class="hdr-logo-mark">₿</div>
        <div class="hdr-logo-text">ETBirr</div>
      </div>
      <div style="display:flex;gap:8px">
        <div class="icon-btn" onclick="manualRefresh(this)">
          <svg id="refresh-home" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/></svg>
        </div>
      </div>
    </div>
  </div>

  <!-- HERO CARD -->
  <div class="hero-card fade-up">
    <div class="hero-label">Ethiopian Birr · Parallel Market</div>
    <div class="hero-pair">USD / ETB</div>
    <div class="hero-rate" id="hero-rate"><?= $D['black_usd'] ?></div>
    <div class="hero-change <?= $isUp?'up':'dn' ?>" id="hero-change">
      <span><?= $isUp?'▲':'▼' ?></span>
      <span><?= abs($D['change24']) ?>%</span>
      <span style="font-weight:400;opacity:.7">24h</span>
    </div>
    <div class="hero-mini-chart"><canvas id="heroChart"></canvas></div>
    <div class="hero-stats">
      <div class="hstat">
        <div class="hstat-l">Official</div>
        <div class="hstat-v" style="color:var(--text2)" id="h-off"><?= $D['official_usd'] ?></div>
      </div>
      <div class="hstat">
        <div class="hstat-l">24h High</div>
        <div class="hstat-v" style="color:var(--green)" id="h-high"><?= $D['high24'] ?></div>
      </div>
      <div class="hstat">
        <div class="hstat-l">24h Low</div>
        <div class="hstat-v" style="color:var(--red)" id="h-low"><?= $D['low24'] ?></div>
      </div>
    </div>
  </div>

  <!-- ETH BANNER -->
  <div class="eth-banner fade-up">
    <div class="eth-flag">
      <span style="background:#078930"></span>
      <span style="background:#FCDD09"></span>
      <span style="background:#DA121A"></span>
    </div>
    <div class="banner-text">
      <h3>Black Market Premium</h3>
      <p>Over official NBE rate · Parallel exchange</p>
    </div>
    <div class="prem-badge" id="h-prem">+<?= $D['premium_pct'] ?>%</div>
  </div>

  <!-- QUICK PAIRS -->
  <div class="section-head fade-up">
    <div class="sec-title">Top Pairs</div>
    <div class="sec-link" onclick="switchTab(1)">See all →</div>
  </div>
  <div class="quick-pairs" id="quick-pairs">
    <?php $topCurs = ['USD','EUR','GBP','SAR','AED','CNY']; foreach ($topCurs as $c): $p=$D['pairs'][$c]; $cls=$p['change']>=0?'up':'dn'; $sign=$p['change']>=0?'▲':'▼'; ?>
    <div class="qpair" onclick="openSheet('<?= $c ?>')">
      <div class="qp-top">
        <div class="qp-flag"><?= $p['flag'] ?></div>
        <div class="qp-chg <?= $cls ?>"><?= $sign ?><?= abs($p['change']) ?>%</div>
      </div>
      <div class="qp-cur"><?= $c ?>/ETB</div>
      <div class="qp-rate" data-qrate="<?= $c ?>"><?= $p['black'] ?></div>
      <div class="qp-spark"><canvas id="qs-<?= $c ?>"></canvas></div>
    </div>
    <?php endforeach; ?>
  </div>

  <div style="height:8px"></div>

  <!-- LAST UPDATE -->
  <div style="text-align:center;padding:0 20px 16px;font-size:11px;color:var(--text3)">
    Last updated: <span id="h-ts"><?= $D['timestamp'] ?></span>
    · Auto-refreshes every 30s
  </div>
</div>

<!-- ════════════════════════════════════
     SCREEN 2 — MARKETS
════════════════════════════════════ -->
<div class="screen" id="screen-markets">
  <div class="app-header no-sticky">
    <div class="hdr-row">
      <div class="hdr-title">Markets</div>
      <div class="icon-btn" onclick="manualRefresh(this)">
        <svg id="refresh-mkt" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/></svg>
      </div>
    </div>
  </div>

  <div class="search-bar">
    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="color:var(--text3);flex-shrink:0"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
    <input type="text" id="search-input" placeholder="Search currencies…" oninput="filterPairs(this.value)">
  </div>

  <div class="mkt-filter">
    <div class="filter-chip active" onclick="setFilter('all',this)">All</div>
    <div class="filter-chip" onclick="setFilter('gulf',this)">Gulf</div>
    <div class="filter-chip" onclick="setFilter('europe',this)">Europe</div>
    <div class="filter-chip" onclick="setFilter('asia',this)">Asia</div>
    <div class="filter-chip" onclick="setFilter('africa',this)">Africa</div>
  </div>

  <div class="pair-list" id="pair-list">
    <?php foreach ($D['pairs'] as $cur => $p):
      $cls = $p['change'] >= 0 ? 'up' : 'dn';
      $sign = $p['change'] >= 0 ? '+' : '';
      $groups = ['SAR'=>'gulf','AED'=>'gulf','QAR'=>'gulf','KWD'=>'gulf','OMR'=>'gulf','BHD'=>'gulf',
                 'EUR'=>'europe','GBP'=>'europe','CHF'=>'europe','TRY'=>'europe',
                 'CNY'=>'asia','JPY'=>'asia','INR'=>'asia',
                 'KES'=>'africa','DJF'=>'africa','SDG'=>'africa'];
      $grp = $groups[$cur] ?? 'other';
    ?>
    <div class="pair-row <?= $cls ?> fade-up" data-cur="<?= $cur ?>" data-group="<?= $grp ?>" onclick="openSheet('<?= $cur ?>')">
      <div class="pr-flag"><?= $p['flag'] ?></div>
      <div class="pr-info">
        <div class="pr-cur"><?= $cur ?>/ETB</div>
        <div class="pr-name"><?= $p['name'] ?></div>
        <div class="pr-prem">+<?= $p['premium'] ?>% black mkt</div>
      </div>
      <div class="pr-spark"><canvas id="ms-<?= $cur ?>"></canvas></div>
      <div class="pr-right">
        <div class="pr-rate" data-mrate="<?= $cur ?>"><?= $p['black'] ?></div>
        <div class="pr-off">Off: <?= $p['official'] ?></div>
        <div class="pr-chg <?= $cls ?>"><?= $sign . $p['change'] ?>%</div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <div style="height:16px"></div>
</div>

<!-- ════════════════════════════════════
     SCREEN 3 — CHART
════════════════════════════════════ -->
<div class="screen" id="screen-chart">
  <div class="app-header no-sticky">
    <div class="hdr-row">
      <div class="hdr-title">Live Chart</div>
      <div class="icon-btn">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
      </div>
    </div>
  </div>

  <div class="chart-selector" id="chart-selector">
    <?php $i=0; foreach (array_slice($D['pairs'],0,6,true) as $cur=>$p): $i++; ?>
    <div class="csel-item <?= $i===1?'active':'' ?>" onclick="selectChartPair('<?= $cur ?>',this)">
      <div class="csel-flag"><?= $p['flag'] ?></div>
      <div class="csel-info">
        <div class="csel-cur"><?= $cur ?></div>
        <div class="csel-rate" id="cs-<?= $cur ?>"><?= $p['black'] ?></div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <div class="chart-price-card">
    <div class="cpc-label" id="cpc-label">USD / ETB · Black Market</div>
    <div class="cpc-rate" id="cpc-rate"><?= $D['black_usd'] ?></div>
    <div class="cpc-sub">Ethiopian Birr per unit · Parallel Market Rate</div>
  </div>

  <div class="time-tabs">
    <button class="ttab active" onclick="setTimeRange(24,this)">24H</button>
    <button class="ttab" onclick="setTimeRange(12,this)">12H</button>
    <button class="ttab" onclick="setTimeRange(6,this)">6H</button>
    <button class="ttab" onclick="setTimeRange(3,this)">3H</button>
  </div>

  <div class="main-chart-wrap">
    <canvas id="mainChartFull"></canvas>
  </div>

  <div class="ohlc-grid">
    <div class="ohlc-box">
      <div class="ohlc-l">Open</div>
      <div class="ohlc-v" id="oc-open"><?= $D['candles'][0]['o'] ?? $D['black_usd'] ?></div>
    </div>
    <div class="ohlc-box">
      <div class="ohlc-l">24h High</div>
      <div class="ohlc-v" style="color:var(--green)" id="oc-high"><?= $D['high24'] ?></div>
    </div>
    <div class="ohlc-box">
      <div class="ohlc-l">24h Low</div>
      <div class="ohlc-v" style="color:var(--red)" id="oc-low"><?= $D['low24'] ?></div>
    </div>
    <div class="ohlc-box">
      <div class="ohlc-l">Premium</div>
      <div class="ohlc-v" style="color:var(--gold)" id="oc-prem">+<?= $D['premium_pct'] ?>%</div>
    </div>
  </div>
</div>

<!-- ════════════════════════════════════
     SCREEN 4 — CONVERT
════════════════════════════════════ -->
<div class="screen" id="screen-convert">
  <div class="app-header no-sticky">
    <div class="hdr-row">
      <div class="hdr-title">Convert</div>
      <div style="font-size:11px;color:var(--text3)">Black Market Rate</div>
    </div>
  </div>

  <div class="convert-card fade-up">
    <div class="conv-label">From</div>
    <div class="conv-row">
      <div class="conv-flag" id="from-flag">🇺🇸</div>
      <div class="conv-cur" id="from-cur">USD</div>
      <input class="conv-input" id="conv-input" type="number" placeholder="0.00" value="1" oninput="doConvert()">
    </div>

    <div class="swap-btn" onclick="swapConvert()">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="var(--gold)" stroke-width="2.5"><polyline points="17 1 21 5 17 9"/><path d="M3 11V9a4 4 0 0 1 4-4h14M7 23l-4-4 4-4"/><path d="M21 13v2a4 4 0 0 1-4 4H3"/></svg>
    </div>

    <div class="conv-label">To</div>
    <div class="conv-row">
      <div class="conv-flag">🇪🇹</div>
      <div class="conv-cur">ETB</div>
      <div style="flex:1;text-align:right;font-family:'JetBrains Mono',monospace;font-size:22px;font-weight:700;color:var(--text3)" id="conv-to-val">—</div>
    </div>

    <div class="rate-type-row">
      <div class="rtype active" onclick="setRateType('black',this)">Black Market</div>
      <div class="rtype" onclick="setRateType('official',this)">Official NBE</div>
    </div>

    <div class="conv-result" style="margin-top:14px" id="conv-result">
      <div class="conv-result-val" id="conv-result-val">—</div>
      <div class="conv-result-label" id="conv-result-label">Ethiopian Birr</div>
    </div>
  </div>

  <div class="section-head">
    <div class="sec-title">Select Currency</div>
  </div>
  <div class="cur-select" id="cur-select">
    <?php foreach ($D['pairs'] as $cur=>$p): ?>
    <div class="cur-chip <?= $cur==='USD'?'sel':'' ?>" onclick="selectConvCur('<?= $cur ?>','<?= $p['flag'] ?>')" data-cur="<?= $cur ?>">
      <div class="cur-chip-flag"><?= $p['flag'] ?></div>
      <div class="cur-chip-code"><?= $cur ?></div>
    </div>
    <?php endforeach; ?>
  </div>

  <div style="padding:0 16px;margin-bottom:16px">
    <div style="background:var(--card);border:1px solid var(--border);border-radius:16px;padding:14px 16px">
      <div style="font-size:11px;color:var(--text3);margin-bottom:8px;text-transform:uppercase;letter-spacing:.5px">Rate Reference</div>
      <div style="display:flex;justify-content:space-between;font-size:13px;margin-bottom:6px">
        <span style="color:var(--text2)">Official NBE</span>
        <span style="font-family:'JetBrains Mono',monospace;font-weight:600" id="ref-off"><?= $D['official_usd'] ?> ETB/USD</span>
      </div>
      <div style="display:flex;justify-content:space-between;font-size:13px">
        <span style="color:var(--text2)">Black Market</span>
        <span style="font-family:'JetBrains Mono',monospace;font-weight:700;color:var(--gold)" id="ref-black"><?= $D['black_usd'] ?> ETB/USD</span>
      </div>
    </div>
  </div>

  <div style="padding:0 16px 16px;font-size:11px;color:var(--text3);text-align:center;line-height:1.6">
    ⚠️ For informational use only. Parallel market rates. Verify locally.
  </div>
</div>

<!-- ════════════════════════════════════
     BOTTOM NAV
════════════════════════════════════ -->
<div class="bottom-nav">
  <div class="nav-indicator" id="nav-indicator" style="left:calc(12.5% - 20px)"></div>

  <button class="nav-item active" id="nav-0" onclick="switchTab(0)">
    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
      <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
      <polyline points="9 22 9 12 15 12 15 22"/>
    </svg>
    <div class="nav-label">Home</div>
  </button>

  <button class="nav-item" id="nav-1" onclick="switchTab(1)">
    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
      <line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/>
      <line x1="6" y1="20" x2="6" y2="14"/>
    </svg>
    <div class="nav-label">Markets</div>
  </button>

  <button class="nav-item" id="nav-2" onclick="switchTab(2)">
    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
      <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>
    </svg>
    <div class="nav-label">Chart</div>
  </button>

  <button class="nav-item" id="nav-3" onclick="switchTab(3)">
    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
      <circle cx="12" cy="12" r="10"/>
      <line x1="12" y1="8" x2="12" y2="12"/>
      <line x1="12" y1="16" x2="12.01" y2="16"/>
    </svg>
    <div class="nav-label">Convert</div>
  </button>
</div>

</div><!-- /app -->

<!-- ════════════════════════════════════
     BOTTOM SHEET
════════════════════════════════════ -->
<div class="sheet-backdrop" id="sheet-backdrop" onclick="closeSheet()"></div>
<div class="bottom-sheet" id="bottom-sheet">
  <div class="sheet-handle"></div>
  <div class="sheet-head">
    <div style="display:flex;align-items:center;gap:12px">
      <div style="font-size:32px" id="sheet-flag">🇺🇸</div>
      <div>
        <div class="sheet-pair" id="sheet-pair">USD / ETB</div>
        <div class="sheet-rate" id="sheet-rate-sub">Black Market Rate</div>
      </div>
    </div>
  </div>
  <div class="sheet-grid">
    <div class="sg-box">
      <div class="sg-l">Black Market</div>
      <div class="sg-v" style="color:var(--gold)" id="sg-black">—</div>
    </div>
    <div class="sg-box">
      <div class="sg-l">Official NBE</div>
      <div class="sg-v" id="sg-off">—</div>
    </div>
    <div class="sg-box">
      <div class="sg-l">24h High</div>
      <div class="sg-v" style="color:var(--green)" id="sg-high">—</div>
    </div>
    <div class="sg-box">
      <div class="sg-l">24h Low</div>
      <div class="sg-v" style="color:var(--red)" id="sg-low">—</div>
    </div>
    <div class="sg-box">
      <div class="sg-l">Premium</div>
      <div class="sg-v" style="color:var(--gold)" id="sg-prem">—</div>
    </div>
    <div class="sg-box">
      <div class="sg-l">24h Change</div>
      <div class="sg-v" id="sg-chg">—</div>
    </div>
  </div>
  <div style="padding:0 16px;margin-bottom:12px">
    <div style="height:80px"><canvas id="sheetChart"></canvas></div>
  </div>
  <div class="sheet-close-btn" onclick="closeSheet()">Close</div>
</div>

<!-- TOAST -->
<div class="toast" id="toast"></div>

<script>
// ── DATA ──
let D = <?= json_encode($D) ?>;
let activeTab = 0;
let convCur = 'USD';
let convFlipped = false;
let rateType = 'black';
let chartPair = 'USD';
let mainChartInst = null;
let sheetChartInst = null;

// ── SPARKLINE HELPER ──
function spark(id, data, color) {
  const el = document.getElementById(id);
  if (!el) return;
  const existing = Chart.getChart(el);
  if (existing) existing.destroy();
  new Chart(el.getContext('2d'), {
    type:'line',
    data:{
      labels:data,
      datasets:[{data,borderColor:color,borderWidth:1.5,pointRadius:0,tension:.4,fill:false}]
    },
    options:{
      responsive:true,maintainAspectRatio:false,
      plugins:{legend:{display:false},tooltip:{enabled:false}},
      scales:{x:{display:false},y:{display:false}},
      animation:{duration:300}
    }
  });
}
function randSpark(base,n=10){
  let pts=[],v=base;
  for(let i=0;i<n;i++){v+=(Math.random()-.48)*.5;pts.push(+v.toFixed(3));}
  return pts;
}

// ── HERO CHART ──
function initHeroChart() {
  const el = document.getElementById('heroChart');
  if (!el) return;
  const existing = Chart.getChart(el);
  if (existing) existing.destroy();
  const ctx = el.getContext('2d');
  const grad = ctx.createLinearGradient(0,0,0,70);
  grad.addColorStop(0,'rgba(0,230,118,.25)');
  grad.addColorStop(1,'rgba(0,230,118,.01)');
  new Chart(ctx, {
    type:'line',
    data:{
      labels:D.chart.map(d=>d.x),
      datasets:[{
        data:D.chart.map(d=>d.y),
        borderColor:'#00e676',backgroundColor:grad,
        borderWidth:2,pointRadius:0,tension:.4,fill:true
      }]
    },
    options:{
      responsive:true,maintainAspectRatio:false,
      plugins:{legend:{display:false},tooltip:{enabled:false}},
      scales:{x:{display:false},y:{display:false}},
      animation:{duration:800}
    }
  });
}

// ── MAIN CHART ──
function initMainChart() {
  const el = document.getElementById('mainChartFull');
  if (!el) return;
  if (mainChartInst) mainChartInst.destroy();
  const ctx = el.getContext('2d');
  const grad = ctx.createLinearGradient(0,0,0,200);
  grad.addColorStop(0,'rgba(251,191,36,.2)');
  grad.addColorStop(1,'rgba(251,191,36,.01)');
  mainChartInst = new Chart(ctx, {
    type:'line',
    data:{
      labels:D.chart.map(d=>d.x),
      datasets:[{
        label:`${chartPair}/ETB`,
        data:D.chart.map(d=>d.y),
        borderColor:'#fbbf24',backgroundColor:grad,
        borderWidth:2,pointRadius:0,tension:.4,fill:true
      }]
    },
    options:{
      responsive:true,maintainAspectRatio:false,
      interaction:{mode:'index',intersect:false},
      plugins:{
        legend:{display:false},
        tooltip:{
          backgroundColor:'rgba(10,15,28,.95)',
          borderColor:'#1a2540',borderWidth:1,
          titleColor:'#94a3b8',bodyColor:'#f1f5f9',
          callbacks:{label:c=>`  ${chartPair}/ETB: ${c.parsed.y.toFixed(2)}`}
        }
      },
      scales:{
        x:{grid:{color:'rgba(26,37,64,.6)',drawBorder:false},
           ticks:{color:'#475569',font:{size:10},maxTicksLimit:6}},
        y:{position:'right',grid:{color:'rgba(26,37,64,.6)',drawBorder:false},
           ticks:{color:'#475569',font:{family:'JetBrains Mono',size:10}}}
      },
      animation:{duration:500}
    }
  });
}

// ── ALL SPARKLINES ──
function initAllSparks() {
  Object.keys(D.pairs).forEach(cur => {
    const p = D.pairs[cur];
    const c = p.change >= 0 ? '#00e676' : '#ff3d71';
    spark('qs-'+cur, randSpark(p.black), c);
    spark('ms-'+cur, randSpark(p.black), c);
  });
}

// ── INIT ──
window.addEventListener('DOMContentLoaded', () => {
  initHeroChart();
  initAllSparks();
  initMainChart();
  doConvert();
  startClock();
  startCountdown();
});

// ── NAV ──
const screens = ['home','markets','chart','convert'];
const navPositions = ['calc(12.5% - 20px)','calc(37.5% - 20px)','calc(62.5% - 20px)','calc(87.5% - 20px)'];
function switchTab(idx) {
  document.querySelectorAll('.screen').forEach((s,i)=>{
    s.classList.toggle('active', i===idx);
  });
  document.querySelectorAll('.nav-item').forEach((n,i)=>{
    n.classList.toggle('active', i===idx);
  });
  document.getElementById('nav-indicator').style.left = navPositions[idx];
  activeTab = idx;
  if (idx===2) setTimeout(()=>{ if(mainChartInst) mainChartInst.resize(); },100);
}

// ── CHART PAIR SELECT ──
function selectChartPair(cur, el) {
  chartPair = cur;
  document.querySelectorAll('.csel-item').forEach(e=>e.classList.remove('active'));
  el.classList.add('active');
  const p = D.pairs[cur];
  document.getElementById('cpc-label').textContent = `${cur} / ETB · Black Market`;
  document.getElementById('cpc-rate').textContent = p.black;
  document.getElementById('oc-high').textContent = p.high;
  document.getElementById('oc-low').textContent = p.low;
  document.getElementById('oc-prem').textContent = `+${p.premium}%`;
  // rebuild chart with new base
  const fakeChart = [];
  let v = p.black - (Math.random()*.5);
  for (let i=23;i>=0;i--) {
    v += (Math.random()-.48)*.4;
    fakeChart.push({x:D.chart[D.chart.length-1-i]?.x||`${i}h`,y:+v.toFixed(3)});
  }
  if (mainChartInst) {
    mainChartInst.data.labels = fakeChart.map(d=>d.x);
    mainChartInst.data.datasets[0].data = fakeChart.map(d=>d.y);
    mainChartInst.data.datasets[0].label = `${cur}/ETB`;
    mainChartInst.update();
  }
}

function setTimeRange(hrs, btn) {
  document.querySelectorAll('.ttab').forEach(t=>t.classList.remove('active'));
  btn.classList.add('active');
  if (!mainChartInst) return;
  const pts = Math.min(hrs*2, D.chart.length);
  const slice = D.chart.slice(-pts);
  mainChartInst.data.labels = slice.map(d=>d.x);
  mainChartInst.data.datasets[0].data = slice.map(d=>d.y);
  mainChartInst.update();
}

// ── BOTTOM SHEET ──
let sheetOpen = false;
function openSheet(cur) {
  const p = D.pairs[cur];
  if (!p) return;
  document.getElementById('sheet-flag').textContent = p.flag;
  document.getElementById('sheet-pair').textContent = `${cur} / ETB`;
  document.getElementById('sheet-rate-sub').textContent = p.name + ' · Black Market';
  document.getElementById('sg-black').textContent = p.black;
  document.getElementById('sg-off').textContent = p.official;
  document.getElementById('sg-high').textContent = p.high;
  document.getElementById('sg-low').textContent = p.low;
  document.getElementById('sg-prem').textContent = `+${p.premium}%`;
  const chgEl = document.getElementById('sg-chg');
  chgEl.textContent = (p.change>=0?'+':'')+p.change+'%';
  chgEl.style.color = p.change>=0?'var(--green)':'var(--red)';
  // mini chart
  if (sheetChartInst) sheetChartInst.destroy();
  const el = document.getElementById('sheetChart');
  const pts = randSpark(p.black, 20);
  const color = p.change >= 0 ? '#00e676' : '#ff3d71';
  sheetChartInst = new Chart(el.getContext('2d'), {
    type:'line',
    data:{labels:pts,datasets:[{data:pts,borderColor:color,borderWidth:2,
      pointRadius:0,tension:.4,fill:false}]},
    options:{responsive:true,maintainAspectRatio:false,
      plugins:{legend:{display:false},tooltip:{enabled:false}},
      scales:{x:{display:false},y:{display:false}},animation:{duration:400}}
  });
  document.getElementById('sheet-backdrop').classList.add('open');
  document.getElementById('bottom-sheet').classList.add('open');
  sheetOpen = true;
}
function closeSheet() {
  document.getElementById('sheet-backdrop').classList.remove('open');
  document.getElementById('bottom-sheet').classList.remove('open');
  sheetOpen = false;
}
document.addEventListener('keydown', e => { if(e.key==='Escape') closeSheet(); });

// ── MARKET FILTER ──
function setFilter(group, el) {
  document.querySelectorAll('.filter-chip').forEach(c=>c.classList.remove('active'));
  el.classList.add('active');
  document.querySelectorAll('.pair-row').forEach(row=>{
    const g = row.dataset.group;
    row.style.display = (group==='all' || g===group) ? '' : 'none';
  });
}
function filterPairs(q) {
  const lower = q.toLowerCase();
  document.querySelectorAll('.pair-row').forEach(row=>{
    const cur = row.dataset.cur.toLowerCase();
    row.style.display = cur.includes(lower) ? '' : 'none';
  });
}

// ── CONVERT ──
function doConvert() {
  const amt = parseFloat(document.getElementById('conv-input').value) || 0;
  const p = D.pairs[convCur];
  if (!p) return;
  const rate = rateType === 'black' ? p.black : p.official;
  let result;
  if (convFlipped) {
    result = amt / rate;
    document.getElementById('conv-to-val').textContent = result.toFixed(4) + ' ' + convCur;
    document.getElementById('conv-result-val').textContent = result.toFixed(4);
    document.getElementById('conv-result-label').textContent = convCur;
  } else {
    result = amt * rate;
    document.getElementById('conv-to-val').textContent = result.toFixed(2) + ' ETB';
    document.getElementById('conv-result-val').textContent = result.toLocaleString('en',{maximumFractionDigits:2});
    document.getElementById('conv-result-label').textContent = 'Ethiopian Birr';
  }
}
function swapConvert() {
  convFlipped = !convFlipped;
  const fromFlag = document.getElementById('from-flag');
  const fromCur  = document.getElementById('from-cur');
  if (convFlipped) {
    fromFlag.textContent = '🇪🇹';
    fromCur.textContent  = 'ETB';
  } else {
    const p = D.pairs[convCur];
    fromFlag.textContent = p?.flag || '🇺🇸';
    fromCur.textContent  = convCur;
  }
  doConvert();
}
function selectConvCur(cur, flag) {
  convCur = cur;
  document.querySelectorAll('.cur-chip').forEach(c=>c.classList.toggle('sel', c.dataset.cur===cur));
  if (!convFlipped) {
    document.getElementById('from-flag').textContent = flag;
    document.getElementById('from-cur').textContent  = cur;
  }
  doConvert();
}
function setRateType(type, el) {
  rateType = type;
  document.querySelectorAll('.rtype').forEach(r=>r.classList.remove('active'));
  el.classList.add('active');
  doConvert();
}

// ── REFRESH ──
let refreshTimer = 30;
let timerInterval;
function startCountdown() {
  timerInterval = setInterval(() => {
    refreshTimer--;
    if (refreshTimer <= 0) { doRefresh(); refreshTimer = 30; }
  }, 1000);
}
async function doRefresh() {
  try {
    const res = await fetch('?json=1');
    if (!res.ok) return;
    D = await res.json();
    updateAllUI();
    showToast('✓ Rates updated');
  } catch(e) { showToast('⚠ Using cached rates'); }
}
function manualRefresh(btn) {
  const icon = btn?.querySelector('svg');
  if (icon) { icon.classList.add('spinning'); setTimeout(()=>icon.classList.remove('spinning'),700); }
  doRefresh();
  refreshTimer = 30;
}
function updateAllUI() {
  // Hero
  flashVal('hero-rate', D.black_usd.toFixed(2));
  document.getElementById('h-off').textContent   = D.official_usd.toFixed(2);
  document.getElementById('h-high').textContent  = D.high24;
  document.getElementById('h-low').textContent   = D.low24;
  document.getElementById('h-prem').textContent  = `+${D.premium_pct}%`;
  document.getElementById('h-ts').textContent    = D.timestamp;
  // Chart screen
  document.getElementById('cpc-rate').textContent = D.pairs[chartPair]?.black || D.black_usd;
  document.getElementById('oc-high').textContent  = D.high24;
  document.getElementById('oc-low').textContent   = D.low24;
  document.getElementById('oc-prem').textContent  = `+${D.premium_pct}%`;
  // Convert refs
  document.getElementById('ref-off').textContent   = D.official_usd + ' ETB/USD';
  document.getElementById('ref-black').textContent = D.black_usd + ' ETB/USD';
  // Market rates
  Object.keys(D.pairs).forEach(cur=>{
    const p = D.pairs[cur];
    const mrEl = document.querySelector(`[data-mrate="${cur}"]`);
    if (mrEl) flashVal(mrEl, p.black, true);
    const qrEl = document.querySelector(`[data-qrate="${cur}"]`);
    if (qrEl) flashVal(qrEl, p.black, true);
    const csEl = document.getElementById(`cs-${cur}`);
    if (csEl) csEl.textContent = p.black;
  });
  // Charts
  if (mainChartInst) {
    mainChartInst.data.labels = D.chart.map(d=>d.x);
    mainChartInst.data.datasets[0].data = D.chart.map(d=>d.y);
    mainChartInst.update();
  }
  initHeroChart();
  doConvert();
}
function flashVal(elOrId, val, isEl=false) {
  const el = isEl ? elOrId : document.getElementById(elOrId);
  if (!el) return;
  const old = parseFloat(el.textContent||'0');
  el.classList.remove('flash-g','flash-r');
  void el.offsetWidth;
  el.classList.add(parseFloat(val) >= old ? 'flash-g' : 'flash-r');
  el.textContent = val;
}

// ── TOAST ──
let toastTimer;
function showToast(msg) {
  const t = document.getElementById('toast');
  t.textContent = msg;
  t.classList.add('show');
  clearTimeout(toastTimer);
  toastTimer = setTimeout(()=>t.classList.remove('show'), 2200);
}

// ── CLOCK ──
function startClock() {
  setInterval(() => {
    const now = new Date();
    document.getElementById('sb-time').textContent =
      now.toLocaleTimeString('en-US',{hour:'numeric',minute:'2-digit'});
  }, 10000);
}
</script>
</body>
</html>
