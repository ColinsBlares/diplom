<?php
session_start();

// Проверка авторизации
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Подключение базы данных
$pdo = require_once 'db.php';

// Получение данных пользователя
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id");
$stmt->execute(['id' => $user_id]);
$user = $stmt->fetch();

if (!$user) {
    session_destroy();
    header("Location: login.php");
    exit;
}

// Путь к админ-панели
$admin_panel_path = 'admin/admin_dashboard.php';

// Приветствие по времени суток
$hour = date('H');
if ($hour >= 5 && $hour < 12) $greeting = 'Доброе утро';
elseif ($hour >= 12 && $hour < 18) $greeting = 'Добрый день';
elseif ($hour >= 18 && $hour < 23) $greeting = 'Добрый вечер';
else $greeting = 'Доброй ночи';

// Извлечение данных из сессии
$role = htmlspecialchars($_SESSION['role'] ?? 'Неизвестно');
$tsj_id = htmlspecialchars($_SESSION['tsj_id'] ?? '');

// Проверка активного приглашения
$active_invitation = false;
$invitation = null;

if ($role === 'user') {
    $stmt_inv = $pdo->prepare("SELECT id FROM invitations WHERE user_id = :user_id AND status = 'pending' LIMIT 1");
    $stmt_inv->execute(['user_id' => $user_id]);
    $invitation = $stmt_inv->fetch();
    $active_invitation = $invitation !== false;
}
?>


<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Профиль | ТСЖ</title>
    <link rel="stylesheet" href="profile.css">
</head>
<body>
<div class="container">
    <h2>Профиль</h2>
    <p><?= $greeting ?>, <?= htmlspecialchars($user['username']) ?>!</p>

    <div class="user-info">
        <p><strong>Роль:</strong> <?= $role ?></p>
        <?php if (($role === 'owner' || $role === 'manager') && $tsj_id): ?>
            <p><strong>ТСЖ ID:</strong> <?= $tsj_id ?></p>
        <?php endif; ?>
    </div>

    <?php if ($active_invitation): ?>
        <div class="invitation-notice">
            <p style="color: green;"><strong>У вас есть активное приглашение присоединиться к ТСЖ!</strong></p>
            <a href="accept_invitation.php?id=<?= $invitation['id'] ?>" class="btn">Присоединиться</a>
        </div>
    <?php endif; ?>

    <div class="actions">
        <?php if ($role === 'admin'): ?>
            <p><a href="<?= htmlspecialchars($admin_panel_path) ?>" class="btn">Админ-панель</a></p>

        <?php elseif (($role === 'owner' || $role === 'manager') && $tsj_id): ?>
            <p><a href="dashboard/dashboard.php?id_tszh=<?= urlencode($tsj_id) ?>" class="btn">Панель управления ТСЖ</a></p>

        <?php elseif ($role === 'user'): ?>
            <p><a href="create_tsj" class="btn">Создать ТСЖ</a></p>
        <?php endif; ?>

        <p><a href="logout.php" class="btn">Выйти</a></p>
    </div>
</div>
</body>
</html>