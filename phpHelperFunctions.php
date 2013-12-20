<?php
/**
 *--outdated
 *printDebug
 *getConnection
 *query
 *  queryMulti
 *  lastIDQuery
 *prepVar
 *addChatText
 *  speakAction
 *  updateChatTime
 *addAlert
 *  removeAlert
 *getSpanText
 *  getSpanTextManagingScene
 *getCombatLevel
 *replaceKeywordType
 *  replaceKeywordID
 *  replacePlayerItems
 *updateDescription
 *getTable
 *  getTableKeywords
 */
include_once 'constants.php';

/**
 *prints the input string to the debug file.
 *adds a new line
 */
function printDebug($word){
    $debugFile = fopen("debug.txt", "a");
    fwrite($debugFile,$word. "\r\n");
    fclose($debugFile);
}

/**
 *returns a connection ($con) to the db.
 *set the global connection, if applicable, to this
 */
function getConnection(){
    $con = mysqli_connect("Localhost","root","","game");
    //check connection
    if (mysqli_connect_errno()){
        die("could not connect to db");
        //failed
    }
    return $con;
}

/**
 *querys the databse and returns the row.
 *only returns 1 row. If you need more, use queryMulti.
 *uses $GLOBALS['con']. doesn't work if not set
 *frees the result on its own
 */
function query($sql){
    $result = mysqli_query($GLOBALS['con'], $sql);
    if(is_bool($result)){
        return false;
    }
    $row = mysqli_fetch_array($result);
    mysqli_free_result($result);
    return $row;
}

/**
 *querys and returns the result rather than the row.
 *used when multiple rows are taken
 *uses $GLOBALS['con']. doesn't work if not set
 *dont't forget mysqli_free_result($result);
 */
function queryMulti($sql){
    $result = mysqli_query($GLOBALS['con'], $sql);
    return $result;
}

/**
 *querys the db and returns the insert id.
 *uses $GLOBALS['con']. doesn't work if not set
 */
function lastIDQuery($sql){
        mysqli_query($GLOBALS['con'], $sql);
        return mysqli_insert_id($GLOBALS['con']);
        
}

/**
 *sanatizes a variable
 */
function prepVar($var){
    //replace ' with ''
    $var = str_replace("'", "''", $var);
    //if not a number, surround in quotes
    if(!is_numeric($var)){
        $var = "'".$var."'";
    }
    return $var;
}

$fileName = "chats/".$_SESSION['currentScene']."Chat.txt";
/**
 *adds the given text to the current chat file
 */
function addChatText($text){
    $time=date_timestamp_get(new DateTime());
    $lines = array();
    $lines = file($GLOBALS['fileName']);
    $chatFile = fopen($GLOBALS['fileName'], "w");
    for($i=4; $i<40; $i++){
        fwrite($chatFile,$lines[$i]);
    }
    fwrite($chatFile,"\r\n".$time."\r\n".$_SESSION['playerID']."\r\n".$_SESSION['playerName']."\r\n".$text);
    fclose($chatFile);
}

/**
 *updates the player's chat time so it is the most current in the scene
 */
function updateChatTime(){
    $lines = array();
    $lines = file($GLOBALS['fileName']);
    if(intval($lines[36]) > $_SESSION['lastChatTime']){
        $_SESSION['lastChatTime'] = intval($lines[36]);
    }
}

/**
 *adds an alert to the player's alert list.
 *Does not add it to their page,this list is only checked during setup
 */
function addAlert($alertNum){
    query("insert into playeralerts (alertID, playerID) values (".$alertNum.",".prepVar($_SESSION['playerID']).")");
}


/**
 *removes the alert from the databse
 */
function removeAlert($alertNum){
    query("delete from playeralerts where playerID=".prepVar($_SESSION['playerID'])." and alertID=".$alertNum);
}

/**
 *adds an action to the current chat
 */
