<?php

include_once "phpHelperFunctions.php";

//books to spells
$bookToClass = array(
    13 => 14 //animatome to necromancer
);
$spellToClass = array(
    "reanimate" => 14, //necromancer
    "summon boss" => 14
);
switch($_POST['function']){
    
    case('readBook')://see book contents
        //make sure book exists
        $IdRow = query("select ID from keywordwords where Word=".prepVar(strtolower($_POST['bookName']))." and type=".prepVar(keywordTypes::SPELLBOOK));
        if($IdRow == false){
            sendError("Could not find the ".$_POST['bookName']." here.");
        }
        //make sure scene has spellbook
        $bookRow = query("select count(1) from scenekeywords where ID=".prepVar($_SESSION['currentScene'])." and type=".prepVar(keywordTypes::SPELLBOOK)." and keywordID=".prepVar($IdRow['ID']));
        if($bookRow[0] != 1){
            sendError("Could not find the ".$_POST['bookName']." here.");
        }
        //display spellbook text
        echo "You open the frail pages of the leatherbound book. The first line reads: How to <b>reanimate</b> the dead. Following is a strange sequence of instructions and illustrations.";
        break;
    
    case('learnSpell')://learn book contents
        //make sure scene has spellbook
        $IdRow = query("select ID from keywordwords where Word=".prepVar(strtolower($_POST['bookName']))." and type=".prepVar(keywordTypes::SPELLBOOK));
        if($IdRow == false){
            sendError("Could not find the ".$_POST['bookName']);
        }
        $bookRow = query("select count(1) from scenekeywords where ID=".prepVar($_SESSION['currentScene'])." and type=".prepVar(keywordTypes::SPELLBOOK)." and keywordID=".prepVar($IdRow['ID']));
        if($bookRow[0] != 1){
            sendError("Could not find the ".$_POST['bookName']." here.");
        }
        //make sure player does not have a spell
        $spellRow = query("select count(1) from playerkeywords where ID=".prepVar($_SESSION['playerID'])." and type=".prepVar(keywordTypes::SPELL));
        if($spellRow[0] == 1){
            sendError("You already know a spell. You would have to forget that one first.");
        }
        //give spell to player
        addKeywordToPlayer($bookToClass[$IdRow['ID']],keywordTypes::SPELL,0,$_SESSION['playerID']);
        //add new spell alert
        addAlert(alertTypes::newSpell);
        break;
    
    case("forgetSpell"):
        $forgetRow = query("delete from playerkeywords where ID=".prepVar($_SESSION['playerID'])." and type=".prepVar(keywordTypes::SPELL));
        break;
    
    case("castSpell"):
        if(!isset($spellToClass[$_POST['name']])){
            sendError($_POST['name']." is not a spell.");
        }
        //make sure they have the spell
        $spellRow = query("select count(1) from playerkeywords where ID=".prepVar($_SESSION['playerID'])." and type=".prepVar(keywordTypes::SPELL)." and keywordID=".prepVar($spellToClass[$_POST['name']]));
        if($spellRow[0] != 1){
            sendError("You can't cast ".$_POST['name']);
        }
        //cast
        switch($_POST['name']){
            case('reanimate'):
                //revive nearby enemies
                $resRow = query("update scenenpcs set health=".prepVar(constants::maxHealth)." where health=0 and sceneID=".prepVar($_SESSION['currentScene'])." and type=".prepVar(npcTypes::CREATURE));
                $numRisen = lastQueryNumRows();
                if($numRisen > 0){
                    echo "You give new life to ".$numRisen." dead creatures nearby.";
                } else{
                    echo "Your spell sizzles, no effect.";
                }
                break;
            case('summon boss'):
                $resRow = query("update scenenpcs set health=".prepVar(constants::maxHealth)." where health=0 and sceneID=".prepVar($_SESSION['currentScene'])." and type=".prepVar(npcTypes::BOSS));
                if(lastQueryNumRows() == 0){
                    sendError("Could not summon the boss here.");
                }
                //create hear effect nearby
                $posQuery = query("select posx, posy from scenes where ID=".prepVar($_SESSION['currentScene']));
                $currentX = $posQuery['posx'];
                $currentY = $posQuery['posy'];
                $scenes = nearbyScenes(3);
                foreach($scenes as $sceneID){
                    //get direction
                    $posQuery = query("select posx, posy from scenes where ID=".prepVar($sceneID));
                    $dir = getSceneDir($currentX,$currentY,$posQuery['posx'],$posQuery['posy']);
                    speakActionMessage($sceneID,"You hear the roar of a boss to the ".$dir);
                }
                echo "A boss risies to your challenge.";
                break;
        }
        //respond with text
        break;
}
?>