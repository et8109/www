/**
 *sends a request to the server
 */
function sendRequest(url, returnFunction){
    request = new XMLHttpRequest();
    request.onreadystatechange = function(){
        if (this.readyState==4 && this.status==200) {
            var response = this.responseText;
            //if an error
            if (response.indexOf("<<") == 0) {
                setErrorMessage(response.replace("<<",""));
            }
            else{
                //success, call function
                returnFunction(response);
            }
        }
    }
    request.open("GET", url, true);
    request.send();
}
/**
 *sets the error message.
 */
function setErrorMessage(message){
    document.getElementById("error").innerHTML = message;
    document.getElementById("errorPoint").style.visibility = "visible";
}
/**
 *clears the error message
 */
function clearErrorMessage(args) {
    document.getElementById("error").innerHTML = "";
    document.getElementById("errorPoint").style.visibility = "hidden";
}

/////////////shared functions///////////////////////////////////////
////////////////////////////////////////////////////
////////////////////////////////////////////////////
//register function
function register() {
    clearErrorMessage();
    var uname = document.getElementById("username").value;
    var pass = document.getElementById("password").value;
    var pass2 = document.getElementById("password2").value;
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