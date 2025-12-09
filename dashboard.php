<?php
session_start();

// Redirection si pas connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php?error=' . urlencode('Veuillez vous connecter.'));
    exit();
}

// Valeurs par défaut
$_SESSION['role']           = $_SESSION['role'] ?? 'Joueur';
$_SESSION['solde']          = $_SESSION['solde'] ?? 0;
$_SESSION['niveau_fidelite']= $_SESSION['niveau_fidelite'] ?? 0;
$_SESSION['points_fidelite']= $_SESSION['points_fidelite'] ?? 0;
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de bord - Casino Blackjack</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            color: white;
            text-align: center;
        }
        .container {
            background: rgba(255, 255, 255, 0.05);
            padding: 40px;
            border-radius: 20px;
            width: 90%;
            max-width: 600px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 8px 32px rgba(0,0,0,0.3);
        }
        h1 { color: #ffd700; margin-bottom: 20px; text-shadow: 2px 2px 4px rgba(0,0,0,0.5); }
        p { color: #cfcfcf; margin-bottom: 30px; }
        .btn {
            display: block;
            padding: 15px 30px;
            margin: 15px auto;
            width: 80%;
            max-width: 300px;
            border-radius: 12px;
            background: linear-gradient(135deg, #ffd700 0%, #ffed4e 100%);
            color: #1a1a2e;
            font-weight: bold;
            font-size: 1.1rem;
            text-decoration: none;
            transition: 0.3s;
        }
        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 20px rgba(255,215,0,0.4);
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Bonjour, <?= htmlspecialchars($_SESSION['pseudo']) ?> 🎉</h1>
        <p>Solde : <strong><?= number_format($_SESSION['solde'], 2) ?> €</strong></p>
        <p>Niveau fidélité : <strong><?= $_SESSION['niveau_fidelite'] ?></strong> | Points : <strong><?= $_SESSION['points_fidelite'] ?></strong></p>

<?php if ($_SESSION['role'] === 'Admin'): ?>

    <!-- BOUTONS POUR ADMIN -->
    <a href="gestion_utilisateurs.php" class="btn">Gestion utilisateurs</a>
    <a href="gestion_soldes.php" class="btn">Gestion des soldes</a>
    <a href="gestion_fidelite.php" class="btn">Gestion fidélité</a>
    <a href="statistics.php" class="btn">Statistiques du site</a>
    <a href="logout.php" class="btn">Déconnexion</a>

<?php else: ?>

    <!-- BOUTONS POUR JOUEUR -->
    <a href="account.php" class="btn">Mon compte</a>
    <a href="leaderboard.php" class="btn">Leaderboard</a>
    <a href="blackjack.php" class="btn">Jouer au Blackjack</a>
    <a href="rules.php" class="btn">Règles du jeu</a>
    <a href="logout.php" class="btn">Déconnexion</a>

<?php endif; ?>
    </div>
</body>
</html>
