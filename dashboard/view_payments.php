<?php
session_start();

// Подключение к базе данных
require_once '../db.php';
$pdo = require '../db.php';

// Проверка авторизации
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

// Получение ID ТСЖ из GET-параметра и валидация
$id_tszh = filter_input(INPUT_GET, 'id_tszh', FILTER_VALIDATE_INT);
if ($id_tszh === false || $id_tszh <= 0) {
    die("Некорректный ID ТСЖ.");
}

// Проверка прав доступа для добавления платежа (только админ)
$user_role = $_SESSION['role'] ?? 'guest';
if ($user_role !== 'admin' || 'owner') {
    $can_add_payment = true;
} else {
    $can_add_payment = false;
}

// Проверка прав доступа для просмотра платежей этого ТСЖ
// (Админ может видеть все, обычный пользователь - только свои, если применимо)
$user_id = $_SESSION['user_id'];

if ($user_role !== 'admin') {
    // Для не-админов может потребоваться дополнительная проверка связи пользователя с ТСЖ
    $stmt_check_tszh = $pdo->prepare("SELECT tsj_id FROM users WHERE id = :user_id");
    $stmt_check_tszh->execute(['user_id' => $user_id]);
    $user_tszh_id = $stmt_check_tszh->fetchColumn();

    if ($user_tszh_id !== $id_tszh) {
        die("У вас нет прав для просмотра платежей этого ТСЖ.");
    }
}

$payments = [];
$error = null;

try {
    $sql = "SELECT p.*, u.username
            FROM payments p
            JOIN users u ON p.user_id = u.id
            WHERE p.tsj_id = :id_tszh
            ORDER BY p.created_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute(['id_tszh' => $id_tszh]);
    $payments = $stmt->fetchAll();

} catch (PDOException $e) {
    $error = "Ошибка при получении истории платежей: " . $e->getMessage();
}

?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>История платежей | ТСЖ</title>
    <link rel="stylesheet" href="../styles.css">
    <style>
        .actions {
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
            align-items: center;
        }
        .filter-form {
            margin-bottom: 20px;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            background-color: #f9f9f9;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .filter-form label {
            font-weight: bold;
        }

        .filter-form select {
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }

        .filter-form button {
            padding: 8px 15px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
        }

        .filter-form button:hover {
            background-color: #0056b3;
        }
        .add-payment-btn {
            display: inline-block;
            padding: 10px 15px;
            background-color: #28a745; /* Green color for add */
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
        }

        .add-payment-btn:hover {
            background-color: #1e7e34;
        }
    </style>
</head>
<body>
<div class="container">
    <h1>История платежей ТСЖ (ID: <?= htmlspecialchars($id_tszh) ?>)</h1>

    <div class="actions">
        <a href="dashboard.php?id_tszh=<?= htmlspecialchars($id_tszh) ?>" class="btn">Назад в панель управления</a>
        <?php if ($can_add_payment): ?>
            <a href="add_payment.php?id_tszh=<?= htmlspecialchars($id_tszh) ?>" class="add-payment-btn">Добавить платеж</a>
        <?php endif; ?>
    </div>

    <?php if ($error): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if (!empty($payments)): ?>
        <table>
            <thead>
                <tr>
                    <th>Дата платежа</th>
                    <th>Плательщик</th>
                    <th>Сумма</th>
                    <th>Статус</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($payments as $payment): ?>
                    <tr>
                        <td><?= htmlspecialchars($payment['created_at']) ?></td>
                        <td><?= htmlspecialchars($payment['username']) ?></td>
                        <td><?= htmlspecialchars($payment['amount']) ?></td>
                        <td><?= htmlspecialchars($payment['status']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>Нет записей о платежах для данного ТСЖ.</p>
    <?php endif; ?>
</div>
</body>
</html>