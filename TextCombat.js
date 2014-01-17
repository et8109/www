///////////////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////
//Globals

var frontLoadAlertText;
var frontLoadSceneText;
var frontLoadKeywords;
/**
 *Set up, needed
 */
(function(){
request = new XMLHttpRequest();
request.onreadystatechange = function(){
    if (this.readyState==4 && this.status==200) {
        var response = this.responseText.split("<>");
        var adminLevel = parseInt(response[1]);
        switch(adminLevel){
            case(1):
                document.getElementById("hub").innerHTML+="</br><a href='edit.php'>edit</a>";
                break;
        }
        currentScene = parseInt(response[2]);
        frontLoadAlertText = parseInt(response[3]);
        frontLoadSceneText = parseInt(response[4]);
        frontLoadKeywords = parseInt(response[5]);
        //see if alerts button should be gold
        var numAlerts = parseInt(response[6]);
        if (numAlerts > 0) {
            document.getElementById("alert").style.color="gold";
        }
        //allow input
        document.getElementById("input").disabled=false;
    }
}
request.open("GET", "setup.php?function=setUp", false);
request.send();
}());
var alertText ={};
if (frontLoadAlertText) {
(function(){
    request = new XMLHttpRequest();
    request.onreadystatechange = function(){
        if (this.readyState==4 && this.status==200) {
            var response = this.responseText.split("<<>>");
            //ids and thier text
            var alertTextsAndIDs = response[0].split("<>");
            for(var i=1; i<alertTextsAndIDs.length; i+=2){;
                alertText[parseInt(alertTextsAndIDs[i])] = alertTextsAndIDs[i+1];
            }
            //the player's alerts
            var playerAlerts = response[1].split("<>");
            for(var i=1; i<playerAlerts.length; i++){
                addAlert(playerAlerts[i]);
            }
        }
    }
    request.open("GET", "setup.php?function=frontLoadAlerts", true);
    request.send();
    }());
}

//[id][0:name, 1:description]
var sceneText={};
if (frontLoadSceneText) {
    (function(){
    request = new XMLHttpRequest();
    request.onreadystatechange = function(){
        if (this.readyState==4 && this.status==200) {
            var sceneTextsAndIDs = this.responseText.split("<>");
            for(var i=1; i<sceneTextsAndIDs.length; i+=3){;
            sceneText[parseInt(sceneTextsAndIDs[i])] = [sceneTextsAndIDs[i+1],sceneTextsAndIDs[i+2]]
            }
        }
    }
    request.open("GET", "setup.php?function=frontLoadScenes", false);
    request.send();
    }());
}

//[word][0: span text 1: desc] //keyword type not needed
var keywordText={};
if (frontLoadKeywords) {
    (function(){
    request = new XMLHttpRequest();
    request.onreadystatechange = function(){
        if (this.readyState==4 && this.status==200) {
            var keywordTextAndDesc = this.responseText.split("<>");
            for(var i=1; i<keywordTextAndDesc.length; i+=3){;
            keywordText[keywordTextAndDesc[i]] = [keywordTextAndDesc[i+1], keywordTextAndDesc[i+2]];
                //keywordText[keywordTextAndDesc[i]][0] = keywordTextAndDesc[i+1];
                //keywordText[keywordTextAndDesc[i]][1] = keywordTextAndDesc[i+2];
            }
        }
    }
    request.open("GET", "setup.php?function=frontLoadKeywords", false);
    request.send();
    }());
}

/**
*sets the timer to update chat
*/
updater: setInterval("updateChat()", 3000);
/**
 *The possible inputs from the text area at the bottom of the page
 */
var textAreaInputs = {
    NOTHING : 0,
    PERSONAL_DESCRIPTION : 1,
    ITEM_DESCRIPTION : 2,
    NOTE_FOR_ADDING_ITEM : 3,
    NEW_ITEM_NOTE_TEXT : 4,
    NEW_SCENE_DESC : 5
};
/**
 *The possible inputs from the main text line
 */
var textLineInputs = {
    NOTHING : 0,
    ITEM_NAME : 1,
    TARGET_NAME : 2,
    ITEM_NAME_TO_ADD_TO_SCENE : 3,
    ITEM_NAME_TO_REMOVE_FROM_SCENE : 4,
    ITEM_NAME_TO_CHANGE_NOTE_OF : 5,
    QUIT_JOB : 6
};
var waitingForTextArea = textAreaInputs.NOTHING;
var waitingForTextLine = textLineInputs.NOTHING;
/**
 A bunch of types of random stuff.
 Each should have:
 id
 description
 **repeated in sql
 */
