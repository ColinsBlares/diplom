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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h2 class="card-title text-center mb-4">Добавить жильца</h2>

                    <?php if ($success): ?>
                        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
                    <?php endif; ?>

                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?= htmlspecialchars($error) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <div class="mb-3">
                            <label for="full_name" class="form-label">ФИО жильца</label>
                            <input type="text" class="form-control" id="full_name" name="full_name" required>
                        </div>

                        <div class="mb-3">
                            <label for="apartment_number" class="form-label">Номер квартиры</label>
                            <input type="text" class="form-control" id="apartment_number" name="apartment_number" required>
                        </div>

                        <div class="mb-3">
                            <label for="phone" class="form-label">Телефон</label>
                            <input type="text" class="form-control" id="phone" name="phone" required>
                        </div>

                        <div class="mb-3">
                            <label for="passport_series" class="form-label">Серия паспорта (опционально)</label>
                            <input type="text" class="form-control" id="passport_series" name="passport_series">
                        </div>

                        <div class="mb-3">
                            <label for="passport_number" class="form-label">Номер паспорта (опционально)</label>
                            <input type="text" class="form-control" id="passport_number" name="passport_number">
                        </div>

                        <div class="mb-3">
                            <label for="passport_issued_by" class="form-label">Кем выдан паспорт (опционально)</label>
                            <input type="text" class="form-control" id="passport_issued_by" name="passport_issued_by">
                        </div>

                        <div class="mb-3">
                            <label for="passport_issue_date" class="form-label">Дата выдачи паспорта (ГГГГ-ММ-ДД)</label>
                            <input type="date" class="form-control" id="passport_issue_date" name="passport_issue_date">
                        </div>

                        <div class="mb-3">
                            <label for="registration_address" class="form-label">Адрес регистрации (опционально)</label>
                            <input type="text" class="form-control" id="registration_address" name="registration_address">
                        </div>

                        <button type="submit" class="btn btn-success w-100">Добавить жильца</button>
                    </form>

                    <div class="text-center mt-4">
                        <a href="dashboard.php?id_tszh=<?= $id_tszh ?>" class="btn btn-secondary">Назад</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php
include '../admin/footer.php';
?>