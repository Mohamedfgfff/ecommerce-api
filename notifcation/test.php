<?php

include "../connect.php";
include "sendnotfication.php";


// getAllData('users',"user_name='moo'");
// sendFcmV1("users","test","test","","",true);
 $token =  getAccessTokenFromServiceAccount();

echo $token;
?>