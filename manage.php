<?php
include 'phpHelperFunctions.php';

$function = $_POST['function'];
switch($function){
    case('addItemToScene'):
        //must be at least an apprentice
        if(getPlayerManageLevel() < keywordTypes::APPSHP){
            sendError("You don't have permission");
        }
        if(itemTypeInScene() == -1){
            sendError("Items cannot be stored here.");
        }
        //get item id
        $idRow = query("select ID from items where playerID=".prepVar($_SESSION['playerID'])." and Name=".prepVar($_POST['Name']));
        if(is_bool($idRow)){
            sendError("You do not have a ".$_POST['Name']);
        }
        //make sure it's not a container
        $itemIsBagRow = query("select count(1) from itemkeywords where ID=".prepVar($idRow['ID'])." and type=".keywordTypes::CONTAINER);
        if($itemIsBagRow[0] > 0){
            sendError("You can't put a container into a location.");
        }
        //make sure scene has less than max items
        $numItems = query("select count(1) from itemsinscenes where sceneID=".prepVar($_SESSION['currentScene']));
        if($numItems[0] >= constants::maxSceneItems){
            sendError("This location is full already");
        }
        //remove item from player
        removeItemIdFromPlayer($idRow['ID']);
        //add item to items in scenes, along with note
        query("insert into itemsinscenes (sceneID,itemID,note) values (".prepVar($_SESSION['currentScene']).",".prepVar($idRow['ID']).",".prepVar($_POST['Note']).")");
        break;
    
    case('removeItemFromScene'):
        //must be at least manager
        if(getPlayerManageLevel() < keywordTypes::MANAGER){
            sendError("You don't have permission");
        }
        if(itemTypeInScene() == -1){
            sendError("Items cannot be removed from here.");
        }
        //get item id
        $idRow = query("select ID from items where Name=".prepVar($_POST['Name']));
        if(is_bool($idRow)){
            sendError("That item does not exist");
        }
        //make sure the player can take an item
        checkPlayerCanTakeItem();
        //remove item from scene list
        $removeRow = query("delete from itemsInScenes where sceneID=".prepVar($_SESSION['currentScene'])." and itemID=".prepVar($idRow['ID']));
        addItemIdToPlayer($idRow['ID'], $_POST['Name']);
        break;
    
    case('changeItemNote'):
        //must be at least apprentice
        if(getPlayerManageLevel() < keywordTypes::APPSHP){
            sendError("You don't have permission");
        }
        if(itemTypeInScene() == -1){
            sendError("Item notes cannot be changed here.");
        }
        $idRow = query("select ID from items where Name=".prepVar($_POST['Name']));
        if($idRow == false){
            sendError($_POST['Name']." does not exist.");
        }
        //get item id
        $itemRow = query("select count(1) from itemsinscenes where sceneID=".prepVar($_SESSION['currentScene'])." and itemID=".prepVar($idRow['ID']));
        if($itemRow[0] != 1){
            sendError($_POST['Name']." not found in this location.");
        }
        query("update itemsinscenes set note=".prepVar($_POST['Note'])." where itemID=".$idRow['ID']);
        break;
    
    case('changeSceneDesc'):
        $manageLevel = getPlayerManageLevel();
        if($manageLevel == keywordTypes::MONARCH){
            updateDescription($_SESSION['currentScene'],$_POST['desc'],spanTypes::SCENE,$keywordTypeNames);
        }
        else{
            //else, no permission
            sendError("You don't have permission");
        }
        break;
    
    case('getNewSceneDescDrafts'):
        $landRow = query("select locationID from playerkeywords where ID=".prepVar($_SESSION['playerID'])." and type=".prepVar(keywordTypes::MONARCH));
        if($landRow == false){
            sendError("You don't have permission to edit location descriptions.");
        }
        //find scenes with drafts
        $scenesRow = queryMulti("select ID,Name from scenes where land=".prepVar($landRow['locationID'])." and descdraft !=''");
        while($row = mysqli_fetch_array($scenesRow)){
            echo "</br><span style='cursor: pointer;' onclick='reviewSceneDesc(".$row['ID'].")'>".$row['Name']."</span>";
        }
        break;
    
    case('getManageSceneText'):
        //find player manage level
        $manageLevel = getPlayerManageLevel();
        //can't manage anything
        if($manageLevel == 0){
            sendError("You cannot manage this location");
        }
        echo "<span class='active action' onclick='quitJobPrompt()'>quit job</span>";
        echo "<><span class='active action' onclick='getItemsInScene()'>view items</span>";
        //if items are accepted
        if(itemTypeInScene() != -1){
            echo "<><span class='active action' onclick='addItemToScenePrompt()'>add item</span>";
            echo "<><span class='active action' onclick='changeItemNotePrompt()'>change an items note</span>";
            if ($manageLevel >= keywordTypes::MANAGER) {
                //at least manager
                echo "<><span class='active action' onclick='removeItemFromScenePrompt()'>take item</span>";
            }
        }
        if ($manageLevel >= keywordTypes::LORD) {
            //at least lord
            //echo "";
        }
        if ($manageLevel >= keywordTypes::MONARCH) {
            //at least monarch
            echo "<><span class='active action' onclick='newSceneDescPrompt()'>edit scene desc</span>";
            echo "<>can't edit scene title yet";
        }
        break;
    
    case('becomeManager'):
        //make sure there are jobs here
        if(!checkLocationAcceptsApprentice()){
            sendError("There are no jobs here."); 
        }
        //make sure there is no manager already
        $positionRow = query("select count(1) from playerkeywords where type=".keywordTypes::MANAGER." and locationID=".prepVar($_SESSION['currentScene']));
        if($positionRow[0] == 1){
            sendError("Someone is already a manager here.");
        }
        //make sure they don't have a job
        if(checkPlayerHasJob()){
            sendError("You already have a job.");
        }
        //add keyword
        addKeywordToPlayer(8,keywordTypes::MANAGER,$_SESSION['currentScene'],$_SESSION['playerID']);
        //let employee know
        addAlert(alertTypes::newJob);
        //let above and below know
        alertOfNewPosition(keywordTypes::MANAGER);
        break;
    
    case("hireEmployee"):
        //get employeeID
        $IdRow = query("select ID from playerinfo where Name=".prepVar($_POST['name']));
        if($IdRow == false){
            sendError($_POST['name']." was not found");
        }
        $employeeID = $IdRow['ID'];
        $employeeKeywordRow = query("select type,locationID from playerkeywords where ID=".prepVar($employeeID)." and (type=".keywordTypes::APPSHP." or type=".keywordTypes::MANAGER." or type=".keywordTypes::LORD." or type=".keywordTypes::MONARCH.")");
        if($employeeKeywordRow != false){
            sendError("They already have a job");
        }
        $manageRow = query("select type,locationID from playerkeywords where ID=".prepVar($_SESSION['playerID'])." and (type=".keywordTypes::APPSHP." or type=".keywordTypes::MANAGER." or type=".keywordTypes::LORD." or type=".keywordTypes::MONARCH.")");
        //player has no job
        if($manageRow == false){
            sendError("You have no job");
        }
        $playerManageLevel = $manageRow['type'];
        if($playerManageLevel == keywordTypes::APPSHP){
            sendError("You cannot hire anyone to work for you");
        }
        if($playerManageLevel == keywordTypes::MANAGER){
            if($manageRow['locationID'] != $_SESSION['currentScene']){
                sendError("You don't work here");
            }
            if(!checkLocationAcceptsApprentice()){
               sendError("There are no jobs here"); 
            }
            $startingKeywordID = 7;
            $position = keywordTypes::APPSHP;
            $location = $_SESSION['currentScene'];
        }
        if($playerManageLevel == keywordTypes::LORD){
            //make sure they work here
            $townRow = query("select town from scenes where ID=".prepVar($_SESSION['currentScene']));
            if($manageRow['locationID'] != $townRow['town']){
                sendError("You don't rule this town");
            }
            if(!checkLocationAcceptsApprentice()){
               sendError("There are no jobs here"); 
            }
            //make sure there is no manager already
            $positionRow = query("select count(1) from playerkeywords where type=".keywordTypes::MANAGER." and locationID=".prepVar($_SESSION['currentScene']));
            if($positionRow[0] == 1){
                sendError("Someone already has that position");
            }
            $startingKeywordID = 8;
            $position = keywordTypes::MANAGER;
            $location = $_SESSION['currentScene'];
        }
        if($playerManageLevel == keywordTypes::MONARCH){
            //get id of current town,land
            $townRow = query("select town,land from scenes where ID=".prepVar($_SESSION['currentScene']));
            //make sure they work here
            if($manageRow['locationID'] != $townRow['land']){
                sendError("You don't rule this land");
            }
            //make sure there is no lord already
            $positionRow = query("select count(1) from playerkeywords where type=".keywordTypes::LORD." and locationID=".prepVar($townRow['town']));
            if($positionRow[0] == 1){
                sendError("Someone already has that position");
            }
            $startingKeywordID = 9;
            $position = keywordTypes::LORD;
            $location = $townRow['town'];
        }
        //add keyword
        addKeywordToPlayer($startingKeywordID,$position,$location,$employeeID);
        //let employee know
        addAlert(alertTypes::newJob, $employeeID);
        //let above and below know
        alertOfNewPosition($position);
        break;
    
    case("fireEmployee"):
        //get employee ID
        $employeeRow = ("select ID from playerinfo where Name=".prepVar($_POST['name']));
        if($employeeRow == false){
            sendError("Player not found");
        }
        $managerRow = query("select type, locationID from playerkeywords where ID=".prepVar($_SESSION['playerID'])." and (type=".keywordTypes::APPSHP." or type=".keywordTypes::MANAGER." or type=".keywordTypes::LORD." or type=".keywordTypes::MONARCH.")");
        if($managerRow == false){
            sendError("You have no job");
        }
        $managerLevel = intval($managerRow['type']);
        switch($managerLevel){
            case(keywordTypes::APPSHP):
                sendError("You don't have any employees");
                break;
            case(keywordTypes::MANAGER):
                //make sure they work for you
                $jobRow = query("select count(1) from playerkeywords where ID=".prepVar($employeeRow['ID'])." and locationID=".prepVar($managerRow['locationID'])." and type=".keywordTypes::APPSHP);
                if($jobRow[0] != 1){
                    sendError("Player does not work for you");
                }
                break;
            case(keywordTypes::LORD):
                //find the location ID of the manager
                $locationRow = query("select locationID from playerkeywords where ID=".prepVar($employeeRow['ID'])." and type =".keywordTypes::MANAGER);
                if($locationRow == false){
                    sendError("Player does not work for you");
                }
                //make sure they work for you
                $jobRow = query("select town from scenes where ID=".prepVar($locationRow['locationID']));
                if(intval($jobRow['town']) != $managerRow['locationID']){
                    sendError("Player does not work for you");
                }
                break;
            case(keywordTypes::MONARCH):
                //find the location ID of the lord
                $locationRow = query("select locationID from playerkeywords where ID=".prepVar($employeeRow['ID'])." and type =".keywordTypes::LORD);
                if($locationRow == false){
                    sendError("Player does not work for you");
                }
                //make sure they work for you
                $jobRow = query("select land from scenes where ID=".prepVar($locationRow['locationID']));
                if(intval($jobRow['land']) != $managerRow['locationID']){
                    sendError("Player does not work for you");
                }
                break;
        }
        //on success:
        query("delete from playerkeywords where ID=".prepVar($employeeRow['ID'])." and (type=".keywordTypes::APPSHP." or type=".keywordTypes::MANAGER." or type=".keywordTypes::LORD." or type=".keywordTypes::MONARCH.")");
        //give alert to fired employee
        addAlert(alertTypes::fired,$employeeRow['ID']);
        //alert above and below
        alertOfFiredPosition($managerLevel-1);
        break;
    
    case('quitJob'):
        //make sure player has a job
        if(!checkPlayerHasJob()){
            sendError("You have no job");
        }
        //get job type and location
        $jobRow = query("select type from playerkeywords where ID=".prepVar($_SESSION['playerID'])." and (type=".keywordTypes::APPSHP." or type=".keywordTypes::MANAGER." or type=".keywordTypes::LORD." or type=".keywordTypes::MONARCH.")");
        $jobType = intval($jobRow['type']);
        //remove job
        query("delete from playerkeywords where ID=".prepVar($_SESSION['playerID'])." and type=".$jobRow['type']);
        //let above and below know
        alertOfQuitPosition($jobType);
        break;
}

