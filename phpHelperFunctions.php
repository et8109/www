<?php

/**
Useful:
Js bookmarks!
del [full path]index.lock

done:
logging in and logging out
stored in $_SESSION: ['playerID'] ['playerName'] ['lastChatTime'] ['currentScene'] ['loginID']

big things:
PHP PDO!
buying items
combat
backgrounds
events
voice

todo:
sql db backups
*/

include_once 'constants.php';

//error_reporting(0);

session_start();
//check inputs
checkInputIsClean();
//get connection to db
$con = getConnection();
//check if logged in
$function = $_POST['function'];
if($function != 'register' && $function != 'login'){
    //check session
    if(!isset($_SESSION['playerID'])){
        session_destroy();
        sendError("Your session was lost. Please log in again.");
    }
    $loginRow = query("select loggedIn from playerinfo where ID=".prepVar($_SESSION['playerID']));
    //check login id
    if($loginRow['loggedIn'] != $_SESSION['loginID']){
        session_destroy();
        sendError("You were recently logged out. Please log in again.");
    }
}

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
    $con = mysqli_connect(constants::dbhostName,constants::dbusername,constants::dbpassword,constants::dbname);
    //check connection
    if (mysqli_connect_errno()){
        sendError("could not connect");
    }
    return $con;
}

/**
 *querys the databse and returns the row.
 *only returns 1 row. If you need more, use queryMulti.
 *uses $GLOBALS['con']. doesn't work if not set
 *frees the result on its own
 *returns false on fail
 */
function query($sql){
    $result = mysqli_query($GLOBALS['con'], $sql);
    if(is_bool($result)){
        return false;
    }
    $numRows = mysqli_num_rows($result);
    if($numRows > 1){
        sendError("result error");
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
    $var = mysqli_real_escape_string($GLOBALS['con'],$var);
    //replace ' with ''
    //$var = str_replace("'", "''", $var);
    //if not a number, surround in quotes
    if(!is_numeric($var)){
        $var = "'".$var."'";
    }
    return $var;
}
/**
 *makes sure an input is clean
 *throws error if not
 *assumes inputs are all get
 */
function checkInputIsClean(){
    /**
    *the characters or strings not allowed in inputs
    */
    $restrictedInputs = array(
       "<",
       ">",
       "<?php",
       "\r",
       "\n"
    );
    $numRestricted = sizeof($restrictedInputs);
    foreach ($_POST as $key => $value) {
        if($value == null || $value==""){
            sendError("restricted char/string in input");
        }
        for($i=0; $i<$numRestricted; $i++){
            //php said to use ===
            if(strpos($value,$restrictedInputs[$i]) === true){
                sendError("restricted char/string in input");
            }
        }
    }
}
/**
 *sends the error to the client
 *terminates all php
 */
function sendError($message){
    echo constants::errorSymbol.$message;
    die();
}

$fileName="";
if(isset($_SESSION['currentScene'])){
 $fileName = "chats/".$_SESSION['currentScene']."Chat.txt";   
}

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
    fwrite($chatFile,"\r\n".$time."\r\n".$_SESSION['playerID']."\r\n".getSpanText(spanTypes::PLAYER,$_SESSION['playerID'],$_SESSION['playerName'])."\r\n".$text);
    fclose($chatFile);
}

/**
 *updates the player's chat time so it is right now.
 */
function updateChatTime(){
    //if subtracting, watch out for the walk line when moving scenes
    $_SESSION['lastChatTime'] = date_timestamp_get(new DateTime());
}

/**
 *adds an alert to the player's alert list.
 *Does not add it to their page,this list is only checked during setup
 *optional second param is playerID
 */
function addAlert($alertNum, $optionalPlayerID = -1){
    if($optionalPlayerID == -1){
        $optionalPlayerID = $_SESSION['playerID'];
    }
    query("insert into playeralerts (alertID, playerID) values (".$alertNum.",".prepVar($optionalPlayerID).")");
}


/**
 *removes the alert from the databse
 */
