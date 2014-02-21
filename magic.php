<?php

//books to spells
$bookToClass = array(
    13 => 14 //animatome to necromancer
);
$spellToClass = array{
    "reanimate" => 14 //necromancer
}
case('readBook'):
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
        echo "You open the frail pages of the leatherbound book. The first line reads: How to <b>Reanimate</b> the dead. Following is a strange sequence of instructions and illustrations.";
        break;
    
    case('learnSpell'):
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
        if($forgetRow == false){
            sendError("Unable to forget current spell.");
        }
        break;
    
    case("castSpell"):
        //make sure they have the spell
        $spellRow = query("select count(1) from playerkeywords where ID=".prepVar($_SESSION['playerID'])." and type=".prepVar(keywordTypes::SPELL)." and keywordID=".prepVar($spellToClass[$_POST['name']]));
        if($spellRow == false){
            sendError("You can't cast ".$_POST['name']);
        }
        //cast
        switch($_POST['name']){
            case('reanimate'):
                //revive nearby enemies
                $resRow = query("update set health=".prepVar(constants::maxHealth)." where ");
                echo "You hear faint grumbling as the dead nearby begin to rise.";
                break;
        }
        //respond with text
        break;
?>