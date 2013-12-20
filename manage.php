<?php

/**
 *returns additional span text for managing a scene
 */
function getSpanTextManagingScene($sceneID){
    return "<span class='active action manageScene' onclick='manageScene(".$sceneID.")'>Manage</span>";
}
/**
 *returns true if the player can manage this scene
 *false if they can't
 */
function checkPlayerManage(){
    //only works because there is 1 job per scene
    $keywordRow = query("select ID from playerkeywords where ID=".prepVar($_SESSION['playerID'])." and type=".keywordTypes::PLAYER_JOB." and locationID=".prepVar($_SESSION['currentScene']));
    if(is_bool($keywordRow)){
        return false;
    }
    return true;
    /* for multiple jobs in 1 scene
    $playerKeywordRow = query("select locationID,keywordID from playerkeywords where ID=".prepVar($_SESSION['playerID'])." and type=".keywordTypes::PLAYER_JOB);
    $sceneKeywordRow = query("select keywordID from scenekeywords where type=".keywordTypes::SCENE_ACTION);
    //scene keyword matches plyer job keyword && correct location
    if($GLOBALS['sceneKeywordToPlayerJob'][$sceneKeywordRow['keywordID']] == $playerKeywordRow['keywordID'] &&
       $playerKeywordRow['locationID'] == $_GET['ID']){
        return true;
    }
    return false;
    */
}


session_start();
include 'phpHelperFunctions.php';
//set connection
$con = getConnection();
$function = $_GET['function'];
switch($function){
    case('addItemToScene'):
        //check player manage keyword/permission
        if(checkPlayerManage == false){
            return "You don't have permission";
        }
        //get item id,size
        $idRow = query("select ID, size from items where playerID=".prepVar($_SESSION['playerID'])." and Name=".prepVar($_GET['Name']));
        if(is_bool($idRow)){
            return "You do not have that item";
        }
        //make sure scene has less than max items
        $numItems = query("select count(1) from itemsinscenes where sceneID=".prepVar($_SESSION['currentScene']));
        if($numItems >= constants::maxSceneItems){
            return "This location is full already";
        }
        //remove item from player
        $removedReponse = removeItemIdFromPlayer($idRow['ID']);
        //if could not remove item from player
        if(is_string($removedReponse)){
            return $removedReponse;
        }
        //add item to items in scenes, along with note
        query("insert into itemsinscenes (sceneID,itemID,note) values (".prepVar($_SESSION['currentScene']).",".prepVar($idRow['ID']).",".prepVar($_GET['Note']).")");
        break;
    
    case('removeItemFromScene'):
        //check player manage keyword/permission
        if(checkPlayerManage == false){
            return "You don't have permission";
        }
        //get item id,size
        $idRow = query("select ID from items where Name=".prepVar($_GET['Name']));
        if(is_bool($idRow)){
            return "That item does not exist";
        }
        //make sure the player can take an item
        $status = checkPlayerCanTakeItem(4);
        if(is_string($status)){
            return $status;
        }
        //remove item from scene list
        $removeRow = query("delete from itemsInScenes where sceneID=".prepVar($_SESSION['currentScene'])." and itemID=".prepVar($idRow['ID']));
        addItemIdToPlayer($idRow['ID']);
        break;
    
    case('changeItemNote'):
        //check player manage keyword/permission
        if(checkPlayerManage == false){
            return "You don't have permission";
        }
        //get item id
        $idRow = query("select ID from items where playerID=".prepVar($_SESSION['playerID'])." and Name=".prepVar($_GET['Name']));
        if(is_bool($idRow)){
            return "Item not found";
        }
        query("update itemsinscenes set note=".prepVar($_GET['Note'])." where itemID=".$idRow['ID']);
        break;
}
?>