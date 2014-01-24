<?php
ob_start();
session_start();
if(isset($_SESSION['playerID'])){
    header("Location: index.php");
}
?>

<html>
    <head>
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
        <img id="errorPoint" src="images/errorPoint.png" style="visibility: hidden"><span id="error" style="color: black"></span></br>
        <div id="info">
            <a>forums will be found soon</a></br></br>
            Updates:</br>
            -v1 :)</br>
            Welcome to the alpha!
        </div>
    </body>
</html>