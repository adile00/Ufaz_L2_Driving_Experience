<?php
require_once 'init.php';
require_once 'csrf.php';
require_once 'id_token.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: dashboard.php');
    exit;
}

if (!csrf_validate($_POST['csrf'] ?? null)) {
    die("CSRF validation failed.");
}

$token = $_POST['t'] ?? '';
$expID = exp_from_token($token);
if (!$expID) die("Invalid token.");

$date  = $_POST['date'] ?? null;
$start = $_POST['startTime'] ?? null;
$end   = $_POST['endTime'] ?? null;
$km    = $_POST['kilometers'] ?? null;

$weatherID    = $_POST['weather_conditionsID'] ?? null;
$speedID      = $_POST['speed_limitsID'] ?? null;
$trafficID    = $_POST['traffic_densitiesID'] ?? null;
$visibilityID = $_POST['visibility_conditionsID'] ?? null;

$maneuvers = $_POST['maneuvers'] ?? [];
if (!is_array($maneuvers)) $maneuvers = [];
$maneuvers = array_values(array_filter($maneuvers, fn($x)=>ctype_digit((string)$x)));

if (!$date || !$start || !$end || $km === null || !is_numeric($km) || $km < 0 ||
    !ctype_digit((string)$weatherID) || !ctype_digit((string)$speedID) ||
    !ctype_digit((string)$trafficID) || !ctype_digit((string)$visibilityID)) {
    die("Invalid form data.");
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
        UPDATE driving_experiences
        SET date = :d, startTime = :st, endTime = :et, kilometers = :km,
            weather_conditionsID = :w, speed_limitsID = :s,
            traffic_densitiesID = :t, visibility_conditionsID = :v
        WHERE expID = :id
    ");
    $stmt->bindValue(':d', $date);
    $stmt->bindValue(':st', $start);
    $stmt->bindValue(':et', $end);
    $stmt->bindValue(':km', (float)$km);
    $stmt->bindValue(':w', (int)$weatherID, PDO::PARAM_INT);
    $stmt->bindValue(':s', (int)$speedID, PDO::PARAM_INT);
    $stmt->bindValue(':t', (int)$trafficID, PDO::PARAM_INT);
    $stmt->bindValue(':v', (int)$visibilityID, PDO::PARAM_INT);
    $stmt->bindValue(':id', (int)$expID, PDO::PARAM_INT);
    $stmt->execute();

    // Replace maneuvers
    $del = $pdo->prepare("DELETE FROM experience_maneuver WHERE expID = :id");
    $del->bindValue(':id', (int)$expID, PDO::PARAM_INT);
    $del->execute();

    if (!empty($maneuvers)) {
        $ins = $pdo->prepare("INSERT INTO experience_maneuver (expID, maneuverID) VALUES (:id, :m)");
        $ins->bindParam(':id', $expID, PDO::PARAM_INT);
        $mID = null;
        $ins->bindParam(':m', $mID, PDO::PARAM_INT);

        foreach ($maneuvers as $x) {
            $mID = (int)$x;
            $ins->execute();
        }
    }

    $pdo->commit();
    header('Location: dashboard.php');
    exit;

} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    die("Update error: " . htmlspecialchars($e->getMessage()));
}
