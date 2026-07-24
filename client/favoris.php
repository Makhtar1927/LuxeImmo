<?php
/**
 * client/favoris.php — Liste des biens favoris du client
 */
require_once '../config/db.php';
require_once '../includes/auth_check.php';
require_role('client');

$client_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("
    SELECT b.*, f.id AS favori_id,
           COALESCE((SELECT chemin FROM images WHERE bien_id = b.id AND est_principale = 1 LIMIT 1), (SELECT chemin FROM images WHERE bien_id = b.id ORDER BY id ASC LIMIT 1)) AS img
    FROM favoris f
    JOIN biens b ON f.bien_id = b.id
    WHERE f.client_id = ?
    ORDER BY f.id DESC
");
$stmt->execute([$client_id]);
$favoris = $stmt->fetchAll();

$page_title = 'Mes Favoris — LuxeImmo';
require_once '../includes/header.php';
?>

<div class="dashboard-layout">
    <!-- Sidebar -->
    <?php require_once 'sidebar.php'; ?>

    <!-- Main Content -->
    <div class="main-content-with-sidebar" style="flex:1;">
        <div style="margin-bottom:32px;">
            <h1 style="font-size:1.8rem;font-weight:800;color:var(--color-text-primary);letter-spacing:-0.5px;margin-bottom:6px;">
                <i class="fas fa-heart me-2" style="background:var(--gradient-accent);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;"></i>
                Mes Favoris
            </h1>
            <p style="color:var(--color-text-muted); font-size:0.9rem;">
                <?= count($favoris) ?> bien(s) sauvegardé(s)
            </p>
        </div>

        <?php if (empty($favoris)): ?>
        <div style="text-align:center;padding:64px;background:var(--color-bg-glass);border:1px solid var(--color-border);border-radius:var(--radius-xl);backdrop-filter:blur(20px);">
            <i class="far fa-heart" style="font-size:4rem;opacity:0.2;display:block;margin-bottom:20px;"></i>
            <p style="font-size:1.1rem;font-weight:600;color:var(--color-text-secondary);margin-bottom:8px;">Aucun bien en favori</p>
            <p style="color:var(--color-text-muted);font-size:0.9rem;margin-bottom:24px;">
                Naviguez parmi nos offres et cliquez sur ❤ pour sauvegarder vos coups de cœur.
            </p>
            <a href="../index.php" class="btn-primary-immo"><i class="fas fa-search"></i> Explorer les biens</a>
        </div>
        <?php else: ?>
        <div class="row g-4">
            <?php foreach ($favoris as $b): ?>
            <div class="col-xl-3 col-lg-4 col-md-6">
                <div class="property-card">
                    <div class="property-card-img-wrapper">
                        <?php if ($b['img']): ?>
                            <img src="../<?= htmlspecialchars($b['img']) ?>" alt="<?= htmlspecialchars($b['titre']) ?>"
                                 loading="lazy" onerror="this.src='../assets/images/placeholder.svg'">
                        <?php else: ?>
                            <div style="width:100%;height:100%;background:var(--color-surface);display:flex;align-items:center;justify-content:center;">
                                <i class="fas fa-home" style="font-size:2.5rem;color:var(--color-text-muted);opacity:0.3;"></i>
                            </div>
                        <?php endif; ?>
                        <span class="property-card-badge <?= $b['type'] ?>"><?= ucfirst($b['type']) ?></span>
                        <span class="property-card-status <?= $b['statut'] ?>"><?= ucfirst($b['statut']) ?></span>
                        <!-- Bouton retirer des favoris -->
                        <a href="#" class="favorite-btn active" data-bien-id="<?= $b['id'] ?>" title="Retirer des favoris">
                            <i class="fas fa-heart"></i>
                        </a>
                    </div>
                    <div class="property-card-body">
                        <div class="property-card-price"><?= number_format($b['prix_mensuel'], 0, ',', ' ') ?> FCFA <span>/ mois</span></div>
                        <h3 class="property-card-title"><?= htmlspecialchars($b['titre']) ?></h3>
                        <div class="property-card-location">
                            <i class="fas fa-map-marker-alt" style="color:var(--color-primary-light);"></i>
                            <?= htmlspecialchars($b['ville']) ?>
                        </div>
                        <div class="property-card-features">
                            <div class="property-feature"><i class="fas fa-bed"></i> <?= $b['chambres'] ?></div>
                            <div class="property-feature"><i class="fas fa-bath"></i> <?= $b['salles_de_bain'] ?></div>
                            <div class="property-feature"><i class="fas fa-vector-square"></i> <?= $b['superficie'] ?>m²</div>
                        </div>
                        <div class="d-flex gap-2 mt-3">
                            <a href="../detail.php?id=<?= $b['id'] ?>" class="btn-outline-immo flex-grow-1 justify-content-center" style="font-size:0.82rem;padding:8px;">
                                <i class="fas fa-eye"></i> Détails
                            </a>
                            <?php if ($b['statut'] === 'disponible'): ?>
                            <a href="reserver.php?bien_id=<?= $b['id'] ?>" class="btn-primary-immo flex-grow-1 justify-content-center" style="font-size:0.82rem;padding:8px;">
                                <i class="fas fa-calendar-check"></i> Réserver
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
// Mise à jour de l'affichage au retrait d'un favori
document.querySelectorAll('.favorite-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        // Attendere le résultat AJAX puis retirer la carte
        setTimeout(() => {
            const card = btn.closest('.col-xl-3, .col-lg-4, .col-md-6');
            if (card && !btn.classList.contains('active')) {
                card.style.transition = 'opacity 0.4s ease, transform 0.4s ease';
                card.style.opacity = '0';
                card.style.transform = 'scale(0.95)';
                setTimeout(() => card.remove(), 400);
            }
        }, 500);
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>
