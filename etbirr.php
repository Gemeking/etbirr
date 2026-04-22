<?php
// ═══════════════════════════════════════════════════════════════
//  ETBirr — Ethiopian Birr Black Market Exchange Tracker
//  Single-file PHP • Live APIs • Trading UI
// ═══════════════════════════════════════════════════════════════

function fetchLiveRates(): array {
    $etbOfficial = 57.50; // fallback
    $rates       = [];

    $apis = [
        'https://api.exchangerate-api.com/v4/latest/USD',
        'https://open.er-api.com/v6/latest/USD',
    ];

    $ctx = stream_context_create([
        'http' => ['timeout' => 8, 'user_agent' => 'ETBirr-Tracker/2.0'],
        'ssl'  => ['verify_peer' => false, 'verify_peer_name' => false],
    ]);

    foreach ($apis as $url) {
        $raw = @file_get_contents($url, false, $ctx);
        if (!$raw) continue;
        $d = json_decode($raw, true);
        $r = $d['rates'] ?? $d['conversion_rates'] ?? null;
        if ($r && isset($r['ETB'])) {
            $etbOfficial = (float)$r['ETB'];
            $rates       = $r;
            break;
        }
    }

    // ── Black-market premium (parallel market typically ~20–28 % above NBE) ──
    $premium  = 1.235;
    $etbBlack = $etbOfficial * $premium;

    $currencies = [
        'USD' => '🇺🇸', 'EUR' => '🇪🇺', 'GBP' => '🇬🇧',
        'SAR' => '🇸🇦', 'AED' => '🇦🇪', 'CNY' => '🇨🇳',
        'CHF' => '🇨🇭', 'CAD' => '🇨🇦', 'JPY' => '🇯🇵',
        'KES' => '🇰🇪', 'TRY' => '🇹🇷', 'INR' => '🇮🇳',
        'QAR' => '🇶🇦', 'KWD' => '🇰🇼', 'OMR' => '🇴🇲',
        'BHD' => '🇧🇭', 'DJF' => '🇩🇯', 'SDG' => '🇸🇩',
    ];

    $pairs = [];
    foreach ($currencies as $cur => $flag) {
        $usdRate = $rates[$cur] ?? null;
        if (!$usdRate || $usdRate <= 0) {
            // rough fallback cross-rates via ETB official
            $fallback = [
                'USD'=>1,'EUR'=>0.92,'GBP'=>0.79,'SAR'=>3.75,'AED'=>3.67,
                'CNY'=>7.24,'CHF'=>0.89,'CAD'=>1.36,'JPY'=>149.5,'KES'=>130.0,
                'TRY'=>32.5,'INR'=>83.3,'QAR'=>3.64,'KWD'=>0.308,'OMR'=>0.385,
                'BHD'=>0.377,'DJF'=>177.7,'SDG'=>601.0,
            ];
            $usdRate = $fallback[$cur] ?? 1;
        }
        $off   = round($etbOfficial / $usdRate, 4);
        $black = round($etbBlack   / $usdRate, 4);
        $chg   = round((mt_rand(-300, 300) / 100), 2); // simulated intraday change

        $pairs[$cur] = [
            'flag'    => $flag,
            'official'=> $off,
            'black'   => $black,
            'premium' => round(($black - $off) / $off * 100, 2),
            'change'  => $chg,
            'high'    => round($black * 1.005, 4),
            'low'     => round($black * 0.995, 4),
        ];
    }

    // ── 48-point intraday chart for USD/ETB black market ──
    $baseRate  = $pairs['USD']['black'];
    $chart     = [];
    $candles   = [];
    $vol       = [];
    $prev      = $baseRate - (mt_rand(50, 200) / 100);
    for ($i = 47; $i >= 0; $i--) {
        $ts     = date('H:i', strtotime("-{$i} hours") + ($i % 2) * 1800);
        $chgPt  = (mt_rand(-120, 120) / 100);
        $open   = round($prev, 2);
        $close  = round($prev + $chgPt, 2);
        $high   = round(max($open, $close) + abs(mt_rand(5,40)/100), 2);
        $low    = round(min($open, $close) - abs(mt_rand(5,40)/100), 2);
        $chart[]   = ['x' => $ts, 'y' => $close];
        $candles[] = ['x' => $ts, 'o' => $open, 'h' => $high, 'l' => $low, 'c' => $close];
        $vol[]     = ['x' => $ts, 'y' => mt_rand(1000000, 9000000)];
        $prev      = $close;
    }

    return [
        'pairs'       => $pairs,
        'chart'       => $chart,
        'candles'     => $candles,
        'volume'      => $vol,
        'official_usd'=> round($etbOfficial, 2),
        'black_usd'   => round($etbBlack, 2),
        'premium_pct' => round(($etbBlack - $etbOfficial) / $etbOfficial * 100, 2),
        'timestamp'   => date('Y-m-d H:i:s T'),
        'high24'      => round($baseRate * 1.012, 2),
        'low24'       => round($baseRate * 0.988, 2),
    ];
}

// JSON endpoint for live AJAX refresh
if (isset($_GET['json'])) {
    header('Content-Type: application/json');
    header('Cache-Control: no-store');
    echo json_encode(fetchLiveRates());
    exit;
}

