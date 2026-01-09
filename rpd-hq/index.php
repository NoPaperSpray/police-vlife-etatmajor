<?php
require_once 'includes/auth_check.php';
require_once 'includes/db.php'; // Inclure la connexion à la BDD

$page_title = 'Tableau de Bord';
include 'includes/header.php';

$pending_approvals_count = 0;
$recent_activities = [];

if (is_admin()) {
    // Compter les demandes en attente
    $stmt = $conn->prepare("SELECT COUNT(*) FROM agents WHERE statut = 'En attente'");
    $stmt->execute();
    $stmt->bind_result($pending_approvals_count);
    $stmt->fetch();
    $stmt->close();
}

// Récupérer les 3 dernières évaluations
$stmt_eval = $conn->prepare("SELECT a.nom as agent_nom, e.commentaire, e.date_creation FROM evaluations e JOIN agents a ON e.agent_id = a.id ORDER BY e.date_creation DESC LIMIT 3");
$stmt_eval->execute();
$result_eval = $stmt_eval->get_result();
while ($row = $result_eval->fetch_assoc()) {
    $recent_activities[] = ['type' => 'Évaluation', 'description' => 'Évaluation pour ' . $row['agent_nom'] . ': ' . $row['commentaire'], 'date' => $row['date_creation']];
}
$stmt_eval->close();

// Récupérer les 3 dernières sanctions
$stmt_sanction = $conn->prepare("SELECT a.nom as agent_nom, s.raison, s.date_sanction FROM sanctions s JOIN agents a ON s.agent_id = a.id ORDER BY s.date_sanction DESC LIMIT 3");
$stmt_sanction->execute();
$result_sanction = $stmt_sanction->get_result();
while ($row = $result_sanction->fetch_assoc()) {
    $recent_activities[] = ['type' => 'Sanction', 'description' => 'Sanction pour ' . $row['agent_nom'] . ': ' . $row['raison'], 'date' => $row['date_sanction']];
}
$stmt_sanction->close();

// Trier les activités par date
usort($recent_activities, function($a, $b) {
    return strtotime($b['date']) - strtotime($a['date']);
});

// Limiter aux 5 dernières activités
$recent_activities = array_slice($recent_activities, 0, 5);

$conn->close();
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Tableau de Bord du RPD HQ</h1>
</div>

<p>Bienvenue sur le système de gestion de l'État-Major du Rockford Police Department. Utilisez la barre de navigation ci-dessus pour accéder aux différentes fonctionnalités.</p>

<div class="row mt-4 mb-4">
    <?php if (is_admin() && $pending_approvals_count > 0): ?>
    <div class="col-md-6 mb-3">
        <div class="card text-white bg-warning h-100">
            <div class="card-body d-flex flex-column justify-content-between">
                <h5 class="card-title"><i class="bi bi-person-fill-exclamation me-2"></i> Demandes en Attente</h5>
                <p class="card-text fs-2"><?php echo $pending_approvals_count; ?></p>
                <a href="admin_approvals.php" class="btn btn-dark mt-3">Gérer les Approbations</a>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="col-md-6 mb-3">
        <div class="card h-100">
            <div class="card-body">
                <h5 class="card-title"><i class="bi bi-activity me-2"></i> Activité Récente</h5>
                <?php if (!empty($recent_activities)): ?>
                    <div class="timeline mt-3">
                        <?php foreach ($recent_activities as $activity): ?>
                            <div class="timeline-item">
                                <div class="timeline-icon">
                                    <?php if ($activity['type'] === 'Évaluation'): ?>
                                        <i class="bi bi-patch-check-fill text-success"></i>
                                    <?php else: ?>
                                        <i class="bi bi-exclamation-triangle-fill text-danger"></i>
                                    <?php endif; ?>
                                </div>
                                <div class="timeline-content">
                                    <h6 class="mb-1"><?php echo htmlspecialchars($activity['type']); ?></h6>
                                    <p class="mb-1"><?php echo htmlspecialchars($activity['description']); ?></p>
                                    <small class="text-muted"><?php echo date('d/m H:i', strtotime($activity['date'])); ?></small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="card-text text-center">Aucune activité récente à afficher.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <?php if (is_privileged()): ?>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Agents</h5>
                <p class="card-text">Gérez les dossiers des agents, ajoutez de nouvelles recrues, et consultez les informations détaillées.</p>
                <a href="agents.php" class="btn btn-primary">Gérer les Agents</a>
            </div>
        </div>
    </div>
    <?php endif; ?>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Dossiers Individuels</h5>
                <p class="card-text">Consultez les dossiers pour y ajouter des évaluations de performance et des sanctions disciplinaires.</p>
                <a href="agents.php" class="btn btn-secondary">Voir les Dossiers</a>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Promotions</h5>
                <p class="card-text">Identifiez les agents éligibles à une promotion basée sur leurs performances et leur dossier.</p>
                <a href="promotions.php" class="btn btn-info">Gérer les Promotions</a>
            </div>
        </div>
    </div>
</div>

<?php
include 'includes/footer.php';
?>
