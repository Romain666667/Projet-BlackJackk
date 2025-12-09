<?php
// index.php - Connexion utilisateur
session_start();
require_once 'config.php';

// Si l'utilisateur est déjà connecté, rediriger
if (isset($_SESSION['user_id'])) {
    header('Location: home.php');
    exit();
}

$error = '';
$success = '';

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = trim($_POST['identifier'] ?? '');
    $password   = trim($_POST['password'] ?? '');

    if (empty($identifier) || empty($password)) {
        $error = 'Tous les champs sont obligatoires';
    } else {
        try {
            $pdo = getConnection();

            // Rechercher l'utilisateur par email ou pseudo
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
            $user = $stmt->fetch(PDO::FETCH_ASSOC); // récupère la première ligne

            if (!$user) {
                $error = 'Identifiants incorrects';
            } elseif ($user['Est_banni']) {
                $error = 'Votre compte a été banni.';
            } elseif (!$user['Est_actif']) {
                $error = 'Votre compte est désactivé.';
            } elseif ($password !== $user['Mot_de_passe_hash']) { // clair pour l'instant
                $error = 'Identifiants incorrects';
            } else {
                $_SESSION['user_id'] = $user['Id_utilisateur'];
                $_SESSION['pseudo'] = $user['Pseudo'];
                $_SESSION['email'] = $user['Email'];
                $_SESSION['role'] = $user['Nom_role'] ?? 'Joueur';
                $_SESSION['solde'] = $user['Montant_solde'] ?? 0;
                $_SESSION['limite_mise'] = $user['Limite_mise'] ?? 0;
                $_SESSION['niveau_fidelite'] = $user['Niveau_fidelite'] ?? 0;
                $_SESSION['points_fidelite'] = $user['Point_totaux'] ?? 0;

                header('Location: home.php');
                exit();
            }

        } catch (PDOException $e) {
            error_log("Erreur de connexion : " . $e->getMessage());
            $error = 'Une erreur est survenue. Veuillez réessayer.';
        }
    }
}

// Messages passés en GET depuis register.php
if (isset($_GET['error'])) $error = $_GET['error'];
if (isset($_GET['success'])) $success = $_GET['success'];

// Inclure la vue HTML
include 'index.html';
?>