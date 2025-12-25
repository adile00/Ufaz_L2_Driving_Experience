<?php
require_once 'init.php';
require_once 'csrf.php';
require_once 'id_token.php';
require_once 'DrivingExperience.php';

/* Filter inputs (GET) */
$dateFrom = $_GET['from'] ?? '';
$dateTo   = $_GET['to'] ?? '';

$weatherF = $_GET['weather'] ?? '';
$speedF   = $_GET['speed'] ?? '';
$trafficF = $_GET['traffic'] ?? '';
$visF     = $_GET['visibility'] ?? '';
$manF     = $_GET['maneuver'] ?? '';

/* Options for filters */
$weatherOptions = $pdo->query("SELECT weather_conditionsID AS id, weather_conditionsName AS label FROM weather_conditions ORDER BY weather_conditionsID")->fetchAll();
$speedOptions   = $pdo->query("SELECT speed_limitsID AS id, speed_limitsName AS label FROM speed_limits ORDER BY speed_limitsID")->fetchAll();
$trafficOptions = $pdo->query("SELECT traffic_densitiesID AS id, traffic_densitiesName AS label FROM traffic_densities ORDER BY traffic_densitiesID")->fetchAll();
$visOptions     = $pdo->query("SELECT visibility_conditionsID AS id, visibility_conditionsName AS label FROM visibility_conditions ORDER BY visibility_conditionsID")->fetchAll();
$manOptions     = $pdo->query("SELECT maneuverID AS id, maneuverName AS label FROM maneuvers ORDER BY maneuverName")->fetchAll();

/* Build WHERE dynamically + prepared params */
$where = [];
$params = [];

if ($dateFrom !== '') { $where[] = "de.date >= :from"; $params[':from'] = $dateFrom; }
if ($dateTo !== '')   { $where[] = "de.date <= :to";   $params[':to']   = $dateTo; }

if (ctype_digit((string)$weatherF)) { $where[] = "de.weather_conditionsID = :w"; $params[':w'] = (int)$weatherF; }
if (ctype_digit((string)$speedF))   { $where[] = "de.speed_limitsID = :s";      $params[':s'] = (int)$speedF; }
if (ctype_digit((string)$trafficF)) { $where[] = "de.traffic_densitiesID = :t"; $params[':t'] = (int)$trafficF; }
if (ctype_digit((string)$visF))     { $where[] = "de.visibility_conditionsID = :v"; $params[':v'] = (int)$visF; }

if (ctype_digit((string)$manF)) {
    $where[] = "EXISTS (
        SELECT 1 FROM experience_maneuver em2
        WHERE em2.expID = de.expID
        AND em2.maneuverID = :mf
    )";
    $params[':mf'] = (int)$manF;
}


$whereSQL = $where ? ("WHERE " . implode(" AND ", $where)) : "";

/* Main query  */
$sql = "
SELECT 
  de.expID, de.date, de.startTime, de.endTime, de.kilometers,
  wc.weather_conditionsName AS weather_name,
  sl.speed_limitsName AS speed_name,
  td.traffic_densitiesName AS traffic_name,
  vc.visibility_conditionsName AS visibility_name,
  GROUP_CONCAT(m.maneuverName ORDER BY m.maneuverName SEPARATOR ', ') AS maneuvers
FROM driving_experiences de
LEFT JOIN weather_conditions wc ON de.weather_conditionsID = wc.weather_conditionsID
LEFT JOIN speed_limits sl ON de.speed_limitsID = sl.speed_limitsID
LEFT JOIN traffic_densities td ON de.traffic_densitiesID = td.traffic_densitiesID
LEFT JOIN visibility_conditions vc ON de.visibility_conditionsID = vc.visibility_conditionsID
LEFT JOIN experience_maneuver em ON de.expID = em.expID
LEFT JOIN maneuvers m ON em.maneuverID = m.maneuverID
$whereSQL
GROUP BY de.expID
ORDER BY de.date DESC, de.startTime DESC
";

