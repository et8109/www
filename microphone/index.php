<?php

session_start();
//make sure they are logged in
if(! isset($_SESSION['playerID'])){
    header("Location: login.php");
}
?>
<html>
    <head>
        <script src="http://cdn.peerjs.com/0.3/peer.js"></script>
        <script src="audioGame.js"></script>
        <script src="controls.js"></script>
        <style>
            body{
                background-color: black;
                overflow: hidden;
            }
            h1{
                color:#7f7f7f;
                text-align: center;
            }
            #main{
                margin-left: auto;
                margin-right: auto;
                margin-top: 50px;
                width: 200px;
                border-radius: 25px;
                background-color: grey;
                text-align: center;
                padding-top: 15px;
                padding-bottom: 20px;
            }
            #logout{
                /*display: none;*/
            }
            #options{
                /*display: none;*/
            }
            #compass{
                visibility: hidden;
                color: white;
                position: absolute;
                margin-top: 160px;
                border: solid 1px white;
                width: 20px;
                /*-webkit-transition: margin-left .3s;
                transition: margin-left .3s;*/
            }
            #log{
                color: #b9b9b9;
                position: absolute;
            }
        </style>
    </head>
    <body onkeypress="keyPressed(event)" onkeyup="keyUp(event)" <!--onmousemove="mouseMoved(event)"--> >
        <audio id="playerAudio" muted="true" autoplay></audio>
        <audio id="otherAudio" autoplay></audio>
        <h1>Audio Game</h1>
        <div id="main">
            <a onclick='stop()' href="logout.php">logout</a>
            <div id="options">
                <input type="button" value="record attack [2 seconds]" onclick="record(recordedAttack())">
            </div>
        </div>
        <div id="log"></div>
    </body>
</html>