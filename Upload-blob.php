<form action="upload.php" method="post" enctype="multipart/form-data">
    <input type="file" name="myFile">
    <input type="submit" value="Upload File">
</form>

async function imgDataURLToBlob(imgElement) {
  const dataURL = imgElement.src;

  // 1. Extract the Base64 data and MIME type
  const parts = dataURL.split(',');
  const mimeType = parts[0].match(/:(.*?);/)[1];
  const base64Data = parts[1];

  // 2. Decode the Base64 string
  const byteCharacters = atob(base64Data);

  // 3. Convert to a Uint8Array
  const byteNumbers = new Array(byteCharacters.length);
  for (let i = 0; i < byteCharacters.length; i++) {
    byteNumbers[i] = byteCharacters.charCodeAt(i);
  }
  const byteArray = new Uint8Array(byteNumbers);

  // 4. Create a Blob
  const blob = new Blob([byteArray], { type: mimeType });
  return blob;
}

const myImage = document.getElementById('myImage');
imgDataURLToBlob(myImage)
  .then(blob => {
    console.log('Blob created:', blob);
  })
  .catch(error => {
    console.error('Error creating blob:', error);
  });

function sendData(){
    var data = new FormData();
    data.append('image', blob, 'image.jpg');
}
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
