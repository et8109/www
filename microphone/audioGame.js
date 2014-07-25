/*
 *Audiogame by et8109
 */


/**
 *---------------------------------------
 *Setup
 *---------------------------------------
 */

window.onload = function(){
    log("sending request");
    sendRequest("setup.php",
                "",
                function(response){
                    log("got response");
                    showOptions();
                    showCompass();
                    //load sprite and player audio
                    spriteObject.requestBuffer(response.spriteaudioURL);
                    players[response.playerID] = new audioNode();
                    players[response.playerID].requestBuffer(response.playeraudioURL);
                    loadRequestArray(requestArray);
                    //create peer
                    createPeer(response.peerID);
                    //set position
                    posX = parseInt(response.posX);
                    posY = parseInt(response.posY);
                    //start updater
                    updater = setInterval("update()", 3000);
                    ticker = setInterval("tick()",1000);
                    }
               );                  
};

/**
 *Remembers is the game is loading or not.
 *Only used for initial loading
 */
var loading = true;

/**
 *The peer2peer data for this player
 */
var peer;

//cross-platform setup
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
/**
 *The sudio source with the sounds for the sprite
 */
var spriteObject= new audioNode();
/**
 *True if an npc has just asked the player a question.
 */
var question = false;
/**
 *holds the answer the player has given until it is send to the server
 *null means no current response
 */
var answer = null;
/**
 *An array which holds all the requests to the server, which are then sent all a *t once
 *Loaded from addURLRequest
 */
var requestArray=[];

var npcs=[];
var enemies = [];
var ambient =[];
var players=[];

var updater;
var ticker;
var posX=0;
var posY=0;

/**
 *repeated in db
 */
var types = {
    ambient_noise:0,
    enemy: 1,
    walk_audio: 2,
    person: 3
}

/**
 *-----------------------------------
 *functions
 *-----------------------------------
 */

function audioNode(){
  this.URLarray = [];
  this.buffer = [];
  this.audioSource = null;
  this.posx = null;
  this.posy = null;
  this.posz = null;
  this.loop = false;
  
  this.requestBuffer=function(URLString){
    this.URLarray = URLString.split(",");
    var l = this.URLarray.length-1;//to flip it around
    for(u in this.URLarray){
        requestArray.push([this,this.URLarray[l-u]]);
    }
  }
  
  this.play=function(audioNum){
    log("starting: "+this.URLarray[audioNum]);
    this.audioSource && this.audioSource.stop();
    
    if (this.posx==null){
        //no panner
        this.audioSource = createAudioSource(this.buffer[audioNum],false/*no panner*/);
    } else{
        //with panner
        this.audioSource = createAudioSource(this.buffer[audioNum],true/*panner*/,this.posx,this.posy,this.posz);
    }
    if (this.loop){
        this.audioSource.loop = true;//for walking
    }
    this.audioSource.start();
    return true;
  }
  
  this.stop=function(){
    if(this.audioSource){
        this.audioSource.stop();
    }
    return true;
  }
}

//[id/obj,audio url]
/**
 *sends requests 1 at a time.
 */
function loadRequestArray(requestArray){
    if (!requestArray.length >0) {
        return;
    }
    var info = requestArray.pop();
    request = new XMLHttpRequest();
    request.open("GET","audio/"+info[1],true/*asynchronous*/);
    request.responseType = "arraybuffer";
    request.onload = function(){
        if (request.response == null) {
            log("error loading");
        }
        //set object's buffer: http request -> buffer
        //info[0].buffer.push(context.createBuffer(request.response, true/*make mono*/));
        info[0].buffer.push(context.decodeAudioData(request.response,function(){}/*callback function*/));
        loadRequestArray(requestArray);
    }
    request.send()
}

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
        //stop loops
        for (a in ambient) {
            ambient[a].stop();
        }
        //clear last zone
        npcs = [];
        ambient = [];
        enemies = [];
        //load data
        response.shift(); //remove first element from array
        for(j in response){
            var data = response[j];
            var node = new audioNode();
            node.requestBuffer(data.audioURL);
            if (data.ambient) {
                node.loop = true;//ambient sounds loop
                ambient.push(node);
            } else if (data.movement) {
                node.loop = true;
                node.playing = false;
                walkObject = node;
            } else if (data.enemy) {
                enemies[data.id] = node;
            } else if (data.npc) {
                npcs[data.id] = node;
            }
        }
        loadRequestArray(requestArray);
    } else{
        //not a new zone
        for(j in response){
            var data = response[j];
            if (data.event) {
                //if audio event
                if (data.npc) {
                    npcs[data.id].play(data.audioType);
                } else if(data.enemy){
                    enemies[data.id].play(data.audioType);
                } else if (data.player) {
                    players[data.id].play(data.audioType);
                }
            } else if (data.spriteEvent) {
                spriteObject.play(data.audioType);
            } else if (data.playerInfo) {
                //update position
                posX = data.posX;
                posY = data.posY;
            } else if(data.question){
                if (data.start){
                    question = true;
                }
                else if (data.done){
                    answer = null;
                }
            }
            //nearby players
            //add to players array players[id]
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
    if (question) {
        if (pressedA && pressedD) {
            answer = false;
            question = false;
        }
        else if (pressedW && pressedS) {
            answer = true;
            question = false;
        }
        return;
    }
    else if (pressedA || pressedD || pressedS || pressedW) {
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
 *starts recording, opens the button to stop, calls the param function afterwards
 */
function record(callback){
    var mediaStreamSource;
    navigator.getMedia(
        {audio: true},
        function(localMediaStream){
            mediaStreamSource = context.createMediaStreamSource(localMediaStream);
           // mediaStreamSource.connect(context.destination);
        },
        function(err){
            log(err);
            return;
        }
    );
    try{
        var recorder = new MediaRecorder(mediaStreamSource);
        recorder.record(/*length in ms: */2000);
        recorder.ondataavailable = function(blob){
            //var audio = document.createElement('audio');
            //audio.setAttribute('controls', '');
            //var audioURL = window.URL.createObjectURL(e.data);
            //audio.src = audioURL;
            callback(blob);
            recorder.stop();
        }
    } catch(err){
        log(err);
        return;
    }
}

function recordedAttack(blob){
    log("recording not yet implemented");
}

/**
 *started when login request is recived
 *sends current position to db
 *reacts to recieved data
 */
function update(){
    log("u: "+posX+" x "+posY);
    var req = "posx="+Math.floor(posX)+"&posy="+Math.floor(posY);
    if (answer != null) {
        req += "&ans="+(answer==true ? 1 : 0);
    }
    sendRequest("audioGame.php",
                req,
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
    sendRequest("logout.php",
                 "",function(){}
               );   
}

/**
 *initialized the peer of the player
 */
function createPeer(peerID){
    peer = new Peer(peerID);
    peer.options.key = "kf8l60l4w3f03sor";
}

function hideOptions(){
    document.getElementById('options').style.display="none";
}

function showOptions(){
    document.getElementById('options').style.display="block";
}

function log(msg){
    document.getElementById("log").innerHTML+="</br>"+msg;
}
