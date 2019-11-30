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

<table style="width:600px;border-collapse:collapse;border-spacing:0;background:#f9fafc;text-align:left;vertical-align:top">
    <tbody style="padding-left:40px">
        <tr><td style="padding-top:40px;padding-bottom:60px;vertical-align:middle">
            <img src="https://habrastorage.org/webt/mx/sg/31/mxsg31tpj_xjumdmbdkpywjv_i0.png" style="width:153px" alt="Логитип Дела в порядке">
        </td></tr
        ><tr><td>
            <div>
                <p style="font:400 18px/1.5 'helvetica', 'arial', sans-serif;color:#502bbb;">Уважаемый, <?= $user["name"]; ?>!</p>
                <span style="margin:0;padding-right:10px;font:400 16px/1.5 'helvetica', 'arial', sans-serif;line-height:1.4">У вас запланирована задача</span><img src="https://habrastorage.org/webt/1m/fh/te/1mfhtewdfxrcszj7wuuqzxdx2ae.png" style="width:13px" alt="Значок дедлайна">
                <ul style="margin:0;padding-left:40px;font:400 16px/1.5 'helvetica', 'arial', sans-serif;line-height:1.4">
                    <?php foreach ($user_tasks as $item): ?>
                        <li><?= htmlspecialchars($item["title"]); ?> на <?= htmlspecialchars(date("d.m.Y", strtotime($item["deadline"]))); ?>.</li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </td></tr>
        <tr><td style="padding-top:80px;padding-bottom:15px">
            <p style="margin:0;font:14px/1.5 'helvetica', 'arial', sans-serif;color:#502bbb">© 2019, «Дела в порядке»</p>
            <p style="margin:0;font:14px/1.5 'helvetica', 'arial', sans-serif">Веб-приложение для удобного ведения списка дел.</p>
        </td></tr>
    </tbody>
</table>
</body>
</html>