function removeAlert($alertNum){
    query("delete from playeralerts where playerID=".prepVar($_SESSION['playerID'])." and alertID=".$alertNum);
}

/**
 *when a player attacks something.
 *sent to chat.
 *attacker - text - target
 */
function speakActionAttack($targetSpanType, $targetID, $targetName, $text){
    $text = getSpanText(spanTypes::PLAYER,$_SESSION['playerID'],$_SESSION['playerName']).$text.getSpanText($targetSpanType,$targetID,$targetName);
    _speakAction(actionTypes::ATTACK,$text);
}
/**
 *when a player walks from one scene to another
 *sent to chat.
 */
function speakActionWalk($sceneID, $sceneName){
    $text = getSpanText(spanTypes::PLAYER,$_SESSION['playerID'],$_SESSION['playerName'])." walked to ".getSpanText(spanTypes::SCENE,$sceneID,$sceneName);
    _speakAction(actionTypes::WALKING, $text);
}
/**
 *only to use by other speak action functions.
 *sends the type and text to chat.
 */
function _speakAction($saType, $text){
    addChatText("<".$saType."><>".$text);
}
/**
 *returns the span text for the given object.
 *the span text is for the title/name, not description
 *Note: id for keywords is the actual word, not number
 *action: id is keyword id
 */
function getSpanText($spanType, $id, $name){
    switch($spanType){
        case(spanTypes::ITEM):
            return "<span class='item' onclick='addDesc(".spanTypes::ITEM.",".$id.")'>".$name."</span>";
            break;
        case(spanTypes::KEYWORD):
            return "<span class='keyword' onclick='addDesc(".spanTypes::KEYWORD.",&apos;".$name."&apos;)'>".$name."</span>";
            break;
        case(spanTypes::PLAYER):
            //find health value
            $healthRow = query("select health from playerinfo where ID=".prepVar($_SESSION['playerID']));
            $health = intval($healthRow['health']);
            return "<span class='name b".$health."' onclick='addDesc(".spanTypes::PLAYER.",".$id.")'>".$name."</span>";
            break;
        case(spanTypes::SCENE):
            return "<span class='sceneName'>".$name."</span>";
            //return "<span class='sceneName' onclick='addDesc(".spanTypes::SCENE.",".$id.")'>".$name."</span>";
            break;
        case(spanTypes::NPC):
            //find health value
            $healthRow = query("select health from scenenpcs where npcID=".prepVar($id));
            $health = intval($healthRow['health']);
            return "<span class='npc b".$health."' onclick='addDesc(".spanTypes::NPC.",".$id.")'>".$name."</span>";
            break;
        case(spanTypes::ACTION):
            final class actionIDs {
                const crafting = 6;
                const pub = 11;
            }
            $actionFunctions = array(
                actionIDs::crafting => "startCraft()",
                actionIDs::pub => "startWaiter()"
            );
            return "<span onclick='".$actionFunctions[$id]."' class='active action'>".$name."</span>";
            break;
    }
}

/**
 *replaces the first keyword/scene action of the given ID.
 *returns false if not found
 */
function replaceKeywordID($desc, $ID){
    $descArray = explode(" ",$desc);
    $descArrayLength = count($descArray);
    for($i=0; $i<$descArrayLength; $i++){
        $keywordRow = query("select ID,Type from keywordwords where Word=".prepVar($descArray[$i])." and ID=".prepVar($ID));
        if(isset($keywordRow['ID'])){
            //found, success
            $spanType = spanTypes::KEYWORD;
            if(intval($keywordRow['Type']) == keywordTypes::SCENE_ACTION){
                $spanType = spanTypes::ACTION;
            }
            $descArray[$i] = getSpanText($spanType,$keywordRow['ID'],$descArray[$i]);
            return implode(" ",$descArray);
        }
    }
    return false;
}

/**
 *replaces all items in the player's description
 *sends error if not found
 */
