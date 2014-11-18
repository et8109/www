<?php
require_once 'util.php';
session_start();
redirectIfLoggedOut();
?>
<html>
    <head>
        <script src="util.js"></script>
        <script>
            function match(){
                sendRequest("matchmaker.php",
                            "",
                            function(response){
                                alert(response);
                                /*var json = JSON.parse(response);
                                for(i in json){//user, ip
                                    alert(json[i].msg);
                                }*/
                            });
            }
        </script>
    </head>
    <body>
        <input type="button" onclick="match()" value="Enter match"></input>
        <a href="logout.php">Logout</a>
    </body>
</html>