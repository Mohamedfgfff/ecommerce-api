<?php
// Dependencies: openssl و curl مفعلين في PHP

function getServiceAccountJson($path = null) {
    // ✅ لو وُجد متغير بيئة يحتوي على الـ JSON، استخدمه
    // if (getenv('FIREBASE_SERVICE_ACCOUNT_JSON')) {
    //     $json = getenv('FIREBASE_SERVICE_ACCOUNT_JSON');
    //     $data = json_decode($json, true);
    //     if (json_last_error() === JSON_ERROR_NONE) {
    //         return $data;
    //     } else {
    //         throw new Exception("Invalid JSON in FIREBASE_SERVICE_ACCOUNT_JSON");
    //     }
    // }

    // ❌ لو مفيش متغير، ارجع للطريقة القديمة (للتطوير المحلي بس)
    if ($path && file_exists($path)) {
        return json_decode(file_get_contents($path), true);
    }

    throw new Exception("Service account not provided via file or environment variable.");
}

function getAccessTokenFromServiceAccount(array $sa) {
    if (empty($sa['private_key']) || empty($sa['client_email'])) {
        throw new Exception('Service account JSON missing private_key or client_email.');
    }

    $privateKey = str_replace('\\n', "\n", $sa['private_key']);
    $privateKey = trim($privateKey);

    $pkey = openssl_pkey_get_private($privateKey);
    if (!$pkey) {
        throw new Exception('Failed to load private key: ' . openssl_error_string());
    }

    $now = time();
    $header = ['alg' => 'RS256', 'typ' => 'JWT'];
    $claimSet = [
        'iss' => $sa['client_email'],
        'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
        'aud' => 'https://oauth2.googleapis.com/token',
        'iat' => $now,
        'exp' => $now + 3600,
    ];

    $base64UrlEncode = fn($data) => rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    $unsignedJwt = $base64UrlEncode(json_encode($header)) . '.' . $base64UrlEncode(json_encode($claimSet));

    if (!openssl_sign($unsignedJwt, $signature, $pkey, OPENSSL_ALGO_SHA256)) {
        throw new Exception('Failed to sign JWT: ' . openssl_error_string());
    }
    openssl_pkey_free($pkey);

    $signedJwt = $unsignedJwt . '.' . $base64UrlEncode($signature);

    $tokenUrl = 'https://oauth2.googleapis.com/token';
    $postFields = http_build_query([
        'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
        'assertion' => $signedJwt
    ]);

    $ch = curl_init($tokenUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);

    $resp = curl_exec($ch);
    if ($resp === false) {
        throw new Exception('Curl error: ' . curl_error($ch));
    }
    curl_close($ch);

    $decoded = json_decode($resp, true);
    if (!isset($decoded['access_token'])) {
        throw new Exception('Failed to obtain access token: ' . $resp);
    }

    return $decoded['access_token'];
}



function sendFcmV1($topicORtoken,$title,$body,$pageID,$pageName,bool $istopic=false) {
    $url = "https://fcm.googleapis.com/v1/projects/todo-bbca0/messages:send";
 
    try {
//     $serviceAccountPath = __DIR__ . '/todo-bbca0-firebase-adminsdk-fbsvc-be1de1e3bb.json'; // ضع المسار الصحيح لملف JSON
//  $sa = getServiceAccountJson($serviceAccountPath);
//     $projectId = $sa['project_id'];

 $sa = getServiceAccountJson(__DIR__ . '/todo-bbca0-firebase-adminsdk-fbsvc-be1de1e3bb.json');
$accessToken = getAccessTokenFromServiceAccount($sa);


   
} catch (Exception $ex) {
    echo 'Error: ' . $ex->getMessage();
}

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $accessToken,
        'Content-Type: application/json; UTF-8'
    ]);
    $messageBody=[] ;
    if($istopic){
        $messageBody = [
            'topic' => $topicORtoken,
            'notification' => [
                'title' => $title,
                'body' => $body
            ],
            // data must be string values; إذا فيه JSON مُتداخل حوّله إلى string
            'data' => [
                'pageid' => $pageID,
                'pagename' => $pageName
            ],
            'android' => [
                'notification' => [
                    'click_action' => 'FLUTTER_NOTIFICATION_CLICK'
                ]
            ]
        ];
    }else{
        $messageBody = [
            'token' => $topicORtoken,
            'notification' => [
                'title' => $title,
                'body' => $body
            ],
            // data must be string values; إذا فيه JSON مُتداخل حوّله إلى string
            'data' => [
                'pageid' => $pageID,
                'pagename' => $pageName
            ],
            'android' => [
                'notification' => [
                    'click_action' => 'FLUTTER_NOTIFICATION_CLICK'
                ]
            ]
        ];
    }
    
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['message' => $messageBody]));

    $resp = curl_exec($ch);
    if ($resp === false) {
        $err = curl_error($ch);
        curl_close($ch);
        throw new Exception('Curl error while sending FCM: ' . $err);
    }
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['http_code' => $httpCode, 'response' => json_decode($resp, true)];
    // echo json_encode(['http_code' => $httpCode, 'response' => json_decode($resp, true)]);
}


