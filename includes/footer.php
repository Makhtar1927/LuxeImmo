<?php
/**
 * includes/footer.php
 * Pied de page commun à toutes les pages publiques
 */
?>
<!-- ===== FOOTER ===== -->
<footer class="footer-immo">
    <div class="container">
        <div class="row g-5">
            <!-- Brand & Description -->
            <div class="col-lg-5 col-md-12 mb-4 mb-lg-0">
                <div class="footer-brand">
                    <i class="fas fa-gem me-2"></i>LuxeImmo
                </div>
                <p class="footer-desc">
                    Votre agence immobilière de référence pour la location d'appartements et villas haut de gamme. 
                    Un service d'exception pour un cadre de vie exceptionnel.
                </p>
                <div class="d-flex gap-3 mt-4">
                    <a href="#" class="btn-outline-immo" style="width:38px;height:38px;padding:0;justify-content:center;">
                        <i class="fab fa-facebook-f"></i>
                    </a>
                    <a href="#" class="btn-outline-immo" style="width:38px;height:38px;padding:0;justify-content:center;">
                        <i class="fab fa-instagram"></i>
                    </a>
                    <a href="#" class="btn-outline-immo" style="width:38px;height:38px;padding:0;justify-content:center;">
                        <i class="fab fa-linkedin-in"></i>
                    </a>
                </div>
            </div>

            <!-- Liens Utiles -->
            <div class="col-lg-3 col-md-6 mb-4 mb-lg-0">
                <h6 class="footer-heading">Catalogue</h6>
                <a href="index.php?type=appartement" class="footer-link">Appartements</a>
                <a href="index.php?type=villa" class="footer-link">Villas</a>
                <a href="index.php" class="footer-link">Toutes les offres</a>
            </div>

            <!-- Contact -->
            <div class="col-lg-4 col-md-6">
                <h6 class="footer-heading">Contact</h6>
                <div class="d-flex align-items-start gap-3 mb-3">
                    <i class="fas fa-map-marker-alt mt-1" style="color: var(--color-primary-light); width:16px;"></i>
                    <span class="footer-desc" style="max-width:none;">Pikine Dagoudane, Sénégal</span>
                </div>
                <div class="d-flex align-items-center gap-3 mb-3">
                    <i class="fas fa-phone" style="color: var(--color-primary-light); width:16px;"></i>
                    <a href="tel:+221771234567" class="footer-link mb-0">+221 77 123 45 67</a>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <i class="fas fa-envelope" style="color: var(--color-primary-light); width:16px;"></i>
                    <a href="mailto:contact@luxeimmo.sn" class="footer-link mb-0">contact@luxeimmo.sn</a>
                </div>
            </div>
        </div>

        <!-- Footer Bottom -->
        <div class="footer-bottom d-flex flex-wrap justify-content-between align-items-center">
            <span>&copy; <?= date('Y') ?> LuxeImmo. Tous droits réservés.</span>
            <span>développé par patriode'dev</span>
        </div>
    </div>
</footer>

<!-- Bootstrap 5 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- JavaScript personnalisé -->
<script src="<?= get_base_url() ?>assets/js/main.js"></script>
</body>
</html>
