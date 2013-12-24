<?php

/**
 *the keyword types required in all items
 *1: material
 *2:quality
 */
final class requiredItemKeywordTypes {
    const material = 1;
    const quality = 2;
}

session_start();
include 'phpHelperFunctions.php';
//set connection
$con = getConnection();
$function = $_GET['function'];
switch($function){
        /**
         *adds the item to the item list
         *adds the item's id to the player's item list
         *adds the item to the player's description.
         *adds an alert for the player
         */
    case('craftItem'):
        //make sure the player can take an item
        checkPlayerCanTakeItem(4);
        //make sure all required keyword types were replaced
        $desc = $_GET['Description'];
        foreach(requiredItemKeywordTypes as $type){
            if(!replaceKeywordType($desc, $type)){
                return "type ".$keywordTypeNames[$type]." keyword was not found";
            }
        }
        //make sure desc length is less than max
        checkDescIsUnderMaxLength($desc, spanTypes::ITEM);
        //add the item into db
        $lastID = lastIDquery("insert into items (playerID, Name, Description) values (".prepVar($_SESSION['playerID']).",".prepVar($_GET['Name']).",".prepVar($desc).")");
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
        addItemIdToPlayer($lastID);
        break;
    
    case('getCraftInfo'):
        $row = query("SELECT `craftSkill` FROM `playerinfo` WHERE ID = ".prepVar($_SESSION['playerID']));
        echo $row['craftSkill'];
        break;
}
?>