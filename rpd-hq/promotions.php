<?php
require_once 'includes/auth_check.php';
require_once 'includes/db.php';

// --- Définition du barème pour le score de promotion ---
// Ces valeurs peuvent être ajustées pour équilibrer le système
define('SCORE_EVAL_POSITIVE', 5);
define('SCORE_EVAL_NEGATIVE', -10);
define('SCORE_SANCTION', -25);
define('SCORE_ANCIENNETE_PAR_MOIS', 1); // Points par mois d'ancienneté

function calculate_promotion_score($conn, $agent) {
    $score = 0;
    $breakdown = [];

    // 1. Score des évaluations
    $stmt_eval = $conn->prepare("SELECT type, COUNT(*) as count FROM evaluations WHERE agent_id = ? GROUP BY type");
    $stmt_eval->bind_param("i", $agent['id']);
    $stmt_eval->execute();
    $result_eval = $stmt_eval->get_result();
    
    $eval_scores = ['pos' => 0, 'neg' => 0];
    while ($row = $result_eval->fetch_assoc()) {
        if ($row['type'] === 'Positive') {
            $eval_scores['pos'] = $row['count'] * SCORE_EVAL_POSITIVE;
        } elseif ($row['type'] === 'Négative') {
            $eval_scores['neg'] = $row['count'] * SCORE_EVAL_NEGATIVE;
        }
    }
    $score += $eval_scores['pos'] + $eval_scores['neg'];
    $breakdown['evaluations'] = $eval_scores['pos'] + $eval_scores['neg'];
    $stmt_eval->close();

    // 2. Score des sanctions
    $stmt_sanction = $conn->prepare("SELECT COUNT(*) as count FROM sanctions WHERE agent_id = ?");
    $stmt_sanction->bind_param("i", $agent['id']);
    $stmt_sanction->execute();
    $sanction_count = $stmt_sanction->get_result()->fetch_assoc()['count'];
    $sanction_score = $sanction_count * SCORE_SANCTION;
    $score += $sanction_score;
    $breakdown['sanctions'] = $sanction_score;
    $stmt_sanction->close();
    
    // 3. Score d'ancienneté
    $date_embauche = new DateTime($agent['date_embauche']);
    $aujourdhui = new DateTime();
    $interval = $date_embauche->diff($aujourdhui);
    $mois_anciennete = ($interval->y * 12) + $interval->m;
    $anciennete_score = $mois_anciennete * SCORE_ANCIENNETE_PAR_MOIS;
    $score += $anciennete_score;
    $breakdown['anciennete'] = $anciennete_score;

    return ['score' => $score, 'breakdown' => $breakdown];
}

// Récupération de tous les agents actifs
$agents_actifs = [];
$result = $conn->query("SELECT * FROM agents WHERE statut = 'Actif' ORDER BY nom ASC");
if ($result) {
    while($agent = $result->fetch_assoc()) {
        $score_data = calculate_promotion_score($conn, $agent);
        $agents_actifs[] = array_merge($agent, [
            'score' => $score_data['score'],
            'breakdown' => $score_data['breakdown']
        ]);
    }
}

// Trier les agents par score
usort($agents_actifs, function($a, $b) {
    return $b['score'] <=> $a['score'];
});


$page_title = 'Aide à la Promotion';
include 'includes/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Aide à la Promotion</h1>
</div>

<div class="alert alert-info">
    <h4 class="alert-heading">Comment ça marche ?</h4>
    <p>Cette page calcule un "score de promotion" pour chaque agent actif. Plus le score est élevé, plus l'agent est un candidat potentiel pour une promotion. Le classement est purement indicatif et doit servir d'outil pour le commandement.</p>
    <hr>
    <p class="mb-0">Le barème actuel est : <strong>Évaluation Positive:</strong> <?php echo SCORE_EVAL_POSITIVE; ?> pts, <strong>Évaluation Négative:</strong> <?php echo SCORE_EVAL_NEGATIVE; ?> pts, <strong>Sanction:</strong> <?php echo SCORE_SANCTION; ?> pts, <strong>Ancienneté:</strong> <?php echo SCORE_ANCIENNETE_PAR_MOIS; ?> pt/mois.</p>
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

