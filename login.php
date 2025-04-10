<?php
session_start();
require_once 'db.php';
$pdo = require 'db.php';

// Константы для ролей пользователей
const ROLE_ADMIN = 'admin';
const ROLE_OWNER = 'owner';
const ROLE_MANAGER = 'manager';

// Параметры для ограничения количества попыток входа
const LOGIN_ATTEMPTS_THRESHOLD = 5;
const LOCKOUT_TIME = 300; // 5 минут в секундах
const LOGIN_ATTEMPTS_SESSION_KEY = 'login_attempts';
const LOCKOUT_UNTIL_SESSION_KEY = 'lockout_until';
const AUTH_CODE_SESSION_KEY = 'auth_code';
const AUTH_CODE_EXPIRY_SESSION_KEY = 'auth_code_expiry';
const AUTH_CODE_USER_ID_SESSION_KEY = 'auth_code_user_id';

const AUTH_CODE_LENGTH = 6;
const AUTH_CODE_EXPIRY_SECONDS = 120; // 2 минуты

$error = null;

// Функция для проверки, заблокирован ли пользователь
function isLockedOut(): bool
{
    return isset($_SESSION[LOCKOUT_UNTIL_SESSION_KEY]) && time() < $_SESSION[LOCKOUT_UNTIL_SESSION_KEY];
}

// Функция для записи неудачной попытки входа
function recordFailedLogin(): void
{
    if (!isset($_SESSION[LOGIN_ATTEMPTS_SESSION_KEY])) {
        $_SESSION[LOGIN_ATTEMPTS_SESSION_KEY] = 0;
    }
    $_SESSION[LOGIN_ATTEMPTS_SESSION_KEY]++;

    if ($_SESSION[LOGIN_ATTEMPTS_SESSION_KEY] >= LOGIN_ATTEMPTS_THRESHOLD && !isset($_SESSION[LOCKOUT_UNTIL_SESSION_KEY])) {
        $_SESSION[LOCKOUT_UNTIL_SESSION_KEY] = time() + LOCKOUT_TIME;
    }
}

// Функция для сброса счетчика попыток входа
function resetLoginAttempts(): void
{
    unset($_SESSION[LOGIN_ATTEMPTS_SESSION_KEY]);
    unset($_SESSION[LOCKOUT_UNTIL_SESSION_KEY]);
}

// Функция для генерации случайного кода
function generateAuthCode(int $length = AUTH_CODE_LENGTH): string
{
    $characters = '0123456789';
    $code = '';
    for ($i = 0; $i < $length; $i++) {
        $code .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $code;
}

// Функция для отправки кода подтверждения на почту
function sendAuthCodeEmail(string $email, string $code): bool
{
    $subject = 'Код подтверждения для входа в ТСЖ';
    $message = "Здравствуйте!\n\n";
    $message .= "Ваш код подтверждения для входа в систему ТСЖ:\n\n";
    $message .= "{$code}\n\n";
    $message .= "Этот код действителен в течение " . (AUTH_CODE_EXPIRY_SECONDS / 60) . " минут.\n\n";
    $message .= "Если вы не запрашивали этот код, проигнорируйте это письмо.\n\n";
    $message .= "С уважением,\nАдминистрация ТСЖ";

    $headers = 'From: noreply@colinsblare.ru' . "\r\n" .
                'Reply-To: noreply@colinsblare.ru' . "\r\n" .
                'X-Mailer: PHP/' . phpversion();

    return mail($email, $subject, $message, $headers);
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (isLockedOut()) {
        $lockoutRemaining = $_SESSION[LOCKOUT_UNTIL_SESSION_KEY] - time();
        $error = "Слишком много неудачных попыток входа. Попробуйте снова через " . gmdate("i:s", $lockoutRemaining) . ".";
    } else {
        $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
        $password = $_POST['password'] ?? '';
        $auth_code = filter_input(INPUT_POST, 'auth_code', FILTER_SANITIZE_STRING);

        // Этап 1: Проверка логина и пароля
        if ($username && $password && !isset($auth_code)) {
            $stmt = $pdo->prepare("SELECT id, password, email, is_verified, role FROM users WHERE username = :username");
            $stmt->execute(['username' => $username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                if (!$user['is_verified']) {
                    $error = "Ваш адрес электронной почты не подтвержден. Пожалуйста, проверьте свою почту.";
                } else {
                    // Проверяем роль пользователя
                    if ($user['role'] === ROLE_ADMIN) {
                        // Авторизация администратора - устанавливаем сессию и перенаправляем в профиль
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['username'] = $username;
                        $_SESSION['role'] = $user['role'];
                        resetLoginAttempts();
                        header("Location: profile.php");
                        exit;
                    } else {
                        // Для не-администраторов - генерация и отправка кода подтверждения
                        $code = generateAuthCode();
                        $expiry = time() + AUTH_CODE_EXPIRY_SECONDS;

                        // Сохраняем код и ID пользователя во временной сессии
                        $_SESSION[AUTH_CODE_SESSION_KEY] = $code;
                        $_SESSION[AUTH_CODE_EXPIRY_SESSION_KEY] = $expiry;
                        $_SESSION[AUTH_CODE_USER_ID_SESSION_KEY] = $user['id'];

                        if (sendAuthCodeEmail($user['email'], $code)) {
                            // Перенаправляем на форму ввода кода
                            header("Location: login_code.php");
                            exit;
                        } else {
                            $error = "Ошибка при отправке кода подтверждения. Пожалуйста, попробуйте позже.";
                            // В реальном приложении стоит рассмотреть логирование этой ошибки
                        }
                    }
                    resetLoginAttempts(); // Сброс счетчика после успешной проверки логина/пароля
                }
            } else {
                recordFailedLogin();
                $error = "Неверное имя пользователя или пароль.";
            }
        } else {
            if (!$username || !$password) {
                $error = "Пожалуйста, заполните все поля.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Вход | ТСЖ</title>
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
    </style>
</head>
<body>
<div class="container">
    <h2>Вход</h2>
    <?php if (isset($error)): ?>
        <p class="error"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>
    <form method="POST">
        <label for="username">Имя пользователя:</label>
        <input type="text" id="username" name="username" required><br><br>
        <label for="password">Пароль:</label>
        <input type="password" id="password" name="password" required><br><br>
        <button type="submit" class="btn">Войти</button>
    </form>
    <p><a href="register.php">Регистрация</a></p>
</div>
</body>
</html>