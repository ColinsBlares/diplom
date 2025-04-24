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
    require_once('../lib/TCPDF/tcpdf.php'); // Путь к библиотеке TCPDF

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
    <link rel="stylesheet" href="../styles.css">
    <style>
        .actions {
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
            align-items: center;
            justify-content: space-between;
        }
        .filter-form {
            margin-bottom: 20px;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            background-color: #f9f9f9;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .filter-form label {
            font-weight: bold;
        }

        .filter-form input[type="text"],
        .filter-form select {
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }

        .filter-form button {
            padding: 8px 15px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
        }

        .filter-form button:hover {
            background-color: #0056b3;
        }
        .add-payment-btn {
            display: inline-block;
            padding: 10px 15px;
            background-color: #28a745; /* Green color for add */
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
        }

        .add-payment-btn:hover {
            background-color: #1e7e34;
        }

        .payments-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            overflow: hidden;
        }

        .payments-table th, .payments-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        .payments-table th {
            background-color: #f2f2f2;
            font-weight: bold;
            color: #333;
        }

        .payments-table tbody tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        .payments-table tbody tr:hover {
            background-color: #e0e0e0;
        }
        .export-btn {
            padding: 8px 15px;
            background-color: #dc3545; /* Red color for export */
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            text-decoration: none;
        }

        .export-btn:hover {
            background-color: #c82333;
        }
    </style>
</head>
<body>
<div class="container">
    <h1>История платежей ТСЖ (ID: <?= htmlspecialchars($id_tszh) ?>)</h1>

    <div class="actions">
        <div>
            <a href="dashboard.php?id_tszh=<?= htmlspecialchars($id_tszh) ?>" class="btn">Назад в панель управления</a>
            <?php if ($can_add_payment): ?>
                <a href="add_payment.php?id_tszh=<?= htmlspecialchars($id_tszh) ?>" class="add-payment-btn">Добавить платеж</a>
            <?php endif; ?>
        </div>
        <div>
            <a href="?id_tszh=<?= htmlspecialchars($id_tszh) ?>&export_pdf" class="export-btn" target="_blank">Выгрузить в PDF</a>
        </div>
    </div>

    <div class="filter-form">
        <form method="GET" action="">
            <input type="hidden" name="id_tszh" value="<?= htmlspecialchars($id_tszh) ?>">
            <label for="resident">Фильтр по плательщику:</label>
            <input type="text" id="resident" name="resident" value="<?= htmlspecialchars($filter_resident ?? '') ?>">
            <label for="status">Фильтр по статусу:</label>
            <select id="status" name="status">
                <option value="">Все статусы</option>
                <option value="оплачено" <?= ($filter_status === 'оплачено' ? 'selected' : '') ?>>Оплачено</option>
                <option value="в ожидании" <?= ($filter_status === 'в ожидании' ? 'selected' : '') ?>>В ожидании</option>
                <option value="частично оплачено" <?= ($filter_status === 'частично оплачено' ? 'selected' : '') ?>>Частично оплачено</option>
                <option value="отклонено" <?= ($filter_status === 'отклонено' ? 'selected' : '') ?>>Отклонено</option>
                <option value="возврат" <?= ($filter_status === 'возврат' ? 'selected' : '') ?>>Возврат</option>
            </select>
            <button type="submit">Применить фильтр</button>
            <button type="button" onclick="window.location.href='?id_tszh=<?= htmlspecialchars($id_tszh) ?>'">Сбросить фильтр</button>
        </form>
    </div>

    <?php if ($error): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if (!empty($payments)): ?>
        <table class="payments-table">
            <thead>
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
    <?php else: ?>
        <p>Нет записей о платежах для данного ТСЖ.</p>
    <?php endif; ?>
</div>
</body>
</html>