<?php

session_start();
include 'phpHelperFunctions.php';

/**
 *the keyword types required in all items
 */
$itemKeywordTypes = array(
    keywordTypes::MATERIAL,
    keywordTypes::QUALITY
);

//set connection
$con = getConnection();
$function = $_POST['function'];
switch($function){
        /**
         *adds the item to the item list
         *adds the item's id to the player's item list
         *adds the item to the player's description.
         *adds an alert for the player
         */
    case('craftItem'):
        //make sure the player can take an item
        checkPlayerCanTakeItem();
        $keywordIDs = array();
        $IdOut = -1;
        //make sure all required keyword types were replaced
        $desc = $_POST['Description'];
        $numTypes = sizeof($itemKeywordTypes);
        for($i=0; $i<$numTypes; $i++){
            $type = $itemKeywordTypes[$i];
            $desc = replaceKeywordType($desc, $type, $IdOut);
            $keywordIDs[$type] = $IdOut;
            if($desc == false){
                sendError("type ".$keywordTypeNames[$type]." keyword was not found");
            }
        }
        //check for optional keywords
        $tempDesc = replaceKeywordType($desc, keywordTypes::CONTAINER,$IdOut);
        $isContainer = false;
        if($tempDesc != false){
            $desc = $tempDesc;
            $isContainer = true;
            //add to keywords of item
            $itemKeywordTypes[] = keywordTypes::CONTAINER;
            $keywordIDs[keywordTypes::CONTAINER] = $IdOut;
        }
        //make sure desc length is less than max
        checkDescIsUnderMaxLength($desc, spanTypes::ITEM);
        //add the item into db
        $lastID = lastIDquery("insert into items (playerID, Name, Description) values (".prepVar($_SESSION['playerID']).",".prepVar($_POST['Name']).",".prepVar($desc).")");
        //if item is a container, give it room
        if($isContainer){
            query("Update items set room=2 where ID=".$lastID);
        }
        //add the item to itemKeywords with it's keywords
        $numKeywords = count($itemKeywordTypes);
        foreach ($itemKeywordTypes as $t){
            query("insert into itemkeywords (ID, keywordID, type) values (".$lastID.",".$keywordIDs[$t].",".$t.")");
        }
        addItemIdToPlayer($lastID, $_POST['Name']);
        break;
    
    case('getCraftInfo'):
        $row = query("SELECT `craftSkill` FROM `playerinfo` WHERE ID = ".prepVar($_SESSION['playerID']));
        echo $row['craftSkill'];
        break;
}
?>