var spanTypes = {
    ITEM: 0,
    PLAYER: 1,
    SCENE: 2,
    KEYWORD: 3
}

var currentLine=0; //17 is max, arbitrary
var textBox="textBox1";
var OfftextBox="textBox2";

/**
 *current active alerts
 */
var alerts ={};
/**
 *all the possible alerts.
 *stored in db as numbers, so must be finable by number
 */
var alertTypes ={
    NEW_ITEM : 1,
    HIDDEN_ITEM : 2,
    REMOVED_ITEM : 3,
    NEW_JOB : 4,
}

/**
 *if the sound is muted or not
 */
var muted = false;
/**
 *the types of public actions added to the chat text.
 *duplicated in helper function
 */
var actionTypes ={
    WALKING : 0,
    ATTACK : 1
}

/**
 *holds the name of the item to be:
 *crafted
 *added to scene
 *have a changed note in scene
 */
var itemName;
/**
 *saves the current scene id. used for addDesc of currentScene
 */
var currentScene;

//////////////////////////////////////////////////////
//////////////////////////////////////////////////////
//////////////////////////////////////////////////////
//////////////////////////////////////////////////////
//big methods
/**
*Returns if key was not enter.
*Checks for waiting, commands with /, and talking
*/
function textTyped(e){
    //if enter button was not pressed, do nothing
    if(event.keyCode != 13){
        return;
    }
    //clear error
    document.getElementById("error").innerHTML="";
    var inputText = getInputText();
    //make sure input is valid
    if (inputText == null) {
        //nothing, skips to clear input
    }
    //command check
    else if(inputText.indexOf("/") == 0){
        closeTextArea();
        cancelWaits();
        inputText = inputText.split(" ");
        //cancel waiting stuff?
        switch (inputText[0]) {
            case("/look"):
                deactivateActiveLinks();
                addDesc(spanTypes.SCENE, currentScene);
                break;
            case("/attack"):
                waitingForTextLine = textLineInputs.TARGET_NAME;
                addText("who would you like to attack?");
                break;
            case("/help"):
                addHelpText();
                break;
            case("/put"):
                if (inputText.indexOf(" in ") == -1) {
                    addText("please use /put [item] in [container item]");
                }
                var items = inputText.replace("/put","").split(" in ");
                putItemIn(items[0], items[1]);
                break;
            case("/manage"):
                getManageSceneText();
                break;
            case("/quitjob"):
                quitJobPrompt();
                break;
            case("/hire"):
                var name = "";
                for(var i=1; i<inputText.length; i++){
                    name+=inputText[i];
                }
                hireEmployee(name);
                break;
            case("/fire"):
                var name = "";
                for(var i=1; i<inputText.length; i++){
                    name+=inputText[i];
                }
                fireEmployee(name);
                break;
            default:
                addText(inputText+" ..unknown command");
                break;
        }
    }
    
    //if not a command
    else if (waitingForTextLine != textLineInputs.NOTHING) {
        switch (waitingForTextLine) {
            case(textLineInputs.ITEM_NAME):
                addCraftName();
            break;
            case(textLineInputs.TARGET_NAME):
                attack();
                break;
            case(textLineInputs.ITEM_NAME_TO_ADD_TO_SCENE):
                addItemNoteToScenePrompt();
                break;
            case(textLineInputs.ITEM_NAME_TO_REMOVE_FROM_SCENE):
                removeItemFromScene();
                break;
            case(textLineInputs.ITEM_NAME_TO_CHANGE_NOTE_OF):
                newNoteTextPromt();
                break;
            case(textLineInputs.QUIT_JOB):
                quitJob();
                break;
        }
    }
    
    //not waiting, and not command
    else{
        if (inputText == "") {
            return;
        }
        speak(inputText);
    }
    
    //always: clear input
    document.getElementById("input").value="";
}

