<?php
require_once 'util.php';
require_once 'database.php';
session_start();
$uname = $_SESSION['uname'];
$db = new Database();
$arr = [];
$opp = false;

if(!$db->isInMatching($uname)){
    $db->addToMatching($uname);
}
//try to find a match
try{
    $opp = $db->findMatch($uname);
    //if match found
    if($opp != false){
        $arr[] = array(
        "found" => true,
        );
    }
} catch(Exception $e){
    addError($arr,$e->getMessage());
}
sendJSON($arr);
?>