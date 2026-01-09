<?php
require_once 'includes/auth_check.php';

// Contrôle d'accès : Seuls les utilisateurs privilégiés peuvent voir la liste des agents
if (!is_privileged()) {
    header('Location: dossier_agent.php?id=' . $_SESSION['agent_id']);
    exit();
}

require_once 'includes/db.php';

if (is_admin()) {
    // --- Traitement des Actions POST pour les admins ---
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // AJOUT D'AGENT
        if (isset($_POST['add_agent'])) {
            $matricule = $_POST['matricule'];
            $nom = $_POST['nom'];
            $grade = $_POST['grade'];
            $statut = 'Actif'; 

            if (!empty($matricule) && !empty($nom) && !empty($grade)) {
                $stmt = $conn->prepare("INSERT INTO agents (matricule, nom, grade, statut) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("ssss", $matricule, $nom, $grade, $statut);
                if ($stmt->execute()) {
                    $_SESSION['toast_message'] = 'Agent ajouté avec succès.';
                    $_SESSION['toast_type'] = 'success';
                } else {
                    $_SESSION['toast_message'] = 'Erreur lors de l\'ajout de l\'agent : ' . $conn->error;
                    $_SESSION['toast_type'] = 'danger';
                }
                $stmt->close();
            } else {
                $_SESSION['toast_message'] = 'Matricule, Nom et Grade sont obligatoires.';
                $_SESSION['toast_type'] = 'warning';
            }
        }

        // SUPPRESSION D'AGENT
        if (isset($_POST['delete_agent'])) {
            $agent_id_to_delete = $_POST['agent_id_delete'];
            // On ne peut pas supprimer son propre compte
            if ($agent_id_to_delete == $_SESSION['agent_id']) {
                $_SESSION['toast_message'] = 'Vous ne pouvez pas supprimer votre propre compte.';
                $_SESSION['toast_type'] = 'danger';
            } else {
                $stmt = $conn->prepare("DELETE FROM agents WHERE id = ?");
                $stmt->bind_param("i", $agent_id_to_delete);
                if ($stmt->execute()) {
                    $_SESSION['toast_message'] = 'Agent supprimé avec succès.';
                    $_SESSION['toast_type'] = 'success';
                } else {
                    $_SESSION['toast_message'] = 'Erreur lors de la suppression de l\'agent.';
                    $_SESSION['toast_type'] = 'danger';
                }
                $stmt->close();
            }
        }
    }
}

// Récupération des agents
$result = $conn->query("SELECT * FROM agents WHERE statut != 'En attente' ORDER BY nom ASC");

$page_title = 'Gestion des Agents';
include 'includes/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Gestion des Agents</h1>
    <?php if (is_admin()): ?>
    <div class="btn-toolbar mb-2 mb-md-0">
        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addAgentModal">
            <i class="bi bi-person-plus-fill me-2"></i> Ajouter un Agent (Admin)
        </button>
    </div>
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

<?php if (is_admin()): ?>
<!-- Modal pour Ajouter un Agent -->
<div class="modal fade" id="addAgentModal" tabindex="-1" aria-labelledby="addAgentModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="addAgentModalLabel">Nouveau Dossier Agent</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="agents.php" method="POST">
          <div class="modal-body">
            <p class="text-muted small">L'ajout manuel par un administrateur crée un compte 'Actif' sans mot de passe. L'agent devra utiliser la fonction "mot de passe oublié" (à créer) ou vous devrez en définir un pour lui.</p>
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label for="matricule" class="form-label">Matricule</label>
                    <input type="text" class="form-control" id="matricule" name="matricule" required>
                </div>
                <div class="col-md-4 mb-3">
                    <label for="nom" class="form-label">Nom Complet</label>
                    <input type="text" class="form-control" id="nom" name="nom" required>
                </div>
                <div class="col-md-4 mb-3">
                    <label for="grade" class="form-label">Grade</label>
                    <select class="form-select" id="grade" name="grade" required>
                        <option value="Recrue">Recrue</option>
                        <option value="Agent">Agent</option>
                        <option value="Officier I">Officier I</option>
                        <option value="Officier II">Officier II</option>
                        <option value="Officier III">Officier III</option>
                        <option value="Officier Sénior">Officier Sénior</option>
                        <option value="Sergeant I">Sergeant I</option>
                        <option value="Sergeant II">Sergeant II</option>
                        <option value="Sergeant Chief">Sergeant Chief</option>
                        <option value="Major">Major</option>
                        <option value="Lieutenant">Lieutenant</option>
                        <option value="Capitaine">Capitaine</option>
                        <option value="Commandant">Commandant</option>
                        <option value="Deputy-Chief">Deputy-Chief</option>
                        <option value="Chief">Chief</option>
                    </select>
                </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
            <button type="submit" name="add_agent" class="btn btn-primary">Enregistrer l'agent</button>
          </div>
      </form>
    </div>
  </div>
