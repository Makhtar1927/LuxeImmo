<?php
/**
 * commercial/index.php — Dashboard principal du commercial
 */
require_once '../config/db.php';
require_once '../includes/auth_check.php';
require_role('commercial');

// Statistiques générales
$stats = [
    'biens'          => $pdo->query("SELECT COUNT(*) FROM biens")->fetchColumn(),
    'disponibles'    => $pdo->query("SELECT COUNT(*) FROM biens WHERE statut='disponible'")->fetchColumn(),
    'clients'        => $pdo->query("SELECT COUNT(*) FROM utilisateurs WHERE role='client'")->fetchColumn(),
    'reservations'   => $pdo->query("SELECT COUNT(*) FROM reservations")->fetchColumn(),
    'en_attente'     => $pdo->query("SELECT COUNT(*) FROM reservations WHERE statut='en_attente'")->fetchColumn(),
    'revenus_mois'   => $pdo->query("SELECT COALESCE(SUM(b.prix_mensuel),0) FROM reservations r JOIN biens b ON r.bien_id=b.id WHERE r.statut='validee' AND MONTH(r.date_creation)=MONTH(NOW())")->fetchColumn(),
];

// Dernières réservations (10)
$last_reservations = $pdo->query("
    SELECT r.*, b.titre AS bien_titre, b.type AS bien_type,
           u.nom, u.prenom, u.email
    FROM reservations r
    JOIN biens b ON r.bien_id = b.id
    JOIN utilisateurs u ON r.client_id = u.id
    ORDER BY r.date_creation DESC LIMIT 10
")->fetchAll();

$page_title = 'Dashboard Commercial — LuxeImmo';
require_once '../includes/header.php';
?>

<div class="dashboard-layout">
    <!-- Sidebar -->
    <?php require_once 'sidebar.php'; ?>

    <!-- Main Content -->
    <div class="main-content-with-sidebar" style="flex:1;">
        <div style="margin-bottom:32px;">
            <h1 style="font-size:1.8rem;font-weight:800;color:var(--color-text-primary);letter-spacing:-0.5px;margin-bottom:6px;">
                Tableau de Bord
            </h1>
            <p style="color:var(--color-text-muted);font-size:0.9rem;">
                Bienvenue, <?= htmlspecialchars($_SESSION['prenom']) ?>. Vue d'ensemble de l'agence.
            </p>
        </div>

        <?php if (isset($_GET['msg'])): ?>
        <div class="alert-immo success mb-4">
            <i class="fas fa-check-circle"></i> <?= htmlspecialchars($_GET['msg']) ?>
        </div>
        <?php endif; ?>

        <!-- Stats Cards -->
        <div class="row g-4 mb-5">
            <?php
            $stat_items = [
                ['fas fa-building',     'Biens totaux',        $stats['biens'],        ''],
                ['fas fa-check-circle', 'Disponibles',         $stats['disponibles'],  'success'],
                ['fas fa-users',        'Clients actifs',      $stats['clients'],      ''],
                ['fas fa-clock',        'Réservations att.',   $stats['en_attente'],   'accent'],
            ];
            foreach ($stat_items as $i => [$icon, $label, $value, $extra]):
            ?>
            <div class="col-sm-6 col-xl-3">
                <div class="stat-card <?= $extra ?>">
                    <div class="stat-card-icon <?= $extra === 'success' ? 'style="background:rgba(16,185,129,0.15);color:#6ee7b7;"' : '' ?>">
                        <i class="<?= $icon ?>"></i>
                    </div>
                    <div class="stat-card-value"><?= $value ?></div>
                    <div class="stat-card-label"><?= $label ?></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Revenus du mois -->
        <div class="glass-card p-4 mb-4"
             style="background:linear-gradient(135deg,rgba(108,99,255,0.12),rgba(167,139,250,0.08));border-color:rgba(108,99,255,0.2);">
            <div style="display:flex;align-items:center;gap:16px;">
                <div style="width:56px;height:56px;background:var(--gradient-primary);border-radius:16px;display:flex;align-items:center;justify-content:center;font-size:1.5rem;color:#fff;flex-shrink:0;">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div>
                    <div style="font-size:0.78rem;color:var(--color-text-muted);text-transform:uppercase;letter-spacing:0.8px;font-weight:700;">
                        Revenus locatifs ce mois
                    </div>
                    <div style="font-size:2rem;font-weight:800;color:var(--color-accent-light);letter-spacing:-1px;line-height:1.2;">
                        <?= number_format($stats['revenus_mois'], 0, ',', ' ') ?> FCFA
                    </div>
                </div>
            </div>
        </div>

        <!-- Tableau des dernières réservations -->
        <div class="glass-card p-4">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;flex-wrap:wrap;gap:12px;">
                <h2 style="font-size:1.1rem;font-weight:700;color:var(--color-text-primary);">
                    <i class="fas fa-calendar-check me-2" style="color:var(--color-primary-light);"></i>
                    Dernières Réservations
                </h2>
                <a href="reservations.php" class="btn-outline-immo" style="font-size:0.82rem;padding:8px 16px;">
                    Voir toutes <i class="fas fa-arrow-right ms-1"></i>
                </a>
            </div>

            <div style="overflow-x:auto;">
                <table class="table-immo">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Client</th>
                            <th>Bien</th>
                            <th>Période</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($last_reservations as $r): ?>
                        <tr>
                            <td style="color:var(--color-text-muted);font-size:0.82rem;">#<?= $r['id'] ?></td>
                            <td>
                                <div style="font-weight:600;color:var(--color-text-primary);font-size:0.88rem;">
                                    <?= htmlspecialchars($r['prenom'] . ' ' . $r['nom']) ?>
                                </div>
                                <div style="font-size:0.75rem;color:var(--color-text-muted);"><?= htmlspecialchars($r['email']) ?></div>
                            </td>
                            <td>
                                <div style="font-weight:600;color:var(--color-text-primary);font-size:0.88rem;"><?= htmlspecialchars($r['bien_titre']) ?></div>
                                <span style="background:rgba(108,99,255,0.1);color:var(--color-primary-light);border-radius:20px;padding:2px 8px;font-size:0.72rem;font-weight:600;">
                                    <?= ucfirst($r['bien_type']) ?>
                                </span>
                            </td>
                            <td style="font-size:0.83rem;color:var(--color-text-secondary);">
                                <?= date('d/m/Y', strtotime($r['date_debut'])) ?> → <?= date('d/m/Y', strtotime($r['date_fin'])) ?>
                            </td>
                            <td><span class="status-badge <?= $r['statut'] ?>"><?= ucfirst(str_replace('_', ' ', $r['statut'])) ?></span></td>
                            <td>
                                <div style="display:flex;gap:6px;flex-wrap:wrap;">
                                    <?php if ($r['statut'] === 'en_attente'): ?>
                                    <a href="reservations.php?action=valider&id=<?= $r['id'] ?>"
                                       class="btn-outline-immo"
                                       style="font-size:0.75rem;padding:5px 10px;border-color:rgba(16,185,129,0.4);color:#6ee7b7;"
                                       onclick="return confirm('Êtes-vous sûr de vouloir VALIDER la réservation #<?= $r['id'] ?> ?');">
                                        <i class="fas fa-check"></i> Valider
                                    </a>
                                    <a href="reservations.php?action=annuler&id=<?= $r['id'] ?>"
                                       class="btn-outline-immo"
                                       style="font-size:0.75rem;padding:5px 10px;border-color:rgba(239,68,68,0.4);color:#fca5a5;"
                                       onclick="return confirm('Êtes-vous sûr de vouloir ANNULER la réservation #<?= $r['id'] ?> ?');">
                                        <i class="fas fa-times"></i> Annuler
                                    </a>
                                    <?php elseif ($r['statut'] === 'validee'): ?>
                                    <a href="reservations.php?action=terminer&id=<?= $r['id'] ?>"
                                       class="btn-outline-immo"
                                       style="font-size:0.75rem;padding:5px 10px;"
                                       onclick="return confirm('Marquer la réservation #<?= $r['id'] ?> comme terminée ?');">
                                        <i class="fas fa-flag-checkered"></i> Terminer
                                    </a>
                                    <a href="reservations.php?action=annuler&id=<?= $r['id'] ?>"
                                       class="btn-outline-immo"
                                       style="font-size:0.75rem;padding:5px 10px;border-color:rgba(239,68,68,0.4);color:#fca5a5;"
                                       onclick="return confirm('ANNULER la réservation #<?= $r['id'] ?> ?');">
                                        <i class="fas fa-times"></i> Annuler
                                    </a>
                                    <?php elseif ($r['statut'] === 'annulee'): ?>
                                    <a href="reservations.php?action=valider&id=<?= $r['id'] ?>"
                                       class="btn-outline-immo"
                                       style="font-size:0.75rem;padding:5px 10px;border-color:rgba(16,185,129,0.4);color:#6ee7b7;"
                                       onclick="return confirm('Ré-activer et VALIDER la réservation #<?= $r['id'] ?> ?');">
                                        <i class="fas fa-check"></i> Valider
                                    </a>
                                    <a href="reservations.php?action=remettre_en_attente&id=<?= $r['id'] ?>"
                                       class="btn-outline-immo"
                                       style="font-size:0.75rem;padding:5px 10px;"
                                       onclick="return confirm('Remettre la réservation #<?= $r['id'] ?> en attente ?');">
                                        <i class="fas fa-undo"></i> En attente
                                    </a>
                                    <?php else: ?>
                                    <a href="reservations.php?action=remettre_en_attente&id=<?= $r['id'] ?>"
                                       class="btn-outline-immo"
                                       style="font-size:0.75rem;padding:5px 10px;"
                                       onclick="return confirm('Réouvrir la réservation #<?= $r['id'] ?> ?');">
                                        <i class="fas fa-undo"></i> Réouvrir
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
