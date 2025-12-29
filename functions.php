<?php


define("MB", 1048576);

function filterRequest($requestname)
{
    if (isset($_POST[$requestname])) {
        return htmlspecialchars(strip_tags($_POST[$requestname]));
    }
    if (isset($_GET[$requestname])) {
        return htmlspecialchars(strip_tags($_GET[$requestname]));
    }
    return null;
}
function filterRequestSearch($requestname)
{
    if (isset($_POST[$requestname])) {
        $value = trim($_POST[$requestname]);
        if (!mb_check_encoding($value, 'UTF-8')) {
            $value = mb_convert_encoding($value, 'UTF-8', 'auto');
        }
        // إذا كنت تريد حماية من XSS، استخدم htmlspecialchars مع تحديد charset
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
    return '';
}

// functions.php

// دالة جديدة لاستقبال نصوص JSON كما هي
function jsonrequest($requestname, $default = null)
{
    return $_POST[$requestname] ?? $default;
}

function getAllData($table, $where = null, $values = null)
{
    global $con;
    $data = array();
    if ($where == null) {
        $stmt = $con->prepare("SELECT  * FROM $table");
    } else {
        $stmt = $con->prepare("SELECT  * FROM $table WHERE   $where ");
    }
    $stmt->execute($values);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $count  = $stmt->rowCount();
    if ($count > 0) {
        echo json_encode(array("status" => "success", "data" => $data));
    } else {
        echo json_encode(array("status" => "failure"));
    }
    return $count;
}

function insertData($table, $data, $json = true)
{
    global $con;
    foreach ($data as $field => $v)
        $ins[] = ':' . $field;
    $ins = implode(',', $ins);
    $fields = implode(',', array_keys($data));
    $sql = "INSERT INTO $table ($fields) VALUES ($ins)";

    $stmt = $con->prepare($sql);
    foreach ($data as $f => $v) {
        $stmt->bindValue(':' . $f, $v);
    }
    $stmt->execute();
    $count = $stmt->rowCount();
    if ($json == true) {
        if ($count > 0) {
            echo json_encode(array("status" => "success"));
        } else {
            echo json_encode(array("status" => "failure"));
        }
    }
    return $count;
}


function updateData($table, $data, $where, $json = true)
{
    global $con;
    $cols = array();
    $vals = array();

    foreach ($data as $key => $val) {
        $vals[] = "$val";
        $cols[] = "`$key` =  ? ";
    }
    $sql = "UPDATE $table SET " . implode(', ', $cols) . " WHERE $where";

    $stmt = $con->prepare($sql);
    $stmt->execute($vals);
    $count = $stmt->rowCount();
    if ($json == true) {
        if ($count > 0) {
            echo json_encode(array("status" => "success"));
        } else {
            echo json_encode(array("status" => "failure"));
        }
    }
    return $count;
}

function deleteData($table, $where, $json = true)
{
    global $con;
    $stmt = $con->prepare("DELETE FROM $table WHERE $where");
    $stmt->execute();
    $count = $stmt->rowCount();
    if ($json == true) {
        if ($count > 0) {
            echo json_encode(array("status" => "success"));
        } else {
            echo json_encode(array("status" => "failure"));
        }
    }
    return $count;
}

function imageUpload($imageRequest)
{
    global $msgError;
    $imagename  = rand(1000, 10000) . $_FILES[$imageRequest]['name'];
    $imagetmp   = $_FILES[$imageRequest]['tmp_name'];
    $imagesize  = $_FILES[$imageRequest]['size'];
    $allowExt   = array("jpg", "png", "gif", "mp3", "pdf", "doc", "docx");
    $strToArray = explode(".", $imagename);
    $ext        = end($strToArray);
    $ext        = strtolower($ext);

    if (!empty($imagename) && !in_array($ext, $allowExt)) {
        $msgError = "EXT";
    }
    if ($imagesize > 10 * MB) { // Increased limit
        $msgError = "size";
    }

    $uploadDir = __DIR__ . "/upload/";
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    } else {
        chmod($uploadDir, 0777);
    }

    if (empty($msgError)) {
        move_uploaded_file($imagetmp,  $uploadDir . $imagename);
        return $imagename;
    } else {
        return "fail";
    }
}



function deleteFile($dir, $imagename)
{
    if (file_exists($dir . "/" . $imagename)) {
        unlink($dir . "/" . $imagename);
    }
}

function checkAuthenticate()
{
    if (isset($_SERVER['PHP_AUTH_USER'])  && isset($_SERVER['PHP_AUTH_PW'])) {
        if ($_SERVER['PHP_AUTH_USER'] != "wael" ||  $_SERVER['PHP_AUTH_PW'] != "wael12345") {
            header('WWW-Authenticate: Basic realm="My Realm"');
            header('HTTP/1.0 401 Unauthorized');
            echo 'Page Not Found';
            exit;
        }
    } else {
        exit;
    }

    // End 
}

function printjeson($masege)
{
    echo json_encode(array("status" => "fail", "message" => $masege));
}




function sendEmail($to, $title, $body)
{
    $header = "From: support@mohamed.com " . "\n" . "CC: mh6285436@gmail.com";
    mail($to, $title, $body, $header);
}

function sendEmaildepage($verifycode, $email, $username)
{
    // $apiKey = "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";
    $apiKey = $_ENV['SENDINBLUE_API_KEY'] ?? '';
    $postData = array(
        "sender" => array("name" => "Ecommerce App", "email" => "mh6285436@gmail.com"),
        "to" => array(
            array("email" => $email, "name" => $username)
        ),
        "subject" => "Verify Code Ecommerce",
        "htmlContent" => "<p>Welcome $username,</p><p>Your verification code is: <b>$verifycode</b></p>"
    );

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://api.sendinblue.com/v3/smtp/email");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        "accept: application/json",
        "content-type: application/json",
        "api-key: $apiKey"
    ));

    $response = curl_exec($ch);
    curl_close($ch);
}


// function sendGCM($title, $message, $topic, $pageid, $pagename)
// {


//     $url = 'https://fcm.googleapis.com/fcm/send';

//     $fields = array(
//         "to" => '/topics/' . $topic,
//         'priority' => 'high',
//         'content_available' => true,

//         'notification' => array(
//             "body" =>  $message,
//             "title" =>  $title,
//             "click_action" => "FLUTTER_NOTIFICATION_CLICK",
//             "sound" => "default"

//         ),
//         'data' => array(
//             "pageid" => $pageid,
//             "pagename" => $pagename
//         )

//     );


//     $fields = json_encode($fields);
//     $headers = array(
//         'Authorization: key=' . "",
//         'Content-Type: application/json'
//     );

//     $ch = curl_init();
//     curl_setopt($ch, CURLOPT_URL, $url);
//     curl_setopt($ch, CURLOPT_POST, true);
//     curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
//     curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
//     curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);

//     $result = curl_exec($ch);
//     return $result;
//     curl_close($ch);
// }