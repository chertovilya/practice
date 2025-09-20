<?php
require 'config.php'; // подключаем файл с переменными


//Макс время выполнения скрипта
set_time_limit(300);

// Соединение с MySQL
$mysqli = new mysqli($dbHost, $dbUser, $dbPass, $dbName);
if ($mysqli->connect_errno) {
    die("Ошибка подключения к MySQL: " . $mysqli->connect_error);
}

$sql = "SELECT * FROM wildberries_fbs_sellers;";
$sellers = $mysqli->query($sql);

foreach ($sellers as $seller) {

    // запрос к бд
    $sellerId = $seller['id'];
    $sql = "SELECT * FROM v_wb_fbs_orders_to_update_status
            WHERE sellerId = $sellerId;";
    $result = $mysqli->query($sql);

    $orders = array();
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $orders[] = (int)$row['id'];
        }
    }

    $apiKey = $seller['apiKey'];

    $url = 'https://marketplace-api.wildberries.ru/api/v3/orders/status';

    $chunks = array_chunk($orders, 100);

    foreach ($chunks as $chunk) {
        $data = json_encode(['orders' => $chunk]);
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: ' . $apiKey
        ]);

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            echo 'Ошибка CURL: ' . curl_error($ch);
            echo "\n";
            curl_close($ch);
            exit;
        }

        curl_close($ch);

        $result = json_decode($response, true);

        if ($result === null) {
            echo "Ошибка декодирования JSON ответа";
            echo "\n";
            exit;
        }

        // Выводим статусы заказов
        foreach ($result['orders'] as $orderStatus) {
            $id = $orderStatus['id'];
            $wbStatus = $orderStatus['wbStatus'];
            $supplierStatus = $orderStatus['supplierStatus'];

            $sql = "UPDATE wildberries_fbs_orders 
                    SET wbStatus='$wbStatus', supplierStatus='$supplierStatus' 
                    WHERE id=$id;";

            if (!$mysqli->query($sql)) {
                echo "Ошибка записи заказа $id: " . $mysqli->error . "\n";
                echo "\n";
            }
        }
        echo date('Y-m-d H:i:s');
        echo "\n";
        echo "Статусы FBS обновлены.\n";
        echo "\n";
    }    
}
// Закрываем соединение
$mysqli->close();
