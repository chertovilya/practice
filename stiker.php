<?php

require 'config.php'; // подключаем файл с переменными


// Соединение с MySQL
$mysqli = new mysqli($dbHost, $dbUser, $dbPass, $dbName);
if ($mysqli->connect_errno) {
    die("Ошибка подключения к MySQL: " . $mysqli->connect_error);
}

$sql = "SELECT * from v_wb_fbs_orders_stiker where id  = '3898108491';";
$result = $mysqli->query($sql);
$row = mysqli_fetch_assoc($result);
$apiKey = $row['apiKey'];
$order = $row['id'];
$url = 'https://marketplace-api.wildberries.ru/api/v3/orders/stickers?type=png&width=58&height=40';
$data = '{"orders":['.$order.']}';
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: ' . $apiKey
]);
$response = curl_exec($ch);
curl_close($ch);
$result = json_decode($response, true);
foreach ($result['stickers'] as $stickers) {
    $file = $stickers['file'];
    $decodedData = base64_decode($file);
    $file = $order.'.png';
    file_put_contents($file, $decodedData);
    if (file_exists($file)) {
        // Очистка буфера вывода
        if (ob_get_level()) {
            ob_end_clean();
        }
        // Заголовки для скачивания
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($file) . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($file));

        // Чтение и вывод файла
        readfile($file);
        exit;
    } else {
        // Если файл не найден, выводим ошибку
        http_response_code(404);
        echo "Файл не найден.";
    }
}

