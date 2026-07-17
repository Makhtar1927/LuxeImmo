<?php
/**
 * detail.php — Page de détails d'un bien immobilier
 * Accès : Tous (visiteurs, clients, commerciaux)
 */
require_once 'config/db.php';
require_once 'includes/auth_check.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) {
    header('Location: index.php');
    exit;
}

// Récupérer le bien
$stmt = $pdo->prepare("SELECT * FROM biens WHERE id = ?");
$stmt->execute([$id]);
$bien = $stmt->fetch();

if (!$bien) {
    header('Location: index.php?error=bien_introuvable');
    exit;
}

// Récupérer toutes les images du bien
$img_stmt = $pdo->prepare("SELECT * FROM images WHERE bien_id = ? ORDER BY est_principale DESC");
$img_stmt->execute([$id]);
$images = $img_stmt->fetchAll();

// Vérifier si le bien est en favori pour le client connecté
$is_favori = false;
if (has_role('client')) {
    $fav_check = $pdo->prepare("SELECT id FROM favoris WHERE client_id = ? AND bien_id = ?");
    $fav_check->execute([$_SESSION['user_id'], $id]);
    $is_favori = (bool)$fav_check->fetch();
}

// Biens similaires (même type, même ville, différent ID)
$sim_stmt = $pdo->prepare("
    SELECT b.*, (SELECT chemin FROM images WHERE bien_id = b.id AND est_principale = 1 LIMIT 1) AS img
    FROM biens b
    WHERE b.type = ? AND b.id != ? AND b.statut = 'disponible'
    ORDER BY ABS(b.prix_mensuel - ?) ASC LIMIT 3
");
$sim_stmt->execute([$bien['type'], $id, $bien['prix_mensuel']]);
$similaires = $sim_stmt->fetchAll();

$page_title = htmlspecialchars($bien['titre']) . ' — LuxeImmo';
$page_description = substr(strip_tags($bien['description']), 0, 160);
require_once 'includes/header.php';
require_once 'includes/navbar.php';
?>

<div style="background:var(--color-bg-dark); padding:40px 0 80px; min-height:100vh;">
<div class="container">

    <!-- Breadcrumb -->
    <nav style="margin-bottom:28px; font-size:0.85rem; color:var(--color-text-muted);">
        <a href="index.php" style="color:var(--color-text-muted); text-decoration:none;">
            <i class="fas fa-home me-1"></i> Accueil
        </a>
        <span class="mx-2">/</span>
        <a href="index.php?type=<?= $bien['type'] ?>" style="color:var(--color-text-muted); text-decoration:none;">
            <?= ucfirst($bien['type']) ?>s
        </a>
        <span class="mx-2">/</span>
        <span style="color:var(--color-text-primary);"><?= htmlspecialchars($bien['titre']) ?></span>
    </nav>

    <div class="row g-5">
        <!-- Colonne principale -->
        <div class="col-lg-8">
            <!-- Carrousel d'images -->
            <?php if (!empty($images)): ?>
            <div id="carouselBien" class="carousel slide carousel-immo mb-4" data-bs-ride="carousel">
                <div class="carousel-inner">
                    <?php foreach ($images as $idx => $img): ?>
                    <div class="carousel-item <?= $idx === 0 ? 'active' : '' ?>">
                        <img src="<?= htmlspecialchars($img['chemin']) ?>"
                             alt="<?= htmlspecialchars($bien['titre']) ?> - Photo <?= $idx+1 ?>"
                             onerror="this.src='assets/images/placeholder.svg'">
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php if (count($images) > 1): ?>
                <button class="carousel-control-prev" type="button" data-bs-target="#carouselBien" data-bs-slide="prev">
                    <i class="fas fa-chevron-left" style="color:#fff;"></i>
                </button>
                <button class="carousel-control-next" type="button" data-bs-target="#carouselBien" data-bs-slide="next">
                    <i class="fas fa-chevron-right" style="color:#fff;"></i>
                </button>
                <!-- Indicateurs miniatures -->
                <div style="display:flex;gap:8px;justify-content:center;margin-top:12px;flex-wrap:wrap;">
                    <?php foreach ($images as $idx => $img): ?>
                    <div onclick="document.querySelector('[data-bs-target=\'#carouselBien\']').dispatchEvent(new Event('click'))"
                         style="width:70px;height:52px;border-radius:8px;overflow:hidden;cursor:pointer;
                                border:2px solid <?= $idx===0 ? 'var(--color-primary)' : 'var(--color-border)' ?>;">
                        <img src="<?= htmlspecialchars($img['chemin']) ?>" style="width:100%;height:100%;object-fit:cover;"
                             onerror="this.src='assets/images/placeholder.svg'">
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
            <?php else: ?>
            <div style="height:350px; background:var(--color-surface); border-radius:var(--radius-lg);
                        display:flex; align-items:center; justify-content:center; margin-bottom:24px;">
                <i class="fas fa-home" style="font-size:5rem; color:var(--color-text-muted); opacity:0.3;"></i>
            </div>
            <?php endif; ?>

            <!-- Description -->
            <div class="glass-card p-4 mb-4">
                <h2 style="font-size:1.1rem; font-weight:700; color:var(--color-text-primary); margin-bottom:16px;">
                    <i class="fas fa-info-circle me-2" style="color:var(--color-primary-light);"></i>Description
                </h2>
                <p style="color:var(--color-text-secondary); line-height:1.8; font-size:0.93rem;">
                    <?= nl2br(htmlspecialchars($bien['description'])) ?>
                </p>
            </div>

            <!-- Caractéristiques détaillées -->
            <div class="glass-card p-4">
                <h2 style="font-size:1.1rem; font-weight:700; color:var(--color-text-primary); margin-bottom:20px;">
                    <i class="fas fa-list-check me-2" style="color:var(--color-primary-light);"></i>Caractéristiques
                </h2>
                <div class="row g-3">
                    <?php
                    $features = [
                        ['fas fa-bed',           'Chambres',      $bien['chambres']],
                        ['fas fa-couch',         'Salons',        $bien['salons']],
                        ['fas fa-bath',          'Salles de bain',$bien['salles_de_bain']],
                        ['fas fa-vector-square', 'Superficie',    $bien['superficie'] . ' m²'],
                        ['fas fa-city',          'Ville',         $bien['ville']],
                        ['fas fa-map-marker-alt','Adresse',       $bien['adresse']],
                    ];
                    foreach ($features as [$icon, $label, $value]): ?>
                    <div class="col-sm-6 col-md-4">
                        <div style="background:rgba(255,255,255,0.03); border:1px solid var(--color-border);
                                    border-radius:12px; padding:16px; display:flex; align-items:center; gap:12px;">
                            <div style="width:38px;height:38px;background:rgba(108,99,255,0.12);border-radius:10px;
                                        display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                <i class="<?= $icon ?>" style="color:var(--color-primary-light);"></i>
                            </div>
                            <div>
                                <div style="font-size:0.72rem;color:var(--color-text-muted);text-transform:uppercase;letter-spacing:0.5px;"><?= $label ?></div>
                                <div style="font-weight:700;color:var(--color-text-primary);font-size:0.95rem;"><?= htmlspecialchars((string)$value) ?></div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Colonne latérale -->
        <div class="col-lg-4">
            <div style="position:sticky; top:90px;">
                <!-- Carte de prix & action -->
                <div class="glass-card p-4 mb-4">
                    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:8px;">
                        <span class="status-badge <?= $bien['statut'] ?>">
                            <?= ucfirst($bien['statut']) ?>
                        </span>
                        <span style="font-size:0.8rem;background:rgba(108,99,255,0.1);color:var(--color-primary-light);
                                     padding:3px 10px;border-radius:20px;font-weight:600;">
                            <?= ucfirst($bien['type']) ?>
                        </span>
                    </div>

                    <div style="font-size:2rem; font-weight:800; color:var(--color-accent-light);
                                letter-spacing:-1px; margin:12px 0 4px;">
                        <?= number_format($bien['prix_mensuel'], 0, ',', ' ') ?> FCFA
                    </div>
                    <div style="font-size:0.82rem; color:var(--color-text-muted); margin-bottom:24px;">par mois</div>

                    <!-- Actions -->
                    <?php if ($bien['statut'] === 'disponible'): ?>
                        <?php if (has_role('client')): ?>
                            <a href="client/reserver.php?bien_id=<?= $bien['id'] ?>"
                               class="btn-primary-immo w-100 justify-content-center mb-3"
                               style="padding:14px; font-size:1rem;">
                                <i class="fas fa-calendar-check"></i> Réserver maintenant
                            </a>
                        <?php elseif (!is_logged_in()): ?>
                            <a href="login.php" class="btn-primary-immo w-100 justify-content-center mb-3"
                               style="padding:14px; font-size:1rem;">
                                <i class="fas fa-sign-in-alt"></i> Se connecter pour réserver
                            </a>
                        <?php else: ?>
                            <div class="alert-immo info mb-3">
                                <i class="fas fa-info-circle"></i> Espace commercial — Pas de réservation ici.
                            </div>
                        <?php endif; ?>

                        <!-- Bouton favori -->
                        <?php if (has_role('client')): ?>
                        <a href="#" class="btn-outline-immo w-100 justify-content-center favorite-btn <?= $is_favori ? 'active' : '' ?>"
                           data-bien-id="<?= $bien['id'] ?>" style="padding:12px;">
                            <i class="<?= $is_favori ? 'fas' : 'far' ?> fa-heart me-2"></i>
                            <?= $is_favori ? 'Retirer des favoris' : 'Ajouter aux favoris' ?>
                        </a>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="alert-immo error">
                            <i class="fas fa-times-circle"></i>
                            Ce bien n'est pas disponible à la location actuellement.
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Contact rapide -->
                <div class="glass-card p-4">
                    <h3 style="font-size:1rem; font-weight:700; color:var(--color-text-primary); margin-bottom:16px;">
                        <i class="fas fa-headset me-2" style="color:var(--color-primary-light);"></i>
                        Nous contacter
                    </h3>
                    <a href="tel:+221771234567" class="btn-outline-immo w-100 justify-content-center mb-2" style="padding:11px;">
                        <i class="fas fa-phone"></i> +221 77 123 45 67
                    </a>
                    <a href="mailto:contact@luxeimmo.sn" class="btn-outline-immo w-100 justify-content-center" style="padding:11px;">
                        <i class="fas fa-envelope"></i> contact@luxeimmo.sn
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Biens similaires -->
    <?php if (!empty($similaires)): ?>
    <div style="margin-top:64px;">
        <p class="section-eyebrow">Vous aimerez peut-être</p>
        <h2 class="section-title mb-4">Biens Similaires</h2>
        <div class="row g-4">
            <?php foreach ($similaires as $sim): ?>
            <div class="col-lg-4 col-md-6">
                <div class="property-card">
                    <div class="property-card-img-wrapper">
                        <?php if ($sim['img']): ?>
                            <img src="<?= htmlspecialchars($sim['img']) ?>" alt="<?= htmlspecialchars($sim['titre']) ?>"
                                 loading="lazy" onerror="this.src='assets/images/placeholder.svg'">
                        <?php else: ?>
                            <div style="width:100%;height:100%;background:var(--color-surface);display:flex;align-items:center;justify-content:center;">
                                <i class="fas fa-home" style="font-size:2.5rem;color:var(--color-text-muted);opacity:0.3;"></i>
                            </div>
                        <?php endif; ?>
                        <span class="property-card-badge <?= $sim['type'] ?>"><?= ucfirst($sim['type']) ?></span>
                    </div>
                    <div class="property-card-body">
                        <div class="property-card-price"><?= number_format($sim['prix_mensuel'], 0, ',', ' ') ?> FCFA <span>/ mois</span></div>
                        <h3 class="property-card-title"><?= htmlspecialchars($sim['titre']) ?></h3>
                        <div class="property-card-location">
                            <i class="fas fa-map-marker-alt" style="color:var(--color-primary-light);"></i>
                            <?= htmlspecialchars($sim['ville']) ?>
                        </div>
                        <a href="detail.php?id=<?= $sim['id'] ?>" class="btn-outline-immo w-100 justify-content-center mt-3" style="font-size:.85rem;">
                            Voir les détails <i class="fas fa-arrow-right ms-1"></i>
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

</div>
</div>

<?php require_once 'includes/footer.php'; ?>
