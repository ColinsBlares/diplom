<?php
require_once 'db.php';
$pdo = require 'db.php';

$message = null;
$error = null;

$token = $_GET['token'] ?? null;

if ($token) {
    try {
        $stmt = $pdo->prepare("SELECT id, is_verified FROM users WHERE verification_token = :token");
        $stmt->execute(['token' => $token]);
        $user = $stmt->fetch();

        if ($user) {
            if ($user['is_verified']) {
                $message = "Ваш адрес электронной почты уже был подтвержден. Вы можете <a href='login.php'>войти</a>.";
            } else {
                $stmt_update = $pdo->prepare("UPDATE users SET is_verified = TRUE, verification_token = NULL WHERE id = :id");
                $stmt_update->execute(['id' => $user['id']]);
                $message = "Ваш адрес электронной почты успешно подтвержден! Теперь вы можете <a href='login.php'>войти</a>.";
            }
        } else {
            $error = "Недействительная ссылка для подтверждения.";
        }
    } catch (Exception $e) {
        $error = "Произошла ошибка при подтверждении: " . $e->getMessage();
    }
} else {
    $error = "Отсутствует токен подтверждения.";
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Подтверждение Email | ТСЖ</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f2f2f2;
            margin: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .container {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        .message {
            color: green;
            margin-bottom: 15px;
        }
        .error {
            color: red;
            margin-bottom: 15px;
        }
        a {
            color: #007bff;
            text-decoration: none;
        }
        a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
<div class="container">
    <h2>Подтверждение Email</h2>
    <?php if ($message): ?>
        <p class="message"><?= $message ?></p>
    <?php elseif ($error): ?>
        <p class="error"><?= $error ?></p>
    <?php else: ?>
        <p>Пожалуйста, перейдите по ссылке, отправленной на ваш адрес электронной почты, чтобы подтвердить регистрацию.</p>
    <?php endif; ?>
</div>
</body>
</html>