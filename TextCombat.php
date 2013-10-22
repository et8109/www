<?php

session_start();
include 'phpHelperFunctions.php';

//set connection
$con = getConnection();
$function = $_GET['function'];
switch($function){
    
    case('getDesc'):
        if($_GET['table'] == "scenes" && $_GET['ID'] == -1){
            $row = query("select Name, Description from ".$_GET['table']." where ID=".prepVar($_SESSION['currentScene']));
            echo $row["Name"]."<>".$row["Description"];
        }
        else if($_GET['table'] == "keywordwords"){
            $row2 = query("select ID from ".$_GET['table']." where Word=".prepVar($_GET['ID']));
            $row = query("select Description from keywords where ID=".$row2['ID']);
            echo $_GET['ID']."<>".$row["Description"];
        }
        else{
            $row = query("select Name, Description from ".$_GET['table']." where ID=".prepVar($_GET['ID']));
            echo $row["Name"]."<>".$row["Description"];
        }
        break;
    
    case('getPlayerDescription'):
            $row = query("select Description from playerinfo where ID=".prepVar($_SESSION['playerID']));
            echo $row["Description"];
        break;
    
    case('updateDescription'):
        $newDescription = $_GET['Description'];
            $multiQuery = "select ID, Name from items where ID=";
            //find item ids
            $result = queryMulti("select itemID from playeritems where playerID=".prepVar($_SESSION['playerID']));
            //add first itemID
            if($row = mysqli_fetch_array($result)){
                $multiQuery .= prepVar($row['itemID']);
            }
            //for each itemID found
            while($row = mysqli_fetch_array($result)){
                $multiQuery .=" or ID=".prepVar($row['itemID']);
            }
            mysqli_free_result($result);
            //find item names
            $result = queryMulti($multiQuery);
            if(!is_bool($result)){
                while($row2 = mysqli_fetch_array($result)){
                    //if an item is not found
                    if(strpos($newDescription, $row2['Name']) == false){
                        echo "Please use all your visible items in your description.".$row2['Name']."was not found.";
                        mysqli_free_result($result);
                        return;
                    }
                    //the item was found
                    else{
                        $newDescription = str_replace($row2['Name'], "<span class='item' onclick='addDesc(0,".$row2['ID'].")'>".$row2['Name']."</span>", $newDescription);
                    }
                }
                mysqli_free_result($result);
            }
            query("Update playerinfo set Description=".prepVar($newDescription)." where ID=".prepVar($_SESSION['playerID']));
            removeAlert(alerts::newItem);
        break;
    
    case('getSceneInfo'):
        //find current scene based on id from db
        $row = query("select Name, Description from scenes where ID=".prepVar($_SESSION['currentScene']));
        //echo scene name and description
        echo $row['Name'] ."<>". $row['Description'];
        break;
    
    case('moveScenes'):
        //recieve id or name of scene, update this players location in cookie and db
        $_SESSION['currentScene'] = $_GET['newScene'];
        query("Update playerinfo set Scene=".prepVar($_GET['newScene'])." where ID=".prepVar($_SESSION['playerID']));
        $row = query("select Name from scenes where ID=".$_GET['newScene']);
        speakAction(actionTypes::WALKING, $row['Name'], $_GET['newScene']);
        updateChatTime();
        break;

        /**
         *adds the item to the item list
         *adds the item's id to the player's item list
         *adds the item to the player's description.
         *adds an alert for the player
         */
    case('craftItem'):
        $requiredKeywordTypes = array();
        //1:material, 2:quality
        $requiredKeywordTypes[1] = false;
        $requiredKeywordTypes[2] = false;
        //replace all keywords
        $descArray = explode(" ",$_GET['Description']);
        $descArrayLength = count($descArray);
        //for each word in the desc, find a keyword to match it
        for($i=0; $i<$descArrayLength; $i++){
            $word = $descArray[$i];
            $row = query("select ID,Type from keywordwords where Word=".prepVar($word));
            //if there is a keyword
            if(isset($row['ID'])){
                printDebug($word);
                $descArray[$i] = "<span class='keyword' onclick='addDesc(3,".$word.")'>".prepVar($word)."</span>";
                $requiredKeywordTypes[$row['Type']] = true;
            }
        }
        //make sure all required keyword types were replaced
        $rktlength = count($requiredKeywordTypes);
        printDebug($rktlength);
        for($i=0; $i<3; $i++){ //the amount of types, or something, as the max for i
            if(isset($requiredKeywordTypes[$i]) && $requiredKeywordTypes[$i] == false){
                echo "keyword of type ".$i." was not found";
                return;
            }
        }
        //add the item into db
        $Description = implode(" ",$descArray);
        $lastID = lastIDquery("insert into items (Name, Description) values (".prepVar($_GET['Name']).",".prepVar($Description).")");
        query("insert into playeritems (playerID, itemID) values (".prepVar($_SESSION['playerID']).",".prepVar($lastID).")");
        //add new item to the end of player's description
        $row = query("select Description from playerinfo where ID=".prepVar($_SESSION['playerID']));
        $playerDescription = $row['Description'];
        $playerDescription .="<span class='item' onclick='addDesc(0,".prepVar($lastID).")'>".prepVar($_GET['Name'])."</span>";
        query("Update playerinfo set Description=".prepVar($playerDescription)." where ID=".prepVar($_SESSION['playerID']));
        //add alert
        addAlert(alerts::newItem);
        break;
    
    case('getCraftInfo'):
        $row = query("SELECT `craftSkill` FROM `playerinfo` WHERE ID = ".prepVar($_SESSION['playerID']));
        echo $row['craftSkill'];
        break;
    
    case('attack'):
        //see if player is there
        $row = query("SELECT ID FROM playerinfo WHERE Scene =".prepVar($_SESSION['currentScene'])." AND Name = ".prepVar($_GET['Name']));
        if($row['ID']){
            speakAction(actionTypes::ATTACK, $_GET['Name'], $row['ID']);
            //no need to echo, it's in chat
        }
        else{
            echo $_GET['Name']." is not nearby..";
        }
        break;
    
    //gets the id of any player from the same scene. scene is indexed in mysql
    case('getPlayerIDFromScene'):
        $row = query("SELECT ID FROM playerinfo WHERE Scene =".prepVar($_SESSION['currentScene'])." AND Name = ".prepVar($_GET['Name']));
        echo $row['ID'];
        break;
    
    case('setUp'):
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

//~~~~~~~~~~~~~~~~~~~~~~~~~~~~alert functions~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~


final class alerts{
    //the number is it's id in db
    const newItem = 100;
}
/**
 *adds an alert to the player's alert list.
 *Does not add it to their page,this list is only checked during setup
 */
function addAlert($alertNum){
    query("insert into playeralerts (alertID, playerID) values (".$alertNum.",".prepVar($_SESSION['playerID']).")");
}


/**
 *removes the alert from the databse
 */
function removeAlert($alertNum){
    query("delete from playeralerts where playerID=".prepVar($_SESSION['playerID'])." and alertID=".$alertNum);
}

?>