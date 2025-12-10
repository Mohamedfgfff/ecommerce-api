<?php
include "connect.php";

$msgError = array();
$fileRequest = "file"; // Default input name

if (isset($_FILES[$fileRequest])) {
    $filename = $_FILES[$fileRequest]['name'];
    $filetmp = $_FILES[$fileRequest]['tmp_name'];
    $filesize = $_FILES[$fileRequest]['size'];
    $filetype = $_FILES[$fileRequest]['type'];

    // Allow extensions
    $allowExt = array("jpg", "png", "gif", "mp3", "pdf", "doc", "docx");

    $strToArray = explode(".", $filename);
    $ext = end($strToArray);
    $ext = strtolower($ext);

    // Generate new name to prevent overwriting
    $newFilename = uniqid() . "." . $ext;

    $uploadDir = __DIR__ . "/upload/";
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    } else {
        chmod($uploadDir, 0777);
    }

    $targetPath = $uploadDir . $newFilename;

    if (!empty($filename) && !in_array($ext, $allowExt)) {
        echo json_encode(array("status" => "fail", "message" => "Extension not allowed"));
        exit;
    }

    if ($filesize > 10 * 1024 * 1024) { // 10MB limit (adjust as needed)
        echo json_encode(array("status" => "fail", "message" => "File size too large"));
        exit;
    }

    if (move_uploaded_file($filetmp, $targetPath)) {
        // Build URL
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
        // Handle load balancers/proxies (like Railway often uses)
        if (isset($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
            $protocol = $_SERVER['HTTP_X_FORWARDED_PROTO'];
        }

        $host = $_SERVER['HTTP_HOST'];

        // Ensure we handle subdirectories if the script isn't at root, 
        // but current setup seems to be root based on previous context.
        // We will assume root for simplicity or try to detect script dir.
        // Given existing code, $uploadDir is relative "upload/", so we append that.

        $fullUrl = "$protocol://$host/upload/" . $newFilename;

        echo json_encode(array(
            "status" => "success",
            "image" => $newFilename,
            "url" => $fullUrl
        ));
    } else {
        echo json_encode(array("status" => "fail", "message" => "Upload failed"));
    }
} else {
    echo json_encode(array("status" => "fail", "message" => "No file sent"));
}