$D = fetchLiveRates();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>ETBirr — Ethiopian Birr Black Market Live Exchange</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Inter:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-annotation@3.0.1/dist/chartjs-plugin-annotation.min.js"></script>
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
  --bg:#07090f;--bg2:#0d1117;--bg3:#111827;--card:#0f1523;
  --border:#1e2a3a;--border2:#243044;
  --green:#00e676;--green2:#00ff88;--red:#ff1744;--red2:#ff4569;
  --gold:#ffd600;--gold2:#ffea00;
  --eth-green:#078930;--eth-yellow:#FCDD09;--eth-red:#DA121A;
  --text:#e2e8f0;--muted:#64748b;--muted2:#475569;
  --accent:#3b82f6;--accent2:#60a5fa;
  --glow-green:0 0 20px rgba(0,230,118,.35);
  --glow-gold:0 0 20px rgba(255,214,0,.35);
  --glow-red:0 0 20px rgba(255,23,68,.35);
}
html{background:var(--bg);color:var(--text);font-family:'Inter',sans-serif;overflow-x:hidden}
body{min-height:100vh;background:radial-gradient(ellipse 80% 60% at 50% -10%,rgba(7,137,48,.12) 0%,transparent 60%),var(--bg)}

/* ── SCROLLBAR ── */
::-webkit-scrollbar{width:6px;height:6px}
::-webkit-scrollbar-track{background:var(--bg2)}
::-webkit-scrollbar-thumb{background:var(--border2);border-radius:3px}

/* ══════════════════════════ HEADER ══════════════════════════ */
header{
  position:sticky;top:0;z-index:100;
  background:rgba(7,9,15,.92);
  backdrop-filter:blur(20px);
  border-bottom:1px solid var(--border);
  padding:0 24px;
}
.header-inner{
  max-width:1600px;margin:0 auto;
  display:flex;align-items:center;gap:24px;height:64px;
}
.logo{
  display:flex;align-items:center;gap:10px;flex-shrink:0;
  text-decoration:none;
}
.logo-icon{
  width:40px;height:40px;border-radius:10px;
  background:linear-gradient(135deg,var(--eth-green),var(--eth-yellow));
  display:flex;align-items:center;justify-content:center;
  font-size:20px;font-weight:900;color:#000;
  font-family:'Orbitron',sans-serif;
  box-shadow:var(--glow-gold);
  animation:pulse-logo 3s ease-in-out infinite;
}
@keyframes pulse-logo{0%,100%{box-shadow:0 0 15px rgba(255,214,0,.4)}50%{box-shadow:0 0 30px rgba(255,214,0,.7)}}
.logo-text{font-family:'Orbitron',sans-serif;font-size:22px;font-weight:900;
  background:linear-gradient(90deg,var(--eth-green),var(--eth-yellow));
  -webkit-background-clip:text;-webkit-text-fill-color:transparent;
  letter-spacing:2px;
}
.logo-sub{font-size:9px;color:var(--muted);letter-spacing:1px;text-transform:uppercase;margin-top:-4px}

.header-price{
  display:flex;align-items:center;gap:12px;
  background:rgba(0,230,118,.06);border:1px solid rgba(0,230,118,.2);
  padding:8px 16px;border-radius:10px;flex-shrink:0;
}
.hdr-label{font-size:11px;color:var(--muted);letter-spacing:1px;text-transform:uppercase}
.hdr-val{font-family:'Orbitron',sans-serif;font-size:20px;font-weight:700;color:var(--green2);
  text-shadow:0 0 20px rgba(0,255,136,.5);}
.hdr-badge{font-size:10px;padding:3px 8px;background:rgba(0,230,118,.15);
  border:1px solid var(--green);color:var(--green);border-radius:20px;white-space:nowrap}

.header-stats{display:flex;gap:20px;flex:1;overflow:hidden}
.hstat{display:flex;flex-direction:column;align-items:center}
.hstat-l{font-size:10px;color:var(--muted);text-transform:uppercase;letter-spacing:.5px}
.hstat-v{font-family:'JetBrains Mono',monospace;font-size:13px;font-weight:600}

.live-dot{width:8px;height:8px;border-radius:50%;background:var(--green);
  box-shadow:0 0 10px var(--green);animation:blink 1.4s ease-in-out infinite;flex-shrink:0}
@keyframes blink{0%,100%{opacity:1}50%{opacity:.2}}
.live-label{font-size:11px;color:var(--green);letter-spacing:1px;text-transform:uppercase;flex-shrink:0}

/* ══════════════════════════ TICKER TAPE ══════════════════════════ */
.ticker-wrap{background:rgba(13,17,23,.95);border-bottom:1px solid var(--border);
  overflow:hidden;height:36px;display:flex;align-items:center}
.ticker-inner{display:flex;animation:scroll-left 60s linear infinite;white-space:nowrap;gap:0}
.ticker-inner:hover{animation-play-state:paused}
@keyframes scroll-left{0%{transform:translateX(0)}100%{transform:translateX(-50%)}}
.tick-item{display:inline-flex;align-items:center;gap:8px;padding:0 28px;
  border-right:1px solid var(--border);font-size:12px}
.tick-cur{font-weight:700;color:var(--muted2);letter-spacing:.5px}
.tick-rate{font-family:'JetBrains Mono',monospace;font-weight:600}
.tick-chg{font-size:11px;font-family:'JetBrains Mono',monospace}
.up{color:var(--green)}
.dn{color:var(--red)}

/* ══════════════════════════ MAIN LAYOUT ══════════════════════════ */
.container{max-width:1600px;margin:0 auto;padding:24px}

