<?php
require 'config.php'; // подключаем файл с переменными

//Макс время выполнения скрипта
set_time_limit(300);

// Функция запроса к API Wildberries FBS
    function getFbsSupplies($url, $apiKey, $limit, $next) {
        $query = http_build_query([
            'limit' => $limit,
            'next' => $next,
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

    echo $seller['id'];
    echo "\n";
    echo $seller['seller'];
    echo "\n";

    // Конфигурация
    $apiKey = $seller['apiKey'];
    $apiUrl = 'https://marketplace-api.wildberries.ru/api/v3/supplies';
    $limit = 1000;
    $next = 0;

    
    // Получаем заказы
    while (true) {
        $data = getFbsSupplies($apiUrl, $apiKey, $limit, $next);

        if (empty($data['supplies'])) {
            echo "Поставки не найдены.\n";
            break;
        }

        $next = $data['next']; //пагинация
        
        // Обработка и запись заказов в БД
        foreach ($data['supplies'] as $supply) {
            $id = $supply['id'];
            $done = $supply['done'];
            $createdAt = $supply['createdAt'];
            $closedAt = $supply['closedAt'];
            $scanDt = $supply['scanDt'];
            $cargoType = $supply['cargoType'];
            $destinationOfficeId = $supply['destinationOfficeId'];
            $sellerId = $seller['id'];

            $sql = "INSERT INTO wildberries_fbs_supplies (id,done,createdAt,closedAt,scanDt,cargoType,destinationOfficeId,sellerId)
                    VALUES ('$id','$done','$createdAt','$closedAt','$scanDt','$cargoType','$destinationOfficeId','$sellerId')
                    ON DUPLICATE KEY UPDATE
                        done = VALUES(done),
                        createdAt = VALUES(createdAt),
                        closedAt = VALUES(closedAt),
                        scanDt = VALUES(scanDt),
                        cargoType = VALUES(cargoType),
                        destinationOfficeId = VALUES(destinationOfficeId),
                        sellerId = VALUES(sellerId);";

            if (!$mysqli->query($sql)) {
                echo "Ошибка записи заказа $id: " . $mysqli->error . "\n";
            }
        }
        echo date('Y-m-d H:i:s');
        echo "\n";
        echo "Импорт поставок FBS завершён.\n";
        echo "\n";
    }
}
$mysqli->close();

