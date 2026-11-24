<?php
require_once 'config.php';

require_login();

// Удаление разрешено только отправкой формы (POST), не по ссылке
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: dashboard.php');
    exit;
}

verify_csrf();

$userId = (int) $_SESSION['user_id'];
$id = clean_id($_POST['id'] ?? null);

try {
    // Удаляется только заметка текущего пользователя
    $stmt = $pdo->prepare('DELETE FROM notes WHERE id = ? AND user_id = ?');
    $stmt->execute([$id, $userId]);

    if ($stmt->rowCount() > 0) {
        app_log('INFO', 'Удалена заметка, id=' . $id);
    } else {
        app_log('WARNING', 'Попытка удалить чужую или несуществующую заметку, id=' . $id);
        header('Location: dashboard.php?error=notfound');
        exit;
    }
} catch (PDOException $exception) {
    app_log('ERROR', 'Ошибка при удалении заметки: ' . $exception->getMessage());
}

header('Location: dashboard.php');
exit;
