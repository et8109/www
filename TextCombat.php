<?php

session_start();
include 'phpHelperFunctions.php';

//set connection
$con = getConnection();
$function = $_GET['function'];
switch($function){
    
    case('getDesc'):
        switch($_GET['type']){
            case(spanTypes::ITEM):
                $row = query("select Name, Description from items where ID=".prepVar($_GET['ID']));
                echo getSpanText(spanTypes::ITEM,$_GET['ID'],$row["Name"])."<>".$row["Description"];
                break;
            case(spanTypes::KEYWORD):
                $row2 = query("select ID from keywordwords where Word=".prepVar($_GET['ID']));
                $row = query("select Description from keywords where ID=".$row2['ID']);
                echo getSpanText(spanTypes::KEYWORD,$_GET['ID'],$_GET['ID'])."<>".$row["Description"];
                break;
            case(spanTypes::PLAYER):
                $row = query("select Name, Description from playerinfo where ID=".prepVar($_GET['ID']));
                echo getSpanText(spanTypes::PLAYER,$_GET['ID'],$row["Name"])."<>".$row["Description"];
                break;
            case(spanTypes::SCENE):
                $row = query("select Name, Description from scenes where ID=".prepVar($_GET['ID']));
                echo getSpanText(spanTypes::SCENE,$_GET['ID'],$row["Name"])."<>".$row["Description"];
                break;
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
                        $newDescription = str_replace($row2['Name'], getSpanText(spanTypes::ITEM,$row2['ID'],$row2['Name']), $newDescription);
                    }
                }
                mysqli_free_result($result);
            }
            query("Update playerinfo set Description=".prepVar($newDescription)." where ID=".prepVar($_SESSION['playerID']));
            removeAlert(alertTypes::newItem);
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
                $descArray[$i] = getSpanText(spanTypes::KEYWORD,$word,$word);
                $requiredKeywordTypes[$row['Type']] = true;
            }
        }
        //make sure all required keyword types were replaced
        $rktlength = count($requiredKeywordTypes);
        for($i=0; $i<3; $i++){ //the amount of types, or something, as the max for i
            if(isset($requiredKeywordTypes[$i]) && $requiredKeywordTypes[$i] == false){
                echo "type ".$i." keyword was not found";
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
        $playerDescription .= getSpanText(spanTypes::ITEM,$lastID,$_GET['Name']);
        query("Update playerinfo set Description=".prepVar($playerDescription)." where ID=".prepVar($_SESSION['playerID']));
        //add alert
        addAlert(alertTypes::newItem);
        break;
    
    case('getCraftInfo'):
        $row = query("SELECT `craftSkill` FROM `playerinfo` WHERE ID = ".prepVar($_SESSION['playerID']));
        echo $row['craftSkill'];
        break;
    
    case('putItemIn'):
        session_start();
        //make sure items exist
        $twoNamesQuery = "";
        $result = queryMulti("select itemID from playeritems where playerID = ".prepVar($_SESSION['playerID']));
            if(!is_bool($result)){
                $row = mysqli_fetch_array($result);
                $twoNamesQuery = "select Name from items where ID=".$row['itemID'];
                while($row = mysqli_fetch_array($result)){
                    $twoNamesQuery .= "or ".$row['itemID'];
                }
                $twoNamesQuery .= " and Name=".prepVar($_GET['itemName'])." or ".prepVar($_GET['containerName']);
                mysqli_free_result($result);
            }
            else{
                echo "you don't have any items.";
                mysqli_free_result($result);
                return;
            }
            $result = queryMulti($twoNamesQuery);
            //result should be a length of 2
            
        //make sure second item is a container
        //make sure first item can be put into the second
        //make sure second item is not full
        //put in
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
    
    case('setFrontLoadAlerts'):
        query("update playerinfo set frontLoadAlerts=".$_GET['load']." where ID=".prepVar($_SESSION['playerID']));
        break;
    
    case('setFrontLoadScenes'):
        query("update playerinfo set frontLoadScenes=".$_GET['load']." where ID=".prepVar($_SESSION['playerID']));
        break;
    
    case('setFrontLoadKeywords'):
        query("update playerinfo set frontLoadKeywords=".$_GET['load']." where ID=".prepVar($_SESSION['playerID']));
        break;
    
    case('getAlertMessages'):
        //get all alert ids
        $result = queryMulti("select alertID from playeralerts where playerID=".prepVar($_SESSION['playerID']));
        while($row = mysqli_fetch_array($result)){
            //get alert message and append
            $row2 = query("select Description from alerts where ID=".prepVar($row['alertID']));
            echo "</br>".$row2['Description'];
        }
        mysqli_free_result($result);
        break;
    
    case('setUp'):
        $toReturn = "";
        $row = query("select adminLevel,Scene,frontLoadAlerts,frontLoadScenes,frontLoadKeywords from playerinfo where ID=".prepVar($_SESSION['playerID']));
        echo "<>".$row['adminLevel'];
        echo "<>".$row['Scene'];
        echo "<>".$row['frontLoadAlerts'];
        echo "<>".$row['frontLoadScenes'];
        echo "<>".$row['frontLoadKeywords'];
        return;
    
    case('frontLoadAlerts'):
        //send alert types
        $result = queryMulti("select ID, Description from alerts");
        while($row = mysqli_fetch_array($result)){
            echo "<>".$row['ID']."<>".$row['Description'];
        }
        mysqli_free_result($result);
        echo "<<>>";
        //send player alerts
        $result = queryMulti("select alertID from playeralerts where playerID=".prepVar($_SESSION['playerID']));
        while($row = mysqli_fetch_array($result)){
            echo "<>".$row['alertID'];
        }
        mysqli_free_result($result);
        break;
    
    case('frontLoadScenes'):
        $result = queryMulti("select ID, Name, Description from scenes");
        while($row = mysqli_fetch_array($result)){
            echo "<>".$row['ID']."<>".getSpanText(spanTypes::SCENE,$row['ID'],$row['Name'])."<>".$row['Description'];
        }
        mysqli_free_result($result);
        break;
    
    case('frontLoadKeywords'):
        //keyword words to description. //type not needed
        $result = queryMulti("select Word, ID from keywordwords");
        while($row = mysqli_fetch_array($result)){
            $row2 = query("select Description from keywords where ID=".$row['ID']);
            echo "<>".$row['Word']."<>".getSpanText(spanTypes::KEYWORD,$row['ID'],$row['Word'])."<>".$row2['Description'];
        }
        mysqli_free_result($result);
        break;
}

//~~~~~~~~~~~~~~~~~~~~~~~~~~~~alert functions~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

/**
 *types of alerts that can show up in the alert box
 */
final class alertTypes{
    //the number is it's id in db
    const newItem = 1;
}

/**
 *the possible actions that are visible in chat.
 *duplicated in js
 */
final class actionTypes {
    const WALKING = 0;
    const ATTACK = 1;
}

/**
 *The types of spans that you can click for a description
 */
final class spanTypes {
    const ITEM = 0;
    const PLAYER = 1;
    const SCENE = 2;
    const KEYWORD = 3;
}

?>