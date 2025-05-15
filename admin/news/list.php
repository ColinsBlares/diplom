<?php
session_start();

ini_set('display_errors', 1);
error_reporting(E_ALL);

// Проверка авторизации
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit;
}

// Подключение к базе данных
$pdo = require_once '../../db.php';

// Получение новостей
$stmt = $pdo->query("SELECT id, tsj_id, title, content, created_at FROM news ORDER BY created_at DESC");
$news = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Список новостей</title>
    <!-- Подключение Bootstrap -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light p-4">

<div class="container">
    <h2 class="mb-4">Новости</h2>

    <div class="mb-3">
        <a href="create.php" class="btn btn-success">+ Добавить новость</a>
        <a href="../admin_dashboard" class="btn btn-danger">Назад</a>
    </div>

    <div class="table-responsive">
        <table class="table table-bordered table-striped">
            <thead class="thead-dark">
                <tr>
                    <th>ID</th>
                    <th>Заголовок</th>
                    <th>Текст</th>
                    <th>Дата создания</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($news) > 0): ?>
                    <?php foreach ($news as $item): ?>
                        <tr>
                            <td><?= htmlspecialchars($item['id']) ?></td>
                            <td><?= htmlspecialchars($item['title']) ?></td>
                            <td><?= htmlspecialchars(mb_strimwidth(strip_tags($item['content']), 0, 100, "...")) ?></td>
                            <td><?= htmlspecialchars($item['created_at']) ?></td>
                            <td>
                                <a href="edit.php?id=<?= $item['id'] ?>" class="btn btn-warning btn-sm">✏️ Редактировать</a>
                                <a href="delete.php?id=<?= $item['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Удалить новость?');">🗑️ Удалить</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="5" class="text-center">Новостей пока нет.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Подключение JS и Popper.js для функционала Bootstrap -->
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.3/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

</body>
</html>