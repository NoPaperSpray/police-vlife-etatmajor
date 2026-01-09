<?php
require_once 'includes/auth_check.php';
require_once 'includes/db.php';

// Seuls les admins peuvent accéder à cette page
if (!is_admin()) {
    die('Accès refusé.');
}

// Traitement des actions d'approbation / refus
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['approve'])) {
        $agent_id_to_approve = $_POST['agent_id'];
        $stmt = $conn->prepare("UPDATE agents SET statut = 'Actif' WHERE id = ?");
        $stmt->bind_param("i", $agent_id_to_approve);
        if ($stmt->execute()) {
            $_SESSION['toast_message'] = 'Agent approuvé et activé.';
            $_SESSION['toast_type'] = 'success';
        } else {
            $_SESSION['toast_message'] = 'Erreur lors de l\'approbation.';
            $_SESSION['toast_type'] = 'danger';
        }
        $stmt->close();
    }

    if (isset($_POST['deny'])) {
        $agent_id_to_deny = $_POST['agent_id'];
        $stmt = $conn->prepare("DELETE FROM agents WHERE id = ? AND statut = 'En attente'");
        $stmt->bind_param("i", $agent_id_to_deny);
        if ($stmt->execute()) {
            $_SESSION['toast_message'] = 'Demande d\'inscription refusée et supprimée.';
            $_SESSION['toast_type'] = 'warning';
        } else {
            $_SESSION['toast_message'] = 'Erreur lors du refus.';
            $_SESSION['toast_type'] = 'danger';
        }
        $stmt->close();
    }
    // Rediriger pour éviter les resoumissions de formulaire
    header('Location: admin_approvals.php');
    exit();
}

// Récupération des agents en attente
$result = $conn->query("SELECT * FROM agents WHERE statut = 'En attente' ORDER BY date_embauche ASC");


$page_title = 'Approbation des Inscriptions';
include 'includes/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Demandes d\'Inscription en Attente</h1>
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

<div class="mb-3">
    <input type="text" id="approvalSearch" class="form-control" placeholder="Rechercher par matricule, nom ou grade...">
</div>
<div class="table-responsive">
    <table class="table table-striped table-sm" id="approvalsTable">
        <thead>
            <tr>
                <th scope="col">Matricule</th>
                <th scope="col">Nom</th>
                <th scope="col">Grade</th>
                <th scope="col">Date de la demande</th>
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
                        <td><?php echo date('d/m/Y H:i', strtotime($agent['date_embauche'])); ?></td>
                        <td>
                            <form action="admin_approvals.php" method="POST" class="d-inline">
                                <input type="hidden" name="agent_id" value="<?php echo $agent['id']; ?>">
                                <button type="submit" name="approve" class="btn btn-sm btn-success">Approuver</button>
                            </form>
                            <form action="admin_approvals.php" method="POST" class="d-inline">
                                <input type="hidden" name="agent_id" value="<?php echo $agent['id']; ?>">
                                <button type="submit" name="deny" class="btn btn-sm btn-danger">Refuser</button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5" class="text-center">Aucune demande d\'inscription en attente.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>


<?php
$conn->close();
include 'includes/footer.php';
?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const approvalSearchInput = document.getElementById('approvalSearch');
    const approvalsTable = document.getElementById('approvalsTable');

    if (approvalSearchInput && approvalsTable) {
        approvalSearchInput.addEventListener('keyup', function() {
            const searchTerm = approvalSearchInput.value.toLowerCase();
            const rows = approvalsTable.getElementsByTagName('tbody')[0].getElementsByTagName('tr');

            for (let i = 0; i < rows.length; i++) {
                const row = rows[i];
                const cells = row.getElementsByTagName('td');
                let found = false;

                // Skip the "Aucune demande d'inscription en attente" row if it exists
                if (cells.length === 1 && cells[0].colSpan === 5) {
                    row.style.display = ''; 
                    continue; 
                }

                // Check 'Matricule' (index 0), 'Nom' (index 1), and 'Grade' (index 2) columns
                const matriculeCell = cells[0];
                const nameCell = cells[1]; 
                const gradeCell = cells[2];

                if (matriculeCell && matriculeCell.textContent.toLowerCase().indexOf(searchTerm) > -1) {
                    found = true;
                }
                if (nameCell && nameCell.textContent.toLowerCase().indexOf(searchTerm) > -1) {
                    found = true;
                }
                if (gradeCell && gradeCell.textContent.toLowerCase().indexOf(searchTerm) > -1) {
                    found = true;
                }
                
                row.style.display = found ? '' : 'none';
            }
        });
    }
});
</script>