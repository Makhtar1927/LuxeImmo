<?php
$current = basename($_SERVER['PHP_SELF']);

$fav_count = 0;
if (isset($pdo) && isset($_SESSION['user_id'])) {
    $fav_stmt = $pdo->prepare("SELECT COUNT(*) FROM favoris WHERE client_id = ?");
    $fav_stmt->execute([$_SESSION['user_id']]);
    $fav_count = $fav_stmt->fetchColumn();
}
?>
<div class="dashboard-mobile-header">
    <button class="sidebar-toggle-btn" title="Ouvrir le menu" aria-label="Ouvrir le menu">
        <i class="fas fa-bars"></i>
    </button>
    <div class="sidebar-logo-mobile">
        <i class="fas fa-gem logo-icon"></i> LuxeImmo
    </div>
    <div style="width: 34px;"></div>
</div>

<div class="sidebar-overlay"></div>

<aside class="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-logo">
            <i class="fas fa-gem logo-icon"></i>
            <span class="sidebar-logo-text">LuxeImmo</span>
        </div>
    </div>

    <div class="sidebar-body">
        <div class="sidebar-section-title">Navigation</div>

        <a href="dashboard.php" class="sidebar-nav-link <?= $current === 'dashboard.php' ? 'active' : '' ?>">
            <i class="fas fa-tachometer-alt nav-icon"></i>
            <span class="sidebar-nav-text">Mon Espace</span>
        </a>
        <a href="favoris.php" class="sidebar-nav-link <?= $current === 'favoris.php' ? 'active' : '' ?>">
            <i class="fas fa-heart nav-icon"></i>
            <span class="sidebar-nav-text">Mes Favoris</span>
            <?php if ($fav_count > 0): ?>
            <span class="sidebar-badge-count">
                <?= $fav_count ?>
            </span>
            <?php endif; ?>
        </a>

        <div class="sidebar-divider"></div>

        <div class="sidebar-section-title">Raccourcis</div>

        <a href="../index.php" class="sidebar-nav-link">
            <i class="fas fa-search nav-icon"></i>
            <span class="sidebar-nav-text">Explorer les biens</span>
        </a>

        <a href="../logout.php" class="sidebar-nav-link logout-link">
            <i class="fas fa-sign-out-alt nav-icon"></i>
            <span class="sidebar-nav-text">Déconnexion</span>
        </a>
    </div>

    <div class="sidebar-footer">
        <div class="sidebar-user-card">
            <div class="user-avatar-badge">
                <?= strtoupper(substr($_SESSION['prenom'] ?? 'C', 0, 1)) ?>
                <span class="user-status-dot" title="En ligne"></span>
            </div>
            <div class="user-details">
                <div class="user-name"><?= htmlspecialchars(($_SESSION['prenom'] ?? '') . ' ' . ($_SESSION['nom'] ?? '')) ?></div>
                <div class="user-role-badge client">
                    <i class="fas fa-user me-1"></i> Client Luxe
                </div>
            </div>
        </div>
    </div>
</aside>
