-- Миграция: одна согласованная оценка вместо оценок каждого эксперта.
-- Бумажные протоколы экспертов остаются; в БД хранится только итог сведения.

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS scores;

CREATE TABLE scores (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    contestant_id INT UNSIGNED NOT NULL,
    criterion_id INT UNSIGNED NOT NULL,
    points DECIMAL(10,2) NOT NULL DEFAULT 0,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_scores_contestant
        FOREIGN KEY (contestant_id) REFERENCES contestants(id) ON DELETE CASCADE,
    CONSTRAINT fk_scores_criterion
        FOREIGN KEY (criterion_id) REFERENCES criteria(id) ON DELETE CASCADE,
    UNIQUE KEY uq_score (contestant_id, criterion_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Колонка score_method больше не используется (оставляем, если есть — не мешает)
-- ALTER TABLE specialties DROP COLUMN score_method; -- опционально

SET FOREIGN_KEY_CHECKS = 1;
