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
                if ($_FILES['images']['size'][$i] > 10 * 1024 * 1024) continue;

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
           (SELECT COUNT(*) FROM images WHERE bien_id=b.id) AS nb_images,
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

        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:32px;flex-wrap:wrap;gap:16px;">
            <div>
                <h1 style="font-size:1.85rem;font-weight:800;color:var(--color-text-primary);letter-spacing:-0.5px;margin-bottom:4px;">
                    Gestion du Catalogue Immobilier
                </h1>
                <p style="color:var(--color-text-muted);font-size:0.9rem;margin:0;">
                    Gérez vos appartements et villas d'exception (Total : <strong style="color:var(--color-primary-light);"><?= $total ?></strong> bien(s)).
                </p>
            </div>
            <a href="biens.php?action=ajouter#form-bien" class="btn-primary-immo" style="padding:10px 18px;font-size:0.88rem;">
                <i class="fas fa-plus me-2"></i>Ajouter un bien
            </a>
        </div>

        <?php if (isset($_GET['success'])): ?>
        <div class="alert-immo success mb-4">
            <i class="fas fa-check-circle me-2"></i>
            <?= htmlspecialchars($_GET['success'] === 'bien_supprime' ? 'Bien supprimé avec succès.' : $_GET['success']) ?>
        </div>
        <?php endif; ?>

        <?php if ($action === 'ajouter' || $action === 'modifier'): ?>
        <!-- ===================== FORMULAIRE DE CRÉATION / ÉDITION ===================== -->
        <div id="form-bien" class="glass-card p-4 p-md-5 mb-5" style="border:1px solid var(--color-border);border-radius:16px;">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px;border-bottom:1px solid var(--color-border);padding-bottom:16px;">
                <h2 style="font-size:1.25rem;font-weight:800;color:var(--color-text-primary);margin:0;">
                    <i class="fas <?= $action === 'ajouter' ? 'fa-plus-circle' : 'fa-edit' ?> me-2" style="color:var(--color-primary-light);"></i>
                    <?= $action === 'ajouter' ? 'Ajouter un nouveau bien d\'exception' : 'Modifier les caractéristiques du bien' ?>
                </h2>
                <a href="biens.php" class="btn-outline-immo" style="font-size:0.8rem;padding:6px 12px;">
                    <i class="fas fa-times me-1"></i> Fermer
                </a>
            </div>

            <?php if (!empty($errors)): ?>
            <div class="alert-immo error mb-4" style="flex-direction:column;align-items:flex-start;gap:6px;">
                <div class="d-flex gap-2"><i class="fas fa-exclamation-circle"></i><strong>Veuillez corriger les erreurs suivantes :</strong></div>
                <ul style="margin:0;padding-left:20px;">
                    <?php foreach ($errors as $e): ?><li style="font-size:0.85rem;"><?= htmlspecialchars($e) ?></li><?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <?php if ($success): ?>
            <div class="alert-immo success mb-4"><i class="fas fa-check-circle me-2"></i> <?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <form method="POST" action="biens.php?action=<?= $action ?>&id=<?= $edit_id ?>" enctype="multipart/form-data">
                <div class="row g-4">
                    <div class="col-12">
                        <label class="form-label-immo">Titre de l'annonce *</label>
                        <input type="text" name="titre" class="form-control-immo" required
                               placeholder="Ex : Superbe Villa F5 avec Piscine aux Almadies"
                               value="<?= htmlspecialchars($bien_edit['titre'] ?? $_POST['titre'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label-immo">Type de bien *</label>
                        <select name="type" class="form-select-immo" required>
                            <option value="">Sélectionner un type...</option>
                            <option value="appartement" <?= ($bien_edit['type'] ?? $_POST['type'] ?? '') === 'appartement' ? 'selected' : '' ?>>Appartement</option>
                            <option value="villa" <?= ($bien_edit['type'] ?? $_POST['type'] ?? '') === 'villa' ? 'selected' : '' ?>>Villa</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label-immo">Statut de disponibilité *</label>
                        <select name="statut" class="form-select-immo" required>
                            <?php foreach (['disponible' => 'Disponible', 'reserve' => 'Réservé', 'occupe' => 'Occupé'] as $key => $label): ?>
                            <option value="<?= $key ?>" <?= ($bien_edit['statut'] ?? 'disponible') === $key ? 'selected' : '' ?>>
                                <?= $label ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label-immo">Description détaillée *</label>
                        <textarea name="description" class="form-control-immo" rows="4" required
                                  placeholder="Décrivez les atouts, l'exposition, les équipements et l'environnement du bien..."
                                  style="resize:vertical;"><?= htmlspecialchars($bien_edit['description'] ?? $_POST['description'] ?? '') ?></textarea>
                    </div>
                    <div class="col-lg-4 col-sm-6 col-12">
                        <label class="form-label-immo">Loyer mensuel (FCFA) *</label>
                        <input type="number" name="prix_mensuel" class="form-control-immo" required min="1"
                               placeholder="Ex: 500000"
                               value="<?= htmlspecialchars((string)($bien_edit['prix_mensuel'] ?? $_POST['prix_mensuel'] ?? '')) ?>">
                    </div>
                    <div class="col-lg-4 col-sm-6 col-12">
                        <label class="form-label-immo">Superficie (m²) *</label>
                        <input type="number" name="superficie" class="form-control-immo" required min="10"
                               placeholder="Ex: 150"
                               value="<?= $bien_edit['superficie'] ?? $_POST['superficie'] ?? '' ?>">
                    </div>
                    <div class="col-lg-4 col-sm-6 col-12">
                        <label class="form-label-immo">Nombre de chambres *</label>
                        <input type="number" name="chambres" class="form-control-immo" required min="1" max="20"
                               placeholder="Ex: 3"
                               value="<?= $bien_edit['chambres'] ?? $_POST['chambres'] ?? '' ?>">
                    </div>
                    <div class="col-lg-4 col-sm-6 col-12">
                        <label class="form-label-immo">Nombre de salons *</label>
                        <input type="number" name="salons" class="form-control-immo" required min="1" max="10"
                               placeholder="Ex: 1"
                               value="<?= $bien_edit['salons'] ?? $_POST['salons'] ?? '' ?>">
                    </div>
                    <div class="col-lg-4 col-sm-6 col-12">
                        <label class="form-label-immo">Salles de bain *</label>
                        <input type="number" name="salles_de_bain" class="form-control-immo" required min="1" max="15"
                               placeholder="Ex: 2"
                               value="<?= $bien_edit['salles_de_bain'] ?? $_POST['salles_de_bain'] ?? '' ?>">
                    </div>
                    <div class="col-lg-4 col-sm-6 col-12">
                        <label class="form-label-immo">Ville *</label>
                        <input type="text" name="ville" class="form-control-immo" required
                               placeholder="Ex: Dakar"
                               value="<?= htmlspecialchars($bien_edit['ville'] ?? $_POST['ville'] ?? '') ?>">
                    </div>
                    <div class="col-12">
                        <label class="form-label-immo">Adresse exacte *</label>
                        <input type="text" name="adresse" class="form-control-immo" required
                               placeholder="Ex: Almadies, Route des deux mamelles, Villa 12"
                               value="<?= htmlspecialchars($bien_edit['adresse'] ?? $_POST['adresse'] ?? '') ?>">
                    </div>

                    <!-- Upload d'images -->
                    <div class="col-12">
                        <label class="form-label-immo"><i class="fas fa-images me-1" style="color:var(--color-primary-light);"></i> Photos de présentation du bien</label>

                        <?php if (!empty($edit_images)): ?>
                        <div style="display:flex;flex-wrap:wrap;gap:12px;margin-bottom:16px;">
                            <?php foreach ($edit_images as $img): ?>
                            <div style="position:relative;width:110px;height:85px;border-radius:10px;overflow:hidden;border:1px solid var(--color-border);box-shadow:0 4px 12px rgba(0,0,0,0.1);">
                                <img src="../<?= htmlspecialchars($img['chemin']) ?>"
                                     style="width:100%;height:100%;object-fit:cover;"
                                     onerror="this.src='../assets/images/placeholder.svg'">
                                <?php if ($img['est_principale']): ?>
                                <span style="position:absolute;bottom:0;left:0;right:0;background:var(--gradient-primary);color:#fff;font-size:0.6rem;text-align:center;padding:2px;font-weight:800;letter-spacing:0.5px;">PRINCIPALE</span>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <label style="display:inline-flex;align-items:center;gap:8px;cursor:pointer;margin-bottom:14px;background:rgba(239,68,68,0.06);padding:8px 14px;border-radius:8px;border:1px solid rgba(239,68,68,0.2);">
                            <input type="checkbox" name="supprimer_images" style="accent-color:var(--color-danger);">
                            <span style="font-size:0.83rem;color:var(--color-danger);font-weight:600;">Remplacer toutes les images existantes par les nouvelles photos téléversées</span>
                        </label>
                        <?php endif; ?>

                        <div class="upload-zone" onclick="document.getElementById('images').click()">
                            <span class="upload-zone-icon"><i class="fas fa-cloud-upload-alt"></i></span>
                            <div style="font-weight:600;color:var(--color-text-primary);margin-bottom:4px;">
                                Glissez vos photos ici ou <span style="color:var(--color-primary-light);">parcourez vos fichiers</span>
                            </div>
                            <div class="upload-zone-count" style="font-size:0.8rem;color:var(--color-text-muted);">
                                Format recommandés : JPG, WebP, PNG (Max 10 Mo par photo)
                            </div>
                        </div>
                        <input type="file" id="images" name="images[]" multiple accept="image/*" style="display:none;">
                        <div id="image-preview"></div>
                    </div>

                    <div class="col-12 d-flex gap-3 pt-2">
                        <button type="submit" class="btn-primary-immo" style="padding:12px 32px;font-size:0.9rem;">
                            <i class="fas <?= $action === 'ajouter' ? 'fa-check' : 'fa-save' ?> me-2"></i>
                            <?= $action === 'ajouter' ? 'Enregistrer et publier le bien' : 'Sauvegarder les modifications' ?>
                        </button>
                        <a href="biens.php" class="btn-outline-immo" style="padding:12px 24px;font-size:0.9rem;">
                            <i class="fas fa-times me-2"></i> Annuler
                        </a>
                    </div>
                </div>
            </form>
        </div>
        <?php endif; ?>

        <!-- ===================== TABLEAU DES BIENS ===================== -->
        <div class="glass-card p-4">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;flex-wrap:wrap;gap:12px;">
                <div>
                    <h2 style="font-size:1.15rem;font-weight:700;color:var(--color-text-primary);margin:0;">
                        <i class="fas fa-list me-2" style="color:var(--color-primary-light);"></i>
                        Liste Générale des Biens
                    </h2>
                    <p style="font-size:0.82rem;color:var(--color-text-muted);margin:4px 0 0 0;">Consultez et modifiez les fiches des propriétés disponibles ou réservées</p>
                </div>
            </div>

            <div class="table-responsive-immo">
                <table class="table-immo">
                    <thead>
                        <tr>
                            <th>Propriété</th>
                            <th>Type</th>
                            <th>Prix Mensuel</th>
                            <th>Spécifications</th>
                            <th>Statut</th>
                            <th>Photos</th>
                            <th style="text-align:right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($biens)): ?>
                        <tr>
                            <td colspan="7" style="text-align:center;padding:32px;color:var(--color-text-muted);">
                                Aucun bien immobilier répertorié.
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($biens as $b): ?>
                        <tr>
                            <td>
                                <div style="display:flex;align-items:center;gap:14px;">
                                    <?php if ($b['img']): ?>
                                    <img src="../<?= htmlspecialchars($b['img']) ?>"
                                         style="width:54px;height:42px;border-radius:8px;object-fit:cover;flex-shrink:0;box-shadow:0 2px 8px rgba(0,0,0,0.15);"
                                         onerror="this.style.display='none'">
                                    <?php else: ?>
                                    <div style="width:54px;height:42px;border-radius:8px;background:var(--color-surface);display:flex;align-items:center;justify-content:center;flex-shrink:0;border:1px solid var(--color-border);">
                                        <i class="fas fa-home" style="color:var(--color-text-muted);font-size:1rem;"></i>
                                    </div>
                                    <?php endif; ?>
                                    <div>
                                        <div style="font-weight:700;color:var(--color-text-primary);font-size:0.88rem;max-width:240px;white-space:normal;line-height:1.3;"><?= htmlspecialchars($b['titre']) ?></div>
                                        <div style="font-size:0.75rem;color:var(--color-text-muted);margin-top:2px;">
                                            <i class="fas fa-map-marker-alt me-1" style="color:var(--color-primary-light);"></i><?= htmlspecialchars($b['ville']) ?>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span style="background:rgba(108,99,255,0.1);color:var(--color-primary-light);border-radius:20px;padding:3px 10px;font-size:0.75rem;font-weight:700;">
                                    <?= ucfirst($b['type']) ?>
                                </span>
                            </td>
                            <td style="font-weight:800;color:var(--color-accent-light);font-size:0.9rem;white-space:nowrap;">
                                <?= number_format($b['prix_mensuel'], 0, ',', ' ') ?> <span style="font-size:0.72rem;font-weight:600;">FCFA</span>
                            </td>
                            <td style="font-size:0.82rem;color:var(--color-text-secondary);white-space:nowrap;">
                                <span title="Chambres"><i class="fas fa-bed me-1" style="color:var(--color-text-muted);"></i><?= $b['chambres'] ?></span>
                                <span class="mx-2">•</span>
                                <span title="Salles de bain"><i class="fas fa-bath me-1" style="color:var(--color-text-muted);"></i><?= $b['salles_de_bain'] ?></span>
                                <span class="mx-2">•</span>
                                <span title="Superficie"><i class="fas fa-vector-square me-1" style="color:var(--color-text-muted);"></i><?= $b['superficie'] ?> m²</span>
                            </td>
                            <td>
                                <span class="status-badge <?= $b['statut'] ?>">
                                    <?= ucfirst($b['statut']) ?>
                                </span>
                            </td>
                            <td>
                                <span style="background:rgba(108,99,255,0.08);color:var(--color-primary-light);padding:4px 10px;border-radius:20px;font-size:0.78rem;font-weight:700;">
                                    <i class="fas fa-camera me-1"></i><?= $b['nb_images'] ?? 0 ?>
                                </span>
                            </td>
                            <td style="text-align:right;">
                                <div style="display:inline-flex;gap:6px;justify-content:flex-end;">
                                    <a href="../detail.php?id=<?= $b['id'] ?>" class="btn-outline-immo"
                                       style="font-size:0.78rem;padding:6px 10px;" target="_blank" title="Voir l'annonce publique">
                                        <i class="fas fa-eye me-1"></i> Voir
                                    </a>
                                    <a href="biens.php?action=modifier&id=<?= $b['id'] ?>#form-bien" class="btn-outline-immo"
                                       style="font-size:0.78rem;padding:6px 10px;" title="Modifier le bien">
                                        <i class="fas fa-edit me-1"></i> Modifier
                                    </a>
                                    <a href="biens.php?action=supprimer&id=<?= $b['id'] ?>" class="btn-outline-immo"
                                       style="font-size:0.78rem;padding:6px 10px;border-color:rgba(239,68,68,0.4);color:#ef4444;"
                                       onclick="return confirm('Êtes-vous sûr de vouloir supprimer définitivement ce bien ?');"
                                       title="Supprimer la propriété">
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

            <!-- Pagination -->
            <?php if ($pages > 1): ?>
            <div class="pagination-immo mt-4">
                <?php for ($i = 1; $i <= $pages; $i++): ?>
                <div class="page-item-immo">
                    <?php if ($i == $page): ?>
                    <span class="active" style="background:var(--gradient-primary);color:#fff;font-weight:700;border:none;"><?= $i ?></span>
                    <?php else: ?>
                    <a href="biens.php?p=<?= $i ?>"><?= $i ?></a>
                    <?php endif; ?>
                </div>
                <?php endfor; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
