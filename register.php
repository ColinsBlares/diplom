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
    $subject = 'Подтверждение регистрации на сайте ТСЖ';
    $verificationLink = 'https://' . $_SERVER['HTTP_HOST'] . '/verify_email.php?token=' . $token; 

    $message = "Здравствуйте!\n\n";
    $message .= "Благодарим вас за регистрацию на сайте ТСЖ.\n";
    $message .= "Пожалуйста, перейдите по следующей ссылке, чтобы подтвердить свой адрес электронной почты:\n";
    $message .= $verificationLink . "\n\n";
    $message .= "Если вы не регистрировались на нашем сайте, проигнорируйте это письмо.\n\n";
    $message .= "С уважением,\nАдминистрация ТСЖ";

    $headers = 'From: noreply@colinsblare.ru' . "\r\n" . // Замените на свой адрес отправителя
               'Reply-To: noreply@colinsblare.ru' . "\r\n" .
               'X-Mailer: PHP/' . phpversion();

    // Используйте функцию mail() или более надежную библиотеку для отправки почты
    return mail($email, $subject, $message, $headers);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim(filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING) ?? '');
    $email = trim(filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL) ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

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
                INSERT INTO users (username, email, password, role, verification_token)
                VALUES (:username, :email, :password, 'user', :verification_token)
            ");
            $stmt_add_user->execute([
                'username' => $username,
                'email' => $email,
                'password' => $hashed_password,
                'verification_token' => $verification_token,
            ]);

            if (sendVerificationEmail($email, $verification_token)) {
                $success = "Вы успешно зарегистрировались! Пожалуйста, проверьте свою электронную почту и перейдите по ссылке для подтверждения.";
            } else {
                $errors[] = "Ошибка при отправке письма с подтверждением. Пожалуйста, попробуйте позже.";
                // В реальном приложении стоит рассмотреть логирование этой ошибки
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
        /* Общие стили */
        body {
            font-family: Arial, sans-serif;
            background-color: #f2f2f2;
            margin: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
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

        /* Стиль формы */
        label {
            display: block;
            margin-top: 15px;
            color: #555;
            font-size: 14px;
        }

        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 10px;
            margin-top: 5px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 16px;
        }
        
        input[type="email"] {
            width: 100%;
            padding: 10px;
            margin-top: 5px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 16px;
        }

        /* Стиль кнопки */
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

        /* Стиль ошибок */
        .error {
            color: red;
            margin-bottom: 15px;
            font-size: 14px;
        }

        /* Стиль ссылок */
        a {
            color: #4CAF50;
            text-decoration: none;
            font-size: 14px;
        }

        a:hover {
            text-decoration: underline;
        }
    .error {
            color: red;
            margin-bottom: 10px;
            padding: 10px;
            border: 1px solid red;
            border-radius: 5px;
            background-color: #ffe0e0;
            text-align: left;
        }
        .error ul {
            margin-top: 0;
            padding-left: 20px;
        }
        .success {
            color: green;
            margin-bottom: 10px;
            padding: 10px;
            border: 1px solid green;
            border-radius: 5px;
            background-color: #e0ffe0;
            text-align: center;
        }
        .info {
            color: blue;
            margin-top: 20px;
            text-align: center;
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

        <button type="submit" class="btn">Зарегистрироваться</button>
    </form>

    <p>Уже есть аккаунт? <a href="login.php">Войти</a></p>

    <?php if (!$success && empty($errors) && $_SERVER["REQUEST_METHOD"] == "POST"): ?>
        <p class="info">На ваш адрес электронной почты отправлено письмо с инструкциями по подтверждению.</p>
    <?php endif; ?>
</div>
</body>
</html>