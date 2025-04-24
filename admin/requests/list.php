<?php
session_start();

// Проверка авторизации администратора
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit;
}

require_once '../../db.php';
$pdo = require '../../db.php';

// Количество заявок на странице
$limit = 20;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Получение общего количества заявок
$total_requests = $pdo->query("SELECT COUNT(*) FROM service_requests")->fetchColumn();
$total_pages = ceil($total_requests / $limit);

// Получение списка заявок с пагинацией
$stmt = $pdo->prepare("SELECT sr.id, sr.user_id, u.username AS user_name, sr.description, sr.created_at, sr.status
                       FROM service_requests sr
                       LEFT JOIN users u ON sr.user_id = u.id
                       ORDER BY sr.created_at DESC
                       LIMIT :limit OFFSET :offset");
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$requests = $stmt->fetchAll();

// Обработка изменения статуса заявки
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_id']) && isset($_POST['status'])) {
    $request_id = filter_input(INPUT_POST, 'request_id', FILTER_SANITIZE_NUMBER_INT);
    $new_status = filter_input(INPUT_POST, 'status', FILTER_SANITIZE_STRING);

    $allowed_statuses = ['pending', 'in_progress', 'completed', 'rejected'];
    if ($request_id && in_array($new_status, $allowed_statuses)) {
        $stmt_update = $pdo->prepare("UPDATE service_requests SET status = :status WHERE id = :id");
        $stmt_update->execute([':status' => $new_status, ':id' => $request_id]);
        // Перенаправляем пользователя обратно на страницу со списком заявок, чтобы увидеть изменения
        header("Location: list.php?page=$page");
        exit;
    } else {
        $error_message = "Некорректный запрос на обновление статуса.";
    }
}

?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Список заявок | Админ панель</title>
    <link rel="stylesheet" href="../../styles.css">
    <style>
        .container {
            max-width: 960px;
            margin: 20px auto;
            padding: 20px;
            background-color: #f9f9f9;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        h1 {
            text-align: center;
            color: #333;
            margin-bottom: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            padding: 10px;
            border: 1px solid #ddd;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        tr:nth-child(even) {
            background-color: #f5f5f5;
        }
        .status-select {
            padding: 8px;
            border-radius: 4px;
            border: 1px solid #ccc;
        }
        .pagination {
            margin-top: 20px;
            text-align: center;
        }
        .pagination a, .pagination span {
            display: inline-block;
            padding: 8px 12px;
            margin: 0 5px;
            border: 1px solid #ddd;
            border-radius: 4px;
            text-decoration: none;
            color: #333;
        }
        .pagination a:hover {
            background-color: #eee;
        }
        .pagination .current {
            background-color: #007bff;
            color: white;
            border-color: #007bff;
        }
        .error-message {
            color: red;
            margin-top: 10px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Список заявок пользователей</h1>

        <?php if (isset($error_message)): ?>
            <p class="error-message"><?= htmlspecialchars($error_message) ?></p>
        <?php endif; ?>

        <?php if (!empty($requests)): ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Пользователь</th>
                        <th>Описание</th>
                        <th>Дата создания</th>
                        <th>Статус</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($requests as $request): ?>
                        <tr>
                            <td><?= htmlspecialchars($request['id']) ?></td>
                            <td><?= htmlspecialchars($request['user_name'] ? $request['user_name'] . ' (' . $request['user_id'] . ')' : 'Гость (' . $request['user_id'] . ')') ?></td>
                            <td><?= htmlspecialchars($request['description']) ?></td>
                            <td><?= htmlspecialchars(date('d.m.Y H:i', strtotime($request['created_at']))) ?></td>
                            <td>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="request_id" value="<?= htmlspecialchars($request['id']) ?>">
                                    <select name="status" class="status-select" onchange="this.form.submit()">
                                        <option value="pending" <?= $request['status'] === 'pending' ? 'selected' : '' ?>>Ожидает</option>
                                        <option value="in_progress" <?= $request['status'] === 'in_progress' ? 'selected' : '' ?>>В обработке</option>
                                        <option value="completed" <?= $request['status'] === 'completed' ? 'selected' : '' ?>>Выполнена</option>
                                        <option value="rejected" <?= $request['status'] === 'rejected' ? 'selected' : '' ?>>Отклонена</option>
                                    </select>
                                </form>
                            </td>
                            <td>
                                <a href="view.php?id=<?= htmlspecialchars($request['id']) ?>">Просмотреть</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?= $page - 1 ?>">«</a>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <?php if ($i === $page): ?>
                            <span class="current"><?= $i ?></span>
                        <?php else: ?>
                            <a href="?page=<?= $i ?>"><?= $i ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>

                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?= $page + 1 ?>">»</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

        <?php else: ?>
            <p>На данный момент нет ни одной заявки.</p>
        <?php endif; ?>

        <p><a href="../admin_dashboard.php">Назад в админ панель</a></p>
    </div>
</body>
</html>