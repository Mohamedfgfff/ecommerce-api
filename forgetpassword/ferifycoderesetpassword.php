<?php
include "../connect.php";


$email = filterRequest("user_email");
$verifycode = filterRequest("user_verifycode"); 

$stmt = $con->prepare("SELECT * FROM users WHERE user_email = ? AND user_verifycode = ?");

$stmt->execute(array($email, $verifycode)); 

$count = $stmt->rowCount();

if ($count > 0) {
    echo json_encode(array("status" => "success"));

} else {
    echo json_encode(array("status" => "failure", "message" => "Incorrect verification code."));
}

?>