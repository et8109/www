<?php

session_start();
include 'phpHelperFunctions.php';
//set connection
$con = getConnection();
$function = $_GET['function'];
switch($function){
    case('addItemToScene'):
        //check player manage keyword/permission
        if(checkPlayerManage == false){
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
        //check player manage keyword/permission
        if(checkPlayerManage == false){
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
        //check player manage keyword/permission
        if(checkPlayerManage == false){
            sendError("You don't have permission");
        }
        //get item id
        $idRow = query("select ID from items where playerID=".prepVar($_SESSION['playerID'])." and Name=".prepVar($_GET['Name']));
        if(is_bool($idRow)){
            sendError("Item not found");
        }
        query("update itemsinscenes set note=".prepVar($_GET['Note'])." where itemID=".$idRow['ID']);
        break;
}
?>