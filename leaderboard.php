<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php?error=' . urlencode('Veuillez vous connecter.'));
    exit();
}

$pdo = getConnection();
$stmt = $pdo->query("SELECT u.Pseudo, l.Nb_victoire, l.Nb_defaite
                     FROM Leaderboard l
                     JOIN Utilisateur u ON l.Id_utilisateur = u.Id_utilisateur
                     ORDER BY l.Nb_victoire DESC
                     LIMIT 10");
$leaders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leaderboard - Casino Blackjack</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #1a1a2e; color: white; text-align: center; padding: 50px; }
        .container { background: rgba(255,255,255,0.05); padding: 30px; border-radius: 20px; max-width: 500px; margin: auto; }
        h1 { color: #ffd700; }
        table { width: 100%; margin-top: 20px; border-collapse: collapse; }
        th, td { padding: 10px; border-bottom: 1px solid rgba(255,255,255,0.2); }
        th { color: #ffd700; }
        tr:hover { background: rgba(255,255,255,0.05); }
        .btn { display: inline-block; margin-top: 20px; padding: 12px 25px; border-radius: 12px; background: #ffd700; color: #1a1a2e; font-weight: bold; text-decoration: none; }
        .btn:hover { opacity: 0.9; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Leaderboard</h1>
        <table>
            <tr>
                <th>Pseudo</th>
                <th>Victoires</th>
                <th>Défaites</th>
            </tr>
            <?php foreach ($leaders as $leader): ?>
                <tr>
                    <td><?= htmlspecialchars($leader['Pseudo']) ?></td>
                    <td><?= $leader['Nb_victoire'] ?></td>
                    <td><?= $leader['Nb_defaite'] ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
        <a href="dashboard.php" class="btn">Retour au dashboard</a>
    </div>
</body>
</html>
