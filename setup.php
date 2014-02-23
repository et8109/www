<?php
include 'phpHelperFunctions.php';

$version = 4;
if(intval($_POST['version']) != $version){
    sendError("You're using an old version of Ignatym. Clear your cache and try again. ".$_POST['version']."_".$version);
}

$function = $_POST['function'];
switch($function){
    case('setUp'):
        $row = query("select Scene,frontLoadAlerts,frontLoadScenes,frontLoadKeywords from playerinfo where ID=".prepVar($_SESSION['playerID']));
        echo "<>".$_SESSION['playerID'];
        echo "<>".$row['Scene'];
        echo "<>".$row['frontLoadScenes'];
        echo "<>".$row['frontLoadKeywords'];
        //add player to scene list
        query("insert into sceneplayers (sceneID,playerID,playerName) values(".prepVar($_SESSION['currentScene']).",".prepVar($_SESSION['playerID']).",".prepVar($_SESSION['playerName']).")");
        return;
    
    case('frontLoadScenes'):
        $result = queryMulti("select ID, Name, Description from scenes");
        while($row = mysqli_fetch_array($result)){
            echo "<>".$row['ID']."<>".getSpanText(spanTypes::SCENE,$row['ID'],$row['Name'])."<>".$row['Description'];
        }
        mysqli_free_result($result);
        break;
    
    case('frontLoadKeywords'):
        //keyword words to description. //type not needed
        $result = queryMulti("select Word, ID from keywordwords");
        while($row = mysqli_fetch_array($result)){
            $row2 = query("select Description from keywords where ID=".$row['ID']);
            echo "<>".$row['Word']."<>".getSpanText(spanTypes::KEYWORD,$row['ID'],$row['Word'])."<>".$row2['Description'];
        }
        mysqli_free_result($result);
        break;
}
?>