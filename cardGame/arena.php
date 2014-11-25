<?php
require_once 'util.php';
session_start();
redirectIfLoggedOut();
?>
<html>
    <head>
        <style>
            body{
                background-color: grey;
            }
            .card{
                width: 80px;
                height: 120px;
                border: solid 3px black;
                background-color: brown;
                position: relative;
                display: inline-block;
                margin-left: 10px;
            }
            .card:hover{
                border: solid 3px gold;
            }
            #hand{
                position: relative;
                /*border: solid 1px blue;*/
                width: 300px;
                top: 75%;
                margin-left: auto;
                margin-right: auto;
            }
        </style>
    </head>
    <body>
        <div id="hand">
            
        </div>
    </body>
    <script src="util.js"></script>
    <script>
        function sendInfo(cardID) {
            sendRequest("updater.php",
                        "req=mine&cid="+cardID,
                        function(){
                            
                        });
        }
        function getInfo() {
            sendRequest("updater.php",
                        "req=thiers",
                        function(){
                            
                        });
        }
        function player(){
            this.handDiv = document.getElementById("hand");
            this.cards=[1,1,2,4,5,8,4,9,6,5];
            this.deck=this.cards;
            this.hand=[];
            this.draw = function(){
                var num = Math.floor(Math.random() * this.deck.length);
                var val = this.deck[num];
                this.hand.push(val);
                this.handDiv.innerHTML+="<div class='card'>"+val+"</div>";
                this.deck.splice(num,1);
            }
        }
        var p1 = new player();
        p1.draw();
        p1.draw();
        p1.draw();
    </script>
</html>