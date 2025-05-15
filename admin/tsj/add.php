<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit;
}
require_once '../../db.php';
$pdo = require '../../db.php';

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
        $stmt = $pdo->prepare("INSERT INTO tszh (name, address) VALUES (:name, :address)");
        $stmt->execute(['name' => $name, 'address' => $address]);
        $success = "ТСЖ успешно добавлено.";
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Добавить ТСЖ | ТСЖ</title>
    <!-- Подключаем Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h2 class="text-center">Добавить новое ТСЖ</h2>
        
        <?php if ($success): ?>
            <div class="alert alert-success text-center"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="mb-3">
                <label for="name" class="form-label">Название ТСЖ:</label>
                <input type="text" class="form-control" id="name" name="name" required>
            </div>

            <div class="mb-3">
                <label for="address" class="form-label">Адрес ТСЖ:</label>
                <input type="text" class="form-control" id="address" name="address" required>
            </div>

            <button type="submit" class="btn btn-primary">Добавить</button>
        </form>

        <p class="mt-3 text-center"><a href="list.php" class="btn btn-secondary">Назад к списку ТСЖ</a></p>
    </div>
    
<?php include '../footer.php';?>