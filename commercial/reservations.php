<?php
/**
 * commercial/reservations.php
 * Gestion et suivi des réservations : Valider, Annuler, Modifier, Filtrer
 */
require_once '../config/db.php';
require_once '../includes/auth_check.php';
require_role('commercial');

// ===== Actions rapides =====
$action = $_GET['action'] ?? '';
$res_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($res_id && in_array($action, ['valider', 'annuler', 'terminer'])) {
    $new_status_map = ['valider' => 'validee', 'annuler' => 'annulee', 'terminer' => 'terminee'];
    $new_status     = $new_status_map[$action];

    // Mettre à jour le statut de la réservation
    $pdo->prepare("UPDATE reservations SET statut = ? WHERE id = ?")->execute([$new_status, $res_id]);

    // Si validation, mettre le bien en 'reserve'
    if ($action === 'valider') {
        $bid = $pdo->prepare("SELECT bien_id FROM reservations WHERE id = ?");
        $bid->execute([$res_id]);
        $b = $bid->fetchColumn();
        if ($b) $pdo->prepare("UPDATE biens SET statut = 'reserve' WHERE id = ?")->execute([$b]);
    }
    // Si annulation ou fin de location, remettre en 'disponible'
    if (in_array($action, ['annuler', 'terminer'])) {
        $bid = $pdo->prepare("SELECT bien_id FROM reservations WHERE id = ?");
        $bid->execute([$res_id]);
        $b = $bid->fetchColumn();
        if ($b) {
            // Vérifier qu'il n'y a pas d'autre réservation validée active sur ce bien
            $other = $pdo->prepare("SELECT COUNT(*) FROM reservations WHERE bien_id=? AND statut='validee' AND id!=?");
            $other->execute([$b, $res_id]);
            if (!$other->fetchColumn()) {
                $pdo->prepare("UPDATE biens SET statut='disponible' WHERE id=?")->execute([$b]);
            }
        }
    }

    header("Location: reservations.php?msg=" . urlencode("Réservation mise à jour avec succès."));
    exit;
}

// ===== Filtres =====
$filter_statut = $_GET['statut'] ?? '';
$filter_search = trim($_GET['search'] ?? '');

$sql    = "SELECT r.*, b.titre AS bien_titre, b.type AS bien_type, b.prix_mensuel,
                  u.nom, u.prenom, u.email, u.telephone
           FROM reservations r
           JOIN biens b ON r.bien_id = b.id
           JOIN utilisateurs u ON r.client_id = u.id
           WHERE 1=1";
$params = [];

if ($filter_statut) {
    $sql .= " AND r.statut = ?";
    $params[] = $filter_statut;
}
if ($filter_search) {
    $sql .= " AND (u.nom LIKE ? OR u.prenom LIKE ? OR b.titre LIKE ?)";
    $params[] = '%' . $filter_search . '%';
    $params[] = '%' . $filter_search . '%';
    $params[] = '%' . $filter_search . '%';
}
$sql .= " ORDER BY r.date_creation DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$reservations = $stmt->fetchAll();

// Compteurs par statut
$counts = [];
foreach (['en_attente', 'validee', 'annulee', 'terminee'] as $s) {
    $c = $pdo->prepare("SELECT COUNT(*) FROM reservations WHERE statut = ?");
    $c->execute([$s]);
    $counts[$s] = $c->fetchColumn();
}

$page_title = 'Gestion des Réservations — LuxeImmo';
require_once '../includes/header.php';
?>

