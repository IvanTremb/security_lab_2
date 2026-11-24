<?php
require_once 'config.php';

$message = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $name = trim((string) ($_POST['name'] ?? ''));
    $email = mb_strtolower(trim((string) ($_POST['email'] ?? '')));
    $password = (string) ($_POST['password'] ?? '');

    // Серверная проверка данных (в браузере проверка легко обходится)
    if (mb_strlen($name) < 2 || mb_strlen($name) > 100) {
        $errors[] = 'Имя должно быть от 2 до 100 символов.';
    }
    if (mb_strlen($email) > 150 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Введите корректный адрес электронной почты.';
    }
    if (strlen($password) < 8 || strlen($password) > 72) {
        $errors[] = 'Пароль должен быть от 8 до 72 символов.';
    }

    if (!$errors) {
        try {
            // Пароль сохраняется только в виде хэша
            $hash = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $pdo->prepare(
                'INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)'
            );
            $stmt->execute([$name, $email, $hash, 'user']);

            app_log('INFO', 'Зарегистрирован новый пользователь, id=' . $pdo->lastInsertId());
            $message = 'Регистрация выполнена. Теперь можно войти.';
            $name = $email = '';
        } catch (PDOException $exception) {
            if ($exception->getCode() === '23000') {
                // 23000 - такой email уже есть в базе
                $errors[] = 'Пользователь с таким адресом электронной почты уже зарегистрирован.';
            } else {
                app_log('ERROR', 'Ошибка при регистрации: ' . $exception->getMessage());
                $errors[] = 'Не удалось зарегистрироваться. Попробуйте позже.';
            }
        }
    }
}
?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Регистрация | Vulnerable Notes</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<main class="card narrow">
    <div class="warning">Учебный проект. Использовать только локально.</div>
    <h1>Регистрация</h1>

    <?php if ($message): ?>
        <div class="message"><?= e($message) ?></div>
    <?php endif; ?>

    <?php foreach ($errors as $error): ?>
        <div class="message error"><?= e($error) ?></div>
    <?php endforeach; ?>

    <form method="post">
        <?= csrf_field() ?>

        <label>Имя</label>
        <input type="text" name="name" maxlength="100" value="<?= e($name ?? '') ?>">

        <label>Электронная почта</label>
        <input type="email" name="email" maxlength="150" value="<?= e($email ?? '') ?>">

        <label>Пароль (от 8 символов)</label>
        <input type="password" name="password" minlength="8" maxlength="72" autocomplete="new-password">

        <button type="submit">Зарегистрироваться</button>
    </form>

    <p><a href="login.php">Уже есть аккаунт? Войти</a></p>
</main>
</body>
</html>
