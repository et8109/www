<?php

include 'phpHelperFunctions.php';
$con = getConnection();
$function = $_GET['function'];
switch($function){
    case('getCurrentScene'):
        session_start();
        $row = query("select Scene from playerinfo where ID=".prepVar($_SESSION['playerID']));
        echo $row['Scene'];
        /*session_start();
        echo $_SESSION['currentScene'];*/
        break;
}
?>