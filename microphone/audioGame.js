window.onerror = function(msg, url, line) {
    alert("Error: "+msg+" url: "+url+" line: "+line);
};

var peer;

window.URL = window.URL || window.webkitURL;
navigator.getMedia = (navigator.getUserMedia || navigator.webkitGetUserMedia || navigator.mozGetUserMedia || navigator.msGetUserMedia);
var audioContext = window.AudioContext || window.webkitAudioContext;

/**
 *The audiocontext for the entire page.
 */
var context = new webkitAudioContext();
/**
 *The audio source with the sound for walking.
 */
var walkObject;

var npcs=[];
var playDist = 10;

var types = {
    ambient_noise: 0,
    enemy: 1
}
function getPlayDist(o){
    if (o.type == types.ambient_noise) {
        return 12;
    }
    else if (o.type == types.enemy) {
        return 5;
    }
    return false;
}

function tickObject(o) {
    //if withing distance
    var playDist = getPlayDist(o) || log("could not tick. [play dist]");
    if (Math.abs(o.posx-posX)<playDist && Math.abs(o.posy-posY)<playDist) {
        playObject(o);
    } else{
        stopObject(o);
    }
}

function loadObject(object){
    log("requesting: "+object.audioURL);
    //hide url, prevent downloading
    //request multiple at once so its faster
    var request = new XMLHttpRequest();
    request.open("GET",object.audioURL,true/*asynchronous*/);
    request.responseType = "arraybuffer";
    request.onload = function(){
        //set play's buffer
        object.buffer = context.createBuffer(request.response, true/*make mono*/);
    }
    request.send();
}

function playObject(object){
    if (object.playing) {
        return false;
    } else{
        log("starting: "+object.audioURL);
        if (object.posx==null) {
            //no panner
            object.audioSource = createAudioSource(object.buffer,false/*no panner*/);
        } else{
            //with panner
            object.audioSource = createAudioSource(object.buffer,true/*panner*/,object.posx,object.posy,object.posz);
        }
        if (object.loop) {
            //loop or not
            object.audioSource.loop = true;
        }
        object.playing = true;
        object.audioSource.start();
        return true;
    }
}

function stopObject(object){
    if (!object.playing) {
        return false;
    } else{
        object.audioSource.stop();
        object.playing = false;
        return true;
    }
}

var updater;
var ticker;
var posX=0;
var posY=0;
var angle=Math.PI/2;
var compassConstant = (window.innerWidth/Math.PI);

var pressedW = false;
var pressedA = false;
var pressedS = false;
var pressedD = false;

var control_options = {
    WASD: 0,
    WASD_JL: 1,
    WASD_MOUSE: 2,
    MOUSE: 3
};
var controls = control_options.WASD;

//var lastMouseX=0;
//var lookSpeed = .0005;

/**
 *checks which sounds were recived and calls setAudioBuffer for them
 */
function checkUpdateResponse(response) {
    //reset npcs 
    if (response[0].newZone) {
        log("new zone");
        npcs = [];
    }
    for(j in response){
        var data = response[j];
        if (data.type == types.ambient_noise) {
            data.loop = true;
            var o = data;
            npcs.push(o);
            loadObject(o);//load all objects at once, would be faster
        }
        else if (data.type == types.enemy) {
            data.loop = false;
            var o = data;
            npcs.push(o);
            loadObject(o);//load all objects at once, would be faster
        }
        //on login
        if (data.login) {
            //create peer
            createPeer(data.peerID);
            //set position
            posX = parseInt(data.posX);
            posY = parseInt(data.posY);
            //create walk sound
            walkObject = data
            walkObject.loop = true;
            loadObject(walkObject);
        }
    }
    //nearby players
}

/**
 *started when login request is recieved
 *updates position
 *updates audio
 */
