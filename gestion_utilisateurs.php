<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php?error=' . urlencode('Veuillez vous connecter.'));
    exit();
}

$pdo = getConnection();

// Traiter l'action de bannissement/débannissement
if (isset($_GET['action']) && isset($_GET['id'])) {
    $userId = (int)$_GET['id'];
    $action = $_GET['action'];
    
    if ($action === 'ban') {
        $stmt = $pdo->prepare("UPDATE Utilisateur SET Est_banni = 1 WHERE Id_utilisateur = ?");
        $stmt->execute([$userId]);
    } elseif ($action === 'unban') {
        $stmt = $pdo->prepare("UPDATE Utilisateur SET Est_banni = 0 WHERE Id_utilisateur = ?");
        $stmt->execute([$userId]);
    }
    
    // Rediriger pour éviter la resoumission du formulaire
    header('Location: gestion_utilisateurs.php');
    exit();
}

// Récupérer tous les utilisateurs sauf celui de l'admin on ne va pas le ban quand meme
$stmt = $pdo->query("SELECT u.Id_utilisateur, u.Pseudo, u.Email, u.Id_fidelite, u.Est_banni
                     FROM Utilisateur u
                     WHERE Id_role  !=2"
                     );
$leaders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des utilisateurs Admin - Casino Blackjack</title>
    <style>
        body { 
            font-family: 'Segoe UI', sans-serif; 
            background: #1a1a2e; 
            color: white; 
            text-align: center; 
            padding: 50px; 
        }
        .container { 
            background: rgba(255,255,255,0.05); 
            padding: 30px; 
            border-radius: 20px; 
            max-width: 900px; 
            margin: auto; 
        }
        h1 { color: #ffd700; }
        table { 
            width: 100%; 
            margin-top: 20px; 
            border-collapse: collapse; 
        }
        th, td { 
            padding: 10px; 
            border-bottom: 1px solid rgba(255,255,255,0.2); 
        }
        th { color: #ffd700; }
        tr:hover { background: rgba(255,255,255,0.05); }
        tr.banned { 
            background: rgba(255,0,0,0.1); 
            opacity: 0.7; 
        }
        tr.banned td { 
            color: #999; 
        }
        .btn { 
            display: inline-block; 
            margin-top: 20px; 
            padding: 12px 25px; 
            border-radius: 12px; 
            background: #ffd700; 
            color: #1a1a2e; 
            font-weight: bold; 
            text-decoration: none; 
        }
        .btn:hover { opacity: 0.9; }
        .btn-ban {
            padding: 6px 12px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
            font-size: 13px;
            text-decoration: none;
            display: inline-block;
        }
        .btn-ban.ban {
            background-color: #dc3545;
            color: white;
        }
        .btn-ban.ban:hover {
            background-color: #c82333;
        }
        .btn-ban.unban {
            background-color: #28a745;
            color: white;
        }
        .btn-ban.unban:hover {
            background-color: #218838;
        }
        .status-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 10px;
            font-size: 11px;
            font-weight: bold;
            margin-left: 5px;
        }
        .status-active {
            background-color: #28a745;
        }
        .status-banned {
            background-color: #dc3545;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Gestion des utilisateurs</h1>
        <table>
            <tr>
                <th>Id</th>
                <th>Pseudo</th>
                <th>Email</th>
                <th>Id Fidelite</th>
                <th>Statut</th>
                <th>Action</th>
            </tr>
            <?php foreach ($leaders as $leader): ?>
                <tr<?= $leader['Est_banni'] ? ' class="banned"' : '' ?>>
                    <td><?= htmlspecialchars($leader['Id_utilisateur']) ?></td>
                    <td><?= htmlspecialchars($leader['Pseudo']) ?></td>
                    <td><?= htmlspecialchars($leader['Email']) ?></td>
                    <td><?= htmlspecialchars($leader['Id_fidelite']) ?></td>
                    <td>
                        <?php if ($leader['Est_banni']): ?>
                            <span class="status-badge status-banned">BANNI</span>
                        <?php else: ?>
                            <span class="status-badge status-active">ACTIF</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($leader['Est_banni']): ?>
                            <a href="gestion_utilisateurs.php?action=unban&id=<?= $leader['Id_utilisateur'] ?>" 
                               class="btn-ban unban"
                               onclick="return confirm('Êtes-vous sûr de vouloir débannir cet utilisateur ?')">
                                Débannir
                            </a>
                        <?php else: ?>
                            <a href="gestion_utilisateurs.php?action=ban&id=<?= $leader['Id_utilisateur'] ?>" 
                               class="btn-ban ban"
                               onclick="return confirm('Êtes-vous sûr de vouloir bannir cet utilisateur ?')">
                                Bannir
                            </a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
        <a href="dashboard.php" class="btn">Retour au menu admin</a>
    </div>
</body>
</html>