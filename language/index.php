<?php

?>
<html>
    <head>
        <script src="parser.js"></script>
        <style>
            body{
                background-color: grey;
                overflow: scroll;
            }
        </style>
    </head>
    <body>
        <input type="text" id="input"/>
        <input type="button" onclick="p()">parse</input>
        <p id="response"></p>
    </body>
    <script>
        var res = document.getElementById("response");
        var inp = document.getElementById("input");
        function p(){
            res.innerHTML +="</br>"+parse(inp.value);
        }
    </script>
</html>