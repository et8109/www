<?php

session_start();
$fileName = $_SESSION['currentScene']."Chat.txt";

//create new chat if none exists
if(!file_exists($fileName)){
    $chatFile = fopen($fileName, "w");
        fwrite($chatFile, "0");
        fwrite($chatFile, "\r\n 0");
        fwrite($chatFile, "\r\n tester");
        fwrite($chatFile, "\r\n new scene filler");
    for($i=1; $i<10; $i++){
        fwrite($chatFile, "\r\n 0");
        fwrite($chatFile, "\r\n 0");
        fwrite($chatFile, "\r\n tester");
        fwrite($chatFile, "\r\n new scene filler");
    }
    fclose($chatFile);
}
          
$function = $_GET['function'];
switch($function){
    //make an array from the current chat, and rewrite from second line, adding most recent one
    case('speak'):
        $time=date_timestamp_get(new DateTime());
        $lines = array();
        $lines = file($GLOBALS['fileName']);
        $chatFile = fopen($GLOBALS['fileName'], "w");
        for($i=4; $i<40; $i++){
            fwrite($chatFile,$lines[$i]);
        }
        fwrite($chatFile,"\r\n".$time."\r\n".$_SESSION['playerID']."\r\n".$_SESSION['playerName']."\r\n".$_GET['inputText']);
        fclose($chatFile);
        break;
    
    //finds the last line not yet seen, and begins to echo from there
    case ('updateChat'):
        $lines = array();
        $lines = file($GLOBALS['fileName']);
        $i=0;
        //lines alredy seen
        while($i<=36 && intval($lines[$i])<=$_SESSION['lastChatTime']){
            $i+=4;
        }
        //new lines
        while($i<=36 && isset($lines[$i])){
            echo $lines[++$i]." ".$lines[++$i]." ".$lines[++$i];
            $i++;
        }
        $_SESSION['lastChatTime']= intval($lines[36]);
        break;
}
?>