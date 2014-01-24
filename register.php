<html>
    <head>
	<!-- shared favicon code -->
        <link rel="icon" href="images/favicon.ico" type="image/x-icon"/>
        <link rel="shortcut icon" href="images/favicon.ico" type="image/x-icon"/>
        <link rel="stylesheet" type="text/css" href="login.css" />
	<script src="register.js"></script>
    </head>
    <body>
Username: <INPUT TYPE = 'TEXT' id ='username' maxlength="20"></br>
Password: <INPUT TYPE = 'TEXT' id ='password' maxlength="20"></br>
Password: <INPUT TYPE = 'TEXT' id ='password2' maxlength="20"></br>
<input type="button" onclick="register()" value="submit">
	<a href="login.php">back</a></br>
	<!-- shared error message -->
        <img id="errorPoint" src="images/errorPoint.png" style="visibility: hidden"><span id="error" style="color: black"></span></br>
	<span id="message"></span>
    </body>
</html>