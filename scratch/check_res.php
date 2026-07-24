<?php
require_once 'config/db.php';
$res = $pdo->query("SELECT r.*, b.statut as bien_statut FROM reservations r JOIN biens b ON r.bien_id = b.id")->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($res, JSON_PRETTY_PRINT);
