<?php
session_start();

// Проверка авторизации
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit;
}

$pdo = require_once '../../db.php';

$news_id = $_GET['id'] ?? null;

if (!$news_id) {
    die("Ошибка: ID новости не указан.");
}

// Получение новости из БД
$stmt = $pdo->prepare("SELECT * FROM news WHERE id = :id");
$stmt->execute(['id' => $news_id]);
$news = $stmt->fetch();

if (!$news) {
    die("Новость не найдена.");
}

// Обработка отправки формы
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');

    if ($title === '' || $content === '') {
        $error = "Все поля обязательны для заполнения.";
    } else {
        $stmt = $pdo->prepare("UPDATE news SET title = :title, content = :content WHERE id = :id");
        $stmt->execute([
            'title' => $title,
            'content' => $content,
            'id' => $news_id
        ]);

        header("Location: list?updated=1");
        exit;
    }
}

$pageTitle = 'Редактировать новость';

include 'header.php';
?>

<div class="container">
    <h2 class="mb-4">Редактировать новость</h2>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <form method="post">
        <div class="form-group">
            <label for="title">Заголовок:</label>
            <input type="text" class="form-control" name="title" value="<?= htmlspecialchars($news['title']) ?>" required>
        </div>

        <div class="form-group">
            <label for="content">Содержание:</label>
            <textarea id="content" name="content" class="form-control"><?= htmlspecialchars($news['content']) ?></textarea>
        </div>

        <button type="submit" class="btn btn-primary">Сохранить</button>
        <a href="list" class="btn btn-secondary">Отмена</a>
    </form>
</div>

<?php
include '../footer.php';
?>