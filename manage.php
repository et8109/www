<?php

session_start();
include 'phpHelperFunctions.php';
//set connection
$con = getConnection();
$function = $_GET['function'];
switch($function){
    case('addItemToScene'):
        //must be at least an apprentice
        if(getPlayerManageLevel() < 1){
            sendError("You don't have permission");
        }
        //get item id,size
        $idRow = query("select ID, size from items where playerID=".prepVar($_SESSION['playerID'])." and Name=".prepVar($_GET['Name']));
        if(is_bool($idRow)){
            sendError("You do not have that item");
        }
        //make sure scene has less than max items
        $numItems = query("select count(1) from itemsinscenes where sceneID=".prepVar($_SESSION['currentScene']));
        if($numItems >= constants::maxSceneItems){
            sendError("This location is full already");
        }
        //remove item from player
        removeItemIdFromPlayer($idRow['ID']);
        //add item to items in scenes, along with note
        query("insert into itemsinscenes (sceneID,itemID,note) values (".prepVar($_SESSION['currentScene']).",".prepVar($idRow['ID']).",".prepVar($_GET['Note']).")");
        break;
    
    case('removeItemFromScene'):
        //must be at least manager
        if(getPlayerManageLevel() < 2){
            sendError("You don't have permission");
        }
        //get item id,size
        $idRow = query("select ID from items where Name=".prepVar($_GET['Name']));
        if(is_bool($idRow)){
            sendError("That item does not exist");
        }
        //make sure the player can take an item
        checkPlayerCanTakeItem(4);
        //remove item from scene list
        $removeRow = query("delete from itemsInScenes where sceneID=".prepVar($_SESSION['currentScene'])." and itemID=".prepVar($idRow['ID']));
        addItemIdToPlayer($idRow['ID']);
        break;
    
    case('changeItemNote'):
        //must be at least apprentice
        if(getPlayerManageLevel() < 1){
            sendError("You don't have permission");
        }
        //get item id
        $idRow = query("select ID from items where playerID=".prepVar($_SESSION['playerID'])." and Name=".prepVar($_GET['Name']));
        if(is_bool($idRow)){
            sendError("Item not found");
        }
        query("update itemsinscenes set note=".prepVar($_GET['Note'])." where itemID=".$idRow['ID']);
        break;
    
    case('changeSceneDesc'):
        //must be at least a lord
        if(getPlayerManageLevel() < 3){
            sendError("You don't have permission");
        }
        updateDescription($_SESSION['currentScene'],$_GET['desc'],spanTypes::SCENE);
        break;
    
    case('getManageSceneText'):
        //find player manage level
        $manageLevel = getPlayerManageLevel();
        //can't manage anything
        if($manageLevel == 0){
            sendError("You cannot manage this location");
        }
        echo "<span class='active action' onclick='quitJobPrompt()'>quit job</span>";
        echo "</br><span class='active action' onclick='getItemsInScene()'>view items</span>";
        echo "</br><span class='active action' onclick='addItemToScenePrompt()'>add item</span>";
        echo "</br><span class='active action' onclick='changeItemNotePrompt()'>change an items note</span>";
        if (manageLevel > 1) {
            //at least manager
            echo "</br><span class='active action' onclick='removeItemFromScenePrompt()'>take item</span>";
        }
        if (manageLevel > 2) {
            //at least lord
            echo "</br><span class='active action' onclick='newSceneDescPrompt()'>edit scene desc</span>";
        }
        if (manageLevel > 3) {
            //at least monarch
            echo "</br>can't edit scene title yet";
        }
        break;
    
    case("hireEmployee"):
        //get employeeID
        $IdRow = query("select ID from playerinfo where Name=".prepVar($_GET['name']));
        if($IdRow == false){
            sendError($_GET['name']." was not found");
        }
        $employeeID = $IdRow['ID'];
        $employeeKeywordRow = query("select type,locationID from playerkeywords where ID=".prepVar($employeeID)." and (type=".keywordTypes::APPSHP." or type=".keywordTypes::MANAGER." or type=".keywordTypes::LORD." or type=".keywordTypes::MONARCH.")");
        if($employeeKeywordRow != false){
            sendError("They already have a job");
        }
        $manageRow = query("select type,locationID from playerkeywords where ID=".prepVar($_SESSION['currentScene'])." and (type=".keywordTypes::APPSHP." or type=".keywordTypes::MANAGER." or type=".keywordTypes::LORD." or type=".keywordTypes::MONARCH.")");
        //player has no job
        if($manageRow == false){
            sendError("You have no job");
        }
        $playerManageLevel = $manageRow['type'];
        if($playerManageLevel == keywordTypes::APPSHP){
            sendError("You cannot hire anyone to work for you");
        }
        //temp: cannot hire as a lord or monarch yet
        if($playerManageLevel == keywordTypes::LORD || $playerManageLevel == keywordTypes::MONARCH){
            sendError("Higher level managers are being worked on");
        }
        //make sure there is room for them
        $sceneRow = query("select appshp from scenes where ID=".prepVar($manageRow['locationID']));
        if($sceneRow['appshp'] == 0){
            sendError("The scene cannot take apprentices");
        }
        //--Hire a new apprentice
        query("delete from playerkeywords where type=".keywordTypes::APPSHP." or type=".keywordTypes::MANAGER." or type=".keywordTypes::LORD." or type=".keywordTypes::MONARCH);
        query("insert into playerkeywords (ID,keywordID,locationID,type) values ".prepVar($employeeID).",7,".prepVar($manageRow['locationID']).",".keywordTypes::APPSHP);
        addAlert(alertTypes::newJob, $employeeID);
        break;
    
    case("fireEmployee"):
        //get employee ID
        $employeeRow = ("select ID from playerinfo where Name=".prepVar($_GET['name']));
        if($employeeRow == false){
            sendError("Player not found");
        }
        $managerRow = query("select type, locationID from playerkeywords where type=".keywordTypes::APPSHP." or type=".keywordTypes::MANAGER." or type=".keywordTypes::LORD." or type=".keywordTypes::MONARCH);
        if($managerRow == false){
            sendError("You have no job");
        }
        switch($managerRow['type']){
            case(keywordTypes::APPSHP):
                sendError("You don't have any employees");
                break;
            case(keywordTypes::MANAGER):
                //make sure they work for you
                $jobRow = query("select count(1) from playerkeywords where ID=".prepVar($employeeRow['ID'])." and locationID=".prepVar($managerRow['locationID'])." and type=".keywordTypes::APPSHP);
                if(is_int($jobRow) && $jobRow != 1){
                    sendError("Player does not work for you");
                }
                break;
            case(keywordTypes::LORD):
                sendError("You don't have any employees");
                break;
            case(keywordTypes::MONARCH):
                sendError("You don't have any employees");
                break;
        }
        //on success:
        query("delete from playerkeywords where ID=".$employeeRow['ID']." and type=".keywordTypes::APPSHP." or type=".keywordTypes::MANAGER." or type=".keywordTypes::LORD." or type=".keywordTypes::MONARCH);
        //give alert to fired employee
        break;
    
    case('quitJob'):
        //make sure player has a job
        if(!checkPlayerHasJob()){
            sendError("You have no job");
        }
        //get job type and location
        $jobRow = query("select type,locationID from playerkeywords where ID=".prepVar($_SESSION['playerID'])." and (type=".keywordTypes::APPSHP." or type=".keywordTypes::MANAGER." or type=".keywordTypes::LORD." or type=".keywordTypes::MONARCH.")");
        //remove job from keywords
        query("delete from playerkeywords where ID=".prepVar($_SESSION['playerID'])." and type=".$jobRow['type']);
        //alert higher, lower, and same level jobs
        //add alert to chenge desc
        //replacement?
        break;
}

