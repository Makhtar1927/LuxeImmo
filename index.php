<?php
/**
 * index.php — Page d'accueil / Catalogue de biens immobiliers
 * Accès : Tous (visiteurs, clients, commerciaux)
 */
require_once 'config/db.php';
require_once 'includes/auth_check.php';

// === Filtres de recherche ===
$type     = $_GET['type']     ?? '';
$ville    = $_GET['ville']    ?? '';
$chambres = $_GET['chambres'] ?? '';
$prix_max = isset($_GET['prix_max']) && is_numeric($_GET['prix_max']) ? (int)$_GET['prix_max'] : 500000;

// === Requête dynamique ===
$sql    = "SELECT b.*, COALESCE((SELECT chemin FROM images WHERE bien_id = b.id AND est_principale = 1 LIMIT 1), (SELECT chemin FROM images WHERE bien_id = b.id ORDER BY id ASC LIMIT 1)) AS image_principale FROM biens b WHERE 1=1";
$params = [];

if ($type && in_array($type, ['appartement', 'villa'])) {
    $sql .= " AND b.type = ?";
    $params[] = $type;
}
if ($ville) {
    $sql .= " AND b.ville LIKE ?";
    $params[] = '%' . $ville . '%';
}
if ($chambres) {
    $sql .= " AND b.chambres >= ?";
    $params[] = (int)$chambres;
}
$sql .= " AND b.prix_mensuel <= ? ORDER BY b.date_creation DESC";
$params[] = $prix_max;

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$biens = $stmt->fetchAll();

// Récupérer les villes distinctes pour le filtre
$villes = $pdo->query("SELECT DISTINCT ville FROM biens ORDER BY ville")->fetchAll(PDO::FETCH_COLUMN);

// Récupérer les favoris du client connecté
$favoris_ids = [];
if (has_role('client')) {
    $fav_stmt = $pdo->prepare("SELECT bien_id FROM favoris WHERE client_id = ?");
    $fav_stmt->execute([$_SESSION['user_id']]);
    $favoris_ids = $fav_stmt->fetchAll(PDO::FETCH_COLUMN);
}

// Stats pour la section hero
$nb_biens     = $pdo->query("SELECT COUNT(*) FROM biens")->fetchColumn();
$nb_clients   = $pdo->query("SELECT COUNT(*) FROM utilisateurs WHERE role='client'")->fetchColumn();
$nb_villes    = $pdo->query("SELECT COUNT(DISTINCT ville) FROM biens")->fetchColumn();

$page_title = 'LuxeImmo — Location de Villas & Appartements Premium';
$page_description = 'Découvrez notre sélection exclusive de villas et appartements haut de gamme à louer. Trouvez le bien idéal avec LuxeImmo.';
require_once 'includes/header.php';
require_once 'includes/navbar.php';
?>

<!-- ===== HERO ===== -->
<section class="hero-section">
    <div class="hero-bg-orbs">
        <div class="orb orb-1"></div>
        <div class="orb orb-2"></div>
        <div class="orb orb-3"></div>
    </div>
    <div class="container hero-content">
        <div class="row align-items-center min-vh-85">
            <div class="col-lg-7">
                <h1 class="hero-title">
                    Trouvez le bien<br>
                    <span class="gradient-word">de vos rêves</span>
                </h1>
                <p class="hero-subtitle">
                    Des villas et appartements d'exception sélectionnés pour vous.
                    Profitez d'un service premium et d'une expérience de location unique.
                </p>
                <div class="hero-cta-group">
                    <a href="#catalogue" class="btn-primary-immo" style="padding:14px 32px; font-size:1rem;">
                        <i class="fas fa-search"></i> Explorer les biens
                    </a>
                    <?php if (!is_logged_in()): ?>
                    <a href="register.php" class="btn-outline-immo" style="padding:13px 28px; font-size:1rem;">
                        <i class="fas fa-user-plus"></i> S'inscrire gratuitement
                    </a>
                    <?php endif; ?>
                </div>
                <div class="hero-stats">
                    <div>
                        <div class="hero-stat-value"><?= $nb_biens ?>+</div>
                        <div class="hero-stat-label">Biens disponibles</div>
                    </div>
                    <div style="width:1px; background:var(--color-border);"></div>
                    <div>
                        <div class="hero-stat-value"><?= $nb_clients ?>+</div>
                        <div class="hero-stat-label">Clients satisfaits</div>
                    </div>
                    <div style="width:1px; background:var(--color-border);"></div>
                    <div>
                        <div class="hero-stat-value"><?= $nb_villes ?>+</div>
                        <div class="hero-stat-label">Villes couvertes</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ===== BARRE DE RECHERCHE ===== -->
