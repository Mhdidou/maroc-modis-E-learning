-- =====================================================================
--  MIGRATION SQL 03 - Objectif quotidien & journal de connexions
--  LMS Maroc-Modis · MySQL 8+
--  À exécuter sur une base EXISTANTE :
--    mysql -u root -p maroc-modis < database/schema_fr_03_activite_objectif.sql
--
--  (Pour une installation neuve, tout est déjà intégré à schema_fr_simple.sql.)
-- =====================================================================

USE `maroc-modis`;

-- ---------------------------------------------------------------------
-- 1) Objectif quotidien fixé par le superviseur lors de l'affectation
--    (nombre de leçons/modules à compléter par jour pour cette formation)
-- ---------------------------------------------------------------------
ALTER TABLE inscriptions
    ADD COLUMN objectif_quotidien TINYINT UNSIGNED NOT NULL DEFAULT 3
    AFTER statut;

-- ---------------------------------------------------------------------
-- 2) Journal des connexions (1 ligne par utilisateur et par jour)
--    Alimente la section « Activité durant la semaine ».
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS journal_connexions (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    utilisateur_id      INT UNSIGNED NOT NULL,
    jour                DATE NOT NULL,
    cree_le             TIMESTAMP NULL,
    UNIQUE KEY uq_utilisateur_jour (utilisateur_id, jour),
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id) ON DELETE CASCADE
) ENGINE=InnoDB;
