<?php
session_start();

// Подключение к базе данных
require_once '../../db.php';
$pdo = require '../../db.php';

// Проверка авторизации администратора
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// Получение ID ТСЖ из GET-параметра и валидация
$id_tszh = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if ($id_tszh === false || $id_tszh <= 0) {
    die("Некорректный ID ТСЖ.");
}

// Получение данных ТСЖ для редактирования
$stmt_tszh = $pdo->prepare("SELECT * FROM tsj WHERE id = :id");
$stmt_tszh->execute(['id' => $id_tszh]);
$tszh = $stmt_tszh->fetch();

// Если ТСЖ не найдено
if (!$tszh) {
    die("ТСЖ с указанным ID не найдено.");
}

// Обработка отправки формы редактирования
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $address = filter_input(INPUT_POST, 'address', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $owner_id = filter_input(INPUT_POST, 'owner_id', FILTER_VALIDATE_INT);
    $inn = filter_input(INPUT_POST, 'inn', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $ogrn = filter_input(INPUT_POST, 'ogrn', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $phone = filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $website = filter_input(INPUT_POST, 'website', FILTER_VALIDATE_URL);
    $bank_account = filter_input(INPUT_POST, 'bank_account', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $bank_name = filter_input(INPUT_POST, 'bank_name', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $bik = filter_input(INPUT_POST, 'bik', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $legal_address = filter_input(INPUT_POST, 'legal_address', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $chairman_name = filter_input(INPUT_POST, 'chairman_name', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

    $errors = [];

    if (empty($name)) {
        $errors[] = "Название ТСЖ обязательно для заполнения.";
    }
    if (empty($address)) {
        $errors[] = "Адрес ТСЖ обязателен для заполнения.";
    }
    if (empty($owner_id) || $owner_id <= 0) {
        $errors[] = "Выберите владельца ТСЖ.";
    }
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Некорректный формат email.";
    }
    if (!empty($website) && !filter_var($website, FILTER_VALIDATE_URL)) {
        $errors[] = "Некорректный формат веб-сайта.";
    }

    if (empty($errors)) {
        try {
            $stmt_update = $pdo->prepare("
                UPDATE tsj
                SET name = :name,
                    address = :address,
                    owner_id = :owner_id,
                    inn = :inn,
                    ogrn = :ogrn,
                    phone = :phone,
                    email = :email,
                    website = :website,
                    bank_account = :bank_account,
                    bank_name = :bank_name,
                    bik = :bik,
                    legal_address = :legal_address,
                    chairman_name = :chairman_name
                WHERE id = :id
            ");
            $stmt_update->execute([
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
                'chairman_name' => $chairman_name,
                'id' => $id_tszh
            ]);

            $_SESSION['success_message'] = "Данные ТСЖ успешно обновлены.";
            header("Location: index.php"); // Перенаправление на страницу со списком ТСЖ
            exit;

        } catch (PDOException $e) {
            $error_message = "Ошибка при обновлении данных ТСЖ: " . $e->getMessage();
        }
    } else {
        $error_message = "Пожалуйста, исправьте следующие ошибки:";
    }
}

// Получение списка всех пользователей для выпадающего списка владельцев
$stmt_users = $pdo->query("SELECT id, username FROM users ORDER BY username");
$users = $stmt_users->fetchAll();

?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Редактирование ТСЖ | Админ панель</title>
    <!-- Подключение Bootstrap CSS -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1 class="text-center">Редактирование ТСЖ</h1>

        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger"><?= $error_message ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="name">Название ТСЖ:</label>
                <input type="text" id="name" name="name" class="form-control" value="<?= htmlspecialchars($tszh['name']) ?>" required>
            </div>

            <div class="form-group">
                <label for="address">Адрес:</label>
                <input type="text" id="address" name="address" class="form-control" value="<?= htmlspecialchars($tszh['address']) ?>" required>
            </div>

            <div class="form-group">
                <label for="owner_id">Владелец ТСЖ:</label>
                <select id="owner_id" name="owner_id" class="form-control" required>
                    <option value="">-- Выберите владельца --</option>
                    <?php foreach ($users as $user): ?>
                        <option value="<?= $user['id'] ?>" <?= ($tszh['owner_id'] == $user['id']) ? 'selected' : '' ?>><?= htmlspecialchars($user['username']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="inn">ИНН:</label>
                <input type="text" id="inn" name="inn" class="form-control" value="<?= htmlspecialchars($tszh['inn']) ?>">
            </div>

            <div class="form-group">
                <label for="ogrn">ОГРН:</label>
                <input type="text" id="ogrn" name="ogrn" class="form-control" value="<?= htmlspecialchars($tszh['ogrn']) ?>">
            </div>

            <div class="form-group">
                <label for="phone">Телефон:</label>
                <input type="text" id="phone" name="phone" class="form-control" value="<?= htmlspecialchars($tszh['phone']) ?>">
            </div>

            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" class="form-control" value="<?= htmlspecialchars($tszh['email']) ?>">
            </div>

            <div class="form-group">
                <label for="website">Веб-сайт:</label>
                <input type="url" id="website" name="website" class="form-control" value="<?= htmlspecialchars($tszh['website']) ?>">
            </div>

            <div class="form-group">
                <label for="bank_account">Расчетный счет:</label>
                <input type="text" id="bank_account" name="bank_account" class="form-control" value="<?= htmlspecialchars($tszh['bank_account']) ?>">
            </div>

            <div class="form-group">
                <label for="bank_name">Наименование банка:</label>
                <input type="text" id="bank_name" name="bank_name" class="form-control" value="<?= htmlspecialchars($tszh['bank_name']) ?>">
            </div>

            <div class="form-group">
                <label for="bik">БИК:</label>
                <input type="text" id="bik" name="bik" class="form-control" value="<?= htmlspecialchars($tszh['bik']) ?>">
            </div>

            <div class="form-group">
                <label for="legal_address">Юридический адрес:</label>
                <textarea id="legal_address" name="legal_address" class="form-control"><?= htmlspecialchars($tszh['legal_address']) ?></textarea>
            </div>

            <div class="form-group">
                <label for="chairman_name">Имя председателя:</label>
                <input type="text" id="chairman_name" name="chairman_name" class="form-control" value="<?= htmlspecialchars($tszh['chairman_name']) ?>">
            </div>

            <div class="text-center">
                <button type="submit" class="btn btn-success">Сохранить</button>
                <a href="list" class="btn btn-danger">Отмена</a>
            </div>
        </form>
    </div>
<?php include '../footer.php';?>