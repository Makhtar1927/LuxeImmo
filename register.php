<?php
/**
 * register.php — Inscription d'un nouveau client
 */
require_once 'config/db.php';
require_once 'includes/auth_check.php';

if (is_logged_in()) {
    header('Location: client/dashboard.php');
    exit;
}

$errors = [];
$data   = ['nom' => '', 'prenom' => '', 'email' => '', 'telephone' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupérer et nettoyer les données
    $data['nom']       = trim($_POST['nom'] ?? '');
    $data['prenom']    = trim($_POST['prenom'] ?? '');
    $data['email']     = trim($_POST['email'] ?? '');
    $data['telephone'] = trim($_POST['telephone'] ?? '');
    $password          = $_POST['mot_de_passe'] ?? '';
    $password_confirm  = $_POST['mot_de_passe_confirm'] ?? '';

    // Validations côté serveur
    if (empty($data['nom']))       $errors[] = 'Le nom est requis.';
    if (empty($data['prenom']))    $errors[] = 'Le prénom est requis.';
    if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL))
        $errors[] = 'Veuillez fournir une adresse email valide.';
    if (empty($data['telephone']) || !preg_match('/^[0-9]{9,15}$/', $data['telephone']))
        $errors[] = 'Numéro de téléphone invalide (9 à 15 chiffres).';
    if (strlen($password) < 8)
        $errors[] = 'Le mot de passe doit contenir au moins 8 caractères.';
    if ($password !== $password_confirm)
        $errors[] = 'Les mots de passe ne correspondent pas.';

    if (empty($errors)) {
        // Vérifier si l'email existe déjà
        $stmt = $pdo->prepare("SELECT id FROM utilisateurs WHERE email = ?");
        $stmt->execute([$data['email']]);
        if ($stmt->fetch()) {
            $errors[] = 'Cette adresse email est déjà utilisée.';
        } else {
            // Insérer le nouveau client
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("
                INSERT INTO utilisateurs (nom, prenom, email, mot_de_passe, telephone, role, statut)
                VALUES (?, ?, ?, ?, ?, 'client', 'actif')
            ");
            $stmt->execute([$data['nom'], $data['prenom'], $data['email'], $hash, $data['telephone']]);

            header('Location: login.php?message=inscription_ok');
            exit;
        }
    }
}

$page_title = 'Inscription — LuxeImmo';
$page_description = 'Créez votre compte LuxeImmo pour accéder aux meilleures offres immobilières.';
require_once 'includes/header.php';
?>

