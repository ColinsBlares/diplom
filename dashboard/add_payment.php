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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Добавить платеж | ТСЖ</title>
    <link rel="stylesheet" href="../styles.css">
    <style>
        /* Ваши стили CSS */
        .form-container { max-width: 600px; margin: 20px auto; background-color: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0, 0, 0, 0.1); }
        .form-container h2 { text-align: center; margin-bottom: 20px; color: #333; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-group select, .form-group input[type="number"], .form-group input[type="date"], .form-group textarea { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        .form-group textarea { resize: vertical; }
        .form-actions { margin-top: 20px; text-align: center; }
        .form-actions button[type="submit"], .form-actions a { padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; display: inline-block; margin: 0 10px; }
        .form-actions button[type="submit"] { background-color: #28a745; color: white; }
        .form-actions button[type="submit"]:hover { background-color: #1e7e34; }
        .form-actions a { background-color: #007bff; color: white; }
        .form-actions a:hover { background-color: #0056b3; }
        .success-message { color: green; text-align: center; margin-top: 20px; font-weight: bold; }
        .error-container { color: red; margin-bottom: 20px; border: 1px solid red; padding: 10px; border-radius: 5px; background-color: #ffe0e0; }
        .error-container ul { list-style-type: disc; margin-left: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="form-container">
            <h2>Добавить платеж</h2>

            <div class="actions">
                <a href="view_payments.php?id_tszh=<?= htmlspecialchars($id_tszh) ?>" class="btn">Назад к истории платежей</a>
            </div>

            <?php if ($success): ?>
                <p class="success-message"><?= htmlspecialchars($success) ?></p>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
                <div class="error-container">
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?= htmlspecialchars($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="POST" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) . '?id_tszh=' . htmlspecialchars($id_tszh) ?>">
                <div class="form-group">
                    <label for="resident_id">Плательщик:</label>
                    <select id="resident_id" name="resident_id" required>
                        <option value="">-- Выберите жильца --</option>
                        <?php foreach ($residents as $id => $full_name): ?>
                            <option value="<?= htmlspecialchars($id) ?>"><?= htmlspecialchars($full_name) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="amount">Сумма платежа:</label>
                    <input type="number" id="amount" name="amount" step="0.01" required>
                </div>

                <div class="form-group">
                    <label for="status">Статус платежа:</label>
                    <select id="status" name="status" required>
                        <option value="">-- Выберите статус --</option>
                        <option value="оплачено">Оплачено</option>
                        <option value="в ожидании">В ожидании</option>
                        <option value="частично оплачено">Частично оплачено</option>
                        <option value="отклонено">Отклонено</option>
                        <option value="возврат">Возврат</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="payment_date">Дата платежа:</label>
                    <input type="date" id="payment_date" name="payment_date" required>
                </div>

                <div class="form-group">
                    <label for="description">Описание (необязательно):</label>
                    <textarea id="description" name="description"></textarea>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn">Добавить платеж</button>
                    <a href="view_payments.php?id_tszh=<?= htmlspecialchars($id_tszh) ?>" class="btn">Отмена</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>