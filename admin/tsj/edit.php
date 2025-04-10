<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit;
}
require_once '../../db.php';
$pdo = require '../../db.php';

$tsj_id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);
if (!$tsj_id) {
    die("ID ТСЖ не указан.");
}

$stmt = $pdo->prepare("SELECT id, name, address FROM tsj WHERE id = :id");
$stmt->execute(['id' => $tsj_id]);
$tsj = $stmt->fetch();

if (!$tsj) {
    die("ТСЖ не найдено.");
}

$errors = [];
$success = null;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
    $address = filter_input(INPUT_POST, 'address', FILTER_SANITIZE_STRING);

    if (empty($name)) {
        $errors[] = "Название ТСЖ обязательно для заполнения.";
    }
    if (empty($address)) {
        $errors[] = "Адрес ТСЖ обязателен для заполнения.";
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("UPDATE tsj SET name = :name, address = :address WHERE id = :id");
        $stmt->execute(['name' => $name, 'address' => $address, 'id' => $tsj_id]);
        $success = "Данные ТСЖ успешно обновлены.";
        // Обновляем данные $tsj для отображения в форме
        $stmt->execute(['id' => $tsj_id]);
        $tsj = $stmt->fetch();
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Редактировать ТСЖ | ТСЖ</title>
    <style>
        /* Ваши стили CSS здесь (можно вынести в отдельный файл) */
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

        input[type="text"] {
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 16px;
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

        .back-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: #007bff;
            text-decoration: none;
        }

        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Редактировать ТСЖ</h2>
        <?php if ($success): ?>
            <p class="success"><?= htmlspecialchars(<span class="math-inline">success\) ?\></p\>
<?php endif; ?\>
<?php if \(\!empty\(</span>errors)): ?>
            <div class="error">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        <form method="POST">
            <label for="name">Название ТСЖ:</label>
            <input type="text" id="name" name="name" value="<?= htmlspecialchars($tsj['name']) ?>" required>

            <label for="address">Адрес ТСЖ:</label>
            <input type="text" id="address" name="address" value="<?= htmlspecialchars($tsj['address']) ?>" required>

            <button type="submit" class="btn">Сохранить</button>
        </form>
        <p class="back-link"><a href="list.php">Назад к списку ТСЖ</a></p>