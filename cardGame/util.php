<?php
function sendJSON($array){
    echo json_encode($array);
}

function redirectIfLoggedOut() {
  if(!isset($_SESSION['uname'])){
    header("Location: login.php");
  }
}

function addError($arr,$msg){
    $arr[] = (array(
    "error" => true,
    "msg" => $msg
    ));
}
?>