<h5 class="mt-4">Classement des Agents pour la Promotion</h5>
<div class="mb-3">
    <input type="text" id="promotionSearch" class="form-control" placeholder="Rechercher par nom ou grade...">
</div>

<div class="row" id="promotionsContainer">
    <?php if (!empty($agents_actifs)): ?>
        <?php 
            $scores = array_column($agents_actifs, 'score');
            $max_score = !empty($scores) ? max($scores) : 0;
            $min_score = !empty($scores) ? min($scores) : 0;
            $range = ($max_score - $min_score);
        ?>
        <?php foreach($agents_actifs as $index => $agent): ?>
            <div class="col-lg-6 col-xl-4 mb-4 agent-card-wrapper">
                <div class="card promo-card-v3 h-100">
                    <div class="card-body">
                        <div class="rank-badge">#<?php echo $index + 1; ?></div>
                        <div class="agent-info text-center">
                            <a href="dossier_agent.php?id=<?php echo $agent['id']; ?>" class="stretched-link">
                                <h4 class="mb-0"><?php echo htmlspecialchars($agent['nom']); ?></h4>
                            </a>
                            <p class="text-muted mb-3"><?php echo htmlspecialchars($agent['grade']); ?></p>
                        </div>

                        <div class="score-display text-center my-4">
                            <small class="text-muted">SCORE</small>
                            <div class="display-3 fw-bold"><?php echo $agent['score']; ?></div>
                            <?php 
                                $normalized_score = $range > 0 ? (($agent['score'] - $min_score) / $range) * 100 : 50;
                            ?>
                            <div class="progress mx-auto" style="height: 6px; max-width: 150px;">
                                <div class="progress-bar rounded-pill" role="progressbar" style="width: <?php echo $normalized_score; ?>%;" aria-valuenow="<?php echo $normalized_score; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                        </div>

                        <div class="breakdown-section d-flex justify-content-around text-center border-top pt-3">
                            <div class="breakdown-item">
                                <i class="bi bi-calendar-check text-info"></i>
                                <div class="fw-bold fs-5">+<?php echo $agent['breakdown']['anciennete']; ?></div>
                                <small class="text-muted">Ancienneté</small>
                            </div>
                             <div class="breakdown-item">
                                <i class="bi bi-clipboard-data <?php echo $agent['breakdown']['evaluations'] >= 0 ? 'text-success' : 'text-danger'; ?>"></i>
                                <div class="fw-bold fs-5 <?php echo $agent['breakdown']['evaluations'] >= 0 ? 'text-success' : 'text-danger'; ?>">
                                    <?php echo $agent['breakdown']['evaluations'] >= 0 ? '+' : ''; ?><?php echo $agent['breakdown']['evaluations']; ?>
                                </div>
                                <small class="text-muted">Évaluations</small>
                            </div>
                            <div class="breakdown-item">
                                <i class="bi bi-shield-slash text-danger"></i>
                                <div class="fw-bold fs-5 text-danger"><?php echo $agent['breakdown']['sanctions']; ?></div>
                                <small class="text-muted">Sanctions</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="col">
            <p class="text-center">Aucun agent actif à classer.</p>
        </div>
    <?php endif; ?>
</div>


<?php
$conn->close();
include 'includes/footer.php';
?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const promotionSearchInput = document.getElementById('promotionSearch');
    const promotionsContainer = document.getElementById('promotionsContainer');

    if (promotionSearchInput && promotionsContainer) {
        promotionSearchInput.addEventListener('keyup', function() {
            const searchTerm = promotionSearchInput.value.toLowerCase();
            const cards = promotionsContainer.getElementsByClassName('agent-card-wrapper');

            for (let i = 0; i < cards.length; i++) {
                const card = cards[i];
                const cardTitle = card.querySelector('.card-title a');
                const cardGrade = card.querySelector('.text-muted');
                let found = false;

                if (cardTitle && cardTitle.textContent.toLowerCase().indexOf(searchTerm) > -1) {
                    found = true;
                }
                if (cardGrade && cardGrade.textContent.toLowerCase().indexOf(searchTerm) > -1) {
                    found = true;
                }
                
                card.style.display = found ? '' : 'none';
            }
        });
    }
});
</script>
