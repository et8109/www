<?php
require_once 'database.php';
require_once 'util.php';
$arr = [];

if(!isset($_SESSION['opp'])){
    addError($arr, "No opponent found.");
    sendJSON($arr);
}

if($_GET['req'] == "mine"){
    //check actions
    //apply actions
    $cid = $_GET['cid'];
}

if($_GET['req'] == "thiers"){
    //get actions
    //get next draw
}
?>