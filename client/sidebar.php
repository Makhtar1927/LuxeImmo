<?php
/**
 * client/sidebar.php — Sidebar commune aux pages client
 */
$current = basename($_SERVER['PHP_SELF']);

// Récupérer le nombre de favoris
$fav_count = 0;
if (isset($pdo) && isset($_SESSION['user_id'])) {
    $fav_stmt = $pdo->prepare("SELECT COUNT(*) FROM favoris WHERE client_id = ?");
    $fav_stmt->execute([$_SESSION['user_id']]);
    $fav_count = $fav_stmt->fetchColumn();
}
?>
<div class="sidebar">
    <!-- Logo + Theme Toggle -->
    <div style="display:flex; align-items:center; justify-content:space-between; padding:0 12px; margin-bottom:36px;">
        <div class="sidebar-logo" style="margin-bottom:0;">
            <i class="fas fa-gem me-2" style="background:var(--gradient-primary); -webkit-background-clip:text; -webkit-text-fill-color:transparent; background-clip:text;"></i>
            LuxeImmo
        </div>
        <button class="theme-toggle-btn" title="Changer de thème" aria-label="Basculer le thème">
            <i class="fas fa-sun sun-icon"></i>
            <i class="fas fa-moon moon-icon"></i>
        </button>
    </div>

    <a href="dashboard.php" class="sidebar-nav-link <?= $current === 'dashboard.php' ? 'active' : '' ?>">
        <i class="fas fa-tachometer-alt"></i> Mon Espace
    </a>
    <a href="favoris.php" class="sidebar-nav-link <?= $current === 'favoris.php' ? 'active' : '' ?>">
        <i class="fas fa-heart"></i> Mes Favoris
        <?php if ($fav_count > 0): ?>
        <span style="margin-left:auto;background:rgba(239,68,68,0.15);color:var(--color-danger);
                     border-radius:20px;padding:1px 8px;font-size:0.72rem;font-weight:700;">
            <?= $fav_count ?>
        </span>
        <?php endif; ?>
    </a>

    <hr class="sidebar-divider">

    <a href="../index.php" class="sidebar-nav-link">
        <i class="fas fa-search"></i> Explorer les biens
    </a>
    <a href="../logout.php" class="sidebar-nav-link" style="color:var(--color-danger) !important; margin-top:auto;">
        <i class="fas fa-sign-out-alt"></i> Déconnexion
    </a>

    <!-- Profil utilisateur -->
    <div style="margin-top:20px; padding:16px 12px; background:rgba(108,99,255,0.08); border-radius:12px;">
        <div style="display:flex; align-items:center; gap:10px;">
            <div style="width:36px; height:36px; background:var(--gradient-primary); border-radius:50%;
                        display:flex; align-items:center; justify-content:center;
                        font-weight:800; color:#fff; font-size:0.9rem; flex-shrink:0;">
                <?= strtoupper(substr($_SESSION['prenom'], 0, 1)) ?>
            </div>
            <div>
                <div style="font-weight:700; font-size:0.85rem; color:var(--color-text-primary);">
                    <?= htmlspecialchars($_SESSION['prenom'] . ' ' . $_SESSION['nom']) ?>
                </div>
                <div style="font-size:0.72rem; color:var(--color-text-muted);">Client</div>
            </div>
        </div>
    </div>
</div>
