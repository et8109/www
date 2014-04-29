<?php

session_start();
$con = _getConnection();


final class constants {
    const zoneWidth = 50;
    const numZonesSrt = 2;//should be a square
    const secBetweenevents = 6;
}
/**
 *the distances at which certain audio starts
 */
final class distances {
    const ambientNotice = 15;
    const enemyNotice = 10;
}
/**
 *a number which descibes the general behavior of differnt npcs
 *the field in the db
 **repeated in js
 */
final class npcTypes {
    const ambient = 0;
    const enemy = 1;
    const walkAudio = 2;
}

/**
 *Which audio of an npc is played
 *the position in url array
 */
final class audioTypes {
    const encounter = 0;
    const attack = 1;
}




function _getConnection(){
    $con = mysqli_connect("localhost","root","","audio_game");
    //check connection
    if (mysqli_connect_errno()){
        throw new Exception("could not connect to database");
    }
    return $con;
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
function sendError($msg){
    sendJSON(array(
            "error" => $msg
        ));
}
/**
 *returns the audioType the player is in range of w/ the given x,y coords
 *returns false if no range
 */
function inRange($px,$py,$x,$y,$npcType){
    $dist = abs($px-$x);
    $dist2 = abs($py-$y);
    $dist = $dist > $dist2 ? $dist : $dist2;
    switch($npcType){
        case(npcTypes::ambient):
            if($dist < distances::ambientNotice){
                return 0;//audioTypes::encounter;
            }
            break;
        case(npcTypes::enemy):
            if($dist < distances::enemyNotice){
                return 0;//audioTypes::attack;
            }
            break;
    }
    return false;
}

try{
switch($_POST['function']){
    
    case('update'):
        $posx = $_POST['posx'];
        $posy = $_POST['posy'];
        //find current zone
        $zone = floor($posx/constants::zoneWidth);
        $zone += constants::numZonesSrt * floor($posy/constants::zoneWidth);
        //check if zone change
        $zoneQuery = query("select 1 from playerinfo where zone!=".prepVar($zone)." and id=".prepVar($_SESSION['playerID']));
        $newZone = false;
        if($zoneQuery){
            $newZone = true;
        }
        //update playerinfo
        query("UPDATE playerinfo SET posx=".prepVar($posx).",posy=".prepVar($posy).",zone=".prepVar($zone)." WHERE id=".prepVar($_SESSION['playerID']));
        //prepare array to send
        $arrayJSON = array();
        //get npcs in zone
        $npcResult = queryMulti("select id,posx,posy,type,audioURL from npcinfo where zone=".prepVar($zone));
        //if in a new zone
        if($newZone){
            $arrayJSON[0] = array("newZone" => true);
        }
        //set current time
        $time = time();
        //remove old events
        query("delete from events where time < ".prepVar($time-constants::secBetweenevents));
        //loop though npcs
        while($npcRow = mysqli_fetch_array($npcResult)){
            //loading
            if($newZone){
                //send npc info
                $arrayJSON[] = (array(
                "success" => true,
                "id" => $npcRow['id'],
                "type" => $npcRow['type'],
                "posx" => $npcRow['posx'],
                "posy" => $npcRow['posy'],
                "posz" => 0,
                "audioURL" => $npcRow['audioURL']
                ));
            }
            //updating
            $audioType = inRange($posx,$posy,$npcRow['posx'],$npcRow['posy'],$npcRow['type']);
            //if in range
            if(is_numeric($audioType)){
                //check if event exists for this npc
                $eventRow = query("select 1 from events where npcid=".prepVar($npcRow['id'])/*." and time > ".prepVar($time-constants::secBetweenevents)*/);
                if($eventRow[0] != 1){
                    //add event
                    $audioType = 0;
                    query("insert into events (time,zone,npcid,audiotype) values (".prepVar($time).",".prepVar($zone).",".prepVar($npcRow['id']).",".prepVar($audioType).")");
                }
            }
        }
        mysqli_free_result($npcResult);
        //check nearby players
        //return events
        $eventsResult = queryMulti("select npcid,audiotype,time from events where zone=".prepVar($zone)." and time>".prepVar($_SESSION['lastEventTime']));
        while($eventRow = mysqli_fetch_array($eventsResult)){
            $arrayJSON[] = (array(
                "event" => true,
                "time" => $eventRow['time'],
                "npcid" => $eventRow['npcid'],
                "audioType" => $eventRow['audiotype']
            ));
        }
        mysqli_free_result($eventsResult);
        sendJSON($arrayJSON);
        //update last event time
        $_SESSION['lastEventTime'] = $time;
        break;
    
    case('login'):
        //make sure they are not logged in
        if(isset($_SESSION['playerID'])){
            throw new Exception("You already logged in. Try refreshing the page.");
        }
        //sanitize
        $uname = $_POST['uname'];
        $pass = $_POST['pass'];
        if($uname == null || $uname == ""){
            throw new Exception("Enter a valid username");
        }
        if($pass == null || $pass == ""){
            throw new Exception("Enter a valid password");
        }
        //get username, password
        $playerRow = query("select id,peerid,posx,posy from playerinfo where uname=".prepVar($uname)." and pass=".prepVar($pass));
        if($playerRow == false){
            throw new Exception("Incorrect username or password");
        }
        //set session
        $_SESSION['playerID'] = $playerRow['id'];
        $_SESSION['lastEventTime'] = 0;
        sendJSON(array(
            "login" => true,
            "success" => true,
            "peerID" => $playerRow['peerid'],
            "posX" => $playerRow['posx'],
            "posY" => $playerRow['posy']
        ));
        break;
    
    //called when the logout button is clicked
    case("logout"):
        if(isset($_SESSION['playerID'])){
            query("UPDATE playerinfo SET zone=".prepVar((constants::numZonesSrt*constants::numZonesSrt)+1)." WHERE id=".prepVar($_SESSION['playerID']));
        }
        session_destroy();
        sendJSON(array(
            "success" => true
        ));
        break;
}
} catch(Exception $e){
    sendJson(array(
        "error" => ($e->getMessage())
    ));
}

?>