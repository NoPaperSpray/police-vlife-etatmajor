<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Si l'utilisateur n'est pas connecté, le rediriger vers la page de connexion
if (!isset($_SESSION['agent_id'])) {
    header('Location: login.php');
    exit();
}

// Fonction pour vérifier si l'utilisateur a le rôle d'administrateur
function is_admin() {
    return isset($_SESSION['agent_role']) && $_SESSION['agent_role'] === 'admin';
}

// Fonction pour vérifier si l'utilisateur a un rôle privilégié (Major et au-dessus)
function is_privileged() {
    if (is_admin()) {
        return true;
    }
    $privileged_roles = [
        'Major',
        'Lieutenant',
        'Capitaine',
        'Commandant',
        'Deputy-Chief',
        'Chief'
    ];
    return isset($_SESSION['agent_role']) && in_array($_SESSION['agent_role'], $privileged_roles);
}
?>
