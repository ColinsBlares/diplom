<?php
session_start();
require_once '../db.php';
$pdo = require '../db.php';

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
    <title>Управление квартирами | ТСЖ</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-5">
    <h1 class="text-center mb-4">Управление квартирами</h1>

    <div class="d-flex justify-content-end mb-3">
        <a href="add_apartment" class="btn btn-success">Добавить квартиру</a>
    </div>
    

    <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle">
            <thead class="table-light">
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
                        <td><?= $apartment['status'] === 'free' ? 'Свободна' : 'Занята' ?></td>
                        <td><?= htmlspecialchars($apartment['full_name'] ?? 'Нет жильца') ?></td>
                        <td>
                            <form method="POST" class="mb-2">
                                <input type="hidden" name="apartment_id" value="<?= $apartment['id'] ?>">

                                <div class="mb-2">
                                    <label for="status_<?= $apartment['id'] ?>" class="form-label">Статус</label>
                                    <select class="form-select" name="status" id="status_<?= $apartment['id'] ?>" required>
                                        <option value="free" <?= $apartment['status'] === 'free' ? 'selected' : '' ?>>Свободна</option>
                                        <option value="occupied" <?= $apartment['status'] === 'occupied' ? 'selected' : '' ?>>Занята</option>
                                    </select>
                                </div>

                                <?php if ($apartment['status'] === 'occupied'): ?>
                                    <div class="mb-2">
                                        <label for="resident_id_<?= $apartment['id'] ?>" class="form-label">Жилец</label>
                                        <select name="resident_id" id="resident_id_<?= $apartment['id'] ?>" class="form-select" required>
                                            <option value="">Выберите жильца</option>
                                            <?php
                                            $stmt_residents = $pdo->prepare("SELECT * FROM residents WHERE tsj_id = :tsj_id");
                                            $stmt_residents->execute(['tsj_id' => $tsj_id]);
                                            $residents = $stmt_residents->fetchAll();
                                            foreach ($residents as $resident): ?>
                                                <option value="<?= $resident['id'] ?>" <?= $apartment['resident_id'] == $resident['id'] ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($resident['full_name']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                <?php endif; ?>

                                <button type="submit" class="btn btn-primary w-100">Сохранить</button>
                            </form>

                            <a href="edit_apartment.php?id=<?= $apartment['id'] ?>" class="btn btn-outline-secondary w-100">Редактировать</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php
include '../admin/footer.php';
?>