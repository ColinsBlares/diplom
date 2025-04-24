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
    <style>
        /* стили — без изменений */
        body {
            font-family: Arial, sans-serif;
            background-color: #f2f2f2;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
        }
        .container {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            max-width: 40%;
            width: 100%;
        }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input[type="text"],
        input[type="email"],
        input[type="url"],
        textarea,
        select {
            width: 100%; padding: 10px; border: 1px solid #ccc;
            border-radius: 4px; box-sizing: border-box;
        }
        textarea { min-height: 80px; }
        .btn {
            background: #4CAF50; color: white; padding: 10px;
            border: none; border-radius: 5px; cursor: pointer;
            width: 100%; font-size: 16px;
        }
        .btn:hover { background: #45a049; }
        .error { color: red; margin-bottom: 10px; }
        .success { color: green; margin-bottom: 10px; }
        .back-link { margin-top: 15px; text-align: center; }
        .back-link a {
            color: #4CAF50; text-decoration: none;
        }
        .back-link a:hover { text-decoration: underline; }

        @media (max-width: 600px) {
            .container { padding: 15px; border-radius: 5px; }
            .form-group { margin-bottom: 10px; }
            .btn { padding: 8px; font-size: 14px; }
        }
    </style>
</head>
<body>
<div class="container">
    <h2>Создать ТСЖ</h2>

    <?php if ($success): ?>
        <p class="success"><?= $success ?></p>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div class="error">
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?= $error ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="POST">
        <div class="form-group">
            <label for="name">Название ТСЖ:</label>
            <input type="text" id="name" name="name" required>
        </div>

        <div class="form-group">
            <label for="address">Адрес ТСЖ:</label>
            <textarea id="address" name="address" required></textarea>
        </div>

        <div class="form-group">
            <label for="owner_id">Выберите владельца ТСЖ:</label>
            <?php if ($role !== 'admin'): ?>
                <input type="hidden" name="owner_id" value="<?= $user_id ?>">
                <input type="text" value="<?= htmlspecialchars($_SESSION['username']) ?>" disabled>
            <?php else: ?>
                <select id="owner_id" name="owner_id" required>
                    <option value="">-- Выберите пользователя --</option>
                    <?php foreach ($users as $user): ?>
                        <option value="<?= $user['id'] ?>"><?= htmlspecialchars($user['username']) ?></option>
                    <?php endforeach; ?>
                </select>
            <?php endif; ?>
        </div>

        <div class="form-group"><label for="inn">ИНН:</label><input type="text" id="inn" name="inn"></div>
        <div class="form-group"><label for="ogrn">ОГРН:</label><input type="text" id="ogrn" name="ogrn"></div>
        <div class="form-group"><label for="phone">Телефон:</label><input type="text" id="phone" name="phone"></div>
        <div class="form-group"><label for="email">Email:</label><input type="email" id="email" name="email"></div>
        <div class="form-group"><label for="website">Веб-сайт:</label><input type="url" id="website" name="website"></div>
        <div class="form-group"><label for="bank_account">Расчетный счет:</label><input type="text" id="bank_account" name="bank_account"></div>
        <div class="form-group"><label for="bank_name">Наименование банка:</label><input type="text" id="bank_name" name="bank_name"></div>
        <div class="form-group"><label for="bik">БИК:</label><input type="text" id="bik" name="bik"></div>
        <div class="form-group"><label for="legal_address">Юридический адрес:</label><textarea id="legal_address" name="legal_address"></textarea></div>
        <div class="form-group"><label for="chairman_name">Имя председателя:</label><input type="text" id="chairman_name" name="chairman_name"></div>

        <button type="submit" class="btn">Создать ТСЖ</button>
    </form>

    <div class="back-link">
        <a href="profile.php">Назад в профиль</a>
    </div>
</div>
</body>
</html>