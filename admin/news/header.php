<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle : 'Сайт'; ?></title>

    <!-- Подключение Bootstrap -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- TinyMCE -->
    <script src="https://cdn.tiny.cloud/1/ojqx6jq1u8uzrt68twrzg3on3fo4bcm1ge1tqegym0hjpszr/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>

    <!-- Ваши дополнительные стили -->
    <!--<link rel="stylesheet" href="path_to_your_custom_styles.css">-->
    <script>
    tinymce.init({
        selector: 'textarea#content',
        height: 400,
        menubar: false,
        plugins: 'advlist autolink lists link image charmap preview anchor pagebreak code',
        toolbar: 'undo redo | formatselect | bold italic backcolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | removeformat | code',
        content_style: 'body { font-family:Arial,sans-serif; font-size:14px }'
    });
    </script>
</head>
<body class="bg-light p-4">