<?php
include "../connect.php";

$email = filterrequest("user_email");
$verifycode = rand(10000 , 90000);

$stem = $con->prepare("SELECT * FROM `users` WHERE user_email=?");
$stem->execute(array($email));
$user = $stem->fetch(PDO::FETCH_ASSOC);

$count = $stem->rowCount();

if ($count > 0) {
    $data=array("user_verifycode"=>$verifycode);

    updateData("users",$data,"user_email='$email'");
  sendEmail($email,"verify Code Ecommerce","verify Code $verifycode");
   sendEmaildepage($verifycode,$email,$email);
  
} else {
    echo json_encode(array("status" => "fail"));
}
