<?php
/**
 * client/reserver.php — Formulaire de réservation d'un bien
 */
require_once '../config/db.php';
require_once '../includes/auth_check.php';
require_role('client');

$client_id = $_SESSION['user_id'];
$bien_id   = isset($_GET['bien_id']) ? (int)$_GET['bien_id'] : 0;

// Récupérer le bien
$stmt = $pdo->prepare("SELECT * FROM biens WHERE id = ? AND statut = 'disponible'");
$stmt->execute([$bien_id]);
$bien = $stmt->fetch();

if (!$bien) {
    header('Location: ../index.php?error=bien_indisponible');
    exit;
}

// Récupérer l'image principale
$img_stmt = $pdo->prepare("SELECT chemin FROM images WHERE bien_id = ? AND est_principale = 1 LIMIT 1");
$img_stmt->execute([$bien_id]);
$img = $img_stmt->fetchColumn();

$errors  = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $date_debut = $_POST['date_debut'] ?? '';
    $date_fin   = $_POST['date_fin']   ?? '';
    $today      = date('Y-m-d');

    // Validations
    if (empty($date_debut) || empty($date_fin))
        $errors[] = 'Les dates de début et de fin sont requises.';
    elseif ($date_debut < $today)
        $errors[] = 'La date de début ne peut pas être dans le passé.';
    elseif ($date_fin <= $date_debut)
        $errors[] = 'La date de fin doit être postérieure à la date de début.';

    // Vérifier qu'il n'y a pas de réservation en chevauchement validée
    if (empty($errors)) {
        $overlap = $pdo->prepare("
            SELECT id FROM reservations
            WHERE bien_id = ? AND statut IN ('en_attente', 'validee')
            AND (date_debut <= ? AND date_fin >= ?)
        ");
        $overlap->execute([$bien_id, $date_fin, $date_debut]);
        if ($overlap->fetch()) {
            $errors[] = 'Ce bien est déjà réservé pour la période sélectionnée. Choisissez d\'autres dates.';
        }
    }

    if (empty($errors)) {
        $pdo->prepare("
            INSERT INTO reservations (client_id, bien_id, date_debut, date_fin, statut)
            VALUES (?, ?, ?, ?, 'en_attente')
        ")->execute([$client_id, $bien_id, $date_debut, $date_fin]);

        $success = true;
    }
}

// Calculer le nombre de jours & montant estimé
$nb_jours = 0;
$montant  = 0;
if (!empty($_POST['date_debut']) && !empty($_POST['date_fin']) && empty($errors)) {
    $d1       = new DateTime($_POST['date_debut']);
    $d2       = new DateTime($_POST['date_fin']);
    $nb_jours = $d1->diff($d2)->days;
    $montant  = round(($bien['prix_mensuel'] / 30) * $nb_jours);
}

$page_title = 'Réserver — ' . htmlspecialchars($bien['titre']) . ' — LuxeImmo';
require_once '../includes/header.php';
require_once '../includes/navbar.php';
?>

<div style="background:var(--color-bg-dark);min-height:100vh;padding:48px 0 80px;">
<div class="container" style="max-width:900px;">

    <!-- Breadcrumb -->
    <nav style="margin-bottom:28px;font-size:0.85rem;color:var(--color-text-muted);">
        <a href="../index.php" style="color:var(--color-text-muted);text-decoration:none;"><i class="fas fa-home me-1"></i> Accueil</a>
        <span class="mx-2">/</span>
        <a href="../detail.php?id=<?= $bien_id ?>" style="color:var(--color-text-muted);text-decoration:none;"><?= htmlspecialchars($bien['titre']) ?></a>
        <span class="mx-2">/</span>
        <span style="color:var(--color-text-primary);">Réservation</span>
    </nav>

    <?php if ($success): ?>
    <!-- Confirmation -->
    <div style="text-align:center;padding:64px 40px;background:var(--color-bg-glass);backdrop-filter:blur(20px);border:1px solid rgba(16,185,129,0.3);border-radius:var(--radius-xl);">
        <div style="width:80px;height:80px;background:rgba(16,185,129,0.15);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 24px;border:2px solid rgba(16,185,129,0.3);">
            <i class="fas fa-check" style="font-size:2rem;color:#10b981;"></i>
        </div>
        <h2 style="font-size:1.8rem;font-weight:800;color:var(--color-text-primary);margin-bottom:12px;">
            Réservation envoyée !
        </h2>
        <p style="color:var(--color-text-secondary);font-size:1rem;max-width:480px;margin:0 auto 28px;line-height:1.7;">
            Votre demande de réservation pour <strong style="color:var(--color-text-primary);"><?= htmlspecialchars($bien['titre']) ?></strong> 
            est en attente de validation par notre équipe. Vous serez notifié dès qu'elle sera traitée.
        </p>
        <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap;">
            <a href="dashboard.php" class="btn-primary-immo"><i class="fas fa-tachometer-alt"></i> Voir mes réservations</a>
            <a href="../index.php" class="btn-outline-immo"><i class="fas fa-search"></i> Explorer d'autres biens</a>
        </div>
    </div>

    <?php else: ?>
    <div class="row g-5">
        <!-- Formulaire -->
        <div class="col-lg-7">
            <div class="glass-card p-4 p-md-5">
                <h1 style="font-size:1.6rem;font-weight:800;color:var(--color-text-primary);letter-spacing:-0.5px;margin-bottom:6px;">
                    <i class="fas fa-calendar-check me-2" style="color:var(--color-primary-light);"></i>
                    Demande de Réservation
                </h1>
                <p style="color:var(--color-text-muted);font-size:0.88rem;margin-bottom:28px;">
                    Choisissez vos dates. La réservation sera soumise à validation par notre équipe.
                </p>

                <!-- Erreurs -->
                <?php if (!empty($errors)): ?>
                <div class="alert-immo error" style="flex-direction:column;align-items:flex-start;gap:6px;margin-bottom:24px;">
                    <div class="d-flex align-items-center gap-2"><i class="fas fa-exclamation-circle"></i><strong>Erreur(s) :</strong></div>
                    <ul style="margin:0;padding-left:20px;">
                        <?php foreach ($errors as $e): ?><li style="font-size:0.85rem;"><?= htmlspecialchars($e) ?></li><?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>

                <form method="POST" class="needs-validation" novalidate id="reservation-form">
                    <div class="row g-3 mb-4">
                        <div class="col-sm-6">
                            <label for="date_debut" class="form-label-immo">
                                <i class="fas fa-calendar-alt me-1"></i> Date d'arrivée
                            </label>
                            <input type="date" id="date_debut" name="date_debut" class="form-control-immo"
                                   value="<?= htmlspecialchars($_POST['date_debut'] ?? '') ?>"
                                   min="<?= date('Y-m-d') ?>" required>
                            <div class="invalid-feedback" style="color:var(--color-danger);font-size:.8rem;margin-top:4px;">
                                Date d'arrivée requise.
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <label for="date_fin" class="form-label-immo">
                                <i class="fas fa-calendar-check me-1"></i> Date de départ
                            </label>
                            <input type="date" id="date_fin" name="date_fin" class="form-control-immo"
                                   value="<?= htmlspecialchars($_POST['date_fin'] ?? '') ?>"
                                   min="<?= date('Y-m-d', strtotime('+1 day')) ?>" required>
                            <div class="invalid-feedback" style="color:var(--color-danger);font-size:.8rem;margin-top:4px;">
                                Date de départ requise (après la date d'arrivée).
                            </div>
                        </div>
                    </div>

                    <!-- Estimation de prix (JS) -->
                    <div id="price-estimate" style="display:none; background:rgba(108,99,255,0.08);border:1px solid rgba(108,99,255,0.2);border-radius:12px;padding:16px;margin-bottom:24px;">
                        <div style="display:flex;justify-content:space-between;align-items:center;">
                            <span style="color:var(--color-text-secondary);font-size:0.88rem;">
                                <i class="fas fa-calculator me-2"></i> Estimation : <span id="estimate-days">-</span> jour(s)
                            </span>
                            <span id="estimate-price" style="font-size:1.2rem;font-weight:800;color:var(--color-accent-light);"></span>
                        </div>
                        <p style="color:var(--color-text-muted);font-size:0.75rem;margin:6px 0 0;">
                            * Estimation basée sur <?= number_format($bien['prix_mensuel'], 0, ',', ' ') ?> FCFA / mois. Sujet à confirmation.
                        </p>
                    </div>

                    <button type="submit" class="btn-primary-immo w-100 justify-content-center" style="padding:14px;font-size:1rem;">
                        <i class="fas fa-paper-plane"></i> Envoyer la demande de réservation
                    </button>
                </form>
            </div>
        </div>

        <!-- Récapitulatif du bien -->
        <div class="col-lg-5">
            <div style="position:sticky;top:90px;">
                <div class="glass-card overflow-hidden">
                    <?php if ($img): ?>
                    <img src="../<?= htmlspecialchars($img) ?>" alt="<?= htmlspecialchars($bien['titre']) ?>"
                         style="width:100%;height:200px;object-fit:cover;"
                         onerror="this.style.display='none'">
                    <?php endif; ?>
                    <div class="p-4">
                        <span style="font-size:0.75rem;font-weight:700;text-transform:uppercase;background:rgba(108,99,255,0.12);color:var(--color-primary-light);border-radius:20px;padding:4px 12px;letter-spacing:0.5px;">
                            <?= ucfirst($bien['type']) ?>
                        </span>
                        <h2 style="font-size:1.1rem;font-weight:700;color:var(--color-text-primary);margin:12px 0 6px;line-height:1.3;">
                            <?= htmlspecialchars($bien['titre']) ?>
                        </h2>
                        <div style="font-size:0.83rem;color:var(--color-text-muted);margin-bottom:16px;">
                            <i class="fas fa-map-marker-alt me-1" style="color:var(--color-primary-light);"></i>
                            <?= htmlspecialchars($bien['adresse']) ?>, <?= htmlspecialchars($bien['ville']) ?>
                        </div>
                        <div style="font-size:1.5rem;font-weight:800;color:var(--color-accent-light);letter-spacing:-0.5px;">
                            <?= number_format($bien['prix_mensuel'], 0, ',', ' ') ?> FCFA
                            <span style="font-size:0.78rem;font-weight:400;color:var(--color-text-muted);">/ mois</span>
                        </div>
                        <hr style="border-color:var(--color-border);margin:16px 0;">
                        <div style="display:flex;flex-wrap:wrap;gap:12px;">
                            <div class="property-feature"><i class="fas fa-bed"></i> <?= $bien['chambres'] ?> ch.</div>
                            <div class="property-feature"><i class="fas fa-bath"></i> <?= $bien['salles_de_bain'] ?> sdb.</div>
                            <div class="property-feature"><i class="fas fa-vector-square"></i> <?= $bien['superficie'] ?> m²</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

</div>
</div>

<script>
(function() {
    const dd = document.getElementById('date_debut');
    const df = document.getElementById('date_fin');
    const estimate = document.getElementById('price-estimate');
    const pricePerMonth = <?= $bien['prix_mensuel'] ?>;

    function updateEstimate() {
        if (!dd || !df || !dd.value || !df.value) return;
        const d1 = new Date(dd.value), d2 = new Date(df.value);
        if (d2 <= d1) { if(estimate) estimate.style.display = 'none'; return; }
        const days = Math.round((d2 - d1) / (1000 * 60 * 60 * 24));
        const total = Math.round((pricePerMonth / 30) * days);
        document.getElementById('estimate-days').textContent = days;
        document.getElementById('estimate-price').textContent = total.toLocaleString('fr-FR') + ' FCFA';
        if(estimate) estimate.style.display = 'block';
    }

    if(dd) dd.addEventListener('change', updateEstimate);
    if(df) df.addEventListener('change', updateEstimate);
})();
</script>

<?php require_once '../includes/footer.php'; ?>
