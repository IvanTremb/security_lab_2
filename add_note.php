<?php
require_once 'config.php';

require_login();

$errors = [];
$title = '';
$content = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $title = trim((string) ($_POST['title'] ?? ''));
    $content = trim((string) ($_POST['content'] ?? ''));

    // Серверная проверка
    if (mb_strlen($title) < 1 || mb_strlen($title) > 255) {
        $errors[] = 'Заголовок обязателен и должен быть не длиннее 255 символов.';
    }
    if (mb_strlen($content) < 1 || mb_strlen($content) > 10000) {
        $errors[] = 'Текст заметки обязателен и должен быть не длиннее 10000 символов.';
    }

    if (!$errors) {
        try {
            $stmt = $pdo->prepare('INSERT INTO notes (user_id, title, content) VALUES (?, ?, ?)');
            $stmt->execute([(int) $_SESSION['user_id'], $title, $content]);

            app_log('INFO', 'Создана заметка, id=' . $pdo->lastInsertId());
            header('Location: dashboard.php');
            exit;
        } catch (PDOException $exception) {
            app_log('ERROR', 'Ошибка при создании заметки: ' . $exception->getMessage());
            $errors[] = 'Не удалось сохранить заметку. Попробуйте позже.';
        }
    }
}
?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Новая заметка</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<main class="card">
    <h1>Новая заметка</h1>

    <?php foreach ($errors as $error): ?>
        <div class="message error"><?= e($error) ?></div>
    <?php endforeach; ?>

    <form method="post">
        <?= csrf_field() ?>

        <label>Заголовок</label>
        <input type="text" name="title" maxlength="255" value="<?= e($title) ?>">

        <label>Текст</label>
        <textarea name="content" rows="8" maxlength="10000"><?= e($content) ?></textarea>

        <button type="submit">Сохранить</button>
        <a class="button secondary" href="dashboard.php">Отмена</a>
    </form>
</main>
</body>
</html>
