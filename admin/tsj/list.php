<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit;
}
require_once '../../db.php';
$pdo = require '../../db.php';

$stmt = $pdo->query("SELECT id, name, address FROM tsj");
$tsj_list = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление ТСЖ | ТСЖ</title>
    <!-- Подключение Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h2 class="text-center">Управление ТСЖ</h2>
        <div class="mb-3 text-right">
            <a href="../../create_tsj.php" class="btn btn-primary">Добавить новое ТСЖ</a>
        </div>
        <?php if (!empty($tsj_list)): ?>
            <table class="table table-bordered">
                <thead class="thead-light">
                    <tr>
                        <th>ID</th>
                        <th>Название</th>
                        <th>Адрес</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tsj_list as $tsj): ?>
                        <tr>
                            <td><?= htmlspecialchars($tsj['id']) ?></td>
                            <td><?= htmlspecialchars($tsj['name']) ?></td>
                            <td><?= htmlspecialchars($tsj['address']) ?></td>
                            <td>
                                <a href="edit.php?id=<?= $tsj['id'] ?>" class="btn btn-warning btn-sm">Редактировать</a>
                                <a href="delete.php?id=<?= $tsj['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Вы уверены, что хотите удалить это ТСЖ?')">Удалить</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>Нет зарегистрированных ТСЖ.</p>
        <?php endif; ?>
        <p class="text-center mt-3"><a href="../admin_dashboard.php" class="btn btn-secondary">Назад в админ-панель</a></p>
    </div>
<?php include '../footer.php';?>