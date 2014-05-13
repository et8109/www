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
    const personTalk = 5;
    const personNotice = 10;
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

function addNpcEvent($px,$py,$x,$y,$npcID,$time){
    $dist = findDist($px,$py,$x,$y);
    if($dist < distances::personTalk){
        //if answered
        if(isset($ans)){
            if($ans){
                _addNpcEvent(2,$npcID,$time);//yes
            } else if(!$ans){
                _addNpcEvent(3,$npcID,$time);//no
            }
        } else{
            //not answered
            _addNpcEvent(1,$npcID,$time);//ask q
            //askQuestion();
        }
    }
    else if($dist < distances::personNotice){
        _addNpcEvent(0,$npcID,$time);//welcome
    }
}

function addEnemyEvent($px,$py,$x,$y,$enemyID,$time){
    $dist = findDist($px,$py,$x,$y);
    if($dist < distances::enemyAttack){
        if(_addPlayerEvent(0,$time)){//if player attacks
            //lower monster health
            query("update enemies set health=health-1 where id=".prepVar($enemyID)." and posx=".prepVar($x)." and posy=".prepVar($y)." and health>1");
            if(lastQueryNumRows() != 1){
                //enemy is killed
                _addEnemyEvent(2, $enemyID, $time);//death audio
                query("update enemies set health=3 where id=".prepVar($enemyID)." and posx=".prepVar($x)." and posy=".prepVar($y));
            }
        }
        if(_addNpcEvent(1, $enemyID, $time)){//if enemy attacks
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
                _addPlayerEvent(1, $time);//death sound as event
                _addSpriteEvent(1, $arrayJSON);//you're dead msg
                return;
            }
            //if low health
            else if($health < 3){
                _addSpriteEvent(0, $arrayJSON);//low health msg
                return;
            }
        } 
    }
    else if($dist < distances::enemyNotice){
        _addNpcEvent(0, $npcID, $time);//notice audio
    }
}

function _addNpcEvent($audio,$id,$time){
    query("update npcs set start=".prepVar($time)." and finish=".prepVar($time+6)." and lastAudio=".prepVar($audio)." where id=".prepVar($id));
}

function _addEnemyEvent($audio,$id,$time){
    query("update enemies set start=".prepVar($time)." and finish=".prepVar($time+6)." and lastAudio=".prepVar($audio)." where id=".prepVar($id));
}

function _addPlayerEvent($audio,$time){
    //use SESSION_['playerid']
}

/**
 *sprite events can only be heard by the player
 */
function _addSpriteEvent($audioType, &$arrayJSON){
    $arrayJSON[] = (array(
        "spriteEvent" => true,
        "audioType" => $audioType
    ));
}

/**
 *requests a yes or no from the player
 */
function askQuestion(&$arrayJSON){
    $arrayJSON[] = (array(
        "question" => true
    ));
}

function findDist($px,$py,$x,$y){
    $dist = abs($px-$x);
    $dist2 = abs($py-$y);
    if($dist > $dist2){
        return $dist;
    }
    return $dist2;
}

try{
switch($_POST['function']){
    
    case('update'):
        $posx = $_POST['posx'];
        $posy = $_POST['posy'];
        $ans = $_POST['ans'];
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
        //if in a new zone
        if($newZone){
            $arrayJSON[0] = array("newZone" => true);
            //send ambient sounds
            $ambientResult = queryMulti("select posx,posy,audioURL from ambient where zone=".prepVar($zone));
            while($row = mysqli_fetch_array($ambientResult)){
                $arrayJSON[] = (array(
                    "ambient" => true,
                    "posx" => $row['posx'],
                    "posy" => $row['posy'],
                    "audioURL" => $row['audioURL']
                ));
            }
            mysqli_free_result($ambientResult);
            //send movement sound
            $moveRow = query("select audioURL from movement where zone=".prepVar($zone));
            $arrayJSON[] = (array(
                "movement" => true,
                "audioURL" => $moveRow['audioURL']
            ));
            //send enemies
            $enemyResult = queryMulti("select id,posx,posy from enemies where zone=".prepVar($zone));
            while($row = mysqli_fetch_array($enemyResult)){
                $audioRow = query("select audioURL from enemyinfo where id=".prepVar($row['id']));
                $arrayJSON[] = (array(
                    "enemy" => true,
                    "id" => $row['id'],
                    "posx" => $row['posx'],
                    "posy" => $row['posy'],
                    "audioURL" => $audioRow['audioURL']
                ));
            }
            mysqli_free_result($enemyResult);
            //send npcs
            $npcResult = queryMulti("select id,posx,posy from npcs where zone=".prepVar($zone));
            while($row = mysqli_fetch_array($npcResult)){
                $audioRow = query("select audioURL from npcinfo where id=".prepVar($row['id']));
                $arrayJSON[] = (array(
                    "npc" => true,
                    "id" = $row['id'],
                    "posx" => $row['posx'],
                    "posy" => $row['posy'],
                    "audioURL" => $audioRow['audioURL']
                ));
            }
            mysqli_free_result($npcResult);
        }
        //set current time
        $time = time();
        //get npcs in zone
        $npcResult = queryMulti("select id,posx,posy,finish,start,lastAudio from npcs where zone=".prepVar($zone));
        //loop though npcs
        while($npcRow = mysqli_fetch_array($npcResult)){
            $npcinfo=query("select audioURL from npcinfo where id=".prepVar($npcRow['id']));
            //if free to speak
            if($time > $npcRow['finish']){
                addNpcEvent($posx, $posy, $npcRow['posx'], $npcRow['posy'], $npcRow['id'],$time);
            } else if($_SESSION['lastupdateTime'] < $npcRow['start']){
                //if new for this player
                $arrayJSON[] = (array(
                    "event" => true,
                    "npc" => true,
                    "id" => $npcRow['id'],
                    "audioType" => $npcRow['lastAudio']
                ));
            }
        }
        mysqli_free_result($npcResult);
        
        //get enemies in zone
        $enemyResult = queryMulti("select id,posx,posy,finish,start,lastAudio from npcs where zone=".prepVar($zone));
        //loop though npcs
        while($enemyRow = mysqli_fetch_array($enemyResult)){
            $npcinfo=query("select audioURL from enemies where id=".prepVar($enemyRow['id']));
            //if free to speak
            if($time > $enemyRow['finish']){
                addEnemyEvent($posx, $posy, $enemyRow['posx'], $enemyRow['posy'], $enemyRow['id'],$time);
            } else if($_SESSION['lastupdateTime'] < $enemyRow['start']){
                //if new for this player
                $arrayJSON[] = (array(
                    "event" => true,
                    "enemy" => true,
                    "id" => $enemyRow['id'],
                    "audioType" => $enemyRow['lastAudio']
                ));
            }
        }
        mysqli_free_result($enemyResult);
        //check nearby players
        sendJSON($arrayJSON);
        //update last event time
        $_SESSION['lastupdateTime'] = $time;
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