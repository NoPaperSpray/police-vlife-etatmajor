<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "rpd_hq";

// Connexion pour la création de la base de données
$conn_init = new mysqli($servername, $username, $password);
if ($conn_init->connect_error) {
    die("Connection failed: " . $conn_init->connect_error);
}

// Créer la base de données si elle n'existe pas
$conn_init->query("CREATE DATABASE IF NOT EXISTS $dbname");
$conn_init->close();

// Connexion à la base de données spécifique
$conn = new mysqli($servername, $username, $password, $dbname);

// Vérifier la connexion
if ($conn->connect_error) {
    die("Connection to database failed: " . $conn->connect_error);
}

// Définir le charset en UTF-8
$conn->set_charset("utf8mb4");
?>
