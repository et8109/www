<!--
    sends all get requests to a different php file from the main one: editBack.php
    change must be logged in to must have proper access
    -->

<html>
    <head>
        <?php
        session_start();
        if(!isset($_SESSION['playerID'])){
            header("Location: login.php");
        }
        ?>
        <script>
            //remember type, from radios
            var type = "Scene";
            /**
             *called by radio buttons to set the new type of object
             */
            function setType(newType) {
                type = newType;
            }
           
            /**
             *loads an object into the editor, needs id and a radio button selected
             */
            function load() {
                var ID = document.getElementById("idInput").value;
                request = new XMLHttpRequest();
                request.onreadystatechange = function(){
                    if (this.readyState==4 && this.status==200) {
                        response = this.responseText.split("<>");
                        document.getElementById("nameInput").value = response[0];
                        document.getElementById("textArea").value = response[1];
                    }
                }
                request.open("GET", "editBack.php?function=getInfo&type="+type+"&ID="+ID, true);
                request.send();
            }
            
           
            /**
             *overrites an object, needs id, name, radio, and description selected
             */
            function save() {
                var ID = document.getElementById("idInput").value;
                var Name = document.getElementById("nameInput").value;
                var Description = document.getElementById("textArea").value
                request = new XMLHttpRequest();
                request.open("GET", "editBack.php?function=save&type="+type+"&ID="+ID+"&Name="+Name+"&Description="+Description, true);
                request.send();
            }
            
   
           
            /**
            *Creates a new object
            */
            function saveNew() {
                var Name = document.getElementById("nameInput").value;
                var Description = document.getElementById("textArea").value
                document.getElementById("idInput").value = "new id created";
                request = new XMLHttpRequest();
                request.open("GET", "editBack.php?function=saveNew&type="+type+"&Name="+Name+"&Description="+Description, true);
                request.send();
            }
        </script>
        <style>
            body{
                background-color:#A89423;
            }
        </style>
    </head>
    <body>
        <textArea id="textArea" maxlength=255></textArea></br>
        Name:<input id="nameInput" type="text" maxlength=20><br/></br>
        ID:<input id="idInput" type="text" maxlength=6><br/>
        <input type="button" value="get" onclick="load()">
        <input type="button" value="save" onclick="save()">
            <input type="button" value="save as new - careful" onclick="saveNew()">
            </br>
        <input type="radio" name="type" onclick="setType(this.value)" value="Scene">Scene</br>
        <input type="radio" name="type" onclick="setType(this.value)" value="Item">Item</br>
        <input type="radio" name="type" onclick="setType(this.value)" value="Player">Player</br>
        <p id="message">
            [no message]
        </p>
            </br>
            text doc holds all info?<br/>
            description max: 255 bytes (tinyText)<br/>
            name max: 20 char</br>
            IDs: 3 char</br>
            [replacing things?]</br>
            Scene: tinyText for desc, 255 chars. Id:3, Name:20</br>
            Players: tinytext for desc, Id:6, Pass: 20, Name:20</br>
            Items: tinytext, Id:3, Name:20
            What else?</br>
            <a href="index.php">Return</a>
    </body>
</html>