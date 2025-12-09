<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php?error=' . urlencode('Veuillez vous connecter.'));
    exit();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Règles - Blackjack Casino</title>
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
            padding: 40px 20px;
            color: white;
        }

        .header {
            text-align: center;
            margin-bottom: 50px;
        }

        .header h1 {
            color: #ffd700;
            font-size: 3rem;
            margin-bottom: 10px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
        }

        .header p {
            color: #a0a0a0;
            font-size: 1.1rem;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .video-section {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 40px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            text-align: center;
        }

        .video-section h2 {
            color: #ffd700;
            margin-bottom: 20px;
            font-size: 1.8rem;
        }

        .video-wrapper {
            position: relative;
            padding-bottom: 56.25%; /* Ratio 16:9 */
            height: 0;
            overflow: hidden;
            border-radius: 15px;
            background: rgba(0, 0, 0, 0.3);
        }

        .video-wrapper iframe {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            border-radius: 15px;
        }

        .rules-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            margin-bottom: 40px;
        }

        .rule-card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 30px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.3s ease;
        }

        .rule-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(255, 215, 0, 0.2);
            border-color: rgba(255, 215, 0, 0.3);
        }

        .rule-card .icon {
            font-size: 3rem;
            margin-bottom: 15px;
        }

        .rule-card h3 {
            color: #ffd700;
            margin-bottom: 15px;
            font-size: 1.5rem;
        }

        .rule-card p {
            color: #d0d0d0;
            line-height: 1.6;
            font-size: 1rem;
        }

        .card-values {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 30px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 40px;
        }

        .card-values h2 {
            color: #ffd700;
            margin-bottom: 25px;
            text-align: center;
            font-size: 1.8rem;
        }

        .cards-display {
            display: flex;
            justify-content: space-around;
            flex-wrap: wrap;
            gap: 20px;
        }

        .card-item {
            text-align: center;
            padding: 20px;
            background: rgba(255, 255, 255, 0.08);
            border-radius: 15px;
            min-width: 120px;
            transition: all 0.3s ease;
        }

        .card-item:hover {
            background: rgba(255, 215, 0, 0.1);
            transform: scale(1.05);
        }

        .card-symbol {
            font-size: 3rem;
            margin-bottom: 10px;
        }

        .card-value {
            color: #ffd700;
            font-size: 1.3rem;
            font-weight: bold;
        }

        .tips-section {
            background: rgba(255, 215, 0, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 30px;
            border: 1px solid rgba(255, 215, 0, 0.3);
            margin-bottom: 40px;
        }

        .tips-section h2 {
            color: #ffd700;
            margin-bottom: 20px;
            font-size: 1.8rem;
            text-align: center;
        }

        .tips-section ul {
            list-style: none;
        }

        .tips-section li {
            padding: 15px;
            margin-bottom: 10px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
            border-left: 4px solid #ffd700;
            transition: all 0.3s ease;
        }

        .tips-section li:hover {
            background: rgba(255, 255, 255, 0.1);
            transform: translateX(5px);
        }

        .tips-section li::before {
            content: "💡 ";
            margin-right: 10px;
        }

        .btn-container {
            text-align: center;
            margin-top: 40px;
        }

        .btn {
            display: inline-block;
            padding: 18px 40px;
            border-radius: 12px;
            background: linear-gradient(135deg, #ffd700 0%, #ffed4e 100%);
            color: #1a1a2e;
            font-weight: bold;
            text-decoration: none;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: all 0.3s ease;
            font-size: 1.1rem;
        }

        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(255, 215, 0, 0.4);
        }

        @media (max-width: 768px) {
            .header h1 {
                font-size: 2rem;
            }

            .rules-grid {
                grid-template-columns: 1fr;
            }

            .cards-display {
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>♠️ Règles du Blackjack ♥️</h1>
        <p>Apprenez à maîtriser le jeu et maximisez vos chances de gagner</p>
    </div>

    <div class="container">
        <!-- Vidéo explicative -->
        <div class="video-section">
            <h2>📺 Comment jouer au Blackjack</h2>
            <div class="video-wrapper">
                <!-- Vidéo YouTube explicative sur le Blackjack -->
                <iframe 
                    src="https://www.youtube.com/embed/qd5oc9hLrXg" 
                    title="Règles du Blackjack" 
                    frameborder="0" 
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                    allowfullscreen>
                </iframe>
            </div>
        </div>

        <!-- Règles principales -->
        <div class="rules-grid">
            <div class="rule-card">
                <div class="icon">🎯</div>
                <h3>Objectif du jeu</h3>
                <p>Le but est d'atteindre 21 points ou de s'en approcher le plus possible sans dépasser ce score. Si vous dépassez 21, vous perdez automatiquement la partie.</p>
            </div>

            <div class="rule-card">
                <div class="icon">🃏</div>
                <h3>Blackjack naturel</h3>
                <p>Un Blackjack est une main de 21 points obtenue avec seulement 2 cartes (As + 10 ou figure). C'est la meilleure main possible et elle bat toutes les autres mains de 21 points.</p>
            </div>

            <div class="rule-card">
                <div class="icon">🎲</div>
                <h3>Actions possibles</h3>
                <p>Vous pouvez "Tirer" pour recevoir une carte supplémentaire, ou "Rester" pour conserver votre total actuel. Choisissez judicieusement en fonction de votre main et de la carte visible du croupier.</p>
            </div>

            <div class="rule-card">
                <div class="icon">👨‍💼</div>
                <h3>Le croupier</h3>
                <p>Le croupier doit tirer jusqu'à atteindre au moins 17 points. Il ne peut pas prendre de décisions stratégiques et doit suivre ces règles strictes.</p>
            </div>
        </div>

        <!-- Valeur des cartes -->
        <div class="card-values">
            <h2>💎 Valeur des cartes</h2>
            <div class="cards-display">
                <div class="card-item">
                    <div class="card-symbol">2-10</div>
                    <div class="card-value">Valeur nominale</div>
                </div>
                <div class="card-item">
                    <div class="card-symbol">J Q K</div>
                    <div class="card-value">10 points</div>
                </div>
                <div class="card-item">
                    <div class="card-symbol">A</div>
                    <div class="card-value">1 ou 11 points</div>
                </div>
            </div>
        </div>

        <!-- Conseils stratégiques -->
        <div class="tips-section">
            <h2>🏆 Conseils pour bien jouer</h2>
            <ul>
                <li>Restez toujours si vous avez 17 points ou plus</li>
                <li>Tirez toujours si vous avez 11 points ou moins</li>
                <li>Si le croupier montre un 6 ou moins, soyez prudent car il risque de dépasser 21</li>
                <li>Si le croupier montre un As ou un 10, soyez vigilant car il a de bonnes chances d'avoir une forte main</li>
                <li>Un As compte comme 11 points sauf si cela vous fait dépasser 21, auquel cas il vaut 1 point</li>
                <li>Ne dépassez jamais 21 points, c'est une défaite automatique !</li>
            </ul>
        </div>

        <!-- Bouton retour -->
        <div class="btn-container">
            <a href="dashboard.php" class="btn">← Retour au Dashboard</a>
        </div>
    </div>
</body>
</html>