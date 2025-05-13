<?php
session_start();

// Подключение к базе данных через db.php
require_once 'db.php';
$pdo = require 'db.php';

// Проверка авторизации
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Получение ID приглашения из параметров GET
$invitation_id = intval($_GET['id'] ?? 0);

if ($invitation_id <= 0) {
    die("Недействительный ID приглашения.");
}

// Проверяем, что приглашение существует и относится к текущему пользователю
$stmt_invitation = $pdo->prepare("
    SELECT i.id, i.tsj_id, t.name AS tsj_name 
    FROM invitations i 
    JOIN tsj t ON i.tsj_id = t.id 
    WHERE i.id = :id AND i.user_id = :user_id AND i.status = 'pending'
");
$stmt_invitation->execute(['id' => $invitation_id, 'user_id' => $_SESSION['user_id']]);
$invitation = $stmt_invitation->fetch();

if (!$invitation) {
    die("Приглашение не найдено или уже обработано.");
}

// Обработка принятия приглашения
try {
    // Начало транзакции
    $pdo->beginTransaction();

    // Обновление роли пользователя на 'manager' и привязка к ТСЖ
    $stmt_update_user = $pdo->prepare("UPDATE users SET role = 'manager', tsj_id = :tsj_id WHERE id = :user_id");
    $stmt_update_user->execute(['tsj_id' => $invitation['tsj_id'], 'user_id' => $_SESSION['user_id']]);

    // Обновление статуса приглашения на 'accepted'
    $stmt_accept_invitation = $pdo->prepare("UPDATE invitations SET status = 'accepted' WHERE id = :id");
    $stmt_accept_invitation->execute(['id' => $invitation_id]);

    // Завершение транзакции
    $pdo->commit();

    // Обновление сессии
    $_SESSION['role'] = 'manager';
    $_SESSION['tsj_id'] = $invitation['tsj_id'];

    // Установка сообщения об успехе
    $_SESSION['success'] = "Вы успешно приняли приглашение в ТСЖ '{$invitation['tsj_name']}'!";
    header("Location: dashboard/manage_tsj.php"); // Перенаправляем на панель управления ТСЖ
    exit;
} catch (Exception $e) {
    // Откат транзакции при ошибке
    $pdo->rollBack();
    die("Ошибка принятия приглашения: " . $e->getMessage());
}
?>