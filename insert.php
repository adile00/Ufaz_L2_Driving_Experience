<?php
require_once 'init.php';
require_once 'csrf.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: form.php');
    exit;
}

/* CSRF CHECK (your project uses csrf_validate) */
$csrf = $_POST['csrf'] ?? '';
if (!csrf_validate($csrf)) {
    die('Invalid CSRF token');
}

/* INPUTS */
$date        = trim($_POST['date'] ?? '');
$startTime   = trim($_POST['startTime'] ?? '');
$endTime     = trim($_POST['endTime'] ?? '');
$kilometers  = $_POST['kilometers'] ?? '';

$weatherID    = $_POST['weather_conditionsID'] ?? '';
$speedID      = $_POST['speed_limitsID'] ?? '';
$trafficID    = $_POST['traffic_densitiesID'] ?? '';
$visibilityID = $_POST['visibility_conditionsID'] ?? '';

$maneuvers = $_POST['maneuvers'] ?? [];

/* SERVER-SIDE VALIDATION */
$errors = [];

if ($date === '') $errors[] = 'Date is required';
if ($startTime === '' || $endTime === '') $errors[] = 'Start and end time are required';

/* rule: end must be later than start (same-day driving) */
if ($startTime !== '' && $endTime !== '' && $endTime <= $startTime) {
    $errors[] = 'End time must be later than start time';
}

if (!is_numeric($kilometers) || (float)$kilometers <= 0) {
    $errors[] = 'Kilometers must be positive';
}

foreach ([$weatherID, $speedID, $trafficID, $visibilityID] as $val) {
    if (!ctype_digit((string)$val)) {
        $errors[] = 'Invalid select input';
        break;
    }
}

if (!is_array($maneuvers)) {
    $maneuvers = [];
}

/* If errors -> back to form */
if (!empty($errors)) {
    $_SESSION['form_errors'] = $errors;
    header('Location: form.php');
    exit;
}

/* DATABASE INSERT (PDO) */
try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
        INSERT INTO driving_experiences
        (date, startTime, endTime, kilometers,
         weather_conditionsID, speed_limitsID,
         traffic_densitiesID, visibility_conditionsID)
        VALUES
        (:date, :startTime, :endTime, :km, :w, :s, :t, :v)
    ");

    $stmt->bindValue(':date', $date);
    $stmt->bindValue(':startTime', $startTime);
    $stmt->bindValue(':endTime', $endTime);
    $stmt->bindValue(':km', (float)$kilometers);

    $stmt->bindValue(':w', (int)$weatherID, PDO::PARAM_INT);
    $stmt->bindValue(':s', (int)$speedID, PDO::PARAM_INT);
    $stmt->bindValue(':t', (int)$trafficID, PDO::PARAM_INT);
    $stmt->bindValue(':v', (int)$visibilityID, PDO::PARAM_INT);

    $stmt->execute();
    $expID = (int)$pdo->lastInsertId();

    /* Insert maneuvers (many-to-many) */
    if (!empty($maneuvers)) {
        $mStmt = $pdo->prepare("
            INSERT INTO experience_maneuver (expID, maneuverID)
            VALUES (:expID, :maneuverID)
        ");

        foreach ($maneuvers as $m) {
            if (!ctype_digit((string)$m)) continue;
            $mStmt->bindValue(':expID', $expID, PDO::PARAM_INT);
            $mStmt->bindValue(':maneuverID', (int)$m, PDO::PARAM_INT);
            $mStmt->execute();
        }
    }

    $pdo->commit();
    header('Location: success.php');
    exit;

} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log($e->getMessage());
    die('Database error');
}
