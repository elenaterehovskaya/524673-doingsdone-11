<!doctype html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
</head>
<body>
<table style="max-width:600px;width:100%;border-collapse:collapse;border-spacing:0;border:0;background-color:#f9fafc;text-align:left;vertical-align:top" role="presentation">
    <tbody>
    <tr>
        <td style="padding-top:40px;padding-bottom:60px;padding-left:40px">
            <img src="https://habrastorage.org/webt/mx/sg/31/mxsg31tpj_xjumdmbdkpywjv_i0.png" alt="Логитип Дела в порядке"
                 width="153" height="42" style="border:0;outline:none;text-decoration:none;display:block">
        </td>
    </tr>
    <tr>
        <td style="padding-left:40px">
            <div>
                <p style="font:400 18px/1.5 'helvetica', 'arial', sans-serif;color:#502bbb;">Уважаемый(ая) <?= htmlspecialchars($dataUser["name"]); ?>!</p>
                <span style="margin:0;padding-right:8px;font:400 16px/1.5 'helvetica', 'arial', sans-serif;line-height:1.4">У вас запланирована задача</span>
                <img src="https://habrastorage.org/webt/1m/fh/te/1mfhtewdfxrcszj7wuuqzxdx2ae.png" width="13" height="14">
                <ul style="margin:0;padding-left:40px;font:400 16px/1.5 'helvetica', 'arial', sans-serif;line-height:1.4">
                    <?php foreach ($tasksUser as $item): ?>
                        <li><?= htmlspecialchars($item["title"]); ?> на <?= htmlspecialchars(date("d.m.Y",
                                strtotime($item["deadline"]))); ?>.
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </td>
    </tr>
    <tr>
        <td style="padding-top:80px;padding-bottom:15px;padding-left:40px">
            <p style="margin:0;font:14px/1.5 'helvetica', 'arial', sans-serif;color:#502bbb">© 2019, «Дела в порядке»</p>
            <p style="margin:0;font:14px/1.5 'helvetica', 'arial', sans-serif">Веб-приложение для удобного ведения списка дел.</p>
        </td>
    </tr>
    </tbody>
</table>
</body>
</html>