function tick(){
    if (pressedA || pressedD || pressedS || pressedW) {
        //play walk audio
        playObject(walkObject);
        //find walk angle
        var walkAngle = getWalkAngle();
        //update position based on angle
        posX+= Math.cos(walkAngle)*2;
        posY+= Math.sin(walkAngle)*2;
        //update listener position
        context.listener.setPosition(posX,posY,0/*z-coord*/);
        context.listener.setOrientation(Math.cos(angle),Math.sin(angle),0,0,0,1);
        for(i in npcs){
            tickObject(npcs[i]);
        }
    } else{
        //stop walk audio
        stopObject(walkObject);
    }
}

/**
 *returns the angle at which the player is walking
 */
function getWalkAngle() {
    if (pressedW) {
        if (pressedA) {
            //a-w-
            return angle+(Math.PI/4);
        } else if (pressedD) {
            //-w-d
            return angle-(Math.PI/4);
        } else{
            //-w-
            return angle;
        }
    } else if (pressedS) {
        if (pressedA) {
            //a-s-
            return angle+Math.PI-(Math.PI/4);
        } else if (pressedD) {
            //-s-d
            return angle+Math.PI+(Math.PI/4);
        } else{
            //-s-
            return angle+Math.PI;
        }
    } else if (pressedA) {
        //a--
        return angle+(Math.PI/2);
    } else if (pressedD) {
        //--d
        return angle-(Math.PI/2);
    }
    log("walk angle not found");
    return false;
}

//navigator.getMedia(
//    {audio: true},
//    function(localMediaStream){
//        log("got stream");
//        var mediaStreamSource = context.createMediaStreamSource(localMediaStream);
//        log("created media stream");
//        mediaStreamSource.connect(context.destination);
//        log("connected stream");
//        log("-> streaming now");
//    },
//    function(err){
//        log(err);
//    }
//);

/**
 *set up convolver
 */
/*
 * Again, the context handles the difficult bits
var convolver = context.createConvolver();

// Wiring
soundSource.connect(convolver);
convolver.connect(context.destination);

// load the impulse response asynchronously
var request = new XMLHttpRequest();
request.open("GET", "impulse-response.mp3", true);
request.responseType = "arraybuffer";

request.onload = function () {
  convolver.buffer = context.createBuffer(request.response, false);
  playSound();
}
request.send();*/

/**
 *called when the login button is pressed
 *hides login stuff, shows logout
 *creates a peer
 *sets x and y position
 *starts updater and ticker
 *initializes sounds
 */
function login(){
    var uname = document.getElementById("uname").value;
    var pass = document.getElementById("pass").value;
    sendRequest("audioGame.php",
                "function=login&uname="+uname+"&pass="+pass,
                function(response){
                    showLogout();
                    showCompass();
                    document.getElementById("uname").value="";
                    document.getElementById("pass").value="";
                    log("logged in as "+uname);
                    //start updater
                    updater = setInterval("update()", 3000);
                    ticker = setInterval("tick()",1000);
                    //set initial sounds
                    checkUpdateResponse(response);
                });
}

/**
 *started when login request is recived
 *sends current position to db
 *reacts to recieved data
 */
function update(){
    log("u: "+posX+" x "+posY);
    sendRequest("audioGame.php",
                "function=update&posx="+Math.floor(posX)+"&posy="+Math.floor(posY),
                function(response){
                    checkUpdateResponse(response);
                }
                );
}

/**
 *returns an audioSourceNode with the audioBuffer
 */
function createAudioSource(audioBuffer,hasPanner,posx,posy,posz){
    var audioSource = context.createBufferSource();
    audioSource.buffer = audioBuffer;
    if (hasPanner) {
        var panner = context.createPanner();
        panner.setPosition(posx,posy,posz);
        audioSource.connect(panner);
        panner.connect(context.destination);
    } else{
        audioSource.connect(context.destination);
    }
    return audioSource;
}

/**
 *logs the player out.
 *shows login fields
 */
