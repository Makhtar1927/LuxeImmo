<?php
/**
 * commercial/clients.php
 * Gestion des comptes clients : Activer, Désactiver, Supprimer, Voir l'historique
 */
require_once '../config/db.php';
require_once '../includes/auth_check.php';
require_role('commercial');

$action    = $_GET['action'] ?? 'list';
$client_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// ===== Actions =====
if ($client_id) {
    if ($action === 'activer') {
        $pdo->prepare("UPDATE utilisateurs SET statut='actif' WHERE id=? AND role='client'")->execute([$client_id]);
        header('Location: clients.php?msg=Client+activé+avec+succès.');
        exit;
    }
    if ($action === 'desactiver') {
        $pdo->prepare("UPDATE utilisateurs SET statut='inactif' WHERE id=? AND role='client'")->execute([$client_id]);
        header('Location: clients.php?msg=Client+désactivé.');
        exit;
    }
    if ($action === 'supprimer') {
        $pdo->prepare("DELETE FROM utilisateurs WHERE id=? AND role='client'")->execute([$client_id]);
        header('Location: clients.php?msg=Client+supprimé.');
        exit;
    }
}

// ===== Vue historique d'un client =====
$client_detail = null;
$historique    = [];
if ($action === 'historique' && $client_id) {
    $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE id=? AND role='client'");
    $stmt->execute([$client_id]);
    $client_detail = $stmt->fetch();

    if ($client_detail) {
        $h_stmt = $pdo->prepare("
            SELECT r.*, b.titre AS bien_titre, b.type AS bien_type, b.prix_mensuel, b.ville
            FROM reservations r
            JOIN biens b ON r.bien_id = b.id
            WHERE r.client_id = ?
            ORDER BY r.date_creation DESC
        ");
        $h_stmt->execute([$client_id]);
        $historique = $h_stmt->fetchAll();
    }
}

// ===== Filtre / Recherche =====
$filter_statut = $_GET['statut'] ?? '';
$filter_search = trim($_GET['search'] ?? '');

$sql    = "SELECT u.*, (SELECT COUNT(*) FROM reservations r WHERE r.client_id=u.id) AS nb_reservations,
                  (SELECT COUNT(*) FROM favoris f WHERE f.client_id=u.id) AS nb_favoris
           FROM utilisateurs u WHERE u.role='client'";
$params = [];

if ($filter_statut) {
    $sql .= " AND u.statut = ?";
    $params[] = $filter_statut;
}
if ($filter_search) {
    $sql .= " AND (u.nom LIKE ? OR u.prenom LIKE ? OR u.email LIKE ?)";
    $s = '%' . $filter_search . '%';
    $params[] = $s; $params[] = $s; $params[] = $s;
}
$sql .= " ORDER BY u.date_creation DESC";

$stmt    = $pdo->prepare($sql);
$stmt->execute($params);
$clients = $stmt->fetchAll();

$page_title = 'Gestion des Clients — LuxeImmo';
require_once '../includes/header.php';
?>

<div class="dashboard-layout">
    <?php require_once 'sidebar.php'; ?>
    <div class="main-content-with-sidebar" style="flex:1;">

        <div style="margin-bottom:32px;">
            <h1 style="font-size:1.85rem;font-weight:800;color:var(--color-text-primary);letter-spacing:-0.5px;margin-bottom:4px;">
                Répertoire des Clients
            </h1>
            <p style="color:var(--color-text-muted);font-size:0.9rem;margin:0;">
                Suivez la liste de vos acheteurs & locataires enregistrés (Total : <strong style="color:var(--color-primary-light);"><?= count($clients) ?></strong> client(s)).
            </p>
        </div>

        <?php if (isset($_GET['msg'])): ?>
        <div class="alert-immo success mb-4">
            <i class="fas fa-check-circle me-2"></i> <?= htmlspecialchars($_GET['msg']) ?>
        </div>
        <?php endif; ?>

        <?php if ($action === 'historique' && $client_detail): ?>
        <!-- ====== VUE HISTORIQUE DÉTAILLÉE ====== -->
        <div style="margin-bottom:20px;">
            <a href="clients.php" class="btn-outline-immo" style="padding:8px 18px;font-size:0.85rem;">
                <i class="fas fa-arrow-left me-2"></i> Retour à la liste des clients
            </a>
        </div>

        <div class="glass-card p-4 mb-4" style="border:1px solid var(--color-border);border-radius:16px;">
            <div style="display:flex;align-items:center;gap:20px;flex-wrap:wrap;">
                <div style="width:60px;height:60px;background:var(--gradient-primary);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:1.4rem;font-weight:800;color:#fff;box-shadow:0 6px 16px rgba(108,99,255,0.3);flex-shrink:0;">
                    <?= strtoupper(substr($client_detail['prenom'], 0, 1)) ?>
                </div>
                <div>
                    <div style="font-size:1.3rem;font-weight:800;color:var(--color-text-primary);">
                        <?= htmlspecialchars($client_detail['prenom'] . ' ' . $client_detail['nom']) ?>
                    </div>
                    <div style="font-size:0.85rem;color:var(--color-text-muted);margin-top:2px;">
                        <i class="far fa-envelope me-1" style="color:var(--color-primary-light);"></i> <?= htmlspecialchars($client_detail['email']) ?>
                        <span class="mx-3">•</span>
                        <i class="fas fa-phone me-1" style="color:var(--color-primary-light);"></i> <?= htmlspecialchars($client_detail['telephone'] ?: 'Non renseigné') ?>
                    </div>
                </div>
                <div style="margin-left:auto;">
                    <span class="status-badge <?= $client_detail['statut'] === 'actif' ? 'disponible' : 'occupe' ?>">
                        <?= ucfirst($client_detail['statut']) ?>
                    </span>
                </div>
            </div>
        </div>

        <div class="glass-card p-4">
            <h2 style="font-size:1.15rem;font-weight:700;color:var(--color-text-primary);margin-bottom:20px;">
                <i class="fas fa-history me-2" style="color:var(--color-primary-light);"></i>
                Historique des Réservations de ce Client (<?= count($historique) ?>)
            </h2>
            <?php if (empty($historique)): ?>
                <div style="text-align:center;padding:40px;color:var(--color-text-muted);">
                    <i class="fas fa-inbox mb-2" style="font-size:2rem;opacity:0.3;display:block;"></i>
                    Ce client n'a effectué aucune réservation pour le moment.
                </div>
            <?php else: ?>
            <div class="table-responsive-immo">
                <table class="table-immo">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Bien Immobiliers</th>
                            <th>Ville</th>
                            <th>Période</th>
                            <th>Montant Estimé</th>
                            <th>Statut</th>
                            <th style="text-align:right;">Date de Demande</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($historique as $h):
                            $days = max(1, (new DateTime($h['date_debut']))->diff(new DateTime($h['date_fin']))->days);
                            $montant = round(($h['prix_mensuel'] / 30) * $days);
                        ?>
                        <tr>
                            <td style="color:var(--color-text-muted);font-size:.82rem;font-weight:700;">#<?= $h['id'] ?></td>
                            <td>
                                <div style="font-weight:700;color:var(--color-text-primary);font-size:.88rem;"><?= htmlspecialchars($h['bien_titre']) ?></div>
                                <span style="background:rgba(108,99,255,0.1);color:var(--color-primary-light);border-radius:20px;padding:2px 8px;font-size:.72rem;font-weight:700;margin-top:2px;display:inline-block;"><?= ucfirst($h['bien_type']) ?></span>
                            </td>
                            <td style="font-size:.83rem;color:var(--color-text-secondary);"><?= htmlspecialchars($h['ville']) ?></td>
                            <td style="font-size:.83rem;color:var(--color-text-secondary);white-space:nowrap;">
                                <?= date('d/m/Y', strtotime($h['date_debut'])) ?> → <?= date('d/m/Y', strtotime($h['date_fin'])) ?>
                            </td>
                            <td style="font-weight:800;color:var(--color-accent-light);font-size:.88rem;white-space:nowrap;">
                                <?= number_format($montant, 0, ',', ' ') ?> FCFA
                            </td>
                            <td><span class="status-badge <?= $h['statut'] ?>"><?= ucfirst(str_replace('_',' ',$h['statut'])) ?></span></td>
                            <td style="font-size:.78rem;color:var(--color-text-muted);text-align:right;white-space:nowrap;">
                                <?= date('d/m/Y H:i', strtotime($h['date_creation'])) ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>

        <?php else: ?>
        <!-- ====== LISTE DES CLIENTS ====== -->
        <!-- Filtres et Recherche -->
        <div class="glass-card p-3 mb-4" style="border:1px solid var(--color-border);">
            <form method="GET" style="display:flex;gap:12px;flex-wrap:wrap;align-items:center;">
                <div style="flex:1;min-width:240px;position:relative;">
                    <i class="fas fa-search" style="position:absolute;left:14px;top:50%;transform:translateY(-50%);color:var(--color-text-muted);"></i>
                    <input type="text" name="search" class="form-control-immo" placeholder="Rechercher par nom, prénom ou adresse email..."
                           style="padding-left:42px;"
                           value="<?= htmlspecialchars($filter_search) ?>">
                </div>
                <select name="statut" class="form-select-immo" style="width:auto;min-width:160px;">
                    <option value="">Tous les statuts</option>
                    <option value="actif"   <?= $filter_statut === 'actif'   ? 'selected' : '' ?>>Comptes Actifs</option>
                    <option value="inactif" <?= $filter_statut === 'inactif' ? 'selected' : '' ?>>Comptes Inactifs</option>
                </select>
                <button type="submit" class="btn-primary-immo" style="padding:10px 20px;font-size:0.88rem;">
                    <i class="fas fa-filter me-1"></i> Filtrer
                </button>
                <?php if ($filter_search || $filter_statut): ?>
                <a href="clients.php" class="btn-outline-immo" style="padding:10px 16px;"><i class="fas fa-times me-1"></i> Effacer</a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Tableau des clients -->
        <div class="glass-card p-4">
            <div class="table-responsive-immo">
                <table class="table-immo">
                    <thead>
                        <tr>
                            <th>Profil Client</th>
                            <th>Coordonnées</th>
                            <th>Réservations</th>
                            <th>Favoris</th>
                            <th>Inscrit le</th>
                            <th>Statut</th>
                            <th style="text-align:right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($clients)): ?>
                        <tr>
                            <td colspan="7" style="text-align:center;padding:40px;color:var(--color-text-muted);">
                                <i class="fas fa-user-slash mb-2" style="font-size:2rem;opacity:0.3;display:block;"></i>
                                Aucun client ne correspond à votre recherche.
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($clients as $c): ?>
                        <tr>
                            <td>
                                <div style="display:flex;align-items:center;gap:12px;">
                                    <div style="width:42px;height:42px;background:var(--gradient-primary);border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:800;color:#fff;font-size:0.95rem;flex-shrink:0;box-shadow:0 4px 10px rgba(108,99,255,0.25);">
                                        <?= strtoupper(substr($c['prenom'], 0, 1)) ?>
                                    </div>
                                    <div>
                                        <div style="font-weight:700;color:var(--color-text-primary);font-size:0.88rem;">
                                            <?= htmlspecialchars($c['prenom'] . ' ' . $c['nom']) ?>
                                        </div>
                                        <div style="font-size:0.72rem;color:var(--color-text-muted);">Identifiant : #<?= $c['id'] ?></div>
                                    </div>
                                </div>
                            </td>
                            <td style="font-size:0.83rem;">
                                <div style="color:var(--color-text-primary);font-weight:600;"><i class="far fa-envelope me-1" style="color:var(--color-primary-light);"></i><?= htmlspecialchars($c['email']) ?></div>
                                <?php if (!empty($c['telephone'])): ?>
                                <div style="color:var(--color-text-muted);margin-top:2px;"><i class="fas fa-phone me-1" style="color:var(--color-primary-light);"></i><?= htmlspecialchars($c['telephone']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span style="background:rgba(108,99,255,0.1);color:var(--color-primary-light);border-radius:20px;padding:3px 10px;font-size:0.78rem;font-weight:700;">
                                    <i class="fas fa-calendar-check me-1"></i><?= $c['nb_reservations'] ?>
                                </span>
                            </td>
                            <td>
                                <span style="background:rgba(239,68,68,0.08);color:#ef4444;border-radius:20px;padding:3px 10px;font-size:0.78rem;font-weight:700;">
                                    <i class="fas fa-heart me-1"></i><?= $c['nb_favoris'] ?>
                                </span>
                            </td>
                            <td style="font-size:0.82rem;color:var(--color-text-secondary);white-space:nowrap;">
                                <?= date('d/m/Y', strtotime($c['date_creation'])) ?>
                            </td>
                            <td>
                                <span class="status-badge <?= $c['statut'] === 'actif' ? 'disponible' : 'occupe' ?>">
                                    <?= ucfirst($c['statut']) ?>
                                </span>
                            </td>
                            <td style="text-align:right;">
                                <div style="display:inline-flex;gap:6px;justify-content:flex-end;">
                                    <a href="clients.php?action=historique&id=<?= $c['id'] ?>"
                                       class="btn-outline-immo" style="font-size:0.78rem;padding:6px 12px;" title="Consulter l'historique complet">
                                        <i class="fas fa-history me-1"></i> Historique
                                    </a>
                                    <?php if ($c['statut'] === 'actif'): ?>
                                    <a href="clients.php?action=desactiver&id=<?= $c['id'] ?>"
                                       class="btn-outline-immo" style="font-size:0.78rem;padding:6px 10px;border-color:rgba(245,158,11,0.4);color:#f59e0b;"
                                       onclick="return confirm('Désactiver temporairement le compte de <?= htmlspecialchars($c['prenom']) ?> ?');"
                                       title="Désactiver le compte">
                                        <i class="fas fa-ban me-1"></i> Désactiver
                                    </a>
                                    <?php else: ?>
                                    <a href="clients.php?action=activer&id=<?= $c['id'] ?>"
                                       class="btn-outline-immo" style="font-size:0.78rem;padding:6px 12px;border-color:rgba(16,185,129,0.4);color:#10b981;"
                                       onclick="return confirm('Réactiver le compte de <?= htmlspecialchars($c['prenom']) ?> ?');"
                                       title="Réactiver le compte">
                                        <i class="fas fa-check me-1"></i> Activer
                                    </a>
                                    <?php endif; ?>
                                    <a href="clients.php?action=supprimer&id=<?= $c['id'] ?>"
                                       class="btn-outline-immo" style="font-size:0.78rem;padding:6px 12px;border-color:rgba(239,68,68,0.4);color:#ef4444;"
                                       onclick="return confirm('Êtes-vous sûr de vouloir supprimer définitivement ce compte client ?');"
                                       title="Supprimer la fiche client">
                                        <i class="fas fa-trash me-1"></i> Supprimer
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
