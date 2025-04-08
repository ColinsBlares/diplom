<?php
session_start();

// Подключение к базе данных через db.php
require_once 'db.php';
$pdo = require 'db.php';

// Проверка прав доступа
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// Получение ID текущего пользователя
$user_id = $_SESSION['user_id'];

// Обработка POST-запроса
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $tsj_name = trim($_POST['tsj_name'] ?? '');
    $address = trim($_POST['address'] ?? '');

    // Валидация данных
    $errors = [];

    if (empty($tsj_name)) {
        $errors[] = "Название ТСЖ обязательно.";
    } elseif (strlen($tsj_name) < 3) {
        $errors[] = "Название ТСЖ должно содержать минимум 3 символа.";
    }

    if (empty($address)) {
        $errors[] = "Адрес ТСЖ обязателен.";
    }

    // Проверка уникальности названия ТСЖ
    $stmt_check = $pdo->prepare("SELECT COUNT(*) AS count FROM tsj WHERE name = :name");
    $stmt_check->execute(['name' => $tsj_name]);
    $count = $stmt_check->fetchColumn();

    if ($count > 0) {
        $errors[] = "ТСЖ с таким названием уже существует.";
    }

    // Если нет ошибок, добавляем ТСЖ в базу данных
    if (empty($errors)) {
        try {
            // Начало транзакции
            $pdo->beginTransaction();

            // Добавление ТСЖ
            $stmt_insert_tsj = $pdo->prepare("INSERT INTO tsj (name, address, owner_id) VALUES (:name, :address, :owner_id)");
            $stmt_insert_tsj->execute([
                'name' => $tsj_name,
                'address' => $address,
                'owner_id' => $user_id
            ]);

            // Присвоение роли "владелец" создателю ТСЖ
            $tsj_id = $pdo->lastInsertId(); // Получаем ID созданного ТСЖ
            $stmt_update_user = $pdo->prepare("UPDATE users SET role = 'owner', tsj_id = :tsj_id WHERE id = :user_id");
            $stmt_update_user->execute([
                'tsj_id' => $tsj_id,
                'user_id' => $user_id
            ]);

            // Завершение транзакции
            $pdo->commit();

            // Успешное создание ТСЖ
            $_SESSION['success_message'] = "ТСЖ успешно создано!";
            header("Location: admin_dashboard.php");
            exit;

        } catch (Exception $e) {
            // Откат транзакции при ошибке
            $pdo->rollBack();
            $errors[] = "Произошла ошибка при создании ТСЖ. Попробуйте снова.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Создание ТСЖ</title>
    <!-- ... CSS ... -->
</head>
<body>
<div class="container">
    <h2>Создание ТСЖ</h2>

    <!-- Вывод сообщений об ошибках -->
    <?php if (!empty($errors)): ?>
        <div class="error">
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="POST" action="">
        <label for="tsj_name">Название ТСЖ:</label>
        <input type="text" id="tsj_name" name="tsj_name" required>

        <label for="address">Адрес:</label>
        <input type="text" id="address" name="address" required>

        <button type="submit" class="btn">Создать ТСЖ</button>
    </form>
    <p><a href="profile.php">Назад</a></p>
</div>
</body>
</html>