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

try {
    $pdo->beginTransaction();

    $d1 = $pdo->prepare("DELETE FROM experience_maneuver WHERE expID = :id");
    $d1->bindValue(':id', $expID, PDO::PARAM_INT);
    $d1->execute();

    $d2 = $pdo->prepare("DELETE FROM driving_experiences WHERE expID = :id");
    $d2->bindValue(':id', $expID, PDO::PARAM_INT);
    $d2->execute();

    $pdo->commit();
    header('Location: dashboard.php');
    exit;

} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    die("Delete error: " . htmlspecialchars($e->getMessage()));
}
