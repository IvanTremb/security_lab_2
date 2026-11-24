<?php
require_once 'config.php';

$message = '';

if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

if (isset($_GET['timeout'])) {
    $message = 'Сессия завершена из-за бездействия. Войдите снова.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $email = mb_strtolower(trim((string) ($_POST['email'] ?? '')));
    $password = (string) ($_POST['password'] ?? '');

    if ($email === '' || $password === '' || mb_strlen($email) > 150 || strlen($password) > 72) {
        $message = 'Пользователь не найден или пароль неправильный.';
    } else {
        try {
            // Запрос с параметрами: введённый текст не становится частью SQL
            $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            // Пароль сравнивается с хэшем в базе
            if ($user && password_verify($password, $user['password'])) {
                login_user($user);
                app_log('INFO', 'Успешный вход в систему');
                header('Location: dashboard.php');
                exit;
            }

            app_log('WARNING', 'Неудачная попытка входа');
            // Одинаковое сообщение для неверного email и неверного пароля
            $message = 'Пользователь не найден или пароль неправильный.';
        } catch (PDOException $exception) {
            app_log('ERROR', 'Ошибка при входе: ' . $exception->getMessage());
            $message = 'Не удалось выполнить вход. Попробуйте позже.';
        }
    }
}
?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Вход | Vulnerable Notes</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<main class="card narrow">
    <div class="warning">Учебный проект. Использовать только локально.</div>
    <h1>Vulnerable Notes</h1>
    <p class="muted">Защищённая версия приложения.</p>

    <?php if ($message): ?>
        <div class="message error"><?= e($message) ?></div>
    <?php endif; ?>

    <form method="post">
        <?= csrf_field() ?>

        <label>Электронная почта</label>
        <input type="email" name="email" maxlength="150" autocomplete="username">

        <label>Пароль</label>
        <input type="password" name="password" maxlength="72" autocomplete="current-password">

        <button type="submit">Войти</button>
    </form>

    <p><a href="register.php">Создать аккаунт</a></p>
</main>
</body>
</html>
