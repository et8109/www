<?php
    
?>
<html>
    <head>
        <script>
            var timer = setInterval("actions()", 5000);
            function actions(){
                if (p1.health>0) {
                    p1.health-=1;
                }
                p1.update();
            }
            function player(id){
                document.getElementById("field").innerHTML+="<div class='player' id="+id+"><div id="+id+"h class='health'>h</div></div>";
                this.health=5;
                this.box = document.getElementById(id);
                this.healthBar = document.getElementById(id+"h");
                this.spd = 5;
                this.leftPos = 0;
                this.topPos = 0;
                this.update = function(){
                    this.healthBar.style.width = this.health*6;
                }
                this.up = function(){
                    this.box.style.top = this.topPos - this.spd + "px";
                    this.topPos-=this.spd;
                }
                this.down = function(){
                    this.box.style.top = this.topPos + this.spd + "px";
                    this.topPos+=this.spd;
                }
                this.left = function(){
                    this.box.style.left = this.leftPos - this.spd + "px";
                    this.leftPos-=this.spd;
                }
                this.right = function(){
                    this.box.style.left = this.leftPos + this.spd + "px";
                    this.leftPos=this.leftPos+this.spd;
                }
            }
                
            function move() {
                var spd = 40;
                if(event.keyCode == 119 || event.keyCode == 87){//w
                    p1.up();
                } else if(event.keyCode == 65 || event.keyCode == 97){//a
                    p1.left();
                } else if(event.keyCode == 83 || event.keyCode == 115){//s
                    p1.down();
                } else if(event.keyCode == 100 || event.keyCode == 68){//d
                    p1.right();
                }
            }
        </script>
        <style>
            body{
                background-color: black;
            }
            .player{
                width: 30px;
                height: 100px;
                background-color: grey;
                position: absolute;
            }
            .health{
                width: 30px;
                height: 8px;
                background-color: purple;
            }
            #timer{
                -webkit-animation: myfirst 5s linear infinite; /* Chrome, Safari, Opera */
                animation: myfirst 5s linear infinite;
                position: absolute;
                left:50px;
                height: 40px;
                background-color: red;
                border: solid 3px grey;
            }
            /* Chrome, Safari, Opera */
            @-webkit-keyframes myfirst {
                0% {width: 200;}
                100% {width: 0;}
            }
            
            /* Standard syntax */
            @keyframes myfirst {
                0% {width: 200;}
                100% {width: 0;}
            }
        </style>
    </head>
    <body onkeypress='move(event)'>
        <div id="field">
            <div id='timer'></div>
        </div>
    </body>
    <script>
        var p1 = new player("p1");
    </script>
</html>