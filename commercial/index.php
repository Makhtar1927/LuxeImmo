<?php
require_once '../config/db.php';
require_once '../includes/auth_check.php';
require_role('commercial');

$stats = [
    'biens'          => $pdo->query("SELECT COUNT(*) FROM biens")->fetchColumn(),
    'disponibles'    => $pdo->query("SELECT COUNT(*) FROM biens WHERE statut='disponible'")->fetchColumn(),
    'clients'        => $pdo->query("SELECT COUNT(*) FROM utilisateurs WHERE role='client'")->fetchColumn(),
    'reservations'   => $pdo->query("SELECT COUNT(*) FROM reservations")->fetchColumn(),
    'en_attente'     => $pdo->query("SELECT COUNT(*) FROM reservations WHERE statut='en_attente'")->fetchColumn(),
    'revenus_mois'   => $pdo->query("SELECT COALESCE(SUM(b.prix_mensuel),0) FROM reservations r JOIN biens b ON r.bien_id=b.id WHERE r.statut='validee' AND MONTH(r.date_creation)=MONTH(NOW())")->fetchColumn(),
];

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
    <?php require_once 'sidebar.php'; ?>

    <div class="main-content-with-sidebar" style="flex:1;">
        
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:32px;flex-wrap:wrap;gap:16px;">
            <div>
                <h1 style="font-size:1.85rem;font-weight:800;color:var(--color-text-primary);letter-spacing:-0.5px;margin-bottom:4px;">
                    Tableau de Bord
                </h1>
                <p style="color:var(--color-text-muted);font-size:0.9rem;margin:0;">
                    Ravi de vous revoir, <strong style="color:var(--color-primary-light);"><?= htmlspecialchars($_SESSION['prenom']) ?></strong> 👋. Voici un aperçu de vos activités commerciales.
                </p>
            </div>
            <div style="display:flex;gap:10px;">
                <a href="biens.php?action=ajouter#form-bien" class="btn-primary-immo" style="padding:10px 18px;font-size:0.88rem;">
                    <i class="fas fa-plus me-2"></i>Nouveau bien
                </a>
            </div>
        </div>

        <?php if (isset($_GET['msg'])): ?>
        <div class="alert-immo success mb-4">
            <i class="fas fa-check-circle me-2"></i> <?= htmlspecialchars($_GET['msg']) ?>
        </div>
        <?php endif; ?>

        <div class="row g-3 g-md-4 mb-4">
            <div class="col-12 col-sm-6 col-lg-3">
                <div class="stat-card">
                    <div class="stat-card-icon">
                        <i class="fas fa-building"></i>
                    </div>
                    <div class="stat-card-value"><?= number_format($stats['biens'], 0, ',', ' ') ?></div>
                    <div class="stat-card-label">Biens au catalogue</div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-lg-3">
                <div class="stat-card success">
                    <div class="stat-card-icon" style="background:rgba(16,185,129,0.15);color:#6ee7b7;">
                        <i class="fas fa-key"></i>
                    </div>
                    <div class="stat-card-value" style="color:#10b981;"><?= number_format($stats['disponibles'], 0, ',', ' ') ?></div>
                    <div class="stat-card-label">Biens disponibles</div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-lg-3">
                <div class="stat-card accent">
                    <div class="stat-card-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-card-value" style="color:var(--color-accent-light);"><?= number_format($stats['en_attente'], 0, ',', ' ') ?></div>
                    <div class="stat-card-label">Réservations en attente</div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-lg-3">
                <div class="stat-card">
                    <div class="stat-card-icon" style="background:rgba(59,130,246,0.15);color:#60a5fa;">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-card-value"><?= number_format($stats['clients'], 0, ',', ' ') ?></div>
                    <div class="stat-card-label">Clients enregistrés</div>
                </div>
            </div>
        </div>

        <div class="glass-card p-4 mb-4"
             style="background:linear-gradient(135deg,rgba(108,99,255,0.14),rgba(167,139,250,0.06));border:1px solid rgba(108,99,255,0.25);border-radius:16px;">
            <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:20px;">
                <div style="display:flex;align-items:center;gap:18px;">
                    <div style="width:58px;height:58px;background:var(--gradient-primary);border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:1.6rem;color:#fff;box-shadow:0 8px 20px rgba(108,99,255,0.3);flex-shrink:0;">
                        <i class="fas fa-wallet"></i>
                    </div>
                    <div>
                        <div style="font-size:0.75rem;color:var(--color-text-muted);text-transform:uppercase;letter-spacing:1px;font-weight:800;margin-bottom:2px;">
                            Revenus locatifs validés ce mois
                        </div>
                        <div style="font-size:2rem;font-weight:800;color:var(--color-accent-light);letter-spacing:-0.5px;line-height:1.2;">
                            <?= number_format($stats['revenus_mois'], 0, ',', ' ') ?> <span style="font-size:1.1rem;font-weight:700;">FCFA</span>
                        </div>
                    </div>
                </div>
                <div>
                    <a href="reservations.php" class="btn-outline-immo" style="padding:10px 18px;font-size:0.85rem;border-color:rgba(108,99,255,0.4);color:var(--color-primary-light);">
                        Gérer les réservations <i class="fas fa-arrow-right ms-2"></i>
                    </a>
                </div>
            </div>
        </div>

        <div class="glass-card p-4">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;flex-wrap:wrap;gap:12px;">
                <div>
                    <h2 style="font-size:1.15rem;font-weight:700;color:var(--color-text-primary);margin:0;">
                        <i class="fas fa-calendar-check me-2" style="color:var(--color-primary-light);"></i>
                        Dernières Réservations
                    </h2>
                    <p style="font-size:0.82rem;color:var(--color-text-muted);margin:4px 0 0 0;">Suivi en temps réel des demandes de réservation</p>
                </div>
                <a href="reservations.php" class="btn-outline-immo" style="font-size:0.82rem;padding:8px 16px;">
                    Voir toutes les réservations <i class="fas fa-arrow-right ms-1"></i>
                </a>
            </div>

            <div class="table-responsive-immo">
                <table class="table-immo">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Client</th>
                            <th>Bien Immobilier</th>
                            <th>Période de Location</th>
                            <th>Statut</th>
                            <th style="text-align:right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($last_reservations)): ?>
                        <tr>
                            <td colspan="6" style="text-align:center;padding:32px;color:var(--color-text-muted);">
                                <i class="fas fa-inbox fa-2x mb-2" style="opacity:0.5;"></i><br>
                                Aucune réservation enregistrée pour le moment.
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($last_reservations as $r): ?>
                        <tr>
                            <td style="color:var(--color-text-muted);font-size:0.82rem;font-weight:700;">#<?= $r['id'] ?></td>
                            <td>
                                <div style="font-weight:600;color:var(--color-text-primary);font-size:0.88rem;">
                                    <?= htmlspecialchars($r['prenom'] . ' ' . $r['nom']) ?>
                                </div>
                                <div style="font-size:0.75rem;color:var(--color-text-muted);"><?= htmlspecialchars($r['email']) ?></div>
                            </td>
                            <td>
                                <div style="font-weight:600;color:var(--color-text-primary);font-size:0.88rem;max-width:200px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                                    <?= htmlspecialchars($r['bien_titre']) ?>
                                </div>
                                <span style="background:rgba(108,99,255,0.1);color:var(--color-primary-light);border-radius:20px;padding:2px 8px;font-size:0.72rem;font-weight:600;">
                                    <?= ucfirst($r['bien_type']) ?>
                                </span>
                            </td>
                            <td style="font-size:0.83rem;color:var(--color-text-secondary);white-space:nowrap;">
                                <i class="far fa-calendar-alt me-1" style="color:var(--color-primary-light);"></i>
                                <?= date('d/m/Y', strtotime($r['date_debut'])) ?> → <?= date('d/m/Y', strtotime($r['date_fin'])) ?>
                            </td>
                            <td>
                                <span class="status-badge <?= $r['statut'] ?>">
                                    <?= ucfirst(str_replace('_', ' ', $r['statut'])) ?>
                                </span>
                            </td>
                            <td style="text-align:right;">
                                <div style="display:inline-flex;gap:6px;justify-content:flex-end;">
                                    <?php if ($r['statut'] === 'en_attente'): ?>
                                    <a href="reservations.php?action=valider&id=<?= $r['id'] ?>"
                                       class="btn-outline-immo"
                                       style="font-size:0.78rem;padding:6px 12px;border-color:rgba(16,185,129,0.4);color:#10b981;"
                                       title="Valider la réservation"
                                       onclick="return confirm('Valider la réservation #<?= $r['id'] ?> ?');">
                                        <i class="fas fa-check me-1"></i> Valider
                                    </a>
                                    <a href="reservations.php?action=annuler&id=<?= $r['id'] ?>"
                                       class="btn-outline-immo"
                                       style="font-size:0.78rem;padding:6px 12px;border-color:rgba(239,68,68,0.4);color:#ef4444;"
                                       title="Annuler la réservation"
                                       onclick="return confirm('Annuler la réservation #<?= $r['id'] ?> ?');">
                                        <i class="fas fa-times me-1"></i> Annuler
                                    </a>
                                    <?php elseif ($r['statut'] === 'validee'): ?>
                                    <a href="reservations.php?action=terminer&id=<?= $r['id'] ?>"
                                       class="btn-outline-immo"
                                       style="font-size:0.78rem;padding:6px 12px;"
                                       title="Marquer comme terminée"
                                       onclick="return confirm('Terminer la réservation #<?= $r['id'] ?> ?');">
                                        <i class="fas fa-flag-checkered me-1"></i> Terminer
                                    </a>
                                    <?php else: ?>
                                    <a href="reservations.php" class="btn-outline-immo" style="font-size:0.78rem;padding:6px 12px;">
                                        <i class="fas fa-eye me-1"></i> Détails
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
