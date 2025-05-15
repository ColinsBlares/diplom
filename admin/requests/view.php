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
    <!-- Подключение Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .container {
            max-width: 800px;
        }
        .back-link {
            margin-top: 20px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <h1 class="text-center mb-4">Просмотр заявки #<?= htmlspecialchars($request['id']) ?></h1>

        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error_message) ?></div>
        <?php endif; ?>

        <div class="card mb-4">
            <div class="card-header">
                <h4 class="card-title">Детали заявки</h4>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <strong>ID:</strong> <?= htmlspecialchars($request['id']) ?>
                </div>
                <div class="mb-3">
                    <strong>ТСЖ:</strong> <?= htmlspecialchars($request['tsj_name'] ? $request['tsj_name'] . ' (' . $request['tsj_id'] . ')' : 'Не указано') ?>
                </div>
                <div class="mb-3">
                    <strong>Пользователь:</strong> <?= htmlspecialchars($request['user_name'] ? $request['user_name'] . ' (' . $request['user_id'] . ')' : 'Гость (' . $request['user_id'] . ')') ?>
                </div>
                <div class="mb-3">
                    <strong>Описание:</strong> <?= nl2br(htmlspecialchars($request['description'])) ?>
                </div>
                <div class="mb-3">
                    <strong>Дата создания:</strong> <?= htmlspecialchars(date('d.m.Y H:i:s', strtotime($request['created_at']))) ?>
                </div>
                <div class="mb-3">
                    <strong>Дата обновления:</strong> <?= htmlspecialchars(date('d.m.Y H:i:s', strtotime($request['updated_at']))) ?>
                </div>
                <div class="mb-3">
                    <strong>Текущий статус:</strong> <?= htmlspecialchars($request['status']) ?>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h4 class="card-title">Изменить статус заявки</h4>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="mb-3">
                        <label for="status" class="form-label">Новый статус</label>
                        <select name="status" id="status" class="form-select">
                            <option value="pending" <?= $request['status'] === 'pending' ? 'selected' : '' ?>>Ожидает</option>
                            <option value="in_progress" <?= $request['status'] === 'in_progress' ? 'selected' : '' ?>>В обработке</option>
                            <option value="completed" <?= $request['status'] === 'completed' ? 'selected' : '' ?>>Выполнена</option>
                            <option value="rejected" <?= $request['status'] === 'rejected' ? 'selected' : '' ?>>Отклонена</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Сохранить статус</button>
                </form>
            </div>
        </div>

        <a href="list.php" class="btn btn-secondary mt-4 back-link">Назад к списку заявок</a>
    </div>

<?php include '../footer.php';?>