<?php
session_start();
require_once 'db.php';
$pdo = require 'db.php';

// Проверка, что пользователь - владелец ТСЖ
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'owner') {
    header("Location: profile.php");
    exit;
}

// Проверка наличия tsj_id в сессии
if (!isset($_SESSION['tsj_id'])) {
    // Если нет tsj_id, перенаправляем на страницу профиля или выводим ошибку
    header("Location: profile.php");
    exit;
}

$tsj_id = $_SESSION['tsj_id'];
$errors = [];
$success = null;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username'] ?? '');

    // Проверка, что введенное имя пользователя не пустое
    if (empty($username)) {
        $errors[] = "Имя пользователя не может быть пустым.";
    } else {
        try {
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
        } catch (PDOException $e) {
            $errors[] = "Ошибка при работе с базой данных: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Приглашение пользователя</title>
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
        .btn {
            display: inline-block;
            width: 100%;
            padding: 10px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s ease;
        }
        .btn:hover {
            background-color: #45a049;
        }
        .error {
            color: red;
            margin-bottom: 15px;
            font-size: 14px;
            text-align: left;
            padding: 10px;
            border: 1px solid red;
            border-radius: 5px;
            background-color: #ffe0e0;
        }
        .success {
            color: green;
            margin-bottom: 15px;
            font-size: 14px;
            text-align: left;
            padding: 10px;
            border: 1px solid green;
            border-radius: 5px;
            background-color: #e0ffe0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Пригласить пользователя</h2>
        
        <?php if (!empty($errors)): ?>
            <div class="error">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <form method="POST">
            <label for="username">Имя пользователя:</label>
            <input type="text" id="username" name="username" required><br><br>
            <button type="submit" class="btn">Пригласить</button>
        </form>

        <p><a href="profile.php">Вернуться в профиль</a></p>
    </div>
</body>
</html>