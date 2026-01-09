<?php
require_once 'includes/auth_check.php';
require_once 'includes/db.php';

$agent_id = $_GET['id'] ?? null;

if (!$agent_id || !is_numeric($agent_id)) {
    // Redirect to a safe page if agent_id is not provided or is invalid
    header('Location: agents.php');
    exit();
}

// Contrôle d'accès : Seuls les utilisateurs privilégiés ou l'agent lui-même peuvent voir le dossier
if (!is_privileged() && $_SESSION['agent_id'] != $agent_id) {
    $_SESSION['toast_message'] = 'Vous n\'êtes pas autorisé à consulter ce dossier.';
    $_SESSION['toast_type'] = 'danger';
    header('Location: index.php');
    exit();
}

if (is_admin()) {
    // --- Traitement des Actions POST ---
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // AJOUT D'ÉVALUATION
        if (isset($_POST['add_evaluation'])) {
            $auteur_nom = $_POST['auteur_nom'];
            $type = $_POST['type'];
            $commentaire = $_POST['commentaire'];

            if (!empty($auteur_nom) && !empty($type) && !empty($commentaire)) {
                $stmt = $conn->prepare("INSERT INTO evaluations (agent_id, auteur_nom, type, commentaire) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("isss", $agent_id, $auteur_nom, $type, $commentaire);
                if ($stmt->execute()) {
                    $_SESSION['toast_message'] = 'Évaluation ajoutée avec succès.';
                    $_SESSION['toast_type'] = 'success';
                } else {
                    $_SESSION['toast_message'] = 'Erreur lors de l\'ajout de l\'évaluation.';
                    $_SESSION['toast_type'] = 'danger';
                }
                $stmt->close();
            } else {
                $_SESSION['toast_message'] = 'Tous les champs pour l\'évaluation sont obligatoires.';
                $_SESSION['toast_type'] = 'warning';
            }
        }
        // MODIFICATION D'ÉVALUATION
        elseif (isset($_POST['edit_evaluation'])) {
            $eval_id = $_POST['edit_evaluation_id'];
            $type = $_POST['edit_type'];
            $commentaire = $_POST['edit_commentaire'];
            $stmt = $conn->prepare("UPDATE evaluations SET type = ?, commentaire = ? WHERE id = ?");
            $stmt->bind_param("ssi", $type, $commentaire, $eval_id);
            if ($stmt->execute()) {
                $_SESSION['toast_message'] = 'Évaluation modifiée avec succès.';
                $_SESSION['toast_type'] = 'success';
            } else {
                $_SESSION['toast_message'] = 'Erreur lors de la modification de l\'évaluation.';
                $_SESSION['toast_type'] = 'danger';
            }
            $stmt->close();
        }
        // SUPPRESSION D'ÉVALUATION
        elseif (isset($_POST['delete_evaluation'])) {
            $eval_id = $_POST['evaluation_id'];
            $stmt = $conn->prepare("DELETE FROM evaluations WHERE id = ? AND agent_id = ?");
            $stmt->bind_param("ii", $eval_id, $agent_id);
            if ($stmt->execute()) {
                $_SESSION['toast_message'] = 'Évaluation supprimée.';
                $_SESSION['toast_type'] = 'success';
            } else {
                $_SESSION['toast_message'] = 'Erreur lors de la suppression.';
                $_SESSION['toast_type'] = 'danger';
            }
            $stmt->close();
        }
        // AJOUT DE SANCTION
        elseif (isset($_POST['add_sanction'])) {
            $auteur_nom = $_POST['auteur_nom_sanction'];
            $type = $_POST['type_sanction'];
            $raison = $_POST['raison_sanction'];

            if (!empty($auteur_nom) && !empty($type) && !empty($raison)) {
                $stmt = $conn->prepare("INSERT INTO sanctions (agent_id, auteur_nom, type, raison) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("isss", $agent_id, $auteur_nom, $type, $raison);
                if ($stmt->execute()) {
                    $_SESSION['toast_message'] = 'Sanction ajoutée avec succès.';
                    $_SESSION['toast_type'] = 'success';
                } else {
                    $_SESSION['toast_message'] = 'Erreur lors de l\'ajout de la sanction.';
                    $_SESSION['toast_type'] = 'danger';
                }
                $stmt->close();
            } else {
                $_SESSION['toast_message'] = 'Tous les champs pour la sanction sont obligatoires.';
                $_SESSION['toast_type'] = 'warning';
            }
        }
        // MODIFICATION DE SANCTION
        elseif (isset($_POST['edit_sanction'])) {
            $sanction_id = $_POST['edit_sanction_id'];
            $type = $_POST['edit_type_sanction'];
            $raison = $_POST['edit_raison_sanction'];
            $stmt = $conn->prepare("UPDATE sanctions SET type = ?, raison = ? WHERE id = ?");
            $stmt->bind_param("ssi", $type, $raison, $sanction_id);
            if ($stmt->execute()) {
                $_SESSION['toast_message'] = 'Sanction modifiée avec succès.';
                $_SESSION['toast_type'] = 'success';
            } else {
                $_SESSION['toast_message'] = 'Erreur lors de la modification de la sanction.';
                $_SESSION['toast_type'] = 'danger';
            }
            $stmt->close();
        }
        // SUPPRESSION DE SANCTION
        elseif (isset($_POST['delete_sanction'])) {
            $sanction_id = $_POST['sanction_id'];
            $stmt = $conn->prepare("DELETE FROM sanctions WHERE id = ? AND agent_id = ?");
            $stmt->bind_param("ii", $sanction_id, $agent_id);
            if ($stmt->execute()) {
                $_SESSION['toast_message'] = 'Sanction supprimée.';
                $_SESSION['toast_type'] = 'success';
            } else {
                $_SESSION['toast_message'] = 'Erreur lors de la suppression.';
                $_SESSION['toast_type'] = 'danger';
            }
            $stmt->close();
        }
    }
}


// --- Récupérer les informations de l'agent ---
$stmt = $conn->prepare("SELECT * FROM agents WHERE id = ?");
$stmt->bind_param("i", $agent_id);
$stmt->execute();
$result_agent = $stmt->get_result();

if ($result_agent->num_rows !== 1) {
    header('Location: agents.php');
    exit();
}
$agent = $result_agent->fetch_assoc();
$stmt->close();

// --- Récupérer les évaluations de l'agent ---
$stmt_eval = $conn->prepare("SELECT * FROM evaluations WHERE agent_id = ? ORDER BY date_creation DESC");
$stmt_eval->bind_param("i", $agent_id);
$stmt_eval->execute();
$result_eval = $stmt_eval->get_result();


// --- Récupérer les sanctions de l'agent ---
$stmt_sanction = $conn->prepare("SELECT * FROM sanctions WHERE agent_id = ? ORDER BY date_sanction DESC");
$stmt_sanction->bind_param("i", $agent_id);
$stmt_sanction->execute();
$result_sanction = $stmt_sanction->get_result();

$page_title = "Dossier Agent : " . htmlspecialchars($agent['nom']);
include 'includes/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Dossier de l'Agent</h1>
    <?php if (is_privileged()): ?>
    <a href="agents.php" class="btn btn-sm btn-outline-secondary">Retour à la liste</a>
    <?php endif; ?>
</div>

<?php if (isset($_SESSION['toast_message'])): ?>
    <div class="toast align-items-center text-white bg-<?php echo $_SESSION['toast_type']; ?> border-0" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body">
                <?php echo $_SESSION['toast_message']; ?>
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>
    <?php
    unset($_SESSION['toast_message']);
    unset($_SESSION['toast_type']);
    ?>
<?php endif; ?>

<!-- Informations de l'agent -->
<div class="card mb-4">
    <div class="card-header">
        <h5><?php echo htmlspecialchars($agent['nom']); ?></h5>
    </div>
    <div class="card-body">
        <p><strong>Matricule :</strong> <?php echo htmlspecialchars($agent['matricule']); ?></p>
        <p><strong>Grade :</strong> <?php echo htmlspecialchars($agent['grade']); ?></p>
        <p><strong>Statut :</strong> <?php echo htmlspecialchars($agent['statut']); ?></p>
        <p><strong>Date d'embauche :</strong> <?php echo date('d/m/Y', strtotime($agent['date_embauche'])); ?></p>
    </div>
</div>

<!-- Sections pour évaluations et sanctions -->
<div class="row">
    <!-- Section Évaluations -->
    <div class="col-md-6">
        <h4>Évaluations</h4>
        <?php if (is_admin()): ?>
        <div class="card card-body mb-3">
             <form action="dossier_agent.php?id=<?php echo $agent_id; ?>" method="POST">
                <div class="mb-3">
                    <label for="auteur_nom" class="form-label">Nom de l'auteur</label>
                    <input type="text" class="form-control" id="auteur_nom" name="auteur_nom" value="<?php echo htmlspecialchars($_SESSION['agent_nom']); ?>" required>
                </div>
                <div class="mb-3">
                    <label for="type" class="form-label">Type d'évaluation</label>
                    <select class="form-select" id="type" name="type">
                        <option value="Positive">Positive</option>
                        <option value="Négative">Négative</option>
                        <option value="Neutre">Neutre</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="commentaire" class="form-label">Commentaire</label>
                    <textarea class="form-control" id="commentaire" name="commentaire" rows="3" required></textarea>
                </div>
                <button type="submit" name="add_evaluation" class="btn btn-sm btn-primary">Ajouter Évaluation</button>
            </form>
        </div>
        <?php endif; ?>
        <div class="table-responsive">
            <table class="table table-striped table-sm">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Type</th>
                        <th>Commentaire</th>
                        <?php if (is_admin()): ?><th>Actions</th><?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result_eval && $result_eval->num_rows > 0): ?>
                        <?php while($eval = $result_eval->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo date('d/m/Y', strtotime($eval['date_creation'])); ?></td>
                                <td><?php echo htmlspecialchars($eval['type']); ?></td>
                                <td><?php echo htmlspecialchars($eval['commentaire']); ?></td>
                                <?php if (is_admin()): ?>
                                <td class="d-flex">
                                    <button type="button" class="btn btn-sm btn-warning me-1" data-bs-toggle="modal" data-bs-target="#editEvaluationModal" 
                                            data-eval-id="<?php echo $eval['id']; ?>"
                                            data-eval-type="<?php echo htmlspecialchars($eval['type']); ?>"
                                            data-eval-comment="<?php echo htmlspecialchars($eval['commentaire']); ?>">
                                        Modif.
                                    </button>
                                    <form action="dossier_agent.php?id=<?php echo $agent_id; ?>" method="POST" onsubmit="return confirm('Voulez-vous vraiment supprimer cette évaluation ?');">
                                        <input type="hidden" name="evaluation_id" value="<?php echo $eval['id']; ?>">
                                        <button type="submit" name="delete_evaluation" class="btn btn-sm btn-danger">X</button>
                                    </form>
                                </td>
                                <?php endif; ?>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="<?php echo is_admin() ? '4' : '3'; ?>" class="text-center">Aucune évaluation enregistrée.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Section Sanctions -->
    <div class="col-md-6">
        <h4>Sanctions</h4>
        <?php if (is_admin()): ?>
        <div class="card card-body mb-3">
            <form action="dossier_agent.php?id=<?php echo $agent_id; ?>" method="POST">
                <div class="mb-3">
                    <label for="auteur_nom_sanction" class="form-label">Nom de l'auteur</label>
                    <input type="text" class="form-control" id="auteur_nom_sanction" name="auteur_nom_sanction" value="<?php echo htmlspecialchars($_SESSION['agent_nom']); ?>" required>
                </div>
                <div class="mb-3">
                    <label for="type_sanction" class="form-label">Type de sanction</label>
                    <input type="text" class="form-control" id="type_sanction" name="type_sanction" required>
                </div>
                <div class="mb-3">
                    <label for="raison_sanction" class="form-label">Raison</label>
                    <textarea class="form-control" id="raison_sanction" name="raison_sanction" rows="3" required></textarea>
                </div>
                <button type="submit" name="add_sanction" class="btn btn-sm btn-danger">Ajouter Sanction</button>
            </form>
        </div>
        <?php endif; ?>
        <div class="table-responsive">
            <table class="table table-striped table-sm">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Type</th>
                        <th>Raison</th>
                        <?php if (is_admin()): ?><th>Actions</th><?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                     <?php if ($result_sanction && $result_sanction->num_rows > 0): ?>
                        <?php while($sanction = $result_sanction->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo date('d/m/Y', strtotime($sanction['date_sanction'])); ?></td>
                                <td><?php echo htmlspecialchars($sanction['type']); ?></td>
                                <td><?php echo htmlspecialchars($sanction['raison']); ?></td>
                                <?php if (is_admin()): ?>
                                <td class="d-flex">
                                     <button type="button" class="btn btn-sm btn-warning me-1" data-bs-toggle="modal" data-bs-target="#editSanctionModal" 
                                            data-sanction-id="<?php echo $sanction['id']; ?>"
                                            data-sanction-type="<?php echo htmlspecialchars($sanction['type']); ?>"
                                            data-sanction-reason="<?php echo htmlspecialchars($sanction['raison']); ?>">
                                        Modif.
                                    </button>
                                    <form action="dossier_agent.php?id=<?php echo $agent_id; ?>" method="POST" class="d-inline" onsubmit="return confirm('Voulez-vous vraiment supprimer cette sanction ?');">
                                        <input type="hidden" name="sanction_id" value="<?php echo $sanction['id']; ?>">
                                        <button type="submit" name="delete_sanction" class="btn btn-sm btn-danger">X</button>
                                    </form>
                                </td>
                                <?php endif; ?>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="<?php echo is_admin() ? '4' : '3'; ?>" class="text-center">Aucune sanction enregistrée.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php if (is_admin()): ?>
<!-- Modal de modification d'évaluation -->
<div class="modal fade" id="editEvaluationModal" tabindex="-1" aria-labelledby="editEvaluationModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="editEvaluationModalLabel">Modifier l'évaluation</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="dossier_agent.php?id=<?php echo $agent_id; ?>" method="POST">
          <div class="modal-body">
                <input type="hidden" name="edit_evaluation_id" id="edit_evaluation_id">
                <div class="mb-3">
                    <label for="edit_type" class="form-label">Type d'évaluation</label>
                    <select class="form-select" id="edit_type" name="edit_type">
                        <option value="Positive">Positive</option>
                        <option value="Négative">Négative</option>
                        <option value="Neutre">Neutre</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="edit_commentaire" class="form-label">Commentaire</label>
                    <textarea class="form-control" id="edit_commentaire" name="edit_commentaire" rows="3" required></textarea>
                </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
            <button type="submit" name="edit_evaluation" class="btn btn-primary">Enregistrer</button>
          </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal de modification de sanction -->
<div class="modal fade" id="editSanctionModal" tabindex="-1" aria-labelledby="editSanctionModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="editSanctionModalLabel">Modifier la sanction</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="dossier_agent.php?id=<?php echo $agent_id; ?>" method="POST">
          <div class="modal-body">
                <input type="hidden" name="edit_sanction_id" id="edit_sanction_id">
                <div class="mb-3">
                    <label for="edit_type_sanction" class="form-label">Type de sanction</label>
                    <input type="text" class="form-control" id="edit_type_sanction" name="edit_type_sanction" required>
                </div>
                <div class="mb-3">
                    <label for="edit_raison_sanction" class="form-label">Raison</label>
                    <textarea class="form-control" id="edit_raison_sanction" name="edit_raison_sanction" rows="3" required></textarea>
                </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
            <button type="submit" name="edit_sanction" class="btn btn-primary">Enregistrer</button>
          </div>
      </form>
    </div>
  </div>
</div>
<?php endif; ?>


<?php
$stmt_eval->close();
$stmt_sanction->close();
$conn->close();
include 'includes/footer.php';
?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Pour la modal d'évaluation
    var editEvaluationModal = document.getElementById('editEvaluationModal');
    if (editEvaluationModal) {
        editEvaluationModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            var evalId = button.getAttribute('data-eval-id');
            var evalType = button.getAttribute('data-eval-type');
            var evalComment = button.getAttribute('data-eval-comment');

            var modalIdInput = editEvaluationModal.querySelector('#edit_evaluation_id');
            var modalTypeSelect = editEvaluationModal.querySelector('#edit_type');
            var modalCommentTextarea = editEvaluationModal.querySelector('#edit_commentaire');

            modalIdInput.value = evalId;
            modalTypeSelect.value = evalType;
            modalCommentTextarea.value = evalComment;
        });
    }

    // Pour la modal de sanction
    var editSanctionModal = document.getElementById('editSanctionModal');
    if (editSanctionModal) {
        editSanctionModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            var sanctionId = button.getAttribute('data-sanction-id');
            var sanctionType = button.getAttribute('data-sanction-type');
            var sanctionReason = button.getAttribute('data-sanction-reason');

            var modalIdInput = editSanctionModal.querySelector('#edit_sanction_id');
            var modalTypeInput = editSanctionModal.querySelector('#edit_type_sanction');
            var modalReasonTextarea = editSanctionModal.querySelector('#edit_raison_sanction');

            modalIdInput.value = sanctionId;
            modalTypeInput.value = sanctionType;
            modalReasonTextarea.value = sanctionReason;
        });
    }
});
</script>