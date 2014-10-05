window.onerror = function(msg, url, line) {
    log("Error: "+msg+" url: "+url+" line: "+line);
};

var loading = true;

var peer;
var localStream;
var connections=[];

window.URL = window.URL || window.webkitURL;
navigator.getMedia = (navigator.getUserMedia || navigator.webkitGetUserMedia || navigator.mozGetUserMedia || navigator.msGetUserMedia);
var audioContext = window.AudioContext || window.webkitAudioContext;

navigator.getMedia({
      audio: true,
      video: false
    },
    function(stream){
        localStream=stream;
        var audioTracks = localStream.getAudioTracks();
        if (audioTracks.length > 0)
            log('Using Audio device: ' + audioTracks[0].label);
    },
    function(e){
      log('getUserMedia() error: ' + e.name);
    });

/**
 *The audiocontext for the entire page.
 */
var context = new webkitAudioContext();
/**
 *The audio source with the sound for walking.
 */
var walkObject;
var spriteObject=new node();
var question = false;
var answer = null;

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

window.onload = function(){
    sendRequest("setup.php",
                "",
                function(response){
                    log("starting loading")
                    response=response[0];
                    //load sprite and player audio
                    spriteObject.requestBuffer(response.spriteaudioURL);
                    players[response.playerID] = new node();
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
                    log("client version 2");
                    log("server version "+response.version);
                    }
               );
}

function node(){
    this.posx = null;
    this.posy = null;
    this.posz = null;
    this.buffers=[];
    this.audioURLs=[];
    /**
     *adds a request to requestArray to get buffers for audio urls
     *takes comma separated urls
     */
    this.requestBuffer=function(URLString){
        this.audioURLs=URLString.split(",");
        //TODO look into this
        var l = this.audioURLs.length-1;//to flip it around
        for(u in this.audioURLs){
           requestArray.push([this,this.audioURLs[l-u]]);
        }
    }
    
    this.play = function(audioNum){
        log("starting: "+this.audioURLs[audioNum]);
        this.audioSource && this.audioSource.stop();
        log(this.buffers[audioNum]);
        if (this.posx==null) {
            //no panner
            this.audioSource = createAudioSource(this.buffers[audioNum],false/*no panner*/);
        } else{
            //with panner
            this.audioSource = createAudioSource(this.buffers[audioNum],true/*panner*/,this.posx,this.posy,this.posz);
        }
        if (this.loop){
            this.audioSource.loop = true;//for walking
        }
        this.audioSource.start();
        return true;
    }
    
    this.stop = function(){
        if(this.audioSource){
            this.audioSource.stop();
        }
        return true;
    }
}

//[id,url]
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
        context.decodeAudioData(request.response,function(decoded){ //callback function
                info[0].buffers.push(decoded);
            });
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
        npcs = [];
        ambient = [];
        enemies = [];
        for(j in response){
            var data = response[j];
            if (data.ambient) {
                var n = new node();
                n.loop = true;//ambient sounds loop
                n.posx = data.posx;
                n.posy = data.posy;
                ambient.push(n);
                ambient[ambient.length-1].requestBuffer(data.audioURL);
            } else if (data.movement) {
                var n = new node();
                n.loop = true;
                n.playing = false;
                walkObject = n;
                walkObject.requestBuffer(data.audioURL);
            } else if (data.enemy) {
                var n = new node();
                n.posx = data.posx;
                n.posy = data.posy;
                enemies[data.id] = n;
                enemies[data.id].requestBuffer(data.audioURL);
            } else if (data.npc) {
                var n = new node();
                n.posx = data.posx;
                n.posy = data.posy;
                npcs[data.id] = n;
                npcs[data.id].requestBuffer(data.audioURL);
            } else if (data.player) {
                log("player found: "+data.peerid);
                if (connections[data.peerid] == null){
                    log("conn not usable. calling.");
                    connections[data.peerid] = true;
                    /*var conn = peer.connect(data.peerid);
                    conn.on('error', function(err){
                        log("connection error: ");
                        log(err);
                    });
                    conn.on('open', function(){
                        conn.send('hi!');
                        log("msg sent");
                    });*/
                    //new audio conn
                    var call = peer.call(data.peerid, localStream);
                    call.on('error', function(err){
                        log("call error: ");
                        log(err);
                    });
                    call.on('stream',function(stream){
                        log("-recieving stream: "+stream);
                        //var audioSource =
                        //connections[call.peer] =
                        createAudioSourceStream(stream,2,2,0);
                        //document.getElementById("otherAudio").setAttribute('src', URL.createObjectURL(stream));
                    });
                    //document.getElementById("playerAudio").prop('src',URL.createObjectURL(stream));
                    //var source = context.createMediaStreamSource(stream);
                }
            }
        }
        loadRequestArray(requestArray);
    } else{
        //play events
        for(j in response){
            var data = response[j];
            if (data.event) {
                //if npc event
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
                posX = parseInt(data.posX);
                posY = parseInt(data.posY);
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
        log("question")
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
        log("walking")
        //play walk audio
        if (!walkObject.playing) {
            log("start walk audio")
            walkObject.play(0);
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
            walkObject.stop();
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
    //TODO change to use localStream!!!!!!!!!!!!!!!!!!!!!!
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

function createAudioSourceStream(audioStream,posx,posy,posz){
    var audioSource = context.createMediaStreamSource(audioStream);
    //var panner = context.createPanner();
    //panner.setPosition(posx,posy,posz);
    //audioSource.connect(panner);
    //panner.connect(context.destination);
    if(!audioSource.connect(context.destination)){
        log("could not connect remote stream to context destination");
        document.getElementById("otherAudio").setAttribute('src', URL.createObjectURL(audioStream));
    }
    log("audio source created");
}

/**
 *initialized the peer of the player
 */
function createPeer(peerID){
    peer = new Peer(peerID,{key: 'kf8l60l4w3f03sor'});
    peer.on('error', function(err){
        log("peer error: ");
        log(err);
    });
    /*peer.on('connection', function(conn) {
        conn.on('data', function(data){
          // Will print 'hi!'
          log(data);
        });
      });*/
    peer.on('call',function(call){
        log("called!");
        /*if (window.existingCall) {
            window.existingCall.close();
        }*/
        call.on('error', function(err){
        log(err.message);
        });
        call.answer(localStream);
        log("-answered");
        call.on('stream',function(stream){
            log("-recieving stream: "+stream);
            //var audioSource =
            //connections[call.peer] =
            createAudioSourceStream(stream,2,2,0);
            //document.getElementById("otherAudio").setAttribute('src', URL.createObjectURL(stream));
        });
    });
}

function stop(){
    clearInterval(updater);
    clearInterval(ticker);
    for (conn in connections){
        conn.close();
    }
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
            log("response: "+this.responseText);
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