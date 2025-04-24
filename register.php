<?php
session_start();

require_once 'db.php';
$pdo = require 'db.php';

$success = null;
$errors = [];

// Параметры безопасности (можно вынести в config-файл)
const MIN_USERNAME_LENGTH = 3;
const MAX_USERNAME_LENGTH = 50;
const USERNAME_ALLOWED_CHARS = '/^[a-zA-Z0-9_-]+$/';
const MIN_PASSWORD_LENGTH = 8;
const VERIFICATION_TOKEN_LENGTH = 32; // Длина токена верификации

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'phpmailer/src/PHPMailer.php';
require 'phpmailer/src/SMTP.php';
require 'phpmailer/src/Exception.php';

// Функция для проверки сложности пароля (пример)
function isPasswordSecure(string $password): bool
{
    return strlen($password) >= MIN_PASSWORD_LENGTH &&
           preg_match('/[a-z]/', $password) &&
           preg_match('/[A-Z]/', $password) &&
           preg_match('/[0-9]/', $password);
}

// Функция для генерации случайного токена
function generateVerificationToken(): string
{
    return bin2hex(random_bytes(VERIFICATION_TOKEN_LENGTH / 2));
}

// Функция для отправки письма с подтверждением (нужно настроить)
function sendVerificationEmail(string $email, string $token): bool
{
    $mail = new PHPMailer(true);
    $mail->CharSet = 'UTF-8';

    try {
        // SMTP конфигурация
        $mail->isSMTP();
        $mail->Host = 'smtp.yandex.ru'; // или другой SMTP-сервер
        $mail->SMTPAuth = true;
        $mail->Username = 'kirilljuk.zhuk@yandex.ru'; // логин
        $mail->Password = 'tyzkcgcxuezkpodc';        // пароль приложения
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = 465;

        // От кого и кому
        $mail->setFrom('kirilljuk.zhuk@yandex.ru', 'ТСЖ');
        $mail->addAddress($email);

        // Контент
        $mail->isHTML(true);
        $mail->Subject = 'Подтверждение регистрации на сайте ТСЖ';

        $verificationLink = 'https://' . $_SERVER['HTTP_HOST'] . '/verify_email.php?token=' . $token;

        $mail->Body = "
            <p>Здравствуйте!</p>
            <p>Благодарим вас за регистрацию на сайте ТСЖ.</p>
            <p>Пожалуйста, перейдите по ссылке ниже, чтобы подтвердить ваш Email:</p>
            <p><a href='{$verificationLink}'>{$verificationLink}</a></p>
            <p>Если вы не регистрировались, просто проигнорируйте это письмо.</p>
            <p><b>Внимание:</b> На данный момент письма лучше доходят на адреса @yandex.ru.</p>
            <br><p>С уважением,<br>Администрация ТСЖ</p>
        ";

        $mail->AltBody = "Перейдите по ссылке для подтверждения: {$verificationLink}";

        return $mail->send();
    } catch (Exception $e) {
        error_log("Ошибка при отправке письма: {$mail->ErrorInfo}");
        return false;
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim(filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING) ?? '');
    $email = trim(filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL) ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $full_name = trim(filter_input(INPUT_POST, 'full_name', FILTER_SANITIZE_STRING) ?? '');
    $phone = trim(filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_STRING) ?? '');
    $date_of_birth = $_POST['date_of_birth'] ?? '';
    $gender = $_POST['gender'] ?? '';
    $agreement = $_POST['agreement'] ?? '';

    // Валидация данных
    if (empty($username)) {
        $errors[] = "Имя пользователя обязательно.";
    } elseif (strlen($username) < MIN_USERNAME_LENGTH || strlen($username) > MAX_USERNAME_LENGTH) {
        $errors[] = "Имя пользователя должен быть от " . MIN_USERNAME_LENGTH . " до " . MAX_USERNAME_LENGTH . " символов.";
    } elseif (!preg_match(USERNAME_ALLOWED_CHARS, $username)) {
        $errors[] = "Имя пользователя может содержать только буквы, цифры, символы _ и -.";
    }

    if (empty($email)) {
        $errors[] = "Email обязателен.";
    } elseif (!$email) { // filter_var может вернуть false
        $errors[] = "Некорректный формат Email.";
    }

    if (empty($password)) {
        $errors[] = "Пароль обязателен.";
    } elseif (strlen($password) < MIN_PASSWORD_LENGTH) {
        $errors[] = "Пароль должен быть не менее " . MIN_PASSWORD_LENGTH . " символов.";
    } elseif (!isPasswordSecure($password)) {
        $errors[] = "Пароль должен содержать строчные и прописные буквы, а также цифры.";
    }

    if ($password !== $confirm_password) {
        $errors[] = "Пароли не совпадают.";
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

    if ($agreement != '1') {
        $errors[] = "Необходимо согласие с политикой конфиденциальности.";
    }

    // Проверка уникальности имени пользователя и email
    if (empty($errors)) {
        $stmt_check_username = $pdo->prepare("SELECT id FROM users WHERE username = :username");
        $stmt_check_username->execute(['username' => $username]);
        if ($stmt_check_username->fetchColumn()) {
            $errors[] = "Имя пользователя уже занято.";
        }

        $stmt_check_email = $pdo->prepare("SELECT id FROM users WHERE email = :email");
        $stmt_check_email->execute(['email' => $email]);
        if ($stmt_check_email->fetchColumn()) {
            $errors[] = "Email уже зарегистрирован.";
        }
    }

    // Если нет ошибок, создаем пользователя и отправляем письмо
    if (empty($errors)) {
        try {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $verification_token = generateVerificationToken();

            $stmt_add_user = $pdo->prepare("
                INSERT INTO users (username, email, password, full_name, phone, date_of_birth, gender, role, verification_token)
                VALUES (:username, :email, :password, :full_name, :phone, :date_of_birth, :gender, 'user', :verification_token)
            ");
            $stmt_add_user->execute([
                'username' => $username,
                'email' => $email,
                'password' => $hashed_password,
                'full_name' => $full_name,
                'phone' => $phone,
                'date_of_birth' => $date_of_birth,
                'gender' => $gender,
                'verification_token' => $verification_token,
            ]);

            if (sendVerificationEmail($email, $verification_token)) {
                $success = "Вы успешно зарегистрировались! Пожалуйста, проверьте свою электронную почту (в том числе папку \"Спам\") и перейдите по ссылке для подтверждения.";
            } else {
                $errors[] = "Ошибка при отправке письма с подтверждением. Пожалуйста, попробуйте позже.";
            }

        } catch (Exception $e) {
            $errors[] = "Ошибка регистрации: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Регистрация | ТСЖ</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f2f2f2;
            margin: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            background-color: #fff;
            padding: 20px 40px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            text-align: center;
            width: 100%;
            max-width: 400px;
        }

        h2 {
            color: #333;
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-top: 15px;
            color: #555;
            font-size: 14px;
            text-align: left;
        }

        input[type="text"], input[type="password"], input[type="email"], input[type="date"], input[type="phone"] {
            width: 100%;
            padding: 10px;
            margin-top: 5px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 16px;
            box-sizing: border-box;
        }

        .btn {
            display: inline-block;
            width: 100%;
            padding: 10px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s ease;
        }

        .btn:hover {
            background-color: #45a049;
        }

        .error {
            color: red;
            margin-bottom: 15px;
            font-size: 14px;
            text-align: left;
            padding: 10px;
            border: 1px solid red;
            border-radius: 5px;
            background-color: #ffe0e0;
        }

        .error ul {
            margin-top: 0;
            padding-left: 20px;
        }

        a {
            color: #4CAF50;
            text-decoration: none;
            font-size: 14px;
        }

        a:hover {
            text-decoration: underline;
        }

        .info {
            color: blue;
            margin-top: 20px;
            text-align: center;
            font-size: 14px;
        }

        .warning {
            color: orange;
            margin-top: 20px;
            text-align: center;
            font-size: 14px;
            padding: 10px;
            border: 1px solid orange;
            border-radius: 5px;
            background-color: #fff3cd;
        }
    </style>
</head>
<body>
<div class="container">
    <h2>Регистрация</h2>

    <?php if ($success): ?>
        <p class="success"><?= htmlspecialchars($success) ?></p>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div class="error">
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="POST" action="">
        <label for="username">Имя пользователя:</label>
        <input type="text" id="username" name="username" required>

        <label for="email">Email:</label>
        <input type="email" id="email" name="email" required>

        <label for="password">Пароль:</label>
        <input type="password" id="password" name="password" required>

        <label for="confirm_password">Подтвердите пароль:</label>
        <input type="password" id="confirm_password" name="confirm_password" required>

        <label for="full_name">Полное имя:</label>
        <input type="text" id="full_name" name="full_name" required>

        <label for="phone">Номер телефона:</label>
        <input type="text" id="phone" name="phone" placeholder="+79991234567" required>

        <label for="date_of_birth">Дата рождения:</label>
        <input type="date" id="date_of_birth" name="date_of_birth" required>

        <label>Пол:</label>
        <div style="text-align: left; margin-bottom: 10px;">
            <label><input type="radio" name="gender" value="male" required> Мужской</label><br>
            <label><input type="radio" name="gender" value="female"> Женский</label><br>
            <label><input type="radio" name="gender" value="other"> Другое</label>
        </div>

        <label>
            <input type="checkbox" name="agreement" value="1" required>
            Я соглашаюсь с <a href="/privacy-policy.html" target="_blank">политикой конфиденциальности</a>
        </label>

        <button type="submit" class="btn">Зарегистрироваться</button>
    </form>

    <p>Уже есть аккаунт? <a href="login.php">Войти</a></p>

    <p class="error"><b>Внимание</b>: На данный момент наблюдаются проблемы с доставкой писем подтверждения на почтовые сервисы, отличные от <i>@yandex.ru</i>. Рекомендуем использовать адрес электронной почты <i>@yandex.ru</i> для гарантированного получения письма.</p>

    <?php if (!$success && empty($errors) && $_SERVER["REQUEST_METHOD"] == "POST"): ?>
        <p class="info">На ваш адрес электронной почты отправлено письмо с инструкциями по подтверждению. Пожалуйста, проверьте папку "Спам", если письмо не пришло в течение нескольких минут.</p>
    <?php endif; ?>
</div>
</body>
</html>