<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit;
}
require_once '../../db.php';
$pdo = require '../../db.php';

$stmt = $pdo->query("SELECT id, name, address FROM tsj");
$tsj_list = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление ТСЖ | ТСЖ</title>
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
            max-width: 800px;
        }

        h2 {
            color: #333;
            margin-bottom: 20px;
            text-align: center;
        }

        .add-new {
            margin-bottom: 20px;
            text-align: right;
        }

        .add-new a {
            display: inline-block;
            padding: 10px 15px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.3s ease;
        }

        .add-new a:hover {
            background-color: #0056b3;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 10px;
            border: 1px solid #ddd;
            text-align: left;
        }

        th {
            background-color: #f2f2f2;
        }

        tbody tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        tbody tr:hover {
            background-color: #e9e9e9;
        }

        .actions a {
            display: inline-block;
            margin-right: 10px;
            text-decoration: none;
            color: #007bff;
            transition: color 0.3s ease;
        }

        .actions a:hover {
            color: #0056b3;
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
        <h2>Управление ТСЖ</h2>
        <div class="add-new">
            <a href="../../create_tsj.php">Добавить новое ТСЖ</a>
        </div>
        <?php if (!empty($tsj_list)): ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Название</th>
                        <th>Адрес</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tsj_list as $tsj): ?>
                        <tr>
                            <td><?= htmlspecialchars($tsj['id']) ?></td>
                            <td><?= htmlspecialchars($tsj['name']) ?></td>
                            <td><?= htmlspecialchars($tsj['address']) ?></td>
                            <td class="actions">
                                <a href="edit.php?id=<?= $tsj['id'] ?>">Редактировать</a>
                                <a href="delete.php?id=<?= $tsj['id'] ?>" onclick="return confirm('Вы уверены, что хотите удалить это ТСЖ?')">Удалить</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>Нет зарегистрированных ТСЖ.</p>
        <?php endif; ?>
        <p class="back-link"><a href="../admin_dashboard.php">Назад в админ-панель</a></p>
    </div>
</body>
</html>