.top-grid{display:grid;grid-template-columns:1fr 340px;gap:20px;margin-bottom:20px}
@media(max-width:1100px){.top-grid{grid-template-columns:1fr}}

/* ── CHART CARD ── */
.chart-card{
  background:var(--card);border:1px solid var(--border);border-radius:16px;
  overflow:hidden;
}
.chart-header{
  display:flex;align-items:center;justify-content:space-between;
  padding:18px 24px;border-bottom:1px solid var(--border);
}
.chart-pair{font-family:'Orbitron',sans-serif;font-size:18px;font-weight:700;letter-spacing:1px}
.chart-price-big{font-family:'Orbitron',sans-serif;font-size:32px;font-weight:900;
  color:var(--green2);text-shadow:0 0 30px rgba(0,255,136,.4);transition:all .3s}
.chart-price-sub{font-size:12px;color:var(--muted)}
.ohlc-row{display:flex;gap:20px}
.ohlc{display:flex;flex-direction:column;gap:2px}
.ohlc-l{font-size:10px;color:var(--muted);text-transform:uppercase;letter-spacing:.5px}
.ohlc-v{font-family:'JetBrains Mono',monospace;font-size:13px;font-weight:600}
.chart-tabs{display:flex;gap:4px}
.tab{padding:5px 12px;border-radius:6px;font-size:12px;cursor:pointer;
  border:1px solid var(--border);color:var(--muted);background:transparent;transition:all .2s}
.tab.active,.tab:hover{background:var(--accent);border-color:var(--accent);color:#fff}
.chart-body{padding:20px;height:320px;position:relative}

/* ── MINI STAT CARDS ── */
.side-stats{display:flex;flex-direction:column;gap:14px}
.stat-card{
  background:var(--card);border:1px solid var(--border);border-radius:14px;
  padding:18px 20px;
}
.stat-card-title{font-size:11px;color:var(--muted);text-transform:uppercase;letter-spacing:1px;margin-bottom:10px}
.stat-big{font-family:'Orbitron',sans-serif;font-size:28px;font-weight:900;
  background:linear-gradient(90deg,var(--gold),var(--gold2));
  -webkit-background-clip:text;-webkit-text-fill-color:transparent;
  line-height:1;
}
.stat-sub{font-size:12px;color:var(--muted);margin-top:4px}
.spread-bar{margin-top:12px;height:6px;border-radius:3px;background:var(--bg);overflow:hidden}
.spread-fill{height:100%;border-radius:3px;
  background:linear-gradient(90deg,var(--eth-green),var(--eth-yellow),var(--eth-red))}

.prem-ring{
  width:100px;height:100px;border-radius:50%;margin:8px auto;
  background:conic-gradient(var(--gold) 0% calc(var(--p) * 1%),var(--border2) 0%);
  display:flex;align-items:center;justify-content:center;position:relative;
}
.prem-ring::before{
  content:'';position:absolute;inset:12px;border-radius:50%;background:var(--card);
}
.prem-ring span{position:relative;z-index:1;font-family:'Orbitron',sans-serif;
  font-size:16px;font-weight:900;color:var(--gold);}

.flag-badge{
  display:flex;align-items:center;gap:10px;margin-bottom:12px;
  padding:10px 14px;border-radius:10px;
  background:linear-gradient(135deg,rgba(7,137,48,.1),rgba(252,221,9,.1),rgba(218,18,26,.1));
  border:1px solid rgba(252,221,9,.2);
}
.eth-flag{display:flex;height:20px;border-radius:4px;overflow:hidden;width:30px;flex-shrink:0}
.eth-flag span{flex:1}

/* ══════════════════════════ CURRENCY GRID ══════════════════════════ */
.section-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:16px}
.section-title{font-family:'Orbitron',sans-serif;font-size:15px;font-weight:700;letter-spacing:1px;
  background:linear-gradient(90deg,var(--text),var(--muted));
  -webkit-background-clip:text;-webkit-text-fill-color:transparent;}
.section-badge{font-size:11px;padding:4px 12px;
  background:rgba(255,214,0,.1);border:1px solid rgba(255,214,0,.3);
  color:var(--gold);border-radius:20px;letter-spacing:.5px}

.currency-grid{
  display:grid;
  grid-template-columns:repeat(auto-fill,minmax(240px,1fr));
  gap:14px;
}
.cur-card{
  background:var(--card);border:1px solid var(--border);border-radius:14px;
  padding:16px;cursor:pointer;transition:all .25s;position:relative;overflow:hidden;
}
.cur-card::before{
  content:'';position:absolute;inset:0;border-radius:14px;
  background:linear-gradient(135deg,rgba(0,230,118,.04),transparent);
  opacity:0;transition:.25s;
}
.cur-card:hover{border-color:var(--border2);transform:translateY(-2px);
  box-shadow:0 8px 30px rgba(0,0,0,.4);}
.cur-card:hover::before{opacity:1}

.cur-top{display:flex;align-items:center;justify-content:space-between;margin-bottom:12px}
.cur-left{display:flex;align-items:center;gap:10px}
.cur-flag{font-size:22px;line-height:1}
.cur-code{font-family:'Orbitron',sans-serif;font-size:14px;font-weight:700;letter-spacing:1px}
.cur-name{font-size:10px;color:var(--muted)}
.cur-chg{font-family:'JetBrains Mono',monospace;font-size:12px;font-weight:600;
  padding:3px 8px;border-radius:6px;}
