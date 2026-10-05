<?php

header("Content-Type: application/json");


$apiKey = "";
$url =
    "https://generativelanguage.googleapis.com/v1beta/models";

$start = microtime(true);

$ch = curl_init($url);

curl_setopt_array($ch, [
    CURLOPT_HTTPGET => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HEADER => false,

    CURLOPT_HTTPHEADER => [
        "Accept: application/json",
        "x-goog-api-key: " . $apiKey
    ],

    CURLOPT_CONNECTTIMEOUT => 10,
    CURLOPT_TIMEOUT => 20,

    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_SSL_VERIFYHOST => 2,
    CURLOPT_SSLVERSION => CURL_SSLVERSION_TLSv1_2,

    CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1
]);

$response = curl_exec($ch);

$errno = curl_errno($ch);
$error = curl_error($ch);

$httpCode = curl_getinfo(
    $ch,
    CURLINFO_HTTP_CODE
);

$time = round(
    microtime(true) - $start,
    3
);

curl_close($ch);

echo json_encode([
    "success" => $response !== false,
    "http_code" => $httpCode,
    "curl_errno" => $errno,
    "curl_error" => $error,
    "time_seconds" => $time,
    "response" =>
        $response !== false
            ? json_decode($response, true)
            : null
], JSON_PRETTY_PRINT);