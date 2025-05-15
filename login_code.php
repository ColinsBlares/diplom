<?php
session_start();
require_once 'db.php';
$pdo = require 'db.php';

// Константы из login.php
const ROLE_ADMIN = 'admin';
const ROLE_OWNER = 'owner';
const ROLE_MANAGER = 'manager';
const AUTH_CODE_SESSION_KEY = 'auth_code';
const AUTH_CODE_EXPIRY_SESSION_KEY = 'auth_code_expiry';
const AUTH_CODE_USER_ID_SESSION_KEY = 'auth_code_user_id';

$error = null;

if (!isset($_SESSION[AUTH_CODE_USER_ID_SESSION_KEY]) || !isset($_SESSION[AUTH_CODE_SESSION_KEY]) || !isset($_SESSION[AUTH_CODE_EXPIRY_SESSION_KEY]) || time() > $_SESSION[AUTH_CODE_EXPIRY_SESSION_KEY]) {
    // Если данные сессии отсутствуют или истекли, перенаправляем на страницу входа
    header("Location: login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $auth_code_input = filter_input(INPUT_POST, 'auth_code', FILTER_SANITIZE_STRING);

    if ($auth_code_input === $_SESSION[AUTH_CODE_SESSION_KEY]) {
        // Код верный, получаем данные пользователя
        $user_id = $_SESSION[AUTH_CODE_USER_ID_SESSION_KEY];
        $stmt = $pdo->prepare("SELECT id, role, tsj_id FROM users WHERE id = :id");
        $stmt->execute(['id' => $user_id]);
        $user = $stmt->fetch();

        if ($user) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['tsj_id'] = $user['tsj_id'];

            // Очищаем временные данные сессии
            unset($_SESSION[AUTH_CODE_SESSION_KEY]);
            unset($_SESSION[AUTH_CODE_EXPIRY_SESSION_KEY]);
            unset($_SESSION[AUTH_CODE_USER_ID_SESSION_KEY]);

            if ($_SESSION['role'] === ROLE_ADMIN) {
                header("Location: admin_dashboard");
            } elseif ($_SESSION['role'] === ROLE_OWNER || $_SESSION['role'] === ROLE_MANAGER) {
                header("Location: dashboard/dashboard?id_tszh=" . $user['tsj_id']);
            } else {
                header("Location: profile");
            }
            exit;
        } else {
            $error = "Ошибка: пользователь не найден.";
            // Внутренняя ошибка, стоит залогировать
        }
    } else {
        $error = "Неверный код подтверждения.";
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Подтверждение входа | ТСЖ</title>
    <style>
        /* Общие стили */
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
            padding: 20px 40px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            text-align: center;
            width: 100%;
            max-width: 400px;
        }

        h2 {
            color: #333;
            margin-bottom: 20px;
        }

        /* Стиль формы */
        label {
            display: block;
            margin-top: 15px;
            color: #555;
            font-size: 14px;
        }

        input[type="text"] {
            width: 100%;
            padding: 10px;
            margin-top: 5px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 16px;
        }

        /* Стиль кнопки */
        .btn {
            display: inline-block;
            width: 100%;
            padding: 10px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s ease;
        }

        .btn:hover {
            background-color: #0056b3;
        }

        /* Стиль ошибок */
        .error {
            color: red;
            margin-bottom: 15px;
            font-size: 14px;
        }

        /* Стиль ссылок */
        a {
            color: #007bff;
            text-decoration: none;
            font-size: 14px;
        }

        a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
<div class="container">
    <h2>Подтверждение входа</h2>
    <p>На ваш адрес электронной почты был отправлен код подтверждения.</p>
    <?php if (isset($error)): ?>
        <p class="error"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>
    <form method="POST">
        <label for="auth_code">Введите код подтверждения:</label>
        <input type="text" id="auth_code" name="auth_code" required><br><br>
        <button type="submit" class="btn">Подтвердить</button>
    </form>
    <p><a href="login.php">Вернуться к форме входа</a></p>
</div>
</body>
</html>