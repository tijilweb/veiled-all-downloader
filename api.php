<?php
// ==== CONFIG ====
$rateLimit = 5;
$rateWindow = 60;
$tmpDir = sys_get_temp_dir() . '/rate_limit/';

// ==== CREATE DIR ====
if (!file_exists($tmpDir)) {
    mkdir($tmpDir, 0755, true);
}

// ==== ONE API KEY ====
$VALID_API_KEY = "VEILED_ASSEMBLY";

// ==== CHECK API KEY ====
$clientKey = $_GET['api_key'] ?? null;
if ($clientKey !== $VALID_API_KEY) {
    http_response_code(401);
    echo json_encode(["error" => "Invalid API Key"]);
    exit;
}

// ==== RATE LIMIT ====
$ip = $_SERVER['REMOTE_ADDR'];
$rateFile = $tmpDir . md5($ip) . '.json';
$now = time();
$accessLog = [];

if (file_exists($rateFile)) {
    $accessLog = json_decode(file_get_contents($rateFile), true);
    $accessLog = array_filter($accessLog, function ($t) use ($now, $rateWindow) {
        return ($t + $rateWindow) >= $now;
    });
}

if (count($accessLog) >= $rateLimit) {
    http_response_code(429);
    echo json_encode(["error" => "Rate limit exceeded"]);
    exit;
}

$accessLog[] = $now;
file_put_contents($rateFile, json_encode($accessLog));

// ==== URL CHECK ====
if (!isset($_GET['url']) || empty($_GET['url'])) {
    http_response_code(400);
    echo json_encode(["error" => "Missing 'url' parameter"]);
    exit;
}

// ==== CALL AWS API ====
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

header("Content-Type: application/json");
echo $response;
?>
