<?php
include "../connect.php";

$userid = filterRequest("usersid"); // Optional: if we want to check favs later

// Simple view all
getAllData("local_services", "1 = 1");
