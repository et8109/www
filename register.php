<html>
    <head>
        <link rel="stylesheet" type="text/css" href="login.css" />
	<link rel="stylesheet" type="text/css" href="shared.css" />
	<script src="jsHelperFunctions.js"></script>
	<script src="register.js"></script>
    </head>
    <body>
        <FORM NAME ="registerForm" onsubmit="register()">
Username: <INPUT TYPE = 'TEXT' Name ='username' maxlength="10"></br>
Password: <INPUT TYPE = 'TEXT' Name ='password' maxlength="20"></br>
Password: <INPUT TYPE = 'TEXT' Name ='password2' maxlength="20"></br>
<P align = center>
<INPUT TYPE = "Submit" Name = "Submit1"  VALUE = "Register">
	<span id="message"></span>
    </body>
</html>