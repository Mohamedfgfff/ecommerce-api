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

    $uploadDir = "upload/";
    // If we are in root, upload/ is correct. 
    // If included from elsewhere, we might need absolute path. 
    // Ideally use __DIR__ . '/upload/'
    $targetPath = __DIR__ . "/upload/" . $newFilename;

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
        // Assuming typical setup: http://server/ecommerce/upload/filename
        // We can't easily auto-detect full URL accurately without config, but we can return relative path or try.
        // User asked "returns link to image". I will return the filename and the relative path.
        echo json_encode(array(
            "status" => "success",
            "image" => $newFilename,
            "url" => "upload/" . $newFilename
        ));
    } else {
        echo json_encode(array("status" => "fail", "message" => "Upload failed"));
    }
} else {
    echo json_encode(array("status" => "fail", "message" => "No file sent"));
}
