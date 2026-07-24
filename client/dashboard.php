<?php
/**
 * client/dashboard.php
 * Espace client : suivi des réservations et statistiques personnelles
 */
require_once '../config/db.php';
require_once '../includes/auth_check.php';
require_role('client');

$client_id = $_SESSION['user_id'];

// Toutes les réservations du client (avec infos du bien)
$stmt = $pdo->prepare("
    SELECT r.*, b.titre, b.type, b.prix_mensuel, b.ville,
           COALESCE((SELECT chemin FROM images WHERE bien_id = b.id AND est_principale = 1 LIMIT 1), (SELECT chemin FROM images WHERE bien_id = b.id ORDER BY id ASC LIMIT 1)) AS img
    FROM reservations r
    JOIN biens b ON r.bien_id = b.id
    WHERE r.client_id = ?
    ORDER BY r.date_creation DESC
");
$stmt->execute([$client_id]);
$reservations = $stmt->fetchAll();

// Statistiques
$stats = [
    'total'      => count($reservations),
    'en_attente' => count(array_filter($reservations, fn($r) => $r['statut'] === 'en_attente')),
    'validees'   => count(array_filter($reservations, fn($r) => $r['statut'] === 'validee')),
    'favoris'    => $pdo->prepare("SELECT COUNT(*) FROM favoris WHERE client_id = ?") ? null : 0,
];
$fav_count_stmt = $pdo->prepare("SELECT COUNT(*) FROM favoris WHERE client_id = ?");
$fav_count_stmt->execute([$client_id]);
$stats['favoris'] = $fav_count_stmt->fetchColumn();

$page_title = 'Mon Espace — LuxeImmo';
require_once '../includes/header.php';
?>

<div class="dashboard-layout">
    <!-- Sidebar -->
    <?php require_once 'sidebar.php'; ?>

    <!-- Main Content -->
    <div class="main-content-with-sidebar" style="flex:1;">
        <!-- Header page -->
        <div style="margin-bottom:32px;">
            <h1 style="font-size:1.8rem;font-weight:800;color:var(--color-text-primary);letter-spacing:-0.5px;margin-bottom:6px;">
                Bonjour, <?= htmlspecialchars($_SESSION['prenom']) ?> 👋
            </h1>
            <p style="color:var(--color-text-muted); font-size:0.9rem;">
                Suivez vos réservations et gérez votre espace personnel.
            </p>
        </div>

        <!-- Stats Cards -->
        <div class="row g-4 mb-5">
            <div class="col-sm-6 col-xl-3">
                <div class="stat-card">
                    <div class="stat-card-icon"><i class="fas fa-calendar"></i></div>
                    <div class="stat-card-value"><?= $stats['total'] ?></div>
                    <div class="stat-card-label">Réservations totales</div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="stat-card" style="--color: var(--color-warning);">
                    <div class="stat-card-icon" style="background:rgba(245,158,11,0.15);color:var(--color-accent-light);">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-card-value"><?= $stats['en_attente'] ?></div>
                    <div class="stat-card-label">En attente de validation</div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="stat-card">
                    <div class="stat-card-icon" style="background:rgba(16,185,129,0.15);color:#6ee7b7;">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-card-value"><?= $stats['validees'] ?></div>
                    <div class="stat-card-label">Réservations validées</div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="stat-card accent">
                    <div class="stat-card-icon"><i class="fas fa-heart"></i></div>
                    <div class="stat-card-value"><?= $stats['favoris'] ?></div>
                    <div class="stat-card-label">Biens en favoris</div>
                </div>
            </div>
        </div>

        <!-- Tableau des réservations -->
        <div class="glass-card p-4">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px;flex-wrap:wrap;gap:12px;">
                <h2 style="font-size:1.1rem;font-weight:700;color:var(--color-text-primary);">
                    <i class="fas fa-calendar-check me-2" style="color:var(--color-primary-light);"></i>
                    Mes Réservations
                </h2>
                <a href="../index.php" class="btn-primary-immo" style="font-size:0.82rem;padding:8px 18px;">
                    <i class="fas fa-plus"></i> Nouvelle réservation
                </a>
            </div>

            <?php if (empty($reservations)): ?>
                <div style="text-align:center;padding:48px;color:var(--color-text-muted);">
                    <i class="fas fa-calendar" style="font-size:3rem;opacity:0.2;display:block;margin-bottom:16px;"></i>
                    <p style="font-size:1rem;font-weight:600;color:var(--color-text-secondary);">Aucune réservation pour le moment.</p>
                    <a href="../index.php" class="btn-primary-immo mt-3" style="font-size:0.88rem;">
                        <i class="fas fa-search"></i> Explorer les biens
                    </a>
                </div>
            <?php else: ?>
                <div style="overflow-x:auto;">
                    <table class="table-immo">
                        <thead>
                            <tr>
                                <th>Bien</th>
                                <th>Type</th>
                                <th>Période</th>
                                <th>Prix / mois</th>
                                <th>Statut</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reservations as $r): ?>
                            <tr>
                                <td>
                                    <div style="display:flex;align-items:center;gap:12px;">
                                        <?php if ($r['img']): ?>
                                        <img src="../<?= htmlspecialchars($r['img']) ?>"
                                             style="width:44px;height:36px;border-radius:8px;object-fit:cover;flex-shrink:0;"
                                             onerror="this.style.display='none'">
                                        <?php endif; ?>
                                        <div>
                                            <div style="font-weight:600;color:var(--color-text-primary);font-size:0.88rem;">
                                                <?= htmlspecialchars($r['titre']) ?>
                                            </div>
                                            <div style="font-size:0.76rem;color:var(--color-text-muted);">
                                                <i class="fas fa-map-marker-alt me-1"></i><?= htmlspecialchars($r['ville']) ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td><span style="background:rgba(108,99,255,0.1);color:var(--color-primary-light);border-radius:20px;padding:3px 10px;font-size:0.75rem;font-weight:600;"><?= ucfirst($r['type']) ?></span></td>
                                <td style="font-size:0.83rem;">
                                    <div><?= date('d/m/Y', strtotime($r['date_debut'])) ?></div>
                                    <div style="color:var(--color-text-muted);">→ <?= date('d/m/Y', strtotime($r['date_fin'])) ?></div>
                                </td>
                                <td style="font-weight:700;color:var(--color-accent-light);font-size:0.88rem;">
                                    <?= number_format($r['prix_mensuel'], 0, ',', ' ') ?> FCFA
                                </td>
                                <td><span class="status-badge <?= $r['statut'] ?>"><?= ucfirst(str_replace('_', ' ', $r['statut'])) ?></span></td>
                                <td>
                                    <a href="../detail.php?id=<?= $r['bien_id'] ?>" class="btn-outline-immo" style="font-size:0.78rem;padding:6px 12px;">
                                        <i class="fas fa-eye"></i> Voir
                                    </a>
                                    <?php if ($r['statut'] === 'en_attente'): ?>
                                    <a href="annuler_reservation.php?id=<?= $r['id'] ?>" class="btn-outline-immo ms-1"
                                       style="font-size:0.78rem;padding:6px 12px;border-color:rgba(239,68,68,0.4);color:#fca5a5;"
                                       data-confirm="Êtes-vous sûr de vouloir annuler cette réservation ?">
                                        <i class="fas fa-times"></i> Annuler
                                    </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
