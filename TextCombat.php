<?php

session_start();
include 'phpHelperFunctions.php';
include 'constants.php';
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
                //managing the scene
                $playerKeywordRow = query("select locationID,keywordID from playerkeywords where ID=".prepVar($_SESSION['playerID'])." and type=".keywordTypes::PLAYER_JOB);
                $sceneKeywordRow = query("select keywordID from scenekeywords where type=".keywordTypes::SCENE_ACTION);
                //scene keyword matches plyer job keyword && correct location
                if($GLOBALS['sceneKeywordToPlayerJob'][$sceneKeywordRow['keywordID']] == $playerKeywordRow['keywordID'] &&
                   $playerKeywordRow['locationID'] == $_GET['ID']){
                    echo " ".getSpanTextManagingScene($_GET['ID']);
                }
                break;
        }
        break;
    
    //when player looks at thier own desc
    case('getPlayerDescription'):
            $row = query("select Description from playerinfo where ID=".prepVar($_SESSION['playerID']));
            echo $row["Description"];
        break;
    
    case('updateDescription'):
         if(updateDescription($_SESSION['playerID'], $_GET['Description'], spanTypes::PLAYER){
            //if success
            removeAlert(alertTypes::newItem);
         }
         else{
            //if fail
            echo "could not update";
         }
        break;
    
    case('moveScenes'):
        //remove player from last scene list
        query("delete from sceneplayers where sceneID=".prepVar($_SESSION['currentScene'])." and playerID=".prepVar($_SESSION['playerID']));
        //recieve id or name of scene, update this players location in cookie and db
        $_SESSION['currentScene'] = $_GET['newScene'];
        query("Update playerinfo set Scene=".prepVar($_GET['newScene'])." where ID=".prepVar($_SESSION['playerID']));
        $row = query("select Name from scenes where ID=".$_GET['newScene']);
        speakAction(actionTypes::WALKING, $row['Name'], $_GET['newScene']);
        updateChatTime();
        //add player to new scene list
        query("insert into sceneplayers (sceneID,playerID,playerName) values(".prepVar($_SESSION['currentScene']).",".prepVar($_SESSION['playerID']).",".prepVar($_SESSION['playerName']).")");
        break;

        /**
         *adds the item to the item list
         *adds the item's id to the player's item list
         *adds the item to the player's description.
         *adds an alert for the player
         */
    case('craftItem'):
        //make sure all required keyword types were replaced
        $desc = $_GET['Description'];
        foreach(requiredItemKeywordTypes as $type){
            if(!replaceKeywordType($desc, $type)){
                echo "type ".$type." keyword was not found";
                return;
            }
        }
        //add the item into db
        $lastID = lastIDquery("insert into items (playerID, Name, Description) values (".prepVar($_SESSION['playerID']).",".prepVar($_GET['Name']).",".prepVar($desc).")");
        //add new item to the end of player's description
        $row = query("select Description from playerinfo where ID=".prepVar($_SESSION['playerID']));
        $playerDescription = $row['Description'];
        $playerDescription .= getSpanText(spanTypes::ITEM,$lastID,$_GET['Name']);
        query("Update playerinfo set Description=".prepVar($playerDescription)." where ID=".prepVar($_SESSION['playerID']));
        //give the item a size
        query("Update items set size=4 where ID=".$lastID);
        //if item is a container, give it room
        if(isset($keywordTypes[0]) && $keywordTypes[0][0] == true){
            query("Update items set room=9 where ID=".$lastID);
        }
        //add the item to itemKeywords with it's keywords
        foreach ($keywordTypes as $type){
            query("insert into itemKeywords (itemID, keywordID, type) values (".$lastID.",".$keywordTypes[$type][1].",".$type.")");
        }
        //add alert
        addAlert(alertTypes::newItem);
        break;
    
    case('getCraftInfo'):
        $row = query("SELECT `craftSkill` FROM `playerinfo` WHERE ID = ".prepVar($_SESSION['playerID']));
        echo $row['craftSkill'];
        break;
    
    case('putItemIn'):
        $itemName = prepVar($_GET['itemName']);
        $containerName = prepVar($_GET['containerName']);
        //get item and container info
        $itemRow = query("select size,ID,insideOf from items where playerID=".prepVar($_SESSION['playerID'])." and Name=".$itemName);
        $containerRow = query("select room,ID from items where playerID=".prepVar($_SESSION['playerID'])." and Name=".$containerName);
        //make sure item was found
        if(!isset($itemRow['ID'])){
            echo "the ".$itemName." was not found";
            return;
        }
        //make sure container was found
        if(!isset($containerRow['ID'])){
            echo "the ".$containerName." was not found";
            return;
        }
        //make sure second item is a container
        if($containerRow['room'] == 0){
            echo "either ".$containerName." is full, or it can not hold any items";
            return;
        }
        //make sure the first item is not is something else
        if($itemRow['insideOf'] != 0){
            echo $itemName." is inside of something else. Remove it first.";
            return;
        }
        //make sure first item can be put into the second
        if($containerRow['room'] < $itemRow['size']){
            echo "there is not enough room for ".$itemName." [".$itemRow['size']."] in ".$containerName." [".$containerRow['room']." left]";
            return;
        }
        //put in
        query("update items set insideOf=".$containerRow['ID']." where ID=".$itemRow['ID']);
        query("update items set room=".$containerRow['room']-$itemRow['size']);
        //add alert
        addAlert(alertTypes::hiddenItem);
        break;
    
    case('getItemsInScene'):
        //get item ids
        $itemIDsResult = queryMulti("select itemID from itemsinscenes where sceneID=".$_SESSION['currentScene']);
        //get items names and ids
        if($row = mysqli_fetch_array($itemIDsResult)){
            $itemNamesQuery = "select ID,Name from items where ID=";
        }
        else{
            //no items in the scene
            echo "";
            mysqli_free_result($itemIDsResult);
            return;
        }
        $itemNamesQuery .= $row['itemID'];
        while($row = mysqli_fetch_array($itemIDsResult)){
            $itemNamesQuery .=" or ".$row['itemID'];
        }
        mysqli_free_result($itemIDsResult);
        $itemNamesResult = queryMulti($itemNamesQuery);
        //seperate into <>
        while($row = mysqli_fetch_array($itemNamesQuery)){
            echo getSpanText(spanTypes::ITEM,$row['ID'],$row['Name'])."<>";
        }
        mysqli_free_result($itemNamesQuery);
        break;
    
    case('attack'):        
        //see if player is there
        $row = query("SELECT playerID FROM sceneplayers WHERE SceneID =".prepVar($_SESSION['currentScene'])." AND playerName = ".prepVar($_GET['Name']));
        if($row['playerID']){
            speakAction(actionTypes::ATTACK, $_GET['Name'], $row['playerID']);
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
        //add player to scene list
        query("insert into sceneplayers (sceneID,playerID,playerName) values(".prepVar($_SESSION['currentScene']).",".prepVar($_SESSION['playerID']).",".prepVar($_SESSION['playerName']).")");

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

?>