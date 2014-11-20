<?php
require_once 'util.php';
session_start();
redirectIfLoggedOut();
?>
<html>
    <head>
        <script src="util.js"></script>
    </head>
    <body>
        <input id='matchBtn' type="button" onclick="search=true,match()" value="Enter match"></input>
        <input id='stopMatchBtn' type="button" onclick="stopMatch()" value="Stop matching"></input>
        <a href="logout.php">Logout</a>
        <div id="msg"></div>
    </body>
    <script>
            var tries = 0;
            var msgBox = document.getElementById('msg');
            var matchButton = document.getElementById('matchBtn');
            var stopMatchButton = document.getElementById('stopMatchBtn');
            var search = false;
            function match(){
                //search = true; [in matchBtn]
                matchButton.style.display='none';
                stopMatchButton.style.display='block';
                sendRequest("matchmaker.php",
                            "",
                            function(response){
                                var found = false;
                                var json = JSON.parse(response);
                                for(i in json){
                                    if(typeof json[i].opp != 'undefined'){
                                        //if opponent found
                                        msgBox.innerHTML+="<a href='arena.php?o="+json[i].opp+"'>Battle</a>";
                                        stopMatchButton.style.display='none';
                                        matchButton.style.display='none';
                                        found = true;
                                    }
                                }
                                if (!found) {
                                    tries++;
                                    msgBox.innerHTML = "matchmaking attempts: "+tries;
                                    if (search) {
                                        setTimeout(match(), 5000);
                                    }
                                }
                            });
            }
            
            function stopMatch(){
                search = false;
                tries = 0;
                stopMatchButton.style.display='none';
                matchButton.style.display='block';
            }
        </script>
</html>