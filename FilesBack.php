<?php

include 'phpHelperFunctions.php';

$fileName = "chats/".$_SESSION['currentScene']."Chat.txt";

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
          
$function = $_POST['function'];
switch($function){
    //make an array from the current chat, and rewrite from second line, adding most recent one
    case('speak'):
        _addChatText($_POST['inputText'], $_SESSION['currentScene']);
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
        if(intval($lines[36]) > $_SESSION['lastChatTime']){
            $_SESSION['lastChatTime'] = intval($lines[36]);
        }
        //send alert on/off info
        $alertRow = query("select count(1) from playeralerts where playerID=".prepVar($_SESSION['playerID']));
        echo constants::numAlertsDivider.$alertRow[0];
        break;
    
    //when entering a new scene
    case ('updateChatTime'):
        updateChatTime();
        break;
}
?>