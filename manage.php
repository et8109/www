<?php

session_start();
include 'phpHelperFunctions.php';
//set connection
$con = getConnection();
$function = $_GET['function'];
switch($function){
    case('addItemToScene'):
        //must be at least an apprentice
        if(getPlayerManageLevel() < 1){
            sendError("You don't have permission");
        }
        //get item id,size
        $idRow = query("select ID, size from items where playerID=".prepVar($_SESSION['playerID'])." and Name=".prepVar($_GET['Name']));
        if(is_bool($idRow)){
            sendError("You do not have that item");
        }
        //make sure scene has less than max items
        $numItems = query("select count(1) from itemsinscenes where sceneID=".prepVar($_SESSION['currentScene']));
        if($numItems >= constants::maxSceneItems){
            sendError("This location is full already");
        }
        //remove item from player
        removeItemIdFromPlayer($idRow['ID']);
        //add item to items in scenes, along with note
        query("insert into itemsinscenes (sceneID,itemID,note) values (".prepVar($_SESSION['currentScene']).",".prepVar($idRow['ID']).",".prepVar($_GET['Note']).")");
        break;
    
    case('removeItemFromScene'):
        //must be at least manager
        if(getPlayerManageLevel() < 2){
            sendError("You don't have permission");
        }
        //get item id,size
        $idRow = query("select ID from items where Name=".prepVar($_GET['Name']));
        if(is_bool($idRow)){
            sendError("That item does not exist");
        }
        //make sure the player can take an item
        checkPlayerCanTakeItem(4);
        //remove item from scene list
        $removeRow = query("delete from itemsInScenes where sceneID=".prepVar($_SESSION['currentScene'])." and itemID=".prepVar($idRow['ID']));
        addItemIdToPlayer($idRow['ID']);
        break;
    
    case('changeItemNote'):
        //must be at least apprentice
        if(getPlayerManageLevel() < 1){
            sendError("You don't have permission");
        }
        //get item id
        $idRow = query("select ID from items where playerID=".prepVar($_SESSION['playerID'])." and Name=".prepVar($_GET['Name']));
        if(is_bool($idRow)){
            sendError("Item not found");
        }
        query("update itemsinscenes set note=".prepVar($_GET['Note'])." where itemID=".$idRow['ID']);
        break;
    
    case('changeSceneDesc'):
        //must be at least a lord
        if(getPlayerManageLevel() < 3){
            sendError("You don't have permission");
        }
        updateDescription($_SESSION['currentScene'],$_GET['desc'],spanTypes::SCENE);
        break;
    
    case('applyappshp'):
        //make sure player has no job
        $playerRow = query("select count(1) from playerkeywords where ID=".prepVar($_SESSION['playerID'])." and (type=".keywordTypes::APPSHP." or type=".keywordTypes::MANAGER." or type=".keywordTypes::LORD." or type=".keywordTypes::MONARCH.")");
        if(is_int($playerRow) && $playerRow > 0){
            sendError("Leave your current job first");
        }
        //make sure the location accepts/has room for apprentice
        $sceneRow = query("select count(1) from scenes where ID=".prepVar($_SESSION['playerID'])." and appshp=1");
        if($playerRow < 1){
            sendError("This location does not accept apprentices");
        }
        //make sure location has a manager, send request
        $managerRow = query("select ID from playerkeywords where locationID=".prepVar($_SESSION['currentScene'])." and type=".keywordTypes::MANAGER);
        if(!isset($managerRow)){
            //if there is no manager
            sendError("No current manager.<span class='active action' onclick='applyManage()'>Apply to manage?</span>");
        }
        else{
            //if there is a manager
            echo sendAppshpRequest($managerRow['ID']);
            return;
        }
        break;
}

/**
 *sends a request to the manager for an apprenticeship
 *returns the name of the manager
 */
function sendAppshpRequest($managerID){
    //get name, email
    $managerRow = query("select Name, email from playerinfo where ID=".prepVar($managerID));
    if(!isset($managerRow)){
        sendError("Error contancting the manager.");
    }
    //send message to manager
    
    return $managerRow['Name'];
}
?>