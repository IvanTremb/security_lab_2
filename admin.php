<?php
require_once 'config.php';

require_admin();

// Пароли не выводятся даже администратору: в базе лежат только хэши
$users = $pdo->query(
    'SELECT id, name, email, role, created_at FROM users ORDER BY id'
)->fetchAll();

app_log('INFO', 'Администратор открыл список пользователей');
?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Администрирование</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<main class="container">
    <h1>Пользователи системы</h1>
    <p class="warning">Страница доступна только администратору. Пароли не отображаются.</p>
    <table>
        <thead>
        <tr>
            <th>ID</th>
            <th>Имя</th>
            <th>Email</th>
            <th>Роль</th>
            <th>Дата регистрации</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($users as $user): ?>
            <tr>
                <td><?= (int) $user['id'] ?></td>
                <td><?= e($user['name']) ?></td>
                <td><?= e($user['email']) ?></td>
                <td><?= e($user['role']) ?></td>
                <td><?= e($user['created_at']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <p><a class="button secondary" href="dashboard.php">Назад</a></p>
</main>
</body>
</html>
