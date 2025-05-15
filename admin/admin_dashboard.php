<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Админ-панель | ТСЖ</title>
    <!-- Подключение Bootstrap -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

    <div class="container mt-5">
        <div class="card shadow-sm">
            <div class="card-body text-center">
                <h1 class="card-title mb-4">Админ-панель</h1>
                <p class="card-text mb-4">Добро пожаловать, администратор!</p>
                <ul class="list-unstyled">
                    <li class="mb-3">
                        <a href="users/list.php" class="btn btn-primary btn-block">Управление пользователями</a>
                    </li>
                    <li class="mb-3">
                        <a href="tsj/list.php" class="btn btn-primary btn-block">Управление ТСЖ</a>
                    </li>
                    <li class="mb-3">
                        <a href="requests/list.php" class="btn btn-primary btn-block">Управление заявками</a>
                    </li>
                    <li class="mb-3">
                        <a href="news/list.php" class="btn btn-primary btn-block">Управление новостями</a>
                    </li>
                    <li class="mb-3">
                        <a href="../logout.php" class="btn btn-danger btn-block">Выйти</a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
<?php

include 'footer.php';
?>