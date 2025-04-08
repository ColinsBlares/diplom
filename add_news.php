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

// Проверка прав доступа (только администраторы, владельцы или менеджеры могут добавлять новости)
if (!in_array($_SESSION['role'], ['admin', 'owner', 'manager'])) {
    header("Location: profile.php");
    exit;
}

// Переменные для сообщений
$success = null;
$errors = [];

// Обработка отправки формы
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');

    // Валидация данных
    if (empty($title)) {
        $errors[] = "Заголовок новости обязателен.";
    }

    if (empty($content)) {
        $errors[] = "Текст новости обязателен.";
    }

    // Если нет ошибок, сохраняем новость
    if (empty($errors)) {
        try {
            $tsj_id = $_SESSION['tsj_id']; // ID текущего ТСЖ из сессии

            // Добавляем новость в базу данных
            $stmt_add_news = $pdo->prepare("INSERT INTO news (tsj_id, title, content) VALUES (:tsj_id, :title, :content)");
            $stmt_add_news->execute([
                'tsj_id' => $tsj_id,
                'title' => $title,
                'content' => $content
            ]);

            $success = "Новость успешно добавлена!";
        } catch (Exception $e) {
            $errors[] = "Ошибка добавления новости: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Добавить новость | ТСЖ</title>
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
            max-width: 600px;
            padding: 20px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        h2 {
            color: #333;
            margin-bottom: 20px;
        }

        form {
            text-align: left;
        }

        label {
            display: block;
            margin-bottom: 5px;
            color: #555;
        }

        input, textarea {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 14px;
        }

        textarea {
            resize: vertical;
            height: 100px;
        }

        .btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            font-size: 16px;
            transition: background-color 0.3s ease;
        }

        .btn:hover {
            background-color: #45a049;
        }

        .error { color: red; }
        .success { color: green; }
    </style>
</head>
<body>
<div class="container">
    <h2>Добавить новость</h2>

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

    <!-- Форма для добавления новости -->
    <form method="POST" action="">
        <label for="title">Заголовок новости:</label>
        <input type="text" id="title" name="title" required>

        <label for="content">Текст новости:</label>
        <textarea id="content" name="content" required></textarea>

        <button type="submit" class="btn">Добавить новость</button>
    </form>

    <!-- Ссылка для возврата -->
    <p><a href="manage_tsj.php" class="btn">Назад</a></p>
</div>
</body>
</html>