$stmt = $pdo->prepare($sql);
foreach ($params as $k => $v) {
    if (is_int($v)) $stmt->bindValue($k, $v, PDO::PARAM_INT);
    else $stmt->bindValue($k, $v);
}
$stmt->execute();
$rows = $stmt->fetchAll();

/* OOP objects for totals */
$objs = array_map(fn($r) => new DrivingExperience($r), $rows);

$totalKm = array_sum(array_map(fn($o) => $o->kilometers, $objs));
$totalHours = array_sum(array_map(fn($o) => $o->durationHours(), $objs));

/* Chart data: weather + traffic counts from filtered data */
$weatherCounts = [];
$trafficCounts = [];
foreach ($rows as $r) {
    $w = $r['weather_name'] ?? 'Unknown';
    $t = $r['traffic_name'] ?? 'Unknown';
    $weatherCounts[$w] = ($weatherCounts[$w] ?? 0) + 1;
    $trafficCounts[$t] = ($trafficCounts[$t] ?? 0) + 1;
}

/* Maneuvers stats: count (filtered by date + variable filters too) */
$mWhere = [];
$mParams = [];
if ($dateFrom !== '') { $mWhere[] = "de.date >= :from"; $mParams[':from'] = $dateFrom; }
if ($dateTo !== '')   { $mWhere[] = "de.date <= :to";   $mParams[':to']   = $dateTo; }
if (ctype_digit((string)$weatherF)) { $mWhere[] = "de.weather_conditionsID = :w"; $mParams[':w'] = (int)$weatherF; }
if (ctype_digit((string)$speedF))   { $mWhere[] = "de.speed_limitsID = :s";      $mParams[':s'] = (int)$speedF; }
if (ctype_digit((string)$trafficF)) { $mWhere[] = "de.traffic_densitiesID = :t"; $mParams[':t'] = (int)$trafficF; }
if (ctype_digit((string)$visF))     { $mWhere[] = "de.visibility_conditionsID = :v"; $mParams[':v'] = (int)$visF; }

$mWhereSQL = $mWhere ? ("WHERE " . implode(" AND ", $mWhere)) : "";

$mSQL = "
SELECT m.maneuverName AS name, COUNT(*) AS cnt
FROM experience_maneuver em
JOIN maneuvers m ON em.maneuverID = m.maneuverID
JOIN driving_experiences de ON de.expID = em.expID
$mWhereSQL
GROUP BY m.maneuverID, m.maneuverName
ORDER BY cnt DESC, name ASC
";
$mStmt = $pdo->prepare($mSQL);
foreach ($mParams as $k => $v) {
    if (is_int($v)) $mStmt->bindValue($k, $v, PDO::PARAM_INT);
    else $mStmt->bindValue($k, $v);
}
$mStmt->execute();
$mRows = $mStmt->fetchAll();
$mLabels = array_map(fn($x) => $x['name'], $mRows);
$mCounts = array_map(fn($x) => (int)$x['cnt'], $mRows);

/* Total KM evolution graph (by date) */
$eSQL = "
SELECT de.date AS d, SUM(de.kilometers) AS km
FROM driving_experiences de
$whereSQL
GROUP BY de.date
ORDER BY de.date ASC
";
$eStmt = $pdo->prepare($eSQL);
foreach ($params as $k => $v) {
    if (is_int($v)) $eStmt->bindValue($k, $v, PDO::PARAM_INT);
    else $eStmt->bindValue($k, $v);
}
$eStmt->execute();
$eRows = $eStmt->fetchAll();
$eDates = array_map(fn($x) => $x['d'], $eRows);
$eKm    = array_map(fn($x) => (float)$x['km'], $eRows);

$csrf = csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Driving Dashboard</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<!-- ChartJS -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<!-- jQuery + DataTables -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css">
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>

