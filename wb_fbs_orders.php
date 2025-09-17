<?php
require 'config.php'; // подключаем файл с переменными

//Макс время выполнения скрипта
set_time_limit(300);

function getFbsOrders($url, $apiKey, $dateFrom, $limit, $next) {
        $query = http_build_query([
            'limit' => $limit,
            'next' => $next,
            'dateFrom' => $dateFrom,        
        ]);

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => "$url?$query",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                "Authorization: $apiKey",
                "Content-Type: application/json"
            ],
        ]);

        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if ($httpCode !== 200) {
            die("Ошибка API: HTTP $httpCode, ответ: $response");
        }

        $data = json_decode($response, true);
        if ($data === null) {
            die("Ошибка декодирования JSON");
        }
        return $data;
    }

// Соединение с MySQL
$mysqli = new mysqli($dbHost, $dbUser, $dbPass, $dbName);
if ($mysqli->connect_errno) {
    die("Ошибка подключения к MySQL: " . $mysqli->connect_error);
}

$sql = "SELECT * FROM wildberries_fbs_sellers;";
$sellers = $mysqli->query($sql);

foreach ($sellers as $seller) {

    // Конфигурация
    //echo $seller['id'];
    //echo $seller['seller'];
    $apiKey = $seller['apiKey'];
    $apiUrl = 'https://marketplace-api.wildberries.ru/api/v3/orders';


    // Создаем объект DateTime для текущей даты
    $date = new DateTime(); 
    // Вычитаем 3 дня
    $date->modify('-1 days'); 
    // Получаем Unix timestamp
    $dateFrom = $date->getTimestamp(); 

    $limit = 1000;
    $next = 0;

   
    // Функция запроса к API Wildberries FBS
    

    // Получаем заказы
    while (true) {
        $data = getFbsOrders($apiUrl, $apiKey, $dateFrom, $limit, $next);

        if (empty($data['orders'])) {
            echo "Заказы не найдены.\n";
            echo "\n";
            break;
        }

        $next = $data['next']; //пагинация
        
        // Обработка и запись заказов в БД
        foreach ($data['orders'] as $order) {
            $id = $order['id'];
            $article = $order['article'];
            $supplyId = $order['supplyId'];
            $warehouseId = $order['warehouseId'];
            $createdAt = $order['createdAt'];
            $skus = $order['skus'][0];
            $sellerId = $seller['id'];
            $sql = "INSERT INTO wildberries_fbs_orders (id,article,supplyId,warehouseId,createdAt,skus,sellerId)
                    VALUES ('$id','$article','$supplyId','$warehouseId','$createdAt','$skus','$sellerId')
                    ON DUPLICATE KEY UPDATE
                        supplyId = VALUES(supplyId),
                        warehouseId = VALUES(warehouseId),
                        createdAt = VALUES(createdAt),
                        skus = VALUES(skus),
                        sellerId = VALUES(sellerId);";

            if (!$mysqli->query($sql)) {
                echo "Ошибка записи заказа $id: " . $mysqli->error . "\n";
            }
        }
        
        echo date('Y-m-d H:i:s');
        echo "\n";
        echo "Импорт заказов FBS завершён.\n";
        echo "\n";
    }
}
$mysqli->close();