function logout() {
    clearInterval(updater);
    clearInterval(ticker);
    sendRequest("audioGame.php",
                "function=logout",
                function(response){
                    if (response.success) {
                        log("logged out");
                        showLogin();
                        hideCompass();
                    }
                });
}

/**
 *changes the angle the player is facing
 */
/*function mouseMoved(e){
    var x = e.clientX - screen.width/2;
    var changeX = x - lastMouseX;
    lastMouseX = x;
    angle += changeX * lookSpeed;
    angle = angle%(Math.PI*2);
    var compass = document.getElementById("compass");
    var compassX = (screen.width/2) + (angle-Math.PI/2)*(screen.width/Math.PI)*10;
    compass.style.marginLeft = compassX + "px";
    //update audio context dir.
}*/

/**
 *tracks when the wasd keys are pressed
 */
function keyPressed(e){
    //more control options, using mouse moved above^
    //movement
    //pressed w
    if(event.keyCode == 119 || event.keyCode == 87){
        pressedW = true;
    }
    //pressed w
    else if (event.keyCode == 65 || event.keyCode == 97) {
        pressedA = true;
    }
    //pressed s
    else if (event.keyCode == 83 || event.keyCode == 115) {
        pressedS = true;
    }
    //pressed d
    else if (event.keyCode == 68 || event.keyCode == 100) {
        pressedD = true;
    }
    if (controls == control_options.WASD_JL) {
        //turning
        //pressed j
        if (event.keyCode == 74 || event.keyCode == 106) {
            updateAngle(false/*to right*/);
        }
        //pressed l
        else if (event.keyCode == 76 || event.keyCode == 108) {
            updateAngle(true/*to right*/);
        }
    }
}

/**
 *updates the compass and audio diretion
 */
function updateAngle(toRight) {
    if (toRight) {
        angle -= .2;
    } else{
        angle += .2;
    }
    if (angle>Math.PI*2) {
        angle = Math.PI*2 - angle;
    } else if (angle<0) {
        angle = Math.PI*2 + angle;
    }
    document.getElementById("compass").style.marginLeft = ((window.innerWidth/2) + (angle-Math.PI/2)*compassConstant) + "px";
}

/**
 *tracks when the wasd keys are released
 */
function keyUp(e){
    //pressed w
    if(event.keyCode == 119 || event.keyCode == 87){
        pressedW = false;
    }
    //pressed w
    else if (event.keyCode == 65 || event.keyCode == 97) {
        pressedA = false;
    }
    //pressed s
    else if (event.keyCode == 83 || event.keyCode == 115) {
        pressedS = false;
    }
    //pressed d
    else if (event.keyCode == 68 || event.keyCode == 100) {
        pressedD = false;
    }
}

/**
 *initialized the peer of the player
 */
function createPeer(peerID){
    peer = new Peer(peerID);
    peer.options.key = "kf8l60l4w3f03sor";
}

function hideCompass(){
    document.getElementById('compass').style.visibility = "hidden";
}

function showCompass() {
    document.getElementById('compass').style.visibility = "visible";
}

function showLogin(){
    document.getElementById('login').style.display="block";
    document.getElementById('logout').style.display="none";
}

function showLogout(){
    document.getElementById('login').style.display="none";
    document.getElementById('logout').style.display="block";
}

function log(msg){
    document.getElementById("log").innerHTML+="</br>"+msg;
}

/**
 *sends a request to the given url
 *if recieved json has .error, logs it
 */
function sendRequest(url,params,returnFunction){
var request = new XMLHttpRequest();
request.open("POST",url);
request.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
request.setRequestHeader("Content-length", params.length);
request.setRequestHeader("Connection", "close");
    request.onreadystatechange = function(){
        if (this.readyState==4 && this.status==200){
            //alert(this.responseText);
            if (!this.responseText) {
                return;
            }
            var json = JSON.parse(this.responseText);
            if (json.error) {
                log(json.error);
            } else{
                returnFunction(json);
            }
        }
    }
request.send(params);
}