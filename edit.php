<?php
require_once 'init.php';
require_once 'csrf.php';
require_once 'id_token.php';

$token = $_GET['t'] ?? '';
$expID = exp_from_token($token);
if (!$expID) die("Invalid link.");

$expStmt = $pdo->prepare("SELECT * FROM driving_experiences WHERE expID = :id");
$expStmt->bindValue(':id', $expID, PDO::PARAM_INT);
$expStmt->execute();
$exp = $expStmt->fetch();
if (!$exp) die("Experience not found.");

$weatherOptions = $pdo->query("SELECT weather_conditionsID AS id, weather_conditionsName AS label FROM weather_conditions ORDER BY weather_conditionsID")->fetchAll();
$speedOptions   = $pdo->query("SELECT speed_limitsID AS id, speed_limitsName AS label FROM speed_limits ORDER BY speed_limitsID")->fetchAll();
$trafficOptions = $pdo->query("SELECT traffic_densitiesID AS id, traffic_densitiesName AS label FROM traffic_densities ORDER BY traffic_densitiesID")->fetchAll();
$visOptions     = $pdo->query("SELECT visibility_conditionsID AS id, visibility_conditionsName AS label FROM visibility_conditions ORDER BY visibility_conditionsID")->fetchAll();
$manOptions     = $pdo->query("SELECT maneuverID AS id, maneuverName AS label FROM maneuvers ORDER BY maneuverName")->fetchAll();

$selStmt = $pdo->prepare("SELECT maneuverID FROM experience_maneuver WHERE expID = :id");
$selStmt->bindValue(':id', $expID, PDO::PARAM_INT);
$selStmt->execute();
$selected = array_map(fn($x) => (int)$x['maneuverID'], $selStmt->fetchAll());

$csrf = csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Edit Driving Experience</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
  body{margin:0;font-family:'Segoe UI',Tahoma;background:url('background1.avif') center/cover no-repeat fixed;color:white;padding:10px;}
  .box{max-width:650px;margin:110px auto 30px;background:rgba(28,7,57,0.75);border:1px solid rgba(255,255,255,0.12);
       border-radius:16px;padding:18px;box-shadow:0 4px 12px rgba(0,0,0,0.4);}
  h1{text-align:center;margin:0 0 14px;text-shadow:0 0 12px rgba(255,255,255,0.35);}
  label{display:block;font-weight:800;margin-top:10px;margin-bottom:6px;}
  input,select{width:100%;padding:10px;border-radius:10px;border:1px solid rgba(255,255,255,0.25);
               background:rgba(255,255,255,0.10);color:white;outline:none;}
  option{background:#1c0739;color:white;}
  .maneuvers{margin-top:8px;padding:12px;border-radius:12px;background:rgba(255,255,255,0.07);border:1px solid rgba(255,255,255,0.12);}
  .grid{display:grid;grid-template-columns:repeat(2, minmax(0,1fr));gap:10px;}
  .pill{display:flex;align-items:center;gap:10px;padding:10px;border-radius:12px;background:rgba(255,255,255,0.08);border:1px solid rgba(255,255,255,0.12);}
  .pill input{transform:scale(1.15);accent-color:#c6abe0;}
  .btnrow{display:flex;gap:10px;flex-wrap:wrap;margin-top:14px;}
  .btn{border:none;border-radius:10px;padding:10px 14px;font-weight:900;cursor:pointer;color:white;
       background:linear-gradient(45deg,#c6abe0,#3e123e);text-decoration:none;transition:0.2s;}
  .btn:hover{transform:translateY(-1px) scale(1.02);box-shadow:0 0 14px rgba(255,255,255,0.25);}
  .btn.secondary{background:rgba(255,255,255,0.14);border:1px solid rgba(255,255,255,0.16);}
  @media(max-width:520px){.grid{grid-template-columns:1fr;}}
</style>
</head>
<body>
<?php include 'nav.php'; ?>

<div class="box">
  <h1>Edit Experience</h1>

  <form action="update.php" method="post">
    <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
    <input type="hidden" name="t" value="<?= htmlspecialchars($token) ?>">

    <label>Date</label>
    <input type="date" name="date" value="<?= htmlspecialchars($exp['date']) ?>" required>

    <label>Start Time</label>
    <input type="time" name="startTime" value="<?= htmlspecialchars($exp['startTime']) ?>" required>

    <label>End Time</label>
    <input type="time" name="endTime" value="<?= htmlspecialchars($exp['endTime']) ?>" required>

    <label>Kilometers</label>
    <input type="number" step="0.01" min="0" inputmode="decimal"
           name="kilometers" value="<?= htmlspecialchars($exp['kilometers']) ?>" required>

    <label>Weather</label>
    <select name="weather_conditionsID" required>
      <?php foreach($weatherOptions as $o): ?>
        <option value="<?= (int)$o['id'] ?>" <?= ((int)$exp['weather_conditionsID']===(int)$o['id'])?'selected':'' ?>>
          <?= htmlspecialchars($o['label']) ?>
        </option>
      <?php endforeach; ?>
    </select>

    <label>Speed limit</label>
    <select name="speed_limitsID" required>
      <?php foreach($speedOptions as $o): ?>
        <option value="<?= (int)$o['id'] ?>" <?= ((int)$exp['speed_limitsID']===(int)$o['id'])?'selected':'' ?>>
          <?= htmlspecialchars($o['label']) ?>
        </option>
      <?php endforeach; ?>
    </select>

    <label>Traffic density</label>
    <select name="traffic_densitiesID" required>
      <?php foreach($trafficOptions as $o): ?>
        <option value="<?= (int)$o['id'] ?>" <?= ((int)$exp['traffic_densitiesID']===(int)$o['id'])?'selected':'' ?>>
          <?= htmlspecialchars($o['label']) ?>
        </option>
      <?php endforeach; ?>
    </select>

    <label>Visibility</label>
    <select name="visibility_conditionsID" required>
      <?php foreach($visOptions as $o): ?>
        <option value="<?= (int)$o['id'] ?>" <?= ((int)$exp['visibility_conditionsID']===(int)$o['id'])?'selected':'' ?>>
          <?= htmlspecialchars($o['label']) ?>
        </option>
      <?php endforeach; ?>
    </select>

    <label>Maneuvers (multiple)</label>
    <div class="maneuvers">
      <div class="grid">
        <?php foreach($manOptions as $o): ?>
          <?php $checked = in_array((int)$o['id'], $selected, true); ?>
          <label class="pill">
            <input type="checkbox" name="maneuvers[]" value="<?= (int)$o['id'] ?>" <?= $checked?'checked':'' ?>>
            <?= htmlspecialchars($o['label']) ?>
          </label>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="btnrow">
      <button class="btn" type="submit">Save changes</button>
      <a class="btn secondary" href="dashboard.php">Cancel</a>
    </div>
  </form>
</div>

</body>
</html>
