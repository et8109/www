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
                //if no id is set, make it the player
                $ID = isset($_GET['ID']) ? $_GET['ID'] : $_SESSION['playerID'];
                $row = query("select Name, Description from playerinfo where ID=".prepVar($ID));
                echo getSpanText(spanTypes::PLAYER,$ID,$row["Name"])."<>".$row["Description"];
                break;
            case(spanTypes::SCENE):
                //if no id set, it's the current scene
                $ID = isset($_GET['ID']) ? $_GET['ID'] : $_SESSION['currentScene'];
                $row = query("select Name, Description, appshp from scenes where ID=".prepVar($ID));
                echo getSpanText(spanTypes::SCENE,$ID,$row["Name"])."<>".$row["Description"];
                //managing the scene
                $manageLevel = getPlayerManageLevel();
                if($manageLevel > 0){
                    echo " ".getSpanTextManagingScene($manageLevel);
                }
                //apply for apprenticeship
                else if($row['appshp'] == true){
                    echo " ".getSpanTextApplyForAppshp();
                }
                break;
        }
        break;
    
    case('updateDescription'):
        return updateDescription($_SESSION['playerID'], $_GET['Description'], spanTypes::PLAYER);
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
    
    case('putItemIn'):
        $itemName = prepVar($_GET['itemName']);
        $containerName = prepVar($_GET['containerName']);
        //get item and container info
        $itemRow = query("select size,ID,insideOf from items where playerID=".prepVar($_SESSION['playerID'])." and Name=".$itemName);
        $containerRow = query("select room,ID from items where playerID=".prepVar($_SESSION['playerID'])." and Name=".$containerName);
        //make sure item was found
        if(!isset($itemRow['ID'])){
            sendError("the ".$itemName." was not found");
        }
        //make sure container was found
        if(!isset($containerRow['ID'])){
            sendError("the ".$containerName." was not found");
        }
        //make sure second item is a container
        if($containerRow['room'] == 0){
            sendError("either ".$containerName." is full, or it can not hold any items");
        }
        //make sure the first item is not is something else
        if($itemRow['insideOf'] != 0){
            sendError($itemName." is inside of something else. Remove it first.");
        }
        //make sure first item can be put into the second
        if($containerRow['room'] < $itemRow['size']){
            sendError("there is not enough room for ".$itemName." [".$itemRow['size']."] in ".$containerName." [".$containerRow['room']." left]");
        }
        //put in
        query("update items set insideOf=".$containerRow['ID']." where ID=".$itemRow['ID']);
        query("update items set room=".$containerRow['room']-$itemRow['size']);
        //add alert
        addAlert(alertTypes::hiddenItem);
        break;
    
    case('getItemsInScene'):
        //get item ids
        $itemIDsResult = queryMulti("select itemID,note from itemsinscenes where sceneID=".$_SESSION['currentScene']);
        //store itemID note connection
        $itemNotes = array();
        //get items names and ids
        if($row = mysqli_fetch_array($itemIDsResult)){
            $itemNamesQuery = "select ID,Name from items where ID=".$row['itemID'];
            $itemNotes[$row['itemID']] = $row['note'];
        }
        else{
            //no items in the scene
            echo "";
            mysqli_free_result($itemIDsResult);
            return;
        }
        while($row = mysqli_fetch_array($itemIDsResult)){
            $itemNamesQuery .=" or ".$row['itemID'];
            $itemNotes[$row['itemID']] = $row['note'];
        }
        mysqli_free_result($itemIDsResult);
        $itemNamesResult = queryMulti($itemNamesQuery);
        //seperate into <>
        while($row = mysqli_fetch_array($itemNamesQuery)){
            echo getSpanText(spanTypes::ITEM,$row['ID'],$row['Name'])."<>";
            echo $itemNotes[$row['ID']];
        }
        mysqli_free_result($itemNamesQuery);
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
}
?>