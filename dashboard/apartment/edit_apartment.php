<?php
session_start();
require_once '../db.php';
$pdo = require '../db.php';

// Проверка авторизации
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Проверка прав доступа
if (!in_array($_SESSION['role'], ['admin', 'owner'])) {
    die("У вас нет прав для редактирования квартир.");
}

// Получение ID ТСЖ из сессии
$tsj_id = $_SESSION['tsj_id'];

// Получение ID квартиры из параметров GET
$apartment_id = intval($_GET['id'] ?? 0);

// Получение данных квартиры
$stmt_apartment = $pdo->prepare("SELECT * FROM apartments WHERE id = :id AND tsj_id = :tsj_id");
$stmt_apartment->execute(['id' => $apartment_id, 'tsj_id' => $tsj_id]);
$apartment = $stmt_apartment->fetch();

if (!$apartment) {
    die("Квартира не найдена.");
}

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
        // Обновляем данные квартиры
        $stmt_update = $pdo->prepare("
            UPDATE apartments 
            SET apartment_number = :apartment_number, 
                area = :area, 
                rooms = :rooms, 
                resident_id = :resident_id 
            WHERE id = :id AND tsj_id = :tsj_id
        ");
        $stmt_update->execute([
            'apartment_number' => $apartment_number,
            'area' => $area,
            'rooms' => $rooms,
            'resident_id' => $resident_id > 0 ? $resident_id : null,
            'id' => $apartment_id,
            'tsj_id' => $tsj_id
        ]);

        // Перенаправляем обратно на страницу управления квартирами
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
    <title>Редактировать квартиру | ТСЖ</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-5">
    <div class="card shadow-sm">
        <div class="card-body">
            <h1 class="card-title text-center mb-4">Редактировать квартиру</h1>

            <form method="POST" action="">
                <div class="mb-3">
                    <label for="apartment_number" class="form-label">Номер квартиры:</label>
                    <input type="text" id="apartment_number" name="apartment_number"
                           value="<?= htmlspecialchars($apartment['apartment_number']) ?>"
                           class="form-control" required>
                </div>

                <div class="mb-3">
                    <label for="area" class="form-label">Площадь (м²):</label>
                    <input type="number" step="0.01" id="area" name="area"
                           value="<?= htmlspecialchars($apartment['area']) ?>"
                           class="form-control" required>
                </div>

                <div class="mb-3">
                    <label for="rooms" class="form-label">Количество комнат:</label>
                    <input type="number" id="rooms" name="rooms"
                           value="<?= htmlspecialchars($apartment['rooms']) ?>"
                           class="form-control" required>
                </div>

                <div class="mb-4">
                    <label for="resident_id" class="form-label">Жилец:</label>
                    <select id="resident_id" name="resident_id" class="form-select">
                        <option value="">Нет жильца</option>
                        <?php foreach ($residents as $resident): ?>
                            <option value="<?= $resident['id'] ?>"
                                <?= $apartment['resident_id'] == $resident['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($resident['full_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="d-grid">
                    <button type="submit" class="btn btn-success">Сохранить изменения</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>