<?php
session_start();

require_once 'db.php';
$pdo = require 'db.php';
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$id_tszh = filter_input(INPUT_GET, 'id_tszh', FILTER_VALIDATE_INT);
if ($id_tszh === false || $id_tszh <= 0) {
    die("Некорректный ID ТСЖ.");
}

$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id");
$stmt->execute(['id' => $user_id]);
$user = $stmt->fetch();

if (!$user) {
    session_destroy();
    header("Location: login.php");
    exit;
}

if ($user['tsj_id'] != $id_tszh && $user['role'] !== 'admin') {
    die("У вас нет прав для управления этим ТСЖ.");
}

$stmt_tsj = $pdo->prepare("SELECT * FROM tsj WHERE id = :id");
$stmt_tsj->execute(['id' => $id_tszh]);
$tsj = $stmt_tsj->fetch();

if (!$tsj) {
    die("ТСЖ не найдено.");
}

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
    <title>Панель управления | <?= htmlspecialchars($tsj['name']) ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Bootstrap 5 CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container py-5">
    <h1 class="mb-4 text-center">ТСЖ: <?= htmlspecialchars($tsj['name']) ?></h1>

    <div class="row g-4 mb-5">
        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-body text-center">
                    <h5 class="card-title">Жильцов</h5>
                    <p class="card-text fs-4"><?= htmlspecialchars($total_residents) ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-body text-center">
                    <h5 class="card-title">Платежи</h5>
                    <p class="card-text fs-4"><?= htmlspecialchars($total_payments) ?> ₽</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-body text-center">
                    <h5 class="card-title">Заявки</h5>
                    <p class="card-text fs-4"><?= htmlspecialchars($total_requests) ?></p>
                </div>
            </div>
        </div>
    </div>

    <div class="d-grid gap-2 d-md-block text-center">
        <a href="add_resident.php?id_tszh=<?= $id_tszh ?>" class="btn btn-primary m-1">Добавить жильца</a>
        <a href="payment/view_payments?id_tszh=<?= $id_tszh ?>" class="btn btn-primary m-1">Платежи</a>
        <a href="request/view_requests?id_tszh=<?= $id_tszh ?>" class="btn btn-primary text-white m-1">Заявки</a>
        <a href="manage_tsj?id_tszh=<?= $id_tszh ?>" class="btn btn-warning m-1">Управление</a>
        <a href="apartment/manage_apartments?id_tszh=<?= $id_tszh ?>" class="btn btn-success m-1">Добавление квартиры</a>
        <a href="../invite_member" class="btn btn-primary m-1">Пригласить пользователя</a>
        <a href="../profile" class="btn btn-outline-secondary m-1">Назад в профиль</a>
    </div>
</div>
<?php
include '../admin/footer.php';
?>