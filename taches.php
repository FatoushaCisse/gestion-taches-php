<?php
session_start();
require_once 'config.php';
require_once 'check_session.php';

$message = '';
$error = '';

// Ajouter une tâche
if(isset($_POST['add_tache'])) {
    $titre = $_POST['titre'];
    $description = $_POST['description'];
    $user_id = $_SESSION['user_id'];
    
    $stmt = $pdo->prepare("INSERT INTO taches (titre, description, user_id) VALUES (?, ?, ?)");
    if($stmt->execute([$titre, $description, $user_id])) {
        $message = 'Tâche ajoutée avec succès !';
    } else {
        $error = 'Erreur lors de l\'ajout de la tâche.';
    }
}

// Modifier une tâche
if(isset($_POST['edit_tache'])) {
    $id = $_POST['id'];
    $titre = $_POST['titre'];
    $description = $_POST['description'];
    
    // Vérifier si l'utilisateur peut modifier cette tâche
    if($_SESSION['user_role'] == 'admin') {
        $stmt = $pdo->prepare("UPDATE taches SET titre = ?, description = ? WHERE id = ?");
    } else {
        $stmt = $pdo->prepare("UPDATE taches SET titre = ?, description = ? WHERE id = ? AND user_id = ?");
    }
    
    if($_SESSION['user_role'] == 'admin') {
        $result = $stmt->execute([$titre, $description, $id]);
    } else {
        $result = $stmt->execute([$titre, $description, $id, $_SESSION['user_id']]);
    }
    
    if($result) {
        $message = 'Tâche modifiée avec succès !';
    } else {
        $error = 'Erreur lors de la modification.';
    }
}

