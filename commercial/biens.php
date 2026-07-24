<?php
/**
 * commercial/biens.php
 * Gestion CRUD des biens immobiliers (appartements & villas)
 * Fonctionnalités : Lister, Ajouter, Modifier, Supprimer + upload multiple d'images
 */
require_once '../config/db.php';
require_once '../includes/auth_check.php';
require_role('commercial');

$UPLOAD_DIR = '../assets/images/';
$errors  = [];
$success = '';
$action  = $_GET['action'] ?? 'list';
$edit_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// ========== SUPPRESSION ==============
if ($action === 'supprimer' && $edit_id) {
    // Supprimer les images physiques
    $imgs = $pdo->prepare("SELECT chemin FROM images WHERE bien_id = ?");
    $imgs->execute([$edit_id]);
    foreach ($imgs->fetchAll(PDO::FETCH_COLUMN) as $img_path) {
        $full = '../' . $img_path;
        if (file_exists($full)) unlink($full);
    }
    $pdo->prepare("DELETE FROM biens WHERE id = ?")->execute([$edit_id]);
    header('Location: biens.php?success=bien_supprime');
    exit;
}

// ========== AJOUT / MODIFICATION ==============
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'titre'          => trim($_POST['titre'] ?? ''),
        'description'    => trim($_POST['description'] ?? ''),
        'type'           => $_POST['type'] ?? '',
        'prix_mensuel'   => $_POST['prix_mensuel'] ?? '',
        'adresse'        => trim($_POST['adresse'] ?? ''),
        'ville'          => trim($_POST['ville'] ?? ''),
        'chambres'       => (int)($_POST['chambres'] ?? 0),
        'salons'         => (int)($_POST['salons'] ?? 0),
        'salles_de_bain' => (int)($_POST['salles_de_bain'] ?? 0),
        'superficie'     => (int)($_POST['superficie'] ?? 0),
        'statut'         => $_POST['statut'] ?? 'disponible',
    ];

    // Validations
    if (empty($data['titre']))       $errors[] = 'Le titre est requis.';
    if (empty($data['description'])) $errors[] = 'La description est requise.';
    if (!in_array($data['type'], ['appartement', 'villa'])) $errors[] = 'Type invalide.';
    if (!is_numeric($data['prix_mensuel']) || $data['prix_mensuel'] <= 0) $errors[] = 'Prix invalide.';
    if (empty($data['adresse']))     $errors[] = 'L\'adresse est requise.';
    if (empty($data['ville']))       $errors[] = 'La ville est requise.';
    if ($data['chambres'] < 1)       $errors[] = 'Nombre de chambres invalide.';
    if ($data['salons'] < 1)         $errors[] = 'Nombre de salons invalide.';
    if ($data['salles_de_bain'] < 1) $errors[] = 'Nombre de salles de bain invalide.';
    if ($data['superficie'] < 10)    $errors[] = 'Superficie invalide.';

    if (empty($errors)) {
        if ($edit_id) {
            // Modification
            $pdo->prepare("
                UPDATE biens SET titre=?, description=?, type=?, prix_mensuel=?, adresse=?, ville=?,
                chambres=?, salons=?, salles_de_bain=?, superficie=?, statut=? WHERE id=?
            ")->execute([...array_values($data), $edit_id]);
            $bien_id_to_use = $edit_id;
            $success = 'Bien mis à jour avec succès.';
        } else {
            // Ajout
            $stmt = $pdo->prepare("
                INSERT INTO biens (titre, description, type, prix_mensuel, adresse, ville, chambres, salons, salles_de_bain, superficie, statut)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute(array_values($data));
            $bien_id_to_use = $pdo->lastInsertId();
            $success = 'Bien ajouté avec succès !';
        }

        // Upload des images
        if (!empty($_FILES['images']['name'][0])) {
            if (!is_dir($UPLOAD_DIR)) mkdir($UPLOAD_DIR, 0775, true);

            // Si modification et option "supprimer images existantes"
            if ($edit_id && isset($_POST['supprimer_images'])) {
                $old_imgs = $pdo->prepare("SELECT chemin FROM images WHERE bien_id = ?");
                $old_imgs->execute([$edit_id]);
                foreach ($old_imgs->fetchAll(PDO::FETCH_COLUMN) as $old) {
                    $f = '../' . $old;
                    if (file_exists($f)) unlink($f);
                }
                $pdo->prepare("DELETE FROM images WHERE bien_id = ?")->execute([$edit_id]);
            }
            $chk_main = $pdo->prepare("SELECT COUNT(*) FROM images WHERE bien_id = ? AND est_principale = 1");
            $chk_main->execute([$bien_id_to_use]);
            $has_main = ($chk_main->fetchColumn() > 0);

            $allowed_exts = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
            $first_image  = true;

            foreach ($_FILES['images']['tmp_name'] as $i => $tmp) {
                if ($_FILES['images']['error'][$i] !== UPLOAD_ERR_OK) continue;
                if ($_FILES['images']['size'][$i] > 10 * 1024 * 1024) continue; // max 10MB

                $ext = strtolower(pathinfo($_FILES['images']['name'][$i], PATHINFO_EXTENSION));
                if (!in_array($ext, $allowed_exts)) continue;

                $filename = 'bien_' . $bien_id_to_use . '_' . time() . '_' . $i . '.' . $ext;
                $dest     = $UPLOAD_DIR . $filename;

                if (move_uploaded_file($tmp, $dest)) {
                    $is_principale = 0;
                    if (isset($_POST['principale_idx']) && $_POST['principale_idx'] == $i) {
                        $is_principale = 1;
                        $pdo->prepare("UPDATE images SET est_principale = 0 WHERE bien_id = ?")->execute([$bien_id_to_use]);
                        $has_main = true;
                    } elseif (!$has_main && $first_image) {
                        $is_principale = 1;
                        $has_main = true;
                    }
                    $pdo->prepare("INSERT INTO images (bien_id, chemin, est_principale) VALUES (?, ?, ?)")
                        ->execute([$bien_id_to_use, 'assets/images/' . $filename, $is_principale]);
                    $first_image = false;
                }
            }
        }

        if (!$edit_id) {
            header("Location: biens.php?success=" . urlencode($success));
            exit;
        }
    }
}

// ========== DONNÉES POUR FORMULAIRE D'ÉDITION ==============
$bien_edit = null;
$edit_images = [];
if ($action === 'modifier' && $edit_id) {
    $bien_edit = $pdo->prepare("SELECT * FROM biens WHERE id = ?");
    $bien_edit->execute([$edit_id]);
    $bien_edit = $bien_edit->fetch();
    $edit_images = $pdo->prepare("SELECT * FROM images WHERE bien_id = ? ORDER BY est_principale DESC");
    $edit_images->execute([$edit_id]);
}

// ========== LISTE DES BIENS (PAGINATION) ==============
$page  = max(1, (int)($_GET['p'] ?? 1));
$limit = 8;
$off   = ($page - 1) * $limit;

$total = $pdo->query("SELECT COUNT(*) FROM biens")->fetchColumn();
$pages = ceil($total / $limit);

$stmt = $pdo->prepare("
    SELECT b.*, 
           COALESCE((SELECT chemin FROM images WHERE bien_id=b.id AND est_principale=1 LIMIT 1), (SELECT chemin FROM images WHERE bien_id=b.id ORDER BY id ASC LIMIT 1)) AS img,
           (SELECT COUNT(*) FROM reservations WHERE bien_id=b.id) AS nb_res
    FROM biens b ORDER BY b.id DESC LIMIT ? OFFSET ?
");
$stmt->execute([$limit, $off]);
$biens = $stmt->fetchAll();

$page_title = 'Gestion des biens — LuxeImmo';
require_once '../includes/header.php';
?>

<div class="dashboard-layout">
    <?php require_once 'sidebar.php'; ?>
    <div class="main-content-with-sidebar" style="flex:1;">

        <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:32px;flex-wrap:wrap;gap:16px;">
            <div>
                <h1 style="font-size:1.8rem;font-weight:800;color:var(--color-text-primary);letter-spacing:-0.5px;margin-bottom:6px;">
                    Gestion des Biens
                </h1>
                <p style="color:var(--color-text-muted);font-size:0.9rem;"><?= count($biens) ?> bien(s) au total</p>
            </div>
            <a href="biens.php?action=ajouter" class="btn-primary-immo">
                <i class="fas fa-plus"></i> Ajouter un bien
            </a>
        </div>

        <?php if (isset($_GET['success'])): ?>
        <div class="alert-immo success mb-4">
            <i class="fas fa-check-circle"></i>
            <?= htmlspecialchars($_GET['success'] === 'bien_supprime' ? 'Bien supprimé avec succès.' : $_GET['success']) ?>
        </div>
        <?php endif; ?>

        <?php if ($action === 'ajouter' || $action === 'modifier'): ?>
        <!-- ===================== FORMULAIRE ===================== -->
        <div class="glass-card p-4 p-md-5 mb-5">
            <h2 style="font-size:1.3rem;font-weight:800;color:var(--color-text-primary);margin-bottom:24px;">
                <i class="fas <?= $action === 'ajouter' ? 'fa-plus-circle' : 'fa-edit' ?> me-2" style="color:var(--color-primary-light);"></i>
                <?= $action === 'ajouter' ? 'Ajouter un nouveau bien' : 'Modifier le bien' ?>
            </h2>

            <?php if (!empty($errors)): ?>
            <div class="alert-immo error mb-4" style="flex-direction:column;align-items:flex-start;gap:6px;">
                <div class="d-flex gap-2"><i class="fas fa-exclamation-circle"></i><strong>Erreurs :</strong></div>
                <ul style="margin:0;padding-left:20px;">
                    <?php foreach ($errors as $e): ?><li style="font-size:0.85rem;"><?= htmlspecialchars($e) ?></li><?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <?php if ($success): ?>
            <div class="alert-immo success mb-4"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <form method="POST" action="biens.php?action=<?= $action ?>&id=<?= $edit_id ?>" enctype="multipart/form-data" class="needs-validation" novalidate>
                <div class="row g-4">
                    <div class="col-12">
                        <label class="form-label-immo">Titre du bien *</label>
                        <input type="text" name="titre" class="form-control-immo" required
                               placeholder="Ex : Superbe Villa F5 avec Piscine"
                               value="<?= htmlspecialchars($bien_edit['titre'] ?? $_POST['titre'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label-immo">Type *</label>
                        <select name="type" class="form-select-immo" required>
                            <option value="">Choisir...</option>
                            <option value="appartement" <?= ($bien_edit['type'] ?? $_POST['type'] ?? '') === 'appartement' ? 'selected' : '' ?>>Appartement</option>
                            <option value="villa" <?= ($bien_edit['type'] ?? $_POST['type'] ?? '') === 'villa' ? 'selected' : '' ?>>Villa</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label-immo">Statut *</label>
                        <select name="statut" class="form-select-immo" required>
                            <?php foreach (['disponible', 'reserve', 'occupe'] as $s): ?>
                            <option value="<?= $s ?>" <?= ($bien_edit['statut'] ?? 'disponible') === $s ? 'selected' : '' ?>>
                                <?= ucfirst($s) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label-immo">Description *</label>
                        <textarea name="description" class="form-control-immo" rows="4" required
                                  placeholder="Décrivez le bien en détail..."
                                  style="resize:vertical;"><?= htmlspecialchars($bien_edit['description'] ?? $_POST['description'] ?? '') ?></textarea>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label-immo">Prix mensuel (FCFA) *</label>
                        <input type="number" name="prix_mensuel" class="form-control-immo" required min="1"
                               placeholder="500000"
                               value="<?= htmlspecialchars((string)($bien_edit['prix_mensuel'] ?? $_POST['prix_mensuel'] ?? '')) ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label-immo">Superficie (m²) *</label>
                        <input type="number" name="superficie" class="form-control-immo" required min="10"
                               placeholder="120"
                               value="<?= $bien_edit['superficie'] ?? $_POST['superficie'] ?? '' ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label-immo">Chambres *</label>
                        <input type="number" name="chambres" class="form-control-immo" required min="1" max="20"
                               placeholder="3"
                               value="<?= $bien_edit['chambres'] ?? $_POST['chambres'] ?? '' ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label-immo">Salons *</label>
                        <input type="number" name="salons" class="form-control-immo" required min="1" max="10"
                               placeholder="1"
                               value="<?= $bien_edit['salons'] ?? $_POST['salons'] ?? '' ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label-immo">Salles de bain *</label>
                        <input type="number" name="salles_de_bain" class="form-control-immo" required min="1" max="15"
                               placeholder="2"
                               value="<?= $bien_edit['salles_de_bain'] ?? $_POST['salles_de_bain'] ?? '' ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label-immo">Ville *</label>
                        <input type="text" name="ville" class="form-control-immo" required
                               placeholder="Dakar"
                               value="<?= htmlspecialchars($bien_edit['ville'] ?? $_POST['ville'] ?? '') ?>">
                    </div>
                    <div class="col-12">
                        <label class="form-label-immo">Adresse complète *</label>
                        <input type="text" name="adresse" class="form-control-immo" required
                               placeholder="Almadies, Zone 10, Villa 42"
                               value="<?= htmlspecialchars($bien_edit['adresse'] ?? $_POST['adresse'] ?? '') ?>">
                    </div>

                    <!-- Upload images -->
                    <div class="col-12">
                        <label class="form-label-immo"><i class="fas fa-images me-1"></i> Images du bien</label>

                        <?php if (!empty($edit_images)): ?>
                        <div style="display:flex;flex-wrap:wrap;gap:10px;margin-bottom:16px;">
                            <?php foreach ($edit_images as $img): ?>
                            <div style="position:relative;width:100px;height:80px;border-radius:10px;overflow:hidden;border:1px solid var(--color-border);">
                                <img src="../<?= htmlspecialchars($img['chemin']) ?>"
                                     style="width:100%;height:100%;object-fit:cover;"
                                     onerror="this.src='../assets/images/placeholder.svg'">
                                <?php if ($img['est_principale']): ?>
                                <span style="position:absolute;bottom:0;left:0;right:0;background:rgba(108,99,255,0.85);color:#fff;font-size:0.6rem;text-align:center;padding:2px;font-weight:700;">PRINCIPALE</span>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <label style="display:flex;align-items:center;gap:8px;cursor:pointer;margin-bottom:12px;">
                            <input type="checkbox" name="supprimer_images" style="accent-color:var(--color-danger);">
                            <span style="font-size:0.83rem;color:var(--color-danger);">Supprimer les images existantes et les remplacer</span>
                        </label>
                        <?php endif; ?>

                        <div class="upload-zone" onclick="document.getElementById('images').click()">
                            <span class="upload-zone-icon"><i class="fas fa-cloud-upload-alt"></i></span>
                            <div style="font-weight:600;color:var(--color-text-secondary);margin-bottom:6px;">
                                Glissez vos images ici ou <span style="color:var(--color-primary-light);">cliquez pour parcourir</span>
                            </div>
                            <div class="upload-zone-count" style="font-size:0.8rem;color:var(--color-text-muted);">
                                JPG, PNG, WebP — 5 MB max par image
                            </div>
                        </div>
                        <input type="file" id="images" name="images[]" multiple accept="image/*" style="display:none;">
                        <div id="image-preview"></div>
                    </div>

                    <div class="col-12 d-flex gap-3 pt-2">
                        <button type="submit" class="btn-primary-immo" style="padding:12px 32px;">
                            <i class="fas <?= $action === 'ajouter' ? 'fa-plus' : 'fa-save' ?>"></i>
                            <?= $action === 'ajouter' ? 'Ajouter le bien' : 'Enregistrer les modifications' ?>
                        </button>
                        <a href="biens.php" class="btn-outline-immo" style="padding:12px 24px;">
                            <i class="fas fa-times"></i> Annuler
                        </a>
                    </div>
                </div>
            </form>
        </div>
        <?php endif; ?>

        <!-- ===================== LISTE ===================== -->
        <div class="glass-card p-4">
            <h2 style="font-size:1.1rem;font-weight:700;color:var(--color-text-primary);margin-bottom:20px;">
                <i class="fas fa-list me-2" style="color:var(--color-primary-light);"></i>Liste des biens
            </h2>
            <div style="overflow-x:auto;">
                <table class="table-immo">
                    <thead>
                        <tr>
                            <th>Bien</th>
                            <th>Type</th>
                            <th>Prix / mois</th>
                            <th>Caractéristiques</th>
                            <th>Statut</th>
                            <th>Images</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($biens as $b): ?>
                        <tr>
                            <td>
                                <div style="display:flex;align-items:center;gap:12px;">
                                    <?php if ($b['img']): ?>
                                    <img src="../<?= htmlspecialchars($b['img']) ?>"
                                         style="width:50px;height:40px;border-radius:8px;object-fit:cover;flex-shrink:0;"
                                         onerror="this.style.display='none'">
                                    <?php else: ?>
                                    <div style="width:50px;height:40px;border-radius:8px;background:var(--color-surface);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                        <i class="fas fa-home" style="color:var(--color-text-muted);font-size:1rem;"></i>
                                    </div>
                                    <?php endif; ?>
                                    <div>
                                        <div style="font-weight:600;color:var(--color-text-primary);font-size:0.88rem;"><?= htmlspecialchars($b['titre']) ?></div>
                                        <div style="font-size:0.75rem;color:var(--color-text-muted);">
                                            <i class="fas fa-map-marker-alt me-1"></i><?= htmlspecialchars($b['ville']) ?>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td><span style="background:rgba(108,99,255,0.1);color:var(--color-primary-light);border-radius:20px;padding:3px 10px;font-size:0.75rem;font-weight:600;"><?= ucfirst($b['type']) ?></span></td>
                            <td style="font-weight:700;color:var(--color-accent-light);font-size:0.88rem;">
                                <?= number_format($b['prix_mensuel'], 0, ',', ' ') ?> FCFA
                            </td>
                            <td style="font-size:0.82rem;color:var(--color-text-secondary);">
                                <i class="fas fa-bed me-1"></i><?= $b['chambres'] ?>
                                <i class="fas fa-bath mx-1"></i><?= $b['salles_de_bain'] ?>
                                <i class="fas fa-vector-square mx-1"></i><?= $b['superficie'] ?>m²
                            </td>
                            <td><span class="status-badge <?= $b['statut'] ?>"><?= ucfirst($b['statut']) ?></span></td>
                            <td>
                                <span style="background:rgba(108,99,255,0.08);color:var(--color-primary-light);padding:4px 10px;border-radius:20px;font-size:0.78rem;font-weight:600;">
                                    <i class="fas fa-images me-1"></i><?= $b['nb_images'] ?>
                                </span>
                            </td>
                            <td>
                                <div style="display:flex;gap:6px;flex-wrap:wrap;">
                                    <a href="../detail.php?id=<?= $b['id'] ?>" class="btn-outline-immo"
                                       style="font-size:0.75rem;padding:5px 10px;" target="_blank" title="Voir">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="biens.php?action=modifier&id=<?= $b['id'] ?>" class="btn-outline-immo"
                                       style="font-size:0.75rem;padding:5px 10px;" title="Modifier">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="biens.php?action=supprimer&id=<?= $b['id'] ?>" class="btn-outline-immo"
                                       style="font-size:0.75rem;padding:5px 10px;border-color:rgba(239,68,68,0.4);color:#fca5a5;"
                                       data-confirm="Supprimer ce bien et toutes ses images ? Cette action est irréversible."
                                       title="Supprimer">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