/**
*gets the lines of chat not yet seen.
*adds the lines to the text box
*/
function updateChat(){
    sendRequest("FilesBack.php?function=updateChat",
        function(response){
            if (response=="") {
                //nothing
                return;
            }
            response = response.split("\r\n");
	    if (response.length>1) {
		for(var i=0; i<response.length; i+=3){
                    //if an action, not a chat
                    if (response[i+2].indexOf("<") == 1) {
                        var type = parseInt(response[i+2].charAt(2));
                        switch(type){
                            case(actionTypes.ATTACK):
                                //let the player know somehow that they were attacked, attak sound
                                var info = response[i+2].split("<>");//1:id, 2:text
                                addText(info[2]);
                                break;
                            case(actionTypes.WALKING):
                                //footsteps sound
                                var info = response[i+2].split("<>");//1:name, 2:id
                                addText(info[2]);
                                break;
                        }
                    }
                    //if a chat
                    else{
                        addText("<span class='name' onclick='addDesc("+spanTypes.PLAYER+","+response[i]+")'>"+response[i+1]+"</span>: "+response[i+2]);
                    }
                }
            }
        }
    );
}

/**
 *prints the description into the text box.
 *id is actually word for descriptions
 */
function addDesc(type, id) {
    switch (type) {
        case(spanTypes.SCENE):
            if (frontLoadSceneText) {
                addText( sceneText[id][0] );
                addText( sceneText[id][1] );
                return;
            }
            break;
        case(spanTypes.KEYWORD):
            if (frontLoadKeywords) {
                addText( keywordText[id][0] );
                addText( keywordText[id][1] );
                return;
            }
            break;
    }
    sendRequest("TextCombat.php?function=getDesc&type="+type+"&ID="+id,
        function(response) {
            response = response.split("<>");
            addText(response[0]);
            addText(response[1]);
        }
    );
}
  
/**
*Sets the player's new description after checking for inventory items, no < or >.
*Do not call directly! call check description first!
*/
function setNewDescription() {
    var newDescription = getTextAreaText();
    //would be null if < or > was in area
    if (null == newDescription) {
        return;
    }
    sendRequest("TextCombat.php?function=updateDescription&Description="+newDescription,
        function(response) {
            closeTextArea();
            //new item, cange description alert
            removeAlert(alertTypes.NEW_ITEM);
            waitingForTextArea=textAreaInputs.NOTHING;
        }
    );
}


/**
* Move scene, then print new scene description.
* End waits
* Also updates currentChatTime and adds a walking message ot chat
*/
function walk(newSceneId) {
    currentScene = newSceneId;
    deactivateActiveLinks();
    if (frontLoadSceneText) {
        addDesc(spanTypes.SCENE, newSceneId);
    }
    sendRequest("TextCombat.php?function=moveScenes&newScene="+newSceneId,
        function(response){
            if (!frontLoadSceneText) {
                addDesc(spanTypes.SCENE, newSceneId);
            }
        }
    );
    cancelWaits();
    closeTextArea();
}
/**
    *open text area and display player description.
    *wait for a new description input
    */
function displayMyDesc() {
    openTextArea("enter a new description");
    sendRequest("TextCombat.php?function=getDesc&type="+spanTypes.PLAYER,
        function(response){
            //first is name, second id desc
            response = response.split("<>");
            //remove styling, not visible in text area
            setTextAreaText(removeHtmlStyling(response[1]));
        }
    );
    cancelWaits();
    waitingForTextArea = textAreaInputs.PERSONAL_DESCRIPTION;
}

/**
 *When the anvil is clicked. checks if the player can start crafting.
 *asks for item name.
 */
function startCraft(){
//if waiting for something.
if (isWaiting()) {
    addText("You're already focused on something else. Finish with that, then you can craft something");
    return;
}
addText("You clear some space on the iron anvil. What do you want to make?");
waitingForTextLine = textLineInputs.ITEM_NAME;
}
/**
 *When an item name is given, tells the player to give a description
 */
function addCraftName(){
    itemName = getInputText();
    if (itemName == null) {
        return;
    }
    openTextArea(itemName+"'s description");
    //has a name, need a description
    sendRequest("crafting.php?function=getCraftInfo",
        function(response){
            waitingForTextLine = textLineInputs.NOTHING;
            cancelWaits();
            addText("Your craftSkill is "+response+ ". enter the "+itemName+"'s description below. Your tags are: tags not done yet");
            waitingForTextArea = textAreaInputs.ITEM_DESCRIPTION;
        }
    );
}
/**
 *When and items description is given, and a name was already chosen
 */
