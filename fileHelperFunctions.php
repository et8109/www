<?php

include 'phpHelperFunctions.php';

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

function updateChatTime(){
    $lines = array();
    $lines = file($GLOBALS['fileName']);
    if(intval($lines[36]) > $_SESSION['lastChatTime']){
        $_SESSION['lastChatTime'] = intval($lines[36]);
    }
}
?>