<?php

session_start();
$con = _getConnection();


final class constants {
    const zoneWidth = 50;
    const numZonesSrt = 2;//should be a square
    const secBetweenevents = 6;
    const maxHealth = 4;
}
/**
 *the distances at which certain audio starts
 */
final class distances {
    const ambientNotice = 15;
    const enemyNotice = 10;
    const enemyAttack = 4;
    const personTalk = 10;
}
/**
 *a number which descibes the general behavior of differnt npcs
 *the field in the db
 **repeated in js
 */
final class npcTypes {//audios commented
    const ambient = 0;//[listen]
    const enemy = 1;//[notice, attack]
    const walkAudio = 2;//[walk]
    const person = 3;//[]
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
function sendError($msg){
    sendJSON(array(
            "error" => $msg
        ));
}
/**
 *adds events and sends audio back to the palyer
 */
function addEvents($px,$py,$x,$y,$npcType,$npcID,$zone,$time,/*just for access:*/&$arrayJSON,$playerQuery){
    $dist = abs($px-$x);
    $dist2 = abs($py-$y);
    $dist = $dist > $dist2 ? $dist : $dist2;
    switch($npcType){
        case(npcTypes::ambient):
            if($dist < distances::ambientNotice){
                addNpcEvent(0, $npcID, $zone, $time,/*override*/false);//ambient aud
            }
            break;
        case(npcTypes::enemy):
            if($dist < distances::enemyAttack){
                if(addPlayerEvent(0, $zone, $time)){//if player attacks
                    //lower monster health
                    query("update npcs set health=health-1 where id=".prepVar($npcID)." and posx=".prepVar($x)." and posy=".prepVar($y)." and health>1");
                    if(lastQueryNumRows() != 1){
                        //enemy is killed
                        addNpcEvent(2, $npcID, $zone, $time,/*override*/true);//death audio
                        query("update npcs set health=3 where id=".prepVar($npcID)." and posx=".prepVar($x)." and posy=".prepVar($y));
                    }
                }
                if(addNpcEvent(1, $npcID, $zone, $time,/*override*/false)){//if enemy attacks
                    //lower player health
                    query("update playerinfo set health=health-1 where id=".prepVar($_SESSION['playerID']));
                    $health = $playerQuery['health'];
                    //if dead
                    if($health < 2){
                        //new coords
                        $arrayJSON[] = (array(
                            "playerInfo" => true,
                            "posX" => 0,
                            "posY" => 0
                        ));
                        //update player
                        query("update playerinfo set health=".prepVar(constants::maxHealth).",posx=0, posy=0 where id=".prepVar($_SESSION['playerID']));
                        //addPlayerEvent(1, $zone, $time);//death sound as event
                        addSpriteEvent(1, $arrayJSON);//you're dead msg
                        return;
                    }
                    //if low health
                    else if($health < 3){
                        addSpriteEvent(0, $arrayJSON);//low health msg
                        return;
                    }
                } 
            }
            else if($dist < distances::enemyNotice){
                addNpcEvent(0, $npcID, $zone, $time,/*override*/false);//notice audio
            }
            break;
        case(npcTypes::person):
            if($dist < distances::ambientNotice){
                addNpcEvent(0, $npcID, $zone, $time,/*override*/false);//person talk
            }
            break;
    }
}

/**
 *npc events can be heard by everyone nearby
 */
function addNpcEvent($audioType, $npcID, $zone, $time,$override){
    //if there is already an event fromt that npc
    $eventRow = query("select 1 from events where id=".prepVar($npcID)." and isnpc=1 limit 1");
    if($eventRow[0] == 1 && !$override){
        return false;
    }
    //if empty or override, add event
    query("insert into events (time,zone,id,audiotype,isnpc) values (".prepVar($time).",".prepVar($zone).",".prepVar($npcID).",".prepVar($audioType).",1)");
    return true;
}
/**
 *sprite events can only be heard by the player
 */
function addSpriteEvent($audioType, &$arrayJSON){
    $arrayJSON[] = (array(
        "spriteEvent" => true,
        "audioType" => $audioType
    ));
}
/**
 *player events can be heard by everyone
 */
function addPlayerEvent($audioType, $zone, $time){
    //if there is already an event fromt that player
    $eventRow = query("select 1 from events where id=".prepVar($_SESSION['playerID'])." and isnpc=0 limit 1");
    if($eventRow[0] == 1){
        return false;
    }
    query("insert into events (time,zone,id,audiotype,isnpc) values (".prepVar($time).",".prepVar($zone).",".prepVar($_SESSION['playerID']).",".prepVar($audioType).",0)");
    return true;
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
        $playerQuery = query("select zone, health from playerinfo where id=".prepVar($_SESSION['playerID']));
        $newZone = false;
        if($playerQuery['zone'] != $zone){
            $newZone = true;
        }
        //update playerinfo
        query("UPDATE playerinfo SET posx=".prepVar($posx).",posy=".prepVar($posy).",zone=".prepVar($zone)." WHERE id=".prepVar($_SESSION['playerID']));
        //prepare array to send
        $arrayJSON = array();
        //get npcs in zone
        $npcResult = queryMulti("select id,posx,posy from npcs where zone=".prepVar($zone));
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
            $npcinfo=query("select type,audioURL from npcinfo where id=".prepVar($npcRow['id']));
            //loading
            if($newZone){
                //send npc info
                $arrayJSON[] = (array(
                "success" => true,
                "id" => $npcRow['id'],
                "type" => $npcinfo['type'],
                "posx" => $npcRow['posx'],
                "posy" => $npcRow['posy'],
                "posz" => 0,
                "audioURL" => $npcinfo['audioURL']
                ));
            }
            addEvents($posx,$posy,$npcRow['posx'],$npcRow['posy'],$npcinfo['type'],$npcRow['id'],$zone,$time,/*for access:*/$arrayJSON,$playerQuery);
        }
        mysqli_free_result($npcResult);
        //check nearby players
        //return events
        $eventsResult = queryMulti("select id,audiotype,time,isnpc from events where zone=".prepVar($zone)." and time>".prepVar($_SESSION['lastEventTime']));
        while($eventRow = mysqli_fetch_array($eventsResult)){
            $arrayJSON[] = (array(
                "event" => true,
                "isnpc" => $eventRow['isnpc'],
                "id" => $eventRow['id'],
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
        $playerRow = query("select id,peerid,posx,posy,audioURL from playerinfo where uname=".prepVar($uname)." and pass=".prepVar($pass));
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
            "posY" => $playerRow['posy'],
            "spriteaudioURL" => "Lowlife.mp3,Dead.mp3",
            "playeraudioURL" => $playerRow['audioURL'],
            "playerID" => $playerRow['id']
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