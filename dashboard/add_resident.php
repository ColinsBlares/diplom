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
$id_tszh = filter_input(INPUT_GET, 'id_tszh', FILTER_VALIDATE_INT);
if ($id_tszh === false || $id_tszh <= 0) {
    die("Некорректный ID ТСЖ.");
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
    $full_name = trim(filter_input(INPUT_POST, 'full_name', FILTER_SANITIZE_STRING) ?? '');
    $apartment_number = trim(filter_input(INPUT_POST, 'apartment_number', FILTER_SANITIZE_STRING) ?? '');
    $phone = trim(filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_STRING) ?? '');
    $passport_series = trim(filter_input(INPUT_POST, 'passport_series', FILTER_SANITIZE_STRING) ?? '');
    $passport_number = trim(filter_input(INPUT_POST, 'passport_number', FILTER_SANITIZE_STRING) ?? '');
    $passport_issued_by = trim(filter_input(INPUT_POST, 'passport_issued_by', FILTER_SANITIZE_STRING) ?? '');
    $passport_issue_date = trim(filter_input(INPUT_POST, 'passport_issue_date', FILTER_SANITIZE_STRING) ?? '');
    $registration_address = trim(filter_input(INPUT_POST, 'registration_address', FILTER_SANITIZE_STRING) ?? '');

    // Валидация данных
    if (empty($full_name)) {
        $errors[] = "ФИО жильца обязательно.";
    }

    if (empty($apartment_number)) {
        $errors[] = "Номер квартиры обязателен.";
    }

    if (empty($phone)) {
        $errors[] = "Телефон обязателен.";
    } elseif (!preg_match('/^\+?[0-9]{7,15}$/', $phone)) {
        $errors[] = "Некорректный формат телефона.";
    }

    if (!empty($passport_series) && !preg_match('/^[0-9]{4}$/', $passport_series)) {
        $errors[] = "Серия паспорта должна состоять из 4 цифр.";
    }

    if (!empty($passport_number) && !preg_match('/^[0-9]{6}$/', $passport_number)) {
        $errors[] = "Номер паспорта должен состоять из 6 цифр.";
    }

    if (!empty($passport_issue_date) && !strtotime($passport_issue_date)) {
        $errors[] = "Некорректный формат даты выдачи паспорта.";
    }

    // Если нет ошибок, сохраняем жильца
    if (empty($errors)) {
        try {
            // Добавляем жильца в базу данных
            $stmt_add_resident = $pdo->prepare("
                INSERT INTO residents (
                    tsj_id,
                    full_name,
                    apartment_number,
                    phone,
                    passport_series,
                    passport_number,
                    passport_issued_by,
                    passport_issue_date,
                    registration_address
                )
                VALUES (
                    :tsj_id,
                    :full_name,
                    :apartment_number,
                    :phone,
                    :passport_series,
                    :passport_number,
                    :passport_issued_by,
                    :passport_issue_date,
                    :registration_address
                )
            ");
            $stmt_add_resident->execute([
                'tsj_id' => $id_tszh,
                'full_name' => $full_name,
                'apartment_number' => $apartment_number,
                'phone' => $phone,
                'passport_series' => $passport_series ?: null,
                'passport_number' => $passport_number ?: null,
                'passport_issued_by' => $passport_issued_by ?: null,
                'passport_issue_date' => $passport_issue_date ?: null,
                'registration_address' => $registration_address ?: null,
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
            font-size: 14px;
        }

        input {
            width: calc(100% - 22px); /* Учет padding и border */
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 14px;
            box-sizing: border-box;
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

        .error {
            color: red;
            margin-bottom: 10px;
        }

        .success {
            color: green;
            margin-bottom: 10px;
        }
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

    <form method="POST" action="">
        <label for="full_name">ФИО жильца:</label>
        <input type="text" id="full_name" name="full_name" required>

        <label for="apartment_number">Номер квартиры:</label>
        <input type="text" id="apartment_number" name="apartment_number" required>

        <label for="phone">Телефон:</label>
        <input type="text" id="phone" name="phone" required>

        <label for="passport_series">Серия паспорта (опционально):</label>
        <input type="text" id="passport_series" name="passport_series">

        <label for="passport_number">Номер паспорта (опционально):</label>
        <input type="text" id="passport_number" name="passport_number">

        <label for="passport_issued_by">Кем выдан паспорт (опционально):</label>
        <input type="text" id="passport_issued_by" name="passport_issued_by">

        <label for="passport_issue_date">Дата выдачи паспорта (ГГГГ-ММ-ДД, опционально):</label>
        <input type="text" id="passport_issue_date" name="passport_issue_date" placeholder="ГГГГ-ММ-ДД">

        <label for="registration_address">Адрес регистрации (опционально):</label>
        <input type="text" id="registration_address" name="registration_address">

        <button type="submit" class="btn">Добавить жильца</button>
    </form>

    <p><a href="dashboard.php?id_tszh=<?= $id_tszh ?>" class="btn">Назад</a></p>
</div>
</body>
</html>