function replacePlayerItems($description){
    //find item names
    $itemNamesResult = queryMulti("select Name,ID from items where playerID=".prepVar($_SESSION['playerID'])." and insideOf=0");
    //if failed in query
    if(is_bool($itemNamesResult)){
        sendError("could not find item names");
    }
    while($itemRow = mysqli_fetch_array($itemNamesResult)){
        //if an item is not found
        $pos = strpos($description, $itemRow['Name']);
        if($pos == false){
            mysqli_free_result($itemNamesResult);
            sendError("description does not contain ".$itemRow['Name']);
        }
        else{
            //the item was found
            $description = substr_replace($description,getSpanText(spanTypes::ITEM,$itemRow['ID'],$itemRow['Name']),$pos,strlen($itemRow['Name']));
        }
    }
    mysqli_free_result($itemNamesResult);
    return $description;
}

/**
 *updates a description in the db
 *sends error on fail
 */
function updateDescription($ID, $description, $spanTypesType, $keywordTypeNames){
    $table = getTable($spanTypesType);
    $keywordTable = getTableKeywords($spanTypesType);
    if($table == null){
        sendError("unfindeable type");
    }
    //if a player, make sure items are there. items first so they don't replace span stuff.
    if($spanTypesType == spanTypes::PLAYER){
        $description = replacePlayerItems($description);
    }
    //if a scene, make sure paths are there
    if($spanTypesType == spanTypes::SCENE){
    }
    //get IDs of keywords
    $keywordsResult = queryMulti("select keywordID,Type from ".$keywordTable." where ID=".$ID);
    if(is_bool($keywordsResult)){
        sendError("can't find the required keywords");
    }
    //replace one of each keyword ID
    while($keywordRow = mysqli_fetch_array($keywordsResult)){
        $description = replaceKeywordID($description,$keywordRow['keywordID']);
        //if ID not found
        if($description == false){
            sendError("could not find keyword type: ".$keywordTypeNames[intval($keywordRow['Type'])]);
        }
    }
    mysqli_free_result($keywordsResult);
    //make sure its under max length
    checkDescIsUnderMaxLength($description,$spanTypesType);
    query("update ".$table." set Description=".prepVar($description)." where ID=".prepVar($ID));
    return true;
}

/**
 *sends error if too short,
 *return num left if ok
 *scene is scene desc
 */
function checkDescIsUnderMaxLength($desc, $spanType){
    $resultNum = 0;
    switch($spanType){
        case(spanTypes::ITEM):
            $resultNum = maxLength::itemDesc - strlen($desc);
            break;
        case(spanTypes::KEYWORD):
            $resultNum = maxLength::keywordDesc - strlen($desc);
            break;
        case(spanTypes::PLAYER):
            $resultNum = maxLength::playerDesc - strlen($desc);
            break;
        case(spanTypes::SCENE):
            $resultNum = maxLength::sceneDesc - strlen($desc);
            break;
    }
    if($resultNum < 0){
        sendError("Your description is ".(-1*$status)." chars too long");
    }
    else{
        return $resultNum;
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
            return 'playerinfo';
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
            return 'playerkeywords';
            break;
        case(spanTypes::KEYWORD):
            return 'keywords';
            break;
    }
    return null;
}

/**
 *adds an item to the player's inventory
 *returns empty string on success
 *sends error on fail
 *checkPlayerCanTakeItem first!
 */
function addItemNameToPlayer($itemName){
    //get item ID
    $idRow = query("select ID from items where playerID=".prepVar($_SESSION['playerID'])." and Name=".prepVar($_POST['Name']));
        if(is_bool($idRow)){
            sendError("You do not have that item");
    }
    return addItemIdToPlayer($idRow['ID'],$itemName);
}
/**
 *adds an item to the player's inventory
 *  call addItemNameToPlayer if you don't know the id
 *adds an alert for a new item
 *checkPlayerCanTakeItem first!
 */
function addItemIdToPlayer($itemID, $itemName){
    //change playerID for the item
    query("update items set playerID=".prepVar($_SESSION['playerID'])." where ID=".prepVar($itemID));
    addWordToPlayerDesc(spanTypes::ITEM,$itemID,$itemName,$_SESSION['playerID']);
    //add an alert for the player
    addAlert(alertTypes::newItem);
    return true;
}
/**
 *makes sure the player can take an arbitrary item
 *sends error on fail, returns true on success
 */
