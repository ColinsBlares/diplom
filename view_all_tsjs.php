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

// Получение списка всех ТСЖ
$stmt = $pdo->query("SELECT * FROM tsj ORDER BY created_at DESC");
$tsjs = $stmt ? $stmt->fetchAll() : [];
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Просмотреть все ТСЖ | ТСЖ</title>
    <style>
                /* Общие стили */
        body {
            font-family: Arial, sans-serif;
            background-color: #f2f2f2;
            margin: 0;
        }

        .container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        h1 {
            color: #2c3e50;
            text-align: center;
            margin-bottom: 30px;
        }

        /* Стиль таблицы */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }

        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        th {
            background-color: #f3f3f3;
            font-weight: bold;
        }

        tr:hover {
            background-color: #f8f9fa;
        }

        /* Стиль кнопок */
        .btn {
            display: inline-block;
            padding: 8px 15px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
            transition: background-color 0.3s;
        }

        .btn-danger {
            background-color: #e74c3c;
        }

        .btn:hover {
            background-color: #45a049;
        }

        .btn-danger:hover {
            background-color: #c0392b;
        }

        /* Стиль сообщений */
        .success {
            color: green;
            margin-bottom: 15px;
            font-size: 14px;
        }

        .error {
            color: red;
            margin-bottom: 15px;
            font-size: 14px;
        }
    </style>
</head>
<body>
<div class="container">
    <h1>Просмотреть все ТСЖ</h1>
    <p>Добро пожаловать, <?php echo htmlspecialchars($_SESSION['username']); ?>!</p>

    <!-- Таблица со списком всех ТСЖ -->
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Название</th>
                <th>Адрес</th>
                <th>Председатель</th>
                <th>Дата создания</th>
                <th>Действия</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($tsjs)): ?>
                <?php foreach ($tsjs as $tsj): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($tsj['id']); ?></td>
                        <td><?php echo htmlspecialchars($tsj['name']); ?></td>
                        <td><?php echo htmlspecialchars($tsj['address']); ?></td>

                        <!-- Председатель -->
                        <td>
                            <?php
                            // Находим текущего председателя
                            $stmt_chairman = $pdo->prepare("SELECT username FROM users WHERE id = :id AND role = 'chairman'");
                            $stmt_chairman->execute(['id' => $tsj['owner_id']]);
                            $chairman = $stmt_chairman->fetchColumn();
                            echo htmlspecialchars($chairman ?? 'Не назначен');
                            ?>
                        </td>

                        <td><?php echo htmlspecialchars($tsj['created_at']); ?></td>
                        <td>
                            <!-- Кнопка "Пригласить" -->
                            <a href="invite_user.php?tsj_id=<?php echo htmlspecialchars($tsj['id']); ?>" class="btn">Пригласить</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="6">Нет созданных ТСЖ.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- Кнопка для возврата -->
    <p><a href="admin_dashboard.php" class="btn">Назад</a></p>
</div>
</body>
</html>