<?php   

require("sharedPhp.php");
connectToDb();
session_start();
if(isset($_SESSION['playerID'])){
  query("UPDATE playerinfo SET zone=".prepVar((constants::numZonesSrt*constants::numZonesSrt)+1)." WHERE id=".prepVar($_SESSION['playerID']));
}
session_destroy();
header("Location: login.php");
?>