/**
 *returns true if the player has a job, false if not
 */
function checkPlayerHasJob(){
    //make sure player has no job
    $playerRow = query("select count(1) from playerkeywords where ID=".prepVar($_SESSION['playerID'])." and (type=".keywordTypes::APPSHP." or type=".keywordTypes::MANAGER." or type=".keywordTypes::LORD." or type=".keywordTypes::MONARCH.")");
    return (is_int($playerRow) && $playerRow > 0);
}

/**
 *returns true if the locations accepts apprentices, false if not
 */
function checkLocationAcceptsApprentice(){
    //make sure the location accepts/has room for apprentice
    $sceneRow = query("select count(1) from scenes where ID=".prepVar($_SESSION['playerID'])." and appshp=1");
    return (is_int($sceneRow) && $sceneRow > 0);
}

/**
 *sends an email to the player after checking email settings
 */
function sendEmail($playerID, $header, $body){
    //check email settings**
    //headers
    $headerSubject = "TextGame: ";
    $headerBody = "**If you do not know where this email came from, please disregard it. Sorry!**
    
    This email is from the text game. You can change your email settings by logging in.
    
    ";
    $footnoteBody = "
    
    -TextGame
    Have a nice day!";
    
    //send email
    mail($playerID, $headerSubject.$header, $headerBody.$body.$footnoteBody);
    
}
?>