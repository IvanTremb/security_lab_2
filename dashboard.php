<?php
require_once 'config.php';

require_login();

$userId = (int) $_SESSION['user_id'];

// Показываем только заметки текущего пользователя
$stmt = $pdo->prepare(
    'SELECT id, title, content, created_at FROM notes WHERE user_id = ? ORDER BY created_at DESC'
);
$stmt->execute([$userId]);
$notes = $stmt->fetchAll();

$error = '';
if (($_GET['error'] ?? '') === 'notfound') {
    $error = 'Заметка не найдена или у вас нет доступа к ней.';
}
?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Мои заметки | Vulnerable Notes</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<header class="topbar">
    <strong>Vulnerable Notes</strong>
    <nav>
        <a href="dashboard.php">Мои заметки</a>
        <a href="add_note.php">Добавить</a>
        <?php if (($_SESSION['role'] ?? '') === 'admin'): ?>
            <a href="admin.php">Администрирование</a>
        <?php endif; ?>
        <a href="logout.php">Выйти</a>
    </nav>
</header>

<main class="container">
    <h1>Здравствуйте, <?= e($_SESSION['name'] ?? '') ?>!</h1>
    <p>Ваш уровень доступа: <strong><?= e($_SESSION['role'] ?? '') ?></strong></p>

    <?php if ($error): ?>
        <div class="message error"><?= e($error) ?></div>
    <?php endif; ?>

    <div class="actions">
        <a class="button" href="add_note.php">Новая заметка</a>
    </div>

    <?php if (!$notes): ?>
        <div class="empty">У вас пока нет заметок.</div>
    <?php endif; ?>

    <section class="notes">
        <?php foreach ($notes as $note): ?>
            <article class="note">
                <h2><?= e($note['title']) ?></h2>
                <div><?= nl2br(e($note['content'])) ?></div>
                <small><?= e($note['created_at']) ?></small>
                <div class="note-actions">
                    <a href="edit_note.php?id=<?= (int) $note['id'] ?>">Изменить</a>
                    <form method="post" action="delete_note.php" class="inline-form"
                          onsubmit="return confirm('Удалить заметку?');">
                        <?= csrf_field() ?>
                        <input type="hidden" name="id" value="<?= (int) $note['id'] ?>">
                        <button type="submit" class="link-button danger">Удалить</button>
                    </form>
                </div>
            </article>
        <?php endforeach; ?>
    </section>
</main>
</body>
</html>
