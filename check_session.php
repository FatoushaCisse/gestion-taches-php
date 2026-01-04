<?php
// Vérifier si l'utilisateur est connecté
if(!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}
?>
