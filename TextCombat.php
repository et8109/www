<?php

include 'phpHelperFunctions.php';

//set connection
$con = getConnection();
$function = $_GET['function'];
switch($function){
    
    case('getDesc'):
        if($_GET['table'] == "scenes" && $_GET['ID'] == -1){
            session_start();
            $row = query("select Name, Description from ".$_GET['table']." where ID=".prepVar($_SESSION['currentScene']));
        }
        else
            $row = query("select Name, Description from ".$_GET['table']." where ID=".prepVar($_GET['ID']));
            
        echo $row["Name"]."<>".$row["Description"];
        break;
    
    case('getPlayerDescription'):
            session_start();
            $row = query("select Description from playerinfo where ID=".prepVar($_SESSION['playerID']));
            echo $row["Description"];
        break;
    
    case('updateDescription'):
            session_start();
            $newDescription = $_GET['Description'];
            query("Update playerinfo set Description=".prepVar($newDescription)." where ID=".prepVar($_SESSION['playerID']));
            removeAlert(alerts::newItem);
        break;
   
    //echos a list of item ids and names, right now, all items
    case('getVisibleItems'):
        session_start();
        $multiQuery = "select ID, Name from items where ID=";
        //find item ids
        $result = queryMulti("select itemID from playeritems where playerID=".prepVar($_SESSION['playerID']));
        //add first itemID
        if($row = mysqli_fetch_array($result)){
            $multiQuery .= prepVar($row['itemID']);
        }
        //for each itemID found
        while($row = mysqli_fetch_array($result)){
            $multiQuery .=" or ".prepVar($row['itemID']);
        }
        mysqli_free_result($result);
        //find item names
        $result = queryMulti($multiQuery);
        $response = "";
        while($row = mysqli_fetch_array($result)){
            $response.= $row["Name"]."<>".$row["ID"]."<>";
        }
        echo $response;
        mysqli_free_result($result);
        break;
    
    case('getSceneInfo'):
        //find current scene based on id from db
        session_start();
        $row = query("select Name, Description from scenes where ID=".prepVar($_SESSION['currentScene']));
        //echo scene name and description
        echo $row['Name'] ."<>". $row['Description'];
        break;
    
    case('moveScenes'):
        session_start();
        //recieve id or name of scene, update this players location in cookie and db
        $_SESSION['currentScene'] = $_GET['newScene'];
        query("Update playerinfo set Scene=".prepVar($_GET['newScene'])." where ID=".prepVar($_SESSION['playerID']));
        break;

        /**
         *adds the item to the item list
         *adds the item's id to the player's item list
         *adds the item to the player's description.
         *adds an alert for the player
         */
    case('craftItem'):
        session_start();
        //add the item into db
        $Description = $_GET['Description'];
        $lastID = lastIDquery("insert into items (Name, Description) values (".prepVar($_GET['Name']).",".prepVar($Description).")");
        query("insert into playeritems (playerID, itemID) values (".prepVar($_SESSION['playerID']).",".prepVar($lastID).")");
        //put the item's id that you just inserted into this player's item list - removed
        //query("Update playerinfo set items=(IFNULL(items, 0)*1000) + ".prepVar($lastID)." where ID=".prepVar($_SESSION['playerID']));
        //add new item to the end of player's description
        $row = query("select Description from playerinfo where ID=".prepVar($_SESSION['playerID']));
        $playerDescription = $row['Description'];
        $playerDescription .="<span class='item' onclick='addDesc(0,".prepVar($lastID).")'>".prepVar($_GET['Name'])."</span>";
        query("Update playerinfo set Description=".prepVar($playerDescription)." where ID=".prepVar($_SESSION['playerID']));
        //add alert
        addAlert(alerts::newItem);
        break;
    
    case('getCraftInfo'):
        session_start();
        $row = query("SELECT `craftSkill` FROM `playerinfo` WHERE ID = ".prepVar($_SESSION['playerID']));
        echo $row['craftSkill'];
        break;
    
    case('getCombatInfo'):
        session_start();
        //get my info
        $row = query();
        //get enemy info
        //concatenate
        break;
    
    //gets the id of any player from the same scene. scene is indexed in mysql
    case('getPlayerIDFromScene'):
        session_start();
        $row = query("SELECT ID FROM playerinfo WHERE Scene =".prepVar($_SESSION['currentScene'])." AND Name = ".prepVar($_GET['Name']));
        echo $row['ID'];
        break;
    
    case('setUp'):
        session_start();
        //player name
        $toReturn = $_SESSION['playerName'];
        //number of items
        $numItems = 0;
        $result = queryMulti("select itemID from playeritems where playerID=".prepVar($_SESSION['playerID']));
        while($row = mysqli_fetch_array($result)){
            $numItems++;
        }
        mysqli_free_result($result);
        $toReturn .= "<>".$numItems;
        //admin level
        $row = query("select adminLevel from playerinfo where ID=".prepVar($_SESSION['playerID']));
        $toReturn .= "<>".$row['adminLevel'];
        
        //current alerts
        $result = queryMulti("select alertID from playeralerts where playerID=".prepVar($_SESSION['playerID']));
        while($row = mysqli_fetch_array($result)){
            $toReturn .= "<>".$row['alertID'];
        }
        mysqli_free_result($result);
        echo $toReturn;
        break;
}

//~~~~~~~~~~~~~~~~~~~~~~~~~~~~helper functions~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~


final class alerts{
    //the number is it's id in db
    const newItem = 100;
}
/**
 *adds an alert to the player's alert list.
 *Does not add it to their page,this list is only checked during setup
 */
function addAlert($alertNum){
    session_start();
    query("insert into playeralerts (alertID, playerID) values (".$alertNum.",".prepVar($_SESSION['playerID']).")");
}


/**
 *removes the alert from the databse
 */
function removeAlert($alertNum){
    session_start();
    query("delete from playeralerts where playerID=".prepVar($_SESSION['playerID'])." and alertID=".$alertNum);
}

?>