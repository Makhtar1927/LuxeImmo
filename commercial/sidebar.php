<?php
$current = basename($_SERVER['PHP_SELF']);
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

        <a href="index.php" class="sidebar-nav-link <?= $current === 'index.php' ? 'active' : '' ?>">
            <i class="fas fa-chart-pie nav-icon"></i>
            <span class="sidebar-nav-text">Vue d'ensemble</span>
        </a>
        <a href="biens.php" class="sidebar-nav-link <?= $current === 'biens.php' ? 'active' : '' ?>">
            <i class="fas fa-building nav-icon"></i>
            <span class="sidebar-nav-text">Gestion des Biens</span>
        </a>
        <a href="reservations.php" class="sidebar-nav-link <?= $current === 'reservations.php' ? 'active' : '' ?>">
            <i class="fas fa-calendar-alt nav-icon"></i>
            <span class="sidebar-nav-text">Réservations</span>
        </a>
        <a href="clients.php" class="sidebar-nav-link <?= $current === 'clients.php' ? 'active' : '' ?>">
            <i class="fas fa-user-friends nav-icon"></i>
            <span class="sidebar-nav-text">Clients</span>
        </a>

        <div class="sidebar-divider"></div>

        <div class="sidebar-section-title">Raccourcis</div>

        <a href="../index.php" class="sidebar-nav-link" target="_blank">
            <i class="fas fa-external-link-alt nav-icon"></i>
            <span class="sidebar-nav-text">Voir le site web</span>
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
                <div class="user-role-badge commercial">
                    <i class="fas fa-user-shield me-1"></i> Commercial
                </div>
            </div>
        </div>
    </div>
</aside>
