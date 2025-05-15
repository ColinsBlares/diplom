<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit;
}
require_once '../../db.php';
$pdo = require '../../db.php';

// Обработка фильтров и поиска
$whereClauses = [];
$params = [];

if (!empty($_GET['search'])) {
    $search = '%' . $_GET['search'] . '%';
    $whereClauses[] = "(username LIKE ? OR email LIKE ?)";
    $params[] = $search;
    $params[] = $search;
}

if (!empty($_GET['role'])) {
    $role = $_GET['role'];
    $whereClauses[] = "role = ?";
    $params[] = $role;
}

if (!empty($_GET['is_verified'])) {
    $is_verified = $_GET['is_verified'];
    $whereClauses[] = "is_verified = ?";
    $params[] = $is_verified;
}

$whereSql = !empty($whereClauses) ? 'WHERE ' . implode(' AND ', $whereClauses) : '';
$stmt = $pdo->prepare("SELECT id, username, email, role, is_verified FROM users $whereSql");
$stmt->execute($params);
$users = $stmt->fetchAll();

// Получаем роли для фильтра
$rolesStmt = $pdo->query("SELECT DISTINCT role FROM users");
$roles = $rolesStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление пользователями | ТСЖ</title>
    <!-- Подключение Bootstrap CSS -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

    <div class="container mt-5">
        <h2 class="text-center mb-4">Управление пользователями</h2>

        <!-- Форма поиска и фильтров -->
        <form method="get" class="mb-4">
            <div class="row">
                <div class="col-md-3">
                    <input type="text" name="search" class="form-control" placeholder="Поиск по имени или email" value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                </div>
                <div class="col-md-3">
                    <select name="role" class="form-control">
                        <option value="">Все роли</option>
                        <?php foreach ($roles as $roleOption): ?>
                            <option value="<?= $roleOption['role'] ?>" <?= isset($_GET['role']) && $_GET['role'] === $roleOption['role'] ? 'selected' : '' ?>><?= htmlspecialchars($roleOption['role']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="is_verified" class="form-control">
                        <option value="">Статус подтверждения</option>
                        <option value="1" <?= isset($_GET['is_verified']) && $_GET['is_verified'] == '1' ? 'selected' : '' ?>>Подтвержден</option>
                        <option value="0" <?= isset($_GET['is_verified']) && $_GET['is_verified'] == '0' ? 'selected' : '' ?>>Не подтвержден</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary w-100">Применить фильтры</button>
                </div>
            </div>
        </form>

        <?php if (!empty($users)): ?>
            <table class="table table-bordered table-striped">
                <thead class="thead-light">
                    <tr>
                        <th>ID</th>
                        <th>Имя пользователя</th>
                        <th>Email</th>
                        <th>Роль</th>
                        <th>Подтвержден</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?= htmlspecialchars($user['id']) ?></td>
                            <td><?= htmlspecialchars($user['username']) ?></td>
                            <td><?= htmlspecialchars($user['email']) ?></td>
                            <td><?= htmlspecialchars($user['role']) ?></td>
                            <td><?= $user['is_verified'] ? 'Да' : 'Нет' ?></td>
                            <td>
                                <a href="edit.php?id=<?= $user['id'] ?>" class="btn btn-warning btn-sm">Редактировать</a>
                                <a href="reset_password.php?id=<?= $user['id'] ?>" class="btn btn-info btn-sm">Сбросить пароль</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p class="text-center text-muted">Нет зарегистрированных пользователей по выбранным фильтрам.</p>
        <?php endif; ?>
        <p class="text-center mt-4">
            <a href="../admin_dashboard.php" class="btn btn-secondary">Назад в админ-панель</a>
        </p>
    </div>
<?php
include '../footer.php';
?>