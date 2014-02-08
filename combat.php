<?php
/**
 * keyword ID => increase in combat skill
 */
$combatItemKeywords = array(
    1 => 1,//wood
    2 => 2 //metal
);

include 'phpHelperFunctions.php';

$function = $_POST['function'];
switch($function){
    case('attack'):        
        //see if player is there
        $row = query("SELECT playerID FROM sceneplayers WHERE SceneID =".prepVar($_SESSION['currentScene'])." AND playerName = ".prepVar($_POST['Name']));
        if($row['playerID']){
            speakAction(actionTypes::ATTACK, $_POST['Name'], $row['playerID']);
            //no need to echo, it's in chat
        }
        else{
            sendError($_POST['Name']." is not nearby");
        }
        break;
}

/**
 *Gets the combat level of the player with the playerID.
 *Does not check if the player is nearby
 */
function getCombatLevel($playerID){
    //set initial
    $playerCombatLevel = 0;
    //get player item ids
    $rowItemIds = queryMulti("select ID from items where playerID=".prepVar($_SESSION['playerID'])." and insideOf=0");
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
                if(isset( $GLOBALS['combatItemKeywords'][$keywordRow['keywordID']] )){
                    $playerCombatLevel += $GLOBALS['combatItemKeywords'][$keywordRow['keywordID']];
                }
            }
        }
    }
    return $playerCombatLevel;
}

?>