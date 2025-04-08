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

// Переменные для сообщений
$success = null;
$errors = [];

// Удаление пользователя (через GET-параметр)
if (isset($_GET['delete'])) {
    $user_id = intval($_GET['delete']);
    if ($user_id == $_SESSION['user_id']) {
        $errors[] = "Нельзя удалить самого себя!";
    } else {
        try {
            // Удаление пользователя
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = :id");
            $stmt->execute(['id' => $user_id]);
            $success = "Пользователь удален!";
        } catch (Exception $e) {
            $errors[] = "Ошибка удаления: " . $e->getMessage();
        }
    }
}

// Получение списка всех пользователей
$stmt = $pdo->query("SELECT * FROM users ORDER BY created_at DESC");
$users = $stmt ? $stmt->fetchAll() : [];
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление пользователями | ТСЖ</title>
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
    <h1>Управление пользователями</h1>
    <p>Добро пожаловать, <?php echo htmlspecialchars($_SESSION['username']); ?>!</p>

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

    <!-- Таблица с пользователями -->
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Имя пользователя</th>
                <th>Email</th>
                <th>Роль</th>
                <th>Дата регистрации</th>
                <th>Действия</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($users)): ?>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($user['id']); ?></td>
                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td><?php echo htmlspecialchars($user['role']); ?></td>
                        <td><?php echo htmlspecialchars($user['created_at']); ?></td>
                        <td>
                            <!-- Кнопка редактирования (можно добавить страницу edit_user.php) -->
                            <a href="edit_user.php?id=<?php echo $user['id']; ?>" class="btn">Редактировать</a>
                            <!-- Кнопка удаления (с подтверждением) -->
                            <a href="manage_users.php?delete=<?php echo $user['id']; ?>" 
                               onclick="return confirm('Вы уверены, что хотите удалить этого пользователя?')" 
                               class="btn btn-danger">Удалить</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="6">Нет зарегистрированных пользователей.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- Кнопка для возврата -->
    <p><a href="admin_dashboard.php" class="btn">Назад</a></p>
</div>
</body>
</html>