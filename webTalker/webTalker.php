<?php
$function = "newPage";
if(isset($_POST['function'])){
    $function = $_POST['function'];
}
switch($function){
    case("read"):
        $words = substr($_POST['words']);
        $words = urlencode($words);
        $file = md5($words);
        $file = "audio/".$file."mp3";
        if(!file_exists($file)){
            $mp3 = file_get_contents("http://translate.google.com/translate_tts?ie=UTF-8&q=Hello&tl=en&total=1&idx=0&textlen=5&prev=input".$words);
            file_put_contents($file, $mp3);
        }
        break;
}
?>

<html>
    <head>
        <script>
            var words = document.getElementById("main").innerHTML;
            sendRequest("webTalker.php",
                        "function=read&words="+words,
                        function(){
                        }
                        );
            
            
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
            if (response.indexOf(">") == 0) {
                alert(response.replace(">",""));
            }
            else{
                //success, call function
                returnFunction(response);
            }
        }
    }
    request.send(params);
}
        </script>
    </head>
    <body>
        <div id="main">
            Hello everyone, I am a robot.
        </div>
        <audio>
            <source src="<? echo $file ?>" type="audio/mp3"/>
        </audio>
    </body>
</html>