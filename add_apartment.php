<?php
session_start();
require_once 'db.php';
$pdo = require 'db.php';

// Проверка авторизации
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Проверка прав доступа
if (!in_array($_SESSION['role'], ['admin', 'owner'])) {
    die("У вас нет прав для добавления квартир.");
}

// Получение ID ТСЖ из сессии
$tsj_id = $_SESSION['tsj_id'];

// Получение списка жильцов для текущего ТСЖ
$stmt_residents = $pdo->prepare("SELECT * FROM residents WHERE tsj_id = :tsj_id");
$stmt_residents->execute(['tsj_id' => $tsj_id]);
$residents = $stmt_residents->fetchAll();

// Обработка отправки формы
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $apartment_number = trim($_POST['apartment_number'] ?? '');
    $area = floatval($_POST['area'] ?? 0);
    $rooms = intval($_POST['rooms'] ?? 0);
    $resident_id = intval($_POST['resident_id'] ?? 0);

    // Валидация данных
    if (empty($apartment_number) || $area <= 0 || $rooms <= 0) {
        die("Некорректные данные.");
    }

    try {
        // Добавляем квартиру в базу данных
        $stmt_add = $pdo->prepare("
            INSERT INTO apartments (tsj_id, apartment_number, area, rooms, status, resident_id) 
            VALUES (:tsj_id, :apartment_number, :area, :rooms, 'free', :resident_id)
        ");
        $stmt_add->execute([
            'tsj_id' => $tsj_id,
            'apartment_number' => $apartment_number,
            'area' => $area,
            'rooms' => $rooms,
            'resident_id' => $resident_id > 0 ? $resident_id : null
        ]);

        // Перенаправляем обратно на страницу управления квартирами
        header("Location: manage_apartments.php");
        exit;
    } catch (Exception $e) {
        die("Ошибка добавления квартиры: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Добавить квартиру | ТСЖ</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f5f5f5;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        h1 {
            text-align: center;
            color: #333;
        }
        label {
            display: block;
            margin-bottom: 5px;
            color: #555;
        }
        input, select {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 14px;
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
    </style>
</head>
<body>
<div class="container">
    <h1>Добавить квартиру</h1>
    <form method="POST" action="">
        <label for="apartment_number">Номер квартиры:</label>
        <input type="text" id="apartment_number" name="apartment_number" required>

        <label for="area">Площадь (м²):</label>
        <input type="number" step="0.01" id="area" name="area" required>

        <label for="rooms">Количество комнат:</label>
        <input type="number" id="rooms" name="rooms" required>

        <label for="resident_id">Жилец:</label>
        <select id="resident_id" name="resident_id">
            <option value="">Нет жильца</option>
            <?php foreach ($residents as $resident): ?>
                <option value="<?= $resident['id'] ?>">
                    <?= htmlspecialchars($resident['full_name']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <button type="submit" class="btn">Добавить квартиру</button>
    </form>
</div>
</body>
</html>