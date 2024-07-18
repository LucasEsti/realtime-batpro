<?php
// upload.php
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $uploadDir = __DIR__ . '/wp-content/uploads/';
    $fileName = basename($_FILES['file']['name']);
    $uploadFile = $uploadDir . $fileName;

    if (move_uploaded_file($_FILES['file']['tmp_name'], $uploadFile)) {
        $response = [
            'status' => 'success',
            'filePath' => '/wp-content/uploads/' . $fileName,
            'fileName' => $fileName
        ];
    } else {
        $response = ['status' => 'error', 'message' => 'File upload failed.'];
    }

    echo json_encode($response);
}
