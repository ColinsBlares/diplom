<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit;
}
require_once '../../db.php';
$pdo = require '../../db.php';

$user_id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);
if (!$user_id) {
    die("ID пользователя не указан.");
}

$stmt = $pdo->prepare("SELECT id, username, email, role, is_verified, full_name, phone, date_of_birth, gender, agreement FROM users WHERE id = :id");
$stmt->execute(['id' => $user_id]);
$user = $stmt->fetch();

if (!$user) {
    die("Пользователь не найден.");
}

$errors = [];
$success = null;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $role = filter_input(INPUT_POST, 'role', FILTER_SANITIZE_STRING);
    $is_verified = isset($_POST['is_verified']) ? 1 : 0;
    $full_name = filter_input(INPUT_POST, 'full_name', FILTER_SANITIZE_STRING);
    $phone = filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_STRING);
    $date_of_birth = filter_input(INPUT_POST, 'date_of_birth', FILTER_SANITIZE_STRING);
    $gender = filter_input(INPUT_POST, 'gender', FILTER_SANITIZE_STRING);
    $agreement = isset($_POST['agreement']) ? 1 : 0;

    // Валидация данных
    if (empty($username)) {
        $errors[] = "Имя пользователя обязательно для заполнения.";
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Некорректный формат email.";
    }
    if (!in_array($role, ['user', 'owner', 'manager', 'admin'])) {
        $errors[] = "Выбрана некорректная роль.";
    }
    if (empty($full_name)) {
        $errors[] = "Полное имя обязательно.";
    }
    if (empty($phone)) {
        $errors[] = "Номер телефона обязателен.";
    }
    if (empty($date_of_birth)) {
        $errors[] = "Дата рождения обязательна.";
    }
    if (empty($gender)) {
        $errors[] = "Пол обязателен.";
    }
    if (!in_array($gender, ['male', 'female', 'other'])) {
        $errors[] = "Выберите корректный пол.";
    }

    // Если ошибок нет, обновляем данные пользователя
    if (empty($errors)) {
        $stmt = $pdo->prepare("UPDATE users SET username = :username, email = :email, role = :role, is_verified = :is_verified, full_name = :full_name, phone = :phone, date_of_birth = :date_of_birth, gender = :gender, agreement = :agreement WHERE id = :id");
        $stmt->execute([
            'username' => $username,
            'email' => $email,
            'role' => $role,
            'is_verified' => $is_verified,
            'full_name' => $full_name,
            'phone' => $phone,
            'date_of_birth' => $date_of_birth,
            'gender' => $gender,
            'agreement' => $agreement,
            'id' => $user_id
        ]);
        $success = "Данные пользователя успешно обновлены.";
        // Обновляем данные пользователя в $user для отображения актуальной информации в форме
        $stmt->execute(['id' => $user_id]);
        $user = $stmt->fetch();
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
            background-color: #f4f4f4;
            margin: 0;
            padding: 20px;
            display: flex;
            justify-content: center;
        }

        .container {
            background-color: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            width: 90%;
            max-width: 600px;
        }

        h2 {
            color: #333;
            margin-bottom: 20px;
            text-align: center;
        }

        .success {
            color: green;
            margin-bottom: 15px;
            text-align: center;
        }

        .error {
            background-color: #ffe0e0;
            color: #d32f2f;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        .error ul {
            list-style-type: none;
            padding: 0;
            margin: 0;
        }

        .error li {
            margin-bottom: 5px;
        }

        form {
            display: flex;
            flex-direction: column;
        }

        label {
            margin-bottom: 5px;
            font-weight: bold;
            color: #555;
        }

        input[type="text"],
        input[type="email"],
        input[type="date"],
        select {
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 16px;
        }

        input[type="checkbox"] {
            margin-top: 10px;
            margin-bottom: 15px;
        }

        .btn {
            padding: 12px 15px;
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

        a {
            display: block;
            margin-top: 20px;
            color: #007bff;
            text-decoration: none;
            text-align: center;
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
        <form method="POST">
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

            <label for="full_name">Полное имя:</label>
            <input type="text" id="full_name" name="full_name" value="<?= htmlspecialchars($user['full_name']) ?>" required>

            <label for="phone">Телефон:</label>
            <input type="text" id="phone" name="phone" value="<?= htmlspecialchars($user['phone']) ?>" required>

            <label for="date_of_birth">Дата рождения:</label>
            <input type="date" id="date_of_birth" name="date_of_birth" value="<?= htmlspecialchars($user['date_of_birth']) ?>" required>

            <label for="gender">Пол:</label>
            <select id="gender" name="gender" required>
                <option value="male" <?= $user['gender'] === 'male' ? 'selected' : '' ?>>Мужской</option>
                <option value="female" <?= $user['gender'] === 'female' ? 'selected' : '' ?>>Женский</option>
                <option value="other" <?= $user['gender'] === 'other' ? 'selected' : '' ?>>Другой</option>
            </select>

            <button type="submit" class="btn">Сохранить</button>
        </form>
        <p><a href="list.php">Назад к списку пользователей</a></p>
    </div>
</body>
</html>