function addCraftDescription(){
    if (itemName == "") {
        addText("[Something wierd happened. Woops! Please let me know what you did. Thanks.]");
        cancelWaits();
        return;
    }
    var itemDescription = getTextAreaText();
    //would be null if < or > in area
    if (null == itemDescription) {
        return;
    }
    //input into database
    sendRequest("crafting.php?function=craftItem&Name="+itemName+"&Description="+itemDescription,
        function(response){
            addText("You make a "+itemName);
            closeTextArea();
            waitingForTextArea = textAreaInputs.NOTHING;
            cancelWaits();
            //new item in inventory alert
            //also in db
            addAlert(alertTypes.NEW_ITEM);
            //sound
            playSound("anvil");
            itemName = "";
        }
    );
}

/**
 *Begins the service at a pub.
 *prints items available
 */
function startWaiter(){
    //check current waits
    if (isWaiting()){
        addText("You're already focused on something else. Finish with that, then you can order something");
        return;
    }
    addText("A waiter approaches your table. 'Hello there. What would you like?' they ask.");
    //check menu at this pub
    getItemsInScene("Oops, sorry. There is nothing available right now.");
}

/**
 *gets the items in the scene(item and store note).
 *prints empty text if nothing was found
 */
function getItemsInScene(onEmptyText){
    sendRequest("TextCombat.php?function=getItemsInScene",
        function(response) {
            if (response == "") {
                onEmptyText ? addText(onEmptyText) : addText('Nothing here.');
                return;
            }
            //success
            var splitResponse = response.split("<>");
            for(i in splitResonse){
                addText(splitResponse[i]);
            }
        }
    );
}
/**
 *prompts for what item to add to the curent scene
 */
function addItemToScenePrompt() {
    addText("what item of yours would you like to add to this location?");
    waitingForTextLine = textLineInputs.ITEM_NAME_TO_ADD_TO_SCENE;
}

/**
 *adds an item to the current scene
 */
function addItemNoteToScenePrompt(){
    //get item name
    itemName = getInputText();
    if (itemName == null) {
        return;
    }
    addText("what is the note for the "+itemName+"?");
    cancelWaits();
    waitingForTextArea = textAreaInputs.NOTE_FOR_ADDING_ITEM;
}
/**
 *prompts for what item to remove from the scene
 */
function removeItemFromScenePrompt() {
    addText("what item would you like to remove from this location?");
    waitingForTextLine = textLineInputs.ITEM_NAME_TO_REMOVE_FROM_SCENE;
}
/**
 *adds the item and its note to the scene
 */
function addItemToScene(){
    var noteText = getTextAreaText();
    if (noteText == null) {
       return; 
    }
    cancelWaits();
    sendRequest("manage.php?function=addItemToScene&Name="+itemName+"&Note="+noteText,
        function(response){
            addText("added "+itemName);
            addAlert(alertTypes.REMOVED_ITEM);
            return;
        }
    );
}
/**
 *removes the given item from the scene
 */
function removeItemFromScene(){
    var name = getInputText();
    if (name == null) {
       return; 
    }
    cancelWaits();
    sendRequest("manage.php?function=removeItemFromScene&Name="+itemName,
        function(response){
            addText("you take the "+itemName);
            addAlert(alertTypes.NEW_ITEM);
            return;
        }
    );
}
/**
 *prompts the player for what note they want to change in this scene
 */
function changeItemNotePrompt() {
    addText("what item note would you like to change in this location?");
    cancelWaits();
    waitingForTextLine = textLineInputs.ITEM_NAME_TO_CHANGE_NOTE_OF;
}
/**
 *prompts for the new note text
 */
function newNoteTextPromt(){
    itemName = getInputText();
    if (itemName == null) {
        return;
    }
    addText("Edit the note below.");
    cancelWaits();
    waitingForTextLine = textAreaInputs.NEW_ITEM_NOTE_TEXT;
}
/**
 *prompts for the new scene description
 */
function newSceneDescPrompt() {
    addText("Edit the description below.");
    cancelWaits();
    //get scene desc
    sendRequest("TextCombat.php?function=getDesc&type="+spanTypes.SCENE,
        function(response){
            openTextArea();
            //first is name, second is desc
            response=response.split("<>");
            setTextAreaText(removeHtmlStyling(response[1]));
            waitingForTextLine = textAreaInputs.NEW_ITEM_NOTE_TEXT;
        }
    );
}
/**
 *gets the note text and changes the item note
 */
