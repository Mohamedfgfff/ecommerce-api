<?php


include "../connect.php";
$username = filterrequest("user_name");
$email = filterrequest("user_email");
$phone = filterrequest("user_phone");
$password = sha1($_POST['user_password']);
$verifycode = rand(10000 , 90000);

$stmt = $con->prepare("SELECT * FROM `users` WHERE user_email = ? OR user_phone = ?");
$stmt->execute(array($email, $phone));
$count = $stmt->rowCount();

if ($count > 0) {
    printjeson("Email or Phone already exists");
} else {
    $data = array(
        "user_name"        => $username,
        "user_email"       => $email,
        "user_phone"       => $phone,
        "user_password"    => $password,
        "user_verifycode"  => $verifycode,
    );

    insertData("users", $data);
   // sendEmail($email,"verify Code Ecommerce","verify Code $verifycode");


   sendEmaildepage($verifycode,$email,$username);

   
}


// include "../connect.php";
// $username = filterrequest("user_name");
// $email = filterrequest("user_email");
// $phone = filterrequest("user_phone");
// $password = filterrequest("user_password");
// $verifycode = rand(10000 , 90000);


// $stmt = $con->prepare("SELECT * FROM users WHERE user_email = ? OR user_phone = ?");
// $stmt->execute(array($email, $phone));
// $count = $stmt->rowCount();

// if ($count > 0) {
//     printjeson("Email or Phone already exists");
// } else {

//     $data=array(

//         "user_name"=>$username,
//         "user_email"=>$email,
//         "user_phone"=>$phone,
//         "user_password"=>$password,
//         "user_verifycode"=>$verifycode,
//     );
//     // sendEmail($email,"verify Code Ecommerce","verify Code $verifycode");

//     insertData("users",$data);

   

