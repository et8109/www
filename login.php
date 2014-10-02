<?php
ob_start();
session_start();
if(isset($_SESSION['playerID'])){
    header("Location: index.php");
}
?>

<html>
    <head>
        <!-- shared favicon code -->
        <meta name="description" content="Explore a unique world, improve your character, and impact the game in your own way.">
        <meta name="keywords" content="game,online,free,multiplayer,text">
        <meta name="author" content="EE">
        <title>Ignatym</title>
        <link rel="icon" href="images/favicon.ico" type="image/x-icon"/>
        <link rel="shortcut icon" href="images/favicon.ico" type="image/x-icon"/>
        <script src="login.js"></script>
        <link rel="stylesheet" type="text/css" href="login.css" />
    </head>
    <body>
I am </br>
<INPUT TYPE = 'TEXT' id="username" maxlength=20></br>
Password: </br>
<INPUT TYPE = 'password' id="password" maxlength=20><br/>
<input type="button" value="login" onclick="login();">
    <a href="register.php">Need to register?</a></br>
    Guest account available,</br>username and password are "guest".</br>
        <!-- shared error message -->
        <img id="errorPoint" src="images/errorPoint.png" style="visibility: hidden"><span id="error"></span></br>
        <div id="info">
            <a href="guide.php" target="_newtab">Guide</a></br></br>
            <a href="http://ignatym.freeforums.net/" target="_newtab">Forums</a></br></br>
            Welcome to the alpha!</br></br></br>
            [ <a href="http://audiogame.ignatym.com">Audio Game</a> in progress. p2p chat not quite working yet! ]
        </div>
    </body>
</html>