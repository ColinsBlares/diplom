<?php
session_start();
require_once 'db.php';
$pdo = require 'db.php';

// Проверка авторизации
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Проверка прав доступа (только администраторы или владельцы могут управлять квартирами)
if (!in_array($_SESSION['role'], ['admin', 'owner'])) {
    die("У вас нет прав для управления квартирами.");
}

// Получение ID ТСЖ из сессии
$tsj_id = $_SESSION['tsj_id'];

// Получение списка квартир для текущего ТСЖ
$stmt_apartments = $pdo->prepare("
    SELECT a.*, r.full_name 
    FROM apartments a 
    LEFT JOIN residents r ON a.resident_id = r.id 
    WHERE a.tsj_id = :tsj_id
");
$stmt_apartments->execute(['tsj_id' => $tsj_id]);
$apartments = $stmt_apartments->fetchAll();

// Обработка формы изменения статуса квартиры
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $apartment_id = intval($_POST['apartment_id'] ?? 0);
    $status = trim($_POST['status'] ?? '');
    $resident_id = intval($_POST['resident_id'] ?? 0);

    // Валидация данных
    if ($apartment_id <= 0 || !in_array($status, ['free', 'occupied'])) {
        die("Некорректные данные.");
    }

    try {
        // Обновляем данные квартиры
        $stmt_update = $pdo->prepare("
            UPDATE apartments 
            SET status = :status, resident_id = :resident_id 
            WHERE id = :id AND tsj_id = :tsj_id
        ");
        $stmt_update->execute([
            'status' => $status,
            'resident_id' => $status === 'free' ? null : $resident_id,
            'id' => $apartment_id,
            'tsj_id' => $tsj_id
        ]);

        // Перенаправляем обратно на страницу
        header("Location: manage_apartments.php");
        exit;
    } catch (Exception $e) {
        die("Ошибка обновления данных: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление квартирами | ТСЖ</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f5f5f5;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 1200px;
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
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #f3f3f3;
        }
        tr:hover {
            background-color: #f8f9fa;
        }
        form {
            margin-top: 20px;
        }
        label {
            display: block;
            margin-bottom: 5px;
        }
        select, input[type="submit"] {
            width: 100%;
            padding: 10px;
            margin-bottom: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        input[type="submit"] {
            background-color: #4CAF50;
            color: white;
            cursor: pointer;
        }
        input[type="submit"]:hover {
            background-color: #45a049;
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
            font-size: 14px;
            transition: background-color 0.3s;
        }
        .btn:hover {
            background-color: #45a049;
        }
        .actions {
            margin-top: 20px;
        }
        .actions a {
            margin-right: 10px;
        }
    </style>
</head>
<body>
<div class="container">
    <h1>Управление квартирами</h1>

    <!-- Кнопки действий -->
    <div class="actions">
        <a href="add_apartment.php" class="btn">Добавить квартиру</a>
    </div>

    <!-- Список квартир -->
    <table>
        <thead>
            <tr>
                <th>Номер квартиры</th>
                <th>Площадь (м²)</th>
                <th>Количество комнат</th>
                <th>Статус</th>
                <th>Жилец</th>
                <th>Действия</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($apartments as $apartment): ?>
                <tr>
                    <td><?= htmlspecialchars($apartment['apartment_number']) ?></td>
                    <td><?= htmlspecialchars($apartment['area']) ?></td>
                    <td><?= htmlspecialchars($apartment['rooms']) ?></td>
                    <td><?= htmlspecialchars($apartment['status'] === 'free' ? 'Свободна' : 'Занята') ?></td>
                    <td><?= htmlspecialchars($apartment['full_name'] ?? 'Нет жильца') ?></td>
                    <td>
                        <form method="POST" action="">
                            <input type="hidden" name="apartment_id" value="<?= $apartment['id'] ?>">
                            <label for="status_<?= $apartment['id'] ?>">Статус:</label>
                            <select name="status" id="status_<?= $apartment['id'] ?>" required>
                                <option value="free" <?= $apartment['status'] === 'free' ? 'selected' : '' ?>>Свободна</option>
                                <option value="occupied" <?= $apartment['status'] === 'occupied' ? 'selected' : '' ?>>Занята</option>
                            </select>
                            <?php if ($apartment['status'] === 'occupied'): ?>
                                <label for="resident_id_<?= $apartment['id'] ?>">Жилец:</label>
                                <select name="resident_id" id="resident_id_<?= $apartment['id'] ?>" required>
                                    <option value="">Выберите жильца</option>
                                    <?php
                                    // Получаем список всех жильцов для текущего ТСЖ
                                    $stmt_residents = $pdo->prepare("SELECT * FROM residents WHERE tsj_id = :tsj_id");
                                    $stmt_residents->execute(['tsj_id' => $tsj_id]);
                                    $residents = $stmt_residents->fetchAll();
                                    foreach ($residents as $resident): ?>
                                        <option value="<?= $resident['id'] ?>" <?= $apartment['resident_id'] == $resident['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($resident['full_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            <?php endif; ?>
                            <input type="submit" value="Сохранить">
                        </form>
                        <a href="edit_apartment.php?id=<?= $apartment['id'] ?>" class="btn">Редактировать</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
</body>
</html>