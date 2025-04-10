<?php
// db.php

try {
    // Настройки подключения к базе данных
    $host = 'localhost:3308';        // Хост (обычно localhost)
    $dbname = 'kirilljuk3';  // Имя вашей базы данных
    $username = 'kirilljuk3';         // Имя пользователя БД
    $password = 'QTLg#V34TGYU1Y1V';             // Пароль пользователя БД

    // Создание подключения через PDO
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);

    // Установка режима обработки ошибок PDO
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Ошибка подключения к базе данных: " . $e->getMessage());
}

// Экспорт подключения для использования в других файлах
return $pdo;