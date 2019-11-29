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

<table style="background:#f9fafc;border-collapse:collapse;border-spacing:0;text-align:left;vertical-align:top;width:620px">
    <tbody><tr>
        <td colspan='2'>
            <div>
                <p style="padding-left:20px;font:400 18px/1.5 'helvetica', 'arial', sans-serif;color:#502bbb;">Уважаемый, <?= $user["name"]; ?>!</p>
                <p style="padding-left:20px;margin:0;font:400 16px/1.5 'helvetica', 'arial', sans-serif">У вас запланирована задача:</p>
                <ul style="padding-left:40px;margin:0;font:400 14px/1.5 'helvetica', 'arial', sans-serif">
                    <?php foreach ($user_tasks as $item): ?>
                        <li><?= htmlspecialchars($item["title"]); ?> на <?= htmlspecialchars(date("d.m.Y", strtotime($item["deadline"]))); ?>.</li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </td>
        <td></td>
    </tr>
    <tr>
        <td style="padding-left:20px">
            <img style="border:0;width:153px" src="../img/logo.png" alt="Логитип Дела в порядке">
        </td>
        <td style="font:13px/1.5 'helvetica', 'arial', sans-serif;padding-top:40px;padding-bottom:15px;padding-left:40px;text-align:left">
            <p style="margin:0">© 2019, «Дела в порядке»</p>
            <p style="margin:0;color:#502bbb">Веб-приложение для удобного<br> ведения списка дел.</p>
        </td>
    </tr></tbody>
</table>
</body>
</html>