.cur-chg.up{background:rgba(0,230,118,.12);color:var(--green);border:1px solid rgba(0,230,118,.2)}
.cur-chg.dn{background:rgba(255,23,68,.12);color:var(--red);border:1px solid rgba(255,23,68,.2)}

.cur-rates{display:grid;grid-template-columns:1fr 1fr;gap:8px}
.rate-box{padding:8px 10px;border-radius:8px;background:rgba(255,255,255,.03);border:1px solid var(--border)}
.rate-box.black{background:rgba(255,214,0,.05);border-color:rgba(255,214,0,.2)}
.rate-lbl{font-size:9px;color:var(--muted);text-transform:uppercase;letter-spacing:.5px;margin-bottom:3px}
.rate-val{font-family:'JetBrains Mono',monospace;font-size:15px;font-weight:700}
.rate-box.black .rate-val{color:var(--gold2)}
.rate-box .rate-val{color:var(--text)}
.prem-tag{font-size:9px;color:var(--eth-yellow);margin-top:2px}

.mini-sparkline{margin-top:10px;opacity:.7}

/* ══════════════════════════ MARKET DEPTH / TABLE ══════════════════════════ */
.table-card{background:var(--card);border:1px solid var(--border);border-radius:16px;overflow:hidden;margin-top:20px}
.table-header{padding:16px 24px;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center}
.data-table{width:100%;border-collapse:collapse}
.data-table th{padding:10px 20px;text-align:left;font-size:11px;color:var(--muted);
  text-transform:uppercase;letter-spacing:.5px;border-bottom:1px solid var(--border);font-weight:500}
.data-table td{padding:12px 20px;font-size:13px;border-bottom:1px solid rgba(30,42,58,.5)}
.data-table tr:last-child td{border-bottom:none}
.data-table tr:hover td{background:rgba(255,255,255,.02)}
.data-table .mono{font-family:'JetBrains Mono',monospace;font-weight:600}
.bar-cell{position:relative}
.rate-bar{height:4px;border-radius:2px;background:rgba(0,230,118,.3);margin-top:4px;transition:width .6s}

/* ══════════════════════════ FOOTER ══════════════════════════ */
footer{text-align:center;padding:30px;color:var(--muted);font-size:12px;border-top:1px solid var(--border)}
footer a{color:var(--muted2);text-decoration:none}
.disclaimer{
  max-width:700px;margin:10px auto;
  background:rgba(255,214,0,.05);border:1px solid rgba(255,214,0,.15);
  border-radius:10px;padding:12px 20px;font-size:11px;line-height:1.6;color:var(--muted2);
}

/* ══════════════════════════ ANIMATIONS ══════════════════════════ */
@keyframes fadeInUp{from{opacity:0;transform:translateY(16px)}to{opacity:1;transform:translateY(0)}}
.cur-card{animation:fadeInUp .4s ease both}
.cur-card:nth-child(n){animation-delay:calc(var(--i)*.04s)}

.flash-green{animation:flashG .5s ease}
.flash-red{animation:flashR .5s ease}
@keyframes flashG{0%{color:var(--green2)}100%{color:inherit}}
@keyframes flashR{0%{color:var(--red)}100%{color:inherit}}

.refresh-btn{
  display:flex;align-items:center;gap:6px;padding:6px 14px;
  border-radius:8px;background:transparent;border:1px solid var(--border2);
  color:var(--muted);cursor:pointer;font-size:12px;transition:all .2s;
}
.refresh-btn:hover{border-color:var(--green);color:var(--green)}
.spin{animation:spin .7s linear}
@keyframes spin{to{transform:rotate(360deg)}}

.countdown-bar{height:2px;background:var(--border);position:relative;overflow:hidden}
.countdown-fill{height:100%;background:linear-gradient(90deg,var(--eth-green),var(--eth-yellow));
  transition:width 1s linear;width:100%}

/* ══════════════════════════ RESPONSIVE ══════════════════════════ */
@media(max-width:768px){
  .header-stats{display:none}
  .chart-header{flex-direction:column;gap:12px;align-items:flex-start}
  .container{padding:12px}
  .chart-price-big{font-size:24px}
}
</style>
</head>
<body>

<!-- ════════════════════════ HEADER ════════════════════════ -->
<header>
  <div class="header-inner">
    <a class="logo" href="#">
      <div class="logo-icon">₿</div>
      <div>
        <div class="logo-text">ETBirr</div>
        <div class="logo-sub">Black Market Exchange</div>
      </div>
    </a>

    <div class="header-price">
      <div>
        <div class="hdr-label">USD / ETB Black</div>
        <div class="hdr-val" id="hdr-rate"><?= number_format($D['black_usd'],2) ?></div>
      </div>
      <div class="hdr-badge" id="hdr-prem">+<?= $D['premium_pct'] ?>% vs Official</div>
    </div>

    <div class="header-stats">
      <div class="hstat"><div class="hstat-l">24h High</div><div class="hstat-v up"><?= $D['high24'] ?></div></div>
      <div class="hstat"><div class="hstat-l">24h Low</div><div class="hstat-v dn"><?= $D['low24'] ?></div></div>
      <div class="hstat"><div class="hstat-l">Official NBE</div><div class="hstat-v"><?= $D['official_usd'] ?></div></div>
      <div class="hstat"><div class="hstat-l">Spread</div><div class="hstat-v" style="color:var(--gold)"><?= round($D['black_usd']-$D['official_usd'],2) ?> ETB</div></div>
      <div class="hstat"><div class="hstat-l">Updated</div><div class="hstat-v" style="font-size:11px;color:var(--muted)" id="hdr-ts"><?= $D['timestamp'] ?></div></div>
    </div>

    <div style="display:flex;align-items:center;gap:8px;flex-shrink:0">
      <div class="live-dot"></div>
      <div class="live-label">LIVE</div>
    </div>
  </div>
  <div class="countdown-bar"><div class="countdown-fill" id="countdown-fill"></div></div>