<div style="min-height:100vh; display:flex; align-items:center; justify-content:center;
            background: var(--color-bg-dark); position:relative; overflow:hidden; padding:40px 20px;">
    <div class="hero-bg-orbs">
        <div class="orb orb-1"></div>
        <div class="orb orb-2"></div>
    </div>

    <div class="w-100" style="max-width: 520px; position:relative; z-index:1;">
        <!-- Logo -->
        <div class="text-center mb-4">
            <a href="index.php" class="text-decoration-none">
                <div style="font-size:2rem; font-weight:800; background:var(--gradient-primary);
                            -webkit-background-clip:text; -webkit-text-fill-color:transparent; background-clip:text;">
                    <i class="fas fa-gem me-2" style="-webkit-text-fill-color:initial; color:#6c63ff;"></i>LuxeImmo
                </div>
            </a>
            <p style="color:var(--color-text-muted); font-size:0.9rem; margin-top:8px;">
                Rejoignez notre communauté
            </p>
        </div>

        <div class="glass-card p-4 p-sm-5">
            <h1 style="font-size:1.6rem; font-weight:800; color:var(--color-text-primary);
                       letter-spacing:-0.5px; margin-bottom:8px;">Créer un compte</h1>
            <p style="color:var(--color-text-muted); font-size:0.88rem; margin-bottom:28px;">
                Déjà inscrit ?
                <a href="login.php" style="color:var(--color-primary-light); font-weight:600;">Se connecter</a>
            </p>

            <!-- Erreurs -->
            <?php if (!empty($errors)): ?>
                <div class="alert-immo error" style="flex-direction:column;align-items:flex-start;gap:6px;">
                    <div class="d-flex align-items-center gap-2"><i class="fas fa-exclamation-circle"></i><strong>Veuillez corriger les erreurs :</strong></div>
                    <ul style="margin:0;padding-left:20px;">
                        <?php foreach ($errors as $e): ?>
                            <li style="font-size:0.85rem;"><?= htmlspecialchars($e) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="POST" action="register.php" class="needs-validation" novalidate>
                <!-- Nom & Prénom -->
                <div class="row g-3 mb-3">
                    <div class="col-sm-6">
                        <label for="nom" class="form-label-immo">Nom</label>
                        <input type="text" id="nom" name="nom" class="form-control-immo"
                               placeholder="Diop" value="<?= htmlspecialchars($data['nom']) ?>"
                               required minlength="2">
                        <div class="invalid-feedback" style="color:var(--color-danger);font-size:.8rem;margin-top:4px;">Nom requis (min. 2 caractères).</div>
                    </div>
                    <div class="col-sm-6">
                        <label for="prenom" class="form-label-immo">Prénom</label>
                        <input type="text" id="prenom" name="prenom" class="form-control-immo"
                               placeholder="Mamadou" value="<?= htmlspecialchars($data['prenom']) ?>"
                               required minlength="2">
                        <div class="invalid-feedback" style="color:var(--color-danger);font-size:.8rem;margin-top:4px;">Prénom requis.</div>
                    </div>
                </div>

                <!-- Email -->
                <div class="mb-3">
                    <label for="email" class="form-label-immo">Adresse Email</label>
                    <div class="input-group-icon">
                        <i class="fas fa-envelope"></i>
                        <input type="email" id="email" name="email" class="form-control-immo"
                               placeholder="votre@email.com"
                               value="<?= htmlspecialchars($data['email']) ?>"
                               required autocomplete="email">
                        <div class="invalid-feedback" style="color:var(--color-danger);font-size:.8rem;margin-top:4px;">Email invalide.</div>
                    </div>
                </div>

                <!-- Téléphone -->
                <div class="mb-3">
                    <label for="telephone" class="form-label-immo">Téléphone</label>
                    <div class="input-group-icon">
                        <i class="fas fa-phone"></i>
                        <input type="tel" id="telephone" name="telephone" class="form-control-immo"
                               placeholder="77 123 45 67"
                               value="<?= htmlspecialchars($data['telephone']) ?>"
                               pattern="[0-9]{9,15}" required>
                        <div class="invalid-feedback" style="color:var(--color-danger);font-size:.8rem;margin-top:4px;">Numéro invalide (9 à 15 chiffres).</div>
                    </div>
                </div>

                <!-- Mot de passe -->
                <div class="mb-3">
                    <label for="mot_de_passe" class="form-label-immo">Mot de passe</label>
                    <div class="input-group-icon" style="position:relative;">
                        <i class="fas fa-lock"></i>
                        <input type="password" id="mot_de_passe" name="mot_de_passe" class="form-control-immo"
                               placeholder="Min. 8 caractères" style="padding-right:42px;"
                               required minlength="8" autocomplete="new-password">
                        <button type="button" onclick="togglePassword('mot_de_passe', this)"
                                style="position:absolute;right:12px;top:50%;transform:translateY(-50%);
                                       background:none;border:none;color:var(--color-text-muted);cursor:pointer;">
                            <i class="far fa-eye"></i>
                        </button>
                        <div class="invalid-feedback" style="color:var(--color-danger);font-size:.8rem;margin-top:4px;">Minimum 8 caractères.</div>
                    </div>
                </div>

                <!-- Confirmation mot de passe -->
                <div class="mb-4">
                    <label for="mot_de_passe_confirm" class="form-label-immo">Confirmer le mot de passe</label>
                    <div class="input-group-icon" style="position:relative;">
                        <i class="fas fa-lock"></i>
                        <input type="password" id="mot_de_passe_confirm" name="mot_de_passe_confirm"
                               class="form-control-immo" placeholder="Répétez le mot de passe"
                               style="padding-right:42px;" required>
                        <button type="button" onclick="togglePassword('mot_de_passe_confirm', this)"
                                style="position:absolute;right:12px;top:50%;transform:translateY(-50%);
                                       background:none;border:none;color:var(--color-text-muted);cursor:pointer;">
                            <i class="far fa-eye"></i>
                        </button>
                        <div class="invalid-feedback" style="color:var(--color-danger);font-size:.8rem;margin-top:4px;">
                            Les mots de passe ne correspondent pas.
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn-primary-immo w-100 justify-content-center" style="padding:14px;">
                    <i class="fas fa-user-plus"></i> Créer mon compte
                </button>
            </form>
        </div>

        <p class="text-center mt-4" style="font-size:0.82rem; color:var(--color-text-muted);">
            <a href="index.php" style="color:var(--color-text-muted);">
                <i class="fas fa-arrow-left me-1"></i> Retour à l'accueil
            </a>
        </p>
    </div>
</div>

<script>
function togglePassword(id, btn) {
    const input = document.getElementById(id);
    const isHidden = input.type === 'password';
    input.type = isHidden ? 'text' : 'password';
    btn.querySelector('i').className = isHidden ? 'far fa-eye-slash' : 'far fa-eye';
}
</script>

<?php require_once 'includes/footer.php'; ?>
