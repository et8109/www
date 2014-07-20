<?php
/**
 * used when player uploads an audio file to use as an attack sound
 */

require("sharedPhp.php");

if ($_FILES["file"]["error"] > 0) {
  echo "Error: ".$_FILES["file"]["error"];
}
else {
  echo "Upload: " . $_FILES["file"]["name"] . "<br>";
  echo "Type: " . $_FILES["file"]["type"] . "<br>";
  echo "Size: " . ($_FILES["file"]["size"] / 1024) . " kB<br>";
  echo "Stored in: " . $_FILES["file"]["tmp_name"];
}
?>

http://www.php.net/manual/en/features.file-upload.php
http://www.php.net/manual/en/features.file-upload.post-method.php
http://www.tizag.com/phpT/fileupload.php
http://www.w3schools.com/php/php_file_upload.asp
