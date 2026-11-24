-- Защищённая версия: пароли хранятся в виде хэшей (bcrypt), добавлен индекс по владельцу заметки.
CREATE DATABASE IF NOT EXISTS vulnerable_notes
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE vulnerable_notes;

DROP TABLE IF EXISTS notes;
DROP TABLE IF EXISTS users;

CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,        -- хэш пароля (bcrypt, 60 символов), не открытый пароль
    role ENUM('user', 'admin') NOT NULL DEFAULT 'user',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE notes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_notes_user (user_id),
    CONSTRAINT fk_notes_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE
);

-- Демонстрационные пользователи. Пароли те же, что в README: admin123 и student123.
-- В базе сохранены только их хэши.
INSERT INTO users (name, email, password, role) VALUES
('Администратор', 'admin@example.local', '$2y$10$XGnncR5hiB3BQexlk3sV9e15nb.z25uhbY0wGJScb.3/Z7uwTMyMS', 'admin'),
('Анна', 'anna@example.local', '$2y$10$sPOFQn/11wL5i/QZF4HAO.O7Y9WDMSqq5b9/x/OA2I/uL44G1m6FC', 'user'),
('Иван', 'ivan@example.local', '$2y$10$sPOFQn/11wL5i/QZF4HAO.O7Y9WDMSqq5b9/x/OA2I/uL44G1m6FC', 'user');

INSERT INTO notes (user_id, title, content) VALUES
(1, 'Системная заметка', 'Проверить права доступа к административной странице.'),
(2, 'Личная заметка Анны', 'Эту запись не должен изменять другой пользователь.'),
(3, 'Личная заметка Ивана', 'Проверить защиту от IDOR и XSS.');
