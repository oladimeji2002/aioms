<?php

/**
 * AIOIMS - Gemini API Helper
 *
 * Uses Gemini 2.5 Flash through the REST generateContent endpoint.
 *
 * Features:
 * - IPv4
 * - HTTP/1.1
 * - TLS 1.2
 * - Connection timeout
 * - Total request timeout
 * - Retry for temporary Gemini errors
 * - JSON response handling
 * - Markdown-wrapper cleanup
 */

function callGemini($prompt)
{
    /*
    |--------------------------------------------------------------------------
    | API KEY
    |--------------------------------------------------------------------------
    |
    | PUT YOUR VALID GEMINI API KEY HERE.
    |
    | Do NOT upload this key to GitHub or expose it publicly.
    |
    |--------------------------------------------------------------------------
    */

    $apiKey = $_ENV['GEMINI_API_KEY'];

    /*
    |--------------------------------------------------------------------------
    | GEMINI MODEL
    |--------------------------------------------------------------------------
    |
    | We are using Gemini 2.5 Flash because your models endpoint
    | confirmed that this model is available and supports
    | generateContent.
    |
    |--------------------------------------------------------------------------
    */

    $model = "gemini-3.6-flash";


    /*
    |--------------------------------------------------------------------------
    | GEMINI ENDPOINT
    |--------------------------------------------------------------------------
    */

    $url =
        "https://generativelanguage.googleapis.com/" .
        "v1beta/models/" .
        $model .
        ":generateContent";


    /*
    |--------------------------------------------------------------------------
    | REQUEST PAYLOAD
    |--------------------------------------------------------------------------
    */

    $payload = [

        "contents" => [

            [

                "parts" => [

                    [
                        "text" => $prompt
                    ]

                ]

            ]

        ],

        "generationConfig" => [

            /*
            | Ask Gemini to return JSON.
            */

            "responseMimeType" =>
                "application/json"

        ]

    ];


    /*
    |--------------------------------------------------------------------------
    | ENCODE REQUEST
    |--------------------------------------------------------------------------
    */

    $jsonPayload = json_encode(
        $payload,
        JSON_UNESCAPED_UNICODE
    );


    if ($jsonPayload === false) {

        throw new Exception(
            "Failed to encode Gemini request."
        );
    }


    /*
    |--------------------------------------------------------------------------
    | RETRY CONFIGURATION
    |--------------------------------------------------------------------------
    */

    $maxAttempts = 2;

    $lastError = null;


    /*
    |--------------------------------------------------------------------------
    | REQUEST LOOP
    |--------------------------------------------------------------------------
    */

    for (
        $attempt = 1;
        $attempt <= $maxAttempts;
        $attempt++
    ) {

        $ch = curl_init($url);


        curl_setopt_array(
            $ch,
            [

                /*
                |--------------------------------------------------------------------------
                | HTTP
                |--------------------------------------------------------------------------
                */

                CURLOPT_POST =>
                    true,

                CURLOPT_POSTFIELDS =>
                    $jsonPayload,

                CURLOPT_RETURNTRANSFER =>
                    true,

                CURLOPT_HEADER =>
                    false,


                /*
                |--------------------------------------------------------------------------
                | HEADERS
                |--------------------------------------------------------------------------
                */

                CURLOPT_HTTPHEADER => [

                    "Content-Type: application/json",

                    "Accept: application/json",

                    "x-goog-api-key: " .
                        $apiKey

                ],


                /*
                |--------------------------------------------------------------------------
                | TIMEOUTS
                |--------------------------------------------------------------------------
                */

                CURLOPT_CONNECTTIMEOUT =>
                    15,

                CURLOPT_TIMEOUT =>
                    45,


                /*
                |--------------------------------------------------------------------------
                | SSL
                |--------------------------------------------------------------------------
                */

                CURLOPT_SSL_VERIFYPEER =>
                    true,

                CURLOPT_SSL_VERIFYHOST =>
                    2,

                CURLOPT_SSLVERSION =>
                    CURL_SSLVERSION_TLSv1_2,


                /*
                |--------------------------------------------------------------------------
                | NETWORK
                |--------------------------------------------------------------------------
                */

                CURLOPT_IPRESOLVE =>
                    CURL_IPRESOLVE_V4,

                CURLOPT_HTTP_VERSION =>
                    CURL_HTTP_VERSION_1_1,


                /*
                |--------------------------------------------------------------------------
                | TCP KEEP ALIVE
                |--------------------------------------------------------------------------
                */

                CURLOPT_TCP_KEEPALIVE =>
                    1,

                CURLOPT_TCP_KEEPIDLE =>
                    30,

                CURLOPT_TCP_KEEPINTVL =>
                    10,

                /*
                |--------------------------------------------------------------------------
                | Do not follow redirects automatically
                |--------------------------------------------------------------------------
                */

                CURLOPT_FOLLOWLOCATION =>
                    false

            ]
        );


        /*
        |--------------------------------------------------------------------------
        | EXECUTE
        |--------------------------------------------------------------------------
        */

        $response =
            curl_exec($ch);


        $curlError =
            curl_error($ch);

        $curlErrno =
            curl_errno($ch);

        $httpCode =
            curl_getinfo(
                $ch,
                CURLINFO_HTTP_CODE
            );


        curl_close($ch);


        /*
        |--------------------------------------------------------------------------
        | HANDLE CURL FAILURE
        |--------------------------------------------------------------------------
        */

        if ($response === false) {

            $lastError =
                "cURL error (" .
                $curlErrno .
                "): " .
                $curlError;


            /*
            |--------------------------------------------------------------------------
            | Retry temporary connection problems
            |--------------------------------------------------------------------------
            */

            if (
                $attempt < $maxAttempts &&
                in_array(
                    $curlErrno,
                    [
                        6,  // DNS resolution
                        7,  // connection failed
                        28  // timeout
                    ],
                    true
                )
            ) {

                sleep(2);

                continue;
            }


            throw new Exception(
                $lastError
            );
        }


        /*
        |--------------------------------------------------------------------------
        | HANDLE HTTP ERRORS
        |--------------------------------------------------------------------------
        */

        if (
            $httpCode < 200 ||
            $httpCode >= 300
        ) {

            $lastError =
                "Gemini HTTP " .
                $httpCode .
                ": " .
                $response;


            /*
            |--------------------------------------------------------------------------
            | Temporary Gemini errors
            |--------------------------------------------------------------------------
            |
            | 429 = rate limit
            | 500 = server error
            | 502 = bad gateway
            | 503 = unavailable
            | 504 = gateway timeout
            |
            |--------------------------------------------------------------------------
            */

            if (
                $attempt < $maxAttempts &&
                in_array(
                    $httpCode,
                    [
                        429,
                        500,
                        502,
                        503,
                        504
                    ],
                    true
                )
            ) {

                sleep(2);

                continue;
            }


            throw new Exception(
                $lastError
            );
        }


        /*
        |--------------------------------------------------------------------------
        | DECODE GEMINI RESPONSE
        |--------------------------------------------------------------------------
        */

        $result =
            json_decode(
                $response,
                true
            );


        if (!is_array($result)) {

            throw new Exception(
                "Invalid Gemini response: " .
                $response
            );
        }


        /*
        |--------------------------------------------------------------------------
        | CHECK CANDIDATE
        |--------------------------------------------------------------------------
        */

        if (
            !isset(
                $result["candidates"][0]
                    ["content"]
                    ["parts"][0]
                    ["text"]
            )
        ) {

            throw new Exception(
                "Gemini response did not contain generated text: " .
                $response
            );
        }


        /*
        |--------------------------------------------------------------------------
        | GET GENERATED TEXT
        |--------------------------------------------------------------------------
        */

        $text =
            $result["candidates"][0]
                ["content"]
                ["parts"][0]
                ["text"];


        $text =
            trim($text);


        /*
        |--------------------------------------------------------------------------
        | REMOVE JSON CODE FENCES
        |--------------------------------------------------------------------------
        |
        | Protect against Gemini returning:
        |
        | ```json
        | {...}
        | ```
        |
        |--------------------------------------------------------------------------
        */

        if (
            str_starts_with(
                $text,
                "```json"
            )
        ) {

            $text =
                preg_replace(
                    '/^```json\s*/',
                    '',
                    $text
                );

            $text =
                preg_replace(
                    '/\s*```$/',
                    '',
                    $text
                );

            $text =
                trim($text);
        }


        /*
        |--------------------------------------------------------------------------
        | DECODE GENERATED JSON
        |--------------------------------------------------------------------------
        */

        $aiResult =
            json_decode(
                $text,
                true
            );


        if (!is_array($aiResult)) {

            throw new Exception(
                "Gemini returned invalid JSON: " .
                $text
            );
        }


        /*
        |--------------------------------------------------------------------------
        | SUCCESS
        |--------------------------------------------------------------------------
        */

        return $aiResult;
    }


    /*
    |--------------------------------------------------------------------------
    | FINAL FAILURE
    |--------------------------------------------------------------------------
    */

    throw new Exception(
        $lastError ??
        "Unable to communicate with Gemini."
    );
}