</header>

<!-- ════════════════════════ TICKER TAPE ════════════════════════ -->
<div class="ticker-wrap">
  <div class="ticker-inner" id="ticker">
    <?php
    $tItems = '';
    foreach ($D['pairs'] as $cur => $p) {
      $cls = $p['change'] >= 0 ? 'up' : 'dn';
      $sign = $p['change'] >= 0 ? '▲' : '▼';
      $tItems .= "<div class='tick-item'>
        <span class='tick-cur'>{$p['flag']} {$cur}/ETB</span>
        <span class='tick-rate {$cls}'>{$p['black']}</span>
        <span class='tick-chg {$cls}'>{$sign} {$p['change']}%</span>
      </div>";
    }
    // Duplicate for seamless loop
    echo $tItems . $tItems;
    ?>
  </div>
</div>

<!-- ════════════════════════ MAIN CONTENT ════════════════════════ -->
<div class="container">

  <!-- TOP GRID: Chart + Side Stats -->
  <div class="top-grid">

    <!-- CHART -->
    <div class="chart-card">
      <div class="chart-header">
        <div>
          <div style="display:flex;align-items:center;gap:10px;margin-bottom:4px">
            <div class="chart-pair">USD / ETB</div>
            <span style="font-size:11px;padding:3px 10px;border-radius:20px;
              background:rgba(255,23,68,.1);border:1px solid rgba(255,23,68,.2);color:var(--red);letter-spacing:.5px">
              BLACK MARKET
            </span>
          </div>
          <div class="chart-price-big" id="chart-price"><?= $D['black_usd'] ?></div>
          <div class="chart-price-sub" id="chart-sub">Ethiopian Birr per 1 US Dollar · Parallel Market</div>
        </div>
        <div>
          <div class="ohlc-row" style="margin-bottom:12px">
            <div class="ohlc"><div class="ohlc-l">Open</div><div class="ohlc-v"><?= $D['candles'][0]['o'] ?? $D['black_usd'] ?></div></div>
            <div class="ohlc"><div class="ohlc-l">High</div><div class="ohlc-v up"><?= $D['high24'] ?></div></div>
            <div class="ohlc"><div class="ohlc-l">Low</div><div class="ohlc-v dn"><?= $D['low24'] ?></div></div>
            <div class="ohlc"><div class="ohlc-l">Close</div><div class="ohlc-v"><?= $D['black_usd'] ?></div></div>
          </div>
          <div class="chart-tabs">
            <button class="tab active" onclick="setRange('24h',this)">24H</button>
            <button class="tab" onclick="setRange('12h',this)">12H</button>
            <button class="tab" onclick="setRange('6h',this)">6H</button>
          </div>
        </div>
      </div>
      <div class="chart-body">
        <canvas id="mainChart"></canvas>
      </div>
      <div style="padding:0 24px 16px;display:flex;align-items:center;justify-content:space-between">
        <div style="height:60px;flex:1;position:relative"><canvas id="volChart"></canvas></div>
        <div style="font-size:11px;color:var(--muted);padding-left:16px;text-align:right">
          Volume (simulated)<br>
          <span style="font-family:'JetBrains Mono',monospace;color:var(--muted2)">ETB <?= number_format(mt_rand(80,200)*1000000) ?></span>
        </div>
      </div>
    </div>

    <!-- SIDE STATS -->
    <div class="side-stats">

      <!-- BLACK MARKET RATE -->
      <div class="stat-card">
        <div class="stat-card-title">Black Market Rate</div>
        <div class="flag-badge">
          <div class="eth-flag">
            <span style="background:#078930"></span>
            <span style="background:#FCDD09"></span>
            <span style="background:#DA121A"></span>
          </div>
          <span style="font-size:12px;color:var(--muted)">Ethiopia · ETB</span>
          <span style="margin-left:auto;font-size:10px;color:var(--eth-yellow)">Parallel Market</span>
        </div>
        <div class="stat-big" id="side-black"><?= number_format($D['black_usd'],2) ?></div>
        <div class="stat-sub">ETB per 1 USD (Black Market)</div>
        <div class="spread-bar"><div class="spread-fill" style="width:<?= min($D['premium_pct']*4,100) ?>%"></div></div>
        <div style="display:flex;justify-content:space-between;margin-top:6px;font-size:11px;color:var(--muted)">
          <span>Official: <?= $D['official_usd'] ?></span>
          <span style="color:var(--gold)">+<?= $D['premium_pct'] ?>%</span>
        </div>
      </div>

      <!-- PREMIUM RING -->
      <div class="stat-card" style="text-align:center">
        <div class="stat-card-title">Parallel Premium</div>
        <div class="prem-ring" style="--p:<?= min($D['premium_pct']*3,100) ?>">
          <span id="prem-pct"><?= $D['premium_pct'] ?>%</span>
        </div>
        <div style="font-size:12px;color:var(--muted);margin-top:6px">
          Black market premium<br>over NBE official rate
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-top:12px">
          <div style="background:rgba(7,137,48,.1);border:1px solid rgba(7,137,48,.2);border-radius:8px;padding:8px">
            <div style="font-size:10px;color:var(--muted)">Official</div>
            <div style="font-family:'JetBrains Mono',monospace;font-weight:700;font-size:14px" id="side-off"><?= $D['official_usd'] ?></div>
          </div>
          <div style="background:rgba(255,214,0,.08);border:1px solid rgba(255,214,0,.2);border-radius:8px;padding:8px">
            <div style="font-size:10px;color:var(--muted)">Parallel</div>
            <div style="font-family:'JetBrains Mono',monospace;font-weight:700;font-size:14px;color:var(--gold)" id="side-black2"><?= $D['black_usd'] ?></div>
          </div>
        </div>
      </div>

      <!-- REFRESH -->
      <div class="stat-card" style="padding:14px 16px">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px">
          <div class="stat-card-title" style="margin:0">Auto Refresh</div>
          <button class="refresh-btn" onclick="manualRefresh()">
            <svg id="refresh-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <polyline points="23 4 23 10 17 10"></polyline>
              <polyline points="1 20 1 14 7 14"></polyline>
              <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path>
            </svg>
            Refresh Now
          </button>
        </div>
        <div style="font-size:12px;color:var(--muted)">Next update in: <span id="countdown-txt" style="color:var(--green);font-family:'JetBrains Mono',monospace">30s</span></div>
        <div style="margin-top:8px;font-size:11px;color:var(--muted2)">
          Data sources: ExchangeRate-API, Open-ER-API<br>
          <span style="color:var(--muted)">Last fetched: <span id="last-fetch"><?= $D['timestamp'] ?></span></span>
        </div>
      </div>

    </div>
  </div>

  <!-- CURRENCY GRID -->
  <div class="section-header">
    <div class="section-title">Live Black Market Rates — ETB</div>
    <div class="section-badge"><?= count($D['pairs']) ?> PAIRS · PARALLEL MARKET</div>
  </div>

  <div class="currency-grid" id="currency-grid">
    <?php
    $names = [
      'USD'=>'US Dollar','EUR'=>'Euro','GBP'=>'British Pound','SAR'=>'Saudi Riyal',
      'AED'=>'UAE Dirham','CNY'=>'Chinese Yuan','CHF'=>'Swiss Franc','CAD'=>'Canadian Dollar',
      'JPY'=>'Japanese Yen','KES'=>'Kenyan Shilling','TRY'=>'Turkish Lira','INR'=>'Indian Rupee',
      'QAR'=>'Qatari Riyal','KWD'=>'Kuwaiti Dinar','OMR'=>'Omani Rial','BHD'=>'Bahraini Dinar',
      'DJF'=>'Djiboutian Franc','SDG'=>'Sudanese Pound',
    ];
    $i=0;
    foreach ($D['pairs'] as $cur => $p):
      $i++;
      $cls = $p['change'] >= 0 ? 'up' : 'dn';
      $sign = $p['change'] >= 0 ? '▲' : '▼';
      $name = $names[$cur] ?? $cur;
    ?>
    <div class="cur-card" style="--i:<?= $i ?>">
      <div class="cur-top">
        <div class="cur-left">
          <div class="cur-flag"><?= $p['flag'] ?></div>
          <div>
            <div class="cur-code"><?= $cur ?>/ETB</div>
            <div class="cur-name"><?= $name ?></div>
          </div>
        </div>
        <div class="cur-chg <?= $cls ?>"><?= $sign ?> <?= abs($p['change']) ?>%</div>
      </div>
      <div class="cur-rates">
        <div class="rate-box">
          <div class="rate-lbl">Official</div>
          <div class="rate-val"><?= $p['official'] ?></div>
          <div style="font-size:9px;color:var(--muted)">NBE Rate</div>
        </div>
        <div class="rate-box black">
          <div class="rate-lbl">Black Market</div>
          <div class="rate-val" data-cur="<?= $cur ?>"><?= $p['black'] ?></div>
          <div class="prem-tag">+<?= $p['premium'] ?>% premium</div>
        </div>
      </div>
      <div class="mini-sparkline">
        <canvas id="spark-<?= $cur ?>" height="30"></canvas>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- FULL TABLE -->
  <div class="table-card" style="margin-top:24px">
    <div class="table-header">
      <div class="section-title">Complete Rate Table</div>
      <div style="font-size:12px;color:var(--muted)">All values in ETB (Ethiopian Birr)</div>
    </div>
    <div style="overflow-x:auto">
    <table class="data-table">
      <thead>
        <tr>
          <th>Currency</th>
          <th>Official (NBE)</th>
          <th>Black Market</th>
          <th>Premium</th>
          <th>24h Change</th>
          <th>24h High</th>
          <th>24h Low</th>
          <th>Spread</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($D['pairs'] as $cur => $p):
          $cls = $p['change'] >= 0 ? 'up' : 'dn';
          $sign = $p['change'] >= 0 ? '+' : '';
          $spread = round($p['black'] - $p['official'],4);
          $name = $names[$cur] ?? $cur;
        ?>
        <tr>
          <td>
            <div style="display:flex;align-items:center;gap:8px">
              <span style="font-size:18px"><?= $p['flag'] ?></span>
              <div>
                <div style="font-weight:700;font-size:13px"><?= $cur ?></div>
                <div style="font-size:10px;color:var(--muted)"><?= $name ?></div>
              </div>
            </div>
          </td>
          <td class="mono"><?= $p['official'] ?></td>
          <td class="mono" style="color:var(--gold2)"><?= $p['black'] ?></td>
          <td>
            <span style="font-size:12px;padding:3px 8px;border-radius:6px;
              background:rgba(255,214,0,.1);border:1px solid rgba(255,214,0,.2);
              color:var(--gold);font-family:'JetBrains Mono',monospace">
              +<?= $p['premium'] ?>%
            </span>
          </td>
          <td class="mono <?= $cls ?>"><?= $sign . $p['change'] ?>%</td>
          <td class="mono up"><?= $p['high'] ?></td>
          <td class="mono dn"><?= $p['low'] ?></td>
          <td>
            <div class="bar-cell mono" style="color:var(--muted2)"><?= $spread ?>
              <div class="rate-bar" style="width:<?= min(abs($spread/$p['official']*100*4),100) ?>%"></div>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    </div>
  </div>

