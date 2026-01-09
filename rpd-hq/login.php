<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'includes/db.php';

// Si déjà connecté, redirection vers l'accueil
if (isset($_SESSION['agent_id'])) {
    header('Location: index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $matricule = $_POST['matricule'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($matricule) || empty($password)) {
        $_SESSION['toast_message'] = 'Matricule et mot de passe sont requis.';
        $_SESSION['toast_type'] = 'warning';
    } else {
        $stmt = $conn->prepare("SELECT * FROM agents WHERE matricule = ?");
        $stmt->bind_param("s", $matricule);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $agent = $result->fetch_assoc();

            // Vérifier le mot de passe
            if ($agent['password'] !== null && password_verify($password, $agent['password'])) {
                // Vérifier le statut du compte
                if ($agent['statut'] === 'Actif') {
                    $_SESSION['agent_id'] = $agent['id'];
                    $_SESSION['agent_nom'] = $agent['nom'];
                    $_SESSION['agent_role'] = $agent['role'];
                    $_SESSION['agent_grade'] = $agent['grade'];
                    header('Location: index.php');
                    exit();
                } elseif ($agent['statut'] === 'En attente') {
                    $_SESSION['toast_message'] = 'Votre compte est en attente d\'approbation par un administrateur.';
                    $_SESSION['toast_type'] = 'info';
                } else {
                    $_SESSION['toast_message'] = 'Votre compte est inactif ou suspendu. Contactez un administrateur.';
                    $_SESSION['toast_type'] = 'danger';
                }
            } elseif ($agent['password'] === null) {
                $_SESSION['toast_message'] = 'Aucun mot de passe défini pour ce compte. Contactez un administrateur.';
                $_SESSION['toast_type'] = 'danger';
            } else {
                $_SESSION['toast_message'] = 'Matricule ou mot de passe incorrect.';
                $_SESSION['toast_type'] = 'danger';
            }
        } else {
            $_SESSION['toast_message'] = 'Matricule ou mot de passe incorrect.';
            $_SESSION['toast_type'] = 'danger';
        }
        $stmt->close();
    }
    // Redirection après POST pour éviter la soumission multiple
    header('Location: login.php');
    exit();
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="fr" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RPD HQ - Connexion</title>
    <!-- Google Fonts - Roboto -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <style>
      .form-signin {
        max-width: 330px;
        padding: 1rem;
      }
    </style>
</head>
<body class="d-flex align-items-center py-4 bg-body-tertiary">
    <!-- Toast Container -->
    <div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 1100;">
        <!-- Toasts will be appended here -->
    </div>
    <main class="form-signin w-100 m-auto text-center">
        <form action="login.php" method="POST">
            <h1 class="h3 mb-3 fw-normal">RPD HQ</h1>
            <h2 class="h5 mb-3 fw-normal">Veuillez vous connecter</h2>
            
            <div class="form-floating">
                <input type="text" class="form-control" id="matricule" name="matricule" placeholder="Matricule" required>
                <label for="matricule">Matricule</label>
            </div>
            <div class="form-floating mt-2">
                <input type="password" class="form-control" id="password" name="password" placeholder="Mot de passe" required>
                <label for="password">Mot de passe</label>
            </div>

            <button class="btn btn-primary w-100 py-2 mt-3" type="submit">Se connecter</button>
        </form>
        <div class="mt-3">
            <a href="register.php">Demander un accès</a>
        </div>
    </main>
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
</body>
</html>