/**
 *returns true if the player has a job, false if not
 */
function checkPlayerHasJob(){
    //make sure player has no job
    $playerRow = query("select count(1) from playerkeywords where ID=".prepVar($_SESSION['playerID'])." and (type=".keywordTypes::APPSHP." or type=".keywordTypes::MANAGER." or type=".keywordTypes::LORD." or type=".keywordTypes::MONARCH.")");
    return ($playerRow[0] > 0);
}

/**
 *returns true if the locations accepts apprentices, false if not
 */
function checkLocationAcceptsApprentice(){
    //make sure the location accepts/has room for apprentice
    $sceneRow = query("select count(1) from scenes where ID=".prepVar($_SESSION['currentScene'])." and appshp=1");
    return ($sceneRow[0] > 0);
}

/**
 *sends an email to the player after checking email settings
 */
function sendEmail($playerID, $header, $body){
    //check email settings**
    //headers
    $headerSubject = "Ignatym: ";
    $headerBody = "**If you do not know where this email came from, please disregard it. Sorry!**
    
    This email is from Ignatym. You can change your email settings by logging in.
    
    ";
    $footnoteBody = "
    Contact us though the forums at ignatym.freeforums.net or contact@ignatym.com
    Have a nice day!";
    
    //send email
    mail($playerID, $headerSubject.$header, $headerBody.$body.$footnoteBody);
    
}

