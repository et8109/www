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

//login function
function login() {
    clearErrorMessage();
    uname = document.getElementById("username").value;
    pass = document.getElementById("password").value;
    sendRequest("TextCombat.php?function=login&uname="+uname+"&pass="+pass,
        function(response){
        //window.location = "index.php"; remove
        window.location.replace("index.php");
        }
    );
}
//get updates function, later