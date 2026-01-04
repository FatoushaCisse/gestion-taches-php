-- Créer la base de données
CREATE DATABASE IF NOT EXISTS gestion_taches;
USE gestion_taches;

-- Table des utilisateurs
CREATE TABLE IF NOT EXISTS utilisateurs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    mot_de_passe VARCHAR(255) NOT NULL,
    role ENUM('admin', 'utilisateur') DEFAULT 'utilisateur',
    date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table des tâches
CREATE TABLE IF NOT EXISTS taches (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titre VARCHAR(200) NOT NULL,
    description TEXT,
    statut ENUM('en_cours', 'terminee') DEFAULT 'en_cours',
    user_id INT NOT NULL,
    date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    date_modification TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES utilisateurs(id) ON DELETE CASCADE
);

-- Insérer un administrateur par défaut
-- Mot de passe : admin123 (hashé avec sha1)
INSERT INTO utilisateurs (nom, email, mot_de_passe, role) 
VALUES ('Administrateur', 'admin@example.com', SHA1('admin123'), 'admin');

-- Insérer un utilisateur simple pour test
-- Mot de passe : user123 (hashé avec sha1)
INSERT INTO utilisateurs (nom, email, mot_de_passe, role) 
VALUES ('Utilisateur Test', 'user@example.com', SHA1('user123'), 'utilisateur');
