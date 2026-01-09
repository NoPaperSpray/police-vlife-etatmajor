<?php
require_once 'includes/auth_check.php';
require_once 'includes/db.php';

// Seuls les admins peuvent accéder
if (!is_admin()) {
    die('Accès refusé.');
}

$agent_id = $_GET['id'] ?? null;

if (!$agent_id || !is_numeric($agent_id)) {
    header('Location: agents.php');
    exit();
}

// Traitement du formulaire de mise à jour
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_agent'])) {
    $matricule = $_POST['matricule'];
    $nom = $_POST['nom'];
    $grade = $_POST['grade'];
    $role = $_POST['role'];
    $statut = $_POST['statut'];

    if (!empty($matricule) && !empty($nom) && !empty($grade) && !empty($role) && !empty($statut)) {
        $stmt = $conn->prepare("UPDATE agents SET matricule = ?, nom = ?, grade = ?, role = ?, statut = ? WHERE id = ?");
        $stmt->bind_param("sssssi", $matricule, $nom, $grade, $role, $statut, $agent_id);
        if ($stmt->execute()) {
            $_SESSION['toast_message'] = 'Informations de l\'agent mises à jour avec succès.';
            $_SESSION['toast_type'] = 'success';
        } else {
            $_SESSION['toast_message'] = 'Erreur lors de la mise à jour : ' . $conn->error;
            $_SESSION['toast_type'] = 'danger';
        }
        $stmt->close();
    } else {
        $_SESSION['toast_message'] = 'Tous les champs sont obligatoires.';
        $_SESSION['toast_type'] = 'warning';
    }
    header('Location: edit_agent.php?id=' . $agent_id);
    exit();
}

// Récupérer les données actuelles de l'agent
$stmt = $conn->prepare("SELECT * FROM agents WHERE id = ?");
$stmt->bind_param("i", $agent_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows !== 1) {
    header('Location: agents.php');
    exit();
}
$agent = $result->fetch_assoc();
$stmt->close();


$page_title = 'Modifier Agent';
include 'includes/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Modifier le dossier de l\'Agent</h1>
    <a href="agents.php" class="btn btn-sm btn-outline-secondary">Retour à la liste</a>
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

<div class="card">
    <div class="card-body">
        <form action="edit_agent.php?id=<?php echo $agent_id; ?>" method="POST">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="matricule" class="form-label">Matricule</label>
                    <input type="text" class="form-control" id="matricule" name="matricule" value="<?php echo htmlspecialchars($agent['matricule']); ?>" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="nom" class="form-label">Nom Complet</label>
                    <input type="text" class="form-control" id="nom" name="nom" value="<?php echo htmlspecialchars($agent['nom']); ?>" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="grade" class="form-label">Grade</label>
                    <select class="form-select" id="grade" name="grade" required>
                        <option value="Recrue" <?php echo $agent['grade'] === 'Recrue' ? 'selected' : ''; ?>>Recrue</option>
                        <option value="Agent" <?php echo $agent['grade'] === 'Agent' ? 'selected' : ''; ?>>Agent</option>
                        <option value="Officier I" <?php echo $agent['grade'] === 'Officier I' ? 'selected' : ''; ?>>Officier I</option>
                        <option value="Officier II" <?php echo $agent['grade'] === 'Officier II' ? 'selected' : ''; ?>>Officier II</option>
                        <option value="Officier III" <?php echo $agent['grade'] === 'Officier III' ? 'selected' : ''; ?>>Officier III</option>
                        <option value="Officier Sénior" <?php echo $agent['grade'] === 'Officier Sénior' ? 'selected' : ''; ?>>Officier Sénior</option>
                        <option value="Sergeant I" <?php echo $agent['grade'] === 'Sergeant I' ? 'selected' : ''; ?>>Sergeant I</option>
                        <option value="Sergeant II" <?php echo $agent['grade'] === 'Sergeant II' ? 'selected' : ''; ?>>Sergeant II</option>
                        <option value="Sergeant Chief" <?php echo $agent['grade'] === 'Sergeant Chief' ? 'selected' : ''; ?>>Sergeant Chief</option>
                        <option value="Major" <?php echo $agent['grade'] === 'Major' ? 'selected' : ''; ?>>Major</option>
                        <option value="Lieutenant" <?php echo $agent['grade'] === 'Lieutenant' ? 'selected' : ''; ?>>Lieutenant</option>
                        <option value="Capitaine" <?php echo $agent['grade'] === 'Capitaine' ? 'selected' : ''; ?>>Capitaine</option>
                        <option value="Commandant" <?php echo $agent['grade'] === 'Commandant' ? 'selected' : ''; ?>>Commandant</option>
                        <option value="Deputy-Chief" <?php echo $agent['grade'] === 'Deputy-Chief' ? 'selected' : ''; ?>>Deputy-Chief</option>
                        <option value="Chief" <?php echo $agent['grade'] === 'Chief' ? 'selected' : ''; ?>>Chief</option>
                    </select>
                </div>
                 <div class="col-md-6 mb-3">
                    <label for="role" class="form-label">Rôle</label>
                    <select class="form-select" id="role" name="role">
                        <option value="officer" <?php echo $agent['role'] === 'officer' ? 'selected' : ''; ?>>officer</option>
                        <option value="admin" <?php echo $agent['role'] === 'admin' ? 'selected' : ''; ?>>admin</option>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="statut" class="form-label">Statut</label>
                    <select class="form-select" id="statut" name="statut">
                        <option value="Actif" <?php echo $agent['statut'] === 'Actif' ? 'selected' : ''; ?>>Actif</option>
                        <option value="Suspendu" <?php echo $agent['statut'] === 'Suspendu' ? 'selected' : ''; ?>>Suspendu</option>
                        <option value="Révoqué" <?php echo $agent['statut'] === 'Révoqué' ? 'selected' : ''; ?>>Révoqué</option>
                        <option value="Retraité" <?php echo $agent['statut'] === 'Retraité' ? 'selected' : ''; ?>>Retraité</option>
                        <option value="En attente" <?php echo $agent['statut'] === 'En attente' ? 'selected' : ''; ?>>En attente</option>
                    </select>
                </div>
            </div>
            <hr class="my-4">
            <button type="submit" name="edit_agent" class="btn btn-primary">Enregistrer les modifications</button>
        </form>
    </div>
</div>


<?php
$conn->close();
include 'includes/footer.php';
?>