function changeItemNote(){
    var noteText = getTextAreaText();
    if (noteText == null) {
       return; 
    }
    cancelWaits();
    sendRequest("manage.php?function=changeItemNote&Name="+itemName+"&Note="+noteText,
        function(response){
            addText("changed note for "+itemName);
            return;
        }
    );
}  
/**
 *reuests to change the description of this scene
 */
function editSceneDesc(){
    var desc = getTextAreaText();
    if (desc == null) {
        //nothing
        return;
    }
    cancelWaits();
    sendRequest("manage.php?function=changeSceneDesc&desc="+desc,
        function(response){
           addText("changed scene description"); 
        }
    );
}
/**
*find who the player want to attack, after /attack
*/
function attack() {
    waitingForTextLine = textAreaInputs.NOTHING;
    cancelWaits();
    var name = getInputText();
    if (name == null) {
        return;
    }  
    sendRequest("combat.php?function=attack&Name="+name,
        function(){}
    );
}

function openMenu(){
    openAlerts();
    document.getElementById("menuMain").style.visibility="visible";
}
/**
 *shows alerts in menu box
 */
function openAlerts(){
    document.getElementById("alert").style.color="black";
    if (frontLoadAlertText) {
        document.getElementById("menuMainInside").innerHTML = "Alerts:";
        for(alertNum in alerts){
            document.getElementById("menuMainInside").innerHTML +="</br>"+alertText[alertNum];
        }
    }
    else{
        document.getElementById("menuMainInside").innerHTML = "Loading..";
        request = new XMLHttpRequest();
        request.onreadystatechange = function(){
            if (this.readyState==4 && this.status==200) {
                document.getElementById("menuMainInside").innerHTML = "Alerts:";
                document.getElementById("menuMainInside").innerHTML += this.responseText;
            }
        }
        request.open("GET", "TextCombat.php?function=getAlertMessages", true);
        request.send();
    }
}
/**
 *closes the alert box.
 *called by the close button on the page
 *sets the alert button to be black
 */
function closeMenu(){
document.getElementById("menuMain").style.visibility="hidden";
}
/**
 *displays the options in the alert box
 */
function openOptions(){
    var menuInside = document.getElementById("menuMainInside");
    menuInside.innerHTML = "";
    menuInside.innerHTML += "Options:";
    //front load alert text
    if (frontLoadAlertText) {
        menuInside.innerHTML +="</br><input type='checkbox' onclick='toggleFrontLoadAlertText()' checked='checked'>";
    }
    else{
        menuInside.innerHTML +="</br><input type='checkbox' onclick='toggleFrontLoadAlertText()'>";
    }
    menuInside.innerHTML +="Front load alert text. About 2 lines.</input>";
    //front load scene text
    if (frontLoadSceneText) {
        menuInside.innerHTML +="</br><input type='checkbox' onclick='toggleFrontLoadSceneText()' checked='checked'>";
    }
    else{
        menuInside.innerHTML +="</br><input type='checkbox' onclick='toggleFrontLoadSceneText()'>";
    }
    menuInside.innerHTML +="Front load scene text. About 3 lines.</input>";
    //front load keywords
    if (frontLoadKeywords) {
        menuInside.innerHTML +="</br><input type='checkbox' onclick='toggleFrontLoadKeywords()' checked='checked'>";
    }
    else{
        menuInside.innerHTML +="</br><input type='checkbox' onclick='toggleFrontLoadKeywords()'>";
    }
    menuInside.innerHTML +="Front load keyword text. About 6 lines.</input>";
}
/**
 *puts an item into a container item
 */
function putItemIn(itemName, containerName) {
    sendRequest("TextCombat.php?function=putItemIn&itemName="+itemName+"&containerName="+containerName,
        function(response){
            addAlert(alertTypes.HIDDEN_ITEM);
        }
    );
}
/**
 *pulls up the options to manage a scene if player has the rights
 */
function getManageSceneText() {
    sendRequest("manage.php?function=getManageSceneText",
        function(response){
            addText(response);
        }
    );
}

/**
 *makes sure the player really wants to quit thier job
 */
