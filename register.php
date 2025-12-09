<?php
// register.php - Page d'inscription
session_start();
require_once 'config.php';

// Vérifier si l'utilisateur est déjà connecté
if (isset($_SESSION['user_id'])) {
    header('Location: home.php');
    exit();
}

// Traitement du formulaire d'inscription
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pseudo = trim($_POST['pseudo'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');
    
    // Validation des champs
    $errors = [];
    
    if (empty($pseudo)) {
        $errors[] = "Le pseudo est obligatoire";
    } elseif (strlen($pseudo) < 3) {
        $errors[] = "Le pseudo doit contenir au moins 3 caractères";
    }
    
    if (empty($email)) {
        $errors[] = "L'email est obligatoire";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "L'email n'est pas valide";
    }
    
    if (empty($password)) {
        $errors[] = "Le mot de passe est obligatoire";
    } elseif (strlen($password) < 6) {
        $errors[] = "Le mot de passe doit contenir au moins 6 caractères";
    }
    
    if ($password !== $confirm_password) {
        $errors[] = "Les mots de passe ne correspondent pas";
    }
    
    if (empty($errors)) {
        try {
            $pdo = getConnection();
            
            // Vérifier si l'email existe déjà
            $checkEmail = $pdo->prepare("SELECT Id_utilisateur FROM Utilisateur WHERE Email = :email");
            $checkEmail->execute(['email' => $email]);
            if ($checkEmail->fetch()) {
                $errors[] = "Cet email est déjà utilisé";
            }
            
            // Vérifier si le pseudo existe déjà
            $checkPseudo = $pdo->prepare("SELECT Id_utilisateur FROM Utilisateur WHERE Pseudo = :pseudo");
            $checkPseudo->execute(['pseudo' => $pseudo]);
            if ($checkPseudo->fetch()) {
                $errors[] = "Ce pseudo est déjà utilisé";
            }
            
            if (empty($errors)) {
                // Générer un ID unique
                $getMaxId = $pdo->query("SELECT COALESCE(MAX(Id_utilisateur), 0) + 1 as next_id FROM Utilisateur");
                $nextId = $getMaxId->fetch()['next_id'];

                // Hasher le mot de passe (IMPORTANT: utiliser password_hash en production!)
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);

                // Commencer une transaction
                $pdo->beginTransaction();

                // Insérer l'utilisateur
                $insertUser = "INSERT INTO Utilisateur (Id_utilisateur, Pseudo, Email, Mot_de_passe_hash, Est_actif, Est_banni, Id_fidelite, Id_role)
                               VALUES (:id, :pseudo, :email, :password, TRUE, FALSE, NULL, 1)";
                $stmtUser = $pdo->prepare($insertUser);
                $stmtUser->execute([
                    'id' => $nextId,
                    'pseudo' => $pseudo,
                    'email' => $email,
                    'password' => $passwordHash
                ]);

                // Créer le solde initial
                $insertSolde = "INSERT INTO Solde (Id_solde, Limit_depot, Limite_mise, Montant_solde, Id_utilisateur)
                                VALUES (:id, 5000.00, 500.00, 100.00, :user_id)";
                $stmtSolde = $pdo->prepare($insertSolde);
                $stmtSolde->execute([
                    'id' => $nextId,
                    'user_id' => $nextId
                ]);

                // Créer l'entrée leaderboard
                $insertLeaderboard = "INSERT INTO Leaderboard (Id_leaderboard, Nb_defaite, Nb_victoire, Id_utilisateur)
                                      VALUES (:id, 0, 0, :user_id)";
                $stmtLeaderboard = $pdo->prepare($insertLeaderboard);
                $stmtLeaderboard->execute([
                    'id' => $nextId,
                    'user_id' => $nextId
                ]);

                // Enregistrer la transaction d'inscription
                $getMaxTransId = $pdo->query("SELECT COALESCE(MAX(Id_transaction), 0) + 1 as next_id FROM Transaction");
                $nextTransId = $getMaxTransId->fetch()['next_id'];

                $insertTransaction = "INSERT INTO Transaction (Id_transaction, Type_transaction, Montant, Date_transaction, Description, Id_utilisateur)
                                      VALUES (:id, 'Inscription', 100.00, NOW(), 'Bonus de bienvenue', :user_id)";
                $stmtTransaction = $pdo->prepare($insertTransaction);
                $stmtTransaction->execute([
                    'id' => $nextTransId,
                    'user_id' => $nextId
                ]);

                // Valider la transaction
                $pdo->commit();

                // Connexion automatique
                $_SESSION['user_id']        = $nextId;
                $_SESSION['pseudo']         = $pseudo;
                $_SESSION['email']          = $email;
                $_SESSION['role']           = 'Joueur';
                $_SESSION['solde']          = 100.00;
                $_SESSION['niveau_fidelite']= 0;
                $_SESSION['points_fidelite']= 0;

                header('Location: home.php');
                exit();
            }
            
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log("Erreur d'inscription : " . $e->getMessage());
            $errors[] = "Une erreur est survenue lors de l'inscription";
        }
    }
    
    // Stocker les erreurs et données pour réaffichage
    if (!empty($errors)) {
        $_SESSION['register_errors'] = $errors;
        $_SESSION['register_data'] = ['pseudo' => $pseudo, 'email' => $email];
        header('Location: register.php');
        exit();
    }
}

