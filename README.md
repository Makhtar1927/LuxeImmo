# LuxeImmo — Agence Immobilière

Application web développée avec **PHP / MySQL / Bootstrap 5** pour la gestion d'une agence immobilière.

## Structure du projet

```
projet-immo/
├── config/db.php               → Connexion PDO MySQL
├── includes/
│   ├── header.php              → En-tête HTML commun
│   ├── footer.php              → Pied de page commun
│   ├── navbar.php              → Navigation adaptative (rôle)
│   └── auth_check.php          → Middleware d'accès (sécurité)
├── assets/
│   ├── css/style.css           → Design System premium
│   ├── js/main.js              → JS client (validation, favoris AJAX, toast)
│   └── images/                 → Images uploadées des biens
├── client/
│   ├── dashboard.php           → Espace client (suivi réservations)
│   ├── favoris.php             → Biens favoris
│   ├── reserver.php            → Formulaire de réservation
│   ├── annuler_reservation.php → Annulation par le client
│   └── toggle_favori.php       → Endpoint AJAX favoris
├── commercial/
│   ├── index.php               → Dashboard commercial
│   ├── biens.php               → CRUD biens + upload images
│   ├── reservations.php        → Validation/suivi des réservations
│   ├── clients.php             → Gestion des comptes clients
│   └── sidebar.php             → Sidebar commune
├── index.php                   → Accueil public / catalogue
├── detail.php                  → Détails d'un bien
├── login.php                   → Connexion
├── register.php                → Inscription client
├── logout.php                  → Déconnexion
└── database.sql                → Script SQL de création de la BD
```

## Installation

1. **Importer la base de données** : Importer `database.sql` dans phpMyAdmin ou via la commande :
   ```bash
   mysql -u root -p < database.sql
   ```

2. **Configurer la connexion** : Éditer `config/db.php` et adapter `DB_USER` / `DB_PASS` selon votre serveur.

3. **Démarrer le serveur** : Placer le projet dans `htdocs/` (XAMPP) ou `www/` (WAMP) et accéder à `http://localhost/nom-du-dossier/`.

## Comptes de démonstration

| Rôle       | Email                  | Mot de passe |
|------------|------------------------|--------------|
| Commercial | commercial@immo.com    | password123  |
| Client     | client@immo.com        | password123  |

## Technologies utilisées

- **PHP 8+** (PDO, Sessions, `password_hash` / `password_verify`)
- **MySQL 8+** (avec clés étrangères et contraintes)
- **Bootstrap 5.3** (grille responsive, composants)
- **CSS personnalisé** (Design system dark mode premium, animations, glassmorphism)
- **JavaScript vanilla** (Validation, AJAX favoris, toast notifications, drag & drop)
- **Font Awesome 6** + **Google Fonts** (Plus Jakarta Sans)

## Documentation

- **[DOCUMENTATION.md](file:///c:/xampp/htdocs/Dev_Web_Avanc%C3%A9/DOCUMENTATION.md)** : Rapport complet d'architecture technique, modèle de base de données (MCD/Diagramme ER), périmètre fonctionnel et mécanismes de sécurité.

