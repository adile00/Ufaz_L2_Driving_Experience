<?php
require_once 'init.php';
require_once 'csrf.php';
$csrf = csrf_token();

$weatherOptions = $pdo->query("
  SELECT weather_conditionsID AS id, weather_conditionsName AS label
  FROM weather_conditions
  ORDER BY weather_conditionsID
")->fetchAll();

$speedOptions = $pdo->query("
  SELECT speed_limitsID AS id, speed_limitsName AS label
  FROM speed_limits
  ORDER BY speed_limitsID
")->fetchAll();

$trafficOptions = $pdo->query("
  SELECT traffic_densitiesID AS id, traffic_densitiesName AS label
  FROM traffic_densities
  ORDER BY traffic_densitiesID
")->fetchAll();

$visibilityOptions = $pdo->query("
  SELECT visibility_conditionsID AS id, visibility_conditionsName AS label
  FROM visibility_conditions
  ORDER BY visibility_conditionsID
")->fetchAll();

$maneuverOptions = $pdo->query("
  SELECT maneuverID, maneuverName
  FROM maneuvers
  ORDER BY maneuverName
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Add Driving Experience</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<style>
  * {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
  }

  body {
    display: flex;
    flex-direction: column;
    align-items: center;
    min-height: 100vh;
    background-image: url('background1.avif');
    background-size: cover;
    background-position: center;
    background-repeat: no-repeat;
    color: #ffffff;
    padding: 10px;
  }

  main {
    width: 100%;
    max-width: 600px;
    margin: 80px 0 20px 0;
  }

  section {
    background: rgba(255, 255, 255, 0.1);
    padding: 24px;
    border-radius: 12px;
    box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.4);
  }

  header {
    text-align: center;
    margin-bottom: 24px;
  }

  h1 {
    font-size: 28px;
    font-weight: 700;
    color: #ffffff;
    text-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
    margin-bottom: 8px;
  }

  .form-subtitle {
    font-size: 16px;
    color: rgba(255, 255, 255, 0.85);
    font-weight: 400;
  }

  label {
    display: block;
    margin: 14px 0 6px;
    font-size: 18px;
    color: #ffffff;
    font-weight: 600;
  }

  input, select {
    width: 100%;
    padding: 12px;
    margin-top: 5px;
    border-radius: 8px;
    border: 1px solid rgba(255, 255, 255, 0.3);
    font-size: 16px;
    color: #ffffff;
    background-color: rgba(255, 255, 255, 0.1);
    outline: none;
    transition: border-color 0.3s ease, transform 0.2s ease, box-shadow 0.2s ease;
    min-height: 44px;
  }

  input:focus, select:focus { 
    border-color: #c6abe0;
    box-shadow: 0 0 12px rgba(198, 171, 224, 0.4);
  }
  
  input:hover, select:hover {
    transform: scale(1.01);
    box-shadow: 0px 0px 10px rgba(255, 255, 255, 0.3);
  }

  input::placeholder {
    color: rgba(255, 255, 255, 0.5);
    font-style: italic;
  }

  option { background-color: #1c0739; color: #ffffff; }

  /* ===== Pretty maneuvers ===== */
  .maneuvers-section {
    margin-top: 20px;
    padding: 16px;
    border-radius: 12px;
    background: rgba(28, 7, 57, 0.35);
    border: 1px solid rgba(255,255,255,0.12);
  }

  .maneuvers-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 12px;
  }

  .maneuvers-label {
    font-size: 18px;
    font-weight: 600;
    color: #ffffff;
  }

  .maneuvers-count {
    font-size: 14px;
    padding: 4px 12px;
    background: rgba(198, 171, 224, 0.25);
    border-radius: 20px;
    font-weight: 600;
    color: #c6abe0;
    transition: all 0.3s ease;
  }

  .maneuvers-count.active {
    background: rgba(198, 171, 224, 0.4);
    box-shadow: 0 0 12px rgba(198, 171, 224, 0.4);
  }

  .maneuvers-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 10px;
  }

  .maneuver-pill {
    position: relative;
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 14px;
    border-radius: 12px;
    background: rgba(255,255,255,0.08);
    border: 2px solid rgba(255,255,255,0.12);
    cursor: pointer;
    transition: all 0.3s ease;
    user-select: none;
  }

  .maneuver-pill:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 16px rgba(198, 171, 224, 0.3);
    border-color: rgba(198, 171, 224, 0.5);
  }

  /* Hide default checkbox */
  .maneuver-pill input[type="checkbox"] {
    position: absolute;
    opacity: 0;
    cursor: pointer;
    height: 0;
    width: 0;
  }

  /* Custom checkbox */
  .custom-checkbox {
    position: relative;
    width: 22px;
    height: 22px;
    min-width: 22px;
    border: 2px solid rgba(255, 255, 255, 0.4);
    border-radius: 6px;
    background: rgba(255, 255, 255, 0.05);
    transition: all 0.3s ease;
  }

  /* Checkmark */
  .custom-checkbox::after {
    content: '';
    position: absolute;
    display: none;
    left: 6px;
    top: 2px;
    width: 6px;
    height: 12px;
    border: solid white;
    border-width: 0 3px 3px 0;
    transform: rotate(45deg);
  }

  /* When checked */
  .maneuver-pill input[type="checkbox"]:checked ~ .custom-checkbox {
    background: linear-gradient(135deg, #c6abe0, #7b4ba6);
    border-color: #c6abe0;
    box-shadow: 0 0 12px rgba(198, 171, 224, 0.6);
  }

  .maneuver-pill input[type="checkbox"]:checked ~ .custom-checkbox::after {
    display: block;
  }

  /* Checked pill styling */
  .maneuver-pill:has(input[type="checkbox"]:checked) {
    background: linear-gradient(135deg, rgba(198, 171, 224, 0.25), rgba(123, 75, 166, 0.15));
    border-color: rgba(198, 171, 224, 0.6);
    box-shadow: 0 0 20px rgba(198, 171, 224, 0.4);
    transform: translateY(-1px);
  }

  .maneuver-pill:has(input[type="checkbox"]:checked):hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 20px rgba(198, 171, 224, 0.5);
  }

  .maneuver-text {
    font-size: 16px;
    font-weight: 600;
    flex: 1;
    transition: color 0.3s ease;
  }

  .maneuver-pill:has(input[type="checkbox"]:checked) .maneuver-text {
    color: #ffffff;
    text-shadow: 0 0 8px rgba(198, 171, 224, 0.5);
  }

  button {
    width: 100%;
    padding: 14px;
    margin-top: 24px;
    border: none;
    background: linear-gradient(45deg,#c6abe0, #3e123e);
    color: #ffffff;
    font-size: 18px;
    font-weight: bold;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s ease;
    min-height: 48px;
  }

  button:hover {
    background: linear-gradient(45deg, #7b4ba6, #250028);
    transform: translateY(-2px);
    box-shadow: 0 4px 20px rgba(198, 171, 224, 0.5);
  }

  button:active {
    transform: translateY(0);
  }

  /* ===== Mobile tweaks ===== */
  @media (max-width: 480px) {
    section { padding: 18px; }
    h1 { font-size: 24px; }
    .form-subtitle { font-size: 14px; }
    label { font-size: 16px; }
    input, select { font-size: 16px; padding: 11px; }
    .maneuvers-grid { grid-template-columns: 1fr; }
    .maneuver-text { font-size: 15px; }
    .maneuver-pill { padding: 11px 12px; }
    .custom-checkbox { width: 20px; height: 20px; min-width: 20px; }
  }
</style>
</head>

<body>
<?php include 'nav.php'; ?>

<main>
  <section>
    <header>
      <h1>Add Driving Experience</h1>
      <p class="form-subtitle">Record your driving session details</p>
    </header>

    <form id="dynamicForm" action="insert.php" method="post">
    <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">

      <label for="date">Date:</label>
      <input 
        id="date" 
        type="date" 
        name="date" 
        required
        aria-label="Date of driving experience"
      >

      <label for="startTime">Start Time:</label>
      <input 
        id="startTime" 
        type="time" 
        name="startTime" 
        required
        placeholder="HH:MM"
        aria-label="Start time of driving"
      >

      <label for="endTime">End Time:</label>
      <input 
        id="endTime" 
        type="time" 
        name="endTime" 
        required
        placeholder="HH:MM"
        aria-label="End time of driving"
      >

      <label for="kilometers">Distance (km):</label>
      <input
        id="kilometers"
        type="number"
        name="kilometers"
        min="0"
        step="0.01"
        inputmode="decimal"
        placeholder="e.g. 12.5"
        required
        aria-label="Distance covered in kilometers"
      >

      <label for="weather">Weather Conditions:</label>
      <select id="weather" name="weather_conditionsID" required aria-label="Select weather conditions">
        <option value="">-- Select weather --</option>
        <?php foreach ($weatherOptions as $opt): ?>
          <option value="<?= htmlspecialchars($opt['id']) ?>">
            <?= htmlspecialchars($opt['label']) ?>
          </option>
        <?php endforeach; ?>
      </select>

      <label for="speedLimit">Speed Limit:</label>
      <select id="speedLimit" name="speed_limitsID" required aria-label="Select speed limit">
        <option value="">-- Select speed limit --</option>
        <?php foreach ($speedOptions as $opt): ?>
          <option value="<?= htmlspecialchars($opt['id']) ?>">
            <?= htmlspecialchars($opt['label']) ?>
          </option>
        <?php endforeach; ?>
      </select>

      <label for="traffic">Traffic Density:</label>
      <select id="traffic" name="traffic_densitiesID" required aria-label="Select traffic density">
        <option value="">-- Select traffic density --</option>
        <?php foreach ($trafficOptions as $opt): ?>
          <option value="<?= htmlspecialchars($opt['id']) ?>">
            <?= htmlspecialchars($opt['label']) ?>
          </option>
        <?php endforeach; ?>
      </select>

      <label for="visibility">Visibility Conditions:</label>
      <select id="visibility" name="visibility_conditionsID" required aria-label="Select visibility conditions">
        <option value="">-- Select visibility --</option>
        <?php foreach ($visibilityOptions as $opt): ?>
          <option value="<?= htmlspecialchars($opt['id']) ?>">
            <?= htmlspecialchars($opt['label']) ?>
          </option>
        <?php endforeach; ?>
      </select>

      <section class="maneuvers-section" aria-labelledby="maneuvers-legend">
        <header class="maneuvers-header">
          <span class="maneuvers-label" id="maneuvers-legend">Maneuvers Performed</span>
          <span class="maneuvers-count" id="maneuverCount">0 selected</span>
        </header>
        <fieldset style="border: none; padding: 0; margin: 0;">
          <legend style="display: none;">Select maneuvers performed during driving</legend>
          <div class="maneuvers-grid">
            <?php foreach ($maneuverOptions as $m): ?>
              <label class="maneuver-pill" for="maneuver_<?= htmlspecialchars($m['maneuverID']) ?>">
                <input 
                  type="checkbox" 
                  name="maneuvers[]" 
                  value="<?= htmlspecialchars($m['maneuverID']) ?>" 
                  class="maneuver-checkbox"
                  id="maneuver_<?= htmlspecialchars($m['maneuverID']) ?>"
                  aria-label="<?= htmlspecialchars($m['maneuverName']) ?>"
                >
                <span class="custom-checkbox"></span>
                <span class="maneuver-text"><?= htmlspecialchars($m['maneuverName']) ?></span>
              </label>
            <?php endforeach; ?>
          </div>
        </fieldset>
      </section>

      <button type="submit">Save Experience</button>
    </form>
  </section>
</main>

<script>
  // Nice UX: default date to today
  const dateEl = document.getElementById('date');
  if (dateEl && !dateEl.value) {
    const today = new Date();
    const yyyy = today.getFullYear();
    const mm = String(today.getMonth() + 1).padStart(2, '0');
    const dd = String(today.getDate()).padStart(2, '0');
    dateEl.value = `${yyyy}-${mm}-${dd}`;
  }

  // Update maneuver counter
  const checkboxes = document.querySelectorAll('.maneuver-checkbox');
  const countDisplay = document.getElementById('maneuverCount');
  
  function updateCount() {
    const checked = document.querySelectorAll('.maneuver-checkbox:checked').length;
    countDisplay.textContent = `${checked} selected`;
    
    if (checked > 0) {
      countDisplay.classList.add('active');
    } else {
      countDisplay.classList.remove('active');
    }
  }
  
  checkboxes.forEach(cb => {
    cb.addEventListener('change', updateCount);
  });

  // Validate time
  document.querySelector("form").addEventListener("submit", function (e) {
    const start = document.querySelector("input[name='startTime']").value;
    const end   = document.querySelector("input[name='endTime']").value;

    // end must be AFTER start
    if (start && end && end <= start) {
      e.preventDefault();
      alert("End time must be later than start time.");
    }
  });
</script>
</body>
</html>