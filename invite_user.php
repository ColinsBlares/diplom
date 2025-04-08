<?php
session_start();

// Подключение к базе данных через db.php
require_once 'db.php';
$pdo = require 'db.php';

// Проверка прав доступа
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// Получаем ID ТСЖ из параметров GET
$tsj_id = intval($_GET['tsj_id'] ?? 0);

// Проверяем, что ТСЖ существует
$stmt_tsj = $pdo->prepare("SELECT * FROM tsj WHERE id = :id");
$stmt_tsj->execute(['id' => $tsj_id]);
$tsj = $stmt_tsj->fetch();

if (!$tsj) {
    die("ТСЖ не найдено.");
}

// Переменные для сообщений
$success = null;
$errors = [];

// Обработка отправки формы
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username'] ?? '');

    // Поиск пользователя по имени
    $stmt_user = $pdo->prepare("SELECT id FROM users WHERE username = :username AND role = 'user'");
    $stmt_user->execute(['username' => $username]);
    $user = $stmt_user->fetch();

    if (!$user) {
        $errors[] = "Пользователь с таким именем не найден или уже имеет роль отличную от 'user'.";
    } else {
        // Проверяем, есть ли уже приглашение для этого пользователя в это ТСЖ
        $stmt_check_invitation = $pdo->prepare("SELECT COUNT(*) AS count FROM invitations WHERE tsj_id = :tsj_id AND user_id = :user_id");
        $stmt_check_invitation->execute(['tsj_id' => $tsj_id, 'user_id' => $user['id']]);
        $count = $stmt_check_invitation->fetchColumn();

        if ($count > 0) {
            $errors[] = "Пользователь уже был приглашен в это ТСЖ.";
        } else {
            try {
                // Добавляем приглашение в базу данных
                $stmt_invite = $pdo->prepare("INSERT INTO invitations (tsj_id, user_id, invited_by) VALUES (:tsj_id, :user_id, :invited_by)");
                $stmt_invite->execute([
                    'tsj_id' => $tsj_id,
                    'user_id' => $user['id'],
                    'invited_by' => $_SESSION['user_id']
                ]);

                $success = "Приглашение успешно отправлено!";
            } catch (Exception $e) {
                $errors[] = "Ошибка отправки приглашения: " . $e->getMessage();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Отправить приглашение | ТСЖ</title>
    <style>
        /* Стили аналогичны предыдущим */
    </style>
</head>
<body>
<div class="container">
    <h2>Отправить приглашение в ТСЖ "<?php echo htmlspecialchars($tsj['name']); ?>"</h2>

    <?php if ($success): ?>
        <p class="success"><?php echo $success; ?></p>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div class="error">
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <!-- Форма для отправки приглашения -->
    <form method="POST" action="">
        <label for="username">Имя пользователя:</label>
        <input type="text" id="username" name="username" required>

        <button type="submit" class="btn">Отправить приглашение</button>
    </form>

    <!-- Кнопка для возврата -->
    <p><a href="view_all_tsjs.php" class="btn">Назад</a></p>
</div>
</body>
</html>