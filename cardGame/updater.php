<?php
require_once 'database.php';
require_once 'util.php';
$arr = [];
$db = new Database();

if(!isset($_SESSION['opp'])){
    addError($arr, "No opponent found.");
    sendJSON($arr);
}

if($_GET['req'] == "mine"){
    //check actions
    try{
        $db->playSpell($_SESSION['uname'], $_GET['cid'];);
    } catch(Exception $e){
        addError($arr, $e->getMessage());
    }
}

else if($_GET['req'] == "thiers"){
    //get actions
    //get next draw
}

sendJSON($arr);

private function playSpell($user, $spellID){
    try{
        removeCards($spellID, $user);
        activateSpell($spellID, $user);
    } catch(Exception $e){
        addError($arr,$e->getMessage());
    }
    //add to activated spell list for opponent
}

/**
 *Returns true if the cards needed for the given spell are in the player's hand
 */
private function removeCards($spellID, $user){
    $needed = [];
    switch($spellID){
        case 1:
            $needed = [[cards::darkness,1]];
            break;
    }
    $db->removeFromHand($needed, $user);
}

/**
 *Activates a spell and updates depending on what it does
 */
private function activateSpell($spellID, $user){
    switch($spellID){
        case 1://???
            break;
    }
}
?>