/**
 *lets those above and below the new worker know of the change
 */
function alertOfNewPosition($keywordType){
    $lowerResult;
    $higherResult;
    setLadderPositions($keywordType,$lowerResult,$higherResult);
    
    if($keywordType != keywordTypes::APPSHP){
        while($row = mysqli_fetch_array($lowerResult)){
            addAlert(alertTypes::newManager,$row['ID']);
        }
        mysqli_free_result($lowerResult);
    }
    if($keywordType != keywordTypes::MONARCH){
        while($row = mysqli_fetch_array($higherResult)){
            addAlert(alertTypes::newEmployee,$row['ID']);
        }
        mysqli_free_result($higherResult);
    }   
}
/**
 *lets those above and below the quit worker know of the change
 */
function alertOfQuitPosition($keywordType){
    $lowerResult;
    $higherResult;
    setLadderPositions($keywordType,$lowerResult,$higherResult);
    
    if($keywordType != keywordTypes::APPSHP){
        while($row = mysqli_fetch_array($lowerResult)){
            addAlert(alertTypes::managerQuit,$row['ID']);
        }
        mysqli_free_result($lowerResult);
    }
    if($keywordType != keywordTypes::MONARCH){
        while($row = mysqli_fetch_array($higherResult)){
            addAlert(alertTypes::employeeQuit,$row['ID']);
        }
        mysqli_free_result($higherResult);
    }   
}
/**
 *lets those above and below the fired worker know of the change
 */
