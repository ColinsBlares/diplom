<?php
session_start();
require_once '../db.php';
$pdo = require '../db.php';

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
    <title>Добавить заявку | ТСЖ</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-5">
    <div class="card shadow-sm">
        <div class="card-body">
            <h1 class="card-title text-center mb-4">Добавить заявку на обслуживание</h1>

            <form method="POST" action="">
                <div class="mb-3">
                    <label for="description" class="form-label">Описание проблемы:</label>
                    <textarea id="description" name="description" class="form-control" rows="4" required></textarea>
                </div>

                <div class="d-grid">
                    <button type="submit" class="btn btn-success">Добавить заявку</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Bootstrap JS (по желанию) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>