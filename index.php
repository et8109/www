<!--
Possible helping methods:
cancel all actions

Useful:
Js bookmarks!

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
        manageScene lets player add/remove from itemsInScene list
            [doneish]adding items to scenes
            [doneish]removing items from scene
            [doneish]change the notes on items
            lets player minimally change description, with scene keyword check
        visitors can buy from shop
        
    sendRequest js function

    make a library

look over later list

rethink items having sizes

debug

take note of all code practices

start changelog

alpha begins!

********************************************
later:
********************************************
manage shows up in every scene
divide php into more manageable files
    move error messages to a constant
scolling/clicking/autocomplete selection from lists
js constants page with enums and text
change waiting so that each wait has its own function to call on hear, rather than all in text/area input method
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
move chat to db?
weather/light and darknes/time
more container words than bag
items need variable sizes, right now, all are 4
variable room/sizes for caontainer items
scrolls/library? A way for players to write thier own lore -> admin hierarchy
    sroll: two types of people, knights and squires. together it is a powerful relationship, but k/k or s/s are easily friends.
    scroll: each month is a cyle of sin (pennance, regret, deciciveness, inaction, ect.)
darkness/night time has a light setting, where the screen gets darker unless there is a candle/lamp
change sql responses to errors, also send to js in setup
put all types into one big enum?
cache stuff?
remove active links is still messed up
does js .length take linear time?
updateChat() still replaces spans in the js
crafting doesn't check crafting skill when looking for craft qualities.
whitespace problems when looking for keywards, happend in crafting
find a way to see sql errors
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
        <span id="alert" onclick="openMenu()">alerts</span>
        
        <div id="extra">
            <textArea id="textArea"></textArea><br/>
            <span class="textAreaButton" onclick="textAreaSubmit()">Done</span>
            <span class="textAreaButton" onclick="closeTextArea(), cancelWaits()">Cancel</span><br/>
            <span id="descriptionError"></span>
        </div>
        </div>
        <!-- The notifications popup -->
        <div id="menuMain">
        <div class="menuButton" id="alertsMenuButton" onclick="openAlerts()">a</div>
        <div class="menuButton" id="optionsMenuButton" onclick="openOptions()">o</div>
        <div id="menuMainInside">
        </div>
        <span id="menuMainClose" onclick="closeMenu()">Close</span>
        <span id="menuMainMute" onclick="toggleMute()">Mute</span>
        </div>
</body>
<script src="TextCombat.js"></script>
</html>