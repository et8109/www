window.onerror = function(msg, url, line) {
    alert("Error: "+msg+" url: "+url+" line: "+line);
};

var loading = true;

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

/**
 *repeated in db
 */
var types = {
    ambient_noise:0,
    enemy: 1,
    walk_audio: 2
}
//[id,url]
function loadRequestArray(requestArray, i, num){
    if (i > num) {
        return;
    }
    request = new XMLHttpRequest();
    request.open("GET",requestArray[i][1],true/*asynchronous*/);
    request.responseType = "arraybuffer";
    request.onload = function(){
        if (request.response == null) {
            log("error loading");
        }
        //set play's buffer
        requestArray[i][0].buffer.push(context.createBuffer(request.response, true/*make mono*/));
        loadRequestArray(requestArray, i+1);
    }
    request.send()
}

function playObject(object, audioNum){
    log("starting: "+object.audioURL[audioNum]);
    object.audioSource && object.audioSource.stop();
    if (object.posx==null) {
        //no panner
        object.audioSource = createAudioSource(object.buffer[audioNum],false/*no panner*/);
    } else{
        //with panner
        object.audioSource = createAudioSource(object.buffer[audioNum],true/*panner*/,object.posx,object.posy,object.posz);
    }
    if (object.loop){
        object.audioSource.loop = true;//for walking
    }
    object.audioSource.start();
    return true;
}

function stopObject(object){
    if(object && object.audioSource){
        object.audioSource.stop();
    }
    return true;
}

var updater;
var ticker;
var posX=0;
var posY=0;

/**
 *checks which sounds were recived and calls setAudioBuffer for them
 */
function checkUpdateResponse(response) {
    if (response == "") {
        return;
    }
    //reset npcs 
    if (response[0].newZone) {
        log("new zone");
        npcs = [];
        var requestArray = [];
        //load all objects at once, would be faster
        for(j in response){
            var data = response[j];
            if (data.type == types.ambient_noise) {
                npcs[data.id] = data;
                npcs[data.id].buffer = [];
                npcs[data.id].audioURL = npcs[data.id].audioURL.split(",");
                for(u in npcs[data.id].audioURL){
                    log("adding: "+data.audioURL[u]);
                    requestArray.push([npcs[data.id],npcs[data.id].audioURL[u]]);
                }
                //loadObject(data);
            }
            else if (data.type == types.enemy) {
                npcs[data.id] = data;
                npcs[data.id].buffer = [];
                npcs[data.id].audioURL = npcs[data.id].audioURL.split(",");
                for(u in npcs[data.id].audioURL){
                    requestArray.push([npcs[data.id],npcs[data.id].audioURL[u]]);
                }
                //loadObject(npcs[data.id]);
            }
            else if (data.type == types.walk_audio) {//walk audio
                data.loop = true;
                data.posx = null;
                data.posy = null;
                data.posz = null;
                data.playing = false;
                walkObject = data;
                walkObject.buffer = [];
                walkObject.audioURL = walkObject.audioURL.split(",");
                requestArray.push([walkObject,walkObject.audioURL[0]]);
            }
        }
        loadRequestArray(requestArray,0,requestArray.length-1);
    } else{
        //play events
        for(j in response){
            var data = response[j];
            if (data.event) {
                playObject(npcs[data.npcid], data.audioType);
            }
            //nearby players
        }
    }
    loading = false;
}

/**
 *started when login request is recieved
 *updates position
 *updates audio
 */
function tick(){
    if (loading) {
        return;
    }
    if (pressedA || pressedD || pressedS || pressedW) {
        //play walk audio
        if (!walkObject.playing) {
            playObject(walkObject,0);
            walkObject.playing = true;
        }
        //find walk angle
        var walkAngle = getWalkAngle();
        //update position based on angle
        posX+= Math.cos(walkAngle)*2;
        posY+= Math.sin(walkAngle)*2;
        //update listener position
        context.listener.setPosition(posX,posY,0/*z-coord*/);
        context.listener.setOrientation(Math.cos(angle),Math.sin(angle),0,0,0,1);
    } else{
        //stop walk audio
        if (walkObject.playing) {
            stopObject(walkObject);
            walkObject.playing = false;
        }
    }
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
                    //create peer
                    createPeer(response.peerID);
                    //set position
                    posX = parseInt(response.posX);
                    posY = parseInt(response.posY);
                    //start updater
                    updater = setInterval("update()", 3000);
                    ticker = setInterval("tick()",1000);
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
 *initialized the peer of the player
 */
function createPeer(peerID){
    peer = new Peer(peerID);
    peer.options.key = "kf8l60l4w3f03sor";
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
            //log("response: "+this.responseText);
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