<div style="display:flex;min-height:100vh;background:var(--color-bg-dark);">
    <?php require_once 'sidebar.php'; ?>
    <div class="main-content-with-sidebar" style="flex:1;">

        <div style="margin-bottom:32px;">
            <h1 style="font-size:1.8rem;font-weight:800;color:var(--color-text-primary);letter-spacing:-0.5px;margin-bottom:6px;">
                Gestion des Réservations
            </h1>
            <p style="color:var(--color-text-muted);font-size:0.9rem;"><?= count($reservations) ?> réservation(s) affichée(s)</p>
        </div>

        <?php if (isset($_GET['msg'])): ?>
        <div class="alert-immo success mb-4">
            <i class="fas fa-check-circle"></i> <?= htmlspecialchars($_GET['msg']) ?>
        </div>
        <?php endif; ?>

        <!-- Onglets de filtre par statut -->
        <div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:24px;">
            <?php
            $tab_labels = [
                '' => ['Toutes', 'fa-list'],
                'en_attente' => ['En attente', 'fa-clock'],
                'validee' => ['Validées', 'fa-check-circle'],
                'annulee' => ['Annulées', 'fa-times-circle'],
                'terminee' => ['Terminées', 'fa-flag-checkered'],
            ];
            foreach ($tab_labels as $val => [$label, $icon]):
                $is_active = $filter_statut === $val;
                $count = $val ? ($counts[$val] ?? 0) : array_sum($counts);
            ?>
            <a href="reservations.php?statut=<?= $val ?>"
               style="display:inline-flex;align-items:center;gap:8px;padding:8px 18px;border-radius:var(--radius-pill);font-size:0.83rem;font-weight:600;text-decoration:none;transition:var(--transition-fast);
                      <?= $is_active ? 'background:var(--gradient-primary);color:#fff;box-shadow:var(--shadow-glow);' : 'background:rgba(255,255,255,0.04);color:var(--color-text-muted);border:1px solid var(--color-border);' ?>">
                <i class="fas <?= $icon ?>"></i> <?= $label ?>
                <span style="background:rgba(255,255,255,0.2);border-radius:20px;padding:1px 7px;font-size:0.72rem;"><?= $count ?></span>
            </a>
            <?php endforeach; ?>
        </div>

        <!-- Barre de recherche -->
        <div class="glass-card p-3 mb-4">
            <form method="GET" style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;">
                <input type="hidden" name="statut" value="<?= htmlspecialchars($filter_statut) ?>">
                <div class="input-group-icon" style="flex:1;min-width:200px;">
                    <i class="fas fa-search"></i>
                    <input type="text" name="search" class="form-control-immo" placeholder="Rechercher un client ou un bien..."
                           value="<?= htmlspecialchars($filter_search) ?>">
                </div>
                <button type="submit" class="btn-primary-immo" style="padding:11px 20px;">
                    <i class="fas fa-search"></i> Chercher
                </button>
                <?php if ($filter_search): ?>
                <a href="reservations.php?statut=<?= $filter_statut ?>" class="btn-outline-immo" style="padding:10px 16px;">
                    <i class="fas fa-times"></i>
                </a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Tableau des réservations -->
        <div class="glass-card p-4">
            <div style="overflow-x:auto;">
                <table class="table-immo">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Client</th>
                            <th>Bien</th>
                            <th>Période</th>
                            <th>Montant estimé</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($reservations)): ?>
                        <tr>
                            <td colspan="7" style="text-align:center;padding:40px;color:var(--color-text-muted);">
                                <i class="fas fa-calendar" style="font-size:2rem;opacity:0.2;display:block;margin-bottom:10px;"></i>
                                Aucune réservation trouvée.
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($reservations as $r):
                            $d1 = new DateTime($r['date_debut']);
                            $d2 = new DateTime($r['date_fin']);
                            $days = $d1->diff($d2)->days;
                            $montant = round(($r['prix_mensuel'] / 30) * $days);
                        ?>
                        <tr>
                            <td style="color:var(--color-text-muted);font-size:0.82rem;font-weight:600;">#<?= $r['id'] ?></td>
                            <td>
                                <div style="font-weight:600;color:var(--color-text-primary);font-size:0.88rem;">
                                    <?= htmlspecialchars($r['prenom'] . ' ' . $r['nom']) ?>
                                </div>
                                <div style="font-size:0.75rem;color:var(--color-text-muted);"><?= htmlspecialchars($r['email']) ?></div>
                                <div style="font-size:0.75rem;color:var(--color-text-muted);">
                                    <i class="fas fa-phone me-1"></i><?= htmlspecialchars($r['telephone']) ?>
                                </div>
                            </td>
                            <td>
                                <div style="font-weight:600;color:var(--color-text-primary);font-size:0.88rem;"><?= htmlspecialchars($r['bien_titre']) ?></div>
                                <span style="background:rgba(108,99,255,0.1);color:var(--color-primary-light);border-radius:20px;padding:2px 8px;font-size:0.72rem;font-weight:600;"><?= ucfirst($r['bien_type']) ?></span>
                            </td>
                            <td style="font-size:0.83rem;color:var(--color-text-secondary);">
                                <div><?= date('d/m/Y', strtotime($r['date_debut'])) ?></div>
                                <div style="color:var(--color-text-muted);">→ <?= date('d/m/Y', strtotime($r['date_fin'])) ?></div>
                                <div style="font-size:0.75rem;color:var(--color-text-muted);"><?= $days ?> jour(s)</div>
                            </td>
                            <td style="font-weight:700;color:var(--color-accent-light);font-size:0.88rem;">
                                <?= number_format($montant, 0, ',', ' ') ?> FCFA
                            </td>
                            <td><span class="status-badge <?= $r['statut'] ?>"><?= ucfirst(str_replace('_',' ',$r['statut'])) ?></span></td>
                            <td>
                                <div style="display:flex;gap:6px;flex-wrap:wrap;">
                                    <?php if ($r['statut'] === 'en_attente'): ?>
                                    <a href="reservations.php?action=valider&id=<?= $r['id'] ?>"
                                       class="btn-outline-immo" style="font-size:0.75rem;padding:5px 10px;border-color:rgba(16,185,129,0.4);color:#6ee7b7;"
                                       data-confirm="Valider la réservation #<?= $r['id'] ?> ?">
                                        <i class="fas fa-check"></i> Valider
                                    </a>
                                    <a href="reservations.php?action=annuler&id=<?= $r['id'] ?>"
                                       class="btn-outline-immo" style="font-size:0.75rem;padding:5px 10px;border-color:rgba(239,68,68,0.4);color:#fca5a5;"
                                       data-confirm="Annuler la réservation #<?= $r['id'] ?> ?">
                                        <i class="fas fa-times"></i> Annuler
                                    </a>
                                    <?php elseif ($r['statut'] === 'validee'): ?>
                                    <a href="reservations.php?action=terminer&id=<?= $r['id'] ?>"
                                       class="btn-outline-immo" style="font-size:0.75rem;padding:5px 10px;"
                                       data-confirm="Marquer la réservation #<?= $r['id'] ?> comme terminée ?">
                                        <i class="fas fa-flag-checkered"></i> Terminer
                                    </a>
                                    <a href="reservations.php?action=annuler&id=<?= $r['id'] ?>"
                                       class="btn-outline-immo" style="font-size:0.75rem;padding:5px 10px;border-color:rgba(239,68,68,0.4);color:#fca5a5;"
                                       data-confirm="Annuler cette réservation validée ?">
                                        <i class="fas fa-times"></i>
                                    </a>
                                    <?php else: ?>
                                    <span style="color:var(--color-text-muted);font-size:0.78rem;">—</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
