<?php
/**
 * login.php — Page de connexion dynamique (client & commercial)
 */
require_once 'config/db.php';
require_once 'includes/auth_check.php';

// Déjà connecté ? Rediriger vers l'espace approprié
if (is_logged_in()) {
    header('Location: ' . (has_role('commercial') ? 'commercial/index.php' : 'client/dashboard.php'));
    exit;
}

$error   = '';
$success = '';

// Messages depuis les redirections
$msg_map = [
    'connexion_requise' => ['info', '<i class="fas fa-lock me-2"></i>Veuillez vous connecter pour accéder à cette page.'],
    'compte_inactif'    => ['error', '<i class="fas fa-ban me-2"></i>Votre compte a été désactivé. Contactez l\'agence.'],
    'inscription_ok'    => ['success', '<i class="fas fa-check-circle me-2"></i>Compte créé avec succès ! Vous pouvez vous connecter.'],
    'deconnecte'        => ['info', '<i class="fas fa-sign-out-alt me-2"></i>Vous avez été déconnecté avec succès.'],
];

$flash_type = 'info';
$flash_msg  = '';
if (isset($_GET['message']) && array_key_exists($_GET['message'], $msg_map)) {
    [$flash_type, $flash_msg] = $msg_map[$_GET['message']];
}

// Traitement du formulaire de connexion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['mot_de_passe'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Veuillez remplir tous les champs.';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['mot_de_passe'])) {
            if ($user['statut'] !== 'actif') {
                $error = 'Votre compte est désactivé. Contactez l\'agence.';
            } else {
                // Régénérer l'identifiant de session pour éviter la fixation
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['nom']     = $user['nom'];
                $_SESSION['prenom']  = $user['prenom'];
                $_SESSION['email']   = $user['email'];
                $_SESSION['role']    = $user['role'];
                $_SESSION['statut']  = $user['statut'];

                // Redirection intelligente
                $redirect = $_SESSION['redirect_after_login'] ?? null;
                unset($_SESSION['redirect_after_login']);

                if ($redirect) {
                    header("Location: $redirect");
                } elseif ($user['role'] === 'commercial') {
                    header('Location: commercial/index.php');
                } else {
                    header('Location: client/dashboard.php');
                }
                exit;
            }
        } else {
            $error = 'Email ou mot de passe incorrect.';
        }
    }
}

$page_title = 'Connexion — LuxeImmo';
$page_description = 'Connectez-vous à votre espace LuxeImmo pour gérer vos réservations et favoris.';
require_once 'includes/header.php';
?>

