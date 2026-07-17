<?php
/**
 * includes/header.php
 * En-tête commun à toutes les pages publiques
 * Variables attendues : $page_title (string), $page_description (string, optionnel)
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$page_title = $page_title ?? 'LuxeImmo - Agence Immobilière Premium';
$page_description = $page_description ?? 'Découvrez les plus belles villas et appartements à louer avec LuxeImmo, votre agence immobilière de confiance.';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <script>
        // Initialiser le thème immédiatement avant le rendu pour éviter le scintillement (FOUC)
        const storedTheme = localStorage.getItem('theme') || 'light';
        document.documentElement.setAttribute('data-theme', storedTheme);
    </script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= htmlspecialchars($page_description) ?>">
    <meta name="theme-color" content="#6c63ff">
    <title><?= htmlspecialchars($page_title) ?></title>

    <!-- Google Fonts : Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <!-- Bootstrap 5 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">

    <!-- CSS personnalisé -->
    <link rel="stylesheet" href="<?= get_base_url() ?>assets/css/style.css">

    <!-- Toast Container Styles (inline car minimal) -->
    <style>
        #toast-container {
            position: fixed;
            bottom: 24px;
            right: 24px;
            z-index: 9999;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .toast-notif {
            display: flex;
            align-items: center;
            gap: 12px;
            background: var(--color-bg-card);
            border: 1px solid var(--color-border);
            border-radius: 12px;
            padding: 14px 18px;
            color: var(--color-text-primary);
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 0.88rem;
            font-weight: 500;
            box-shadow: var(--shadow-soft);
            opacity: 0;
            transform: translateX(40px);
            transition: all 0.35s cubic-bezier(0.25, 0.46, 0.45, 0.94);
            min-width: 280px;
            max-width: 360px;
        }
        .toast-notif.show {
            opacity: 1;
            transform: translateX(0);
        }
        .toast-success { border-color: rgba(16,185,129,0.4); }
        .toast-success i { color: #10b981; }
        .toast-error   { border-color: rgba(239,68,68,0.4); }
        .toast-error i  { color: #ef4444; }
        .toast-info     { border-color: rgba(108,99,255,0.4); }
        .toast-info i   { color: #8b83ff; }
        .toast-warning  { border-color: rgba(245,158,11,0.4); }
        .toast-warning i{ color: #f59e0b; }

        /* Image Preview Styles */
        #image-preview {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 16px;
        }
        .preview-img-wrapper {
            position: relative;
            width: 90px;
            height: 70px;
            border-radius: 8px;
            overflow: hidden;
            border: 1px solid var(--color-border);
        }
        .preview-img-wrapper img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .preview-badge {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(108,99,255,0.9);
            color: #fff;
            font-size: 0.6rem;
            font-weight: 700;
            text-align: center;
            padding: 2px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
    </style>
</head>
<body>
