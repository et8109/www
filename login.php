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
        <!-- shared error message -->
        <img id="errorPoint" src="images/errorPoint.png" style="visibility: hidden"><span id="error"></span></br>
        <div id="info">
            <a href="http://ignatym.freeforums.net/">Forums</a></br></br>
            Welcome to the alpha!
        </div>
    </body>
</html>