// Changer le statut d'une tâche
if(isset($_GET['toggle_statut'])) {
    $id = $_GET['toggle_statut'];
    
    if($_SESSION['user_role'] == 'admin') {
        $stmt = $pdo->prepare("UPDATE taches SET statut = IF(statut = 'en_cours', 'terminee', 'en_cours') WHERE id = ?");
        $stmt->execute([$id]);
    } else {
        $stmt = $pdo->prepare("UPDATE taches SET statut = IF(statut = 'en_cours', 'terminee', 'en_cours') WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $_SESSION['user_id']]);
    }
    
    header('Location: taches.php');
    exit();
}

// Supprimer une tâche
if(isset($_GET['delete'])) {
    $id = $_GET['delete'];
    
    if($_SESSION['user_role'] == 'admin') {
        $stmt = $pdo->prepare("DELETE FROM taches WHERE id = ?");
        $stmt->execute([$id]);
    } else {
        $stmt = $pdo->prepare("DELETE FROM taches WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $_SESSION['user_id']]);
    }
    
    $message = 'Tâche supprimée avec succès !';
}

// Récupérer les tâches
$search = isset($_GET['search']) ? $_GET['search'] : '';
$filtre_statut = isset($_GET['statut']) ? $_GET['statut'] : '';

if($_SESSION['user_role'] == 'admin') {
    // L'admin voit toutes les tâches
    $query = "SELECT t.*, u.nom as user_nom FROM taches t 
              JOIN utilisateurs u ON t.user_id = u.id 
              WHERE 1=1";
    
    $params = [];
    
    if($search) {
        $query .= " AND (t.titre LIKE ? OR t.description LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    if($filtre_statut) {
        $query .= " AND t.statut = ?";
        $params[] = $filtre_statut;
    }
    
    $query .= " ORDER BY t.date_creation DESC";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
} else {
    // L'utilisateur voit seulement ses tâches
    $query = "SELECT * FROM taches WHERE user_id = ?";
    $params = [$_SESSION['user_id']];
    
    if($search) {
        $query .= " AND (titre LIKE ? OR description LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    if($filtre_statut) {
        $query .= " AND statut = ?";
        $params[] = $filtre_statut;
    }
    
    $query .= " ORDER BY date_creation DESC";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
}

$taches = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupérer une tâche pour modification
$tache_edit = null;
if(isset($_GET['edit'])) {
    $id = $_GET['edit'];
    
    if($_SESSION['user_role'] == 'admin') {
        $stmt = $pdo->prepare("SELECT * FROM taches WHERE id = ?");
        $stmt->execute([$id]);
    } else {
        $stmt = $pdo->prepare("SELECT * FROM taches WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $_SESSION['user_id']]);
    }
    
    $tache_edit = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Tâches</title>
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
                <a href="logout.php">Déconnexion</a>
            </nav>
        </div>
    </header>

    <div class="container">
        <h2><?php echo $_SESSION['user_role'] == 'admin' ? 'Toutes les tâches' : 'Mes tâches'; ?></h2>
        
        <?php if($message): ?>
            <div class="message success"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <?php if($error): ?>
            <div class="message error"><?php echo $error; ?></div>
        <?php endif; ?>

        <!-- Formulaire d'ajout/modification -->
        <?php if(isset($_GET['action']) && $_GET['action'] == 'add' || $tache_edit): ?>
        <div class="card">
            <h3><?php echo $tache_edit ? 'Modifier la tâche' : 'Ajouter une nouvelle tâche'; ?></h3>
            <form method="POST">
                <?php if($tache_edit): ?>
                    <input type="hidden" name="id" value="<?php echo $tache_edit['id']; ?>">
                <?php endif; ?>
                
                <div class="form-group">
                    <label for="titre">Titre :</label>
                    <input type="text" id="titre" name="titre" required 
                           value="<?php echo $tache_edit ? htmlspecialchars($tache_edit['titre']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="description">Description :</label>
                    <textarea id="description" name="description"><?php echo $tache_edit ? htmlspecialchars($tache_edit['description']) : ''; ?></textarea>
                </div>
                
                <button type="submit" name="<?php echo $tache_edit ? 'edit_tache' : 'add_tache'; ?>" 
                        class="btn btn-success">
                    <?php echo $tache_edit ? 'Modifier' : 'Ajouter'; ?>
                </button>
                <a href="taches.php" class="btn btn-danger">Annuler</a>
            </form>
        </div>
        <?php else: ?>
            <a href="taches.php?action=add" class="btn btn-success" style="margin-bottom: 20px;">+ Ajouter une tâche</a>
        <?php endif; ?>

        <!-- Recherche et filtres -->
        <div class="search-filter">
            <form method="GET">
                <div class="form-group">
                    <label for="search">Rechercher :</label>
                    <input type="text" id="search" name="search" 
                           placeholder="Titre ou description..." 
                           value="<?php echo htmlspecialchars($search); ?>">
                </div>
                
                <div class="form-group">
                    <label for="statut">Statut :</label>
                    <select id="statut" name="statut">
                        <option value="">Tous</option>
                        <option value="en_cours" <?php echo $filtre_statut == 'en_cours' ? 'selected' : ''; ?>>En cours</option>
                        <option value="terminee" <?php echo $filtre_statut == 'terminee' ? 'selected' : ''; ?>>Terminée</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">Filtrer</button>
                    <a href="taches.php" class="btn btn-warning">Réinitialiser</a>
                </div>
            </form>
        </div>

        <!-- Liste des tâches -->
        <?php if(count($taches) > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>Titre</th>
                    <th>Description</th>
                    <?php if($_SESSION['user_role'] == 'admin'): ?>
                        <th>Utilisateur</th>
                    <?php endif; ?>
                    <th>Statut</th>
                    <th>Date de création</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($taches as $tache): ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($tache['titre']); ?></strong></td>
                    <td><?php echo htmlspecialchars(substr($tache['description'], 0, 50)); ?><?php echo strlen($tache['description']) > 50 ? '...' : ''; ?></td>
                    <?php if($_SESSION['user_role'] == 'admin'): ?>
                        <td><?php echo htmlspecialchars($tache['user_nom']); ?></td>
                    <?php endif; ?>
                    <td>
                        <span class="badge <?php echo $tache['statut']; ?>">
                            <?php echo $tache['statut'] == 'en_cours' ? 'En cours' : 'Terminée'; ?>
                        </span>
                    </td>
                    <td><?php echo date('d/m/Y H:i', strtotime($tache['date_creation'])); ?></td>
                    <td class="actions">
                        <a href="taches.php?toggle_statut=<?php echo $tache['id']; ?>" 
                           class="btn btn-success" 
                           onclick="return confirm('Changer le statut de cette tâche ?')">
                            <?php echo $tache['statut'] == 'en_cours' ? '✓' : '↺'; ?>
                        </a>
                        <a href="taches.php?edit=<?php echo $tache['id']; ?>" class="btn btn-warning">Modifier</a>
                        <a href="taches.php?delete=<?php echo $tache['id']; ?>" 
                           class="btn btn-danger"
                           onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette tâche ?')">
                            Supprimer
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
            <div class="card">
                <p style="text-align: center; color: #666;">Aucune tâche trouvée.</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
