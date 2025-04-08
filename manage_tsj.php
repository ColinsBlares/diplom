<?php
session_start();

// Подключение к базе данных
require_once 'db.php';
$pdo = require 'db.php';

// Проверка прав доступа
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['owner', 'manager'])) {
    header("Location: profile.php");
    exit;
}

// Получение ID ТСЖ из сессии
$tsj_id = $_SESSION['tsj_id'];

// Получение данных ТСЖ
$stmt_tsj = $pdo->prepare("SELECT * FROM tsj WHERE id = :id");
$stmt_tsj->execute(['id' => $tsj_id]);
$tsj = $stmt_tsj->fetch();

if (!$tsj) {
    die("ТСЖ не найдено.");
}

// Получение списка жильцов
$stmt_residents = $pdo->prepare("SELECT * FROM residents WHERE tsj_id = :tsj_id");
$stmt_residents->execute(['tsj_id' => $tsj_id]);
$residents = $stmt_residents->fetchAll();

// Получение статистики
$total_residents = $pdo->prepare("SELECT COUNT(*) FROM residents WHERE tsj_id = :tsj_id");
$total_residents->execute(['tsj_id' => $tsj_id]);
$total_residents = $total_residents->fetchColumn();

$total_payments = $pdo->prepare("SELECT SUM(amount) FROM payments WHERE tsj_id = :tsj_id");
$total_payments->execute(['tsj_id' => $tsj_id]);
$total_payments = $total_payments->fetchColumn() ?: 0;

$total_requests = $pdo->prepare("SELECT COUNT(*) FROM service_requests WHERE tsj_id = :tsj_id");
$total_requests->execute(['tsj_id' => $tsj_id]);
$total_requests = $total_requests->fetchColumn();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление ТСЖ: <?= htmlspecialchars($tsj['name']) ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f2f2f2;
            margin: 0;
        }

        .container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }

        h1 {
            color: #2c3e50;
            text-align: center;
        }

        .stats {
            display: flex;
            justify-content: space-around;
            margin: 20px 0;
        }

        .stat-box {
            padding: 15px;
            background: #f8f9fa;
            border-radius: 5px;
            text-align: center;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }

        th, td {
            padding: 10px;
            border: 1px solid #ddd;
        }

        th {
            background: #f3f3f3;
        }

        tr:hover {
            background-color: #f8f9fa;
        }

        .btn {
            padding: 10px 20px;
            background: #4CAF50;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin: 5px;
        }

        .btn-danger {
            background: #e74c3c;
        }
    </style>
</head>
<body>
<div class="container">
    <h1>Управление ТСЖ: <?= htmlspecialchars($tsj['name']) ?></h1>
    
    <!-- Статистика -->
    <div class="stats">
        <div class="stat-box">
            <strong>Жильцов:</strong><br>
            <?= htmlspecialchars($total_residents) ?>
        </div>
        <div class="stat-box">
            <strong>Платежи:</strong><br>
            <?= htmlspecialchars($total_payments) ?> ₽
        </div>
        <div class="stat-box">
            <strong>Заявки:</strong><br>
            <?= htmlspecialchars($total_requests) ?>
        </div>
    </div>

    <!-- Список жильцов -->
    <h2>Жильцы</h2>
    <table>
        <thead>
            <tr>
                <th>ФИО</th>
                <th>Квартира</th>
                <th>Телефон</th>
                <th>Действия</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($residents as $resident): ?>
                <tr>
                    <td><?= htmlspecialchars($resident['full_name']) ?></td>
                    <td><?= htmlspecialchars($resident['apartment_number']) ?></td>
                    <td><?= htmlspecialchars($resident['phone']) ?></td>
                    <td>
                        <a href="edit_resident.php?id=<?= $resident['id'] ?>" class="btn">Редактировать</a>
                        <a href="delete_resident.php?id=<?= $resident['id'] ?>" class="btn btn-danger" onclick="return confirm('Удалить жильца?')">Удалить</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- Действия -->
    <div class="actions">
        <a href="add_resident.php?tsj_id=<?= $tsj_id ?>" class="btn">Добавить жильца</a>
        <a href="view_payments.php?tsj_id=<?= $tsj_id ?>" class="btn">Платежи</a>
        <a href="view_requests.php?tsj_id=<?= $tsj_id ?>" class="btn">Заявки</a>
        <a href="dashboard.php?id_tszh=<?= $tsj_id ?>" class="btn">Назад</a>
    </div>
</div>
</body>
</html>