<section style="background:var(--color-bg-dark); padding:0 0 40px;">
    <div class="container">
        <div class="search-bar-wrapper" id="catalogue">
            <form method="GET" action="index.php" id="search-form">
                <div class="row g-3 align-items-end">
                    <div class="col-lg-3 col-md-6 col-12">
                        <span class="search-label"><i class="fas fa-home me-1"></i> Type de bien</span>
                        <select name="type" class="form-select">
                            <option value="" <?= $type === '' ? 'selected' : '' ?>>Tous les types</option>
                            <option value="appartement" <?= $type === 'appartement' ? 'selected' : '' ?>>Appartement</option>
                            <option value="villa" <?= $type === 'villa' ? 'selected' : '' ?>>Villa</option>
                        </select>
                    </div>
                    <div class="col-lg-3 col-md-6 col-12">
                        <span class="search-label"><i class="fas fa-map-marker-alt me-1"></i> Ville</span>
                        <select name="ville" class="form-select">
                            <option value="">Toutes les villes</option>
                            <?php foreach ($villes as $v): ?>
                                <option value="<?= htmlspecialchars($v) ?>" <?= $ville === $v ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($v) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-lg-2 col-md-6 col-12">
                        <span class="search-label"><i class="fas fa-bed me-1"></i> Chambres min.</span>
                        <select name="chambres" class="form-select">
                            <option value="">Peu importe</option>
                            <?php for ($i = 1; $i <= 6; $i++): ?>
                                <option value="<?= $i ?>" <?= $chambres == $i ? 'selected' : '' ?>><?= $i ?>+</option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="col-lg-3 col-md-6 col-12">
                        <span class="search-label">
                            <i class="fas fa-tag me-1"></i> Prix max :
                            <strong id="prix_display" style="color:var(--color-accent-light);">
                                <?= number_format($prix_max, 0, ',', ' ') ?> FCFA
                            </strong>
                        </span>
                        <input type="range" name="prix_max" id="prix_max"
                               min="50000" max="500000" step="10000"
                               value="<?= $prix_max ?>"
                               style="width:100%; accent-color:var(--color-primary);">
                    </div>
                    <div class="col-lg-1 col-md-12 col-12">
                        <button type="submit" class="btn-primary-immo w-100 justify-content-center"
                                style="padding:13px; border-radius:12px;">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</section>

