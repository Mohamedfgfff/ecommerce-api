<?php
include_once "../connect.php";
$email=filterRequest("user_email");
$verifycode = rand(10000 , 90000);

 sendEmail($email,"verify Code Ecommerce","verify Code $verifycode");
sendEmaildepage($verifycode,$email,$email);

$data=array("user_verifycode"=>$verifycode);
updateData("users",$data,"user_email='$email'");

?>

