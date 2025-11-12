<?php
// Dependencies: openssl و curl مفعلين في PHP

function getServiceAccountJson($path = null) {
    // ✅ لو وُجد متغير بيئة يحتوي على الـ JSON، استخدمه
    if (getenv('FIREBASE_SERVICE_ACCOUNT_JSON')) {
        $json = getenv('FIREBASE_SERVICE_ACCOUNT_JSON');
        $data = json_decode($json, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $data;
        } else {
            throw new Exception("Invalid JSON in FIREBASE_SERVICE_ACCOUNT_JSON");
        }
    }

    // ❌ لو مفيش متغير، ارجع للطريقة القديمة (للتطوير المحلي بس)
    if ($path && file_exists($path)) {
        return json_decode(file_get_contents($path), true);
    }

    throw new Exception("Service account not provided via file or environment variable.");
}

function getAccessTokenFromServiceAccount() {
   

    // ✅ لو موجود توكن صالح في متغير البيئة نرجعه مباشرة
    $envToken = getenv('FCM_ACCESS_TOKEN');
    $envExpire = getenv('FCM_ACCESS_TOKEN_EXPIRES');

    if ($envToken && $envExpire && intval($envExpire) > time()) {
        return $envToken;
    }
     $sa = getServiceAccountJson();

    // إنشاء توكن جديد
    $now = time();
    $header = ['alg' => 'RS256', 'typ' => 'JWT'];
    $claimSet = [
        'iss' => $sa['client_email'],
        'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
        'aud' => 'https://oauth2.googleapis.com/token',
        'iat' => $now,
        'exp' => $now + 3600,
    ];

    $base64UrlEncode = function($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    };

    $jwtHeader = $base64UrlEncode(json_encode($header));
    $jwtClaim = $base64UrlEncode(json_encode($claimSet));
    $unsignedJwt = $jwtHeader . '.' . $jwtClaim;

    $privateKey = $sa['private_key'];
    $signature = '';
    if (!openssl_sign($unsignedJwt, $signature, $privateKey, OPENSSL_ALGO_SHA256)) {
        throw new Exception('Failed to sign JWT');
    }
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
        $err = curl_error($ch);
        curl_close($ch);
        throw new Exception('Curl error while obtaining access token: ' . $err);
    }
    curl_close($ch);

    $decoded = json_decode($resp, true);
    if (!isset($decoded['access_token'])) {
        throw new Exception('Failed to obtain access token: ' . $resp);
    }

    // ✅ خزّن التوكن وتاريخ الانتهاء في متغيرات بيئة
    putenv('FCM_ACCESS_TOKEN=' . $decoded['access_token']);
    putenv('FCM_ACCESS_TOKEN_EXPIRES=' . (time() + 3500));

    return $decoded['access_token'];
}



function sendFcmV1($topicORtoken,$title,$body,$pageID,$pageName,bool $istopic=false) {
    $url = "https://fcm.googleapis.com/v1/projects/todo-bbca0/messages:send";
 
    try {


    $accessToken = getAccessTokenFromServiceAccount();

   
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