function checkPlayerCanTakeItem($playerID = null){
    if($playerID == null){
        $playerID = $_SESSION['playerID'];
    }
    //check player has less than max items
    $numItems = query("select count(1) from items where playerID=".prepVar($_SESSION['playerID']));
    if($numItems[0] >= constants::maxPlayerItems){
        sendError("Item limit reached, found ".$numItems[0]);
    }
    //check player desc length
    $row = query("select Description from playerinfo where ID=".prepVar($_SESSION['playerID']));
    $playerDescription = $row['Description'];
    checkDescIsUnderMaxLength($playerDescription,maxLength::maxSpanLength);
    return true;
}
/**
 *removes the item from the player
 *sends error on fail
 */
function removeItemIdFromPlayer($itemID){
    $updateRow = query("update items set playerID=0 where playerID=".prepVar($_SESSION['playerID'])." and ID=".prepVar($itemID));
    addAlert(alertTypes::removedItem);
    return true;
}

/**
 *gives the keyword to the player in playerkeywords
 *adds the keyword word to the end of the player's description.
 *player ID is optional
 *does not add an alert
 */
function addKeywordToPlayer($keywordID,$keywordType,$location,$playerID = -1){
    if($playerID == -1){
        $playerID = $_SESSION['playerID'];
    }
    $nameRow = query("select Word from keywordwords where ID=".prepVar($keywordID)." limit 1");
    query("insert into playerkeywords (ID,keywordID,locationID,type) values (".prepVar($playerID).",".prepVar($keywordID).",".prepVar($location).",".prepVar($keywordType).")");
    addWordToPlayerDesc(spanTypes::KEYWORD,$keywordID,$nameRow['Word'],$playerID);
}

/**
 *adds a word to the end of a player's description
 */
function addWordToPlayerDesc($spanType, $kworitemID, $name, $playerID = -1){
    if($playerID == -1){
        $playerID = $_SESSION['playerID'];
    }
    query("Update playerinfo set Description=(Description + ' ".getSpanText($spanType,$kworitemID,$name)."') where ID=".prepVar($playerID));
}
/**
 *returns the manage level of the player in the current scene,
 *which is the type in constants page.
 *Returns 0 if no manage level.
 */
function getPlayerManageLevel(){
    //only works because there is 1 job per scene
    //type is hierarchy level
    $keywordRow = query("select type, locationID from playerkeywords where ID=".prepVar($_SESSION['playerID'])." and (type=".keywordTypes::APPSHP." or type=".keywordTypes::MANAGER." or type=".keywordTypes::LORD." or type=".keywordTypes::MONARCH.")");
    //apprentice
    if($keywordRow['type'] == keywordTypes::APPSHP && $keywordRow['locationID'] == $_SESSION['currentScene']){
        return keywordTypes::APPSHP;
    }
    //manager
    else if($keywordRow['type'] == keywordTypes::MANAGER && $keywordRow['locationID'] == $_SESSION['currentScene']){
        return keywordTypes::MANAGER;
    }
    //get the current scene town and land
    $sceneRow = query("select town, land from scenes where ID=".prepVar($_SESSION['currentScene']));
    //lord
    if($keywordRow['type'] == keywordTypes::LORD && $keywordRow['locationID'] == $sceneRow['town']){
        return keywordTypes::LORD;
    }
    //lord
    else if($keywordRow['type'] == keywordTypes::MONARCH && $keywordRow['locationID'] == $sceneRow['land']){
        return keywordTypes::MONARCH;
    }
    else{
        //nothing
        return 0;
    }
}

/**
 *returns the id of the monarch of this scene
 *returns false on fail.
 */
function getMonarchId(){
    $landQuery = "(select land from scenes where ID=".prepVar($_SESSION['currentScene']).")";
    $monarchRow = query("select ID from playerkeywords where type =".keywordTypes::MONARCH." and locationID=".$landQuery);
    if($monarchRow == false){
        return false;
    }
    return $monarchRow['ID'];
}
?>