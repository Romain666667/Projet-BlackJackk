<?php
// test_connexion.php - Fichier pour tester la connexion à la base de données et la requête principale

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Test de connexion à la base de données</h2>";

// Paramètres de connexion
$host = 'localhost'; // Ou essayez '127.0.0.1'
$dbname = 'Casino'; // Changez si nécessaire
$username = 'root';
$password = 'NouveauMotDePasse'; // Mettez votre mot de passe si nécessaire

echo "<p><strong>Paramètres testés :</strong></p>";
echo "<ul>";
echo "<li>Hôte : $host</li>";
echo "<li>Base de données : $dbname</li>";
echo "<li>Utilisateur : $username</li>";
echo "<li>Mot de passe : " . (empty($password) ? '(vide)' : '****') . "</li>";
echo "</ul>";

try {
    $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    
    $pdo = new PDO($dsn, $username, $password, $options);
    
    echo "<p style='color: green; font-weight: bold;'>✅ Connexion réussie à la base de données !</p>";
    
    // Test d'une requête simple
    $stmt = $pdo->query("SELECT COUNT(*) as nb FROM Utilisateur");
    $result = $stmt->fetch();
    echo "<p>Nombre d'utilisateurs dans la base : <strong>" . $result['nb'] . "</strong></p>";
    
    // Lister les tables
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "<p><strong>Tables disponibles :</strong></p>";
    echo "<ul>";
    foreach ($tables as $table) {
        echo "<li>$table</li>";
    }
    echo "</ul>";
    
    // ===== TEST DE LA REQUETE PRINCIPALE DU LOGIN =====
    echo "<h3>Test de la requête principale du login</h3>";

    $identifier = 'alice@email.com'; // Remplacez par un email ou pseudo existant pour tester
    $sql = "SELECT 
            u.Id_utilisateur,
            u.Pseudo,
            u.Email,
            u.Mot_de_passe_hash,
            u.Est_actif,
            u.Est_banni,
            r.Nom_role,
            s.Montant_solde,
            s.Limite_mise,
            f.Niveau_fidelite,
            f.Point_totaux
        FROM Utilisateur u
        JOIN Role r ON u.Id_role = r.Id_role
        LEFT JOIN Solde s ON u.Id_utilisateur = s.Id_utilisateur
        LEFT JOIN Fidelite f ON u.Id_fidelite = f.Id_fidelite
        WHERE (u.Email = :identifier_email OR u.Pseudo = :identifier_pseudo)
        LIMIT 1";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
    'identifier_email' => $identifier,
    'identifier_pseudo' => $identifier
    ]);
    $user = $stmt->fetch();

    if ($user) {
        echo "<p style='color: green;'>✅ Requête exécutée avec succès !</p>";
        echo "<pre>";
        print_r($user);
        echo "</pre>";
    } else {
        echo "<p style='color: orange;'>⚠️ Aucun utilisateur trouvé avec l'identifiant '$identifier'</p>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red; font-weight: bold;'>❌ Erreur de connexion ou de requête</p>";
    echo "<p><strong>Message d'erreur :</strong> " . $e->getMessage() . "</p>";
    echo "<p><strong>Code d'erreur :</strong> " . $e->getCode() . "</p>";
}
?>

<style>
    body {
        font-family: Arial, sans-serif;
        max-width: 800px;
        margin: 50px auto;
        padding: 20px;
        background: #f5f5f5;
    }
    h2, h3 {
        color: #333;
        border-bottom: 2px solid #ffd700;
        padding-bottom: 10px;
    }
    ul, ol {
        line-height: 1.8;
    }
    p {
        margin: 10px 0;
    }
    pre {
        background: #eee;
        padding: 10px;
        border-radius: 5px;
        overflow-x: auto;
    }
</style>
