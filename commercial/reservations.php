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

if ($res_id && in_array($action, ['valider', 'annuler', 'terminer', 'remettre_en_attente'])) {
    $new_status_map = [
        'valider'               => 'validee',
        'annuler'               => 'annulee',
        'terminer'              => 'terminee',
        'remettre_en_attente'   => 'en_attente'
    ];
    $new_status = $new_status_map[$action];

    // Mettre à jour le statut de la réservation
    $pdo->prepare("UPDATE reservations SET statut = ? WHERE id = ?")->execute([$new_status, $res_id]);

    // Si validation, mettre le bien en 'reserve'
    if ($action === 'valider') {
        $bid = $pdo->prepare("SELECT bien_id FROM reservations WHERE id = ?");
        $bid->execute([$res_id]);
        $b = $bid->fetchColumn();
        if ($b) $pdo->prepare("UPDATE biens SET statut = 'reserve' WHERE id = ?")->execute([$b]);
    }
    // Si annulation, fin ou remise en attente, vérifier l'état du bien
    if (in_array($action, ['annuler', 'terminer', 'remettre_en_attente'])) {
        $bid = $pdo->prepare("SELECT bien_id FROM reservations WHERE id = ?");
        $bid->execute([$res_id]);
        $b = $bid->fetchColumn();
        if ($b) {
            $other = $pdo->prepare("SELECT COUNT(*) FROM reservations WHERE bien_id=? AND statut='validee' AND id!=?");
            $other->execute([$b, $res_id]);
            if (!$other->fetchColumn()) {
                $pdo->prepare("UPDATE biens SET statut='disponible' WHERE id=?")->execute([$b]);
            }
        }
    }

    $status_labels = [
        'valider'             => 'validée',
        'annuler'             => 'annulée',
        'terminer'            => 'terminée',
        'remettre_en_attente' => 'remise en attente'
    ];

    $msg = urlencode("Réservation #" . $res_id . " " . $status_labels[$action] . " avec succès.");

    $referer = $_SERVER['HTTP_REFERER'] ?? '';
    if (str_contains($referer, 'index.php')) {
        header("Location: index.php?msg=" . $msg);
    } else {
        header("Location: reservations.php?msg=" . $msg);
    }
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

<div class="dashboard-layout">
    <?php require_once 'sidebar.php'; ?>
    <div class="main-content-with-sidebar" style="flex:1;">

        <div style="margin-bottom:32px;">
            <h1 style="font-size:1.85rem;font-weight:800;color:var(--color-text-primary);letter-spacing:-0.5px;margin-bottom:4px;">
                Gestion des Demandes de Réservation
            </h1>
            <p style="color:var(--color-text-muted);font-size:0.9rem;margin:0;">
                Validez, modifiez ou annulez les réservations enregistrées sur vos propriétés.
            </p>
        </div>

        <?php if (isset($_GET['msg'])): ?>
        <div class="alert-immo success mb-4">
            <i class="fas fa-check-circle me-2"></i> <?= htmlspecialchars($_GET['msg']) ?>
        </div>
        <?php endif; ?>

        <!-- Onglets de filtre par statut -->
        <div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:24px;">
            <?php
            $tab_labels = [
                '' => ['Toutes les demandes', 'fa-list'],
                'en_attente' => ['En attente', 'fa-clock'],
                'validee' => ['Validées', 'fa-check-circle'],
                'terminee' => ['Terminées', 'fa-flag-checkered'],
                'annulee' => ['Annulées', 'fa-times-circle'],
            ];
            foreach ($tab_labels as $val => [$label, $icon]):
                $is_active = $filter_statut === $val;
                $count = $val ? ($counts[$val] ?? 0) : array_sum($counts);
            ?>
            <a href="reservations.php?statut=<?= $val ?>"
               style="display:inline-flex;align-items:center;gap:8px;padding:9px 18px;border-radius:20px;font-size:0.83rem;font-weight:700;text-decoration:none;transition:var(--transition-fast);
                      <?= $is_active ? 'background:var(--gradient-primary);color:#fff;box-shadow:0 4px 12px rgba(108,99,255,0.3);' : 'background:var(--color-bg-card);color:var(--color-text-muted);border:1px solid var(--color-border);' ?>">
                <i class="fas <?= $icon ?>"></i> <?= $label ?>
                <span style="background:<?= $is_active ? 'rgba(255,255,255,0.25)' : 'rgba(108,99,255,0.1)' ?>;border-radius:20px;padding:2px 8px;font-size:0.72rem;"><?= $count ?></span>
            </a>
            <?php endforeach; ?>
        </div>

        <!-- Barre de recherche -->
        <div class="glass-card p-3 mb-4" style="border:1px solid var(--color-border);">
            <form method="GET" style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;">
                <input type="hidden" name="statut" value="<?= htmlspecialchars($filter_statut) ?>">
                <div style="flex:1;min-width:240px;position:relative;">
                    <i class="fas fa-search" style="position:absolute;left:14px;top:50%;transform:translateY(-50%);color:var(--color-text-muted);"></i>
                    <input type="text" name="search" class="form-control-immo" placeholder="Rechercher par nom de client, email ou titre de bien..."
                           style="padding-left:42px;"
                           value="<?= htmlspecialchars($filter_search) ?>">
                </div>
                <button type="submit" class="btn-primary-immo" style="padding:10px 20px;font-size:0.88rem;">
                    <i class="fas fa-search me-1"></i> Rechercher
                </button>
                <?php if ($filter_search): ?>
                <a href="reservations.php?statut=<?= $filter_statut ?>" class="btn-outline-immo" style="padding:10px 16px;">
                    <i class="fas fa-times me-1"></i> Réinitialiser
                </a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Tableau des réservations -->
        <div class="glass-card p-4">
            <div class="table-responsive-immo">
                <table class="table-immo">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Client</th>
                            <th>Bien Immobiliers</th>
                            <th>Période & Durée</th>
                            <th>Montant Estimé</th>
                            <th>Statut</th>
                            <th style="text-align:right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($reservations)): ?>
                        <tr>
                            <td colspan="7" style="text-align:center;padding:40px;color:var(--color-text-muted);">
                                <i class="fas fa-calendar-times mb-2" style="font-size:2rem;opacity:0.3;display:block;"></i>
                                Aucune réservation ne correspond à vos critères.
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($reservations as $r):
                            $d1 = new DateTime($r['date_debut']);
                            $d2 = new DateTime($r['date_fin']);
                            $days = max(1, $d1->diff($d2)->days);
                            $montant = round(($r['prix_mensuel'] / 30) * $days);
                        ?>
                        <tr>
                            <td style="color:var(--color-text-muted);font-size:0.82rem;font-weight:700;">#<?= $r['id'] ?></td>
                            <td>
                                <div style="font-weight:700;color:var(--color-text-primary);font-size:0.88rem;">
                                    <?= htmlspecialchars($r['prenom'] . ' ' . $r['nom']) ?>
                                </div>
                                <div style="font-size:0.75rem;color:var(--color-text-muted);margin-top:2px;">
                                    <i class="far fa-envelope me-1"></i><?= htmlspecialchars($r['email']) ?>
                                </div>
                                <?php if (!empty($r['telephone'])): ?>
                                <div style="font-size:0.75rem;color:var(--color-text-muted);">
                                    <i class="fas fa-phone me-1" style="color:var(--color-primary-light);"></i><?= htmlspecialchars($r['telephone']) ?>
                                </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div style="font-weight:700;color:var(--color-text-primary);font-size:0.88rem;max-width:200px;white-space:normal;line-height:1.3;"><?= htmlspecialchars($r['bien_titre']) ?></div>
                                <span style="background:rgba(108,99,255,0.1);color:var(--color-primary-light);border-radius:20px;padding:2px 8px;font-size:0.72rem;font-weight:700;margin-top:4px;display:inline-block;">
                                    <?= ucfirst($r['bien_type']) ?>
                                </span>
                            </td>
                            <td style="font-size:0.83rem;color:var(--color-text-secondary);white-space:nowrap;">
                                <div><i class="far fa-calendar-alt me-1" style="color:var(--color-primary-light);"></i> Du <?= date('d/m/Y', strtotime($r['date_debut'])) ?></div>
                                <div style="color:var(--color-text-muted);"><i class="fas fa-arrow-right me-1" style="font-size:0.7rem;opacity:0.5;"></i> Au <?= date('d/m/Y', strtotime($r['date_fin'])) ?></div>
                                <div style="font-size:0.74rem;color:var(--color-accent-light);font-weight:600;margin-top:2px;"><?= $days ?> jour(s)</div>
                            </td>
                            <td style="font-weight:800;color:var(--color-accent-light);font-size:0.9rem;white-space:nowrap;">
                                <?= number_format($montant, 0, ',', ' ') ?> <span style="font-size:0.72rem;font-weight:600;">FCFA</span>
                            </td>
                            <td>
                                <span class="status-badge <?= $r['statut'] ?>">
                                    <?= ucfirst(str_replace('_',' ',$r['statut'])) ?>
                                </span>
                            </td>
                            <td style="text-align:right;">
                                <div style="display:inline-flex;gap:6px;justify-content:flex-end;">
                                    <?php if ($r['statut'] === 'en_attente'): ?>
                                    <a href="reservations.php?action=valider&id=<?= $r['id'] ?>"
                                       class="btn-outline-immo" style="font-size:0.78rem;padding:6px 12px;border-color:rgba(16,185,129,0.4);color:#10b981;"
                                       title="Valider cette réservation"
                                       onclick="return confirm('Valider la réservation #<?= $r['id'] ?> ?');">
                                        <i class="fas fa-check me-1"></i> Valider
                                    </a>
                                    <a href="reservations.php?action=annuler&id=<?= $r['id'] ?>"
                                       class="btn-outline-immo" style="font-size:0.78rem;padding:6px 12px;border-color:rgba(239,68,68,0.4);color:#ef4444;"
                                       title="Refuser/Annuler la réservation"
                                       onclick="return confirm('Annuler la réservation #<?= $r['id'] ?> ?');">
                                        <i class="fas fa-times me-1"></i> Annuler
                                    </a>
                                    <?php elseif ($r['statut'] === 'validee'): ?>
                                    <a href="reservations.php?action=terminer&id=<?= $r['id'] ?>"
                                       class="btn-outline-immo" style="font-size:0.78rem;padding:6px 12px;"
                                       title="Marquer comme terminée"
                                       onclick="return confirm('Marquer la réservation #<?= $r['id'] ?> comme terminée ?');">
                                        <i class="fas fa-flag-checkered me-1"></i> Terminer
                                    </a>
                                    <a href="reservations.php?action=annuler&id=<?= $r['id'] ?>"
                                       class="btn-outline-immo" style="font-size:0.78rem;padding:6px 12px;border-color:rgba(239,68,68,0.4);color:#ef4444;"
                                       title="Annuler la réservation"
                                       onclick="return confirm('Annuler la réservation #<?= $r['id'] ?> ?');">
                                        <i class="fas fa-times me-1"></i> Annuler
                                    </a>
                                    <?php elseif ($r['statut'] === 'annulee'): ?>
                                    <a href="reservations.php?action=valider&id=<?= $r['id'] ?>"
                                       class="btn-outline-immo" style="font-size:0.78rem;padding:6px 12px;border-color:rgba(16,185,129,0.4);color:#10b981;"
                                       title="Réactiver et valider"
                                       onclick="return confirm('Réactiver la réservation #<?= $r['id'] ?> ?');">
                                        <i class="fas fa-check me-1"></i> Réactiver
                                    </a>
                                    <?php else: ?>
                                    <a href="reservations.php?action=remettre_en_attente&id=<?= $r['id'] ?>"
                                       class="btn-outline-immo" style="font-size:0.78rem;padding:6px 12px;"
                                       title="Remettre en attente"
                                       onclick="return confirm('Remettre la réservation #<?= $r['id'] ?> en attente ?');">
                                        <i class="fas fa-undo me-1"></i> En attente
                                    </a>
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
