<?php
session_start();
require_once 'db.php';
$pdo = require 'db.php';

// Получение ID ТСЖ из сессии
$tsj_id = $_SESSION['tsj_id'];
$id_tszh = $tsj_id; // Assign $tsj_id to $id_tszh for the back button link

// Проверка авторизации
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Проверка прав доступа (только администраторы или владельцы могут просматривать заявки)
if (!in_array($_SESSION['role'], ['admin', 'owner'])) {
    die("У вас нет прав для просмотра заявок.");
}

// Обработка изменения статуса заявки
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['request_id']) && isset($_POST['status'])) {
    $request_id = intval($_POST['request_id']);
    $status = trim($_POST['status']);

    // Валидация данных
    if ($request_id <= 0 || !in_array($status, ['pending', 'in_progress', 'completed', 'rejected'])) {
        die("Некорректные данные для обновления статуса.");
    }

    try {
        // Обновляем статус заявки
        $stmt_update = $pdo->prepare("
            UPDATE service_requests
            SET status = :status
            WHERE id = :id AND tsj_id = :tsj_id
        ");
        $stmt_update->execute([
            'status' => $status,
            'id' => $request_id,
            'tsj_id' => $tsj_id
        ]);

        // Перенаправляем обратно на страницу
        header("Location: view_requests.php");
        exit;
    } catch (Exception $e) {
        die("Ошибка обновления статуса: " . $e->getMessage());
    }
}

// Фильтрация заявок
$filter_owner = filter_input(INPUT_GET, 'owner', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
$filter_status = filter_input(INPUT_GET, 'status', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

$where_clause = "WHERE sr.tsj_id = :tsj_id";
$params = ['tsj_id' => $tsj_id];

if (!empty($filter_owner)) {
    $where_clause .= " AND u.username LIKE :owner";
    $params['owner'] = "%" . $filter_owner . "%";
}

if (!empty($filter_status)) {
    $where_clause .= " AND sr.status = :status";
    $params['status'] = $filter_status;
}

// Получение списка заявок для текущего ТСЖ с учетом фильтрации
$stmt_requests = $pdo->prepare("
    SELECT sr.*, u.username
    FROM service_requests sr
    JOIN users u ON sr.user_id = u.id
    " . $where_clause . "
    ORDER BY sr.created_at DESC
");
$stmt_requests->execute($params);
$requests = $stmt_requests->fetchAll();

// Функция для перевода статуса
function translateStatus($status) {
    switch ($status) {
        case 'pending':
            return 'В ожидании';
        case 'in_progress':
            return 'В работе';
        case 'completed':
            return 'Завершено';
        case 'rejected':
            return 'Отклонено';
        default:
            return $status;
    }
}

// Генерация PDF (требует установки библиотеки TCPDF)
if (isset($_GET['export_pdf'])) {
    require_once('../lib/TCPDF/tcpdf.php'); // Путь к библиотеке TCPDF

    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor('Ваше ТСЖ');
    $pdf->SetTitle('Заявки на обслуживание ТСЖ (ID: ' . $id_tszh . ')');
    $pdf->SetSubject('Заявки на обслуживание');
    $pdf->SetKeywords('ТСЖ, заявки, обслуживание');

    $pdf->SetFont('dejavusans', '', 10);
    $pdf->AddPage();

    $html = '<h1>Заявки на обслуживание ТСЖ (ID: ' . $id_tszh . ')</h1>';
    $html .= '<table border="1">';
    $html .= '<thead><tr><th>Владелец</th><th>Дата создания</th><th>Описание</th><th>Статус</th></tr></thead><tbody>';
    foreach ($requests as $request) {
        $html .= '<tr>';
        $html .= '<td>' . htmlspecialchars($request['username']) . '</td>';
        $html .= '<td>' . htmlspecialchars($request['created_at']) . '</td>';
        $html .= '<td>' . htmlspecialchars($request['description']) . '</td>';
        $html .= '<td>' . htmlspecialchars(translateStatus($request['status'])) . '</td>';
        $html .= '</tr>';
    }
    $html .= '</tbody></table>';

    $pdf->writeHTML($html, true, false, true, false, '');

    $current_date = date('Y-m-d');
    $filename = 'заявки_ТСЖ_' . $id_tszh . '_' . $current_date . '.pdf';
    $pdf->Output($filename, 'D'); // 'D' для скачивания
    exit;
}

?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Просмотр заявок | ТСЖ</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f5f5f5;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        h1 {
            text-align: center;
            color: #333;
            margin-bottom: 20px;
        }
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
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #f3f3f3;
            font-weight: bold;
        }
        tr:hover {
            background-color: #f8f9fa;
        }
        .status-form {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .status-form label {
            display: inline-block;
            margin-bottom: 0;
            font-weight: bold;
            font-size: 0.9em;
        }
        .status-form select {
            width: auto;
            padding: 6px;
            margin-bottom: 0;
            font-size: 0.9em;
        }
        .status-form input[type="submit"] {
            width: auto;
            padding: 6px 10px;
            font-size: 0.9em;
            margin-bottom: 0;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
            transition: background-color 0.3s;
        }
        .btn:hover {
            background-color: #0056b3;
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
    <h1>Просмотр заявок на обслуживание</h1>

    <div class="actions">
        <div>
            <a href="add_request.php" class="btn">Добавить заявку</a>
            <a href="dashboard.php?id_tszh=<?= $id_tszh ?>" class="btn">Назад</a>
        </div>
        <div>
            <a href="?export_pdf" class="export-btn" target="_blank">Выгрузить в PDF</a>
        </div>
    </div>

    <div class="filter-form">
        <form method="GET" action="">
            <label for="owner">Фильтр по владельцу:</label>
            <input type="text" id="owner" name="owner" value="<?= htmlspecialchars($filter_owner ?? '') ?>">
            <label for="status">Фильтр по статусу:</label>
            <select id="status" name="status">
                <option value="">Все статусы</option>
                <option value="pending" <?= ($filter_status === 'pending' ? 'selected' : '') ?>>В ожидании</option>
                <option value="in_progress" <?= ($filter_status === 'in_progress' ? 'selected' : '') ?>>В работе</option>
                <option value="completed" <?= ($filter_status === 'completed' ? 'selected' : '') ?>>Завершено</option>
                <option value="rejected" <?= ($filter_status === 'rejected' ? 'selected' : '') ?>>Отклонено</option>
            </select>
            <button type="submit">Применить фильтр</button>
            <button type="button" onclick="window.location.href='view_requests.php'">Сбросить фильтр</button>
        </form>
    </div>

    <table>
        <thead>
            <tr>
                <th>Владелец</th>
                <th>Дата создания</th>
                <th>Описание</th>
                <th>Статус</th>
                <th>Действия</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($requests)): ?>
                <tr><td colspan="5">Нет заявок для отображения.</td></tr>
            <?php else: ?>
                <?php foreach ($requests as $request): ?>
                    <tr>
                        <td><?= htmlspecialchars($request['username']) ?></td>
                        <td><?= htmlspecialchars($request['created_at']) ?></td>
                        <td><?= htmlspecialchars($request['description']) ?></td>
                        <td><?= htmlspecialchars(translateStatus($request['status'])) ?></td>
                        <td>
                            <form method="POST" class="status-form" action="">
                                <input type="hidden" name="request_id" value="<?= $request['id'] ?>">
                                <label for="status_<?= $request['id'] ?>">Статус:</label>
                                <select name="status" id="status_<?= $request['id'] ?>" required>
                                    <option value="pending" <?= $request['status'] === 'pending' ? 'selected' : '' ?>>В ожидании</option>
                                    <option value="in_progress" <?= $request['status'] === 'in_progress' ? 'selected' : '' ?>>В работе</option>
                                    <option value="completed" <?= $request['status'] === 'completed' ? 'selected' : '' ?>>Завершено</option>
                                    <option value="rejected" <?= $request['status'] === 'rejected' ? 'selected' : '' ?>>Отклонено</option>
                                </select>
                                <input type="submit" value="Сохранить">
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
</body>
</html>