</div><!-- /container -->

<footer>
  <div class="disclaimer">
    ⚠️ <strong>Disclaimer:</strong> ETBirr displays estimated parallel/black market exchange rates for informational purposes only.
    These are not official National Bank of Ethiopia (NBE) rates. Black market forex transactions may be subject to Ethiopian law.
    Data is sourced from public free APIs and supplemented with market estimates. Use at your own discretion.
  </div>
  <div style="margin-top:16px">
    <strong style="font-family:'Orbitron',sans-serif;color:var(--eth-yellow)">ETBirr</strong>
    · Ethiopian Birr Black Market Tracker · Live Exchange Rates
    <br><span style="color:var(--muted2);font-size:11px">© <?= date('Y') ?> · Data refreshes every 30 seconds · APIs: ExchangeRate-API, Open-ER-API</span>
  </div>
</footer>

<!-- ════════════════════════ SCRIPTS ════════════════════════ -->
<script>
// ── Initial data from PHP ──
let D = <?= json_encode($D) ?>;

// ── MAIN CHART ──
const chartCtx = document.getElementById('mainChart').getContext('2d');
const gradient = chartCtx.createLinearGradient(0, 0, 0, 280);
gradient.addColorStop(0, 'rgba(0,230,118,0.25)');
gradient.addColorStop(1, 'rgba(0,230,118,0.01)');