<style>
  * { box-sizing: border-box; }
  body { margin:0; font-family:'Segoe UI',Tahoma; background:url('background1.avif') center/cover no-repeat fixed; color:white; }
  
  main { width:92%; max-width: 1200px; margin:110px auto 50px; padding: 0 10px; }

  header { text-align:center; margin-bottom:24px; }
  h1 { font-size:32px; margin-bottom:8px; text-shadow:0 0 12px rgba(255,255,255,0.35); }
  .dashboard-subtitle { font-size:16px; color:rgba(255,255,255,0.8); }

  /* Summary cards */
  .summary-grid { display:grid; grid-template-columns: repeat(3, minmax(0,1fr)); gap:14px; margin-bottom:20px; }
  .summary-box {
    background: rgba(28,7,57,0.75);
    padding: 16px; border-radius: 14px; text-align:center;
    box-shadow:0 4px 12px rgba(0,0,0,0.4);
    transition:0.2s ease;
    border:1px solid rgba(255,255,255,0.12);
  }
  .summary-box:hover { transform: translateY(-2px) scale(1.02); box-shadow:0 0 18px rgba(255,255,255,0.22); }
  .summary-box h2 { font-size:16px; margin:0 0 8px; color:rgba(255,255,255,0.9); }
  .summary-box p { font-size:20px; font-weight:800; margin:0; color:#c6abe0; }

  /* Filters section */
  section.filters-panel {
    background: rgba(28,7,57,0.75);
    border:1px solid rgba(255,255,255,0.12);
    border-radius: 14px;
    padding: 20px;
    box-shadow:0 4px 12px rgba(0,0,0,0.4);
    margin-bottom: 20px;
  }
  
  .filters-panel h2 {
    font-size: 20px;
    margin-bottom: 16px;
    color: #c6abe0;
  }

  .filters-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
    margin-bottom: 12px;
  }

  .filters-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 12px;
  }

  .filters-row > fieldset,
  .filters-grid > fieldset {
    min-width: 0;
    border: none;
    padding: 0;
    margin: 0;
  }

  label { font-weight:700; font-size:14px; display:block; margin-bottom:6px; color:rgba(255,255,255,0.95); }
  input, select {
    width:100%; 
    padding:12px; 
    border-radius:10px;
    border:1px solid rgba(255,255,255,0.25);
    background: rgba(255,255,255,0.10);
    color:white; 
    outline:none;
    font-size: 16px;
    min-height: 44px;
  }
  
  input::placeholder {
    color: rgba(255,255,255,0.5);
  }
  
  input:focus, select:focus {
    border-color: #c6abe0;
    box-shadow: 0 0 10px rgba(198, 171, 224, 0.3);
  }
  
  option { background:#1c0739; color:white; }

  .btnrow { display:flex; gap:10px; margin-top: 16px; flex-wrap: wrap; }
  .btn {
    border:none; border-radius: 10px; padding: 12px 18px;
    font-weight:800; cursor:pointer; color:white;
    background: linear-gradient(45deg,#c6abe0,#3e123e);
    transition:0.2s ease;
    text-decoration:none; display:inline-block;
    font-size: 16px;
    min-height: 44px;
    line-height: 20px;
    text-align: center;
  }
  .btn:hover { transform: translateY(-1px) scale(1.02); box-shadow:0 0 14px rgba(255,255,255,0.25); }
  .btn.secondary { background: rgba(255,255,255,0.14); border:1px solid rgba(255,255,255,0.16); }

  /* Charts section */
  section.charts-section {
    margin-bottom: 20px;
  }
  
  .charts-section h2 {
    font-size: 22px;
    margin-bottom: 16px;
    text-align: center;
    color: #c6abe0;
  }

  .charts-grid { display:grid; grid-template-columns: repeat(2, minmax(0,1fr)); gap: 16px; }
  .chart-box { background: rgba(28,7,57,0.75); border:1px solid rgba(255,255,255,0.12); border-radius: 14px; padding: 16px; }
  .chart-title { text-align:center; font-weight:900; margin-bottom: 10px; font-size: 17px; color:rgba(255,255,255,0.95); }
  canvas { width:100% !important; max-height: 280px; }

  /* Table section */
  section.table-section {
    background: rgba(28,7,57,0.70); 
    border:1px solid rgba(255,255,255,0.12); 
    border-radius:14px; 
    padding: 16px;
  }
  
  .table-section h2 {
    font-size: 22px;
    margin-bottom: 16px;
    text-align: center;
    color: #c6abe0;
  }

  .table-wrap { 
    overflow-x:auto;
    -webkit-overflow-scrolling: touch;
  }
  
  /* Desktop table */
  .desktop-table { display: block; }
  .mobile-cards { display: none; }
  
  table { width:100%; min-width: 980px; border-collapse: collapse; color:white; }

  /* DataTables overrides to match theme */
  .dataTables_wrapper .dataTables_filter input,
  .dataTables_wrapper .dataTables_length select {
    background: rgba(255,255,255,0.10) !important;
    color: white !important;
    border: 1px solid rgba(255,255,255,0.25) !important;
    border-radius: 10px !important;
    padding: 8px 12px !important;
    font-size: 16px !important;
  }
  .dataTables_wrapper .dataTables_info,
  .dataTables_wrapper .dataTables_paginate,
  .dataTables_wrapper .dataTables_length,
  .dataTables_wrapper .dataTables_filter {
    color: white !important;
  }
  table.dataTable thead th { background: rgba(62,18,62,0.9); }

  th, td { padding: 12px; border-bottom: 1px solid rgba(255,255,255,0.18); text-align:center; white-space:nowrap; }
  tr:hover { background: rgba(255,255,255,0.08); }

  /* Mobile cards layout */
  .exp-card {
    background: rgba(62,18,62,0.6);
    border: 1px solid rgba(255,255,255,0.12);
    border-radius: 12px;
    padding: 16px;
    margin-bottom: 12px;
  }
  
  .exp-card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 12px;
    padding-bottom: 12px;
    border-bottom: 1px solid rgba(255,255,255,0.12);
  }
  
  .exp-card-date {
    font-size: 18px;
    font-weight: 800;
  }
  
  .exp-card-km {
    font-size: 16px;
    color: #c6abe0;
    font-weight: 700;
  }
  
  .exp-card-row {
    display: grid;
    grid-template-columns: 120px 1fr;
    gap: 8px;
    margin-bottom: 8px;
    font-size: 14px;
  }
  
  .exp-card-label {
    font-weight: 700;
    color: rgba(255,255,255,0.8);
  }
  
  .exp-card-value {
    color: white;
  }
  
  .exp-card-actions {
    display: flex;
    gap: 8px;
    margin-top: 12px;
    padding-top: 12px;
    border-top: 1px solid rgba(255,255,255,0.12);
  }
  
  .exp-card-actions .btn {
    flex: 1;
  }
  
  .exp-card-actions form {
    flex: 1;
  }

  @media (max-width: 900px) {
    .summary-grid { grid-template-columns: 1fr; }
    .charts-grid { grid-template-columns: 1fr; }
    .filters-grid { grid-template-columns: 1fr; }
    h1 { font-size: 28px; }
    main { margin-top: 80px; }
  }
  
  @media (max-width: 768px) {
    .desktop-table { display: none; }
    .mobile-cards { display: block; }
    .table-wrap { 
      overflow-x: visible;
    }
    .summary-box h2 { font-size: 14px; }
    .summary-box p { font-size: 18px; }
    section.filters-panel { padding: 16px; }
    h1 { font-size: 26px; margin-bottom: 8px; }
    .dashboard-subtitle { font-size: 14px; }
    .chart-box { padding: 12px; }
    canvas { max-height: 240px !important; }
    .filters-row { grid-template-columns: 1fr; }
  }
  
  @media (max-width: 480px) {
    main { width: 96%; padding: 0 5px; }
    h1 { font-size: 24px; }
    .summary-box { padding: 14px; }
    .summary-box h2 { font-size: 13px; }
    .summary-box p { font-size: 17px; }
    .btn { padding: 11px 15px; font-size: 15px; }
    input, select { padding: 11px; font-size: 16px; }
    .chart-title { font-size: 16px; }
  }
</style>
</head>

<body>
<?php include 'nav.php'; ?>

<main>
  <header>
    <h1>Driving Dashboard</h1>
    <p class="dashboard-subtitle">Track and analyze your driving experiences</p>
  </header>

  <section class="summary-grid" aria-label="Statistics Summary">
    <article class="summary-box">
      <h2>Total Drives</h2>
      <p><?= count($rows) ?></p>
    </article>
    <article class="summary-box">
      <h2>Total Kilometers</h2>
      <p><?= number_format($totalKm,2) ?> km</p>
    </article>
    <article class="summary-box">
      <h2>Total Hours</h2>
      <p><?= number_format($totalHours,2) ?> h</p>
    </article>
  </section>

  <!-- FILTERS -->
  <section class="filters-panel" aria-labelledby="filters-heading">
    <h2 id="filters-heading">Filter Driving Experiences</h2>
    <form method="get" action="dashboard.php">

      <!-- Dates row -->
      <div class="filters-row">
        <fieldset>
          <label for="from">From date</label>
          <input id="from" type="date" name="from" value="<?= htmlspecialchars($dateFrom) ?>" placeholder="Start date">
        </fieldset>
        <fieldset>
          <label for="to">To date</label>
          <input id="to" type="date" name="to" value="<?= htmlspecialchars($dateTo) ?>" placeholder="End date">
        </fieldset>
      </div>

      <!-- Other filters -->
      <div class="filters-grid">
        <fieldset>
          <label for="maneuver">Maneuver</label>
          <select id="maneuver" name="maneuver">
            <option value="">All maneuvers</option>
            <?php foreach ($manOptions as $o): ?>
              <option value="<?= (int)$o['id'] ?>" <?= ((string)$manF === (string)$o['id']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($o['label']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </fieldset>

        <fieldset>
          <label for="weather">Weather</label>
          <select id="weather" name="weather">
            <option value="">All weather</option>
            <?php foreach ($weatherOptions as $o): ?>
              <option value="<?= (int)$o['id'] ?>" <?= ((string)$weatherF === (string)$o['id']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($o['label']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </fieldset>

        <fieldset>
          <label for="speed">Speed limit</label>
          <select id="speed" name="speed">
            <option value="">All speeds</option>
            <?php foreach ($speedOptions as $o): ?>
              <option value="<?= (int)$o['id'] ?>" <?= ((string)$speedF === (string)$o['id']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($o['label']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </fieldset>

        <fieldset>
          <label for="traffic">Traffic density</label>
          <select id="traffic" name="traffic">
            <option value="">All traffic</option>
            <?php foreach ($trafficOptions as $o): ?>
              <option value="<?= (int)$o['id'] ?>" <?= ((string)$trafficF === (string)$o['id']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($o['label']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </fieldset>

        <fieldset>
          <label for="visibility">Visibility</label>
          <select id="visibility" name="visibility">
            <option value="">All visibility</option>
            <?php foreach ($visOptions as $o): ?>
              <option value="<?= (int)$o['id'] ?>" <?= ((string)$visF === (string)$o['id']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($o['label']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </fieldset>
      </div>

      <div class="btnrow">
        <button class="btn" type="submit">Apply Filters</button>
        <a class="btn secondary" href="dashboard.php">Reset Filters</a>
      </div>

    </form>
  </section>

  <!-- CHARTS -->
  <section class="charts-section" aria-labelledby="charts-heading">
    <h2 id="charts-heading">Visual Analytics</h2>
    <div class="charts-grid">
      <article class="chart-box">
        <h3 class="chart-title">Weather Distribution</h3>
        <canvas id="weatherChart" aria-label="Pie chart showing weather conditions distribution"></canvas>
      </article>
      <article class="chart-box">
        <h3 class="chart-title">Traffic Density</h3>
        <canvas id="trafficChart" aria-label="Pie chart showing traffic density distribution"></canvas>
      </article>

      <article class="chart-box" style="grid-column: 1 / -1;">
        <h3 class="chart-title">Total Kilometers Evolution (by date)</h3>
        <canvas id="evolutionChart" aria-label="Line chart showing kilometers evolution over time"></canvas>
      </article>

      <article class="chart-box" style="grid-column: 1 / -1;">
        <h3 class="chart-title">Most Performed Maneuvers</h3>
        <canvas id="maneuversChart" aria-label="Bar chart showing most performed maneuvers"></canvas>
      </article>
    </div>
  </section>

  <!-- TABLE with DataTables (Desktop) -->
  <section class="table-section" aria-labelledby="table-heading">
    <h2 id="table-heading">Driving Experiences List</h2>
    <div class="table-wrap desktop-table">
      <table id="expTable" class="display">
        <thead>
          <tr>
            <th>Date</th><th>Start</th><th>End</th><th>Km</th>
            <th>Weather</th><th>Speed</th><th>Traffic</th><th>Visibility</th>
            <th>Maneuvers</th><th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rows as $r): ?>
            <?php $tok = token_for_exp((int)$r['expID']); ?>
            <tr>
              <td><?= htmlspecialchars($r['date']) ?></td>
              <td><?= htmlspecialchars($r['startTime']) ?></td>
              <td><?= htmlspecialchars($r['endTime']) ?></td>
              <td><?= htmlspecialchars($r['kilometers']) ?></td>
              <td><?= htmlspecialchars($r['weather_name'] ?? '—') ?></td>
              <td><?= htmlspecialchars($r['speed_name'] ?? '—') ?></td>
              <td><?= htmlspecialchars($r['traffic_name'] ?? '—') ?></td>
              <td><?= htmlspecialchars($r['visibility_name'] ?? '—') ?></td>
              <td><?= htmlspecialchars($r['maneuvers'] ?: '—') ?></td>
              <td>
                <a class="btn" href="edit.php?t=<?= htmlspecialchars($tok) ?>">Edit</a>
                <form action="delete.php" method="post" style="display:inline;">
                  <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
                  <input type="hidden" name="t" value="<?= htmlspecialchars($tok) ?>">
                  <button class="btn secondary" type="submit" onclick="return confirm('Delete this experience?');">Delete</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    
    <!-- MOBILE CARDS VIEW -->
    <div class="mobile-cards">
      <?php foreach ($rows as $r): ?>
        <?php $tok = token_for_exp((int)$r['expID']); ?>
        <article class="exp-card">
          <header class="exp-card-header">
            <div class="exp-card-date"><?= htmlspecialchars($r['date']) ?></div>
            <div class="exp-card-km"><?= htmlspecialchars($r['kilometers']) ?> km</div>
          </header>
          
          <div class="exp-card-row">
            <div class="exp-card-label">Time:</div>
            <div class="exp-card-value"><?= htmlspecialchars($r['startTime']) ?> - <?= htmlspecialchars($r['endTime']) ?></div>
          </div>
          
          <div class="exp-card-row">
            <div class="exp-card-label">Weather:</div>
            <div class="exp-card-value"><?= htmlspecialchars($r['weather_name'] ?? '—') ?></div>
          </div>
          
          <div class="exp-card-row">
            <div class="exp-card-label">Speed limit:</div>
            <div class="exp-card-value"><?= htmlspecialchars($r['speed_name'] ?? '—') ?></div>
          </div>
          
          <div class="exp-card-row">
            <div class="exp-card-label">Traffic:</div>
            <div class="exp-card-value"><?= htmlspecialchars($r['traffic_name'] ?? '—') ?></div>
          </div>

          <div class="exp-card-row">
        <div class="exp-card-label">Visibility:</div>
        <div class="exp-card-value"><?= htmlspecialchars($r['visibility_name'] ?? '—') ?></div>
      </div>
      
      <div class="exp-card-row">
        <div class="exp-card-label">Maneuvers:</div>
        <div class="exp-card-value"><?= htmlspecialchars($r['maneuvers'] ?: '—') ?></div>
      </div>
      
      <footer class="exp-card-actions">
        <a class="btn" href="edit.php?t=<?= htmlspecialchars($tok) ?>">Edit</a>
        <form action="delete.php" method="post">
          <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
          <input type="hidden" name="t" value="<?= htmlspecialchars($tok) ?>">
          <button class="btn secondary" type="submit" onclick="return confirm('Delete this experience?');">Delete</button>
        </form>
      </footer>
    </article>
  <?php endforeach; ?>
</div>

<script>
$(function() {
  // Only initialize DataTables on desktop
  if (window.innerWidth > 768) {
    $('#expTable').DataTable({
      pageLength: 10,
      lengthMenu: [5, 10, 25, 50],
      order: [[0, 'desc']],
      columnDefs: [
      { orderable: true, targets: [0,1,2,3] }, // date, start, end, km
      { orderable: false, targets: '_all' }
    ]
    });
  }
});

new Chart(document.getElementById('weatherChart'), {
  type: 'pie',
  data: { labels: <?= json_encode(array_keys($weatherCounts)) ?>,
    datasets: [{ data: <?= json_encode(array_values($weatherCounts)) ?>,
      backgroundColor: ['#ffbc00','#00b0ff','#ff4d4d','#ff7f50','#808080','#c2c2c2'] }] },
  options: { 
    responsive: true, 
    maintainAspectRatio: true,
    plugins: {
      legend: {
        position: 'bottom',
        labels: { color: 'white', font: { size: 11 } }
      }
    }
  }
});

new Chart(document.getElementById('trafficChart'), {
  type: 'pie',
  data: { labels: <?= json_encode(array_keys($trafficCounts)) ?>,
    datasets: [{ data: <?= json_encode(array_values($trafficCounts)) ?>,
      backgroundColor: ['#00ff99','#ff9933','#ff3333','#9999ff','#ff66cc'] }] },
  options: { 
    responsive: true, 
    maintainAspectRatio: true,
    plugins: {
      legend: {
        position: 'bottom',
        labels: { color: 'white', font: { size: 11 } }
      }
    }
  }
});

new Chart(document.getElementById('evolutionChart'), {
  type: 'line',
  data: {
    labels: <?= json_encode($eDates) ?>,
    datasets: [{
      label: 'Km per day',
      data: <?= json_encode($eKm) ?>,
      tension: 0.25,
      fill: false,
      borderColor: '#c6abe0',
      backgroundColor: '#c6abe0'
    }]
  },
  options: { 
    responsive: true, 
    maintainAspectRatio: true,
    scales: { 
      y: { 
        beginAtZero: true,
        ticks: { color: 'white' },
        grid: { color: 'rgba(255,255,255,0.1)' }
      },
      x: {
        ticks: { color: 'white' },
        grid: { color: 'rgba(255,255,255,0.1)' }
      }
    },
    plugins: {
      legend: {
        labels: { color: 'white' }
      }
    }
  }
});

new Chart(document.getElementById('maneuversChart'), {
  type: 'bar',
  data: {
    labels: <?= json_encode($mLabels) ?>,
    datasets: [{
      label: 'Count',
      data: <?= json_encode($mCounts) ?>,
      backgroundColor: '#c6abe0'
    }]
  },
  options: { 
    responsive: true, 
    maintainAspectRatio: true,
    scales: { 
      y: { 
        beginAtZero: true,
        ticks: { color: 'white' },
        grid: { color: 'rgba(255,255,255,0.1)' }
      },
      x: {
        ticks: { color: 'white' },
        grid: { color: 'rgba(255,255,255,0.1)' }
      }
    },
    plugins: {
      legend: {
        labels: { color: 'white' }
      }
    }
  }
});
</script>

</body>
</html>