// Récupérer les erreurs et données
$errors = $_SESSION['register_errors'] ?? [];
$oldData = $_SESSION['register_data'] ?? [];
unset($_SESSION['register_errors'], $_SESSION['register_data']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription - Blackjack Casino</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .container {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 40px;
            width: 100%;
            max-width: 450px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .logo {
            text-align: center;
            margin-bottom: 30px;
        }

        .logo h1 {
            color: #ffd700;
            font-size: 2.5rem;
            margin-bottom: 5px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
        }

        .logo p {
            color: #a0a0a0;
            font-size: 0.9rem;
        }

        .form-group {
            margin-bottom: 25px;
        }

        label {
            display: block;
            color: #ffffff;
            margin-bottom: 8px;
            font-weight: 500;
            font-size: 0.95rem;
        }

        input[type="text"],
        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 15px;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 10px;
            color: #ffffff;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        input:focus {
            outline: none;
            background: rgba(255, 255, 255, 0.15);
            border-color: #ffd700;
            box-shadow: 0 0 15px rgba(255, 215, 0, 0.2);
        }

        input::placeholder {
            color: rgba(255, 255, 255, 0.5);
        }

        .btn {
            width: 100%;
            padding: 15px;
            border: none;
            border-radius: 10px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #ffd700 0%, #ffed4e 100%);
            color: #1a1a2e;
            margin-bottom: 15px;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(255, 215, 0, 0.4);
        }

        .btn-secondary {
            background: transparent;
            color: #ffd700;
            border: 2px solid #ffd700;
            text-decoration: none;
            display: block;
            text-align: center;
        }

        .btn-secondary:hover {
            background: rgba(255, 215, 0, 0.1);
            transform: translateY(-2px);
        }

        .divider {
            text-align: center;
            margin: 25px 0;
            position: relative;
        }

        .divider::before,
        .divider::after {
            content: '';
            position: absolute;
            top: 50%;
            width: 40%;
            height: 1px;
            background: rgba(255, 255, 255, 0.2);
        }

        .divider::before {
            left: 0;
        }

        .divider::after {
            right: 0;
        }

        .divider span {
            color: #a0a0a0;
            background: transparent;
            padding: 0 15px;
            font-size: 0.9rem;
        }

        .error-message {
            background: rgba(255, 0, 0, 0.1);
            border: 1px solid rgba(255, 0, 0, 0.3);
            color: #ff6b6b;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 0.9rem;
        }

        .success-message {
            background: rgba(0, 255, 0, 0.1);
            border: 1px solid rgba(0, 255, 0, 0.3);
            color: #51cf66;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 0.9rem;
        }

        .forgot-password {
            text-align: right;
            margin-top: -15px;
            margin-bottom: 20px;
        }

        .forgot-password a {
            color: #ffd700;
            text-decoration: none;
            font-size: 0.9rem;
            transition: color 0.3s ease;
        }

        .forgot-password a:hover {
            color: #ffed4e;
            text-decoration: underline;
        }

        @media (max-width: 480px) {
            .container {
                padding: 30px 20px;
            }

            .logo h1 {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">
            <h1>♠️ BLACKJACK ♥️</h1>
            <p>Créer un compte</p>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="error-message">
                <?php foreach ($errors as $error): ?>
                    • <?= htmlspecialchars($error) ?><br>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="register.php">
            <div class="form-group">
                <label for="pseudo">Pseudo</label>
                <input type="text" 
                       id="pseudo" 
                       name="pseudo" 
                       value="<?= htmlspecialchars($oldData['pseudo'] ?? '') ?>" 
                       placeholder="Choisissez un pseudo"
                       required>
            </div>

            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" 
                       id="email" 
                       name="email" 
                       value="<?= htmlspecialchars($oldData['email'] ?? '') ?>" 
                       placeholder="votre@email.com"
                       required>
            </div>

            <div class="form-group">
                <label for="password">Mot de passe</label>
                <input type="password" 
                       id="password" 
                       name="password" 
                       placeholder="Minimum 6 caractères"
                       required>
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirmer le mot de passe</label>
                <input type="password" 
                       id="confirm_password" 
                       name="confirm_password" 
                       placeholder="Retapez votre mot de passe"
                       required>
            </div>

            <button type="submit" class="btn btn-primary">S'inscrire</button>
        </form>

        <div class="divider">
            <span>OU</span>
        </div>

        <a href="index.php" class="btn btn-secondary">← Retour à la connexion</a>
    </div>
</body>
</html>