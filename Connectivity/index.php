<html>
    <head>
        <link rel="stylesheet" type="text/css" href="Connectivity/style.css" />
        <script>
            function setConType(type) {
                window.location = 'Connectivity/step2.php?type='+type;
            }
        </script>
    </head>
    <body>
        Welcome to the CWRU connectivity app.
        What wold you like to report?<br>
        <a class="option" onclick="setConType('cell')">Cell signal</a><br>
        <a class="option" onclick="setConType('wireless')">Wireless signal</a>
    </body>
</html>