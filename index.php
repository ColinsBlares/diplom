<?php
session_start();

// Подключение к конфигурационному файлу
require_once 'config.php';

// Подключение к базе данных
$pdo = require 'db.php';

// Проверка авторизации
$is_logged_in = isset($_SESSION['user_id']);

// Константа для количества новостей на главной странице
const NEWS_LIMIT = 5;
function getRecentNews(PDO $pdo, int $limit): array
{
    $stmt = $pdo->prepare("SELECT * FROM news ORDER BY created_at DESC LIMIT :limit");
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

// Получение последних новостей
$recent_news = getRecentNews($pdo, NEWS_LIMIT);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ТСЖ - Главная страница</title>
    <meta name="description" content="Главная страница системы управления ТСЖ. Управляйте платежами, заявками и будьте в курсе последних новостей вашего дома.">
    <style>
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
        
        .container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        
        .intro {
            margin-bottom: 30px;
            text-align: center;
        }
        
        .features-section, .news-section, .contact-section{
            margin-bottom: 30px;
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
            margin: 10px;
        }
        
        .features {
            display: flex;
            gap: 20px;
        }
        
        .feature-box {
            flex: 1;
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            border: 1px solid #ddd;
        }
        
        .news-section ul {
            list-style-type: none;
            padding-left: 0;
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
        
        .news-section li p {
            margin-bottom: 10px;
        }
        
        footer {
            background-color: #333;
            color: white;
            text-align: center;
            padding: 10px;
        }

    </style>
    <!-- Яндекс.Метрика -->
    <script type="text/javascript">
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
            <a href="profile" class="btn">Перейти в профиль</a>
        <?php else: ?>
            <a href="login" class="btn">Войти</a>
            <a href="register" class="btn">Зарегистрироваться</a>
        <?php endif; ?>
    </div>

    <!--<section class="features-section">-->
    <!--    <h2>Что вы можете делать?</h2>-->
    <!--    <div class="features">-->
    <!--        <div class="feature-box">-->
    <!--            <strong>Просмотр платежей</strong>-->
    <!--            <p>Отслеживайте все платежи и управляйте ими.</p>-->
    <!--        </div>-->
    <!--        <div class="feature-box">-->
    <!--            <strong>Подача заявок</strong>-->
    <!--            <p>Оставляйте заявки на обслуживание и следите за их статусом.</p>-->
    <!--        </div>-->
    <!--        <div class="feature-box">-->
    <!--            <strong>Получение новостей</strong>-->
    <!--            <p>Будьте в курсе последних новостей от вашего ТСЖ.</p>-->
    <!--        </div>-->
    <!--    </div>-->
    <!--</section>-->

    <section class="news-section">
        <h2>Последние новости</h2>
        <ul>
            <?php if (!empty($recent_news)): ?>
                <?php foreach ($recent_news as $news): ?>
                    <li>
                        <strong><?= htmlspecialchars($news['title']) ?></strong>
                        <p><?= $news['content'] ?></p>
                        <small>Дата: <?= htmlspecialchars($news['created_at']) ?></small>
                    </li>
                <?php endforeach; ?>
                <li><a href="all_news.php">Все новости</a></li>
            <?php else: ?>
                <li>На данный момент нет актуальных новостей. Следите за обновлениями!</li>
            <?php endif; ?>
        </ul>
    </section>

    <section class="contact-section">
        <h2>Контакты</h2>
        <p>Если у вас есть вопросы, свяжитесь с нами:</p>
        <ul>
            <li>Email: <a href="mailto:<?= CONTACT_EMAIL ?>"><?= CONTACT_EMAIL ?></a></li>
            <li>Телефон: <?= CONTACT_PHONE ?></li>
        </ul>
    </section>
</div>

<footer>
    &copy; <?= date('Y') ?> <?= SITE_NAME ?>. Все права защищены.
</footer>
</body>
</html>