<?php
session_start();

// Подключение к базе данных через db.php
require_once 'db.php';
$pdo = require 'db.php';

// Переменные для сообщений
$success = null;
$errors = [];

// Обработка отправки формы
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Валидация данных
    if (empty($username)) {
        $errors[] = "Имя пользователя обязательно.";
    }

    if (empty($email)) {
        $errors[] = "Email обязателен.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Некорректный формат Email.";
    }

    if (empty($password)) {
        $errors[] = "Пароль обязателен.";
    }

    if ($password !== $confirm_password) {
        $errors[] = "Пароли не совпадают.";
    }

    // Проверка уникальности имени пользователя и email
    if (empty($errors)) {
        $stmt_check_username = $pdo->prepare("SELECT id FROM users WHERE username = :username");
        $stmt_check_username->execute(['username' => $username]);
        if ($stmt_check_username->fetchColumn()) {
            $errors[] = "Имя пользователя уже занято.";
        }

        $stmt_check_email = $pdo->prepare("SELECT id FROM users WHERE email = :email");
        $stmt_check_email->execute(['email' => $email]);
        if ($stmt_check_email->fetchColumn()) {
            $errors[] = "Email уже зарегистрирован.";
        }
    }

    // Если нет ошибок, создаем пользователя
    if (empty($errors)) {
        try {
            // Хэширование пароля
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // Добавляем пользователя в базу данных
            $stmt_add_user = $pdo->prepare("
                INSERT INTO users (username, email, password, role) 
                VALUES (:username, :email, :password, 'user')
            ");
            $stmt_add_user->execute([
                'username' => $username,
                'email' => $email,
                'password' => $hashed_password
            ]);

            $success = "Вы успешно зарегистрировались! Теперь вы можете войти в систему.";
        } catch (Exception $e) {
            $errors[] = "Ошибка регистрации: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Регистрация | ТСЖ</title>
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

        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 10px;
            margin-top: 5px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 16px;
        }
        
        input[type="email"] {
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

        /* Стиль ошибок */
        .error {
            color: red;
            margin-bottom: 15px;
            font-size: 14px;
        }

        /* Стиль ссылок */
        a {
            color: #4CAF50;
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
    <h2>Регистрация</h2>

    <?php if ($success): ?>
        <p class="success"><?= htmlspecialchars($success) ?></p>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div class="error">
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <!-- Форма для регистрации -->
    <form method="POST" action="">
        <label for="username">Имя пользователя:</label>
        <input type="text" id="username" name="username" required>

        <label for="email">Email:</label>
        <input type="email" id="email" name="email" required>

        <label for="password">Пароль:</label>
        <input type="password" id="password" name="password" required>

        <label for="confirm_password">Подтвердите пароль:</label>
        <input type="password" id="confirm_password" name="confirm_password" required>

        <button type="submit" class="btn">Зарегистрироваться</button>
    </form>

    <!-- Ссылка для входа -->
    <p>Уже есть аккаунт? <a href="login.php">Войти</a></p>
</div>
</body>
</html>