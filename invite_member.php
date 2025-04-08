<?php
session_start();
require_once 'db.php';
$pdo = require 'db.php';

// Проверка, что пользователь - владелец ТСЖ
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'owner') {
    header("Location: profile.php");
    exit;
}

$tsj_id = $_SESSION['tsj_id'];
$errors = [];
$success = null;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username'] ?? '');

    // Поиск пользователя по нику
    $stmt_user = $pdo->prepare("SELECT id FROM users WHERE username = :username AND role = 'user'");
    $stmt_user->execute(['username' => $username]);
    $user = $stmt_user->fetch();

    if (!$user) {
        $errors[] = "Пользователь не найден.";
    } else {
        // Проверка, что пользователь не приглашен ранее
        $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM invitations WHERE tsj_id = :tsj_id AND user_id = :user_id");
        $stmt_check->execute(['tsj_id' => $tsj_id, 'user_id' => $user['id']]);
        if ($stmt_check->fetchColumn() > 0) {
            $errors[] = "Пользователь уже приглашен.";
        } else {
            // Создание приглашения
            $stmt_invite = $pdo->prepare("INSERT INTO invitations (tsj_id, user_id, invited_by) VALUES (:tsj_id, :user_id, :invited_by)");
            $stmt_invite->execute([
                'tsj_id' => $tsj_id,
                'user_id' => $user['id'],
                'invited_by' => $_SESSION['user_id']
            ]);
            $success = "Приглашение отправлено!";
        }
    }
}
?>

<!-- HTML аналогичен предыдущему примеру -->