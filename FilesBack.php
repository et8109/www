<?php
$function = $_GET['function'];
switch($function){
    //make an array from the current chat, and rewrite from second line, adding most recent one
    case('speak'):
        $time=date_timestamp_get(new DateTime());
        session_start();
        $lines = array();
        $lines = file("Chat.txt");
        $chatFile = fopen("Chat.txt", "w");
        for($i=4; $i<40; $i++){
            fwrite($chatFile,$lines[$i]);
        }
        fwrite($chatFile,"\r\n".$time."\r\n".$_SESSION['playerID']."\r\n".$_SESSION['playerName']."\r\n".$_GET['inputText']);
        fclose($chatFile);
        break;
    
    //finds the last line not yet seen, and begins to echo from there
    case ('updateChat'):
        $lines = array();
        $lines = file("chat.txt");
        session_start();
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