function quitJobPrompt(){
    cancelWaits();
    addText("Type 'quit' to leave your current job.");
    waitingForTextLine = textLineInputs.QUIT_JOB;
}

/**
 *removes the player's current job
 */
function quitJob() {
    sendRequest("manage.php?function=quitJob",
        function(response){
            addText("You have quit your job");
        }
    );
}
/**
 *hires someone to the rank below you with the given name
 */
function hireEmployee(name){
    sendRequest("manage.php?function=hireEmployee&name="+name,
        function(response){
            //say that a message has been sent
        }
    );
}
/**
 *fires someone who works for you so they loose thier job
 */
function fireEmployee(name) {
    sendRequest("manage.php?function=fireEmployee&name="+name,
        function(response){
            //say that they have been fired
        }
    );
}
////////////////////////////////////////////////////
////////////////////////////////////////////////////
////////////////////////////////////////////////////
////////////////////////////////////////////////////
//small methods

/**
 *adds an alert to the list.
 *does not add to the db
 */
function addAlert(alertType) {
    if (alerts[alertType]) {
        return;
    }
    alerts[alertType] = true;
    document.getElementById("alert").style.color="gold";
}
/**
*removes the given alert from the alerts list.
*does not remove from the database
*/
function removeAlert(alertType) {
    delete alerts[alertType];
}

/**
*Adds a line of text to the screen. Also controls the opacity and left/right columns
*/
function addText(text) {
    document.getElementById(this.textBox).innerHTML += "</br>"+ text;
    document.getElementById(this.OfftextBox).style.opacity =(17-this.currentLine)/17;
    this.currentLine++;
    if (this.currentLine>16) {
        //switch text boxes
        textBox_ = this.textBox;
        this.textBox = this.OfftextBox;
        this.OfftextBox = textBox_;
        //reset opacity, text, line number
        document.getElementById(this.textBox).style.opacity=1;
        document.getElementById(this.textBox).innerHTML = "";
        this.currentLine=0;
    }
}

/**
*Deactivates all spans with class active
*/
function deactivateActiveLinks(){
    var previous = document.getElementsByClassName('active');
    var numDeactivated = 0;
    var numTotal = previous.length;
    while(numDeactivated < numTotal){
        previous = document.getElementsByClassName('active');
        for(var i=0; i<previous.length; i++){
            previous[i].setAttribute("onclick", null);
            previous[i].setAttribute("class", "inactive");
            numDeactivated++;
        }
    }
}

/**
 *adds to the chat file
 */
function speak(inputText){
    sendRequest("FilesBack.php?function=speak&inputText="+inputText,
        function() {}
    );
}

/**
 *returns the text in the input field/text line
 *returns null if invalid input and prints error
 */
function getInputText(){
    var text =  document.getElementById("input").value;
    if (!validateInput(text)) {
        return null;
    }
    return text;
}

/**
* Opens the bottom text area, sets the value to blank
*/
function openTextArea() {
    document.getElementById("textArea").value="";
    document.getElementById("extra").style.display="block";
}
/**
 *sets the text area value
 *use removeHtmlStyling beforehand
 */
function setTextAreaText(text){
    document.getElementById("textArea").value=text;
}
/**
 *sets the message of the text area. does not open it.
 */
function setErrorMessage(message){
    document.getElementById("error").innerHTML = message;
}
/**
 *Returns the text in the text area.
 *returns null and gives an error message if the text area contained < or >
 */
function getTextAreaText(){
    var text = document.getElementById("textArea").value;
    if (!validateInput(text)){
        return null;
    }
    return text;
}
/**
    *Called when the text area done button is clicked
    *looks at waiting stuff
    */
function textAreaSubmit() {
    //clear error
    document.getElementById("error").innerHTML="";
    switch (waitingForTextArea) {
        case(textAreaInputs.PERSONAL_DESCRIPTION):
            setNewDescription();
            break;
        case(textAreaInputs.ITEM_DESCRIPTION):
            addCraftDescription();
            break;
        case(textAreaInputs.NOTE_FOR_ADDING_ITEM):
            addItemToScene();
            break;
        case(textAreaInputs.NEW_ITEM_NOTE_TEXT):
            changeItemNote();
            break;
        case(textAreaInputs.NEW_SCENE_DESC):
            editSceneDesc();
            break;
    }
}
/**
*Closes the text area.
*Ends crafting.
*Ends waiting for text area.
*/
function closeTextArea() {
    document.getElementById("extra").style.display="none";
}

