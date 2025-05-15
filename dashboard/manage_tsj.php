<?php
session_start();
require_once 'db.php';
$pdo = require 'db.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['owner', 'manager'])) {
    header("Location: profile.php");
    exit;
}

$tsj_id = $_SESSION['tsj_id'];

$stmt_tsj = $pdo->prepare("SELECT * FROM tsj WHERE id = :id");
$stmt_tsj->execute(['id' => $tsj_id]);
$tsj = $stmt_tsj->fetch();

if (!$tsj) {
    die("ТСЖ не найдено.");
}

$stmt_residents = $pdo->prepare("SELECT id, full_name, apartment_number, phone, passport_series, passport_number FROM residents WHERE tsj_id = :tsj_id");
$stmt_residents->execute(['tsj_id' => $tsj_id]);
$residents = $stmt_residents->fetchAll();

$total_residents = $pdo->prepare("SELECT COUNT(*) FROM residents WHERE tsj_id = :tsj_id");
$total_residents->execute(['tsj_id' => $tsj_id]);
$total_residents = $total_residents->fetchColumn();

$total_payments = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE tsj_id = :tsj_id");
$total_payments->execute(['tsj_id' => $tsj_id]);
$total_payments = $total_payments->fetchColumn();

$total_requests = $pdo->prepare("SELECT COUNT(*) FROM service_requests WHERE tsj_id = :tsj_id");
$total_requests->execute(['tsj_id' => $tsj_id]);
$total_requests = $total_requests->fetchColumn();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Управление ТСЖ: <?= htmlspecialchars($tsj['name']) ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
  <div class="container py-5">
    <h1 class="text-center mb-4">Управление ТСЖ: <?= htmlspecialchars($tsj['name']) ?></h1>

    <div class="row mb-4 text-center">
      <div class="col-md-4 mb-2">
        <div class="card shadow-sm">
          <div class="card-body">
            <h5 class="card-title">Жильцов</h5>
            <p class="card-text fs-4"><?= htmlspecialchars($total_residents) ?></p>
          </div>
        </div>
      </div>
      <div class="col-md-4 mb-2">
        <div class="card shadow-sm">
          <div class="card-body">
            <h5 class="card-title">Платежи</h5>
            <p class="card-text fs-4"><?= htmlspecialchars($total_payments) ?> ₽</p>
          </div>
        </div>
      </div>
      <div class="col-md-4 mb-2">
        <div class="card shadow-sm">
          <div class="card-body">
            <h5 class="card-title">Заявки</h5>
            <p class="card-text fs-4"><?= htmlspecialchars($total_requests) ?></p>
          </div>
        </div>
      </div>
    </div>

    <h2 class="mb-3">Жильцы</h2>
    <div class="table-responsive mb-4">
      <table class="table table-bordered table-hover align-middle">
        <thead class="table-light">
          <tr>
            <th>ФИО</th>
            <th>Квартира</th>
            <th>Телефон</th>
            <th>Серия паспорта</th>
            <th>Номер паспорта</th>
            <th>Действия</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($residents as $resident): ?>
            <tr>
              <td><?= htmlspecialchars($resident['full_name']) ?></td>
              <td><?= htmlspecialchars($resident['apartment_number']) ?></td>
              <td><?= htmlspecialchars($resident['phone']) ?></td>
              <td><?= htmlspecialchars($resident['passport_series'] ?: '-') ?></td>
              <td><?= htmlspecialchars($resident['passport_number'] ?: '-') ?></td>
              <td>
                <a href="edit_resident.php?id=<?= $resident['id'] ?>" class="btn btn-sm btn-outline-primary me-2">Редактировать</a>
                <a href="delete_resident.php?id=<?= $resident['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Удалить жильца?')">Удалить</a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <div class="d-flex justify-content-center gap-3">
      <a href="view_payments.php?id_tszh=<?= $tsj_id ?>" class="btn btn-primary">Платежи</a>
      <a href="view_requests.php?tsj_id=<?= $tsj_id ?>" class="btn btn-primary">Заявки</a>
      <a href="dashboard.php?id_tszh=<?= $tsj_id ?>" class="btn btn-secondary">Назад</a>
    </div>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>