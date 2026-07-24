<?php
require_once 'config/db.php';
$pdo->exec("UPDATE reservations SET statut = 'en_attente' WHERE id IN (1, 2)");
$pdo->exec("UPDATE biens SET statut = 'disponible' WHERE id IN (SELECT bien_id FROM reservations WHERE id IN (1, 2))");
echo "Reservations 1 and 2 reset to 'en_attente'\n";
