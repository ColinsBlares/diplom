<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

// Подключение к базе данных (предполагается, что файл db.php находится на уровень выше)
require_once '../db.php';
$pdo = require '../db.php';

// Проверка авторизации (только авторизованные пользователи с определенной ролью могут добавлять платежи)
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'owner')) {
    header("Location: ../login.php");
    exit;
}

// Получение ID ТСЖ из GET-параметра (обязательно для определения контекста)
$id_tszh = filter_input(INPUT_GET, 'id_tszh', FILTER_VALIDATE_INT);
if ($id_tszh === false || $id_tszh <= 0) {
    die("Некорректный ID ТСЖ.");
}

$errors = [];
$success = null;

// Обработка отправки формы
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Получение данных из формы
    $resident_id = filter_input(INPUT_POST, 'resident_id', FILTER_VALIDATE_INT);
    $amount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT);
    $status = filter_input(INPUT_POST, 'status', FILTER_DEFAULT);
    $payment_date_str = filter_input(INPUT_POST, 'payment_date', FILTER_DEFAULT);
    $description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

    // Валидация данных
    if ($resident_id === false || $resident_id <= 0) {
        $errors[] = "Выберите плательщика.";
    }

    if ($amount === false || $amount <= 0) {
        $errors[] = "Введите корректную сумму платежа.";
    }

    $allowed_statuses = ['оплачено', 'в ожидании', 'частично оплачено', 'отклонено', 'возврат'];
    if (empty($status) || !in_array($status, $allowed_statuses)) {
        $errors[] = "Выберите корректный статус платежа.";
        $status = '';
    }

    if (empty($payment_date_str)) {
        $errors[] = "Выберите дату платежа.";
    } else {
        $payment_date = DateTime::createFromFormat('Y-m-d', $payment_date_str);
        if (!$payment_date) {
            $errors[] = "Некорректный формат даты.";
        } else {
            $payment_date = $payment_date->format('Y-m-d H:i:s'); // Формат для базы данных
        }
    }

    // Если нет ошибок, добавляем платеж в базу данных
    if (empty($errors) && isset($payment_date)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO payments (tsj_id, resident_id, amount, status, payment_date, description) VALUES (:tsj_id, :resident_id, :amount, :status, :payment_date, :description)");
            $stmt->execute([
                'tsj_id' => $id_tszh,
                'resident_id' => $resident_id,
                'amount' => $amount,
                'status' => $status,
                'payment_date' => $payment_date,
                'description' => $description,
            ]);
            $success = "Платеж успешно добавлен.";
        } catch (PDOException $e) {
            $errors[] = "Ошибка при добавлении платежа: " . $e->getMessage();
        }
    }
}

// Получение списка жильцов для выбора плательщика, относящихся к текущему ТСЖ
$residents = [];
try {
    $stmt_residents = $pdo->prepare("SELECT id, full_name FROM residents WHERE tsj_id = :id_tszh ORDER BY full_name");
    $stmt_residents->bindParam(':id_tszh', $id_tszh, PDO::PARAM_INT);
    $stmt_residents->execute();
    $residents = $stmt_residents->fetchAll(PDO::FETCH_KEY_PAIR);
} catch (PDOException $e) {
    $errors[] = "Ошибка при получении списка жильцов: " . $e->getMessage();
}

?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Добавить платеж | ТСЖ</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container my-5">
    <div class="card shadow mx-auto" style="max-width: 600px;">
        <div class="card-body">
            <h2 class="card-title text-center mb-4">Добавить платеж</h2>

            <div class="mb-3 text-center">
                <a href="view_payments.php?id_tszh=<?= htmlspecialchars($id_tszh) ?>" class="btn btn-primary">Назад к истории платежей</a>
            </div>

            <?php if ($success): ?>
                <div class="alert alert-success text-center">
                    <?= htmlspecialchars($success) ?>
                </div>
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

            <form method="POST" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) . '?id_tszh=' . htmlspecialchars($id_tszh) ?>">
                <div class="mb-3">
                    <label for="resident_id" class="form-label">Плательщик</label>
                    <select class="form-select" id="resident_id" name="resident_id" required>
                        <option value="">-- Выберите жильца --</option>
                        <?php foreach ($residents as $id => $full_name): ?>
                            <option value="<?= htmlspecialchars($id) ?>"><?= htmlspecialchars($full_name) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="amount" class="form-label">Сумма платежа</label>
                    <input type="number" class="form-control" id="amount" name="amount" step="0.01" required>
                </div>

                <div class="mb-3">
                    <label for="status" class="form-label">Статус платежа</label>
                    <select class="form-select" id="status" name="status" required>
                        <option value="">-- Выберите статус --</option>
                        <option value="оплачено">Оплачено</option>
                        <option value="в ожидании">В ожидании</option>
                        <option value="частично оплачено">Частично оплачено</option>
                        <option value="отклонено">Отклонено</option>
                        <option value="возврат">Возврат</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="payment_date" class="form-label">Дата платежа</label>
                    <input type="date" class="form-control" id="payment_date" name="payment_date" required>
                </div>

                <div class="mb-3">
                    <label for="description" class="form-label">Описание (необязательно)</label>
                    <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                </div>

                <div class="d-flex justify-content-between">
                    <button type="submit" class="btn btn-success">Добавить платеж</button>
                    <a href="view_payments.php?id_tszh=<?= htmlspecialchars($id_tszh) ?>" class="btn btn-secondary">Отмена</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
include '../../admin/footer.php';
?>