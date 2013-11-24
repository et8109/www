<?php
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
 */
function query($sql){
    $result = mysqli_query($GLOBALS['con'], $sql);
    if(is_bool($result)){
        return;
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
            $text .= "<>".$targetID."<>".getSpanText(spanTypes::PLAYER,$_SESSION['playerID'],$_SESSION['playerName']).$actionWords.getSpanText(spanTypes::PLAYER,$targetID,$targetName);
            break;
    }
    addChatText($text);
}

/**
 *returns the span text for the given object.
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
 *Gets the combat level of the player with the playerID.
 *Does not check if the player is nearby
 */
function getCombatLevel($playerID){
    //set initial
    $playerCombatLevel = 0;
    //get player item ids
    $rowItemIds = queryMulti("select ID from items where playerID=".prepVar($_SESSION['playerID']));
    //if player has no items
    if(is_bool($rowItemIds)){
        mysqli_free_result($rowItemIds);
    }
    else{
        //get keywords from items
        $itemRow = mysqli_fetch_array($rowItemIds);
        $multiQuery = "select keywordID from itemKeywords where itemID=".prepVar($itemRow['ID'])
        while($itemRow = mysqli_fetch_array($rowItemIds)){
            $multiQuery .= "or ".prepVar($itemRow['ID']);
        }
        mysqli_free_result($rowItemIds);
        $keywordIdRows = queryMulti($multiQuery);
        //if items have no keywords
        if(is_bool($keywordIdRows)){
            mysqli_free_result($keywordIdRows);
        }
        else{
            //combat math, items
            while($keywordRow = mysqli_fetch_array($rowItemIds)){
                if(isset( $GLOBALS['combatItemKeywords'][$keywordRow['keywordID']] )){
                    $playerCombatLevel += $GLOBALS['combatItemKeywords'][$keywordRow['keywordID']];
                }
            }
        }
    }
    return $playerCombatLevel;
}
?>