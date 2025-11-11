<?php
include "../connect.php";

$email = filterrequest("user_email");
$password = sha1($_POST['user_password']);

$stem = $con->prepare("SELECT * FROM `users` WHERE user_email=? AND `user_password`=?");
$stem->execute(array($email, $password));
$user = $stem->fetch(PDO::FETCH_ASSOC);

$count = $stem->rowCount();

if ($count > 0) {
    if ($user['user_approve'] == 0) {
        echo json_encode(array("status" => "not_approve" , "data" => $user));
    } else {
        echo json_encode(array("status" => "success", "data" => $user));
    }
} else {
    echo json_encode(array("status" => "fail"));
}
