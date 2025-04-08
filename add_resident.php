<?php
session_start();

// Подключение к базе данных через db.php
require_once 'db.php';
$pdo = require 'db.php';

// Проверка авторизации
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Проверка прав доступа (только владельцы или менеджеры могут добавлять жильцов)
if (!in_array($_SESSION['role'], ['owner', 'manager'])) {
    header("Location: profile.php");
    exit;
}

// Получение ID ТСЖ из параметров GET
$id_tszh = intval($_GET['id_tszh'] ?? 0);

if ($id_tszh <= 0) {
    die("ID ТСЖ не указан.");
}

// Проверяем, что пользователь связан с указанным ТСЖ
if ($_SESSION['tsj_id'] != $id_tszh && $_SESSION['role'] !== 'admin') {
    die("У вас нет прав для управления этим ТСЖ.");
}

// Переменные для сообщений
$success = null;
$errors = [];

// Обработка отправки формы
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $full_name = trim($_POST['full_name'] ?? '');
    $apartment_number = trim($_POST['apartment_number'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    // Валидация данных
    if (empty($full_name)) {
        $errors[] = "ФИО жильца обязательно.";
    }

    if (empty($apartment_number)) {
        $errors[] = "Номер квартиры обязателен.";
    }

    if (empty($phone)) {
        $errors[] = "Телефон обязателен.";
    }

    // Если нет ошибок, сохраняем жильца
    if (empty($errors)) {
        try {
            // Добавляем жильца в базу данных
            $stmt_add_resident = $pdo->prepare("
                INSERT INTO residents (tsj_id, full_name, apartment_number, phone) 
                VALUES (:tsj_id, :full_name, :apartment_number, :phone)
            ");
            $stmt_add_resident->execute([
                'tsj_id' => $id_tszh,
                'full_name' => $full_name,
                'apartment_number' => $apartment_number,
                'phone' => $phone
            ]);

            $success = "Жилец успешно добавлен!";
        } catch (Exception $e) {
            $errors[] = "Ошибка добавления жильца: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Добавить жильца | ТСЖ</title>
    <style>
        /* Общие стили */
        body {
            font-family: Arial, sans-serif;
            background-color: #f2f2f2;
            margin: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .container {
            max-width: 600px;
            padding: 20px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        h2 {
            color: #333;
            margin-bottom: 20px;
        }

        form {
            text-align: left;
        }

        label {
            display: block;
            margin-bottom: 5px;
            color: #555;
        }

        input {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 14px;
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

        .error { color: red; }
        .success { color: green; }
    </style>
</head>
<body>
<div class="container">
    <h2>Добавить жильца</h2>

    <?php if ($success): ?>
        <p class="success"><?= htmlspecialchars($success) ?></p>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div class="error">
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <!-- Форма для добавления жильца -->
    <form method="POST" action="">
        <label for="full_name">ФИО жильца:</label>
        <input type="text" id="full_name" name="full_name" required>

        <label for="apartment_number">Номер квартиры:</label>
        <input type="text" id="apartment_number" name="apartment_number" required>

        <label for="phone">Телефон:</label>
        <input type="text" id="phone" name="phone" required>

        <button type="submit" class="btn">Добавить жильца</button>
    </form>

    <!-- Ссылка для возврата -->
    <p><a href="dashboard.php?id_tszh=<?= $id_tszh ?>" class="btn">Назад</a></p>
</div>
</body>
</html>