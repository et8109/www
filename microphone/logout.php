<?php

require_once("shared.php");

if(isset($_SESSION['playerID'])){
    query("UPDATE playerinfo SET zone=0 WHERE id=".prepVar($_SESSION['playerID']));
}
session_destroy();
echo '<a href="login.php">Back to login</a>'
?>