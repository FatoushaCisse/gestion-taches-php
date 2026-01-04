<?php
session_start();

// Si l'utilisateur est déjà connecté, rediriger vers le dashboard
if(isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit();
}

// Inclure la configuration de la base de données
require_once 'config.php';

$error = '';

// Traitement du formulaire de connexion
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];
    
    // Vérifier les identifiants
    $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Vérifier si l'utilisateur existe et si le mot de passe correspond
    if($user && $user['mot_de_passe'] == sha1($password)) {
        // Créer la session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_nom'] = $user['nom'];
        $_SESSION['user_role'] = $user['role'];
        
        // Rediriger vers le dashboard
        header('Location: dashboard.php');
        exit();
    } else {
        $error = 'Email ou mot de passe incorrect !';
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - Gestion des Tâches</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="form-container">
        <h2>Connexion</h2>
        
        <?php if($error): ?>
            <div class="message error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label for="email">Email :</label>
                <input type="email" id="email" name="email" required>
            </div>
            
            <div class="form-group">
                <label for="password">Mot de passe :</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <button type="submit" class="btn btn-primary btn-full">Se connecter</button>
        </form>
        
        <p style="margin-top: 20px; text-align: center; color: #666; font-size: 14px;">
            <strong>Comptes de test :</strong><br>
            Admin : admin@example.com / admin123<br>
            Utilisateur : user@example.com / user123
        </p>
    </div>
</body>
</html>
