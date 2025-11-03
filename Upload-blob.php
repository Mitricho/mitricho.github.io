<form action="upload.php" method="post" enctype="multipart/form-data">
    <input type="file" name="myFile">
    <input type="submit" value="Upload File">
</form>

<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['myFile'])) {
    $file = $_FILES['myFile'];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        echo "Error uploading file: " . $file['error'];
        exit;
    }
    $tmpFilePath = $file['tmp_name'];
    $blobData = file_get_contents($tmpFilePath);
    echo "File uploaded and processed successfully.";
} else {
    echo "No file uploaded or invalid request method.";
}
?>
