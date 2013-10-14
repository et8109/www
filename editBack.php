<?php

include 'phpHelperFunctions.php';

$function = $_GET['function'];
switch($function){
    
    case('getInfo'):
        printDebug($_GET['table']);
        $row = query("select Name, Description from ".$_GET['table']." where ID=".prepVar($_GET['ID']));
        //echo scene name and description
        /*$row = mysqli_fetch_array($result);
        mysqli_free_result($result);*/
        echo $row['Name'] ."<>". $row['Description'];
        break;
    
    case('save'):
        $newDescription = $_GET['Description'];
        //$newDescription = str_replace(??,??);
        query("update ".$_GET['table']." set Name=".prepVar($_GET['Name']).",Description=".prepVar($newDescription)." where ID=".prepVar($_GET['ID']));
        break;
    
    case('saveNew'):
        $newDescription = $_GET['Description'];
        //$newDescription = str_replace(??,??);
            query("insert into ".$_GET['table']." (Name, Description) values (".prepVar($_GET['Name']) .", ". prepVar($newDescription).")");
        break;
}
?>