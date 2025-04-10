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

// Проверка прав доступа (только администраторы могут редактировать пользователей)
if ($_SESSION['role'] !== 'admin') {
    die("У вас нет прав для редактирования пользователей.");
}

// Получение ID пользователя из параметров GET
$user_id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);

if (!$user_id) {
    die("ID пользователя не указан или некорректен.");
}

// Получение данных пользователя
$stmt = $pdo->prepare("SELECT id, username, email, role, is_verified FROM users WHERE id = :id");
$stmt->execute(['id' => $user_id]);
$user = $stmt->fetch();

if (!$user) {
    die("Пользователь не найден.");
}

// Обработка отправки формы
$errors = [];
$success = null;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Фильтрация и очистка входных данных
    $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $role = filter_input(INPUT_POST, 'role', FILTER_SANITIZE_STRING);
    $is_verified = isset($_POST['is_verified']);

    // Валидация данных
    if (empty($username)) {
        $errors[] = "Имя пользователя обязательно.";
    }

    if (empty($email)) {
        $errors[] = "Email обязателен.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Некорректный формат Email.";
    }

    if (!in_array($role, ['user', 'owner', 'manager', 'admin'])) {
        $errors[] = "Некорректная роль.";
    }

    // Проверка уникальности имени пользователя и email
    if (empty($errors)) {
        $stmt_check_username = $pdo->prepare("SELECT id FROM users WHERE username = :username AND id != :user_id");
        $stmt_check_username->execute(['username' => $username, 'user_id' => $user_id]);
        if ($stmt_check_username->fetchColumn()) {
            $errors[] = "Имя пользователя уже занято.";
        }

        $stmt_check_email = $pdo->prepare("SELECT id FROM users WHERE email = :email AND id != :user_id");
        $stmt_check_email->execute(['email' => $email, 'user_id' => $user_id]);
        if ($stmt_check_email->fetchColumn()) {
            $errors[] = "Email уже зарегистрирован.";
        }
    }

    // Если нет ошибок, обновляем данные пользователя
    if (empty($errors)) {
        try {
            $stmt_update_user = $pdo->prepare("
                UPDATE users
                SET username = :username, email = :email, role = :role, is_verified = :is_verified
                WHERE id = :id
            ");
            $stmt_update_user->execute([
                'username' => $username,
                'email' => $email,
                'role' => $role,
                'is_verified' => $is_verified ? 1 : 0,
                'id' => $user_id
            ]);

            $success = "Данные пользователя успешно обновлены!";
            // Обновляем данные пользователя в сессии, если редактируется текущий пользователь
            if (isset($_SESSION['user_id']) && $_SESSION['user_id'] === $user_id) {
                $_SESSION['role'] = $role;
            }
        } catch (Exception $e) {
            $errors[] = "Ошибка обновления данных: " . $e->getMessage();
            // Логирование ошибки (в реальном приложении)
            error_log("Ошибка обновления пользователя ID " . $user_id . ": " . $e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Редактирование пользователя | ТСЖ</title>
    <style>
        /* Общие стили */
        body {
            font-family: Arial, sans-serif;
            background-color: #f5f5f5;
            margin: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh; /* Изменено на min-height для небольшого контента */
        }

        .container {
            max-width: 400px;
            padding: 30px; /* Увеличено padding */
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        h2 {
            color: #2c3e50;
            margin-bottom: 25px; /* Увеличено margin-bottom */
        }

        /* Стиль формы */
        label {
            display: block;
            margin-top: 18px; /* Увеличено margin-top */
            color: #555;
            font-size: 14px;
            text-align: left; /* Выравнивание текста слева */
        }

        input[type="text"],
        input[type="email"],
        select {
            width: 100%;
            padding: 12px; /* Увеличено padding */
            margin-top: 6px; /* Увеличено margin-top */
            margin-bottom: 20px; /* Увеличено margin-bottom */
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 16px;
            box-sizing: border-box; /* Добавлено для правильного расчета ширины */
        }

        /* Стиль кнопки */
        .btn {
            display: inline-block;
            width: 100%;
            padding: 12px; /* Увеличено padding */
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s ease;
            margin-top: 15px; /* Добавлено margin-top */
        }

        .btn:hover {
            background-color: #45a049;
        }

        /* Стиль ошибок */
        .error {
            color: red;
            margin-bottom: 20px; /* Увеличено margin-bottom */
            font-size: 14px;
            text-align: left; /* Выравнивание текста слева */
        }

        .error ul {
            padding-left: 20px;
        }

        /* Стиль сообщения об успехе */
        .success {
            color: green;
            margin-bottom: 20px; /* Увеличено margin-bottom */
            font-size: 14px;
            text-align: center;
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
    <h2>Редактирование пользователя</h2>

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

    <form method="POST" action="">
        <label for="username">Имя пользователя:</label>
        <input type="text" id="username" name="username" value="<?= htmlspecialchars($user['username']) ?>" required>

        <label for="email">Email:</label>
        <input type="email" id="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>

        <label for="role">Роль:</label>
        <select id="role" name="role" required>
            <option value="user" <?= $user['role'] === 'user' ? 'selected' : '' ?>>Пользователь</option>
            <option value="owner" <?= $user['role'] === 'owner' ? 'selected' : '' ?>>Владелец ТСЖ</option>
            <option value="manager" <?= $user['role'] === 'manager' ? 'selected' : '' ?>>Менеджер</option>
            <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Администратор</option>
        </select>

        <label for="is_verified">Подтвержденный Email:</label>
        <input type="checkbox" id="is_verified" name="is_verified" <?= $user['is_verified'] ? 'checked' : '' ?>>

        <button type="submit" class="btn">Сохранить изменения</button>
    </form>

    <p style="margin-top: 20px;"><a href="admin_dashboard.php">Назад в панель управления</a></p>
</div>
</body>
</html>