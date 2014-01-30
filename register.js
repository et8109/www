/**
 *sends a request to the server
 *repeated
 */
function sendRequest(url,params,returnFunction){
    var request = new XMLHttpRequest();
    request.open("POST",url);
    request.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    request.setRequestHeader("Content-length", params.length);
    request.setRequestHeader("Connection", "close");
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
    request.send(params);
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
    sendRequest("TextCombat.php",
                "function=register&uname="+uname+"&pass="+pass+"&pass2="+pass2,
        function (response) {
            document.getElementById("message").innerHTML = "Welcome, "+uname+"! <a href='login.php'>Back to login</a>";
        }
    );
}
//example name generator, later