//register function
function register() {
    var uname = document.forms["registerForm"]["username"];
    var pass = document.forms["registerForm"]["password"];
    var pass2 = document.forms["registerForm"]["password2"];
    if (pass != pass2) {
        setErrorMessage("Your passwords don't match");
    }
    sendRequest("TextCombat.php?function=register&uname="+uname+"&pass="+pass+"&pass2="+pass2,
        function (response) {
            document.getElementById("message").innerHTML = "Welcome, "+uname+". <a href='index.php'>Begin!</a>";
        }
    );
}
//example name generator, later