<!-- Fond animé -->
<div style="min-height:100vh; display:flex; align-items:center; justify-content:center;
            background: var(--color-bg-dark); position:relative; overflow:hidden; padding:40px 20px;">
    <!-- Orbes de fond -->
    <div class="hero-bg-orbs">
        <div class="orb orb-1"></div>
        <div class="orb orb-2"></div>
    </div>

    <div class="w-100" style="max-width: 460px; position:relative; z-index:1;">

        <!-- Logo -->
        <div class="text-center mb-4">
            <a href="index.php" class="text-decoration-none">
                <div style="font-size:2rem; font-weight:800; background: var(--gradient-primary);
                            -webkit-background-clip:text; -webkit-text-fill-color:transparent; background-clip:text;">
                    <i class="fas fa-gem me-2" style="-webkit-text-fill-color: initial; color:#6c63ff;"></i>LuxeImmo
                </div>
            </a>
            <p style="color:var(--color-text-muted); font-size:0.9rem; margin-top:8px;">
                Bienvenue ! Connectez-vous à votre espace.
            </p>
        </div>

        <!-- Carte glassmorphism -->
        <div class="glass-card p-4 p-sm-5">
            <h1 style="font-size:1.6rem; font-weight:800; color:var(--color-text-primary);
                       letter-spacing:-0.5px; margin-bottom:8px;">
                Connexion
            </h1>
            <p style="color:var(--color-text-muted); font-size:0.88rem; margin-bottom:28px;">
                Pas encore inscrit ?
                <a href="register.php" style="color:var(--color-primary-light); font-weight:600;">
                    Créer un compte
                </a>
            </p>

            <!-- Flash message -->
            <?php if ($flash_msg): ?>
                <div class="alert-immo <?= $flash_type ?>">
                    <?= $flash_msg ?>
                </div>
            <?php endif; ?>

            <!-- Erreur serveur -->
            <?php if ($error): ?>
                <div class="alert-immo error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <!-- Formulaire -->
            <form method="POST" action="login.php" class="needs-validation" novalidate id="login-form">
                <!-- Email -->
                <div class="mb-4">
                    <label for="email" class="form-label-immo">Adresse Email</label>
                    <div class="input-group-icon">
                        <i class="fas fa-envelope"></i>
                        <input type="email" id="email" name="email"
                               class="form-control-immo"
                               placeholder="votre@email.com"
                               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                               required autocomplete="email">
                        <div class="invalid-feedback" style="color:var(--color-danger);font-size:.8rem;margin-top:5px;">
                            Veuillez entrer une adresse email valide.
                        </div>
                    </div>
                </div>

                <!-- Mot de passe -->
                <div class="mb-4">
                    <label for="mot_de_passe" class="form-label-immo">Mot de passe</label>
                    <div class="input-group-icon" style="position:relative;">
                        <i class="fas fa-lock"></i>
                        <input type="password" id="mot_de_passe" name="mot_de_passe"
                               class="form-control-immo"
                               placeholder="••••••••"
                               style="padding-right: 42px;"
                               required autocomplete="current-password">
                        <button type="button" onclick="togglePassword('mot_de_passe', this)"
                                style="position:absolute;right:12px;top:50%;transform:translateY(-50%);
                                       background:none;border:none;color:var(--color-text-muted);cursor:pointer;">
                            <i class="far fa-eye"></i>
                        </button>
                        <div class="invalid-feedback" style="color:var(--color-danger);font-size:.8rem;margin-top:5px;">
                            Veuillez entrer votre mot de passe.
                        </div>
                    </div>
                </div>

                <!-- Bouton de connexion -->
                <button type="submit" class="btn-primary-immo w-100 justify-content-center" style="padding:14px;">
                    <i class="fas fa-sign-in-alt"></i> Se connecter
                </button>
            </form>

            <!-- Comptes de démo -->
            <div style="margin-top:28px; padding:16px; background:rgba(108,99,255,0.06);
                        border-radius:12px; border:1px solid rgba(108,99,255,0.15);">
                <p style="font-size:0.75rem; color:var(--color-text-muted); text-align:center;
                          text-transform:uppercase; letter-spacing:0.8px; font-weight:700; margin-bottom:12px;">
                    Comptes de démonstration
                </p>
                <div class="d-flex gap-2 flex-wrap justify-content-center">
                    <button onclick="fillDemo('commercial@immo.com')"
                            style="background:rgba(245,158,11,0.1);border:1px solid rgba(245,158,11,0.3);
                                   color:#fbbf24;border-radius:8px;padding:6px 14px;font-size:0.78rem;
                                   font-weight:600;cursor:pointer;font-family:var(--font-main);">
                        <i class="fas fa-user-tie me-1"></i> Commercial
                    </button>
                    <button onclick="fillDemo('client@immo.com')"
                            style="background:rgba(108,99,255,0.1);border:1px solid rgba(108,99,255,0.3);
                                   color:#a78bfa;border-radius:8px;padding:6px 14px;font-size:0.78rem;
                                   font-weight:600;cursor:pointer;font-family:var(--font-main);">
                        <i class="fas fa-user me-1"></i> Client
                    </button>
                </div>
                <p style="font-size:0.72rem; color:var(--color-text-muted); text-align:center; margin-top:8px;">
                    Mot de passe : <code style="color:var(--color-primary-light);">password123</code>
                </p>
            </div>
        </div>

        <p class="text-center mt-4" style="font-size:0.82rem; color:var(--color-text-muted);">
            <a href="index.php" style="color:var(--color-text-muted);">
                <i class="fas fa-arrow-left me-1"></i> Retour à l'accueil
            </a>
        </p>
    </div>
</div>

<script>
function fillDemo(email) {
    document.getElementById('email').value = email;
    document.getElementById('mot_de_passe').value = 'password123';
}

function togglePassword(id, btn) {
    const input = document.getElementById(id);
    const isHidden = input.type === 'password';
    input.type = isHidden ? 'text' : 'password';
    btn.querySelector('i').className = isHidden ? 'far fa-eye-slash' : 'far fa-eye';
}
</script>

<?php require_once 'includes/footer.php'; ?>
