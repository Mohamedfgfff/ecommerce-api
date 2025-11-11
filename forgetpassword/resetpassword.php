<?php
include "../connect.php" ;
$email=filterRequest("user_email");
$password=sha1($_POST['user_password']);

$data=array("user_password"=>$password);
updateData("users",$data,"user_email='$email'");