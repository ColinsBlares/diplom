<?php
session_start();

// Проверка авторизации (можно дополнить проверкой роли: админ/менеджер)
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: /login.php");
    exit;
}

// Подключение к базе данных
$pdo = require_once '../../db.php';

// Получение и проверка ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: index.php?error=invalid_id");
    exit;
}

$news_id = (int)$_GET['id'];

// Удаление новости
$stmt = $pdo->prepare("DELETE FROM news WHERE id = :id");
$stmt->execute(['id' => $news_id]);

// Перенаправление назад на список новостей
header("Location: list?success=deleted");
exit;