<?php
session_start();
require_once '../db.php';
$pdo = require '../db.php';

// Получение ID ТСЖ из сессии
$tsj_id = $_SESSION['tsj_id'];
$id_tszh = $tsj_id; // Assign $tsj_id to $id_tszh for the back button link

// Проверка авторизации
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if (!in_array($_SESSION['role'], ['admin', 'owner', 'manager'])) {
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
    require_once('../../lib/TCPDF/tcpdf.php'); // Путь к библиотеке TCPDF

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
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Просмотр заявок | ТСЖ</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
  <div class="container py-4">
    <h1 class="text-center mb-4">Просмотр заявок на обслуживание</h1>

    <form class="row g-3 align-items-end mb-4 border rounded p-3 bg-white" method="GET" action="">
        <div class="d-flex justify-content-between mb-3">
      <div>
        <a href="add_request" class="btn btn-primary me-2">Добавить заявку</a>
        <a href="../dashboard?id_tszh=<?= $id_tszh ?>" class="btn btn-secondary">Назад</a>
      </div>
      <a href="?export_pdf" class="btn btn-danger" target="_blank">Выгрузить в PDF</a>
    </div>
      <div class="col-md-4">
        <label for="owner" class="form-label">Фильтр по владельцу</label>
        <input type="text" class="form-control" id="owner" name="owner" value="<?= htmlspecialchars($filter_owner ?? '') ?>">
      </div>
      <div class="col-md-4">
        <label for="status" class="form-label">Фильтр по статусу</label>
        <select class="form-select" id="status" name="status">
          <option value="">Все статусы</option>
          <option value="pending" <?= ($filter_status === 'pending' ? 'selected' : '') ?>>В ожидании</option>
          <option value="in_progress" <?= ($filter_status === 'in_progress' ? 'selected' : '') ?>>В работе</option>
          <option value="completed" <?= ($filter_status === 'completed' ? 'selected' : '') ?>>Завершено</option>
          <option value="rejected" <?= ($filter_status === 'rejected' ? 'selected' : '') ?>>Отклонено</option>
        </select>
      </div>
      <div class="col-md-4 d-flex gap-2">
        <button type="submit" class="btn btn-primary">Применить фильтр</button>
        <a href="view_requests.php" class="btn btn-outline-secondary">Сбросить</a>
      </div>
    </form>

    <div class="table-responsive bg-white p-3 rounded shadow-sm">
      <table class="table table-bordered table-hover">
        <thead class="table-light">
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
            <tr><td colspan="5" class="text-center text-muted">Нет заявок для отображения.</td></tr>
          <?php else: ?>
            <?php foreach ($requests as $request): ?>
              <tr>
                <td><?= htmlspecialchars($request['username']) ?></td>
                <td><?= htmlspecialchars($request['created_at']) ?></td>
                <td><?= htmlspecialchars($request['description']) ?></td>
                <td><?= htmlspecialchars(translateStatus($request['status'])) ?></td>
                <td>
                  <form method="POST" class="d-flex align-items-center gap-2" action="">
                    <input type="hidden" name="request_id" value="<?= $request['id'] ?>">
                    <select name="status" class="form-select form-select-sm w-auto" required>
                      <option value="pending" <?= $request['status'] === 'pending' ? 'selected' : '' ?>>В ожидании</option>
                      <option value="in_progress" <?= $request['status'] === 'in_progress' ? 'selected' : '' ?>>В работе</option>
                      <option value="completed" <?= $request['status'] === 'completed' ? 'selected' : '' ?>>Завершено</option>
                      <option value="rejected" <?= $request['status'] === 'rejected' ? 'selected' : '' ?>>Отклонено</option>
                    </select>
                    <button type="submit" class="btn btn-sm btn-success">Сохранить</button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>