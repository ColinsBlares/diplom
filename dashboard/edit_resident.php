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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card shadow">
                <div class="card-body">
                    <h2 class="card-title text-center mb-4">Редактировать жильца</h2>

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

                    <?php if ($resident): ?>
                        <form method="POST">
                            <div class="mb-3">
                                <label for="full_name" class="form-label">ФИО жильца</label>
                                <input type="text" class="form-control" id="full_name" name="full_name" value="<?= htmlspecialchars($resident['full_name']) ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="apartment_number" class="form-label">Номер квартиры</label>
                                <input type="text" class="form-control" id="apartment_number" name="apartment_number" value="<?= htmlspecialchars($resident['apartment_number']) ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="phone" class="form-label">Телефон</label>
                                <input type="text" class="form-control" id="phone" name="phone" value="<?= htmlspecialchars($resident['phone']) ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="passport_series" class="form-label">Серия паспорта (опционально)</label>
                                <input type="text" class="form-control" id="passport_series" name="passport_series" value="<?= htmlspecialchars($resident['passport_series'] ?? '') ?>">
                            </div>

                            <div class="mb-3">
                                <label for="passport_number" class="form-label">Номер паспорта (опционально)</label>
                                <input type="text" class="form-control" id="passport_number" name="passport_number" value="<?= htmlspecialchars($resident['passport_number'] ?? '') ?>">
                            </div>

                            <div class="mb-3">
                                <label for="passport_issued_by" class="form-label">Кем выдан паспорт (опционально)</label>
                                <input type="text" class="form-control" id="passport_issued_by" name="passport_issued_by" value="<?= htmlspecialchars($resident['passport_issued_by'] ?? '') ?>">
                            </div>

                            <div class="mb-3">
                                <label for="passport_issue_date" class="form-label">Дата выдачи паспорта (ГГГГ-ММ-ДД, опционально)</label>
                                <input type="date" class="form-control" id="passport_issue_date" name="passport_issue_date" value="<?= htmlspecialchars($resident['passport_issue_date'] ?? '') ?>">
                            </div>

                            <div class="mb-3">
                                <label for="registration_address" class="form-label">Адрес регистрации (опционально)</label>
                                <input type="text" class="form-control" id="registration_address" name="registration_address" value="<?= htmlspecialchars($resident['registration_address'] ?? '') ?>">
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">Сохранить изменения</button>
                            </div>
                        </form>

                        <div class="mt-3 text-center">
                            <a href="manage_tsj.php" class="btn btn-secondary">Назад к управлению</a>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-danger">Жилец не найден.</div>
                        <div class="text-center">
                            <a href="manage_tsj.php" class="btn btn-secondary">Назад к управлению</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>