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

// Получение ID ТСЖ из параметров GET
$id_tszh = intval($_GET['id_tszh'] ?? 0);

if ($id_tszh <= 0) {
    die("ID ТСЖ не указан.");
}

// Получение данных пользователя
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id");
$stmt->execute(['id' => $user_id]);
$user = $stmt->fetch();

if (!$user) {
    session_destroy();
    header("Location: login.php");
    exit;
}

// Проверяем, что пользователь связан с указанным ТСЖ
if ($user['tsj_id'] != $id_tszh && $user['role'] !== 'admin') {
    die("У вас нет прав для управления этим ТСЖ.");
}

// Получение данных ТСЖ
$stmt_tsj = $pdo->prepare("SELECT * FROM tsj WHERE id = :id");
$stmt_tsj->execute(['id' => $id_tszh]);
$tsj = $stmt_tsj->fetch();

if (!$tsj) {
    die("ТСЖ не найдено.");
}

// Получение статистики
$total_residents = $pdo->prepare("SELECT COUNT(*) FROM residents WHERE tsj_id = :tsj_id");
$total_residents->execute(['tsj_id' => $id_tszh]);
$total_residents = $total_residents->fetchColumn();

$total_payments = $pdo->prepare("SELECT SUM(amount) FROM payments WHERE tsj_id = :tsj_id");
$total_payments->execute(['tsj_id' => $id_tszh]);
$total_payments = $total_payments->fetchColumn() ?: 0;

$total_requests = $pdo->prepare("SELECT COUNT(*) FROM service_requests WHERE tsj_id = :tsj_id AND status = 'pending'");
$total_requests->execute(['tsj_id' => $id_tszh]);
$total_requests = $total_requests->fetchColumn();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Панель управления | <?= htmlspecialchars($tsj['name']) ?></title>
    <style>
        /* Общие стили */
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f9f9f9;
            color: #333;
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        h1 {
            font-size: 24px;
            margin-bottom: 20px;
            color: #2c3e50;
        }

        /* Стили для статистики */
        .stats {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
        }

        .stat-box {
            flex: 1;
            padding: 15px;
            background-color: #f0f8ff;
            border: 1px solid #e0e0e0;
            border-radius: 5px;
            text-align: center;
        }

        .stat-box strong {
            display: block;
            font-size: 18px;
            margin-bottom: 5px;
            color: #34495e;
        }

        /* Стили для кнопок */
        .btn {
            display: block;
            padding: 12px;
            margin-bottom: 10px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            text-decoration: none;
            text-align: center;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .btn:hover {
            background-color: #45a049;
        }

        /* Медиа-запросы для мобильных устройств */
        @media (max-width: 768px) {
            .stats {
                flex-direction: column;
            }

            .stat-box {
                margin-bottom: 15px;
            }
        }
    </style>
</head>
<body>
<div class="container">
    <h1>Информация про ТСЖ: <?= htmlspecialchars($tsj['name']) ?></h1>

    <!-- Статистика -->
    <div class="stats">
        <div class="stat-box">
            <strong><?= htmlspecialchars($total_residents) ?></strong>
            Жильцов
        </div>
        <div class="stat-box">
            <strong><?= htmlspecialchars($total_payments) ?> ₽</strong>
            Платежи
        </div>
        <div class="stat-box">
            <strong><?= htmlspecialchars($total_requests) ?></strong>
            Заявки
        </div>
    </div>

    <!-- Действия -->
    <a href="add_resident.php?id_tszh=<?= $id_tszh ?>" class="btn">Добавить жильца</a>
    <a href="view_payments.php?id_tszh=<?= $id_tszh ?>" class="btn">Платежи</a>
    <a href="view_requests.php?id_tszh=<?= $id_tszh ?>" class="btn">Заявки</a>
    <a href="manage_tsj.php?id_tszh=<?= $id_tszh ?>" class="btn">Управление</a>
    <a href="manage_apartments.php?id_tszh=<?= $id_tszh ?>" class="btn">Добавление квартиры</a>
    <a href="profile.php" class="btn">Назад в профиль</a>
</div>
</body>
</html>