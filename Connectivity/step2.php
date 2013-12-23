<?php
$type = $_GET['type'];
?>

<html>
    <head>
        <link rel="stylesheet" type="text/css" href="style.css" />
        <script>
            /*function useGPS() {
                if (navigator.geolocation) {
                    navigator.geolocation.getCurrentPosition(showPosition);
                    window.location = 'step3.php?type='+<?php echo $type;?>+"&lat="+position.coords.latitude+"&lon="position.coords.longitude;
                }
                else{
                    //no geolocation
                    document.getElementById("gpsError").innerHTML = "Woops, this browser does not support geolocation";
                }
            }*/
            
            function openCampusDropDown(){
                document.getElementById("campusDropDown").style.visibility = "visible";
            }
            
            function changeCampus(){
                var dropDown = document.getElementById("campusDropDown");
                var area = dropDown.options[dropDown.selectedIndex].text;
                alert(area);
            }
        </script>
    </head>
    <body>
        connection type = <?php print $_GET['type'] ?></br>
        Enter your location:</br>
        <a class="option" onclick="useGPS()">Use GPS</a></br>
        <span id="gpsError"></span>
        <a class="option" onclick="openCampusDropDown()">Let me tell you</a>
        <select class ="posQ" id="campusDropDown" onchange="changeCampus()">
            <option>Select a location</option>
            <option>North side residential</option>
            <option>South side residential</option>
            <option>Main engineering quad</option>
            <option>Mather Quad</option>
        </select>
        </br>
        <input class="posQ" type="textArea">
        </input>
        </br>
        <a href="../index.php">back</a>
    </body>
</html>