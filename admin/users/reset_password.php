<?php
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Проверка авторизации и роли администратора
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit;
}

// Подключение базы данных
$pdo = require_once '../../db.php';

// Подключение PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once '../../phpmailer/src/Exception.php';
require_once '../../phpmailer/src/PHPMailer.php';
require_once '../../phpmailer/src/SMTP.php';

// SMTP настройки
$mail_host = 'smtp.yandex.ru';
$mail_username = 'kirilljuk.zhuk@yandex.ru';
$mail_password = 'tyzkcgcxuezkpodc';
$mail_from = 'kirilljuk.zhuk@yandex.ru';
$mail_from_name = 'ТСЖ Администрация';

$errors = [];
$success = null;

// Проверяем, передан ли id пользователя
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $user_id = (int)$_GET['id'];

    // Получение данных пользователя
    $stmt_user = $pdo->prepare("SELECT username, email FROM users WHERE id = :id");
    $stmt_user->execute(['id' => $user_id]);
    $user = $stmt_user->fetch();

    if (!$user) {
        $errors[] = "Пользователь не найден.";
    } elseif (empty($user['email'])) {
        $errors[] = "У пользователя не указан email.";
    } else {
        // Генерация нового случайного пароля
        $new_password = bin2hex(random_bytes(4)); // 8 символов (например: 'a7f3d9e1')

        // Хэширование пароля
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

        // Обновление пароля в базе
        $stmt_update = $pdo->prepare("UPDATE users SET password = :password WHERE id = :id");
        $stmt_update->execute([
            'password' => $hashed_password,
            'id' => $user_id
        ]);

        // Отправка письма
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = $mail_host;
            $mail->SMTPAuth = true;
            $mail->Username = $mail_username;
            $mail->Password = $mail_password;
            $mail->SMTPSecure = 'ssl';
            $mail->Port = 465;

            $mail->setFrom($mail_from, $mail_from_name);
            $mail->addAddress($user['email'], $user['username']);

            $mail->isHTML(true);
            $mail->Subject = 'Ваш пароль был сброшен';
            $mail->Body    = "<p>Здравствуйте, <strong>" . htmlspecialchars($user['username']) . "</strong>!</p>
                              <p>Ваш пароль был сброшен администратором.</p>
                              <p>Ваш новый пароль: <strong>" . htmlspecialchars($new_password) . "</strong></p>
                              <p>Рекомендуем изменить его после входа в систему.</p>";

            $mail->send();
            $success = "Пароль успешно сброшен и отправлен на почту пользователя.";
        } catch (Exception $e) {
            $errors[] = "Пароль сброшен, но письмо не отправлено: {$mail->ErrorInfo}";
        }
    }
} else {
    $errors[] = "ID пользователя не передан.";
}

?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Сброс пароля</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f2f2f2;
            padding: 20px;
        }
        .container {
            max-width: 600px;
            margin: auto;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
        }
        .message {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
        }
        .success {
            background-color: #d4edda;
            color: #155724;
        }
        a.btn {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 20px;
            background: #4CAF50;
            color: white;
            text-decoration: none;
            border-radius: 5px;
        }
        a.btn:hover {
            background: #45a049;
        }
    </style>
</head>
<body>

<div class="container">
    <h2>Сброс пароля</h2>

    <?php if (!empty($errors)): ?>
        <div class="message error">
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="message success">
            <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>

    <a href="admin/users/list.php" class="btn">Назад к списку пользователей</a>
</div>

</body>
</html>