function speakAction($type, $targetName, $targetID){
    $text = "<".$type.">";
    switch($type){
        case(actionTypes::WALKING):
            $text .= "<>".$targetID."<>".getSpanText(spanTypes::PLAYER,$_SESSION['playerID'],$_SESSION['playerName'])." walked to ".getSpanText(spanTypes::SCENE,$targetID,$targetName);
            break;
        case(actionTypes::ATTACK):
            $playerCombatLevel = getCombatLevel($_SESSION['playerID']);
            $opponentCombatLevel = getCombatLevel($_GET['Name']);
            if($playerCombatLevel > $opponentCombatLevel){
                $actionWords = " attacked ";
            }
            else{
                $actionWords = " blocked and retaliated against ";
            }
            $actionWords .= $playerCombatLevel." p--o ".$opponentCombatLevel." ";
            $text .= "<>".$targetID."<>".getSpanText(spanTypes::PLAYER,$_SESSION['playerID'],$_SESSION['playerName']).$actionWords.getSpanText(spanTypes::PLAYER,$targetID,$targetName);
            break;
    }
    addChatText($text);
}

/**
 *returns the span text for the given object.
 *the span text is for the title/name, not description
 *Note: id for keywords is the actual word, not number
 */
function getSpanText($type, $id, $name){
    switch($type){
        case(spanTypes::ITEM):
            return "<span class='item' onclick='addDesc(".spanTypes::ITEM.",".$id.")'>".$name."</span>";
            break;
        case(spanTypes::KEYWORD):
            return "<span class='keyword' onclick='addDesc(".spanTypes::KEYWORD.",&apos;".$name."&apos;)'>".$name."</span>";
            break;
        case(spanTypes::PLAYER):
            return "<span class='name' onclick='addDesc(".spanTypes::PLAYER.",".$id.")'>".$name."</span>";
            break;
        case(spanTypes::SCENE):
            return "<span class='sceneName' onclick='addDesc(".spanTypes::SCENE.",".$id.")'>".$name."</span>";
            break;
    }
}

/**
 *replaces the first keyword of the given type.
 *returns false if not found
 */
function replaceKeywordType($desc, $type){
    $descArray = explode(" ",$desc);
    $descArrayLength = count($descArray);
    for($i=0; $i<$descArrayLength; $i++){
        $keywordRow = query("select ID from keywordwords where Word=".prepVar($descArray[$i])." and Type=".prepVar($type));
        if(!is_bool($keywordRow)){
            $descArray[$i] = getSpanText(spanTypes::KEYWORD,$descArray[$i],$descArray[$i]);
            return implode(" ",$descArray);
        }
    }
    return false;
}

/**
 *replaces the first keyword of the given ID.
 *returns false if not found
 */
function replaceKeywordID($desc, $ID){
    $descArray = explode(" ",$desc);
    $descArrayLength = count($descArray);
    for($i=0; i<$descArrayLength; $i++){
        $keywordRow = query("select ID from keywordwords where Word=".prepVar($descArray[$i])." and ID=".prepVar($ID));
        if(!is_bool($keywordRow)){
            $descArray[$i] = getSpanText(spanTypes::KEYWORD,$descArray[$i],$descArray[$i]);
            return implode(" ",$descArray);
        }
    }
    return false;
}

/**
 *replaces all items in the player's description
 *returns false if not found
 */
function replacePlayerItems($description){
    //find item names
    $itemNamesResult = queryMulti("select Name from items where playerID=".prepVar($_SESSION['playerID'])." and insideOf=0");
    //if failed in query
    if(is_bool($itemNamesResult)){
        return false;
    }
    while($itemRow = mysqli_fetch_array($itemNamesResult)){
        //if an item is not found
        if(strpos($description, $itemRow['Name']) == false){
            mysqli_free_result($itemNamesResult);
            return false;
        }
        else{
            //the item was found
            $description = str_replace($itemRow['Name'], getSpanText(spanTypes::ITEM,$itemRow['ID'],$itemRow['Name']), $description);
        }
    }
    mysqli_free_result($itemNamesResult);
}

/**
 *updates a description in the db
 *returns false if failed
 */
function updateDescription($ID, $description, $spanTypesType){
    $table = getTable($spanTypesType);
    $keywordTable = getTableKeywords($spanTypesType);
    if($table == null){
        return false;
    }
    //get IDs of keywords
    $keywordsResult = queryMulti("select keywordID from ".$keywordTable." where ID=".$ID);
    if(is_bool($keywordsResult)){
        return false;
    }
    //replace one of each keyword ID
    while($keywordRow = mysqli_fetch_array($keywordsResult)){
        $description = replaceKeywordID($description,$keywordRow['keywordID']);
        //if ID not found
        if($description == false){
            mysqli_free_result($keywordsResult);
            return false;
        }
    }
    mysqli_free_result($keywordsResult);
    //if a player, make sure items are there
    if($spanTypesType == spanTypes::PLAYER){
        $description = replacePlayerItems($description);
        if($description == false){
            return false;
        }
    }
    //make sure its under max length
    $status = checkDescIsUnderMaxLength();
    if($status < 0){
        return false;
    }
    query("update ".$table." set Description=".prepVar($description)." where ID=".prepVar($ID));
    return true;
}

