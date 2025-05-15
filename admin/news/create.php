<?php
session_start();

// Проверка авторизации и роли администратора
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit;
}

$pdo = require_once '../../db.php';

$errors = [];
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');

    if (!$title || !$content) {
        $errors[] = "Пожалуйста, заполните все поля.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO news (title, content, created_at) VALUES (:title, :content, NOW())");
        $stmt->execute([
            'title' => $title,
            'content' => $content
        ]);
        $success = "Новость успешно добавлена.";
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Создать новость</title>
    <link rel="stylesheet" href="../../styles.css">
    
    <!-- Подключение TinyMCE -->
    <script src="https://cdn.tiny.cloud/1/ojqx6jq1u8uzrt68twrzg3on3fo4bcm1ge1tqegym0hjpszr/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
    <script>
      tinymce.init({
        selector: 'textarea',
        language: 'ru',
        height: 300,
        plugins: 'link image lists code',
        toolbar: 'undo redo | bold italic underline | alignleft aligncenter alignright | bullist numlist | link image | code',
        branding: false
      });
    </script>
</head>
<body>
<div class="container">
    <h2>Создание новости</h2>

    <?php if ($success): ?>
        <p class="success"><?= htmlspecialchars($success) ?></p>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <ul class="error">
            <?php foreach ($errors as $error): ?>
                <li><?= htmlspecialchars($error) ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <form method="POST">
        <label for="title">Заголовок:</label>
        <input type="text" name="title" id="title" required>

        <label for="content">Содержимое:</label>
        <textarea name="content" id="content"></textarea>

        <button type="submit">Создать</button>
    </form>

    <p><a href="list.php">← Назад к списку новостей</a></p>
</div>
</body>
</html>