</div>
<?php endif; ?>

<h5 class="mt-4">Liste des Agents</h5>
<div class="mb-3">
    <input type="text" id="agentSearch" class="form-control" placeholder="Rechercher par matricule, nom ou grade...">
</div>
<div class="table-responsive">
    <table class="table table-striped table-sm" id="agentsTable">
        <thead>
            <tr>
                <th scope="col">Matricule</th>
                <th scope="col">Nom</th>
                <th scope="col">Grade</th>
                <th scope="col">Statut</th>
                <th scope="col">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($result && $result->num_rows > 0): ?>
                <?php while($agent = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($agent['matricule']); ?></td>
                        <td><?php echo htmlspecialchars($agent['nom']); ?></td>
                        <td><?php echo htmlspecialchars($agent['grade']); ?></td>
                        <td><?php echo htmlspecialchars($agent['statut']); ?></td>
                        <td>
                            <a href="dossier_agent.php?id=<?php echo $agent['id']; ?>" class="btn btn-sm btn-info">Dossier</a>
                            <?php if (is_admin()): ?>
                                <a href="edit_agent.php?id=<?php echo $agent['id']; ?>" class="btn btn-sm btn-warning">Modifier</a>
                                <button type="button" class="btn btn-sm btn-danger delete-agent-btn" data-bs-toggle="modal" data-bs-target="#deleteAgentModal" data-agent-id="<?php echo $agent['id']; ?>">
                                    Supprimer
                                </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5" class="text-center">Aucun agent actif ou retraité enregistré.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Modal de Confirmation de Suppression -->
<div class="modal fade" id="deleteAgentModal" tabindex="-1" aria-labelledby="deleteAgentModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="deleteAgentModalLabel">Confirmation de Suppression</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        Êtes-vous sûr de vouloir supprimer cet agent ? Cette action est irréversible et supprimera également toutes les évaluations et sanctions associées.
      </div>
      <div class="modal-footer">
        <form action="agents.php" method="POST">
            <input type="hidden" name="agent_id_delete" id="agent_id_delete">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
            <button type="submit" name="delete_agent" class="btn btn-danger">Supprimer Définitivement</button>
        </form>
      </div>
    </div>
  </div>
</div>

<?php
$conn->close();
include 'includes/footer.php';
?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Logic for delete modal
    var deleteModal = document.getElementById('deleteAgentModal');
    if (deleteModal) {
        deleteModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            var agentId = button.getAttribute('data-agent-id');
            var modalInput = deleteModal.querySelector('#agent_id_delete');
            modalInput.value = agentId;
        });
    }

    // Logic for table search
    const agentSearchInput = document.getElementById('agentSearch');
    const agentsTable = document.getElementById('agentsTable');
    if (agentSearchInput && agentsTable) {
        agentSearchInput.addEventListener('keyup', function() {
            const searchTerm = agentSearchInput.value.toLowerCase();
            const rows = agentsTable.getElementsByTagName('tbody')[0].getElementsByTagName('tr');

            for (let i = 0; i < rows.length; i++) {
                const row = rows[i];
                const cells = row.getElementsByTagName('td');
                let found = false;

                // Skip the "Aucun agent enregistré" row if it exists
                if (cells.length === 1 && cells[0].colSpan === 5) {
                    row.style.display = ''; // Always show empty state if no agents, or hide if agents exist
                    continue; 
                }

                for (let j = 0; j < cells.length - 1; j++) { // Exclude 'Actions' column
                    const cellText = cells[j].textContent || cells[j].innerText;
                    if (cellText.toLowerCase().indexOf(searchTerm) > -1) {
                        found = true;
                        break;
                    }
                }
                row.style.display = found ? '' : 'none';
            }
        });
    }
});
</script>
