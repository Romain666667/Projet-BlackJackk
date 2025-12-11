<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php?error=' . urlencode('Veuillez vous connecter.'));
    exit();
}

$pdo = getConnection();
$message = '';
$error = '';

// Traitement du dépôt
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'depot') {
    $montant = floatval($_POST['montant'] ?? 0);

    if ($montant <= 0) {
        $error = "Le montant doit être supérieur à 0";
    } else {
        try {
            $pdo->beginTransaction();

            // Récupérer limite + solde actuel
            $stmt = $pdo->prepare("SELECT Limit_depot, Montant_solde FROM Solde WHERE Id_utilisateur = :id");
            $stmt->execute(['id' => $_SESSION['user_id']]);
            $solde = $stmt->fetch();

            if (!$solde) {
                throw new Exception("Impossible de récupérer les informations de solde.");
            }

            // ***** Vérification de la limite hebdomadaire *****

            // Total des dépôts depuis lundi
            $stmt = $pdo->prepare("
                SELECT COALESCE(SUM(Montant), 0) AS total_semaine
                FROM Transaction
                WHERE Id_utilisateur = :id
                AND Type_transaction = 'Dépôt'
                AND YEARWEEK(Date_transaction, 1) = YEARWEEK(NOW(), 1)
            ");
            $stmt->execute(['id' => $_SESSION['user_id']]);
            $totalSemaine = floatval($stmt->fetch()['total_semaine']);

            // Vérifier si on dépasse la limite
            if ($totalSemaine + $montant > $solde['Limit_depot']) {
                $restant = $solde['Limit_depot'] - $totalSemaine;

                if ($restant < 0) $restant = 0;

                $error = "⛔ Vous avez atteint votre limite hebdomadaire de dépôt (" 
                      . number_format($solde['Limit_depot'], 2) . " €).<br>"
                      . "Déjà déposés cette semaine : " . number_format($totalSemaine, 2) . " €.<br>"
                      . "Montant maximum restant cette semaine : " . number_format($restant, 2) . " €.";

                $pdo->rollBack();
            } 
            else {

                // ***** Dépôt autorisé *****

                // Mise à jour du solde
                $nouveauSolde = $solde['Montant_solde'] + $montant;
                $stmt = $pdo->prepare("UPDATE Solde SET Montant_solde = :montant WHERE Id_utilisateur = :id");
                $stmt->execute(['montant' => $nouveauSolde, 'id' => $_SESSION['user_id']]);

                // Bonus fidélité : 50% du dépôt
                $pointsFidelite = $montant * 0.5;
                $stmt = $pdo->prepare("
                    UPDATE Fidelite 
                    SET Point_totaux = Point_totaux + :points 
                    WHERE Id_fidelite = (SELECT Id_fidelite FROM Utilisateur WHERE Id_utilisateur = :id)
                ");
                $stmt->execute(['points' => $pointsFidelite, 'id' => $_SESSION['user_id']]);

                // ID de transaction suivant
                $getMaxId = $pdo->query("SELECT COALESCE(MAX(Id_transaction), 0) + 1 AS next_id FROM Transaction");
                $nextId = $getMaxId->fetch()['next_id'];

                // Ajouter la transaction
                $stmt = $pdo->prepare("
                    INSERT INTO Transaction 
                    (Id_transaction, Type_transaction, Montant, Date_transaction, Description, Id_utilisateur) 
                    VALUES (:id, 'Dépôt', :montant, NOW(), 'Dépôt sur le compte', :user_id)
                ");
                $stmt->execute([
                    'id' => $nextId,
                    'montant' => $montant,
                    'user_id' => $_SESSION['user_id']
                ]);

                $pdo->commit();

                $_SESSION['solde'] = $nouveauSolde;

                $message = "Dépôt de " . number_format($montant, 2) . " € effectué avec succès !";
            }

        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Erreur lors du dépôt : " . $e->getMessage();
        }
    }
}


// Traitement du retrait
if ($_SERVER['REQUEST_METHOD'] === 'POST' 
    && isset($_POST['action']) 
    && $_POST['action'] === 'retrait') {

    $montant = filter_var($_POST['montant'], FILTER_VALIDATE_FLOAT);

    // Vérification du montant
    if ($montant === false || $montant <= 0 || $montant > 999999.99) {
        $error = "Montant invalide";
        exit();
    }

    try {
        $pdo->beginTransaction();

        // Vérifier le solde disponible
        $stmt = $pdo->prepare("SELECT Montant_solde FROM Solde WHERE Id_utilisateur = :id");
        $stmt->execute(['id' => $_SESSION['user_id']]);
        $solde = $stmt->fetch();

        if (!$solde) {
            throw new Exception("Impossible de récupérer le solde.");
        }

        // Solde insuffisant
        if ($montant > $solde['Montant_solde']) {
            $error = "Solde insuffisant pour effectuer ce retrait";
        } else {
            // Mise à jour du solde
            $nouveauSolde = $solde['Montant_solde'] - $montant;

            $stmt = $pdo->prepare("UPDATE Solde 
                                   SET Montant_solde = :montant 
                                   WHERE Id_utilisateur = :id");
            $stmt->execute([
                'montant' => $nouveauSolde, 
                'id' => $_SESSION['user_id']
            ]);

            // ID de transaction
            $getMaxId = $pdo->query("SELECT COALESCE(MAX(Id_transaction), 0) + 1 AS next_id 
                                     FROM Transaction");
            $nextId = $getMaxId->fetch()['next_id'];

            // Enregistrer la transaction
            $stmt = $pdo->prepare("
                INSERT INTO Transaction 
                (Id_transaction, Type_transaction, Montant, Date_transaction, Description, Id_utilisateur)
                VALUES 
                (:id, 'Retrait', :montant, NOW(), 'Retrait du compte', :user_id)
            ");
            $stmt->execute([
                'id' => $nextId,
                'montant' => $montant,
                'user_id' => $_SESSION['user_id']
            ]);

            $pdo->commit();

            $_SESSION['solde'] = $nouveauSolde;
            $message = "Retrait de " . number_format($montant, 2) . " € effectué avec succès !";
        }

    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Erreur lors du retrait : " . $e->getMessage();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'modifier_limite') {

    $nouvelleLimite = floatval($_POST['nouvelle_limite']);
    $password = $_POST['password_limite'] ?? '';

    if ($nouvelleLimite <= 0) {
        $error = "La limite doit être supérieure à 0.";
    } else {
        // Récupérer le mot de passe stocké en clair
        $stmt = $pdo->prepare("SELECT Mot_de_passe_hash FROM Utilisateur WHERE Id_utilisateur = :id");
        $stmt->execute(['id' => $_SESSION['user_id']]);
        $user_check = $stmt->fetch();

        // Comparer en clair
        if ($password !== $user_check['Mot_de_passe_hash']) {
            $error = "Mot de passe incorrect.";
        } else {
            // Mise à jour de la limite
            $stmt = $pdo->prepare("UPDATE Solde SET Limit_depot = :limite WHERE Id_utilisateur = :id");
            $stmt->execute([
                'limite' => $nouvelleLimite,
                'id' => $_SESSION['user_id']
            ]);

            $message = "La limite de dépôt a bien été modifiée !";
        }
    }
}

// Traitement de la modification du profil
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'modifier_profil') {
    $pseudo = trim($_POST['pseudo'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $ancien_mdp = $_POST['ancien_mdp'] ?? '';
    $nouveau_mdp = $_POST['nouveau_mdp'] ?? '';
    $confirm_mdp = $_POST['confirm_mdp'] ?? '';
    
    $errors = [];
    
    if (!empty($pseudo) && $pseudo !== $_SESSION['pseudo']) {
        // Vérifier si le pseudo existe déjà
        $stmt = $pdo->prepare("SELECT Id_utilisateur FROM Utilisateur WHERE Pseudo = :pseudo AND Id_utilisateur != :id");
        $stmt->execute(['pseudo' => $pseudo, 'id' => $_SESSION['user_id']]);
        // Erreur si le pseudo est déja utilisé
        if ($stmt->fetch()) {
            $errors[] = "Ce pseudo est déjà utilisé";
        // Modification du pseudo
        } else {
            $stmt = $pdo->prepare("UPDATE Utilisateur SET Pseudo = :pseudo WHERE Id_utilisateur = :id");
            $stmt->execute(['pseudo' => $pseudo, 'id' => $_SESSION['user_id']]);
            $_SESSION['pseudo'] = $pseudo;
            $message = "Pseudo modifié avec succès !";
        }
    }
    
    if (!empty($email) && $email !== $_SESSION['email']) {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Email invalide";
        } else {
            // Vérifier si l'email existe déjà
            $stmt = $pdo->prepare("SELECT Id_utilisateur FROM Utilisateur WHERE Email = :email AND Id_utilisateur != :id");
            $stmt->execute(['email' => $email, 'id' => $_SESSION['user_id']]);
            if ($stmt->fetch()) {
                $errors[] = "Cet email est déjà utilisé";
            } else {
                $stmt = $pdo->prepare("UPDATE Utilisateur SET Email = :email WHERE Id_utilisateur = :id");
                $stmt->execute(['email' => $email, 'id' => $_SESSION['user_id']]);
                $_SESSION['email'] = $email;
                $message = "Email modifié avec succès !";
            }
        }
    }
    
    if (!empty($nouveau_mdp)) {
        if (empty($ancien_mdp)) {
            $errors[] = "Veuillez entrer votre ancien mot de passe";
        } elseif ($nouveau_mdp !== $confirm_mdp) {
            $errors[] = "Les nouveaux mots de passe ne correspondent pas";
        } elseif (strlen($nouveau_mdp) < 6) {
            $errors[] = "Le nouveau mot de passe doit contenir au moins 6 caractères";
        } else {
            // Vérifier l'ancien mot de passe
            $stmt = $pdo->prepare("SELECT Mot_de_passe_hash FROM Utilisateur WHERE Id_utilisateur = :id");
            $stmt->execute(['id' => $_SESSION['user_id']]);
            $user_check = $stmt->fetch();
            
            if (password_verify($ancien_mdp, $user_check['Mot_de_passe_hash'])) {
                $nouveau_hash = password_hash($nouveau_mdp, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE Utilisateur SET Mot_de_passe_hash = :hash WHERE Id_utilisateur = :id");
                $stmt->execute(['hash' => $nouveau_hash, 'id' => $_SESSION['user_id']]);
                $message = "Mot de passe modifié avec succès !";
            } else {
                $errors[] = "Ancien mot de passe incorrect";
            }
        }
    }
    
    if (!empty($errors)) {
        $error = implode("<br>", $errors);
    }
}

// Récupérer les informations de l'utilisateur
$stmt = $pdo->prepare("SELECT u.Pseudo, u.Email, r.Nom_role, s.Montant_solde, s.Limite_mise, s.Limit_depot, f.Niveau_fidelite, f.Point_totaux
                       FROM Utilisateur u
                       JOIN Role r ON u.Id_role = r.Id_role
                       LEFT JOIN Solde s ON u.Id_utilisateur = s.Id_utilisateur
                       LEFT JOIN Fidelite f ON u.Id_fidelite = f.Id_fidelite
                       WHERE u.Id_utilisateur = :id");
$stmt->execute(['id' => $_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Inclure la vue HTML
include 'account.html';
?>