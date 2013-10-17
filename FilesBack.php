<?php
include 'phpHelperFunctions.php';
$function = $_GET['function'];
switch($function){
    //make an array from the current chat, and rewrite from second line, adding most recent one
    case('speak'):
        printDebug("speaking");
        session_start();
        $time=date_timestamp_get(new DateTime());
        $lines = array();
        $SESSION_['currentScene'] = 100;
        printDebug("-".$SESSION_['currentScene']."-");
        $fileName = $SESSION_['currentScene']."Chat.txt";
        printDebug($fileName);
        $chatFile = fopen($fileName, "w");
        $lines = file($fileName);
        printDebug($lines);
        //$chatFile = fopen($fileName, "w"); moved up
        for($i=4; $i<40 && $i-4<$lines.length; $i++){
            fwrite($chatFile,$lines[$i]);
        }
        fwrite($chatFile,"\r\n".$time."\r\n".$_SESSION['playerID']."\r\n".$_SESSION['playerName']."\r\n".$_GET['inputText']);
        fclose($chatFile);
        break;
    
    //finds the last line not yet seen, and begins to echo from there
    case ('updateChat'):
        session_start();
        $lines = array();
        $fileName = $SESSION_['currentScene']."Chat.txt";
        $lines = file($fileName);
        $i=0;
        //lines alredy seen
        while($i<=36 && intval($lines[$i])<=$_SESSION['lastChatTime']){
            $i+=4;
        }
        //new lines
        while($i<=36){
            echo $lines[++$i]." ".$lines[++$i]." ".$lines[++$i];
            $i++;
        }
        $_SESSION['lastChatTime']=intval($lines[36]);
        break;
}
?>