<?php
/*
 * Общие функции безопасности.
 * Подключаются через config.php, отдельно открывать этот файл не нужно.
 */

/**
 * Безопасный вывод текста в HTML (защита от XSS).
 * Любые данные пользователя выводим только через эту функцию.
 */
function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Запись события в журнал logs/app.log.
 * Пароли в журнал не пишутся.
 */
function app_log(string $level, string $message): void
{
    $dir = __DIR__ . '/../logs';
    if (!is_dir($dir)) {
        @mkdir($dir, 0750, true);
    }

    $userId = $_SESSION['user_id'] ?? '-';
    $ip = $_SERVER['REMOTE_ADDR'] ?? '-';
    $page = basename($_SERVER['SCRIPT_NAME'] ?? '-');

    $line = sprintf(
        "[%s] %s | страница: %s | пользователь: %s | IP: %s | %s\n",
        date('Y-m-d H:i:s'),
        $level,
        $page,
        $userId,
        $ip,
        $message
    );

    @file_put_contents($dir . '/app.log', $line, FILE_APPEND | LOCK_EX);
}

/**
 * Проверка ID из адреса или формы. Возвращает число больше нуля или 0.
 */
function clean_id($value): int
{
    $id = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    return $id === false ? 0 : $id;
}

/**
 * Токен защиты от CSRF: одна случайная строка на сессию.
 */
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Скрытое поле с токеном, которое вставляется в каждую форму.
 */
function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

/**
 * Проверка токена при отправке формы. Без правильного токена запрос отклоняется.
 */
function verify_csrf(): void
{
    $sent = $_POST['csrf_token'] ?? '';

    if (!is_string($sent) || !hash_equals(csrf_token(), $sent)) {
        app_log('WARNING', 'Отклонён запрос с неверным CSRF-токеном');
        http_response_code(403);
        exit('Запрос отклонён. Обновите страницу и попробуйте снова.');
    }
}

/**
 * Вход пользователя: новая сессия (защита от фиксации сессии).
 */
function login_user(array $user): void
{
    session_regenerate_id(true);

    $_SESSION['user_id'] = (int) $user['id'];
    $_SESSION['name'] = $user['name'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['last_activity'] = time();

    // После входа выдаём новый токен защиты от CSRF
    unset($_SESSION['csrf_token']);
}

/**
 * Полный выход: очистка данных сессии и удаление cookie из браузера.
 */
function logout_user(): void
{
    $_SESSION = [];

    $params = session_get_cookie_params();
    setcookie(session_name(), '', [
        'expires' => time() - 42000,
        'path' => $params['path'],
        'domain' => $params['domain'],
        'secure' => $params['secure'],
        'httponly' => true,
        'samesite' => 'Strict',
    ]);

    session_destroy();
}

/**
 * Автоматический выход, если пользователь не был активен 30 минут.
 */
function enforce_session_timeout(): void
{
    if (!isset($_SESSION['user_id'])) {
        return;
    }

    $idle = time() - ($_SESSION['last_activity'] ?? time());
    if ($idle > 1800) {
        app_log('INFO', 'Сессия завершена из-за бездействия');
        logout_user();
        session_start();
        header('Location: login.php?timeout=1');
        exit;
    }

    $_SESSION['last_activity'] = time();
}

/**
 * Страница доступна только вошедшему пользователю.
 */
function require_login(): void
{
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }
}

/**
 * Страница доступна только администратору.
 * Роль проверяется по базе данных, а не только по сессии.
 */
function require_admin(): void
{
    global $pdo;

    require_login();

    $stmt = $pdo->prepare('SELECT role FROM users WHERE id = ?');
    $stmt->execute([(int) $_SESSION['user_id']]);
    $role = $stmt->fetchColumn();

    if ($role !== 'admin') {
        app_log('WARNING', 'Попытка открыть страницу администратора без прав');
        http_response_code(403);
        exit('Доступ запрещён.');
    }
}
