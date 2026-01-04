<?php
session_start();
require_once 'config.php';
require_once 'check_session.php';

// Récupérer les statistiques pour l'administrateur
if($_SESSION['user_role'] == 'admin') {
    // Total des utilisateurs
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM utilisateurs");
    $total_users = $stmt->fetch()['total'];
    
    // Total des tâches
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM taches");
    $total_taches = $stmt->fetch()['total'];
    
    // Tâches en cours
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM taches WHERE statut = 'en_cours'");
    $taches_en_cours = $stmt->fetch()['total'];
    
    // Tâches terminées
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM taches WHERE statut = 'terminee'");
    $taches_terminees = $stmt->fetch()['total'];
} else {
    // Statistiques pour un utilisateur simple
    $user_id = $_SESSION['user_id'];
    
    // Total des tâches de l'utilisateur
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM taches WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $total_taches = $stmt->fetch()['total'];
    
    // Tâches en cours
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM taches WHERE user_id = ? AND statut = 'en_cours'");
    $stmt->execute([$user_id]);
    $taches_en_cours = $stmt->fetch()['total'];
    
    // Tâches terminées
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM taches WHERE user_id = ? AND statut = 'terminee'");
    $stmt->execute([$user_id]);
    $taches_terminees = $stmt->fetch()['total'];
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de bord - Gestion des Tâches</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <div class="container">
            <h1>Gestion des Tâches</h1>
            <nav>
                <a href="dashboard.php">Tableau de bord</a>
                <a href="taches.php">Mes tâches</a>
                <?php if($_SESSION['user_role'] == 'admin'): ?>
                    <a href="utilisateurs.php">Utilisateurs</a>
                <?php endif; ?>
                <a href="logout.php">Déconnexion (<?php echo $_SESSION['user_nom']; ?>)</a>
            </nav>
        </div>
    </header>

    <div class="container">
        <h2>Bienvenue, <?php echo $_SESSION['user_nom']; ?> !</h2>
        <p style="margin-bottom: 30px; color: #666;">
            Rôle : <strong><?php echo $_SESSION['user_role'] == 'admin' ? 'Administrateur' : 'Utilisateur'; ?></strong>
        </p>

        <div class="stats-grid">
            <?php if($_SESSION['user_role'] == 'admin'): ?>
                <div class="stat-card">
                    <h3><?php echo $total_users; ?></h3>
                    <p>Utilisateurs</p>
                </div>
            <?php endif; ?>
            
            <div class="stat-card">
                <h3><?php echo $total_taches; ?></h3>
                <p>Total des tâches</p>
            </div>
            
            <div class="stat-card">
                <h3><?php echo $taches_en_cours; ?></h3>
                <p>Tâches en cours</p>
            </div>
            
            <div class="stat-card">
                <h3><?php echo $taches_terminees; ?></h3>
                <p>Tâches terminées</p>
            </div>
        </div>

        <div class="card">
            <h3>Actions rapides</h3>
            <a href="taches.php" class="btn btn-primary">Voir mes tâches</a>
            <a href="taches.php?action=add" class="btn btn-success">Ajouter une tâche</a>
            <?php if($_SESSION['user_role'] == 'admin'): ?>
                <a href="utilisateurs.php" class="btn btn-warning">Gérer les utilisateurs</a>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
