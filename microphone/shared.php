<?php

session_start();
$con = _getConnection();

final class dbconstants {
    const dbhostName = "localhost";
    const dbusername = "ignatymc_admin";
    const dbpassword = "1Gn4tym";
    const dbname = "ignatymc_audioGame";
}


function query($sql){
    $result = mysqli_query($GLOBALS['con'], $sql);
    if(is_bool($result)){
        return false;
    }
    $numRows = mysqli_num_rows($result);
    if($numRows > 1){
        throw new Exception("q>1");
    }
    $row = mysqli_fetch_array($result);
    mysqli_free_result($result);
    return $row;
}

function queryMulti($sql){
    $result = mysqli_query($GLOBALS['con'], $sql);
    return $result;
}

function lastQueryNumRows(){
    return mysqli_affected_rows($GLOBALS['con']);
}

function prepVar($var){
    $var = mysqli_real_escape_string($GLOBALS['con'],$var);
    //replace ' with ''
    //$var = str_replace("'", "''", $var);
    //if not a number, surround in quotes
    if(!is_numeric($var)){
        $var = "'".$var."'";
    }
    return $var;
}

function sendJSON($array){
    echo json_encode($array);
}

function _getConnection(){
    $con = mysqli_connect(dbconstants::dbhostName,dbconstants::dbusername,dbconstants::dbpassword,dbconstants::dbname);
    //check connection
    if (mysqli_connect_errno()){
        throw new Exception("could not connect to database");
    }
    return $con;
}
?>