<!-- ===== CATALOGUE DES BIENS ===== -->
<section style="padding: 20px 0 80px;">
    <div class="container">
        <!-- En-tête de section -->
        <div class="d-flex flex-wrap justify-content-between align-items-end mb-5">
            <div>
                <p class="section-eyebrow"><i class="fas fa-sparkles me-1"></i> Sélection Premium</p>
                <h2 class="section-title">
                    <?php if ($type === 'appartement'): ?>
                        Nos Appartements
                    <?php elseif ($type === 'villa'): ?>
                        Nos Villas
                    <?php else: ?>
                        Tous les Biens
                    <?php endif; ?>
                </h2>
            </div>
            <p style="color:var(--color-text-muted); font-size:0.9rem;">
                <strong style="color:var(--color-text-primary);"><?= count($biens) ?></strong> bien(s) trouvé(s)
            </p>
        </div>

        <?php if (empty($biens)): ?>
            <!-- Aucun résultat -->
            <div class="text-center py-5" style="color:var(--color-text-muted);">
                <i class="fas fa-search" style="font-size:3rem; opacity:0.3; margin-bottom:16px; display:block;"></i>
                <p style="font-size:1.1rem; font-weight:600; color:var(--color-text-secondary);">
                    Aucun bien ne correspond à votre recherche.
                </p>
                <a href="index.php" class="btn-outline-immo mt-3">
                    <i class="fas fa-times me-1"></i> Réinitialiser les filtres
                </a>
            </div>
        <?php else: ?>
            <!-- Grille des biens -->
            <div class="row g-4" id="biens-grid">
                <?php foreach ($biens as $i => $bien): ?>
                    <div class="col-xl-3 col-lg-4 col-md-6 animate-fade-in-up animate-delay-<?= ($i % 4) + 1 ?>">
                        <div class="property-card">
                            <!-- Image principale -->
                            <div class="property-card-img-wrapper">
                                <?php if ($bien['image_principale']): ?>
                                    <img src="<?= htmlspecialchars($bien['image_principale']) ?>"
                                         alt="<?= htmlspecialchars($bien['titre']) ?>"
                                         loading="lazy"
                                         onerror="this.src='assets/images/placeholder.svg'">
                                <?php else: ?>
                                    <div style="width:100%;height:100%;background:var(--color-surface);
                                               display:flex;align-items:center;justify-content:center;">
                                        <i class="fas fa-home" style="font-size:3rem;color:var(--color-text-muted);opacity:0.3;"></i>
                                    </div>
                                <?php endif; ?>

                                <!-- Badge type -->
                                <span class="property-card-badge <?= $bien['type'] ?>">
                                    <i class="fas <?= $bien['type'] === 'villa' ? 'fa-house' : 'fa-building' ?> me-1"></i>
                                    <?= ucfirst($bien['type']) ?>
                                </span>

                                <!-- Statut -->
                                <span class="property-card-status <?= $bien['statut'] ?>">
                                    <?= ucfirst(str_replace('_', ' ', $bien['statut'])) ?>
                                </span>

                                <!-- Bouton favori (clients connectés seulement) -->
                                <?php if (has_role('client')): ?>
                                    <?php $is_favori = in_array($bien['id'], $favoris_ids); ?>
                                    <a href="#" class="favorite-btn <?= $is_favori ? 'active' : '' ?>"
                                       data-bien-id="<?= $bien['id'] ?>"
                                       title="<?= $is_favori ? 'Retirer des favoris' : 'Ajouter aux favoris' ?>">
                                        <i class="<?= $is_favori ? 'fas' : 'far' ?> fa-heart"></i>
                                    </a>
                                <?php elseif (!is_logged_in()): ?>
                                    <a href="login.php" class="favorite-btn" title="Se connecter pour ajouter aux favoris">
                                        <i class="far fa-heart"></i>
                                    </a>
                                <?php endif; ?>
                            </div>

                            <!-- Corps de la carte -->
                            <div class="property-card-body">
                                <div class="property-card-price">
                                    <?= number_format($bien['prix_mensuel'], 0, ',', ' ') ?> FCFA
                                    <span>/ mois</span>
                                </div>
                                <h3 class="property-card-title" title="<?= htmlspecialchars($bien['titre']) ?>">
                                    <?= htmlspecialchars($bien['titre']) ?>
                                </h3>
                                <div class="property-card-location">
                                    <i class="fas fa-map-marker-alt" style="color:var(--color-primary-light);"></i>
                                    <?= htmlspecialchars($bien['adresse']) ?>, <?= htmlspecialchars($bien['ville']) ?>
                                </div>
                                <div class="property-card-features">
                                    <div class="property-feature">
                                        <i class="fas fa-bed"></i> <?= $bien['chambres'] ?>
                                    </div>
                                    <div class="property-feature">
                                        <i class="fas fa-couch"></i> <?= $bien['salons'] ?>
                                    </div>
                                    <div class="property-feature">
                                        <i class="fas fa-bath"></i> <?= $bien['salles_de_bain'] ?>
                                    </div>
                                    <div class="property-feature">
                                        <i class="fas fa-vector-square"></i> <?= $bien['superficie'] ?>m²
                                    </div>
                                </div>
                                <a href="detail.php?id=<?= $bien['id'] ?>"
                                   class="btn-outline-immo w-100 justify-content-center mt-3"
                                   style="font-size:0.85rem; padding:9px;">
                                    Voir les détails <i class="fas fa-arrow-right ms-1"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
