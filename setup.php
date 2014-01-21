<?php
session_start();
include 'phpHelperFunctions.php';
//set connection
$con = getConnection();
$function = $_GET['function'];
switch($function){
    case('setUp'):
        $row = query("select adminLevel,Scene,frontLoadAlerts,frontLoadScenes,frontLoadKeywords from playerinfo where ID=".prepVar($_SESSION['playerID']));
        echo "<>".$row['Scene'];
        echo "<>".$row['frontLoadAlerts'];
        echo "<>".$row['frontLoadScenes'];
        echo "<>".$row['frontLoadKeywords'];
        //find if the player has a alert
        $alertCount = query("select count(1) from playeralerts where playerID=".$_SESSION['playerID']);
        echo "<>".$alertCount[0];
        //add player to scene list
        query("insert into sceneplayers (sceneID,playerID,playerName) values(".prepVar($_SESSION['currentScene']).",".prepVar($_SESSION['playerID']).",".prepVar($_SESSION['playerName']).")");

        return;
    
    case('frontLoadAlerts'):
        //send alert types
        $result = queryMulti("select ID, Description from alerts");
        while($row = mysqli_fetch_array($result)){
            echo "<>".$row['ID']."<>".$row['Description'];
        }
        mysqli_free_result($result);
        echo "<<>>";
        //send player alerts
        $result = queryMulti("select alertID from playeralerts where playerID=".prepVar($_SESSION['playerID']));
        while($row = mysqli_fetch_array($result)){
            echo "<>".$row['alertID'];
        }
        mysqli_free_result($result);
        break;
    
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