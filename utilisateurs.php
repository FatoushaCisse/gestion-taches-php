<?php
session_start();
require_once 'config.php';
require_once 'check_session.php';

// Vérifier que l'utilisateur est admin
if($_SESSION['user_role'] != 'admin') {
    header('Location: dashboard.php');
    exit();
}

$message = '';
$error = '';

// Ajouter un utilisateur
if(isset($_POST['add_user'])) {
    $nom = $_POST['nom'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $role = $_POST['role'];
    
    // Vérifier si l'email existe déjà
    $stmt = $pdo->prepare("SELECT id FROM utilisateurs WHERE email = ?");
    $stmt->execute([$email]);
    
    if($stmt->fetch()) {
        $error = 'Cet email est déjà utilisé !';
    } else {
        $stmt = $pdo->prepare("INSERT INTO utilisateurs (nom, email, mot_de_passe, role) VALUES (?, ?, SHA1(?), ?)");
        if($stmt->execute([$nom, $email, $password, $role])) {
            $message = 'Utilisateur ajouté avec succès !';
        } else {
            $error = 'Erreur lors de l\'ajout de l\'utilisateur.';
        }
    }
}

// Modifier un utilisateur
if(isset($_POST['edit_user'])) {
    $id = $_POST['id'];
    $nom = $_POST['nom'];
    $email = $_POST['email'];
    $role = $_POST['role'];
    
    if(!empty($_POST['password'])) {
        // Si un nouveau mot de passe est fourni
        $stmt = $pdo->prepare("UPDATE utilisateurs SET nom = ?, email = ?, mot_de_passe = SHA1(?), role = ? WHERE id = ?");
        $result = $stmt->execute([$nom, $email, $_POST['password'], $role, $id]);
    } else {
        // Sans changer le mot de passe
        $stmt = $pdo->prepare("UPDATE utilisateurs SET nom = ?, email = ?, role = ? WHERE id = ?");
        $result = $stmt->execute([$nom, $email, $role, $id]);
    }
    
    if($result) {
        $message = 'Utilisateur modifié avec succès !';
    } else {
        $error = 'Erreur lors de la modification.';
    }
}

// Supprimer un utilisateur
if(isset($_GET['delete'])) {
    $id = $_GET['delete'];
    
    // Ne pas permettre de supprimer son propre compte
    if($id == $_SESSION['user_id']) {
        $error = 'Vous ne pouvez pas supprimer votre propre compte !';
    } else {
        $stmt = $pdo->prepare("DELETE FROM utilisateurs WHERE id = ?");
        if($stmt->execute([$id])) {
            $message = 'Utilisateur supprimé avec succès !';
        }
    }
}

// Récupérer tous les utilisateurs
$stmt = $pdo->query("SELECT * FROM utilisateurs ORDER BY date_creation DESC");
$utilisateurs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupérer un utilisateur pour modification
$user_edit = null;
if(isset($_GET['edit'])) {
    $id = $_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE id = ?");
    $stmt->execute([$id]);
    $user_edit = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Utilisateurs</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <div class="container">
            <h1>Gestion des Tâches</h1>
            <nav>
                <a href="dashboard.php">Tableau de bord</a>
                <a href="taches.php">Mes tâches</a>
                <a href="utilisateurs.php">Utilisateurs</a>
                <a href="logout.php">Déconnexion</a>
            </nav>
        </div>
    </header>

    <div class="container">
        <h2>Gestion des Utilisateurs</h2>
        
        <?php if($message): ?>
            <div class="message success"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <?php if($error): ?>
            <div class="message error"><?php echo $error; ?></div>
        <?php endif; ?>

        <!-- Formulaire d'ajout/modification -->
        <?php if(isset($_GET['action']) && $_GET['action'] == 'add' || $user_edit): ?>
        <div class="card">
            <h3><?php echo $user_edit ? 'Modifier l\'utilisateur' : 'Ajouter un nouvel utilisateur'; ?></h3>
            <form method="POST">
                <?php if($user_edit): ?>
                    <input type="hidden" name="id" value="<?php echo $user_edit['id']; ?>">
                <?php endif; ?>
                
                <div class="form-group">
                    <label for="nom">Nom complet :</label>
                    <input type="text" id="nom" name="nom" required 
                           value="<?php echo $user_edit ? htmlspecialchars($user_edit['nom']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="email">Email :</label>
                    <input type="email" id="email" name="email" required 
                           value="<?php echo $user_edit ? htmlspecialchars($user_edit['email']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="password">Mot de passe <?php echo $user_edit ? '(laisser vide pour ne pas changer)' : ''; ?> :</label>
                    <input type="password" id="password" name="password" 
                           <?php echo $user_edit ? '' : 'required'; ?>>
                </div>
                
                <div class="form-group">
                    <label for="role">Rôle :</label>
                    <select id="role" name="role" required>
                        <option value="utilisateur" <?php echo ($user_edit && $user_edit['role'] == 'utilisateur') ? 'selected' : ''; ?>>Utilisateur</option>
                        <option value="admin" <?php echo ($user_edit && $user_edit['role'] == 'admin') ? 'selected' : ''; ?>>Administrateur</option>
                    </select>
                </div>
                
                <button type="submit" name="<?php echo $user_edit ? 'edit_user' : 'add_user'; ?>" 
                        class="btn btn-success">
                    <?php echo $user_edit ? 'Modifier' : 'Ajouter'; ?>
                </button>
                <a href="utilisateurs.php" class="btn btn-danger">Annuler</a>
            </form>
        </div>
        <?php else: ?>
            <a href="utilisateurs.php?action=add" class="btn btn-success" style="margin-bottom: 20px;">+ Ajouter un utilisateur</a>
        <?php endif; ?>

        <!-- Liste des utilisateurs -->
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nom</th>
                    <th>Email</th>
                    <th>Rôle</th>
                    <th>Date de création</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($utilisateurs as $user): ?>
                <tr>
                    <td><?php echo $user['id']; ?></td>
                    <td><strong><?php echo htmlspecialchars($user['nom']); ?></strong></td>
                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                    <td>
                        <span class="badge <?php echo $user['role'] == 'admin' ? 'terminee' : 'en-cours'; ?>">
                            <?php echo $user['role'] == 'admin' ? 'Admin' : 'Utilisateur'; ?>
                        </span>
                    </td>
                    <td><?php echo date('d/m/Y H:i', strtotime($user['date_creation'])); ?></td>
                    <td class="actions">
                        <a href="utilisateurs.php?edit=<?php echo $user['id']; ?>" class="btn btn-warning">Modifier</a>
                        <?php if($user['id'] != $_SESSION['user_id']): ?>
                            <a href="utilisateurs.php?delete=<?php echo $user['id']; ?>" 
                               class="btn btn-danger"
                               onclick="return confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ? Toutes ses tâches seront également supprimées.')">
                                Supprimer
                            </a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
