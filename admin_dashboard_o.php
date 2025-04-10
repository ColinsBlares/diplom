<?php
session_start();

// Подключение к базе данных через db.php
require_once 'db.php';
$pdo = require 'db.php';

// Проверка прав доступа
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// Получение данных пользователя
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id");
$stmt->execute(['id' => $user_id]);
$user = $stmt->fetch();

// --- Функции для получения данных (Рефакторинг) ---

function getTotalUsers(PDO $pdo): int
{
    return $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
}

function getTotalTSJ(PDO $pdo): int
{
    return $pdo->query("SELECT COUNT(*) FROM tsj")->fetchColumn();
}

function getRecentTSJ(PDO $pdo, int $limit = 5): array
{
    $stmt = $pdo->prepare("SELECT t.*, u.username AS owner_name FROM tsj t LEFT JOIN users u ON t.owner_id = u.id ORDER BY t.created_at DESC LIMIT :limit");
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

function getRecentUsers(PDO $pdo, int $limit = 10): array
{
    $stmt = $pdo->query("SELECT id, username, email, role, created_at FROM users ORDER BY created_at DESC LIMIT $limit");
    return $stmt->fetchAll();
}

// Получение общей статистики
$total_users = getTotalUsers($pdo);
$total_tsj = getTotalTSJ($pdo);

// Последние созданные ТСЖ
$recent_tsjs = getRecentTSJ($pdo);

// Список последних пользователей
$recent_users = getRecentUsers($pdo);

// --- Генерация токена CSRF (Безопасность) ---
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Админ-панель | ТСЖ</title>
    <link rel="stylesheet" href="styles.css"> <style>
        /* Временные стили (лучше перенести в styles.css) */
        body { font-family: Arial, sans-serif; background-color: #f2f2f2; margin: 0; }
        .container { max-width: 1200px; margin: 20px auto; padding: 20px; background-color: #fff; border-radius: 8px; box-shadow: 0 0 10px rgba(0, 0, 0, 0.1); }
        h1, h2 { text-align: center; color: #333; }
        .section { margin-bottom: 20px; }
        .stats { display: flex; justify-content: space-around; }
        .stat-box { padding: 15px; background-color: #f4f4f4; border-radius: 5px; text-align: center; width: 200px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 10px; border: 1px solid #ddd; text-align: left; }
        th { background-color: #f4f4f4; }
        .btn { display: inline-block; padding: 10px 20px; background-color: #4CAF50; color: white; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; font-size: 16px; transition: background-color 0.3s ease; }
        .btn:hover { background-color: #45a049; }
        a { color: #4CAF50; text-decoration: none; font-weight: bold; }
        a:hover { text-decoration: underline; }
        .action-links a { margin-right: 10px; }
        .error-message { color: red; margin-top: 10px; }
    </style>
</head>
<body>
<div class="container">
    <h1>Админ-панель ТСЖ</h1>
    <p>Добро пожаловать, <?php echo htmlspecialchars($user['username']); ?>!</p>

    <div class="section">
        <h2>Общая статистика</h2>
        <div class="stats">
            <div class="stat-box">
                <strong>Всего пользователей:</strong><br>
                <?php echo htmlspecialchars($total_users); ?>
            </div>
            <div class="stat-box">
                <strong>Всего ТСЖ:</strong><br>
                <?php echo htmlspecialchars($total_tsj); ?>
            </div>
        </div>
    </div>

    <div class="section">
        <h2>Последние созданные ТСЖ</h2>
        <table>
            <thead>
                <tr>
                    <th>Название</th>
                    <th>Адрес</th>
                    <th>Владелец</th>
                    <th>Дата создания</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($recent_tsjs)): ?>
                    <?php foreach ($recent_tsjs as $tsj): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($tsj['name']); ?></td>
                            <td><?php echo htmlspecialchars($tsj['address']); ?></td>
                            <td><?php echo htmlspecialchars($tsj['owner_name'] ?? 'Неизвестно'); ?></td>
                            <td><?php echo htmlspecialchars($tsj['created_at']); ?></td>
                            <td class="action-links">
                                <a href="view_tsj_details.php?id=<?php echo $tsj['id']; ?>" class="btn btn-sm">Подробнее</a>
                                <a href="edit_tsj.php?id=<?php echo $tsj['id']; ?>" class="btn btn-sm">Редактировать</a>
                                <form method="POST" action="delete_tsj.php" style="display: inline;">
                                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                    <input type="hidden" name="tsj_id" value="<?php echo $tsj['id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Вы уверены, что хотите удалить это ТСЖ?')">Удалить</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="5">Нет созданных ТСЖ.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="section">
        <h2>Последние зарегистрированные пользователи</h2>
        <table>
            <thead>
                <tr>
                    <th>Имя пользователя</th>
                    <th>Email</th>
                    <th>Роль</th>
                    <th>Дата регистрации</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($recent_users)): ?>
                    <?php foreach ($recent_users as $user_item): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($user_item['username']); ?></td>
                            <td><?php echo htmlspecialchars($user_item['email']); ?></td>
                            <td><?php echo htmlspecialchars($user_item['role']); ?></td>
                            <td><?php echo htmlspecialchars($user_item['created_at']); ?></td>
                            <td class="action-links">
                                <a href="edit_user.php?id=<?php echo $user_item['id']; ?>" class="btn btn-sm">Редактировать</a>
                                <form method="POST" action="delete_user.php" style="display: inline;">
                                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                    <input type="hidden" name="user_id" value="<?php echo $user_item['id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Вы уверены, что хотите удалить этого пользователя?')">Удалить</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="5">Нет зарегистрированных пользователей.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="section">
        <h2>Действия</h2>
        <ul>
            <li><a href="create_tsj.php" class="btn">Создать новое ТСЖ</a></li>
            <li><a href="manage_users.php" class="btn">Управление пользователями</a></li>
            <li><a href="view_all_tsjs.php" class="btn">Просмотреть все ТСЖ</a></li>
            <li><a href="profile.php" class="btn">Назад в профиль</a></li>
            <li><a href="logout.php" class="btn">Выйти</a></li>
        </ul>
    </div>
</div>
</body>
</html>