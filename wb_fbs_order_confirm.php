<?php
require 'config.php'; // подключаем файл с переменными

// Соединение с MySQL
$mysqli = new mysqli($dbHost, $dbUser, $dbPass, $dbName);
if ($mysqli->connect_errno) {
    die("Ошибка подключения к MySQL: " . $mysqli->connect_error);
}

// Функция запроса к API Wildberries FBS
function getFbsOrderConfirm($url, $apiKey, $supplyId, $orderId) {

    $url = "https://marketplace-api.wildberries.ru/api/v3/supplies/{$supplyId}/orders/{$orderId}";

    $curl = curl_init($url);
    curl_setopt_array($curl, [
        CURLOPT_CUSTOMREQUEST => 'PATCH',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            "Authorization: $apiKey",
            "Content-Type: application/json"
        ],
    ]);

    $response = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    
    if ($httpCode !== 204) {
        die("Ошибка API: HTTP $httpCode, ответ: $response");
    }
}


if (isset($_POST['my_button'])) {
    $barcode = isset($_POST['barcode']) ? htmlspecialchars($_POST['barcode']) : 0;
    $orderId = isset($_POST['orderId']) ? (int)$_POST['orderId'] : 0;
    $sellerId = isset($_POST['sellerId']) ? (int)$_POST['sellerId'] : 0;
    $supplyId = isset($_POST['supplyId']) ? htmlspecialchars($_POST['supplyId']) : 0;
    $apiKey = isset($_POST['apiKey']) ? htmlspecialchars($_POST['apiKey']) : 0;

    echo "Заказ был переведен в статус на сборке!<br>";
    echo "ШК: $barcode<br>";
    echo "Заказ: $orderId<br>";
    echo "Кабинет: $sellerId<br>";
    echo "Поставка: $supplyId<br><br>";
   
    $url = "https://marketplace-api.wildberries.ru/api/v3/supplies/{$supplyId}/orders/{$orderId}";
    getFbsOrderConfirm($url, $apiKey, $supplyId, $orderId);

    $sql = "UPDATE wildberries_fbs_orders 
            SET supplierStatus='confirm' 
            WHERE id=$orderId;";

    if (!$mysqli->query($sql)) {
        echo "Ошибка записи заказа $id: " . $mysqli->error . "\n";
        echo "\n";
    }

    // Кнопка назад — это обычная ссылка или форма с кнопкой, которая ведет на нужную страницу
    echo '<form action="index.php" method="get">';
    echo '<button type="submit">Назад</button>';
    echo '</form>';
} else {
    echo "Кнопка не была нажата.";
}
// Закрываем соединение
$mysqli->close();
?>
