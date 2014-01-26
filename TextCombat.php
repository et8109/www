<?php

session_start();
include 'phpHelperFunctions.php';
//set connection
$con = getConnection();
$function = $_POST['function'];
switch($function){
    
    case('getDesc'):
        switch($_POST['type']){
            case(spanTypes::ITEM):
                $row = query("select Name, Description from items where ID=".prepVar($_POST['ID']));
                echo getSpanText(spanTypes::ITEM,$_POST['ID'],$row["Name"])."<>".$row["Description"];
                break;
            case(spanTypes::KEYWORD):
                $row2 = query("select ID from keywordwords where Word=".prepVar($_POST['ID']));
                $row = query("select Description from keywords where ID=".$row2['ID']);
                echo getSpanText(spanTypes::KEYWORD,$_POST['ID'],$_POST['ID'])."<>".$row["Description"];
                break;
            case(spanTypes::PLAYER):
                //if no id is set, make it the player
                $ID = isset($_POST['ID']) ? $_POST['ID'] : $_SESSION['playerID'];
                $row = query("select Name, Description from playerinfo where ID=".prepVar($ID));
                echo getSpanText(spanTypes::PLAYER,$ID,$row["Name"])."<>".$row["Description"];
                break;
            case(spanTypes::SCENE):
                //if no id set, it's the current scene
                $ID = isset($_POST['ID']) ? $_POST['ID'] : $_SESSION['currentScene'];
                $row = query("select Name, Description, appshp from scenes where ID=".prepVar($ID));
                echo getSpanText(spanTypes::SCENE,$ID,$row["Name"])."<>".$row["Description"];
                //managing the scene
                /*$manageLevel = getPlayerManageLevel();
                if($manageLevel > 0){
                    echo " ".getSpanTextManagingScene($manageLevel);
                }
                //apply for apprenticeship
                else if($row['appshp'] == true){
                    echo " ".getSpanTextApplyForAppshp();
                }*/
                break;
        }
        break;
    
    case('updateDescription'):
        $success = updateDescription($_SESSION['playerID'], $_POST['Description'], spanTypes::PLAYER);
        if($success){
            removeAlert(alertTypes::newItem);
            removeAlert(alertTypes::removedItem);
            removeAlert(alertTypes::hiddenItem);
        }
        break;
    
    case('moveScenes'):
        //remove player from last scene list
        query("delete from sceneplayers where sceneID=".prepVar($_SESSION['currentScene'])." and playerID=".prepVar($_SESSION['playerID']));
        //recieve id or name of scene, update this players location in cookie and db
        $_SESSION['currentScene'] = $_POST['newScene'];
        query("Update playerinfo set Scene=".prepVar($_POST['newScene'])." where ID=".prepVar($_SESSION['playerID']));
        $row = query("select Name from scenes where ID=".$_POST['newScene']);
        speakAction(actionTypes::WALKING, $row['Name'], $_POST['newScene']);
        updateChatTime();
        //add player to new scene list
        query("insert into sceneplayers (sceneID,playerID,playerName) values(".prepVar($_SESSION['currentScene']).",".prepVar($_SESSION['playerID']).",".prepVar($_SESSION['playerName']).")");
        break;
    
    case('putItemIn'):
        $itemName = prepVar($_POST['itemName']);
        $containerName = prepVar($_POST['containerName']);
        //get item and container info
        $itemRow = query("select ID,insideOf from items where playerID=".prepVar($_SESSION['playerID'])." and Name=".$itemName);
        $containerRow = query("select room,ID from items where playerID=".prepVar($_SESSION['playerID'])." and Name=".$containerName);
        //make sure item was found
        if(!isset($itemRow['ID'])){
            sendError("the ".$itemName." was not found");
        }
        //make sure container was found
        if(!isset($containerRow['ID'])){
            sendError("the ".$containerName." was not found");
        }
        //make sure second item is a container
        if($containerRow['room'] == 0){
            sendError("either ".$containerName." is full, or it can not hold any items");
        }
        //make sure the first item is not in something else
        if($itemRow['insideOf'] != 0){
            sendError($itemName." is inside of something else. Remove it first.");
        }
        //put in
        query("update items set insideOf=".prepVar($containerRow['ID'])." where ID=".prepVar($itemRow['ID']));
        query("update items set room=".prepVar(intval($containerRow['room'])-1)." where ID=".prepVar($containerRow['ID']));
        //add alert
        addAlert(alertTypes::hiddenItem);
        break;
    
    case('takeItemFrom'):
        $itemRow = query("select ID,insideOf from items where playerID=".prepVar($_SESSION['playerID'])." and Name=".prepVar($_POST['itemName']));
        $containerRow = query("select room,ID from items where playerID=".prepVar($_SESSION['playerID'])." and Name=".prepVar($_POST['containerName']));
        if($itemRow == false){
            sendError("could not find ".$_POST['itemName']);
        }
        if($containerRow == false){
            sendError("could not find ".$_POST['containerName']);
        }
        //make sure item is in the container
        if($itemRow['insideOf'] != $containerRow['ID']){
            sendError("The ".$_POST['itemName']." is not in the ".$_POST['containerName']);
        }
        //take out
        query("update items set insideOf=0 where ID=".prepVar($itemRow['ID']));
        query("update items set room=".prepVar(intval($containerRow['room'])+1)." where ID=".prepVar($containerRow['ID']));
        //add name to desc
        addItemIdToPlayer($itemRow['ID'],$_POST['itemName']);
        break;
    
    case('getItemsInScene'):
        //get item ids
        $itemIDsResult = queryMulti("select itemID,note from itemsinscenes where sceneID=".$_SESSION['currentScene']);
        //store itemID note connection
        $itemNotes = array();
        //get items names and ids
        if($row = mysqli_fetch_array($itemIDsResult)){
            $itemNamesQuery = "select ID,Name from items where ID=".$row['itemID'];
            $itemNotes[$row['itemID']] = $row['note'];
        }
        else{
            //no items in the scene
            echo "";
            mysqli_free_result($itemIDsResult);
            return;
        }
        while($row = mysqli_fetch_array($itemIDsResult)){
            $itemNamesQuery .=" or ".$row['itemID'];
            $itemNotes[$row['itemID']] = $row['note'];
        }
        mysqli_free_result($itemIDsResult);
        $itemNamesResult = queryMulti($itemNamesQuery);
        //seperate into <>
        while($row = mysqli_fetch_array($itemNamesQuery)){
            echo getSpanText(spanTypes::ITEM,$row['ID'],$row['Name'])."<>";
            echo $itemNotes[$row['ID']];
        }
        mysqli_free_result($itemNamesQuery);
        break;
    
    //gets the id of any player from the same scene. scene is indexed in mysql
    case('getPlayerIDFromScene'):
        $row = query("SELECT ID FROM playerinfo WHERE Scene =".prepVar($_SESSION['currentScene'])." AND Name = ".prepVar($_POST['Name']));
        echo $row['ID'];
        break;
    
    //used for /self
    case('getPlayerInfo'):
        //info
        $playerRow = query("select Name,craftSkill from playerinfo where ID=".prepVar($_SESSION['playerID']));
        if($playerRow == false){
            sendError("Error finding your stats.");
        }
        echo "Name: ".$playerRow['Name'];
        echo "<>ID: ".$_SESSION['playerID'];
        echo "<>Craft skill: ".$playerRow['craftSkill'];
        //job
        $jobRow = query("select locationID,type from playerkeywords where playerID=".prepVar($_SESSION['playerID'])." and type=".keywordTypes::APPSHP." or type=".keywordTypes::MANAGER." or type=".keywordTypes::LORD." or type=".keywordTypes::MONARCH);
        if($jobRow == false){
            echo "<>No Job";
        }
        else{
            //find name of lcoation
            switch(intval($jobRow['type'])){
                case(keywordTypes::APPSHP):
                    $locationRow = query("select Name from scenes where ID=".prepVar($jobRow['locationID']));
                    echo "Apprentice at ".$locationRow['Name'];
                    break;
                case(keywordTypes::MANAGER):
                    $locationRow = query("select Name from scenes where ID=".prepVar($jobRow['locationID']));
                    echo "Manager at ".$locationRow['Name'];
                    break;
                case(keywordTypes::LORD):
                    $locationRow = query("select count(1) from scenes where town=".prepVar($jobRow['locationID']));
                    echo "Lord of ".$locationRow[0]." locations";
                    break;
                case(keywordTypes::MONARCH):
                    $locationRow = query("select count(1) from scenes where land=".prepVar($jobRow['locationID']));
                    echo "Monarch of [in progress]";
                    break;
            }
        }
        //keywords
        $keywordsResult = queryMulti("select keywordID,locationID,type from playerkeywords where ID=".prepVar($_SESSION['playerID']));
        $row;
        if(!$row = mysqli_fetch_array($keywordsResult)){
            //no keywords
            echo "<>No keywords";
            mysqli_free_result($keywordsResult);
        }
        else{
            echo "<>Keywords:";
            do{
                //get the description of the keyword type
                echo "<>- ".$keywordTypeNames[$row['type']];
                //find first keyword option
                $wordRow = query("select word from keywordwords where ID=".prepVar($row['keywordID'])." limit 1");
                echo ": ".$wordRow['word'];
                //find location name, if applicable
                if($row['locationID'] != 0){
                    $locationRow = query("select name from scenes where ID=".prepVar($row['locationID']));
                    echo ", ".$locationRow['name'];
                }
            }while($row = mysqli_fetch_array($keywordsResult));
            mysqli_free_result($keywordsResult);
        }
        //items
        $itemsResult = queryMulti("select name from items where playerID=".prepVar($_SESSION['playerID']));
        $row;
        if(!$row = mysqli_fetch_array($itemsResult)){
            //no items
            echo "<>No items";
            mysqli_free_result($itemsResult);
        }
        else{
            echo "<>Items:<>";
            echo $row['name'];
            while($row = mysqli_fetch_array($itemsResult)){
                echo ", ".$row['name'];
            }
            mysqli_free_result($itemsResult);
        }
        break;
    
    case('setFrontLoadAlerts'):
        query("update playerinfo set frontLoadAlerts=".$_POST['load']." where ID=".prepVar($_SESSION['playerID']));
        break;
    
    case('setFrontLoadScenes'):
        query("update playerinfo set frontLoadScenes=".$_POST['load']." where ID=".prepVar($_SESSION['playerID']));
        break;
    
    case('setFrontLoadKeywords'):
        query("update playerinfo set frontLoadKeywords=".$_POST['load']." where ID=".prepVar($_SESSION['playerID']));
        break;
    
    case('getAlertMessages'):
        //get all alert ids
        $result = queryMulti("select alertID from playeralerts where playerID=".prepVar($_SESSION['playerID']));
        while($row = mysqli_fetch_array($result)){
            //get alert message and append
            $row2 = query("select Description from alerts where ID=".prepVar($row['alertID']));
            echo "</br>".$row2['Description'];
        }
        mysqli_free_result($result);
        break;
    
    case('login'):
        //make sure they are not logged in
        if(isset($_SESSION['playerID'])){
            sendError("You already logged in. Try refreshing the page.");
        }
        //sanitize
        $uname = $_POST['uname'];
        $pass = $_POST['pass'];
        if($uname == null || $uname == ""){
            sendError("Enter a valid username");
        }
        if($pass == null || $pass == ""){
            sendError("Enter a valid password");
        }
        //get username, password
        $playerRow = query("select ID,Name,Scene from playerinfo where Name=".prepVar($uname)." and password=".prepVar($pass));
        if($playerRow == false){
            sendError("Incorrect username or password");
        }
        //select needed info from playerinfo
        $_SESSION['playerID'] = $playerRow['ID'];
        $_SESSION['playerName'] = $playerRow['Name'];
        $_SESSION['lastChatTime'] = date_timestamp_get(new DateTime());
        $_SESSION['currentScene'] = $playerRow['Scene'];
        header("Location: index.php");
        break;
    
    case('register'):
        //make sure they are not logged in
        if(isset($_SESSION['playerID'])){
            sendError("You already logged in. Try refreshing the page.");
        }
        //check amount of players
        $numPlayers = query("select count(1) from playerinfo");
        if($numPlayers[0] > 2){
            sendError("Sorry, max amount of players reached. Check the updates for when we can let more in.");
        }
        //sanitize
        $uname = $_POST['uname'];
        $pass = $_POST['pass'];
        $pass2 = $_POST['pass2'];
        //check password similarity
        if($pass != $pass2){
            sendError("Your passwords don't match");
        }
        //check players for name
        $sharedNameRow = query("select ID from playerinfo where Name=".prepVar($uname));
        if($sharedNameRow != false){
            sendError("Someone already has that name");
        }
        //add player
        $playerID = lastIDQuery("insert into playerinfo (Name,Password,Description,Scene)values(".prepVar($uname).",".prepVar($pass).",".prepVar("I am new, so be nice to me!").",".constants::startSceneID.")");
        $_SESSION['playerID'] = $playerID;
        $_SESSION['playerName'] = $uname;
        $_SESSION['lastChatTime'] = date_timestamp_get(new DateTime());
        $_SESSION['currentScene'] = constants::startSceneID;
        break;
}
?>