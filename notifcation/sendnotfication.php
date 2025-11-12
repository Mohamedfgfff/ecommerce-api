<?php
// Dependencies: openssl و curl مفعلين في PHP

function getServiceAccountJson($path = null) {
    $json = getenv('FIREBASE_SERVICE_ACCOUNT_JSON');
    
    if ($json && trim($json) !== '') {
        // بعض الأنظمة مثل Railway تحفظ JSON بسطر واحد بدون \n
        $data = json_decode($json, true);

        // جرّب تصحيح \n لو JSON فشل
        if (json_last_error() !== JSON_ERROR_NONE) {
            $jsonFixed = str_replace('\\n', "\n", $json);
            $data = json_decode($jsonFixed, true);
        }

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Invalid FIREBASE_SERVICE_ACCOUNT_JSON format");
        }

        // 👇 هنا أهم خطوة — إعادة بناء private_key بالأسطر الصحيحة
        if (isset($data['private_key'])) {
            $key = $data['private_key'];

            // إذا المفتاح كله على سطر واحد، قسمه
            if (!str_contains($key, "\n")) {
                $key = str_replace(
                    ['-----BEGIN PRIVATE KEY----- ', ' -----END PRIVATE KEY-----'],
                    ["-----BEGIN PRIVATE KEY-----\n", "\n-----END PRIVATE KEY-----"],
                    $key
                );
            }

            // لو في \n مهرب بدلها بأسطر فعلية
            $key = str_replace('\\n', "\n", $key);

            $data['private_key'] = trim($key);
        }

        return $data;
    }

    // fallback لو مفيش env
    if ($path && file_exists($path)) {
        return json_decode(file_get_contents($path), true);
    }

    throw new Exception("Service account not provided via FIREBASE_SERVICE_ACCOUNT_JSON or file.");
}


function getAccessTokenFromServiceAccount() {
    $sa = getServiceAccountJson();

    // ✅ أولاً: لو عندنا توكن محفوظ ولسه صالح نرجّعه مباشرة
    if (file_exists(__DIR__ . '/access_token.json')) {
        $tokenData = json_decode(file_get_contents(__DIR__ . '/access_token.json'), true);
        if (isset($tokenData['expires_at']) && $tokenData['expires_at'] > time()) {
            return $tokenData['access_token'];
        }
    }

    // لو مفيش توكن صالح، نعمل واحد جديد
    $now = time();
    $header = ['alg' => 'RS256', 'typ' => 'JWT'];
    $claimSet = [
        'iss' => $sa['client_email'],
        'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
        'aud' => 'https://oauth2.googleapis.com/token',
        'iat' => $now,
        'exp' => $now + 3600, // token valid 1 hour
    ];

    $base64UrlEncode = function($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    };

    $jwtHeader = $base64UrlEncode(json_encode($header));
    $jwtClaim = $base64UrlEncode(json_encode($claimSet));
    $unsignedJwt = $jwtHeader . '.' . $jwtClaim;

    // ✅ تصحيح تنسيق الـ private key
    $privateKey = $sa['private_key'];
    $privateKey = str_replace(['\\n', '\n'], "\n", $privateKey); // تحويل \n إلى سطر جديد
    $privateKey = trim($privateKey); // إزالة المسافات الزائدة

    // ✅ التأكد من وجود BEGIN/END
    if (!str_contains($privateKey, '-----BEGIN PRIVATE KEY-----')) {
        $privateKey = "-----BEGIN PRIVATE KEY-----\n" . $privateKey . "\n-----END PRIVATE KEY-----";
    }

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

    // ✅ نحفظ التوكن في ملف access_token.json
    file_put_contents(__DIR__ . '/access_token.json', json_encode([
        'access_token' => $decoded['access_token'],
        'expires_at' => time() + 3500
    ]));

    return $decoded['access_token'];
}


// function sendFcmV1($topicORtoken,$title,$body,$pageID,$pageName,bool $istopic=false) {
//     $url = "https://fcm.googleapis.com/v1/projects/todo-bbca0/messages:send";
 
//     try {
//     // $serviceAccountPath = __DIR__ . '/todo-bbca0-firebase-adminsdk-fbsvc-be1de1e3bb.json'; // ضع المسار الصحيح لملف JSON
//  $sa = getServiceAccountJson();
//     $projectId = $sa['project_id'];

//     $accessToken = getAccessTokenFromServiceAccount();

   
// } catch (Exception $ex) {
//     echo 'Error: ' . $ex->getMessage();
// }

//     $ch = curl_init($url);
//     curl_setopt($ch, CURLOPT_POST, true);
//     curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
//     curl_setopt($ch, CURLOPT_HTTPHEADER, [
//         'Authorization: Bearer ' . $accessToken,
//         'Content-Type: application/json; UTF-8'
//     ]);
//     $messageBody=[] ;
//     if($istopic){
//         $messageBody = [
//             'topic' => $topicORtoken,
//             'notification' => [
//                 'title' => $title,
//                 'body' => $body
//             ],
//             // data must be string values; إذا فيه JSON مُتداخل حوّله إلى string
//             'data' => [
//                 'pageid' => $pageID,
//                 'pagename' => $pageName
//             ],
//             'android' => [
//                 'notification' => [
//                     'click_action' => 'FLUTTER_NOTIFICATION_CLICK'
//                 ]
//             ]
//         ];
//     }else{
//         $messageBody = [
//             'token' => $topicORtoken,
//             'notification' => [
//                 'title' => $title,
//                 'body' => $body
//             ],
//             // data must be string values; إذا فيه JSON مُتداخل حوّله إلى string
//             'data' => [
//                 'pageid' => $pageID,
//                 'pagename' => $pageName
//             ],
//             'android' => [
//                 'notification' => [
//                     'click_action' => 'FLUTTER_NOTIFICATION_CLICK'
//                 ]
//             ]
//         ];
//     }
    
//     curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['message' => $messageBody]));

//     $resp = curl_exec($ch);
//     if ($resp === false) {
//         $err = curl_error($ch);
//         curl_close($ch);
//         throw new Exception('Curl error while sending FCM: ' . $err);
//     }
//     $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
//     curl_close($ch);
//     return ['http_code' => $httpCode, 'response' => json_decode($resp, true)];
//     // echo json_encode(['http_code' => $httpCode, 'response' => json_decode($resp, true)]);
// }


