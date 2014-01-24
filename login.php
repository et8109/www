<?php
ob_start();
session_start();
if(isset($_SESSION['playerID'])){
    header("Location: index.php");
}
?>

<html>
    <head>
        <link rel="stylesheet" type="text/css" href="login.css" />
        <link rel="stylesheet" type="text/css" href="shared.css" />
        <script src="jsHelperFunctions.js"></script>
        <script src="login.js"></script>
    </head>
    <body>
        <FORM NAME ="loginForm" onsubmit="login()">
I am </br>
<INPUT TYPE = 'TEXT' Name ='username' maxlength=20></br>
Password: </br>
<INPUT TYPE = 'password' Name ='password' maxlength=20><br/>
<INPUT TYPE = "Submit" Name = "Submit1">
    <a href="register.php">Need to register?</a>
        </FORM>
        <div id="info">
            <a>forums will be found soon</a></br></br>
            Updates:</br>
            -v1 :)</br>
            Welcome to the alpha!
        </div>
    </body>
</html>