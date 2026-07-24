<?php
/**
 * commercial/sidebar.php — Sidebar commune à toutes les pages commerciales
 */
$current = basename($_SERVER['PHP_SELF']);
?>
<!-- Mobile Header for Dashboard -->
<div class="dashboard-mobile-header">
    <button class="sidebar-toggle-btn" title="Ouvrir le menu" aria-label="Ouvrir le menu">
        <i class="fas fa-bars"></i>
    </button>
    <div class="sidebar-logo-mobile">
        <i class="fas fa-gem" style="color:var(--color-primary-light);"></i> LuxeImmo
    </div>
    <div style="width: 38px;"></div> <!-- Spacer to keep logo centered -->
</div>

<!-- Sidebar Overlay -->
<div class="sidebar-overlay"></div>

<div class="sidebar">
    <!-- Logo + Theme Toggle -->
    <div style="display:flex; align-items:center; justify-content:space-between; padding:0 12px; margin-bottom:36px;">
        <div class="sidebar-logo" style="margin-bottom:0;">
            <i class="fas fa-gem me-2" style="background:var(--gradient-primary);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;"></i>
            LuxeImmo
        </div>
        <button class="theme-toggle-btn" title="Changer de thème" aria-label="Basculer le thème">
            <i class="fas fa-sun sun-icon"></i>
            <i class="fas fa-moon moon-icon"></i>
        </button>
    </div>

    <div style="font-size:0.7rem;color:var(--color-text-muted);text-transform:uppercase;letter-spacing:1px;font-weight:700;padding:0 12px;margin-bottom:8px;">
        Principal
    </div>
    <a href="index.php" class="sidebar-nav-link <?= $current === 'index.php' ? 'active' : '' ?>">
        <i class="fas fa-chart-bar"></i> Dashboard
    </a>
    <a href="biens.php" class="sidebar-nav-link <?= $current === 'biens.php' ? 'active' : '' ?>">
        <i class="fas fa-building"></i> Gestion des biens
    </a>
    <a href="reservations.php" class="sidebar-nav-link <?= $current === 'reservations.php' ? 'active' : '' ?>">
        <i class="fas fa-calendar-check"></i> Réservations
    </a>
    <a href="clients.php" class="sidebar-nav-link <?= $current === 'clients.php' ? 'active' : '' ?>">
        <i class="fas fa-users"></i> Clients
    </a>

    <hr class="sidebar-divider">

    <a href="../index.php" class="sidebar-nav-link" target="_blank">
        <i class="fas fa-external-link-alt"></i> Voir le site
    </a>
    <a href="../logout.php" class="sidebar-nav-link" style="color:var(--color-danger)!important;margin-top:auto;">
        <i class="fas fa-sign-out-alt"></i> Déconnexion
    </a>

    <!-- Profil -->
    <div style="margin-top:20px;padding:14px 12px;background:rgba(108,99,255,0.08);border-radius:12px;">
        <div style="display:flex;align-items:center;gap:10px;">
            <div style="width:36px;height:36px;background:var(--gradient-accent);border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:800;color:#fff;font-size:0.9rem;flex-shrink:0;">
                <?= strtoupper(substr($_SESSION['prenom'], 0, 1)) ?>
            </div>
            <div>
                <div style="font-weight:700;font-size:0.85rem;color:var(--color-text-primary);">
                    <?= htmlspecialchars($_SESSION['prenom'] . ' ' . $_SESSION['nom']) ?>
                </div>
                <div style="font-size:0.72rem;color:var(--color-accent-light);">Commercial</div>
            </div>
        </div>
    </div>
</div>
