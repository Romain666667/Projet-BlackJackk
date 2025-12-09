<?php
// config.php - Fichier de configuration de la base de données

// Paramètres de connexion
define('DB_HOST', 'localhost');
define('DB_NAME', 'Casino'); // Remplacez par le nom de votre base
define('DB_USER', 'root'); // Remplacez par votre utilisateur MySQL
define('DB_PASS', 'NouveauMotDePasse'); // Remplacez par votre mot de passe MySQL
define('DB_CHARSET', 'utf8mb4');

// Fonction de connexion PDO
function getConnection() {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        
        return $pdo;
        
    } catch (PDOException $e) {
        // En production, ne jamais afficher les erreurs détaillées
        error_log("Erreur de connexion : " . $e->getMessage());
        die("Erreur de connexion à la base de données. Veuillez réessayer plus tard.");
    }
}

// Test de connexion (à supprimer en production)
function testConnection() {
    try {
        $pdo = getConnection();
        echo "✅ Connexion réussie à la base de données !";
        return true;
    } catch (Exception $e) {
        echo "❌ Échec de la connexion : " . $e->getMessage();
        return false;
    }
}
?>