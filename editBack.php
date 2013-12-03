<?php

session_start();
include 'phpHelperFunctions.php';
$con = getConnection();
$function = $_GET['function'];
switch($function){
    
    case('getInfo'):
        $table = getTable($_GET['type']);
        if($table == null){
            return "error<>getInfoerror";
        }
        $row = query("select Name, Description from ".$table." where ID=".prepVar($_GET['ID']));
        echo $row['Name'] ."<>". $row['Description'];
        break;
    
    case('save'):
        $table = getTable($_GET['type']);
        if($table == null){
            return "error<>saveerror";
        }
        $newDescription = $_GET['Description'];
        query("update ".$table." set Name=".prepVar($_GET['Name']).",Description=".prepVar($newDescription)." where ID=".prepVar($_GET['ID']));
        break;
    
    case('saveNew'):
        $table = getTable($_GET['type']);
        if($table == null){
            return "error<>saveNewerror";
        }
        $newDescription = $_GET['Description'];
        query("insert into ".$table." (Name, Description) values (".prepVar($_GET['Name']) .", ". prepVar($newDescription).")");
        break;
}

/**
 *returns the table where the item type is
 */
function getTable($type){
    switch($type){
        case("Scene"):
            return 'scenes';
            break;
        case("Item"):
            return 'items';
            break;
        case("Player"):
            return 'playerInfo';
            break;
    }
    return null;
}
?>