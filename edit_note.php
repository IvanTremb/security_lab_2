<?php
require_once 'config.php';

require_login();

$userId = (int) $_SESSION['user_id'];
$id = clean_id($_GET['id'] ?? null);

// Заметку можно открыть только если она принадлежит текущему пользователю
$note = null;
if ($id > 0) {
    $stmt = $pdo->prepare('SELECT * FROM notes WHERE id = ? AND user_id = ?');
    $stmt->execute([$id, $userId]);
    $note = $stmt->fetch();
}

if (!$note) {
    app_log('WARNING', 'Попытка открыть чужую или несуществующую заметку, id=' . $id);
    header('Location: dashboard.php?error=notfound');
    exit;
}

$errors = [];
$title = $note['title'];
$content = $note['content'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $title = trim((string) ($_POST['title'] ?? ''));
    $content = trim((string) ($_POST['content'] ?? ''));

    if (mb_strlen($title) < 1 || mb_strlen($title) > 255) {
        $errors[] = 'Заголовок обязателен и должен быть не длиннее 255 символов.';
    }
    if (mb_strlen($content) < 1 || mb_strlen($content) > 10000) {
        $errors[] = 'Текст заметки обязателен и должен быть не длиннее 10000 символов.';
    }

    if (!$errors) {
        try {
            // В WHERE снова проверяется владелец заметки
            $stmt = $pdo->prepare('UPDATE notes SET title = ?, content = ? WHERE id = ? AND user_id = ?');
            $stmt->execute([$title, $content, $id, $userId]);

            app_log('INFO', 'Изменена заметка, id=' . $id);
            header('Location: dashboard.php');
            exit;
        } catch (PDOException $exception) {
            app_log('ERROR', 'Ошибка при изменении заметки: ' . $exception->getMessage());
            $errors[] = 'Не удалось сохранить изменения. Попробуйте позже.';
        }
    }
}
?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Изменение заметки</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<main class="card">
    <h1>Изменение заметки #<?= $id ?></h1>

    <?php foreach ($errors as $error): ?>
        <div class="message error"><?= e($error) ?></div>
    <?php endforeach; ?>

    <form method="post" action="edit_note.php?id=<?= $id ?>">
        <?= csrf_field() ?>

        <label>Заголовок</label>
        <input type="text" name="title" maxlength="255" value="<?= e($title) ?>">

        <label>Текст</label>
        <textarea name="content" rows="8" maxlength="10000"><?= e($content) ?></textarea>

        <button type="submit">Сохранить изменения</button>
        <a class="button secondary" href="dashboard.php">Отмена</a>
    </form>
</main>
</body>
</html>
