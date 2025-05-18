<?php
session_start();

// Подключение к базе данных
require_once '../db.php';
$pdo = require '../db.php';

// Проверка авторизации
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

// Получение ID ТСЖ из GET-параметра и валидация
$id_tszh = filter_input(INPUT_GET, 'id_tszh', FILTER_VALIDATE_INT);
if ($id_tszh === false || $id_tszh <= 0) {
    die("Некорректный ID ТСЖ.");
}

// Проверка прав доступа для добавления платежа (только админ и владелец ТСЖ)
$user_role = $_SESSION['role'] ?? 'guest';
$user_id = $_SESSION['user_id'];
$can_add_payment = false;

if ($user_role === 'admin' || $user_role === 'owner') {
    // Для владельца нужно дополнительно проверить, является ли он владельцем этого ТСЖ
    if ($user_role === 'owner') {
        $stmt_check_owner = $pdo->prepare("SELECT owner_id FROM tsj WHERE id = :tsj_id");
        $stmt_check_owner->execute(['tsj_id' => $id_tszh]);
        $owner_id = $stmt_check_owner->fetchColumn();
        if ($owner_id == $user_id) {
            $can_add_payment = true;
        }
    } else {
        $can_add_payment = true; // Админ имеет право добавлять платежи в любое ТСЖ
    }
}

// Проверка прав доступа для просмотра платежей этого ТСЖ
if ($user_role !== 'admin') {
    // Для не-админов может потребоваться дополнительная проверка связи пользователя с ТСЖ
    $stmt_check_tszh = $pdo->prepare("SELECT tsj_id FROM users WHERE id = :user_id");
    $stmt_check_tszh->execute(['user_id' => $user_id]);
    $user_tszh_id = $stmt_check_tszh->fetchColumn();

    if ($user_tszh_id !== $id_tszh) {
        die("У вас нет прав для просмотра платежей этого ТСЖ.");
    }
}

// Фильтрация платежей
$filter_resident = filter_input(INPUT_GET, 'resident', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
$filter_status = filter_input(INPUT_GET, 'status', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

$where_clause = "WHERE p.tsj_id = :id_tszh";
$params = ['id_tszh' => $id_tszh];

if (!empty($filter_resident)) {
    $where_clause .= " AND r.full_name LIKE :resident";
    $params['resident'] = "%" . $filter_resident . "%";
}

if (!empty($filter_status)) {
    $where_clause .= " AND p.status = :status";
    $params['status'] = $filter_status;
}

$payments = [];
$error = null;

try {
    $sql = "SELECT p.*, r.full_name AS resident_name
            FROM payments p
            JOIN residents r ON p.resident_id = r.id
            " . $where_clause . "
            ORDER BY p.created_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $payments = $stmt->fetchAll();

} catch (PDOException $e) {
    $error = "Ошибка при получении истории платежей: " . $e->getMessage();
}

// Генерация PDF (требует установки библиотеки TCPDF)
if (isset($_GET['export_pdf'])) {
    require_once('../../lib/TCPDF/tcpdf.php'); // Путь к библиотеке TCPDF

    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor('Ваше ТСЖ');
    $pdf->SetTitle('История платежей ТСЖ (ID: ' . $id_tszh . ')');
    $pdf->SetSubject('История платежей');
    $pdf->SetKeywords('ТСЖ, платежи, история');

    $pdf->SetFont('dejavusans', '', 10);
    $pdf->AddPage();

    $html = '<h1>История платежей ТСЖ (ID: ' . $id_tszh . ')</h1>';
    $html .= '<table border="1">';
    $html .= '<thead><tr><th>Дата платежа</th><th>Плательщик</th><th>Сумма</th><th>Статус</th></tr></thead><tbody>';
    foreach ($payments as $payment) {
        $html .= '<tr>';
        $html .= '<td>' . htmlspecialchars(date('d.m.Y H:i:s', strtotime($payment['created_at']))) . '</td>';
        $html .= '<td>' . htmlspecialchars($payment['resident_name']) . '</td>';
        $html .= '<td>' . htmlspecialchars(number_format($payment['amount'], 2, ',', ' ')) . ' р</td>';
        $html .= '<td>' . htmlspecialchars($payment['status']) . '</td>';
        $html .= '</tr>';
    }
    $html .= '</tbody></table>';

    $pdf->writeHTML($html, true, false, true, false, '');
    $current_date = date('Y-m-d');
    $filename = 'платежи_ТСЖ_' . $id_tszh . '_' . $current_date . '.pdf';
    $pdf->Output($filename, 'D'); // 'D' для скачивания
    exit;
}

?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>История платежей | ТСЖ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container py-4">
    <h1 class="mb-4">История платежей ТСЖ (ID: <?= htmlspecialchars($id_tszh) ?>)</h1>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div class="btn-group">
            <a href="../dashboard.php?id_tszh=<?= htmlspecialchars($id_tszh) ?>" class="btn btn-secondary">Назад</a>
            <?php if ($can_add_payment): ?>
                <a href="add_payment.php?id_tszh=<?= htmlspecialchars($id_tszh) ?>" class="btn btn-success">Добавить платеж</a>
            <?php endif; ?>
        </div>
        <a href="?id_tszh=<?= htmlspecialchars($id_tszh) ?>&export_pdf" class="btn btn-danger" target="_blank">Выгрузить в PDF</a>
    </div>

    <form class="row g-3 align-items-end mb-4" method="GET" action="">
        <input type="hidden" name="id_tszh" value="<?= htmlspecialchars($id_tszh) ?>">
        <div class="col-md-4">
            <label for="resident" class="form-label">Фильтр по плательщику</label>
            <input type="text" class="form-control" id="resident" name="resident" value="<?= htmlspecialchars($filter_resident ?? '') ?>">
        </div>
        <div class="col-md-4">
            <label for="status" class="form-label">Фильтр по статусу</label>
            <select class="form-select" id="status" name="status">
                <option value="">Все статусы</option>
                <option value="оплачено" <?= ($filter_status === 'оплачено' ? 'selected' : '') ?>>Оплачено</option>
                <option value="в ожидании" <?= ($filter_status === 'в ожидании' ? 'selected' : '') ?>>В ожидании</option>
                <option value="частично оплачено" <?= ($filter_status === 'частично оплачено' ? 'selected' : '') ?>>Частично оплачено</option>
                <option value="отклонено" <?= ($filter_status === 'отклонено' ? 'selected' : '') ?>>Отклонено</option>
                <option value="возврат" <?= ($filter_status === 'возврат' ? 'selected' : '') ?>>Возврат</option>
            </select>
        </div>
        <div class="col-md-4 d-flex gap-2">
            <button type="submit" class="btn btn-primary w-100">Применить</button>
            <a href="?id_tszh=<?= htmlspecialchars($id_tszh) ?>" class="btn btn-outline-secondary w-100">Сбросить</a>
        </div>
    </form>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if (!empty($payments)): ?>
        <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Дата платежа</th>
                        <th>Плательщик</th>
                        <th>Сумма</th>
                        <th>Статус</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($payments as $payment): ?>
                    <tr>
                        <td><?= htmlspecialchars(date('d.m.Y H:i:s', strtotime($payment['created_at']))) ?></td>
                        <td><?= htmlspecialchars($payment['resident_name']) ?></td>
                        <td><?= htmlspecialchars(number_format($payment['amount'], 2, ',', ' ')) ?> ₽</td>
                        <td><?= htmlspecialchars($payment['status']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="alert alert-info">Нет записей о платежах для данного ТСЖ.</div>
    <?php endif; ?>
</div>
</body>
</html>