/**
 *toggles if the sound is muted
 */
function toggleMute(){
    if (muted) {
        muted = false;
        document.getElementById("menuMainMute").innerHTML = "Mute";
    }
    else{
        muted = true;
        document.getElementById("menuMainMute").innerHTML = "Unmute";
    }
}
/**
*plays the specified sound.
*does not repeat
*/
function playSound(soundId){
    if (!muted) {
        document.getElementById(soundId).play();
    }
}

/**
 *adds the help text to the screen
 */
function addHelpText(args) {
    addText("Help:");
    addText("-Commands");
    addText("/look : shows where you are");
    addText("/attack : asks for the name of the player you want to attack");
    addText("-Description");
    addText("to set your description, click on your name below the text input. Your description must include all visible items");
    addText("this help text should be moved to a text file");
}

/**
 *cancels waiting stuff
 */
function cancelWaits() {
    switch(waitingForTextArea){
        //Crafting related
        case(textAreaInputs.ITEM_DESCRIPTION):
            addText("you decide not to make the "+itemName);
            itemName="";
            break;
        //personal description related
        case(textAreaInputs.PERSONAL_DESCRIPTION):
            break;
    }
    switch(waitingForTextLine){
        //crafting related
        case(textLineInputs.ITEM_NAME):
            addText("you decide not to make anything");
            break;
        //just combat so far
        case(textLineInputs.TARGET_NAME):
            addText("-canceled");
            break;
    }
    waitingForTextArea = textAreaInputs.NOTHING;
    waitingForTextLine = textLineInputs.NOTHING;
}
/**
 *returns true if the player is waiting for something,
 *  in the line or area
 */
function isWaiting() {
    return(waitingForTextArea != textAreaInputs.NOTHING || waitingForTextLine != textLineInputs.NOTHING);
}
/**
 *removes the html from the text
 *returns the new text
 */
function removeHtmlStyling(text){
    return text.replace(/(<([^>]+)>)/ig,"");
}
/**
 *switches whether the alert text is front loaded or not
 *also tells db
 */
function toggleFrontLoadAlertText() {
    frontLoadAlertText=!frontLoadAlertText;
    var frontLoad;
    if (frontLoadAlertText) {
        frontLoad = 1;
    }
    else{
        frontLoad = 0;
    }
    sendRequest("TextCombat.php?function=setFrontLoadAlerts&load="+frontLoad,
        function(){}
    );
}
/**
 *switches whether the scene text is front loaded or not
 *also tells db
 */
function toggleFrontLoadSceneText(){
    frontLoadSceneText=!frontLoadSceneText;
    var frontLoad;
    if (frontLoadSceneText) {
        frontLoad = 1;
    }
    else{
        frontLoad = 0;
    }
    sendRequest("TextCombat.php?function=setFrontLoadScenes&load="+frontLoad,
        function(){}
    );
}
/**
 *switches whether the keyword text is front loaded or not
 *also tells db
 */
function toggleFrontLoadKeywords(){ 
    frontLoadKeywords=!frontLoadKeywords;
    var frontLoad;
    if (frontLoadKeywords) {
        frontLoad = 1;
    }
    else{
        frontLoad = 0;
    }
    sendRequest("TextCombat.php?function=setFrontLoadKeywords&load="+frontLoad,
        function(){}
    );
}
/**
 *checks for unwanted input
 *returns false on fail
 *pints the error message on its own
 */
function validateInput(text){
    //check for < or >
    if (text.indexOf("<") != -1 || text.indexOf(">") != -1) {
        return "please don't use < or >";
    }
    //check for empty string
    if (text.trim == "") {
        return "that was an empty input";
    }
    return true;
}
/**
 *sends a request to the server
 */
function sendRequest(url, returnFunction){
    request = new XMLHttpRequest();
    request.onreadystatechange = function(){
        if (this.readyState==4 && this.status==200) {
            var response = this.responseText;
            //if an error
            if (response.indexOf("<<") == 0) {
                setErrorMessage(response.replace("<<",""));
            }
            else{
                //success, call function
                returnFunction(response);
            }
        }
    }
    request.open("GET", url, true);
    request.send();
}