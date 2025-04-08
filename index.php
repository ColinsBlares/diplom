<?php
session_start();

// Подключение к базе данных через db.php (если нужно)
require_once 'db.php';
$pdo = require 'db.php';

// Проверка авторизации
$is_logged_in = isset($_SESSION['user_id']);

// Получение последних новостей из базы данных
$stmt_news = $pdo->prepare("SELECT * FROM news ORDER BY created_at DESC LIMIT 5");
$stmt_news->execute();
$recent_news = $stmt_news->fetchAll();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ТСЖ - Главная страница</title>
    <style>
        /* Общие стили */
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f5f5f5;
            color: #333;
        }

        header {
            background-color: #4CAF50;
            color: white;
            padding: 20px;
            text-align: center;
        }

        header h1 {
            margin: 0;
            font-size: 28px;
        }

        .container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        .intro {
            text-align: center;
            margin-bottom: 30px;
        }

        .intro h2 {
            font-size: 24px;
            color: #2c3e50;
        }

        .intro p {
            font-size: 16px;
            color: #555;
        }

        .btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            font-size: 16px;
            transition: background-color 0.3s;
            margin: 10px;
        }

        .btn:hover {
            background-color: #45a049;
        }

        .features {
            display: flex;
            justify-content: space-around;
            gap: 20px;
            margin-bottom: 30px;
        }

        .feature-box {
            flex: 1;
            padding: 20px;
            background-color: #f8f9fa;
            border: 1px solid #ddd;
            border-radius: 8px;
            text-align: center;
        }

        .feature-box strong {
            font-size: 18px;
            display: block;
            margin-bottom: 10px;
        }

        footer {
            background-color: #333;
            color: white;
            text-align: center;
            padding: 10px;
            margin-top: 30px;
        }

        /* Стили для новостей */
        .news-section ul {
            list-style-type: none;
            padding: 0;
            margin: 0;
        }

        .news-section li {
            background-color: #f8f9fa;
            padding: 15px;
            margin-bottom: 10px;
            border-radius: 5px;
            border-left: 4px solid #4CAF50;
        }

        .news-section li strong {
            font-size: 18px;
            display: block;
            margin-bottom: 5px;
        }

        .news-section li small {
            color: #666;
            display: block;
            margin-top: 5px;
        }

        @media (max-width: 768px) {
            .features {
                flex-direction: column;
            }

            .feature-box {
                margin-bottom: 20px;
            }
        }
    </style>
    <!-- Yandex.Metrika counter -->
    <script type="text/javascript" >
       (function(m,e,t,r,i,k,a){m[i]=m[i]||function(){(m[i].a=m[i].a||[]).push(arguments)};
       m[i].l=1*new Date();
       for (var j = 0; j < document.scripts.length; j++) {if (document.scripts[j].src === r) { return; }}
       k=e.createElement(t),a=e.getElementsByTagName(t)[0],k.async=1,k.src=r,a.parentNode.insertBefore(k,a)})
       (window, document, "script", "https://mc.yandex.ru/metrika/tag.js", "ym");
    
       ym(100476206, "init", {
            clickmap:true,
            trackLinks:true,
            accurateTrackBounce:true,
            webvisor:true
       });
    </script>
    <noscript><div><img src="https://mc.yandex.ru/watch/100476206" style="position:absolute; left:-9999px;" alt="" /></div></noscript>
    <!-- /Yandex.Metrika counter -->
</head>
<body>
<header>
    <h1>Добро пожаловать в систему управления ТСЖ</h1>
</header>

<div class="container">
    <div class="intro">
        <h2>Упрощенное управление вашим домом</h2>
        <p>Наш сайт поможет вам легко управлять платежами, заявками и новостями для вашего ТСЖ.</p>
        <?php if ($is_logged_in): ?>
            <a href="profile.php" class="btn">Перейти в профиль</a>
        <?php else: ?>
            <a href="login.php" class="btn">Войти</a>
            <a href="register.php" class="btn">Зарегистрироваться</a>
        <?php endif; ?>
    </div>

    <!-- Раздел: Возможности -->
    <h2>Что вы можете делать?</h2>
    <div class="features">
        <div class="feature-box">
            <strong>Просмотр платежей</strong>
            <p>Отслеживайте все платежи и управляйте ими.</p>
        </div>
        <div class="feature-box">
            <strong>Подача заявок</strong>
            <p>Оставляйте заявки на обслуживание и следите за их статусом.</p>
        </div>
        <div class="feature-box">
            <strong>Получение новостей</strong>
            <p>Будьте в курсе последних новостей от вашего ТСЖ.</p>
        </div>
    </div>

    <!-- Раздел: Новости -->
    <h2>Последние новости</h2>
    <div class="news-section">
        <ul>
            <?php if (!empty($recent_news)): ?>
                <?php foreach ($recent_news as $news): ?>
                    <li>
                        <strong><?= htmlspecialchars($news['title']) ?></strong><br>
                        <?= htmlspecialchars($news['content']) ?><br>
                        <small>Дата: <?= htmlspecialchars($news['created_at']) ?></small>
                    </li>
                <?php endforeach; ?>
            <?php else: ?>
                <li>Нет новых новостей.</li>
            <?php endif; ?>
        </ul>
    </div>

    <!-- Раздел: Контакты -->
    <h2>Контакты</h2>
    <p>Если у вас есть вопросы, свяжитесь с нами:</p>
    <ul>
        <li>Email: admin@colinsblare.ru</li>
        <li>Телефон: +7 (999) 123-45-67</li>
    </ul>
</div>

<footer>
    &copy; 2025 ТСЖ. Все права защищены.
</footer>
</body>
</html>