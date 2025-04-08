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

// Получение данных пользователя
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id");
$stmt->execute(['id' => $user_id]);
$user = $stmt->fetch();

// Получение общей статистики
$total_users = $pdo->query("SELECT COUNT(*) AS count FROM users")->fetchColumn();
$total_tsj = $pdo->query("SELECT COUNT(*) AS count FROM tsj")->fetchColumn();

// Последние созданные ТСЖ
$stmt_tsj = $pdo->query("SELECT * FROM tsj ORDER BY created_at DESC LIMIT 5");
$recent_tsjs = $stmt_tsj ? $stmt_tsj->fetchAll() : [];

// Список всех пользователей
$stmt_users = $pdo->query("SELECT * FROM users ORDER BY created_at DESC LIMIT 10");
$recent_users = $stmt_users ? $stmt_users->fetchAll() : [];
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Админ-панель | ТСЖ</title>
    <style>
        /* Общие стили */
        body {
            font-family: Arial, sans-serif;
            background-color: #f2f2f2;
            margin: 0;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        h1, h2 {
            text-align: center;
            color: #333;
        }

        .section {
            margin-bottom: 20px;
        }

        .stats {
            display: flex;
            justify-content: space-around;
        }

        .stat-box {
            padding: 15px;
            background-color: #f4f4f4;
            border-radius: 5px;
            text-align: center;
            width: 200px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 10px;
            border: 1px solid #ddd;
            text-align: left;
        }

        th {
            background-color: #f4f4f4;
        }

        .btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            font-size: 16px;
            transition: background-color 0.3s ease;
        }

        .btn:hover {
            background-color: #45a049;
        }

        a {
            color: #4CAF50;
            text-decoration: none;
            font-weight: bold;
        }

        a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
<div class="container">
    <h1>Админ-панель ТСЖ</h1>
    <p>Добро пожаловать, <?php echo htmlspecialchars($user['username']); ?>!</p>

    <!-- Раздел: Статистика -->
    <div class="section">
        <h2>Общая статистика</h2>
        <div class="stats">
            <div class="stat-box">
                <strong>Всего пользователей:</strong><br>
                <?php echo htmlspecialchars($total_users); ?>
            </div>
            <div class="stat-box">
                <strong>Всего ТСЖ:</strong><br>
                <?php echo htmlspecialchars($total_tsj); ?>
            </div>
        </div>
    </div>

    <!-- Раздел: Последние созданные ТСЖ -->
    <div class="section">
        <h2>Последние созданные ТСЖ</h2>
        <table>
            <thead>
                <tr>
                    <th>Название</th>
                    <th>Адрес</th>
                    <th>Владелец</th>
                    <th>Дата создания</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($recent_tsjs)): ?>
                    <?php foreach ($recent_tsjs as $tsj): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($tsj['name']); ?></td>
                            <td><?php echo htmlspecialchars($tsj['address']); ?></td>
                            <td>
                                <?php
                                // Получаем имя владельца ТСЖ
                                $owner_stmt = $pdo->prepare("SELECT username FROM users WHERE id = :owner_id");
                                $owner_stmt->execute(['owner_id' => $tsj['owner_id']]);
                                $owner = $owner_stmt->fetchColumn();
                                echo htmlspecialchars($owner ?? 'Неизвестно');
                                ?>
                            </td>
                            <td><?php echo htmlspecialchars($tsj['created_at']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="4">Нет созданных ТСЖ.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Раздел: Последние зарегистрированные пользователи -->
    <div class="section">
        <h2>Последние зарегистрированные пользователи</h2>
        <table>
            <thead>
                <tr>
                    <th>Имя пользователя</th>
                    <th>Email</th>
                    <th>Роль</th>
                    <th>Дата регистрации</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($recent_users)): ?>
                    <?php foreach ($recent_users as $recent_user): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($recent_user['username']); ?></td>
                            <td><?php echo htmlspecialchars($recent_user['email']); ?></td>
                            <td><?php echo htmlspecialchars($recent_user['role']); ?></td>
                            <td><?php echo htmlspecialchars($recent_user['created_at']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="4">Нет зарегистрированных пользователей.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Раздел: Действия -->
    <div class="section">
        <h2>Действия</h2>
        <ul>
            <li><a href="create_tsj.php" class="btn">Создать новое ТСЖ</a></li>
            <li><a href="manage_users.php" class="btn">Управление пользователями</a></li>
            <li><a href="view_all_tsjs.php" class="btn">Просмотреть все ТСЖ</a></li>
            <li><a href="profile.php" class="btn">Назад в профиль</a></li>
            <li><a href="logout.php" class="btn">Выйти</a></li>
        </ul>
    </div>
</div>
</body>
</html>