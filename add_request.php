<?php
session_start();
require_once 'db.php';
$pdo = require 'db.php';

// Проверка авторизации
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Получение ID пользователя и ТСЖ из сессии
$user_id = $_SESSION['user_id'];
$tsj_id = $_SESSION['tsj_id'];

// Обработка отправки формы
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $description = trim($_POST['description'] ?? '');

    // Валидация данных
    if (empty($description)) {
        die("Некорректные данные.");
    }

    try {
        // Добавляем новую заявку в базу данных
        $stmt_add = $pdo->prepare("
            INSERT INTO service_requests (tsj_id, user_id, description, status) 
            VALUES (:tsj_id, :user_id, :description, 'pending')
        ");
        $stmt_add->execute([
            'tsj_id' => $tsj_id,
            'user_id' => $user_id,
            'description' => $description
        ]);

        // Перенаправляем обратно на страницу просмотра заявок
        header("Location: view_requests.php");
        exit;
    } catch (Exception $e) {
        die("Ошибка добавления заявки: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Добавить заявку | ТСЖ</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f5f5f5;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 600px;
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
        label {
            display: block;
            margin-bottom: 5px;
            color: #555;
        }
        textarea {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 14px;
            resize: vertical;
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
    </style>
</head>
<body>
<div class="container">
    <h1>Добавить заявку на обслуживание</h1>
    <form method="POST" action="">
        <label for="description">Описание проблемы:</label>
        <textarea id="description" name="description" rows="4" required></textarea>

        <button type="submit" class="btn">Добавить заявку</button>
    </form>
</div>
</body>
</html>