<?php
// update_company_logo.php

header('Content-Type: application/json'); // So we return valid JSON
session_start();

// Must be a Company
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'Company') {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized.']);
    exit();
}

require_once '../../config/db_config.php';

// The currently logged-in company's user_id
$company_id = $_SESSION['user_id'];

// Check if the file was actually uploaded
if (!isset($_FILES['logo']) || $_FILES['logo']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['error' => 'No file uploaded or upload error.']);
    exit();
}

// Validate the file type (optional, but recommended)
$allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
$fileName = $_FILES['logo']['name'];
$fileTmpPath = $_FILES['logo']['tmp_name'];
$fileSize = $_FILES['logo']['size'];
$fileType = $_FILES['logo']['type'];

$fileNameCmps = explode(".", $fileName);
$fileExtension = strtolower(end($fileNameCmps));
if (!in_array($fileExtension, $allowedExtensions)) {
    http_response_code(400);
    echo json_encode(['error' => 'Only JPG, JPEG, PNG, and GIF files are allowed.']);
    exit();
}

// Decide the upload directory
$uploadDir = '../../uploads/logos/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Create a unique filename, e.g.:  {company_id}_logo.{ext}
$newFileName = $company_id . '_logo.' . $fileExtension;
$destPath = $uploadDir . $newFileName;

// Move the file
if (!move_uploaded_file($fileTmpPath, $destPath)) {
    http_response_code(500);
    echo json_encode(['error' => 'Error moving the uploaded file.']);
    exit();
}

// Construct the path we’ll store in DB
// e.g. "../../uploads/logos/123_logo.jpg"
$logoPath = $destPath;

// Update the companies table
$updateSql = "UPDATE companies SET logo_path = ? WHERE user_id = ?";
if ($stmt = $conn->prepare($updateSql)) {
    $stmt->bind_param("si", $logoPath, $company_id);
    if ($stmt->execute()) {
        echo json_encode(['success' => 'Logo updated successfully!']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'DB update failed: ' . $stmt->error]);
    }
    $stmt->close();
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $conn->error]);
}

$conn->close();
