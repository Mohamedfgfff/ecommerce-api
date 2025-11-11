<?php
include "../connect.php";


$email = filterRequest("email");
$verifycode = filterRequest("verifycode"); 

$stmt = $con->prepare("SELECT * FROM users WHERE user_email = ? AND user_verifycode = ?");

$stmt->execute(array($email, $verifycode)); 

$count = $stmt->rowCount();

if ($count > 0) {
    $data = array("user_approve" => "1");
    
    updateData("users", $data, "user_email ='$email'");
    
    // echo json_encode(array("status" => "success"));

} else {
    echo json_encode(array("status" => "failure", "message" => "Incorrect verification code."));
}

?>