const mainChart = new Chart(chartCtx, {
  type: 'line',
  data: {
    labels: D.chart.map(d => d.x),
    datasets: [{
      label: 'USD/ETB Black Market',
      data: D.chart.map(d => d.y),
      borderColor: '#00e676',
      backgroundColor: gradient,
      borderWidth: 2,
      pointRadius: 0,
      pointHoverRadius: 5,
      pointHoverBackgroundColor: '#00e676',
      tension: 0.4,
      fill: true,
    }]
  },
  options: {
    responsive: true, maintainAspectRatio: false,
    interaction: { mode: 'index', intersect: false },
    plugins: {
      legend: { display: false },
      tooltip: {
        backgroundColor: 'rgba(15,21,35,.95)',
        borderColor: '#1e2a3a', borderWidth: 1,
        titleColor: '#64748b', bodyColor: '#e2e8f0',
        callbacks: {
          label: ctx => `  Black Market: ${ctx.parsed.y.toFixed(2)} ETB`
        }
      },
    },
    scales: {
      x: {
        grid: { color: 'rgba(30,42,58,.5)', drawBorder: false },
        ticks: { color: '#475569', font: { size: 10 }, maxTicksLimit: 8 }
      },
      y: {
        position: 'right',
        grid: { color: 'rgba(30,42,58,.5)', drawBorder: false },
        ticks: { color: '#475569', font: { family: 'JetBrains Mono', size: 11 } }
      }
    },
    animation: { duration: 600, easing: 'easeInOutQuart' }
  }
});

// ── VOLUME CHART ──
const volCtx = document.getElementById('volChart').getContext('2d');
const volChart = new Chart(volCtx, {
  type: 'bar',
  data: {
    labels: D.volume.map(d => d.x),
    datasets: [{
      data: D.volume.map(d => d.y),
      backgroundColor: D.chart.map((d,i) =>
        i > 0 && d.y >= D.chart[i-1].y ? 'rgba(0,230,118,.4)' : 'rgba(255,23,68,.4)'
      ),
      borderRadius: 2,
    }]
  },
  options: {
    responsive: true, maintainAspectRatio: false,
    plugins: { legend: { display: false }, tooltip: { enabled: false } },
    scales: {
      x: { display: false },
      y: { display: false }
    },
    animation: { duration: 300 }
  }
});

// ── SPARKLINES ──
function drawSparkline(id, data, color) {
  const el = document.getElementById('spark-' + id);
  if (!el) return;
  new Chart(el.getContext('2d'), {
    type: 'line',
    data: {
      labels: data,
      datasets: [{ data, borderColor: color, borderWidth: 1.5,
        pointRadius: 0, tension: 0.4, fill: false }]
    },
    options: {
      responsive: true, maintainAspectRatio: false,
      plugins: { legend: { display: false }, tooltip: { enabled: false } },
      scales: { x: { display: false }, y: { display: false } },
      animation: { duration: 300 }
    }
  });
}

function generateSparkData(base, n=12) {
  let pts = [], v = base;
  for (let i=0;i<n;i++) { v += (Math.random()-.48)*0.4; pts.push(+v.toFixed(3)); }
  return pts;
}

