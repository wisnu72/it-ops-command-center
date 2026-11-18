<?php
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['incident_id'];
    $sopAction = trim($_POST['sop_action']);
    $shift = $_POST['operator_shift'];

    $stmt = $pdo->prepare("UPDATE incident_logs SET status = 'RESOLVED', sop_action_taken = ?, operator_shift = ?, resolved_at = NOW() WHERE id = ?");
    $stmt->execute([$sopAction, $shift, $id]);
}

header("Location: ../index.php?msg=Insiden+berhasil+ditutup+sesuai+SOP");
exit;