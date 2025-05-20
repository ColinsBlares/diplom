<?php
session_start();
$pdo = require_once 'db.php';

// Проверка наличия ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: all_news.php");
    exit;
}

$news_id = (int) $_GET['id'];

// Получение новости
$stmt = $pdo->prepare("SELECT title, content, created_at FROM news WHERE id = :id");
$stmt->execute(['id' => $news_id]);
$news = $stmt->fetch();

if (!$news) {
    echo "<h3>Новость не найдена.</h3>";
    exit;
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($news['title']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container py-5">
    <a href="all_news.php" class="btn btn-outline-secondary mb-4">← Назад ко всем новостям</a>

    <div class="card shadow">
        <div class="card-body">
            <h2 class="card-title"><?= htmlspecialchars($news['title']) ?></h2>
            <p class="text-muted mb-3">
                Опубликовано: <?= date('d.m.Y H:i', strtotime($news['created_at'])) ?>
            </p>
            <div class="card-text">
                <?= $news['content'] ?>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>