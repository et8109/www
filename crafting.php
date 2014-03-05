<?php

include 'phpHelperFunctions.php';

/**
 *the keyword types required in all items
 */
$itemKeywordTypes = array(
    keywordTypes::MATERIAL,
    keywordTypes::QUALITY
);

$function = $_POST['function'];
switch($function){
        /**
         *adds the item to the item list
         *adds the item's id to the player's item list
         *adds the item to the player's description.
         *adds an alert for the player
         */
    case('craftItem'):
        //make sure the player is a blacksmith
        $level = getPlayerManageLevel();
        if($level != keywordTypes::APPSHP && $level != keywordTypes::MANAGER){
            sendError("You don't have permission to craft here.");
        }
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
        //remove materials from scene
        foreach ($itemKeywordTypes as $t){
            query("remove from scenekeywords where ID=".prepVar($_SESSION['currentScene'])." and keywordID=".$keywordIDs[$t]." limit 1");
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

/**
 *replaces the first keyword of the given type.
 *returns error on insufficient materials
 *returns false on not type not found
 *should work for scene actions if corrent kwt is given
 */
function replaceKeywordType($desc, $keywordType, &$IdOut){
    //find prerequisites
    $prerequisite = "";
    switch($keywordType){
        case(keywordTypes::QUALITY):
            $row = query("select craftSkill from playerinfo where ID = ".prepVar($_SESSION['playerID']));
            if($row == false){
                sendError("error finding craft level");
            }
            switch($row['craftSkill']){
                case(0):
                    $prerequisite = "ID<=3";
                    break;
                case(1):
                    $prerequisite = "ID<=4";
                    break;
            }
            break;
    }
    //find and replace the word
    $descArray = explode(" ",$desc);
    $descArrayLength = count($descArray);
    for($i=0; $i<$descArrayLength; $i++){
        $word = $descArray[$i];
        $query = "select ID from keywordwords where Word=".prepVar(strtolower($word))." and Type=".prepVar($keywordType);
        if($prerequisite != ""){
            $query.=" and ".$prerequisite;
        }
        $keywordRow = query($query);
        if(isset($keywordRow['ID'])){
            //if a material, make sure it is available
            if($keywordType == keywordTypes::MATERIAL){
                $numMatRow = query("select count(1) from scenekeywords where ID=".prepVar($_SESSION['currentScene'])." and keywordID=".prepVar($keywordRow['ID']));
                if($numMatRow[0] < 1){
                    sendError("You don't have enough material for: ".$word);
                }
            }
            //find correct span to replace with
            $spanType = spanTypes::KEYWORD;
            if($keywordType == keywordTypes::SCENE_ACTION){
                $spanType = spanTypes::ACTION;
            }
            $descArray[$i] = getSpanText($spanType,$descArray[$i],$descArray[$i]);
            $IdOut = $keywordRow['ID'];
            return implode(" ",$descArray);
        }
    }
    return false;
}
?>