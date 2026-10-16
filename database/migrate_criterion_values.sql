SET NAMES utf8mb4;

-- Добавить колонку допустимых значений критерия (если ещё нет — выполняется из PHP)
ALTER TABLE criteria
    ADD COLUMN allowed_values VARCHAR(255) NOT NULL DEFAULT '[]' AFTER max_points;
