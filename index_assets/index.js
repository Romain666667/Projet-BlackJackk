// Fichier JavaScript pour la page de connexion
// Actuellement, toutes les interactions sont gérées via le HTML inline
// Ce fichier peut être utilisé pour ajouter des fonctionnalités supplémentaires

// Exemple : validation du formulaire côté client
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form');
    
    if (form) {
        form.addEventListener('submit', function(e) {
            const identifier = document.getElementById('identifier').value.trim();
            const password = document.getElementById('password').value.trim();
            
            if (!identifier || !password) {
                e.preventDefault();
                alert('Tous les champs sont obligatoires');
                return false;
            }
        });
    }
    
    // Auto-focus sur le premier champ
    const firstInput = document.getElementById('identifier');
    if (firstInput) {
        firstInput.focus();
    }
});