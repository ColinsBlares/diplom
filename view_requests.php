<?php
session_start();
require_once 'db.php';
$pdo = require 'db.php';

// Проверка авторизации
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Проверка прав доступа (только администраторы или владельцы могут просматривать заявки)
if (!in_array($_SESSION['role'], ['admin', 'owner'])) {
    die("У вас нет прав для просмотра заявок.");
}

// Получение ID ТСЖ из сессии
$tsj_id = $_SESSION['tsj_id'];

// Обработка изменения статуса заявки
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['request_id'])) {
    $request_id = intval($_POST['request_id']);
    $status = trim($_POST['status']);

    // Валидация данных
    if ($request_id <= 0 || !in_array($status, ['pending', 'in_progress', 'completed', 'rejected'])) {
        die("Некорректные данные.");
    }

    try {
        // Обновляем статус заявки
        $stmt_update = $pdo->prepare("
            UPDATE service_requests 
            SET status = :status 
            WHERE id = :id AND tsj_id = :tsj_id
        ");
        $stmt_update->execute([
            'status' => $status,
            'id' => $request_id,
            'tsj_id' => $tsj_id
        ]);

        // Перенаправляем обратно на страницу
        header("Location: view_requests.php");
        exit;
    } catch (Exception $e) {
        die("Ошибка обновления данных: " . $e->getMessage());
    }
}

// Получение списка заявок для текущего ТСЖ
$stmt_requests = $pdo->prepare("
    SELECT sr.*, u.username 
    FROM service_requests sr 
    JOIN users u ON sr.user_id = u.id 
    WHERE sr.tsj_id = :tsj_id 
    ORDER BY sr.created_at DESC
");
$stmt_requests->execute(['tsj_id' => $tsj_id]);
$requests = $stmt_requests->fetchAll();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Просмотр заявок | ТСЖ</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f5f5f5;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        h1 {
            text-align: center;
            color: #333;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #f3f3f3;
        }
        tr:hover {
            background-color: #f8f9fa;
        }
        form {
            margin-top: 20px;
        }
        label {
            display: block;
            margin-bottom: 5px;
        }
        select, input[type="submit"] {
            width: 100%;
            padding: 10px;
            margin-bottom: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        input[type="submit"] {
            background-color: #4CAF50;
            color: white;
            cursor: pointer;
        }
        input[type="submit"]:hover {
            background-color: #45a049;
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
            font-size: 14px;
            transition: background-color 0.3s;
        }
        .btn:hover {
            background-color: #45a049;
        }
        .actions {
            margin-top: 20px;
        }
        .actions a {
            margin-right: 10px;
        }
    </style>
</head>
<body>
<div class="container">
    <h1>Просмотр заявок на обслуживание</h1>

    <!-- Кнопка для добавления новой заявки -->
    <div class="actions">
        <a href="add_request.php" class="btn">Добавить заявку</a>
    </div>

    <!-- Список заявок -->
    <table>
        <thead>
            <tr>
                <th>Владелец</th>
                <th>Дата создания</th>
                <th>Описание</th>
                <th>Статус</th>
                <th>Действия</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($requests as $request): ?>
                <tr>
                    <td><?= htmlspecialchars($request['username']) ?></td>
                    <td><?= htmlspecialchars($request['created_at']) ?></td>
                    <td><?= htmlspecialchars($request['description']) ?></td>
                    <td><?= htmlspecialchars($request['status']) ?></td>
                    <td>
                        <form method="POST" action="">
                            <input type="hidden" name="request_id" value="<?= $request['id'] ?>">
                            <label for="status_<?= $request['id'] ?>">Изменить статус:</label>
                            <select name="status" id="status_<?= $request['id'] ?>" required>
                                <option value="pending" <?= $request['status'] === 'pending' ? 'selected' : '' ?>>В ожидании</option>
                                <option value="in_progress" <?= $request['status'] === 'in_progress' ? 'selected' : '' ?>>В работе</option>
                                <option value="completed" <?= $request['status'] === 'completed' ? 'selected' : '' ?>>Завершено</option>
                                <option value="rejected" <?= $request['status'] === 'rejected' ? 'selected' : '' ?>>Отклонено</option>
                            </select>
                            <input type="submit" value="Сохранить">
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
</body>
</html>