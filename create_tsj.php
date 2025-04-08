<?php
session_start();
require_once 'db.php';
$pdo = require 'db.php';

// Проверка авторизации
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Проверка, что пользователь является администратором
if ($_SESSION['role'] !== 'admin') {
    die("Только администраторы могут создавать ТСЖ.");
}

$errors = [];
$success = null;

// Получение списка пользователей для выбора владельца
$stmt_users = $pdo->query("SELECT id, username FROM users WHERE role = 'user' OR role IS NULL");
$users = $stmt_users->fetchAll();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $owner_id = intval($_POST['owner_id'] ?? 0);

    if (empty($name)) {
        $errors[] = "Название ТСЖ обязательно.";
    }

    if (empty($address)) {
        $errors[] = "Адрес ТСЖ обязателен.";
    }

    if ($owner_id <= 0) {
        $errors[] = "Необходимо выбрать владельца ТСЖ.";
    }

    if (empty($errors)) {
        try {
            // Начало транзакции
            $pdo->beginTransaction();

            // Создаем новое ТСЖ
            $stmt_create_tsj = $pdo->prepare("INSERT INTO tsj (name, address, owner_id) VALUES (:name, :address, :owner_id)");
            $stmt_create_tsj->execute([
                'name' => $name,
                'address' => $address,
                'owner_id' => $owner_id
            ]);

            $tsj_id = $pdo->lastInsertId();

            // Обновляем роль выбранного пользователя на 'owner' и привязываем к ТСЖ
            $stmt_update_user = $pdo->prepare("UPDATE users SET role = 'owner', tsj_id = :tsj_id WHERE id = :id");
            $stmt_update_user->execute(['tsj_id' => $tsj_id, 'id' => $owner_id]);

            // Завершение транзакции
            $pdo->commit();
            $success = "ТСЖ успешно создано!";
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = "Ошибка создания ТСЖ: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Создать ТСЖ</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f2f2f2;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .container {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            max-width: 400px;
            width: 100%;
        }
        .btn {
            background: #4CAF50;
            color: white;
            padding: 10px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            width: 100%;
        }
        .error { color: red; }
        .success { color: green; }
    </style>
</head>
<body>
<div class="container">
    <h2>Создать ТСЖ</h2>
    
    <?php if ($success): ?>
        <p class="success"><?= $success ?></p>
    <?php endif; ?>
    
    <?php if (!empty($errors)): ?>
        <div class="error">
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?= $error ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    
    <form method="POST">
        <label>Название ТСЖ:</label>
        <input type="text" name="name" required><br><br>
        
        <label>Адрес ТСЖ:</label>
        <textarea name="address" required></textarea><br><br>
        
        <label>Выберите владельца ТСЖ:</label>
        <select name="owner_id" required>
            <option value="">-- Выберите пользователя --</option>
            <?php foreach ($users as $user): ?>
                <option value="<?= $user['id'] ?>"><?= htmlspecialchars($user['username']) ?></option>
            <?php endforeach; ?>
        </select><br><br>
        
        <button type="submit" class="btn">Создать ТСЖ</button>
    </form>
    <p><a href="profile.php" style="color: #4CAF50;">Назад в профиль</a></p>
</div>
</body>
</html>