<?php
session_start();
require_once 'db.php';
$pdo = require 'db.php';

// Проверка авторизации
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'] ?? '';

// Если не админ, получаем имя пользователя
if ($role !== 'admin') {
    $stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $_SESSION['username'] = $stmt->fetchColumn();
}

$errors = [];
$success = null;

// Получение списка пользователей (для админа)
$users = [];
if ($role === 'admin') {
    $stmt_users = $pdo->query("SELECT id, username FROM users ORDER BY username");
    $users = $stmt_users->fetchAll();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $owner_id = intval($_POST['owner_id'] ?? 0);
    $inn = trim($_POST['inn'] ?? '');
    $ogrn = trim($_POST['ogrn'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $website = trim($_POST['website'] ?? '');
    $bank_account = trim($_POST['bank_account'] ?? '');
    $bank_name = trim($_POST['bank_name'] ?? '');
    $bik = trim($_POST['bik'] ?? '');
    $legal_address = trim($_POST['legal_address'] ?? '');
    $chairman_name = trim($_POST['chairman_name'] ?? '');

    if (empty($name)) {
        $errors[] = "Название ТСЖ обязательно.";
    }

    if (empty($address)) {
        $errors[] = "Адрес ТСЖ обязателен.";
    }

    if ($owner_id <= 0) {
        $errors[] = "Необходимо выбрать владельца ТСЖ.";
    }

    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Некорректный формат email.";
    }

    if (!empty($website) && !filter_var($website, FILTER_VALIDATE_URL)) {
        $errors[] = "Некорректный формат веб-сайта.";
    }

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            $stmt_create_tsj = $pdo->prepare("
                INSERT INTO tsj (name, address, owner_id, inn, ogrn, phone, email, website, bank_account, bank_name, bik, legal_address, chairman_name)
                VALUES (:name, :address, :owner_id, :inn, :ogrn, :phone, :email, :website, :bank_account, :bank_name, :bik, :legal_address, :chairman_name)
            ");
            $stmt_create_tsj->execute([
                'name' => $name,
                'address' => $address,
                'owner_id' => $owner_id,
                'inn' => $inn,
                'ogrn' => $ogrn,
                'phone' => $phone,
                'email' => $email,
                'website' => $website,
                'bank_account' => $bank_account,
                'bank_name' => $bank_name,
                'bik' => $bik,
                'legal_address' => $legal_address,
                'chairman_name' => $chairman_name
            ]);

            $tsj_id = $pdo->lastInsertId();

            $stmt_update_user = $pdo->prepare("UPDATE users SET role = 'owner', tsj_id = :tsj_id WHERE id = :id");
            $stmt_update_user->execute(['tsj_id' => $tsj_id, 'id' => $owner_id]);

            $pdo->commit();
            $success = "ТСЖ успешно создано!";
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = "Ошибка создания ТСЖ: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Создать ТСЖ</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Подключаем Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h2 class="text-center">Создать ТСЖ</h2>

        <?php if ($success): ?>
            <div class="alert alert-success text-center"><?= $success ?></div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?= $error ?></li>
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
                <textarea class="form-control" id="address" name="address" required></textarea>
            </div>

            <div class="mb-3">
                <label for="owner_id" class="form-label">Выберите владельца ТСЖ:</label>
                <?php if ($role !== 'admin'): ?>
                    <input type="hidden" name="owner_id" value="<?= $user_id ?>">
                    <input type="text" class="form-control" value="<?= htmlspecialchars($_SESSION['username']) ?>" disabled>
                <?php else: ?>
                    <select class="form-select" id="owner_id" name="owner_id" required>
                        <option value="">-- Выберите пользователя --</option>
                        <?php foreach ($users as $user): ?>
                            <option value="<?= $user['id'] ?>"><?= htmlspecialchars($user['username']) ?></option>
                        <?php endforeach; ?>
                    </select>
                <?php endif; ?>
            </div>

            <div class="mb-3">
                <label for="inn" class="form-label">ИНН:</label>
                <input type="text" class="form-control" id="inn" name="inn">
            </div>

            <div class="mb-3">
                <label for="ogrn" class="form-label">ОГРН:</label>
                <input type="text" class="form-control" id="ogrn" name="ogrn">
            </div>

            <div class="mb-3">
                <label for="phone" class="form-label">Телефон:</label>
                <input type="text" class="form-control" id="phone" name="phone">
            </div>

            <div class="mb-3">
                <label for="email" class="form-label">Email:</label>
                <input type="email" class="form-control" id="email" name="email">
            </div>

            <div class="mb-3">
                <label for="website" class="form-label">Веб-сайт:</label>
                <input type="url" class="form-control" id="website" name="website">
            </div>

            <div class="mb-3">
                <label for="bank_account" class="form-label">Расчетный счет:</label>
                <input type="text" class="form-control" id="bank_account" name="bank_account">
            </div>

            <div class="mb-3">
                <label for="bank_name" class="form-label">Наименование банка:</label>
                <input type="text" class="form-control" id="bank_name" name="bank_name">
            </div>

            <div class="mb-3">
                <label for="bik" class="form-label">БИК:</label>
                <input type="text" class="form-control" id="bik" name="bik">
            </div>

            <div class="mb-3">
                <label for="legal_address" class="form-label">Юридический адрес:</label>
                <textarea class="form-control" id="legal_address" name="legal_address"></textarea>
            </div>

            <div class="mb-3">
                <label for="chairman_name" class="form-label">Имя председателя:</label>
                <input type="text" class="form-control" id="chairman_name" name="chairman_name">
            </div>

            <button type="submit" class="btn btn-success w-100">Создать ТСЖ</button>
        </form>

        <div class="text-center mt-3">
            <a href="profile" class="btn btn-secondary">Назад в профиль</a>
        </div>
    </div>

<?php include '../footer.php';?>