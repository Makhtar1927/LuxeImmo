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
            <h1 style="font-size:1.8rem;font-weight:800;color:var(--color-text-primary);letter-spacing:-0.5px;margin-bottom:6px;">
                Gestion des Clients
            </h1>
            <p style="color:var(--color-text-muted);font-size:0.9rem;"><?= count($clients) ?> client(s)</p>
        </div>

        <?php if (isset($_GET['msg'])): ?>
        <div class="alert-immo success mb-4">
            <i class="fas fa-check-circle"></i> <?= htmlspecialchars($_GET['msg']) ?>
        </div>
        <?php endif; ?>

        <?php if ($action === 'historique' && $client_detail): ?>
        <!-- ====== VUE HISTORIQUE ====== -->
        <div style="margin-bottom:16px;">
            <a href="clients.php" class="btn-outline-immo" style="padding:8px 18px;font-size:0.85rem;">
                <i class="fas fa-arrow-left"></i> Retour à la liste
            </a>
        </div>

        <div class="glass-card p-4 mb-4">
            <div style="display:flex;align-items:center;gap:16px;flex-wrap:wrap;">
                <div style="width:56px;height:56px;background:var(--gradient-primary);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:1.3rem;font-weight:800;color:#fff;flex-shrink:0;">
                    <?= strtoupper(substr($client_detail['prenom'], 0, 1)) ?>
                </div>
                <div>
                    <div style="font-size:1.2rem;font-weight:800;color:var(--color-text-primary);">
                        <?= htmlspecialchars($client_detail['prenom'] . ' ' . $client_detail['nom']) ?>
                    </div>
                    <div style="font-size:0.85rem;color:var(--color-text-muted);">
                        <i class="fas fa-envelope me-2"></i><?= htmlspecialchars($client_detail['email']) ?>
                        <span class="mx-3">|</span>
                        <i class="fas fa-phone me-2"></i><?= htmlspecialchars($client_detail['telephone']) ?>
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
            <h2 style="font-size:1.1rem;font-weight:700;color:var(--color-text-primary);margin-bottom:20px;">
                <i class="fas fa-history me-2" style="color:var(--color-primary-light);"></i>
                Historique des Réservations (<?= count($historique) ?>)
            </h2>
            <?php if (empty($historique)): ?>
                <p style="color:var(--color-text-muted);text-align:center;padding:32px;">Aucune réservation pour ce client.</p>
            <?php else: ?>
            <div style="overflow-x:auto;">
                <table class="table-immo">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Bien</th>
                            <th>Ville</th>
                            <th>Période</th>
                            <th>Montant est.</th>
                            <th>Statut</th>
                            <th>Date résa.</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($historique as $h):
                            $days = (new DateTime($h['date_debut']))->diff(new DateTime($h['date_fin']))->days;
                            $montant = round(($h['prix_mensuel'] / 30) * $days);
                        ?>
                        <tr>
                            <td style="color:var(--color-text-muted);font-size:.82rem;">#<?= $h['id'] ?></td>
                            <td>
                                <div style="font-weight:600;color:var(--color-text-primary);font-size:.88rem;"><?= htmlspecialchars($h['bien_titre']) ?></div>
                                <span style="background:rgba(108,99,255,0.1);color:var(--color-primary-light);border-radius:20px;padding:2px 8px;font-size:.72rem;font-weight:600;"><?= ucfirst($h['bien_type']) ?></span>
                            </td>
                            <td style="font-size:.83rem;color:var(--color-text-secondary);"><?= htmlspecialchars($h['ville']) ?></td>
                            <td style="font-size:.83rem;color:var(--color-text-secondary);">
                                <?= date('d/m/Y', strtotime($h['date_debut'])) ?> → <?= date('d/m/Y', strtotime($h['date_fin'])) ?>
                            </td>
                            <td style="font-weight:700;color:var(--color-accent-light);font-size:.88rem;">
                                <?= number_format($montant, 0, ',', ' ') ?> FCFA
                            </td>
                            <td><span class="status-badge <?= $h['statut'] ?>"><?= ucfirst(str_replace('_',' ',$h['statut'])) ?></span></td>
                            <td style="font-size:.78rem;color:var(--color-text-muted);">
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
        <!-- Filtres -->
        <div class="glass-card p-3 mb-4">
            <form method="GET" style="display:flex;gap:12px;flex-wrap:wrap;align-items:center;">
                <div class="input-group-icon" style="flex:1;min-width:180px;">
                    <i class="fas fa-search"></i>
                    <input type="text" name="search" class="form-control-immo" placeholder="Nom, prénom, email..."
                           value="<?= htmlspecialchars($filter_search) ?>">
                </div>
                <select name="statut" class="form-select-immo" style="width:auto;">
                    <option value="">Tous les statuts</option>
                    <option value="actif"   <?= $filter_statut === 'actif'   ? 'selected' : '' ?>>Actifs</option>
                    <option value="inactif" <?= $filter_statut === 'inactif' ? 'selected' : '' ?>>Inactifs</option>
                </select>
                <button type="submit" class="btn-primary-immo" style="padding:11px 20px;">
                    <i class="fas fa-search"></i> Filtrer
                </button>
                <?php if ($filter_search || $filter_statut): ?>
                <a href="clients.php" class="btn-outline-immo" style="padding:10px 16px;"><i class="fas fa-times"></i></a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Tableau des clients -->
        <div class="glass-card p-4">
            <div style="overflow-x:auto;">
                <table class="table-immo">
                    <thead>
                        <tr>
                            <th>Client</th>
                            <th>Contact</th>
                            <th>Réservations</th>
                            <th>Favoris</th>
                            <th>Inscrit le</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($clients)): ?>
                        <tr>
                            <td colspan="7" style="text-align:center;padding:40px;color:var(--color-text-muted);">
                                <i class="fas fa-users" style="font-size:2rem;opacity:0.2;display:block;margin-bottom:10px;"></i>
                                Aucun client trouvé.
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($clients as $c): ?>
                        <tr>
                            <td>
                                <div style="display:flex;align-items:center;gap:12px;">
                                    <div style="width:38px;height:38px;background:var(--gradient-primary);border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:800;color:#fff;font-size:0.9rem;flex-shrink:0;">
                                        <?= strtoupper(substr($c['prenom'], 0, 1)) ?>
                                    </div>
                                    <div>
                                        <div style="font-weight:600;color:var(--color-text-primary);font-size:0.88rem;">
                                            <?= htmlspecialchars($c['prenom'] . ' ' . $c['nom']) ?>
                                        </div>
                                        <div style="font-size:0.72rem;color:var(--color-text-muted);">#<?= $c['id'] ?></div>
                                    </div>
                                </div>
                            </td>
                            <td style="font-size:0.83rem;">
                                <div style="color:var(--color-text-secondary);"><?= htmlspecialchars($c['email']) ?></div>
                                <div style="color:var(--color-text-muted);"><?= htmlspecialchars($c['telephone']) ?></div>
                            </td>
                            <td>
                                <span style="background:rgba(108,99,255,0.1);color:var(--color-primary-light);border-radius:20px;padding:3px 10px;font-size:0.78rem;font-weight:700;">
                                    <i class="fas fa-calendar me-1"></i><?= $c['nb_reservations'] ?>
                                </span>
                            </td>
                            <td>
                                <span style="background:rgba(239,68,68,0.08);color:#fca5a5;border-radius:20px;padding:3px 10px;font-size:0.78rem;font-weight:700;">
                                    <i class="fas fa-heart me-1"></i><?= $c['nb_favoris'] ?>
                                </span>
                            </td>
                            <td style="font-size:0.78rem;color:var(--color-text-muted);">
                                <?= date('d/m/Y', strtotime($c['date_creation'])) ?>
                            </td>
                            <td>
                                <span class="status-badge <?= $c['statut'] === 'actif' ? 'disponible' : 'occupe' ?>">
                                    <?= ucfirst($c['statut']) ?>
                                </span>
                            </td>
                            <td>
                                <div style="display:flex;gap:6px;flex-wrap:wrap;">
                                    <a href="clients.php?action=historique&id=<?= $c['id'] ?>"
                                       class="btn-outline-immo" style="font-size:0.75rem;padding:5px 10px;" title="Voir l'historique">
                                        <i class="fas fa-history"></i>
                                    </a>
                                    <?php if ($c['statut'] === 'actif'): ?>
                                    <a href="clients.php?action=desactiver&id=<?= $c['id'] ?>"
                                       class="btn-outline-immo" style="font-size:0.75rem;padding:5px 10px;border-color:rgba(245,158,11,0.4);color:#fcd34d;"
                                       data-confirm="Désactiver le compte de <?= htmlspecialchars($c['prenom']) ?> ?"
                                       title="Désactiver">
                                        <i class="fas fa-ban"></i>
                                    </a>
                                    <?php else: ?>
                                    <a href="clients.php?action=activer&id=<?= $c['id'] ?>"
                                       class="btn-outline-immo" style="font-size:0.75rem;padding:5px 10px;border-color:rgba(16,185,129,0.4);color:#6ee7b7;"
                                       data-confirm="Réactiver le compte de <?= htmlspecialchars($c['prenom']) ?> ?"
                                       title="Activer">
                                        <i class="fas fa-check"></i>
                                    </a>
                                    <?php endif; ?>
                                    <a href="clients.php?action=supprimer&id=<?= $c['id'] ?>"
                                       class="btn-outline-immo" style="font-size:0.75rem;padding:5px 10px;border-color:rgba(239,68,68,0.4);color:#fca5a5;"
                                       data-confirm="Supprimer définitivement le compte de <?= htmlspecialchars($c['prenom']) ?> et toutes ses données ? Cette action est irréversible."
                                       title="Supprimer">
                                        <i class="fas fa-trash"></i>
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
