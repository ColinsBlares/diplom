<?php
session_start();
$pdo = require_once 'db.php';

// Получаем все новости
$stmt = $pdo->query("SELECT id, title, content, created_at FROM news ORDER BY created_at DESC");
$newsList = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Все новости</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container py-4">
    <h2 class="mb-4">Все новости</h2>
    <a href="index" class="btn btn-outline-secondary mb-4">← Назад</a>

    <?php if (count($newsList) > 0): ?>
        <div class="row row-cols-1 g-4">
            <?php foreach ($newsList as $news): ?>
                <div class="col">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title"><?= htmlspecialchars($news['title']) ?></h5>
                            <h6 class="card-subtitle mb-2 text-muted">
                                <?= date('d.m.Y H:i', strtotime($news['created_at'])) ?>
                            </h6>
                            <p class="card-text">
                                <?= htmlspecialchars(mb_strimwidth(strip_tags($news['content']), 0, 200, '...')) ?>
                            </p>
                            <a href="news_detail.php?id=<?= $news['id'] ?>" class="btn btn-primary btn-sm">
                                Читать далее
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="alert alert-info">Новостей пока нет.</div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>