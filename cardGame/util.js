/**
*Sends an ajax request to the server
*/
function sendRequest(url,params,returnFunction){
   var request = new XMLHttpRequest();
   request.open("POST",url);
   request.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
   request.setRequestHeader("Content-length", params.length);
   request.setRequestHeader("Connection", "close");
   request.onreadystatechange = function(){
       if (this.readyState==4 && this.status==200) {
           returnFunction(this.responseText);
       }
   }
   request.send(params);
}