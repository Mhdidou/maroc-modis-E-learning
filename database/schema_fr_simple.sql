-- =====================================================================
--  SCHEMA SQL - LMS Maroc-Modis (version initiale, simplifiee)
--  MySQL 8+
--  Import : mysql -u root -p < database/schema_fr_simple.sql
-- =====================================================================

CREATE DATABASE IF NOT EXISTS `maroc-modis`
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `maroc-modis`;

SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------
-- Utilisateurs (les 3 acteurs)
-- ---------------------------------------------------------------------
CREATE TABLE utilisateurs (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nom_complet         VARCHAR(150) NOT NULL,
    email               VARCHAR(150) NOT NULL UNIQUE,
    mot_de_passe        VARCHAR(255) NOT NULL,
    role                ENUM('admin', 'apprenant', 'formateur', 'superviseur') NOT NULL DEFAULT 'apprenant',
    domaine             VARCHAR(100) NULL,          -- ex: Couture, Coupe, Qualite
    superviseur_id      INT UNSIGNED NULL,        -- lien apprenant -> superviseur
    cree_le             TIMESTAMP NULL,
    mis_a_jour_le       TIMESTAMP NULL,
    FOREIGN KEY (superviseur_id) REFERENCES utilisateurs(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Jetons de reinitialisation de mot de passe (fonction « mot de passe oublie »)
-- ---------------------------------------------------------------------
CREATE TABLE password_reset_tokens (
    email               VARCHAR(191) NOT NULL PRIMARY KEY,
    token               VARCHAR(255) NOT NULL,
    created_at          TIMESTAMP NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Formations
-- ---------------------------------------------------------------------
CREATE TABLE formations (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    titre               VARCHAR(200) NOT NULL,
    description         TEXT NULL,
    type                ENUM('certifiante', 'non_certifiante') NOT NULL DEFAULT 'non_certifiante',
    cree_par            INT UNSIGNED NOT NULL,    -- formateur
    cree_le             TIMESTAMP NULL,
    mis_a_jour_le       TIMESTAMP NULL,
    FOREIGN KEY (cree_par) REFERENCES utilisateurs(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Modules (contenu generique : pdf, video ou quiz)
-- ---------------------------------------------------------------------
CREATE TABLE modules (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    formation_id        INT UNSIGNED NOT NULL,
    type                ENUM('pdf', 'video', 'quiz') NOT NULL,
    titre               VARCHAR(200) NOT NULL,
    contenu             VARCHAR(500) NULL,           -- chemin fichier (pdf/video) ou NULL si quiz
    position            INT UNSIGNED NOT NULL DEFAULT 0,
    cree_le             TIMESTAMP NULL,
    mis_a_jour_le       TIMESTAMP NULL,
    FOREIGN KEY (formation_id) REFERENCES formations(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Questions de quiz
-- ---------------------------------------------------------------------
CREATE TABLE questions (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    module_id           INT UNSIGNED NOT NULL,    -- module de type 'quiz'
    enonce              TEXT NOT NULL,
    bonne_reponse       VARCHAR(255) NOT NULL,
    mauvaises_reponses  JSON NOT NULL,                -- ex: ["choix1", "choix2", "choix3"]
    cree_le             TIMESTAMP NULL,
    FOREIGN KEY (module_id) REFERENCES modules(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Inscriptions
-- ---------------------------------------------------------------------
CREATE TABLE inscriptions (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    utilisateur_id      INT UNSIGNED NOT NULL,
    formation_id        INT UNSIGNED NOT NULL,
    statut              ENUM('non_commencee', 'en_cours', 'terminee') NOT NULL DEFAULT 'non_commencee',
    inscrit_le          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    termine_le          TIMESTAMP NULL,
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id) ON DELETE CASCADE,
    FOREIGN KEY (formation_id) REFERENCES formations(id) ON DELETE CASCADE,
    UNIQUE KEY uq_utilisateur_formation (utilisateur_id, formation_id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Progression par module
-- ---------------------------------------------------------------------
CREATE TABLE progressions (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    inscription_id      INT UNSIGNED NOT NULL,
    module_id           INT UNSIGNED NOT NULL,
    statut              ENUM('non_commencee', 'en_cours', 'terminee') NOT NULL DEFAULT 'non_commencee',
    score               TINYINT UNSIGNED NULL,        -- pour les modules quiz
    termine_le          TIMESTAMP NULL,
    FOREIGN KEY (inscription_id) REFERENCES inscriptions(id) ON DELETE CASCADE,
    FOREIGN KEY (module_id) REFERENCES modules(id) ON DELETE CASCADE,
    UNIQUE KEY uq_inscription_module (inscription_id, module_id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Certificats
-- ---------------------------------------------------------------------
CREATE TABLE certificats (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    utilisateur_id      INT UNSIGNED NOT NULL,
    formation_id        INT UNSIGNED NOT NULL,
    chemin_fichier      VARCHAR(500) NOT NULL,
    delivre_le          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id) ON DELETE CASCADE,
    FOREIGN KEY (formation_id) REFERENCES formations(id) ON DELETE CASCADE
) ENGINE=InnoDB;

SET FOREIGN_KEY_CHECKS = 1;
