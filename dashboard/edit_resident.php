<?php
session_start();

// Подключение к базе данных
require_once 'db.php';
$pdo = require 'db.php';

// Проверка авторизации
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Проверка прав доступа (только владельцы или менеджеры могут редактировать жильцов)
if (!in_array($_SESSION['role'], ['owner', 'manager'])) {
    header("Location: profile.php");
    exit;
}

// Получение ID жильца из GET-параметра
$resident_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if ($resident_id === false || $resident_id <= 0) {
    die("Некорректный ID жильца.");
}

// Получение ID ТСЖ из сессии
$tsj_id = $_SESSION['tsj_id'];

// Получение данных жильца
$stmt_resident = $pdo->prepare("SELECT * FROM residents WHERE id = :id AND tsj_id = :tsj_id");
$stmt_resident->execute(['id' => $resident_id, 'tsj_id' => $tsj_id]);
$resident = $stmt_resident->fetch();

if (!$resident) {
    die("Жилец не найден или не принадлежит вашему ТСЖ.");
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

    // Валидация данных (аналогично add_resident.php)
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

    // Если нет ошибок, обновляем данные жильца
    if (empty($errors)) {
        try {
            $stmt_update_resident = $pdo->prepare("
                UPDATE residents SET
                    full_name = :full_name,
                    apartment_number = :apartment_number,
                    phone = :phone,
                    passport_series = :passport_series,
                    passport_number = :passport_number,
                    passport_issued_by = :passport_issued_by,
                    passport_issue_date = :passport_issue_date,
                    registration_address = :registration_address
                WHERE id = :id AND tsj_id = :tsj_id
            ");
            $stmt_update_resident->execute([
                'id' => $resident_id,
                'tsj_id' => $tsj_id,
                'full_name' => $full_name,
                'apartment_number' => $apartment_number,
                'phone' => $phone,
                'passport_series' => $passport_series ?: null,
                'passport_number' => $passport_number ?: null,
                'passport_issued_by' => $passport_issued_by ?: null,
                'passport_issue_date' => $passport_issue_date ?: null,
                'registration_address' => $registration_address ?: null,
            ]);

            $success = "Данные жильца успешно обновлены!";

            // После успешного обновления снова получаем данные жильца для отображения в форме
            $stmt_resident->execute(['id' => $resident_id, 'tsj_id' => $tsj_id]);
            $resident = $stmt_resident->fetch();

        } catch (Exception $e) {
            $errors[] = "Ошибка обновления данных жильца: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Редактировать жильца | ТСЖ</title>
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
            background-color: #007bff; /* Синий цвет для редактирования */
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            font-size: 16px;
            transition: background-color 0.3s ease;
        }

        .btn:hover {
            background-color: #0056b3;
        }

        .error {
            color: red;
            margin-bottom: 10px;
        }

        .success {
            color: green;
            margin-bottom: 10px;
        }

        .back-btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: #6c757d; /* Серый цвет для кнопки "Назад" */
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            font-size: 16px;
            transition: background-color 0.3s ease;
            margin-top: 10px;
        }

        .back-btn:hover {
            background-color: #5a6268;
        }
    </style>
</head>
<body>
<div class="container">
    <h2>Редактировать жильца</h2>

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

    <?php if ($resident): ?>
        <form method="POST" action="">
            <label for="full_name">ФИО жильца:</label>
            <input type="text" id="full_name" name="full_name" value="<?= htmlspecialchars($resident['full_name']) ?>" required>

            <label for="apartment_number">Номер квартиры:</label>
            <input type="text" id="apartment_number" name="apartment_number" value="<?= htmlspecialchars($resident['apartment_number']) ?>" required>

            <label for="phone">Телефон:</label>
            <input type="text" id="phone" name="phone" value="<?= htmlspecialchars($resident['phone']) ?>" required>

            <label for="passport_series">Серия паспорта (опционально):</label>
            <input type="text" id="passport_series" name="passport_series" value="<?= htmlspecialchars($resident['passport_series'] ?? '') ?>">

            <label for="passport_number">Номер паспорта (опционально):</label>
            <input type="text" id="passport_number" name="passport_number" value="<?= htmlspecialchars($resident['passport_number'] ?? '') ?>">

            <label for="passport_issued_by">Кем выдан паспорт (опционально):</label>
            <input type="text" id="passport_issued_by" name="passport_issued_by" value="<?= htmlspecialchars($resident['passport_issued_by'] ?? '') ?>">

            <label for="passport_issue_date">Дата выдачи паспорта (ГГГГ-ММ-ДД, опционально):</label>
            <input type="text" id="passport_issue_date" name="passport_issue_date" placeholder="ГГГГ-ММ-ДД" value="<?= htmlspecialchars($resident['passport_issue_date'] ?? '') ?>">

            <label for="registration_address">Адрес регистрации (опционально):</label>
            <input type="text" id="registration_address" name="registration_address" value="<?= htmlspecialchars($resident['registration_address'] ?? '') ?>">

            <button type="submit" class="btn">Сохранить изменения</button>
        </form>

        <p><a href="manage_tsj.php" class="back-btn">Назад к управлению</a></p>
    <?php else: ?>
        <p class="error">Жилец не найден.</p>
        <p><a href="manage_tsj.php" class="back-btn">Назад к управлению</a></p>
    <?php endif; ?>
</div>
</body>
</html>