function alertOfFiredPosition($keywordType){
    $lowerResult;
    $higherResult;
    setLadderPositions($keywordType,$lowerResult,$higherResult);
    
    if($keywordType != keywordTypes::APPSHP){
        while($row = mysqli_fetch_array($lowerResult)){
            addAlert(alertTypes::managerFired,$row['ID']);
        }
        mysqli_free_result($lowerResult);
    }
    if($keywordType != keywordTypes::MONARCH){
        while($row = mysqli_fetch_array($higherResult)){
            addAlert(alertTypes::employeeFired,$row['ID']);
        }
        mysqli_free_result($higherResult);
    }   
}
/**
 *sets the params as the two lists of playerIDs above and below the player
 */
function setLadderPositions($keywordType,&$lowerResult,&$higherResult){
    $townQuery = "(select town from scenes where ID=".prepVar($_SESSION['currentScene']);
    switch($keywordType){
        case(keywordTypes::APPSHP):
            $higherResult = queryMulti("select ID from playerkeywords where type =".keywordTypes::MANAGER." and locationID=".prepVar($_SESSION['currentScene']));
            break;
        case(keywordTypes::MANAGER):
            $lowerResult = queryMulti("select ID from playerkeywords where type=".keywordTypes::APPSHP." and locationID=".prepVar($_SESSION['currentScene']));
            
            $higherResult = queryMulti("select ID from playerkeywords where type =".keywordTypes::LORD." and locationID=".$townQuery);
            break;
        case(keywordTypes::LORD):
            $IdFromTownQuery = "(select ID from scenes where town=".$townQuery.")";
            $lowerResult = queryMulti("select ID from playerkeywords where type=".keywordTypes::MANAGER." and locationID=".$IdFromTownQuery);
            
            //same as getMonarchId function in helpers
            $landQuery = "(select land from scenes where ID=".prepVar($_SESSION['currentScene']).")";
            $higherResult = queryMulti("select ID from playerkeywords where type =".keywordTypes::MONARCH." and locationID=".$landQuery);
            break;
        case(keywordTypes::MONARCH):
            $lowerResult = queryMulti("select ID from playerkeywords where type=".keywordTypes::LORD." and locationID=".$townQuery);
            break;
    }
    
}

/**
 *returns the keyword type that items need to be placed in this scene
 *-1 means no items accepted
 *0 means all items accepted
 */
function itemTypeInScene(){
    //check scene keywords
    $sceneRow = queryMulti("select keywordID from scenekeywords where ID=".prepVar($_SESSION['currentScene']));
    while($row = mysqli_fetch_array($sceneRow)){
        if($row['keywordID'] == 11){
            //pub
            return 0;
        }
    }
    return -1;
}
?>