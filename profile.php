<?php
session_start();
require_once 'db.php';
$pdo = require 'db.php';

// Проверка авторизации
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

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

// Определите желаемый путь к админ-панели
$admin_panel_path = 'admin/admin_dashboard.php'; // Пример: если папка admin находится в той же директории

?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Профиль | ТСЖ</title>
    <style>
        /* Общие стили */
        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f5f5f5;
            color: #333;
        }

        .container {
            max-width: 600px;
            margin: 40px auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        h2 {
            color: #2c3e50;
            margin-bottom: 20px;
        }

        p {
            font-size: 16px;
            margin-bottom: 15px;
        }

        /* Кнопки */
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
            transition: background-color 0.3s;
        }

        .btn:hover {
            background-color: #45a049;
        }

        /* Дополнительные стили для ссылок */
        a {
            color: #4CAF50;
            text-decoration: none;
            font-weight: bold;
        }

        a:hover {
            text-decoration: underline;
        }

        /* Раздел с информацией о пользователе */
        .user-info {
            margin-bottom: 30px;
        }

        .user-info strong {
            color: #34495e;
        }

        /* Раздел с действиями */
        .actions {
            margin-top: 20px;
        }

        .actions p {
            margin: 5px 0; /* Улучшено вертикальное расстояние между кнопками */
        }

        .actions a.btn {
            display: block; /* Кнопки на всю ширину на мобильных */
            width: 100%;
            box-sizing: border-box; /* Чтобы padding не увеличивал ширину */
            margin-bottom: 10px; /* Добавлено расстояние между кнопками */
        }

        /* Медиа-запросы для мобильных устройств */
        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }
        }
    </style>
</head>
<body>
<div class="container">
    <h2>Профиль</h2>
    <p>Добро пожаловать, <?= htmlspecialchars($user['username']) ?>!</p>

    <div class="user-info">
        <p><strong>Роль:</strong> <?= htmlspecialchars($_SESSION['role']) ?></p>
        <?php if ($_SESSION['role'] === 'owner' || $_SESSION['role'] === 'manager'): ?>
            <p><strong>ТСЖ ID:</strong> <?= htmlspecialchars($_SESSION['tsj_id']) ?></p>
        <?php endif; ?>
    </div>

    <div class="actions">
        <?php if ($_SESSION['role'] === 'admin'): ?>
            <p><a href="<?= htmlspecialchars($admin_panel_path) ?>" class="btn">Админ-панель</a></p>
        <?php elseif ($_SESSION['role'] === 'owner' || $_SESSION['role'] === 'manager'): ?>
            <p><a href="dashboard/dashboard.php?id_tszh=<?= $_SESSION['tsj_id'] ?>" class="btn">Панель управления ТСЖ</a></p>
        <?php endif; ?>

        <p><a href="logout.php" class="btn">Выйти</a></p>
    </div>
</div>
</body>
</html>