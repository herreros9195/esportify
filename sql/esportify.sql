-- ============================================================
-- Esportify - Base de données relationnelle (MySQL/MariaDB)
-- ============================================================
-- Ce script crée la base de données, les tables, les contraintes
-- et insère des données de test.
-- ============================================================

DROP DATABASE IF EXISTS esportify;
CREATE DATABASE esportify CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE esportify;

-- ============================================================
-- Table : users (utilisateurs)
-- ============================================================
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pseudo VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('joueur', 'organisateur', 'administrateur') DEFAULT 'joueur',
    is_active TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_role (role),
    INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Table : events (événements e-sport)
-- ============================================================
CREATE TABLE events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(150) NOT NULL,
    description TEXT,
    max_players INT NOT NULL DEFAULT 10,
    start_date DATETIME NOT NULL,
    end_date DATETIME NOT NULL,
    organizer_id INT NOT NULL,
    status ENUM('en_attente', 'valide', 'non_valide', 'suspendu') DEFAULT 'en_attente',
    visible TINYINT(1) DEFAULT 0,
    started TINYINT(1) DEFAULT 0,
    started_at DATETIME NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (organizer_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_status (status),
    INDEX idx_visible (visible),
    INDEX idx_start_date (start_date),
    INDEX idx_organizer (organizer_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Table : event_images (images des événements)
-- ============================================================
CREATE TABLE event_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_id INT NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    is_main TINYINT(1) DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    INDEX idx_event (event_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Table : event_registrations (inscriptions aux événements)
-- ============================================================
CREATE TABLE event_registrations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_id INT NOT NULL,
    user_id INT NOT NULL,
    status ENUM('en_attente', 'accepte', 'refuse') DEFAULT 'en_attente',
    registered_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_registration (event_id, user_id),
    INDEX idx_event_user (event_id, user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Table : favorites (favoris des joueurs)
-- ============================================================
CREATE TABLE favorites (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    event_id INT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    UNIQUE KEY unique_favorite (user_id, event_id),
    INDEX idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Table : scores (scores des joueurs par événement)
-- ============================================================
CREATE TABLE scores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    event_id INT NOT NULL,
    score INT NOT NULL DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    INDEX idx_user_event (user_id, event_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Table : chat_messages (fil de discussion des événements)
-- ============================================================
CREATE TABLE chat_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_id INT NOT NULL,
    user_id INT NOT NULL,
    pseudo VARCHAR(50) NOT NULL,
    message TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    INDEX idx_event (event_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Insertion des données de test
-- ============================================================

-- Mots de passe hashés (bcrypt) correspondant à "Password123!"
-- $2y$10$UGvKNwJAnl3.Bl2haE.xpuoEATkIPDUK3ByRcmCkm3B6vkqvocAZK

INSERT INTO users (pseudo, email, password_hash, role) VALUES
('admin_esportify', 'admin@esportify.fr', '$2y$10$UGvKNwJAnl3.Bl2haE.xpuoEATkIPDUK3ByRcmCkm3B6vkqvocAZK', 'administrateur'),
('organisateur_1', 'org1@esportify.fr', '$2y$10$UGvKNwJAnl3.Bl2haE.xpuoEATkIPDUK3ByRcmCkm3B6vkqvocAZK', 'organisateur'),
('organisateur_2', 'org2@esportify.fr', '$2y$10$UGvKNwJAnl3.Bl2haE.xpuoEATkIPDUK3ByRcmCkm3B6vkqvocAZK', 'organisateur'),
('joueur_1', 'joueur1@esportify.fr', '$2y$10$UGvKNwJAnl3.Bl2haE.xpuoEATkIPDUK3ByRcmCkm3B6vkqvocAZK', 'joueur'),
('joueur_2', 'joueur2@esportify.fr', '$2y$10$UGvKNwJAnl3.Bl2haE.xpuoEATkIPDUK3ByRcmCkm3B6vkqvocAZK', 'joueur'),
('joueur_3', 'joueur3@esportify.fr', '$2y$10$UGvKNwJAnl3.Bl2haE.xpuoEATkIPDUK3ByRcmCkm3B6vkqvocAZK', 'joueur');

INSERT INTO events (title, description, max_players, start_date, end_date, organizer_id, status, visible) VALUES
('Tournoi League of Legends Saison 1', 'Compétition 5v5 sur League of Legends avec phases éliminatoires.', 10, '2026-06-15 18:00:00', '2026-06-15 22:00:00', 2, 'valide', 1),
('Cup Fortnite Battle Royale', 'Tournoi solo de Fortnite. 100 joueurs, le dernier survivant remporte la mise !', 100, '2026-06-20 14:00:00', '2026-06-20 18:00:00', 2, 'valide', 1),
('Championnat FIFA 26', 'Tournoi 1v1 sur FIFA 26. Console PS5 fournie sur place.', 32, '2026-07-05 19:00:00', '2026-07-05 23:00:00', 3, 'valide', 1),
('Speedrun Mario Kart', 'Compétition de speedrun sur Mario Kart 8 Deluxe.', 16, '2026-06-10 20:00:00', '2026-06-10 22:00:00', 3, 'valide', 1),
('Tournoi Valorant Esportify', 'Tournoi 5v5 sur Valorant. Cashprize pour l\'équipe gagnante.', 10, '2026-08-01 17:00:00', '2026-08-01 21:00:00', 2, 'en_attente', 0),
('Event Rocket League', 'Tournoi 3v3 sur Rocket League.', 12, '2026-05-10 16:00:00', '2026-05-10 19:00:00', 3, 'valide', 1);

INSERT INTO event_registrations (event_id, user_id, status) VALUES
(1, 4, 'accepte'),
(1, 5, 'accepte'),
(2, 4, 'accepte'),
(2, 6, 'accepte'),
(3, 5, 'accepte'),
(4, 6, 'accepte'),
(6, 4, 'accepte');

INSERT INTO favorites (user_id, event_id) VALUES
(4, 1),
(4, 3),
(5, 2);

INSERT INTO scores (user_id, event_id, score) VALUES
(4, 1, 1500),
(5, 1, 1200),
(4, 2, 850),
(6, 4, 2100);
