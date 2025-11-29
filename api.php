<?php
// ==== CONFIG ====
$rateLimit = 5;           // Max requests
$rateWindow = 60;         // In seconds
$tmpDir = sys_get_temp_dir() . '/rate_limit/'; // Store files here

// ==== SETUP ====
if (!file_exists($tmpDir)) {
    mkdir($tmpDir, 0755, true);
}

$ip = $_SERVER['REMOTE_ADDR'];
$rateFile = $tmpDir . md5($ip) . '.json';

// ==== RATE LIMIT CHECK ====
$now = time();
$accessLog = [];

if (file_exists($rateFile)) {
    $accessLog = json_decode(file_get_contents($rateFile), true);
    // Remove old timestamps
    $accessLog = array_filter($accessLog, function ($timestamp) use ($now, $rateWindow) {
        return ($timestamp + $rateWindow) >= $now;
    });
}

if (count($accessLog) >= $rateLimit) {
    http_response_code(429);
    echo json_encode(["error" => "Rate limit exceeded. Try again later."]);
    exit;
}

// Log current request
$accessLog[] = $now;
file_put_contents($rateFile, json_encode($accessLog));

// ==== VALIDATION ====
if (!isset($_GET['url']) || empty($_GET['url'])) {
    http_response_code(400);
    echo json_encode(["error" => "Missing 'url' parameter"]);
    exit;
}

// ==== BUILD API CALL ====
$inputUrl = $_GET['url'];
$encodedUrl = urlencode($inputUrl);

$apiUrl = "https://utdqxiuahh.execute-api.ap-south-1.amazonaws.com/pro/fetch?url=$encodedUrl&user_id=h2";

$headers = [
    "x-api-key: fAtAyM17qm9pYmsaPlkAT8tRrDoHICBb2NnxcBPM",
    "User-Agent: okhttp/4.12.0",
    "Accept-Encoding: gzip"
];

$ch = curl_init($apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_ENCODING, '');

$response = curl_exec($ch);

if (curl_errno($ch)) {
    http_response_code(500);
    echo json_encode(["error" => curl_error($ch)]);
    curl_close($ch);
    exit;
}

curl_close($ch);

// ==== RETURN RESULT ====
header("Content-Type: application/json");
echo $response;