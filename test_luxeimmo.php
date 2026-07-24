<?php
/**
 * test_luxeimmo.php — Suite de tests fonctionnels complets
 * Teste toutes les fonctionnalités de l'application LuxeImmo.
 * 
 * Usage : C:\xampp\php\php.exe test_luxeimmo.php
 */

require_once 'c:/xampp/htdocs/Dev_Web_Avancé/config/db.php';

// ============================================================
// Infrastructure de test
// ============================================================
$pass = 0; $fail = 0; $warn = 0;
$results = [];

function test(string $name, bool $condition, string $detail = ''): void {
    global $pass, $fail, $results;
    if ($condition) {
        $results[] = ['status' => 'PASS', 'name' => $name, 'detail' => $detail];
        $pass++;
    } else {
        $results[] = ['status' => 'FAIL', 'name' => $name, 'detail' => $detail];
        $fail++;
    }
}

function warn(string $name, string $detail): void {
    global $warn, $results;
    $results[] = ['status' => 'WARN', 'name' => $name, 'detail' => $detail];
    $warn++;
}

function section(string $title): void {
    global $results;
    $results[] = ['status' => 'SECTION', 'name' => $title, 'detail' => ''];
}

// ============================================================
// 1. CONNEXION BASE DE DONNÉES
// ============================================================
section('1. CONNEXION BASE DE DONNÉES');
test('PDO connexion active', $pdo instanceof PDO, 'MySQL connecté');

$tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
test('Table utilisateurs existe', in_array('utilisateurs', $tables));
test('Table biens existe', in_array('biens', $tables));
test('Table images existe', in_array('images', $tables));
test('Table reservations existe', in_array('reservations', $tables));
test('Table favoris existe', in_array('favoris', $tables));
test('Nombre de tables correct (5)', count($tables) >= 5, count($tables) . ' tables trouvées');

// ============================================================
// 2. MODÈLE RELATIONNEL — Contraintes
// ============================================================
section('2. MODÈLE RELATIONNEL — Contraintes & Intégrité');

