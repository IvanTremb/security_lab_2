<?php
// Настройки подключения к базе данных
$host = 'localhost';
$database = 'vulnerable_notes';
$username = 'root';
$password = '';

// Общие функции безопасности
require_once __DIR__ . '/includes/functions.php';

// Ошибки не показываются пользователю, они записываются в журнал logs/
ini_set('display_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);
if (!is_dir(__DIR__ . '/logs')) {
    @mkdir(__DIR__ . '/logs', 0750, true);
}
ini_set('error_log', __DIR__ . '/logs/php_errors.log');

// Заголовки безопасности браузера
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: same-origin');

// Подключение к базе.
// ERRMODE_EXCEPTION - ошибки обрабатываются в коде, EMULATE_PREPARES=false - настоящие prepared statements.
try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$database;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $exception) {
    app_log('ERROR', 'Не удалось подключиться к базе: ' . $exception->getMessage());
    http_response_code(500);
    exit('Сервис временно недоступен. Попробуйте позже.');
}

// Настройки сессии: cookie недоступна из JavaScript, не уходит на другие сайты
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'httponly' => true,
    'samesite' => 'Strict',
]);
ini_set('session.use_strict_mode', '1');
session_start();

// Проверка времени бездействия
enforce_session_timeout();
