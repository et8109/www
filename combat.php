<?php

include 'phpHelperFunctions.php';

$function = $_POST['function'];
switch($function){
    case('attack'):
        //check player health
        $healthRow = query("select health from playerinfo where ID=".prepVar($_SESSION['playerID']));
        if(!$healthRow || $healthRow['health'] <= 0){
            sendError("Heal yourself first.");
        }
        //check sanctuary
        $sceneRow = query("select count(1) from scenekeywords where ID=".prepVar($_SESSION['currentScene'])." and keywordID=12");
        if($sceneRow[0] == 1){
            sendError("You cannot fight in a sanctuary.");
        }
        $targetID;
        $targetSpanType;
        $opponentCombatLevel;
        //see if player is there
        $row = query("SELECT playerID FROM sceneplayers WHERE SceneID =".prepVar($_SESSION['currentScene'])." AND playerName = ".prepVar($_POST['Name']));
        if($row != false){
            $targetID = $row['playerID'];
            $targetSpanType = spanTypes::PLAYER;
            $opponentCombatLevel = getPlayerCombatLevel($targetID);
        }
        else{
            $row = query("SELECT npcID FROM scenenpcs WHERE SceneID =".prepVar($_SESSION['currentScene'])." AND npcName = ".prepVar($_POST['Name'])." and health>0");
            if($row != false){
                $targetID = $row['npcID'];
                $targetSpanType = spanTypes::NPC;
                $opponentCombatLevel = getNpcCombatLevel($targetID);
            } else{
                sendError($_POST['Name']." is not nearby");
            } 
        }
        //determine outcome
        $actionWords;
        $playerCombatLevel = getPlayerCombatLevel($_SESSION['playerID']);
        //math
        $chance = $playerCombatLevel/($playerCombatLevel + $opponentCombatLevel);
        $win = ((rand(0,10)*.1) < $chance);
        if($win){
            $actionWords = " struck ";
            //lower health
            if($targetSpanType == spanTypes::PLAYER){
                query("update playerinfo set health=health-1 where ID=".prepVar($targetID)." and health>0");
            } else if($targetSpanType == spanTypes::NPC){
                query("update scenenpcs set health=health-1 where sceneID=".prepVar($_SESSION['currentScene'])." and npcID=".prepVar($targetID)." and health>0");
                $killRow = query("select count(1) from scenenpcs where sceneID=".prepVar($_SESSION['currentScene'])." and npcID=".prepVar($targetID)." and health=0");
                if($killRow[0] > 0){
                    $actionWords = " defeated ";
                    //if a killing blow, check for npc material
                    $materialRow = query("select keywordID from npckeywords where ID=".prepVar($targetID)." and sceneID=".prepVar($_SESSION['currentScene'])." and type=1");
                    if($materialRow){
                        //if a material is available, check for job in craft scene
                        $locationQuery = query("select locationID from playerkeywords where ID=".prepVar($_SESSION['playerID'])." and (type=".keywordTypes::APPSHP." or type=".keywordTypes::MANAGER.")");
                        if($locationQuery){
                            //if a crafter, check amount of materials
                            $numMatRow = query("select count(1) from scenekeywords where ID=".prepVar($locationQuery['locationID']));
                            if($numMatRow < constants::maxSceneItems){
                                $matName = query("select Word from keywordwords where ID=".prepVar($materialRow['keywordID'])." limit 1");
                                $actionWords = " looted ".$matName['Word']." from ";
                                //add material to craft job scene
                                query("insert into scenekeywords (ID,keywordID,type) values (".$locationRow.",".$materialRow['keywordID'].",".keywordTypes::MATERIAL.")");
                            }
                        }
                    }
                }
            }
        }
        else{
            $actionWords = " was blocked by ";
            //lower health
            query("update playerinfo set health=health-1 where ID=".prepVar($_SESSION['playerID'])." and health>0");
        }
        $actionWords .= $playerCombatLevel." -> ".$opponentCombatLevel." ";
        speakActionAttack($targetSpanType,$targetID,$_POST['Name'],$actionWords);
        break;
    
    case('regen'):
        //check if in sanctuary
        $sceneRow = query("select count(1) from scenekeywords where ID=".prepVar($_SESSION['currentScene'])." and keywordID=12");
        if($sceneRow[0] != 1){
            sendError("You can only regenerate in a sanctuary.");
        }
        //set health to max
        query("update playerinfo set health=".prepVar(constants::maxHealth)." where ID=".prepVar($_SESSION['playerID']));
        break;
}

/**
 *Gets the combat level of the player/npc
 *Does not check if they are nearby
 */
function getPlayerCombatLevel($playerID){
    /**
    * keyword ID => increase in combat skill
    */
   $combatItemKeywords = array(
       1 => 1,//wood
       2 => 2 //metal
   );
    //set initial
    $combatLevel = 1;
    //get player item ids
    $rowItemIds = queryMulti("select ID from items where playerID=".prepVar($playerID)." and insideOf=0");
    //if player has no items
    if(is_bool($rowItemIds)){
        //nothing
    }
    else{
        //get keywords from items
        $itemRow = mysqli_fetch_array($rowItemIds);
        $multiQuery = "select keywordID from itemKeywords where ID=".prepVar($itemRow['ID']);
        while($itemRow = mysqli_fetch_array($rowItemIds)){
            $multiQuery .= " or ID=".prepVar($itemRow['ID']);
        }
        mysqli_free_result($rowItemIds);
        $keywordIdRows = queryMulti($multiQuery);
        //if items have no keywords
        if(is_bool($keywordIdRows)){
            //nothing
        }
        else{
            //combat math, items
            while($keywordRow = mysqli_fetch_array($keywordIdRows)){
                if(isset( $combatItemKeywords[$keywordRow['keywordID']])){
                    $combatLevel += $combatItemKeywords[$keywordRow['keywordID']];
                }
            }
        }
    }
    return $combatLevel;
}
/**
 *returns the combat level of an npc
 */
function getNpcCombatLevel($npcID){
    $row = query("select level from npcs where ID=".prepVar($npcID));
    return $row['level'];
}
?>