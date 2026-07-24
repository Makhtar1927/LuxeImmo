/**
 * assets/js/main.js
 * Logique JavaScript côté client :
 * - Navbar scroll effect
 * - Validation des formulaires Bootstrap
 * - Toggle favori (asynchrone)
 * - Micro-interactions et animations
 */

document.addEventListener('DOMContentLoaded', () => {

    // ====================================================
    // 1. Navbar : effet glassmorphism au scroll
    // ====================================================
    const navbar = document.querySelector('.navbar-immo');
    if (navbar) {
        window.addEventListener('scroll', () => {
            navbar.classList.toggle('scrolled', window.scrollY > 20);
        }, { passive: true });
    }

    // ====================================================
    // 2. Validation Bootstrap des formulaires (côté client)
    // ====================================================
    const forms = document.querySelectorAll('.needs-validation');
    forms.forEach(form => {
        form.addEventListener('submit', event => {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        });
    });

    // ====================================================
    // 3. Validation du mot de passe (inscription)
    // ====================================================
    const passwordInput    = document.getElementById('mot_de_passe');
    const passwordConfirm  = document.getElementById('mot_de_passe_confirm');

    if (passwordInput && passwordConfirm) {
        const checkMatch = () => {
            if (passwordInput.value !== passwordConfirm.value) {
                passwordConfirm.setCustomValidity('Les mots de passe ne correspondent pas.');
                passwordConfirm.classList.add('is-invalid');
            } else {
                passwordConfirm.setCustomValidity('');
                passwordConfirm.classList.remove('is-invalid');
            }
        };
        passwordInput.addEventListener('input', checkMatch);
        passwordConfirm.addEventListener('input', checkMatch);
    }

    // ====================================================
    // 4. Toggle Favori (requête AJAX sans rechargement)
    // ====================================================
    document.querySelectorAll('.favorite-btn').forEach(btn => {
        btn.addEventListener('click', async (e) => {
            e.preventDefault();
            e.stopPropagation();

            const bienId = btn.dataset.bienId;
            if (!bienId) return;

            try {
                const response = await fetch('client/toggle_favori.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `bien_id=${bienId}`
                });

                const data = await response.json();

                if (data.success) {
                    btn.classList.toggle('active', data.is_favori);
                    const icon = btn.querySelector('i');
                    if (icon) {
                        const hasMe2 = icon.classList.contains('me-2');
                        icon.className = (data.is_favori ? 'fas fa-heart' : 'far fa-heart') + (hasMe2 ? ' me-2' : '');
                    }
                    const textSpan = btn.querySelector('.fav-btn-text');
                    if (textSpan) {
                        textSpan.textContent = data.is_favori ? 'Retirer des favoris' : 'Ajouter aux favoris';
                    }
                    showToast(data.message, data.is_favori ? 'success' : 'info');
                } else if (data.redirect) {
                    window.location.href = data.redirect;
                } else {
                    showToast(data.message || 'Une erreur est survenue.', 'error');
                }
            } catch (err) {
                showToast('Erreur de connexion. Veuillez réessayer.', 'error');
                console.error(err);
            }
        });
    });

    // ====================================================
    // 5. Système de Toast Notifications
    // ====================================================
    window.showToast = (message, type = 'info') => {
        const container = getOrCreateToastContainer();
        const toast = document.createElement('div');
        toast.className = `toast-notif toast-${type}`;
        const icons = { success: 'fa-check-circle', error: 'fa-times-circle', info: 'fa-info-circle', warning: 'fa-exclamation-circle' };
        toast.innerHTML = `<i class="fas ${icons[type] || icons.info}"></i><span>${message}</span>`;
        container.appendChild(toast);

        // Animation d'entrée
        requestAnimationFrame(() => toast.classList.add('show'));

        setTimeout(() => {
            toast.classList.remove('show');
            toast.addEventListener('transitionend', () => toast.remove(), { once: true });
        }, 3500);
    };

    const getOrCreateToastContainer = () => {
        let c = document.getElementById('toast-container');
        if (!c) {
            c = document.createElement('div');
            c.id = 'toast-container';
            document.body.appendChild(c);
        }
        return c;
    };

    // Détecter automatiquement un message dans l'URL et afficher le toast
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('msg')) {
        window.showToast(urlParams.get('msg'), 'success');
    } else if (urlParams.has('error')) {
        window.showToast(urlParams.get('error'), 'error');
    }

    // ====================================================
    // 6. Drag & Drop Upload Zone (pour les images des biens)
    // ====================================================
    const uploadZone = document.querySelector('.upload-zone');
    const fileInput  = document.getElementById('images');

    if (uploadZone && fileInput) {
        uploadZone.addEventListener('click', () => fileInput.click());

        uploadZone.addEventListener('dragover', (e) => {
            e.preventDefault();
            uploadZone.classList.add('drag-over');
        });

        uploadZone.addEventListener('dragleave', () => {
            uploadZone.classList.remove('drag-over');
        });

        uploadZone.addEventListener('drop', (e) => {
            e.preventDefault();
            uploadZone.classList.remove('drag-over');
            if (e.dataTransfer.files.length) {
                fileInput.files = e.dataTransfer.files;
                previewImages(e.dataTransfer.files);
            }
        });

        fileInput.addEventListener('change', () => {
            if (fileInput.files.length) {
                previewImages(fileInput.files);
            }
        });
    }

    const previewImages = (files) => {
        const previewContainer = document.getElementById('image-preview');
        if (!previewContainer) return;
        previewContainer.innerHTML = '';

        Array.from(files).forEach((file, i) => {
            if (!file.type.startsWith('image/')) return;
            const reader = new FileReader();
            reader.onload = (e) => {
                const div = document.createElement('div');
                div.className = 'preview-img-wrapper';
                div.innerHTML = `
                    <img src="${e.target.result}" alt="Aperçu ${i+1}" />
                    ${i === 0 ? '<span class="preview-badge">Principale</span>' : ''}
                `;
                previewContainer.appendChild(div);
            };
            reader.readAsDataURL(file);
        });

        const countEl = document.querySelector('.upload-zone-count');
        if (countEl) countEl.textContent = `${files.length} image(s) sélectionnée(s)`;
    };

    // ====================================================
    // 7. Filtres de recherche dynamique (sur la page d'accueil)
    //    Actualise les résultats en temps réel au changement de filtre
    // ====================================================
    const searchForm = document.getElementById('search-form');
    if (searchForm) {
        const filterInputs = searchForm.querySelectorAll('select, input[type="range"]');
        filterInputs.forEach(input => {
            input.addEventListener('change', () => {
                // Afficher un indicateur de chargement
                const grid = document.getElementById('biens-grid');
                if (grid) grid.style.opacity = '0.5';
                // Soumettre le formulaire
                searchForm.submit();
            });
        });

        // Affichage dynamique de la valeur du slider de prix
        const priceSlider  = document.getElementById('prix_max');
        const priceDisplay = document.getElementById('prix_display');
        if (priceSlider && priceDisplay) {
            priceSlider.addEventListener('input', () => {
                priceDisplay.textContent = parseInt(priceSlider.value).toLocaleString('fr-FR') + ' FCFA';
            });
        }
    }

    // ====================================================
    // 8. Validation des dates de réservation (date_fin > date_debut)
    // ====================================================
    const dateDebut = document.getElementById('date_debut');
    const dateFin   = document.getElementById('date_fin');
    if (dateDebut && dateFin) {
        // Empêcher les dates passées
        const today = new Date().toISOString().split('T')[0];
        dateDebut.setAttribute('min', today);

        dateDebut.addEventListener('change', () => {
            dateFin.setAttribute('min', dateDebut.value);
            if (dateFin.value && dateFin.value <= dateDebut.value) {
                dateFin.value = '';
                dateFin.setCustomValidity('La date de fin doit être postérieure à la date de début.');
            } else {
                dateFin.setCustomValidity('');
            }
        });

        dateFin.addEventListener('change', () => {
            if (dateFin.value <= dateDebut.value) {
                dateFin.setCustomValidity('La date de fin doit être postérieure à la date de début.');
            } else {
                dateFin.setCustomValidity('');
            }
        });
    }

    // ====================================================
    // 9. Confirmation de suppression
    // ====================================================
    document.querySelectorAll('[data-confirm]').forEach(btn => {
        btn.addEventListener('click', (e) => {
            const message = btn.dataset.confirm || 'Êtes-vous sûr de vouloir effectuer cette action ?';
            if (!confirm(message)) {
                e.preventDefault();
            }
        });
    });

    // ====================================================
    // 10. Animation d'entrée au scroll (Intersection Observer)
    // ====================================================
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.1 });

    document.querySelectorAll('.animate-fade-in-up').forEach(el => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(24px)';
        el.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
        observer.observe(el);
    });

    // ====================================================
    // Theme Switcher (Light / Dark Mode)
    // ====================================================
    const toggleTheme = () => {
        const currentTheme = document.documentElement.getAttribute('data-theme') || 'light';
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
        document.documentElement.setAttribute('data-theme', newTheme);
        localStorage.setItem('theme', newTheme);
        
        if (window.showToast) {
            window.showToast(`Mode ${newTheme === 'dark' ? 'sombre' : 'clair'} activé`, 'info');
        }
    };

    document.querySelectorAll('.theme-toggle-btn').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            toggleTheme();
        });
    });

    // ====================================================
    // 11. Sidebar Drawer Toggle for mobile dashboards
    // ====================================================
    const sidebarToggle = document.querySelector('.sidebar-toggle-btn');
    const sidebar = document.querySelector('.sidebar');
    const sidebarOverlay = document.querySelector('.sidebar-overlay');

    if (sidebarToggle && sidebar && sidebarOverlay) {
        const toggleSidebar = (show) => {
            sidebar.classList.toggle('show', show);
            sidebarOverlay.classList.toggle('show', show);
        };

        sidebarToggle.addEventListener('click', () => toggleSidebar(true));
        sidebarOverlay.addEventListener('click', () => toggleSidebar(false));

        window.addEventListener('resize', () => {
            if (window.innerWidth > 1024) {
                toggleSidebar(false);
            }
        });
    }
});

