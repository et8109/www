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
 *adds an action to the current chat
 */
function speakAction($type, $targetName, $targetID){
    $text = "<".$type.">";
    switch($type){
        case(actionTypes::WALKING):
            $text .= "<>".$targetName."<>".$targetID;
            break;
        case(actionTypes::ATTACK):
            $text .= "<>".$targetName."<>".$targetID;
            break;
    }
    addChatText($text);
}

/**
 *the possible actions that are visible in chat.
 *duplicated in js
 */
final class actionTypes {
    const WALKING = 0;
    const ATTACK = 1;
}

?>