<!--
Audiogame by et8109

todo:
fix up outer pages: login, logout, main, ect.
fix up shared: php and js
fix up error sending and logging

respawning enemies does not check for overlaps with npcs/other enemies.
answering questions might still be messed up

general progress

research:
why all audio requests cant be sent at once
a better was to store connection in php

-->

<?php

require("sharedPhp.php");

session_start();
if(!isset($_SESSION['playerID'])){
 header("Location: login.php");
}
?>

<html>
    <head>
        <script src="http://cdn.peerjs.com/0.3/peer.js"></script>
        <script src="audioGame.js"></script>
        <script src="controls.js"></script>
        <script src="sharedJs.js"></script>
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
            }
            #options{
                display: none;
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
        <h1>Audio Game</h1>
        <div id="log"></div>
        <div id="compass">
            N
        </div>
        <div id="main">
            <div id="logout">
                <input type="button" value="logout" onclick="logout()">
            </div>
            <div id="options">
                <input type="button" value="record attack [2 seconds]" onclick="record(recordedAttack())">
                <form action="audioUpload.php" method="post" enctype="multipart/form-data">
                  <input type="file" name="file"> 
                  <input type="submit" name="submit" value="Submit">
                </form>
            </div>
        </div>
    </body>
</html>
