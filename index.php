<?php
require 'config.php'; // подключаем файл с переменными


// Соединение с MySQL
$mysqli = new mysqli($dbHost, $dbUser, $dbPass, $dbName);
if ($mysqli->connect_errno) {
    die("Ошибка подключения к MySQL: " . $mysqli->connect_error);
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Получаем штрихкод из POST-запроса
    $barcode = trim($_POST['barcode'] ?? '');

    // Простая валидация: проверим, что штрихкод не пустой и состоит из цифр
    if ($barcode === '') {
        $message = "Пожалуйста, введите штрихкод.";
    } elseif (!preg_match('/^\d+$/', $barcode)) {
        $message = "Штрихкод должен содержать только цифры.";
    } else {
        $sql = "SELECT * FROM `v_wb_fbs_orders_to_confirm`
                WHERE skus = '$barcode'
                LIMIT 1;";
        $result = $mysqli->query($sql);
        $row = mysqli_fetch_assoc($result);

        if (empty($row)) {
            $message = "Заказ не найден";
        } else {
            $message = "ШК: " . $barcode. "\n";
            $message .= "</BR>";
            $message .= "Заказ: " . htmlspecialchars($row['id']) . "\n";
            $message .= "</BR>";
            $message .= "Заказ создан: " . htmlspecialchars($row['createdAt']) . "\n";
            $message .= "</BR>";
            $message .= "Артикул: " . htmlspecialchars($row['article']) . "\n";
            $message .= "</BR>";
            $message .= "Кабинет: " . htmlspecialchars($row['seller']) . "\n";
            $message .= "</BR>";

            $orderId = $row['id'];
            $sellerId = $row['sellerId'];

            $sql = "SELECT * FROM v_wb_fbs_supply_for_orders_confirm
                    WHERE sellerId = $sellerId
                    LIMIT 1;";
            $result = $mysqli->query($sql);
            $row = mysqli_fetch_assoc($result);
            $supplyId = $row['id'];
            $apiKey = $row['apiKey'];

            $message .= "Поставка: " . htmlspecialchars($supplyId) . "\n";
            $message .= "</BR>";
            $message .= "В постваке заказов: " . htmlspecialchars($row['cnt']) . "\n";
            $message .= "</BR>";
            $message .= "Поставка создана : " . htmlspecialchars($row['createdAt']) . "\n";
            $message .= "</BR>";
        }

    }
}
$mysqli->close();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Ввод штрихкода</title>
</head>
<body>
    <h1>Введите штрихкод</h1>
    
    <form method="post" action="">
        <label for="barcode">Штрихкод:</label>
        <input type="text" id="barcode" name="barcode" required pattern="\d+" autofocus>
        <button type="submit">Отправить</button>
    </form>
    <?php if (!empty($message)): ?>
        <p><?= $message ?></p>
    <?php endif;?>
    <?php echo '</br>';?>

    <?php if (!empty($orderId)): ?>
        <form method="post" action="wb_fbs_order_confirm.php">
            <input type="hidden" name="barcode" value="<?php echo htmlspecialchars($barcode); ?>">
            <input type="hidden" name="orderId" value="<?php echo htmlspecialchars($orderId); ?>">
            <input type="hidden" name="sellerId" value="<?php echo htmlspecialchars($sellerId); ?>">
            <input type="hidden" name="supplyId" value="<?php echo htmlspecialchars($supplyId); ?>">
            <input type="hidden" name="apiKey" value="<?php echo htmlspecialchars($apiKey); ?>">
            <button type="submit" name="my_button">Перевести заказ на сборку</button>
        </form>
    <?php endif; ?>
</form>
</body>
</html>