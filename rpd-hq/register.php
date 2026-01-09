<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start(); // Ajoutez ceci au début de register.php
}
require_once 'includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $matricule = $_POST['matricule'] ?? '';
    $nom = $_POST['nom'] ?? '';
    $grade = $_POST['grade'] ?? '';
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';

    // Validation
    if (empty($matricule) || empty($nom) || empty($grade) || empty($password)) {
        $_SESSION['toast_message'] = 'Tous les champs sont obligatoires.';
        $_SESSION['toast_type'] = 'warning';
    } elseif ($password !== $password_confirm) {
        $_SESSION['toast_message'] = 'Les mots de passe ne correspondent pas.';
        $_SESSION['toast_type'] = 'danger';
    } else {
        // Vérifier si le matricule existe déjà
        $stmt = $conn->prepare("SELECT id FROM agents WHERE matricule = ?");
        $stmt->bind_param("s", $matricule);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $_SESSION['toast_message'] = 'Ce matricule est déjà utilisé.';
            $_SESSION['toast_type'] = 'danger';
        } else {
            // Hasher le mot de passe et insérer l'utilisateur
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt_insert = $conn->prepare("INSERT INTO agents (matricule, nom, grade, password, statut) VALUES (?, ?, ?, ?, 'En attente')");
            $stmt_insert->bind_param("ssss", $matricule, $nom, $grade, $password_hash);

            if ($stmt_insert->execute()) {
                $_SESSION['toast_message'] = 'Votre demande d\'inscription a été soumise. Elle est en attente d\'approbation par un administrateur.';
                $_SESSION['toast_type'] = 'info';
            } else {
                $_SESSION['toast_message'] = 'Erreur lors de l\'inscription : ' . $conn->error;
                $_SESSION['toast_type'] = 'danger';
            }
            $stmt_insert->close();
        }
        $stmt->close();
    }
    header('Location: register.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="fr" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RPD HQ - Inscription</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
</head>
<body class="">
    <!-- Toast Container -->
    <div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 1100;">
        <!-- Toasts will be appended here -->
    </div>
    <div class="container">
        <main>
            <div class="py-5 text-center">
                <h1 class="display-5 fw-bold text-primary">RPD HQ</h1>
                <p class="lead">Demande d'accès au panel de gestion</p>
            </div>

            <div class="row g-5">
                <div class="col-md-7 col-lg-8 mx-auto">
                    <h4 class="mb-3">Formulaire d'inscription</h4>
                    
                    <form action="register.php" method="POST">
                        <div class="row g-3">
                            <div class="col-12">
                                <label for="nom" class="form-label">Nom & Prénom RP</label>
                                <input type="text" class="form-control" id="nom" name="nom" required>
                            </div>

                            <div class="col-sm-6">
                                <label for="matricule" class="form-label">Matricule (sera votre login)</label>
                                <input type="text" class="form-control" id="matricule" name="matricule" placeholder="Ex: JDOE-123" required>
                            </div>

                            <div class="col-sm-6">
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
                            
                            <div class="col-sm-6">
                                <label for="password" class="form-label">Mot de passe</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>

                            <div class="col-sm-6">
                                <label for="password_confirm" class="form-label">Confirmer le mot de passe</label>
                                <input type="password" class="form-control" id="password_confirm" name="password_confirm" required>
                            </div>
                        </div>

                        <hr class="my-4">

                        <button class="w-100 btn btn-primary btn-lg" type="submit">Soumettre la demande</button>
                    </form>
                    <div class="text-center mt-3">
                        <a href="login.php">Déjà un compte ? Se connecter</a>
                    </div>
                </div>
            </div>
        </main>
    </div>
<script src="js/bootstrap.bundle.min.js"></script>
<script>
    // JavaScript for Toast messages
    document.addEventListener('DOMContentLoaded', function() {
        const toastEl = document.querySelector('.toast');
        if (toastEl) {
            const toast = new bootstrap.Toast(toastEl);
            toast.show();
        }
    });
</script>
<?php $conn->close(); ?>
</body>
</html>
