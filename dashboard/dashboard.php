<?php
session_start();

// Подключение к базе данных
require_once 'db.php';
$pdo = require 'db.php'; // Убрано повторное подключение

// Настройка PDO для выброса исключений (рекомендуется)
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Проверка авторизации
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Получение ID ТСЖ из параметров GET и валидация
$id_tszh = filter_input(INPUT_GET, 'id_tszh', FILTER_VALIDATE_INT);
if ($id_tszh === false || $id_tszh <= 0) {
    die("Некорректный ID ТСЖ.");
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

// Получение статистики (можно вынести в отдельные функции)
$stmt_residents = $pdo->prepare("SELECT COUNT(*) FROM residents WHERE tsj_id = :tsj_id");
$stmt_residents->execute(['tsj_id' => $id_tszh]);
$total_residents = $stmt_residents->fetchColumn();

$stmt_payments = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE tsj_id = :tsj_id");
$stmt_payments->execute(['tsj_id' => $id_tszh]);
$total_payments = $stmt_payments->fetchColumn();

$stmt_requests = $pdo->prepare("SELECT COUNT(*) FROM service_requests WHERE tsj_id = :tsj_id AND status = 'pending'");
$stmt_requests->execute(['tsj_id' => $id_tszh]);
$total_requests = $stmt_requests->fetchColumn();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Панель управления | <?= htmlspecialchars($tsj['name']) ?></title>
    <link rel="stylesheet" href="../styles.css"> </head>
<body>
<div class="container">
    <h1>Информация про ТСЖ: <?= htmlspecialchars($tsj['name']) ?></h1>

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

    <a href="add_resident.php?id_tszh=<?= $id_tszh ?>" class="btn">Добавить жильца</a>
    <a href="view_payments.php?id_tszh=<?= $id_tszh ?>" class="btn">Платежи</a>
    <a href="view_requests.php?id_tszh=<?= $id_tszh ?>" class="btn">Заявки</a>
    <a href="manage_tsj.php?id_tszh=<?= $id_tszh ?>" class="btn">Управление</a>
    <a href="manage_apartments.php?id_tszh=<?= $id_tszh ?>" class="btn">Добавление квартиры</a>
    <a href="profile.php" class="btn">Назад в профиль</a>
</div>
</body>
</html>