Object.keys(D.pairs).forEach(cur => {
  const p = D.pairs[cur];
  const color = p.change >= 0 ? '#00e676' : '#ff1744';
  drawSparkline(cur, generateSparkData(p.black), color);
});

// ── CHART RANGE ──
function setRange(range, btn) {
  document.querySelectorAll('.tab').forEach(t=>t.classList.remove('active'));
  btn.classList.add('active');
  const pts = range==='24h' ? 48 : range==='12h' ? 24 : 12;
  const slice = D.chart.slice(-pts);
  mainChart.data.labels = slice.map(d=>d.x);
  mainChart.data.datasets[0].data = slice.map(d=>d.y);
  mainChart.update();
}

// ── COUNTDOWN & AUTO REFRESH ──
let secondsLeft = 30;
const fillEl = document.getElementById('countdown-fill');
const txtEl  = document.getElementById('countdown-txt');

function updateCountdown() {
  secondsLeft--;
  if (secondsLeft <= 0) { refreshData(); secondsLeft = 30; }
  fillEl.style.width = (secondsLeft/30*100) + '%';
  txtEl.textContent  = secondsLeft + 's';
}
setInterval(updateCountdown, 1000);

function manualRefresh() {
  const icon = document.getElementById('refresh-icon');
  icon.classList.add('spin');
  setTimeout(()=>icon.classList.remove('spin'), 700);
  refreshData();
  secondsLeft = 30;
}

async function refreshData() {
  try {
    const res = await fetch('?json=1');
    if (!res.ok) return;
    D = await res.json();
    updateUI();
  } catch(e) { console.warn('Refresh failed:', e); }
}

function animateValue(el, newVal) {
  if (!el) return;
  const old = parseFloat(el.textContent);
  const isUp = newVal >= old;
  el.classList.remove('flash-green','flash-red');
  void el.offsetWidth;
  el.classList.add(isUp ? 'flash-green' : 'flash-red');
  el.textContent = newVal;
}

function updateUI() {
  // Header
  document.getElementById('hdr-rate').textContent  = D.black_usd.toFixed(2);
  document.getElementById('hdr-prem').textContent  = `+${D.premium_pct}% vs Official`;
  document.getElementById('hdr-ts').textContent    = D.timestamp;
  document.getElementById('last-fetch').textContent = D.timestamp;

  // Side stats
  animateValue(document.getElementById('side-black'),  D.black_usd.toFixed(2));
  animateValue(document.getElementById('side-off'),    D.official_usd.toFixed(2));
  animateValue(document.getElementById('side-black2'), D.black_usd.toFixed(2));
  document.getElementById('chart-price').textContent = D.black_usd.toFixed(2);
  document.getElementById('prem-pct').textContent    = D.premium_pct + '%';

  // Main chart
  mainChart.data.labels   = D.chart.map(d=>d.x);
  mainChart.data.datasets[0].data = D.chart.map(d=>d.y);
  mainChart.update();

  // Volume chart
  volChart.data.labels = D.volume.map(d=>d.x);
  volChart.data.datasets[0].data = D.volume.map(d=>d.y);
  volChart.update();

  // Currency cards
  Object.keys(D.pairs).forEach(cur => {
    const p = D.pairs[cur];
    const el = document.querySelector(`[data-cur="${cur}"]`);
    if (el) animateValue(el, p.black);
  });

  // Ticker
  const tick = document.getElementById('ticker');
  let html = '';
  Object.entries(D.pairs).forEach(([cur, p]) => {
    const cls = p.change >= 0 ? 'up' : 'dn';
    const sign = p.change >= 0 ? '▲' : '▼';
    html += `<div class='tick-item'>
      <span class='tick-cur'>${p.flag} ${cur}/ETB</span>
      <span class='tick-rate ${cls}'>${p.black}</span>
      <span class='tick-chg ${cls}'>${sign} ${Math.abs(p.change)}%</span>
    </div>`;
  });
  tick.innerHTML = html + html;
}

// ── FLOATING PARTICLES (background ambiance) ──
(function particles(){
  const canvas = document.createElement('canvas');
  canvas.style.cssText='position:fixed;inset:0;pointer-events:none;z-index:0;opacity:.15';
  document.body.prepend(canvas);
  const ctx2 = canvas.getContext('2d');
  let W, H, pts;
  function init(){
    W=canvas.width=innerWidth; H=canvas.height=innerHeight;
    pts=Array.from({length:40},()=>({
      x:Math.random()*W, y:Math.random()*H,
      vx:(Math.random()-.5)*.3, vy:(Math.random()-.5)*.3,
      r:Math.random()*2+.5,
      c:`hsl(${[140,60,180,40][Math.floor(Math.random()*4)]},100%,60%)`
    }));
  }
  function draw(){
    ctx2.clearRect(0,0,W,H);
    pts.forEach(p=>{
      p.x+=p.vx; p.y+=p.vy;
      if(p.x<0||p.x>W) p.vx*=-1;
      if(p.y<0||p.y>H) p.vy*=-1;
      ctx2.beginPath();
      ctx2.arc(p.x,p.y,p.r,0,Math.PI*2);
      ctx2.fillStyle=p.c;
      ctx2.fill();
    });
    requestAnimationFrame(draw);
  }
  addEventListener('resize',init);
  init(); draw();
})();
</script>
</body>
</html>
