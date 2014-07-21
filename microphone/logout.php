<?php   

require sharedPhp.php

if(isset($_SESSION['playerID'])){
  query("UPDATE playerinfo SET zone=".prepVar((constants::numZonesSrt*constants::numZonesSrt)+1)." WHERE id=".prepVar($_SESSION['playerID']));
}
session_destroy();
sendJSON(array(
  "success" => true
));
header("Location: login.php");
?>