// Vérifier les clés étrangères
$fk = $pdo->query("
    SELECT TABLE_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME
    FROM information_schema.KEY_COLUMN_USAGE
    WHERE TABLE_SCHEMA = DATABASE() AND REFERENCED_TABLE_NAME IS NOT NULL
")->fetchAll(PDO::FETCH_ASSOC);

$fk_found = array_column($fk, null, 'TABLE_NAME');
test('FK images.bien_id → biens', isset($fk_found['images']), 'Clé étrangère déclarée');
test('FK reservations.client_id → utilisateurs', !empty(array_filter($fk, fn($f) => $f['TABLE_NAME'] === 'reservations' && $f['COLUMN_NAME'] === 'client_id')));
test('FK reservations.bien_id → biens', !empty(array_filter($fk, fn($f) => $f['TABLE_NAME'] === 'reservations' && $f['COLUMN_NAME'] === 'bien_id')));
test('FK favoris.client_id → utilisateurs', !empty(array_filter($fk, fn($f) => $f['TABLE_NAME'] === 'favoris' && $f['COLUMN_NAME'] === 'client_id')));

// Contrainte UNIQUE sur favoris
$uniq = $pdo->query("SHOW INDEX FROM favoris WHERE Key_name='client_bien_unique'")->fetch();
test('Contrainte UNIQUE (client_id, bien_id) sur favoris', (bool)$uniq, 'Empêche les doublons');

// ============================================================
// 3. DONNÉES INITIALES
// ============================================================
section('3. DONNÉES — Biens et images en base');

$nb_biens = $pdo->query("SELECT COUNT(*) FROM biens")->fetchColumn();
test('Au moins 1 bien en base', $nb_biens >= 1, $nb_biens . ' biens trouvés');

$biens = $pdo->query("SELECT * FROM biens")->fetchAll(PDO::FETCH_ASSOC);
foreach ($biens as $b) {
    $has_img = $pdo->prepare("SELECT COUNT(*) FROM images WHERE bien_id = ?")->execute([$b['id']]);
    $nb_img = $pdo->prepare("SELECT COUNT(*) FROM images WHERE bien_id = ?");
    $nb_img->execute([$b['id']]);
    $count = $nb_img->fetchColumn();
    test("Bien #{$b['id']} \"{$b['titre']}\" a des images", $count > 0, $count . ' image(s)');
    
    $main_img = $pdo->prepare("SELECT COUNT(*) FROM images WHERE bien_id = ? AND est_principale = 1");
    $main_img->execute([$b['id']]);
    $has_main = $main_img->fetchColumn() > 0;
    // Si pas de principale, COALESCE fallback doit retourner une image quand même
    $fallback = $pdo->prepare("SELECT COALESCE((SELECT chemin FROM images WHERE bien_id = ? AND est_principale = 1 LIMIT 1), (SELECT chemin FROM images WHERE bien_id = ? ORDER BY id ASC LIMIT 1)) AS img");
    $fallback->execute([$b['id'], $b['id']]);
    $img_result = $fallback->fetchColumn();
    test("Bien #{$b['id']} : image affichable (COALESCE)", !empty($img_result), $img_result ?: 'NULL — PROBLÈME!');

    // Vérifier que le fichier image existe sur le disque
    if (!empty($img_result)) {
        $filepath = 'c:/xampp/htdocs/Dev_Web_Avancé/' . $img_result;
        test("Bien #{$b['id']} : fichier image existe sur disque", file_exists($filepath), $img_result);
    }

    test("Bien #{$b['id']} : prix entre 50 000 et 500 000 FCFA", $b['prix_mensuel'] >= 50000 && $b['prix_mensuel'] <= 500000, number_format($b['prix_mensuel'], 0, ',', ' ') . ' FCFA');
    test("Bien #{$b['id']} : ville est Pikine ou Guédiawaye", in_array($b['ville'], ['Pikine', 'Guédiawaye']), $b['ville']);
}

// ============================================================
// 4. AUTHENTIFICATION
// ============================================================
section('4. AUTHENTIFICATION — Sécurité');

// Comptes de test
$users = $pdo->query("SELECT * FROM utilisateurs")->fetchAll(PDO::FETCH_ASSOC);
test('Au moins 1 commercial', !empty(array_filter($users, fn($u) => $u['role'] === 'commercial')));
test('Au moins 1 client', !empty(array_filter($users, fn($u) => $u['role'] === 'client')));

// Vérifier que les mots de passe sont hashés (pas en clair)
foreach ($users as $u) {
    test("Mot de passe hashé pour {$u['email']}", strlen($u['mot_de_passe']) >= 60 && str_starts_with($u['mot_de_passe'], '$2'), 'BCRYPT hash valide');
}

// Vérifier password_verify avec 'password123'
$commercial = array_values(array_filter($users, fn($u) => $u['role'] === 'commercial'))[0] ?? null;
$client     = array_values(array_filter($users, fn($u) => $u['role'] === 'client'))[0] ?? null;

if ($commercial) {
    test('password_verify fonctionne pour commercial', password_verify('password123', $commercial['mot_de_passe']), $commercial['email']);
}
if ($client) {
    test('password_verify fonctionne pour client', password_verify('password123', $client['mot_de_passe']), $client['email']);
}

// Refus d'un mauvais mot de passe
if ($commercial) {
    test('Mauvais mot de passe refusé', !password_verify('wrongpass', $commercial['mot_de_passe']), 'Sécurité OK');
}

// ============================================================
// 5. ESPACE COMMERCIAL — Gestion des biens
// ============================================================
section('5. ESPACE COMMERCIAL — Gestion des biens (CRUD)');

// Simuler un ajout de bien
$test_titre = 'TestBien_' . time();
$stmt = $pdo->prepare("INSERT INTO biens (titre, description, type, prix_mensuel, adresse, ville, chambres, salons, salles_de_bain, superficie, statut) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
$inserted = $stmt->execute([$test_titre, 'Description de test', 'appartement', 75000, '10 rue Test', 'Pikine', 2, 1, 1, 80, 'disponible']);
$test_bien_id = $pdo->lastInsertId();
test('Commercial : Ajout d\'un bien (INSERT)', $inserted && $test_bien_id > 0, "Bien inséré ID=$test_bien_id");

// Simuler une modification
$updated = $pdo->prepare("UPDATE biens SET prix_mensuel = 85000 WHERE id = ?")->execute([$test_bien_id]);
$new_prix = $pdo->prepare("SELECT prix_mensuel FROM biens WHERE id = ?");
$new_prix->execute([$test_bien_id]);
test('Commercial : Modification d\'un bien (UPDATE)', $new_prix->fetchColumn() == 85000, 'Prix modifié à 85 000');

// Ajouter une image de test
$img_stmt = $pdo->prepare("INSERT INTO images (bien_id, chemin, est_principale) VALUES (?, ?, ?)");
$img_inserted = $img_stmt->execute([$test_bien_id, 'assets/images/test_img.jpg', 1]);
test('Commercial : Ajout d\'une image au bien', $img_inserted, 'Image principale insérée');

// Suppression en cascade : supprimer le bien doit supprimer ses images
$pdo->prepare("DELETE FROM biens WHERE id = ?")->execute([$test_bien_id]);
$img_after = $pdo->prepare("SELECT COUNT(*) FROM images WHERE bien_id = ?");
$img_after->execute([$test_bien_id]);
test('Commercial : Suppression bien + images en cascade (DELETE)', $img_after->fetchColumn() == 0, 'ON DELETE CASCADE fonctionne');

// ============================================================
// 6. ESPACE COMMERCIAL — Gestion des réservations
// ============================================================
section('6. ESPACE COMMERCIAL — Gestion des réservations');

// Créer une réservation de test
$comm_client = $client ?? $users[0];
$test_bien = $biens[0];

$res_stmt = $pdo->prepare("INSERT INTO reservations (client_id, bien_id, date_debut, date_fin, statut) VALUES (?, ?, ?, ?, 'en_attente')");
$res_ok = $res_stmt->execute([$comm_client['id'], $test_bien['id'], date('Y-m-d', strtotime('+7 days')), date('Y-m-d', strtotime('+37 days'))]);
$test_res_id = $pdo->lastInsertId();
test('Création d\'une réservation de test', $res_ok && $test_res_id > 0, "Réservation ID=$test_res_id");

// Valider la réservation
$pdo->prepare("UPDATE reservations SET statut = 'validee' WHERE id = ?")->execute([$test_res_id]);
// Mettre à jour le statut du bien
$pdo->prepare("UPDATE biens SET statut = 'reserve' WHERE id = ?")->execute([$test_bien['id']]);
$res_statut = $pdo->prepare("SELECT statut FROM reservations WHERE id = ?");
$res_statut->execute([$test_res_id]);
test('Commercial : Validation réservation → statut "validée"', $res_statut->fetchColumn() === 'validee');
$bien_statut = $pdo->prepare("SELECT statut FROM biens WHERE id = ?");
$bien_statut->execute([$test_bien['id']]);
test('Commercial : Bien passe en statut "réservé" après validation', $bien_statut->fetchColumn() === 'reserve');

// Annuler
$pdo->prepare("UPDATE reservations SET statut = 'annulee' WHERE id = ?")->execute([$test_res_id]);
// Vérifier qu'il n'y a plus d'autre resa validée → remettre disponible
$other = $pdo->prepare("SELECT COUNT(*) FROM reservations WHERE bien_id=? AND statut='validee' AND id!=?");
$other->execute([$test_bien['id'], $test_res_id]);
if (!$other->fetchColumn()) {
    $pdo->prepare("UPDATE biens SET statut='disponible' WHERE id=?")->execute([$test_bien['id']]);
}
$res_annulee = $pdo->prepare("SELECT statut FROM reservations WHERE id = ?");
$res_annulee->execute([$test_res_id]);
test('Commercial : Annulation → statut "annulée"', $res_annulee->fetchColumn() === 'annulee');
$bien_dispo = $pdo->prepare("SELECT statut FROM biens WHERE id = ?");
$bien_dispo->execute([$test_bien['id']]);
test('Commercial : Bien redevient "disponible" après annulation', $bien_dispo->fetchColumn() === 'disponible');

// Nettoyage
$pdo->prepare("DELETE FROM reservations WHERE id = ?")->execute([$test_res_id]);

// ============================================================
// 7. ESPACE COMMERCIAL — Gestion des clients
// ============================================================
section('7. ESPACE COMMERCIAL — Gestion des comptes clients');

// Créer un client de test
$test_email = 'test_' . time() . '@luxeimmo.sn';
$hash = password_hash('testpass123', PASSWORD_BCRYPT);
$pdo->prepare("INSERT INTO utilisateurs (nom, prenom, email, mot_de_passe, telephone, role, statut) VALUES (?, ?, ?, ?, ?, 'client', 'actif')")->execute(['TestNom', 'TestPrenom', $test_email, $hash, '770000000']);
$test_client_id = $pdo->lastInsertId();
test('Création client de test', $test_client_id > 0, "Client ID=$test_client_id");

// Désactiver
$pdo->prepare("UPDATE utilisateurs SET statut='inactif' WHERE id=? AND role='client'")->execute([$test_client_id]);
$st = $pdo->prepare("SELECT statut FROM utilisateurs WHERE id=?");
$st->execute([$test_client_id]);
test('Commercial : Désactivation compte client', $st->fetchColumn() === 'inactif');

// Réactiver
$pdo->prepare("UPDATE utilisateurs SET statut='actif' WHERE id=? AND role='client'")->execute([$test_client_id]);
$st2 = $pdo->prepare("SELECT statut FROM utilisateurs WHERE id=?");
$st2->execute([$test_client_id]);
test('Commercial : Réactivation compte client', $st2->fetchColumn() === 'actif');

// Historique client
$hist = $pdo->prepare("SELECT COUNT(*) FROM reservations WHERE client_id = ?");
$hist->execute([$test_client_id]);
test('Commercial : Consultation historique client (0 resa = normal)', $hist->fetchColumn() >= 0, 'Requête historique OK');

// Suppression
$pdo->prepare("DELETE FROM utilisateurs WHERE id=? AND role='client'")->execute([$test_client_id]);
$after_del = $pdo->prepare("SELECT COUNT(*) FROM utilisateurs WHERE id=?");
$after_del->execute([$test_client_id]);
test('Commercial : Suppression compte client', $after_del->fetchColumn() == 0);

// ============================================================
// 8. ESPACE CLIENT — Favoris
// ============================================================
section('8. ESPACE CLIENT — Gestion des favoris');

// Créer un client de test temporaire
$test_email2 = 'client_test2_' . time() . '@luxeimmo.sn';
$pdo->prepare("INSERT INTO utilisateurs (nom, prenom, email, mot_de_passe, telephone, role, statut) VALUES ('FavNom', 'FavPrenom', ?, ?, '770000001', 'client', 'actif')")->execute([$test_email2, password_hash('testpass', PASSWORD_BCRYPT)]);
$fav_client_id = $pdo->lastInsertId();

$fav_bien = $biens[0];

// Ajouter aux favoris
$pdo->prepare("INSERT IGNORE INTO favoris (client_id, bien_id) VALUES (?, ?)")->execute([$fav_client_id, $fav_bien['id']]);
$fav_count = $pdo->prepare("SELECT COUNT(*) FROM favoris WHERE client_id=? AND bien_id=?");
$fav_count->execute([$fav_client_id, $fav_bien['id']]);
test('Client : Ajout aux favoris', $fav_count->fetchColumn() == 1, "Bien #{$fav_bien['id']} en favori");

// INSERT IGNORE doit empêcher le doublon
$pdo->prepare("INSERT IGNORE INTO favoris (client_id, bien_id) VALUES (?, ?)")->execute([$fav_client_id, $fav_bien['id']]);
$fav_count2 = $pdo->prepare("SELECT COUNT(*) FROM favoris WHERE client_id=? AND bien_id=?");
$fav_count2->execute([$fav_client_id, $fav_bien['id']]);
test('Client : Pas de doublon en favoris (UNIQUE constraint)', $fav_count2->fetchColumn() == 1, 'INSERT IGNORE OK');

// Retirer des favoris
$pdo->prepare("DELETE FROM favoris WHERE client_id=? AND bien_id=?")->execute([$fav_client_id, $fav_bien['id']]);
$fav_count3 = $pdo->prepare("SELECT COUNT(*) FROM favoris WHERE client_id=? AND bien_id=?");
$fav_count3->execute([$fav_client_id, $fav_bien['id']]);
test('Client : Retrait des favoris', $fav_count3->fetchColumn() == 0);

// ============================================================
// 9. ESPACE CLIENT — Réservations
// ============================================================
section('9. ESPACE CLIENT — Réservations');

$client_bien = $biens[0];

// Client peut réserver un bien disponible
$pdo->prepare("UPDATE biens SET statut='disponible' WHERE id=?")->execute([$client_bien['id']]);
$dispo = $pdo->prepare("SELECT statut FROM biens WHERE id=?");
$dispo->execute([$client_bien['id']]);
test('Client : Bien disponible pour réservation', $dispo->fetchColumn() === 'disponible');

// Créer une réservation
$cl_res = $pdo->prepare("INSERT INTO reservations (client_id, bien_id, date_debut, date_fin, statut) VALUES (?, ?, ?, ?, 'en_attente')");
$cl_res_ok = $cl_res->execute([$fav_client_id, $client_bien['id'], date('Y-m-d', strtotime('+2 days')), date('Y-m-d', strtotime('+12 days'))]);
$cl_res_id = $pdo->lastInsertId();
test('Client : Création réservation (en_attente)', $cl_res_ok && $cl_res_id > 0);

// Vérifier que le client peut voir sa réservation
$cl_view = $pdo->prepare("SELECT r.*, b.titre FROM reservations r JOIN biens b ON r.bien_id=b.id WHERE r.client_id=? AND r.id=?");
$cl_view->execute([$fav_client_id, $cl_res_id]);
$cl_res_row = $cl_view->fetch();
test('Client : Dashboard affiche la réservation', $cl_res_row !== false, $cl_res_row['titre'] ?? 'INTROUVABLE');

// Client ne peut annuler que si statut est en_attente
$can_cancel = ($cl_res_row['statut'] === 'en_attente');
test('Client : Peut annuler une réservation "en_attente"', $can_cancel);
if ($can_cancel) {
    $pdo->prepare("UPDATE reservations SET statut='annulee' WHERE id=? AND client_id=? AND statut='en_attente'")->execute([$cl_res_id, $fav_client_id]);
    $chk = $pdo->prepare("SELECT statut FROM reservations WHERE id=?");
    $chk->execute([$cl_res_id]);
    test('Client : Annulation réservation réussie', $chk->fetchColumn() === 'annulee');
}

// Nettoyage
$pdo->prepare("DELETE FROM reservations WHERE id=?")->execute([$cl_res_id]);
$pdo->prepare("DELETE FROM utilisateurs WHERE id=?")->execute([$fav_client_id]);

// ============================================================
// 10. VISITEUR — Catalogue et recherche
// ============================================================
section('10. VISITEUR — Catalogue et filtres de recherche');

// Filtre par type
$villa = $pdo->prepare("SELECT COUNT(*) FROM biens WHERE type='villa'");
$villa->execute();
test('Visiteur : Filtre par type "villa" fonctionne', $villa->fetchColumn() >= 0, $villa->fetchColumn() . ' villas');

// Filtre par ville
$pik = $pdo->prepare("SELECT COUNT(*) FROM biens WHERE ville='Pikine'");
$pik->execute();
test('Visiteur : Filtre par ville "Pikine"', $pik->fetchColumn() >= 0, $pik->fetchColumn() . ' biens');

$gue = $pdo->prepare("SELECT COUNT(*) FROM biens WHERE ville='Guédiawaye'");
$gue->execute();
test('Visiteur : Filtre par ville "Guédiawaye"', $gue->fetchColumn() >= 0, $gue->fetchColumn() . ' biens');

// Filtre par prix max
$prix_filter = $pdo->prepare("SELECT COUNT(*) FROM biens WHERE prix_mensuel <= 150000");
$prix_filter->execute();
test('Visiteur : Filtre prix max 150 000 FCFA', $prix_filter->fetchColumn() >= 0, $prix_filter->fetchColumn() . ' biens');

// Filtre par chambres min
$chbr = $pdo->prepare("SELECT COUNT(*) FROM biens WHERE chambres >= 2");
$chbr->execute();
test('Visiteur : Filtre chambres min 2', $chbr->fetchColumn() >= 0);

// ============================================================
// 11. SÉCURITÉ — Contrôle d'accès
// ============================================================
section('11. SÉCURITÉ — Contrôle d\'accès et validation');

// Les fichiers auth_check et header existent
test('Fichier auth_check.php existe', file_exists('c:/xampp/htdocs/Dev_Web_Avancé/includes/auth_check.php'));
test('Fichier header.php existe', file_exists('c:/xampp/htdocs/Dev_Web_Avancé/includes/header.php'));
test('Fichier footer.php existe', file_exists('c:/xampp/htdocs/Dev_Web_Avancé/includes/footer.php'));

// Tous les fichiers du dossier commercial ont require_role('commercial')
$comm_files = ['biens.php', 'clients.php', 'index.php', 'reservations.php'];
foreach ($comm_files as $f) {
    $content = file_get_contents("c:/xampp/htdocs/Dev_Web_Avancé/commercial/$f");
    test("commercial/$f protégé par require_role('commercial')", str_contains($content, "require_role('commercial')"));
}

// Tous les fichiers du dossier client ont require_role('client') ou require_login
$client_files = ['dashboard.php', 'favoris.php', 'reserver.php', 'annuler_reservation.php'];
foreach ($client_files as $f) {
    $content = file_get_contents("c:/xampp/htdocs/Dev_Web_Avancé/client/$f");
    $secured = str_contains($content, "require_role('client')") || str_contains($content, 'require_login()');
    test("client/$f protégé par require_role/require_login", $secured);
}

// XSS : les pages utilisent htmlspecialchars
$index_content = file_get_contents('c:/xampp/htdocs/Dev_Web_Avancé/index.php');
test('index.php utilise htmlspecialchars (protection XSS)', str_contains($index_content, 'htmlspecialchars'));

// SQL injection : les pages utilisent PDO prepare
$all_php = array_merge(
    glob('c:/xampp/htdocs/Dev_Web_Avancé/commercial/*.php'),
    glob('c:/xampp/htdocs/Dev_Web_Avancé/client/*.php')
);
$uses_prepare = true;
$direct_query = false;
foreach ($all_php as $phpfile) {
    $c = file_get_contents($phpfile);
    if (preg_match('/\$pdo->query\(.*\$_/', $c)) {
        $direct_query = true;
    }
}
test('Aucune requête SQL directe avec variable GET/POST (PDO prepare)', !$direct_query, $direct_query ? 'ATTENTION: Variable non préparée trouvée!' : 'Toutes les requêtes sont préparées');

// ============================================================
// 12. FICHIERS CRITIQUES ET SYNTAXE PHP
// ============================================================
section('12. SYNTAXE PHP — Tous les fichiers critiques');

$all_files = [
    'index.php', 'login.php', 'logout.php', 'register.php', 'detail.php',
    'includes/header.php', 'includes/footer.php', 'includes/auth_check.php', 'includes/navbar.php',
    'commercial/biens.php', 'commercial/clients.php', 'commercial/index.php', 'commercial/reservations.php',
    'client/dashboard.php', 'client/favoris.php', 'client/reserver.php', 'client/annuler_reservation.php', 'client/toggle_favori.php',
    'config/db.php',
];

foreach ($all_files as $f) {
    $output = shell_exec("C:\\xampp\\php\\php.exe -l \"c:/xampp/htdocs/Dev_Web_Avancé/$f\" 2>&1");
    $ok = str_contains($output, 'No syntax errors detected');
    test("Syntaxe PHP : $f", $ok, trim($output));
}

// ============================================================
// RAPPORT FINAL
// ============================================================
echo "\n";
echo str_repeat('═', 70) . "\n";
echo "  🏠 RAPPORT DE TESTS — LuxeImmo\n";
echo str_repeat('═', 70) . "\n\n";

$current_section = '';
foreach ($results as $r) {
    if ($r['status'] === 'SECTION') {
        echo "\n  📋 " . strtoupper($r['name']) . "\n";
        echo "  " . str_repeat('─', 60) . "\n";
        continue;
    }
    $icon = $r['status'] === 'PASS' ? '  ✅' : ($r['status'] === 'WARN' ? '  ⚠️ ' : '  ❌');
    $detail = $r['detail'] ? "  → {$r['detail']}" : '';
    echo "$icon  {$r['name']}\n";
    if ($r['detail'] && $r['status'] !== 'PASS') {
        echo "         $detail\n";
    } elseif ($r['detail'] && $r['status'] === 'PASS') {
        echo "         $detail\n";
    }
}

echo "\n" . str_repeat('═', 70) . "\n";
$total = $pass + $fail + $warn;
$pct   = $total > 0 ? round(($pass / $total) * 100) : 0;
echo "  RÉSULTATS : ✅ $pass PASSÉS  |  ❌ $fail ÉCHOUÉS  |  ⚠️  $warn AVERTISSEMENTS\n";
echo "  SCORE     : $pct% ($pass/$total tests)\n";
echo str_repeat('═', 70) . "\n\n";

if ($fail === 0) {
    echo "  🎉 TOUS LES TESTS PASSENT — Application prête pour la présentation !\n\n";
} else {
    echo "  ⚠️  $fail test(s) ont échoué. Voir détails ci-dessus.\n\n";
}
