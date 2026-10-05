<?php

require_once __DIR__ . "/gemini.php";

header("Content-Type: application/json");

try {

    $prompt = "Return a JSON object with a greeting and a short message about AIOIMS.";

    $result = callGemini($prompt);

    echo json_encode([
        "success" => true,
        "message" => "Gemini API is working.",
        "response" => $result
    ], JSON_PRETTY_PRINT);

} catch (Exception $e) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}