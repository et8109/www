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

function addNpcEvent($px,$py,$x,$y,$npcID,$time,$busy,&$arrayJSON){
    $dist = findDist($px,$py,$x,$y);
    if($dist < distances::personTalk && !$busy){
        //if answered
        if(isset($ans)){
            if($ans){
                _addNpcEvent(2,$npcID,$time,$px,$py,$arrayJSON);//yes
            } else if(!$ans){
                _addNpcEvent(3,$npcID,$time,$px,$py,$arrayJSON);//no
            }
        } else{
            //not answered
            _addNpcEvent(1,$npcID,$time,$px,$py,$arrayJSON);//ask q
            //askQuestion();
        }
    }
    else if($dist < distances::personNotice && !$busy){
        _addNpcEvent(0,$npcID,$time,$px,$py,$arrayJSON);//welcome
    }
}

function addEnemyEvent($px,$py,$x,$y,$enemyID,$time,/*player:*/$zone,$health,$busy,&$arrayJSON){
    $dist = findDist($px,$py,$x,$y);
    if($dist < distances::enemyAttack){
        if(_addPlayerEvent(0,$time, $zone,false)){//if player attacks
            //lower monster health
            query("update enemies set health=health-1 where id=".prepVar($enemyID)." and posx=".prepVar($x)." and posy=".prepVar($y)." and health>1");
            if(lastQueryNumRows() != 1){
                //enemy is killed
                _addEnemyEvent(2, $enemyID, $time,$px,$py,$arrayJSON);//death audio
                query("update enemies set health=3 where id=".prepVar($enemyID)." and posx=".prepVar($x)." and posy=".prepVar($y));
            }
        }
        if(!$busy){//if enemy attacks
            _addEnemyEvent(1, $enemyID, $time,$px,$py,$arrayJSON);//attacking
            //lower player health
            query("update playerinfo set health=health-1 where id=".prepVar($_SESSION['playerID']));
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
                //_addPlayerEvent(1, $time, $zone,true);//death sound as event
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
    else if($dist < distances::enemyNotice && !$busy){
        _addEnemyEvent(0, $enemyID, $time,$px,$py,$arrayJSON);//notice audio
    }
}

/**
 *overrides current event
 */
function _addNpcEvent($audio,$id,$time,$px,$py,&$arrayJSON){
    query("update npcs set start=".prepVar($time)." and finish=".prepVar($time+6)." and lastAudio=".prepVar($audio)." where id=".prepVar($id));
    $arrayJSON[] = (array(
        "event" => true,
        "npc" => true,
        "id" => $id,
        "posx" => $px,
        "posy" => $py,
        "audioType" => $audio
    ));
}
/**
 *overrides current event
 */
function _addEnemyEvent($audio,$id,$time,$px,$py,&$arrayJSON){
    query("update enemies set start=".prepVar($time)." and finish=".prepVar($time+6)." and lastAudio=".prepVar($audio)." where id=".prepVar($id));
    $arrayJSON[] = (array(
        "event" => true,
        "enemy" => true,
        "id" => $id,
        "posx" => $px,
        "posy" => $py,
        "audioType" => $audio
    ));
}

/**
 *returns false is player is busy
 *true if event added
 */
function _addPlayerEvent($audio,$time,$zone, $override){
    $checkRow = query("select 1 from playerevents where id=".prepVar($_SESSION['playerID'])." limit 1");
    if(lastQueryNumRows() == 1 && !$override){
        return false;
    }
    query("insert into playerevents (id,zone,audiotype,start,finish) values (".prepVar($_SESSION['playerID']).",".prepVar($zone).",".prepVar($audio).",".prepVar($time).",".prepVar($time+6).")");
    return true;
}

/**
 *sprite events can only be heard by the player
 */
function _addSpriteEvent($audioType,&$arrayJSON){
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
        if($newZone && $zone==0){//only 0 for now
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
            $npcResult = queryMulti("select id,posx,posy,audioURL from npcs where zone=".prepVar($zone));
            while($row = mysqli_fetch_array($npcResult)){
                $arrayJSON[] = (array(
                    "npc" => true,
                    "id" => $row['id'],
                    "posx" => $row['posx'],
                    "posy" => $row['posy'],
                    "audioURL" => $row['audioURL']
                ));
            }
            mysqli_free_result($npcResult);
            sendJSON($arrayJSON);
            return;
        }
        //set current time
        $time = time();
        //remove old player events
        query("delete from playerevents where finish<".prepVar($time));
        //get npcs in zone
        $npcResult = queryMulti("select id,posx,posy,finish,start,lastAudio from npcs where zone=".prepVar($zone));
        //loop though npcs
        while($npcRow = mysqli_fetch_array($npcResult)){
            addNpcEvent($posx, $posy, $npcRow['posx'], $npcRow['posy'], $npcRow['id'],$time, $time < $npcRow['finish'],$arrayJSON);
            if($_SESSION['lastupdateTime'] < $npcRow['start']){
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
        $enemyResult = queryMulti("select id,posx,posy,finish,start,lastAudio from enemies where zone=".prepVar($zone));
        //loop though npcs
        while($enemyRow = mysqli_fetch_array($enemyResult)){
            addEnemyEvent($posx, $posy, $enemyRow['posx'], $enemyRow['posy'], $enemyRow['id'],$time,$zone,$playerQuery['health'],$time < $enemyRow['finish'],$arrayJSON);
            if($_SESSION['lastupdateTime'] < $enemyRow['start']){
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
        //check player events
        $eventsResult = queryMulti("select id,audiotype from playerevents where zone=".prepVar($zone)." and start>=".prepVar($_SESSION['lastupdateTime']));
        while($row = mysqli_fetch_array($eventsResult)){
            $arrayJSON[] =(array(
                "event" => true,
                "player" => true,
                "id" => $row['id'],
                "audioType" => $row['audiotype']
            ));
        }
        mysqli_free_result($eventsResult);
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
        $_SESSION['lastupdateTime'] = 0;
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