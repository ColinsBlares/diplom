<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit;
}
require_once '../../db.php';
$pdo = require '../../db.php';

$user_id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);
if (!$user_id) {
    die("ID пользователя не указан.");
}

$stmt = $pdo->prepare("SELECT id, username, email, role, is_verified, full_name, phone, date_of_birth, gender, agreement FROM users WHERE id = :id");
$stmt->execute(['id' => $user_id]);
$user = $stmt->fetch();

if (!$user) {
    die("Пользователь не найден.");
}

$errors = [];
$success = null;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $role = filter_input(INPUT_POST, 'role', FILTER_SANITIZE_STRING);
    $is_verified = isset($_POST['is_verified']) ? 1 : 0;
    $full_name = filter_input(INPUT_POST, 'full_name', FILTER_SANITIZE_STRING);
    $phone = filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_STRING);
    $date_of_birth = filter_input(INPUT_POST, 'date_of_birth', FILTER_SANITIZE_STRING);
    $gender = filter_input(INPUT_POST, 'gender', FILTER_SANITIZE_STRING);
    $agreement = isset($_POST['agreement']) ? 1 : 0;

    // Валидация данных
    if (empty($username)) {
        $errors[] = "Имя пользователя обязательно для заполнения.";
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Некорректный формат email.";
    }
    if (!in_array($role, ['user', 'owner', 'manager', 'admin'])) {
        $errors[] = "Выбрана некорректная роль.";
    }
    if (empty($full_name)) {
        $errors[] = "Полное имя обязательно.";
    }
    if (empty($phone)) {
        $errors[] = "Номер телефона обязателен.";
    }
    if (empty($date_of_birth)) {
        $errors[] = "Дата рождения обязательна.";
    }
    if (empty($gender)) {
        $errors[] = "Пол обязателен.";
    }
    if (!in_array($gender, ['male', 'female', 'other'])) {
        $errors[] = "Выберите корректный пол.";
    }

    // Если ошибок нет, обновляем данные пользователя
    if (empty($errors)) {
        $stmt = $pdo->prepare("UPDATE users SET username = :username, email = :email, role = :role, is_verified = :is_verified, full_name = :full_name, phone = :phone, date_of_birth = :date_of_birth, gender = :gender, agreement = :agreement WHERE id = :id");
        $stmt->execute([
            'username' => $username,
            'email' => $email,
            'role' => $role,
            'is_verified' => $is_verified,
            'full_name' => $full_name,
            'phone' => $phone,
            'date_of_birth' => $date_of_birth,
            'gender' => $gender,
            'agreement' => $agreement,
            'id' => $user_id
        ]);
        $success = "Данные пользователя успешно обновлены.";
        // Обновляем данные пользователя в $user для отображения актуальной информации в форме
        $stmt->execute(['id' => $user_id]);
        $user = $stmt->fetch();
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Редактирование пользователя | ТСЖ</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <h2 class="text-center mb-4">Редактирование пользователя</h2>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
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
            <div class="form-group">
                <label for="username">Имя пользователя:</label>
                <input type="text" id="username" name="username" class="form-control" value="<?= htmlspecialchars($user['username']) ?>" required>
            </div>

            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" required>
            </div>

            <div class="form-group">
                <label for="role">Роль:</label>
                <select id="role" name="role" class="form-control" required>
                    <option value="user" <?= $user['role'] === 'user' ? 'selected' : '' ?>>Пользователь</option>
                    <option value="owner" <?= $user['role'] === 'owner' ? 'selected' : '' ?>>Владелец ТСЖ</option>
                    <option value="manager" <?= $user['role'] === 'manager' ? 'selected' : '' ?>>Менеджер</option>
                    <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Администратор</option>
                </select>
            </div>

            <div class="form-group">
                <label for="is_verified">Подтвержденный Email:</label>
                <input type="checkbox" id="is_verified" name="is_verified" <?= $user['is_verified'] ? 'checked' : '' ?> class="form-check-input">
            </div>

            <div class="form-group">
                <label for="full_name">Полное имя:</label>
                <input type="text" id="full_name" name="full_name" class="form-control" value="<?= htmlspecialchars($user['full_name']) ?>" required>
            </div>

            <div class="form-group">
                <label for="phone">Телефон:</label>
                <input type="text" id="phone" name="phone" class="form-control" value="<?= htmlspecialchars($user['phone']) ?>" required>
            </div>

            <div class="form-group">
                <label for="date_of_birth">Дата рождения:</label>
                <input type="date" id="date_of_birth" name="date_of_birth" class="form-control" value="<?= htmlspecialchars($user['date_of_birth']) ?>" required>
            </div>

            <div class="form-group">
                <label for="gender">Пол:</label>
                <select id="gender" name="gender" class="form-control" required>
                    <option value="male" <?= $user['gender'] === 'male' ? 'selected' : '' ?>>Мужской</option>
                    <option value="female" <?= $user['gender'] === 'female' ? 'selected' : '' ?>>Женский</option>
                </select>
            </div>

            <button type="submit" class="btn btn-primary w-100">Сохранить</button>
        </form>

        <p class="text-center mt-4">
            <a href="list.php" class="btn btn-secondary">Назад к списку пользователей</a>
        </p>
    </div>
<?php
include '../footer.php';
?>