/**
 *returns negetive number if too long, positive if ok
 *number is difference in lengths
 */
function checkDescIsUnderMaxLength($desc, $spanType){
    switch($spanType){
        case(spanTypes::ITEM):
            return maxLength::itemDesc - strlen($desc);
            break;
        case(spanTypes::KEYWORD):
            return maxLength::keywordDesc - strlen($desc);
            break;
        case(spanTypes::PLAYER):
            return maxLength::playerDesc - strlen($desc);
            break;
        case(spanTypes::SCENE):
            return maxLength::sceneDesc - strlen($desc);
            break;
    }
}

/**
 *returns the table where the object itself it
 */
function getTable($spanTypesType){
    switch($spanTypesType){
        case(spanTypes::SCENE):
            return 'scenes';
            break;
        case(spanTypes::ITEM):
            return 'items';
            break;
        case(spanTypes::PLAYER):
            return 'playerInfo';
            break;
        case(spanTypes::KEYWORD):
            return 'keywords';
            break;
    }
    return null;
}

/**
 *returns the table where the object's keywords are
 */
function getTableKeywords($spanTypesType){
    switch($spanTypesType){
        case(spanTypes::SCENE):
            return 'scenekeywords';
            break;
        case(spanTypes::ITEM):
            return 'items';
            break;
        case(spanTypes::PLAYER):
            return 'playerInfo';
            break;
        case(spanTypes::KEYWORD):
            return 'keywords';
            break;
    }
    return null;
}

/**
 *adds an item to the player's inventory
 *returns empty string on success, a string on fail
 *checkPlayerCanTakeItem first!
 */
function addItemNameToPlayer($itemName){
    //get item ID
    $idRow = query("select ID from items where playerID=".prepVar($_SESSION['playerID'])." and Name=".prepVar($_GET['Name']));
        if(is_bool($idRow)){
            return "You do not have that item";
    }
    return addItemIdToPlayer($idRow['ID']);
}
/**
 *adds an item to the player's inventory
 *returns string message on fail
 *checkPlayerCanTakeItem first!
 */
function addItemIdToPlayer($itemID){
    //change playerID for the item
    query("update items set playerID=".prepVar($_SESSION['playerID'])." where ID=".prepVar($itemID));
    //add item to player desc
    $row = query("select Description from playerinfo where ID=".prepVar($_SESSION['playerID']));
    $playerDescription = $row['Description'];
    $playerDescription .= getSpanText(spanTypes::ITEM,$itemID,$itemName);
    query("Update playerinfo set Description=".prepVar($playerDescription)." where ID=".prepVar($_SESSION['playerID']));
    //add an alert for the player
    addAlert(alertTypes::newItem);
    return true;
}
/**
 *makes sure the player can take the described item
 *returns string on fail, true on success
 */
function checkPlayerCanTakeItem($itemSize){
    //check size of item, player room -not done
    //check player has less than max items
    $numItems = query("select count(1) from items where playerID=".prepVar($_SESSION['playerID']));
    if($numItems >= constants::maxPlayerItems){
        return "You have too many items";
    }
    //check player desc length
    $row = query("select Description from playerinfo where ID=".prepVar($_SESSION['playerID']));
    $playerDescription = $row['Description'];
    $descLength = strlen($playerDescription)+maxLength::maxSpanLength;
    if($descLength > maxLength::playerDesc){
        return "Your description is ".$descLength-maxLength::playerDesc." chars too long.";
    }
    return true;
}
/**
 *removes the item from the player
 *returns string message on fail
 */
function removeItemIdFromPlayer($itemID){
    $updateRow = query("update items set playerID=0 where playerID=".prepVar($_SESSION['playerID'])." and ID=".prepVar($idRow['ID']));
    if($updateRow == false){
        return "You do not have this item";
    }
    addAlert(alertTypes::removedItem);
    return true;
}
?>