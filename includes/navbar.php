<?php
/**
 * includes/navbar.php
 * Navigation principale adaptative selon le rôle de l'utilisateur connecté
 * Roles : visiteur (non connecté), client, commercial
 */
?>
<nav class="navbar navbar-expand-lg navbar-immo" id="main-navbar">
    <div class="container">
        <!-- Logo / Brand -->
        <a class="navbar-brand navbar-brand-text text-decoration-none" href="index.php">
            <i class="fas fa-gem me-2" style="font-size:1.1rem;"></i>LuxeImmo
        </a>

        <!-- Controls container for mobile (Theme Switcher + Hamburger) -->
        <div class="d-flex align-items-center gap-2">
            <!-- Theme Toggle Mobile -->
            <button class="theme-toggle-btn d-lg-none" title="Changer de thème" aria-label="Basculer le thème">
                <i class="fas fa-sun sun-icon"></i>
                <i class="fas fa-moon moon-icon"></i>
            </button>
            
            <!-- Mobile Toggle -->
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse"
                    data-bs-target="#navbarContent" style="color: var(--color-text-secondary); padding: 0;">
                <i class="fas fa-bars"></i>
            </button>
        </div>

        <div class="collapse navbar-collapse" id="navbarContent">
            <!-- Liens de navigation principaux -->
            <ul class="navbar-nav me-auto ms-lg-4 ms-0 gap-1 mt-3 mt-lg-0">
                <li class="nav-item">
                    <a class="nav-link nav-link-immo <?= (basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : '') ?>"
                       href="index.php">
                        <i class="fas fa-home me-1"></i> Accueil
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link nav-link-immo" href="index.php?type=appartement">
                        <i class="fas fa-building me-1"></i> Appartements
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link nav-link-immo" href="index.php?type=villa">
                        <i class="fas fa-house me-1"></i> Villas
                    </a>
                </li>
            </ul>

            <!-- Actions selon le rôle -->
            <ul class="navbar-nav align-items-stretch align-items-lg-center gap-2 mt-3 mt-lg-0">
                <!-- Bouton Theme Toggle Desktop -->
                <li class="nav-item d-none d-lg-block">
                    <button class="theme-toggle-btn" id="theme-toggle" title="Changer de thème" aria-label="Basculer le thème">
                        <i class="fas fa-sun sun-icon"></i>
                        <i class="fas fa-moon moon-icon"></i>
                    </button>
                </li>
                <?php if (isset($_SESSION['user_id'])): ?>

                    <?php if ($_SESSION['role'] === 'commercial'): ?>
                        <!-- Connecté en tant que Commercial -->
                        <li class="nav-item">
                            <a class="nav-link nav-link-immo" href="commercial/index.php">
                                <i class="fas fa-chart-bar me-1"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="btn-primary-immo text-decoration-none dropdown-toggle" href="#"
                               role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user-tie me-1"></i>
                                <?= htmlspecialchars($_SESSION['prenom']) ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end dropdown-menu-immo mt-2">
                                <li>
                                    <a class="dropdown-item" href="commercial/biens.php">
                                        <i class="fas fa-building me-2"></i> Gérer les biens
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="commercial/reservations.php">
                                        <i class="fas fa-calendar-check me-2"></i> Réservations
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="commercial/clients.php">
                                        <i class="fas fa-users me-2"></i> Clients
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item text-danger" href="logout.php">
                                        <i class="fas fa-sign-out-alt me-2"></i> Déconnexion
                                    </a>
                                </li>
                            </ul>
                        </li>

                    <?php elseif ($_SESSION['role'] === 'client'): ?>
                        <!-- Connecté en tant que Client -->
                        <li class="nav-item">
                            <a class="nav-link nav-link-immo" href="client/favoris.php">
                                <i class="far fa-heart me-1"></i> Favoris
                            </a>
                        </li>
                        <li class="nav-item dropdown">
                             <a class="btn-primary-immo text-decoration-none dropdown-toggle" href="#"
                                role="button" data-bs-toggle="dropdown">
                                 <i class="fas fa-user me-1"></i>
                                 <?= htmlspecialchars($_SESSION['prenom']) ?>
                             </a>
                             <ul class="dropdown-menu dropdown-menu-end dropdown-menu-immo mt-2">
                                <li>
                                    <a class="dropdown-item" href="client/dashboard.php">
                                        <i class="fas fa-tachometer-alt me-2"></i> Mon Espace
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="client/favoris.php">
                                        <i class="fas fa-heart me-2"></i> Mes Favoris
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item text-danger" href="logout.php">
                                        <i class="fas fa-sign-out-alt me-2"></i> Déconnexion
                                    </a>
                                </li>
                            </ul>
                        </li>
                    <?php endif; ?>

                <?php else: ?>
                    <!-- Visiteur non connecté -->
                    <li class="nav-item">
                        <a class="btn-outline-immo text-decoration-none" href="login.php">
                            <i class="fas fa-sign-in-alt me-1"></i> Connexion
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="btn-primary-immo text-decoration-none" href="register.php">
                            <i class="fas fa-user-plus me-1"></i> S'inscrire
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>
