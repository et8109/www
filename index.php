<?php
    ob_start();
    session_start();
    if(!isset($_SESSION['playerID'])){
        header("Location: login.php");
    }
?>

<!--
Useful:
Js bookmarks!
del [full path]index.lock

done:
logging in and logging out
stored in $_SESSION: ['playerID'] ['playerName'] ['lastChatTime'] ['currentScene']

big things:
change autoincrement value in db when done testing
PHP PDO!
buying items
combat
backgrounds
events
voice

todo:
sql db backups
---
a popup that asks if each keyword should be enabled or ignored
********************************************
[done]creating items add the item to itemKeywords
[done]scenes have keywords
    move constants to new php page
    [done]on scene creation or modification, check keywords: use new phphf
        [done]do for items and players too?
[done]scenePlayers db table with players in each scene
    [done]revamp walk
    [done]add player to list on login
    [done]revamp attack
[1/2 done]####Attacking will be the addition of player combat-related skills with player's combat-related item keywords
    player skills be be player desc keywords
********************************************

[done]combine playerItems and items tables

think about item sizes/weights and bags again

[sort of]look for repeated code

[doneish]scenes can hold things

    [started]make a pub
        [done]player keyword db table
            [done]keyowrds have name, description, locationID
        [done]when creating a player description, check player keywords
            [done, except for edit]universalize update desc
        [done]getSpanText gives manage span if the player has the right keyword and location
        [doneish]manageScene lets player add/remove from itemsInScene list
            [doneish]adding items to scenes
            [doneish]removing items from scene
            [doneish]change the notes on items
        [done]lets player minimally change description, with scene keyword check
        visitors can buy from shop
            trading between players
        
    [done]sendRequest js function
        [done]constant error recieving
        [done]a php error sender function
        
        responsibility hierarchy
            [done]-each has their own keyword type for playerkeywords
            [done]-scens have a town and land location, shared with other scenes
                [done]lord kwrd uses town, diety kwrd uses land
            [done]-change manage functions to manage level
            ---
            to become:
            apprentice: manager adds you via command
                cannot leave any notes
            manager: lord adds you via command or previous manager sets you
                cannot leave any notes
                if no manager for __ days, it closes down
            lord: monarch adds you via command or previous lord sets you
                cannot leave notes
                if no lord for __ days, monarch is warned and other lords may choose?
            to leave:
            apprentice: use leave command
            manager,lord, monarch: optionally choose successor
                successor must have a certain amount of previous experience
            ---
            email supprt
                send lower level changes higher up, can change in options
            people who work at the same location should have shared notes
            recieve an alert when you are accepted, told to update email

    make a library
    
can't log in if already logged in
move chat to db
error on wrong password

look over later list

debug

take note of all code practices

start changelog

change email headers
db uname/pass

alpha begins!

********************************************
later:
********************************************
combine multiple querys into one in long functions
when hiring, say what position people were hired to on success and error 
replacing keywords must take into account crafting level restictions
a way to remove certain alerts
remove admin level from playerInfo
ability to view players from the home page
player description should always include responsibilities, from appshp to diety
location creation needs apprenticeship option
scolling/clicking/autocomplete selection from lists
js constants page with enums and text
change function names so that action is first, such as prompt
incorrect username/password message on login
make sure items table has a secondary search set fo playerID
scenes can only hold 1 list of items/1 job
auto-completeing when selecting things, like items from your inventory
javascript can be disabled, double check things on server side
a logout function, player is removed from sceneplayers when not active..
add options for each command, custom commands
    add list of players to look
landmark scenes(essentially seach by scene keyword)
testing
edit produces a log of changes
weather/light and darknes/time
more container words than bag
variable room/sizes for container items
scrolls/library? A way for players to write thier own lore -> admin hierarchy
    sroll: two types of people, knights and squires. together it is a powerful relationship, but k/k or s/s are easily friends.
    scroll: each month is a cyle of sin (pennance, regret, deciciveness, inaction, ect.)
darkness/night time has a light setting, where the screen gets darker unless there is a candle/lamp
change sql responses to errors, also send to js in setup
cache stuff?
does js .length take linear time?
updateChat() still replaces spans in the js
whitespace problems when looking for keywards, happend in crafting
find a way to see sql errors
php count() is not automatic, and loops through each time. avoid.
make a string builder
don't accept wirdt stuf fin inputs, like empystring/blank
when walking into a scene, show things that happened a bit before.
no idea if message on moving works or not
move help text to a text file
add flushing to the server to speed up the response
one server request per function!
websockets
look at login and logout again
    !###!#!#!# new scene creation prevents speaking until leave and come back
    add  "name moved here" to scene text
on opening page: character list(some hand drawn pics), story introduction
login redirects to index if already logged in
add final to final variables
prepVar causes craft item to append an item name with single quotes
make sure removing an alerts actually removes it, not extends it by null
there is a maximum amount of alerts
make sure all variables have var
only have things on client-side which will not impact anyone else
when text wraps due to small screen width, the text box can go below the page
clean methods, input and output: found in set new description
BAD CHARS: #,&, more?
loading screen
make sure everything is searching by ID
store textBox object in js memory?
dynamically change max length of inputs when listening
don't completely trust js saved variables, like namItems. have a db lookup too
check to make sure cookies were not changed
looks for upper case and lower case items in description
in php sql statements, rather than appending quotes, prepare a query and then release it
prevent code injection while logging in, and in general-prevent #,& in querys
login and password for database
change gets to posts
remove \r\n from all input text
-->
<html>
    <head>
<link rel="stylesheet" type="text/css" href="TextCombat.css" />
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
        <img id="errorPoint" src="images/errorPoint.png"><span id="error"></span></br>
        <input id="input" disabled="true" type="text" id="input" maxlength="100" onkeypress="textTyped(event)"></input></br>
        <span id="logout" onclick="logout()"><a href="logout.php">log out</a></span>
        <span id="hubName" onclick="displayMyDesc()"><?php echo $_SESSION['playerName']; ?></span>
        <span id="id"><?php echo $_SESSION['playerID']; ?></span>
        <span id="alert" onclick="openMenu()">alerts</span>
        
        <div id="extra">
            <textArea id="textArea" maxlength="1000"></textArea><br/>
            <span class="textAreaButton" onclick="textAreaSubmit()">Done</span>
            <span class="textAreaButton" onclick="closeTextArea(), cancelWaits()">Cancel</span><br/>
        </div>
        </div>
        <!-- The notifications popup -->
        <div id="menuMain">
        <div class="menuButton" id="alertsMenuButton" onclick="openAlerts()"></div>
        <div class="menuButton" id="optionsMenuButton" onclick="openOptions()"></div>
        <div id="menuMainInside">
        </div>
        <span id="menuMainClose" onclick="closeMenu()">Close</span>
        <span id="menuMainMute" onclick="toggleMute()">Mute</span>
        </div>
</body>
<script src="TextCombat.js"></script>
</html>