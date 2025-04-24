<?php
session_start();

// Проверка авторизации администратора
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit;
}

require_once '../../db.php';
$pdo = require '../../db.php';

$request_id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);

if (!$request_id) {
    die("ID заявки не указан.");
}

// Получение деталей заявки
$stmt = $pdo->prepare("SELECT sr.id, sr.tsj_id, t.name AS tsj_name, sr.user_id, u.username AS user_name, sr.description, sr.status, sr.created_at, sr.updated_at
                       FROM service_requests sr
                       LEFT JOIN users u ON sr.user_id = u.id
                       LEFT JOIN tsj t ON sr.tsj_id = t.id
                       WHERE sr.id = :id");
$stmt->execute([':id' => $request_id]);
$request = $stmt->fetch();

if (!$request) {
    die("Заявка не найдена.");
}

// Обработка изменения статуса заявки (аналогично list.php)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['status'])) {
    $new_status = filter_input(INPUT_POST, 'status', FILTER_SANITIZE_STRING);
    $allowed_statuses = ['pending', 'in_progress', 'completed', 'rejected'];
    if (in_array($new_status, $allowed_statuses)) {
        $stmt_update = $pdo->prepare("UPDATE service_requests SET status = :status, updated_at = NOW() WHERE id = :id");
        $stmt_update->execute([':status' => $new_status, ':id' => $request_id]);
        // Перезагружаем страницу для отображения обновленного статуса
        header("Location: view.php?id=$request_id");
        exit;
    } else {
        $error_message = "Некорректный статус заявки.";
    }
}

?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Просмотр заявки #<?= htmlspecialchars($request['id']) ?> | Админ панель</title>
    <link rel="stylesheet" href="../../styles.css">
    <style>
        .container {
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
            background-color: #f9f9f9;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        h1 {
            text-align: center;
            color: #333;
            margin-bottom: 20px;
        }
        .request-details {
            margin-bottom: 20px;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            background-color: #fff;
        }
        .detail-row {
            display: flex;
            margin-bottom: 10px;
        }
        .detail-label {
            font-weight: bold;
            width: 150px;
            color: #555;
        }
        .detail-value {
            flex-grow: 1;
            color: #333;
        }
        .status-form {
            margin-top: 20px;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            background-color: #fff;
        }
        .status-label {
            font-weight: bold;
            margin-bottom: 5px;
            color: #555;
        }
        .status-select {
            padding: 8px;
            border-radius: 4px;
            border: 1px solid #ccc;
        }
        .status-button {
            padding: 8px 15px;
            border: none;
            border-radius: 4px;
            background-color: #007bff;
            color: white;
            cursor: pointer;
            font-size: 16px;
        }
        .status-button:hover {
            background-color: #0056b3;
        }
        .error-message {
            color: red;
            margin-top: 10px;
        }
        .back-link {
            display: block;
            margin-top: 20px;
            text-align: center;
            color: #007bff;
            text-decoration: none;
        }
        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Просмотр заявки #<?= htmlspecialchars($request['id']) ?></h1>

        <?php if (isset($error_message)): ?>
            <p class="error-message"><?= htmlspecialchars($error_message) ?></p>
        <?php endif; ?>

        <div class="request-details">
            <div class="detail-row">
                <div class="detail-label">ID:</div>
                <div class="detail-value"><?= htmlspecialchars($request['id']) ?></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">ТСЖ:</div>
                <div class="detail-value"><?= htmlspecialchars($request['tsj_name'] ? $request['tsj_name'] . ' (' . $request['tsj_id'] . ')' : 'Не указано (' . $request['tsj_id'] . ')') ?></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Пользователь:</div>
                <div class="detail-value"><?= htmlspecialchars($request['user_name'] ? $request['user_name'] . ' (' . $request['user_id'] . ')' : 'Гость (' . $request['user_id'] . ')') ?></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Описание:</div>
                <div class="detail-value"><?= nl2br(htmlspecialchars($request['description'])) ?></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Дата создания:</div>
                <div class="detail-value"><?= htmlspecialchars(date('d.m.Y H:i:s', strtotime($request['created_at']))) ?></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Дата обновления:</div>
                <div class="detail-value"><?= htmlspecialchars(date('d.m.Y H:i:s', strtotime($request['updated_at']))) ?></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Текущий статус:</div>
                <div class="detail-value"><?= htmlspecialchars($request['status']) ?></div>
            </div>
        </div>

        <div class="status-form">
            <h3 class="status-label">Изменить статус заявки</h3>
            <form method="POST">
                <label for="status" class="status-label">Новый статус:</label>
                <select name="status" id="status" class="status-select">
                    <option value="pending" <?= $request['status'] === 'pending' ? 'selected' : '' ?>>Ожидает</option>
                    <option value="in_progress" <?= $request['status'] === 'in_progress' ? 'selected' : '' ?>>В обработке</option>
                    <option value="completed" <?= $request['status'] === 'completed' ? 'selected' : '' ?>>Выполнена</option>
                    <option value="rejected" <?= $request['status'] === 'rejected' ? 'selected' : '' ?>>Отклонена</option>
                </select>
                <button type="submit" class="status-button">Сохранить статус</button>
            </form>
        </div>

        <a href="list.php" class="back-link">Назад к списку заявок</a>
    </div>
</body>
</html>