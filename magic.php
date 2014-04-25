<?php

include_once "phpHelperFunctions.php";

//books to spells
$bookToClass = array(
    13 => 14, //animatome to necromancer
    15 => 16  //zephytome to percipitator
);
$spellToClass = array(
    "reanimate" => 14, //necromancer
    "summon boss" => 14,
    "rainfall" => 16, //percipitator
    "sunshine" => 16
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
        switch($_POST['bookName']){
            case("animatome"):
                echo "You open the frail pages of the leatherbound book. The first line reads: How to <b>reanimate</b> the dead or <b>summon a boss</b>. Following is a strange sequence of instructions and illustrations.";
                break;
            case("zephytome"):
                echo "The pages of the ancient book feel damp, but they must be dry. The first line reads: How to call forth <b>rainfall</b> and other weather conditions. Following is a strange sequence of instructions and illustrations.";
                break;
        }
        
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
                    echo "Your spell fizzles, no effect.";
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
            
            case("rainfall"):
                //set raining constant in db
                $rainQuery = query("update constants set raining=1 where raining=0");
                if(lastQueryNumRows() == 1){
                    //speakaction that it is raining to all scenes
                    for($i=100,$n = 100+constants::numScenes; $i<$n; $i++){
                        speakActionMessage($i,"It starts raining..");
                    }
                    echo "You call down the rain from the sky";
                } else{
                    echo "It's already raining";
                }
                break;
            
            case("sunshine"):
                //set raining constant in db
                $rainQuery = query("update constants set raining=0 where raining=1");
                if(lastQueryNumRows() == 1){
                    //speakaction that it is raining to all scenes
                    for($i=100,$n = 100+constants::numScenes; $i<$n; $i++){
                        speakActionMessage($i,"The sun begins to shine though the clouds..");
                    }
                    echo "You call forth the sun to shine";
                } else{
                    echo "The sun is already out";
                }
                break;
        }
        //respond with text
        break;
}
?>