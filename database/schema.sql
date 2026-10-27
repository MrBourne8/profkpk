SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    login VARCHAR(64) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    display_name VARCHAR(128) NOT NULL DEFAULT 'Администратор',
    role ENUM('admin', 'chief_expert') NOT NULL DEFAULT 'admin',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS competitions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    start_date DATE NULL,
    end_date DATE NULL,
    status ENUM('draft', 'active', 'finished') NOT NULL DEFAULT 'draft',
    notes TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS specialties (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    competition_id INT UNSIGNED NOT NULL,
    name VARCHAR(255) NOT NULL,
    experts_count TINYINT UNSIGNED NOT NULL DEFAULT 3,
    chief_expert_id INT UNSIGNED NULL,
    sort_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_specialties_competition
        FOREIGN KEY (competition_id) REFERENCES competitions(id) ON DELETE CASCADE,
    CONSTRAINT fk_specialties_chief_expert
        FOREIGN KEY (chief_expert_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS modules (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    specialty_id INT UNSIGNED NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT NULL,
    max_score DECIMAL(10,2) NULL,
    sort_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_modules_specialty
        FOREIGN KEY (specialty_id) REFERENCES specialties(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS criteria (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    module_id INT UNSIGNED NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT NULL,
    max_points DECIMAL(10,2) NOT NULL DEFAULT 0,
    -- JSON-массив до 5 допустимых значений кроме 0, например [1,2,3]
    allowed_values VARCHAR(255) NOT NULL DEFAULT '[]',
    sort_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_criteria_module
        FOREIGN KEY (module_id) REFERENCES modules(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS contestants (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    specialty_id INT UNSIGNED NOT NULL,
    number VARCHAR(32) NOT NULL,
    full_name VARCHAR(255) NOT NULL,
    group_name VARCHAR(128) NULL,
    -- Рабочее место по жеребьёвке (уникально в рамках компетенции)
    workplace INT UNSIGNED NULL,
    sort_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_contestants_specialty
        FOREIGN KEY (specialty_id) REFERENCES specialties(id) ON DELETE CASCADE,
    UNIQUE KEY uq_contestant_number (specialty_id, number),
    UNIQUE KEY uq_contestant_workplace (specialty_id, workplace)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS experts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    specialty_id INT UNSIGNED NOT NULL,
    number TINYINT UNSIGNED NOT NULL,
    full_name VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_experts_specialty
        FOREIGN KEY (specialty_id) REFERENCES specialties(id) ON DELETE CASCADE,
    UNIQUE KEY uq_expert_number (specialty_id, number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Одна согласованная оценка главного эксперта: конкурсант × критерий
CREATE TABLE IF NOT EXISTS scores (
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

SET FOREIGN_KEY_CHECKS = 1;
