<!doctype html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
</head>
<body>
<!-- Текст письма: Уважаемый, %имя пользователя%. У вас запланирована задача %имя задачи% на %время задачи% -->

<h1>Уважаемый, <?= $user["name"]; ?></h1>
<ul>
    <?php foreach ($user_tasks as $item): ?>
        <li>У вас запланирована задача <?= htmlspecialchars($item["title"]); ?> на <?= $item["deadline"]; ?></li>
    <?php endforeach; ?>
</ul>

</body>
</html>
