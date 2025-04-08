<?php
session_start();

// Уничтожение всех данных сессии
$_SESSION = []; // Очистка переменных сессии
if (ini_get("session.use_cookies")) {
    // Удаление куки сессии
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000, // Время истечения в прошлом
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Уничтожение самой сессии
session_destroy();

// Перенаправление на страницу входа
header("Location: login.php");
exit;