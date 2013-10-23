<!--
Possible helping methods:
cancel all actions

Useful:
Js bookmarks!

done:
logging in and logging out
stored in $_SESSION: ['playerID'] ['playerName'] ['lastChatTime'] ['currentScene']

big things:
make testing suite
change autoincrement value in db when done testing
PHP PDO!
crafting/buying items
combat
backgrounds
events
voice

todo:
start on testing - alerts, items, db
git
move chat to db
___
-done-moving constant to db
---
-done-sql function to get span text/replace
send types to js from php
    crafting needs type info on fail
change sql responses to errors, also send to js in setup
php replaces spans, not js
---
sql db backups
---
edit produces a log of changes
---
**Option for front-end loading, they pick what they want to speed up
---

getSpanText() deos not prepvar
crafting doesn't check crafting skill when looking for craft qualities.
whitespace problems when looking for keywards, happend in crafting
find a way to see sql errors
textAreaSubmit is spelled wrong
sql required keyword types should be on the lower end to reduce the lendth of the array when crafting items
php count() is not automatic, and loops through each time. avoid.
make a string builder
don't accept wirdt stuf fin inputs, like empystring/blank
object sizes and weights
when walking into a scene, show things that happened a bit before.
addAlert is unclear with the numbers
check/set new description in unclear
no idea if message on moving works or not
move help text to a text file
add flushing to the server to speed up the response
one server request per function!
websockets
look at login and logout again
    !###!#!#!# new scene creation prevents speaking until leave and come back
    add  "name moved here" to scene text
add fail actions to helper functions
on opening page: character list(some hand drawn pics), story introduction
login redirects to index if already logged in
add final to final variables
prepVar causes craft item to append an item name with single quotes
make sure removing an alerts actually removes it, not extends it by null
add alert check to setup function. set alert and numAlerts
there is a maximum amount of alerts
**sometimes an item is not added tot he item list?? -> HAPPENS WHEN TINYTEXT IN PLAYER DESCRIPTION RUNS OUT??
make sure all variables have var
only have things on client-side which will not impact anyone else
find both wood and wooden in descriptions, ect.
when text wraps due to small screen width, the text box can go below the page
clean methods, input and output: found in set new description
BAD CHARS: #,&, more?
make some cancelling methods
loading screen
add a quality tag to the item description check. commented right now
make sure everything is searching by ID
store textBox object in js memory
a chat for each scene
"include" php helper functions, or paste them into every php file?
put chat into db rather than text file?
no max length for input text/talking
prepVar type thing for putting into text file
don't completely trust js saved variables, like namItems. have a db lookup too
check to make sure cookies were not changed
looks for upper case and lower case items in description
in php sql statements, rather than appending quotes, prepare a query and then release it
prevent code injection while logging in, and in general-prevent #,& in querys
login and password for database
change gets to posts
hide password while it is being entered
remove \r\n from all input text
-->

<html>
    <head>
        <?php
        session_start();
        if(!isset($_SESSION['playerID'])){
            header("Location: login.php");
        }
        ?>
<link rel="stylesheet" type="text/css" href="TextCombat.css" />
<script src="TextCombat.js"></script>
<!--//////////////////////////////////remove testing///////////////////////////////////////////////////-->
<!--<script src="testing.js"></script>-->
<audio id="anvil">
    <source src="sounds/anvil.wav" type="audio/wav"/>
    Your browser doesn't support wav sound
</audio>
</head>
<body>
    <!--  main area, text on top, then input and buttons, then extra on bottom -->
        <div class="textBox" id="textBox1">
        </div>
        <div class="textBox" id="textBox2">
        </div>
        <br/>
        <!-- holds the input, buttons, and text area -->
        <div id="hub">
        <input id="input" disabled="true" type="text" id="input" maxlength="100" onkeypress="textTyped(event)"></input></br>
        <span id="logout"><a href="logout.php">log out</a></span>
        <span id="hubName" onclick="displayMyDesc()"><?php echo $_SESSION['playerName']; ?></span>
        <span id="id"><?php echo $_SESSION['playerID']; ?></span>
        <span id="alert" onclick="openAlerts()">alerts</span>
        
        <div id="extra">
            <textArea id="textArea"></textArea><br/>
            <span class="textAreaButton" onclick="textAreaSumbit()">Done</span>
            <span class="textAreaButton" onclick="closeTextArea()">Cancel</span><br/>
            <span id="descriptionError"></span>
        </div>
        </div>
        <!-- The notifications popup -->
        <div id="alertMain">
        Alerts:
        <div id="alertMainInside">
            hihi
        </div>
        <span id="alertMainClose" onclick="closeAlerts()">Close</span>
        <span id="alertMainMute" onclick="toggleMute()">Mute</span>
        </div>
</body>
</html>