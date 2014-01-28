///////////////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////
//Globals

var frontLoadSceneText;
var frontLoadKeywords;
/**
 *Set up, needed
 */
(function(){
   sendRequest(
        "setup.php",
        "function=setUp",
        function(response){
            response = response.split("<>");
            currentScene = parseInt(response[1]);
            frontLoadSceneText = parseInt(response[2]);
            frontLoadKeywords = parseInt(response[3]);
            document.getElementById("input").disabled = false;
        }
    ); 
}());

//[id][0:name, 1:description]
var sceneText={};
if (frontLoadSceneText) {
    sendRequest(
        "setup.php",
        "function=frontLoadScenes",
        function(response){
            var sceneTextsAndIDs = response.split("<>");
            for(var i=1; i<sceneTextsAndIDs.length; i+=3){
                sceneText[parseInt(sceneTextsAndIDs[i])] = [sceneTextsAndIDs[i+1],sceneTextsAndIDs[i+2]];
            }
        }
    );
}

//[word][0: span text 1: desc] //keyword type not needed
var keywordText={};
if (frontLoadKeywords) {
    sendRequest(
        "setup.php",
        "function=frontLoadKeywords",
        function(response) {
            var keywordTextAndDesc = response.split("<>");
            for(var i=1; i<keywordTextAndDesc.length; i+=3){
                keywordText[keywordTextAndDesc[i]] = [keywordTextAndDesc[i+1], keywordTextAndDesc[i+2]];
                //keywordText[keywordTextAndDesc[i]][0] = keywordTextAndDesc[i+1];
                //keywordText[keywordTextAndDesc[i]][1] = keywordTextAndDesc[i+2];
            }
        }
    );
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

var textBox="textBox1";
var OfftextBox="textBox2";

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
    if(event.keyCode != 13){
        //if enter button was not pressed, do nothing
        return;
    }
    //reset prev input index
    clearErrorMessage();
    var inputText = getInputText();
    //make sure input is valid
    if (inputText == null) {
        //nothing, skips to clear input
    }
    //command check
    else if(inputText.indexOf("/") == 0){
        closeTextArea();
        cancelWaits();
        //find command
        inputText = inputText.split(" ");
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
                inputText[0] = "";
                inputText = inputText.join(" ");
                inputText = inputText.split(" in ");
                putItemIn(inputText[0].trim(), inputText[1].trim());
                break;
            case("/take"):
                inputText[0] = "";
                inputText = inputText.join(" ");
                inputText = inputText.split(" from ");
                takeItemFrom(inputText[0].trim(), inputText[1].trim());
                break;
            case("/manage"):
                getManageSceneText();
                break;
            case("/quitjob"):
                quitJobPrompt();
                break;
            case("/hire"):
                inputText[0] = "";
                inputText = inputText.join("");
                hireEmployee(inputText);
                break;
            case("/fire"):
                inputText[0] = "";
                inputText = inputText.join("");
                fireEmployee(inputText);
                break;
            case("/self"):
                addPlayerInfo();
                break;
            default:
                addText(inputText+"..unknown command");
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
    sendRequest("FilesBack.php","function=updateChat",
        function(response){
            response = response.split(">>>");
            var numAlerts = response[1];
            var text = response[0].split("\r\n");
	    if (text.length>1) {
		for(var i=0; i<text.length; i+=3){
                    var chatLine = text[i+2];
                    //if an action, not a chat
                    if (chatLine.indexOf("<") == 1) {
                        var type = parseInt(chatLine.charAt(2));
                        switch(type){
                            case(actionTypes.ATTACK):
                                //let the player know somehow that they were attacked, attak sound
                                var info = chatLine.split("<>");//1:id, 2:text
                                addText(info[2]);
                                break;
                            case(actionTypes.WALKING):
                                //footsteps sound
                                var info = chatLine.split("<>");//1:name, 2:id
                                addText(info[2]);
                                break;
                        }
                    }
                    //if a chat
                    else{
                        addText("<span class='name' onclick='addDesc("+spanTypes.PLAYER+","+text[i]+")'>"+text[i+1]+"</span>: "+chatLine);
                    }
                }
            }
            //alerts
            setAlertButton(parseInt(numAlerts));
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
    sendRequest("TextCombat.php","function=getDesc&type="+type+"&ID="+id,
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
    sendRequest("TextCombat.php","function=updateDescription&Description="+newDescription,
        function(response) {
            closeTextArea();
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
    sendRequest("TextCombat.php","function=moveScenes&newScene="+newSceneId,
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
    openTextArea();
    sendRequest("TextCombat.php","function=getDesc&type="+spanTypes.PLAYER,
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
    openTextArea();
    //has a name, need a description
    sendRequest("crafting.php","function=getCraftInfo",
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
    sendRequest("crafting.php","function=craftItem&Name="+itemName+"&Description="+itemDescription,
        function(response){
            addText("You make a "+itemName);
            closeTextArea();
            waitingForTextArea = textAreaInputs.NOTHING;
            cancelWaits();
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
    sendRequest("TextCombat.php","function=getItemsInScene",
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
    sendRequest("manage.php","function=addItemToScene&Name="+itemName+"&Note="+noteText,
        function(response){
            addText("added "+itemName);
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
    sendRequest("manage.php","function=removeItemFromScene&Name="+itemName,
        function(response){
            addText("you take the "+itemName);
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
    sendRequest("TextCombat.php","function=getDesc&type="+spanTypes.SCENE,
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
    sendRequest("manage.php","function=changeItemNote&Name="+itemName+"&Note="+noteText,
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
    sendRequest("manage.php","function=changeSceneDesc&desc="+desc,
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
    sendRequest("combat.php","function=attack&Name="+name,
        function(){}
    );
}
/**
 *clears the alerts which are not required
 */
function clearAlerts(){
    sendRequest("TextCombat.php",
                "function=clearAlerts",
        function(){}
    );
}
/**
 *opens the menu and the first page
 */
function openMenu(){
    openAlerts();
    document.getElementById("menuMain").style.visibility="visible";
}
/**
 *shows alerts in menu box
 */
function openAlerts(){
    var inside = document.getElementById("menuMainInside");
    sendRequest(
        "TextCombat.php",
        "function=getAlertMessages",
        function(response){
            inside.innerHTML = "Alerts:";
            inside.innerHTML += response;
            addAlertsEnding(response!="");
        }
    );
}
/**
 *adds the ending to the alerts menu
 */
function addAlertsEnding(alertsBool) {
    var inside = document.getElementById("menuMainInside");
    var clearButton = "</br><span id='clearAlertsButton' onclick='clearAlerts()'>[Clear]</span>";
    var noAlerts = "</br>None";
    if (alertsBool) {
        inside.innerHTML+=clearButton;
    } else{
        inside.innerHTML+=noAlerts;
    }
}
/**
 *closes the menu
 */
function closeMenu(){
document.getElementById("menuMain").style.visibility="hidden";
}
/**
 *displays the options in the alert box
 */
function openOptions(){
    var menuInside = document.getElementById("menuMainInside");
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
    sendRequest("TextCombat.php","function=putItemIn&itemName="+itemName+"&containerName="+containerName,
        function(response){
        }
    );
}
/**
 *removes an item from a container
 */
function takeItemFrom(itemName, containerName){
    sendRequest("TextCombat.php","function=takeItemFrom&itemName="+itemName+"&containerName="+containerName,
        function(response){
        }
    );
}
/**
 *pulls up the options to manage a scene if player has the rights
 */
function getManageSceneText() {
    sendRequest("manage.php","function=getManageSceneText",
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
    sendRequest("manage.php","function=quitJob",
        function(response){
            addText("You have quit your job");
        }
    );
}
/**
 *hires someone to the rank below you with the given name
 */
function hireEmployee(name){
    sendRequest("manage.php","function=hireEmployee&name="+name,
        function(response){
            addText(name+" has been hired");
        }
    );
}
/**
 *fires someone who works for you so they loose thier job
 */
function fireEmployee(name){
    sendRequest("manage.php",
                 "function=fireEmployee&name="+name,
        function(response){
            addText(name+" has been hired");
        }
    );
}
/**
 *displays some info about the player
 */
function addPlayerInfo(){
    sendRequest("TextCombat.php","function=getPlayerInfo",
        function(response){
            response = response.split("<>");
            for(var i=0; i<response.length; i++){
                addText(response[i]);
            }
        }
    );
}
////////////////////////////////////////////////////
////////////////////////////////////////////////////
////////////////////////////////////////////////////
////////////////////////////////////////////////////
//small methods
/**
*sets the alert button
*/
function setAlertButton(numAlerts) {
    var button = document.getElementById("alert");
    button.innerHTML="alerts["+numAlerts+"]";
    if (numAlerts>0) {
        button.style.color = "gold";
    } else{
        button.style.color = "black";
    }
}
/**
 *sets the text line to the input text
 */
function setTextLine(text){
    document.getElementById("input").value = text;
}

/**
*Adds a line of text to the screen. Also controls the opacity and left/right columns
*/
function addText(text) {
    var currentHeight = document.getElementById(this.textBox).offsetHeight;
    var maxHeight = (document.height || document.body.offsetHeight)-document.getElementById("hub").offsetHeight;
    document.getElementById(this.textBox).innerHTML += "</br>"+ text;
    document.getElementById(this.OfftextBox).style.opacity =(maxHeight-currentHeight)/maxHeight;
    if (currentHeight+80>maxHeight) {
        //switch text boxes
        var textBox_ = this.textBox;
        this.textBox = this.OfftextBox;
        this.OfftextBox = textBox_;
        //reset opacity, text, line number
        document.getElementById(this.textBox).style.opacity=1;
        document.getElementById(this.textBox).innerHTML = "";
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
    sendRequest("FilesBack.php","function=speak&inputText="+inputText,
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
    clearErrorMessage();
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
    sendRequest("TextCombat.php","function=setFrontLoadScenes&load="+frontLoad,
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
    sendRequest("TextCombat.php","function=setFrontLoadKeywords&load="+frontLoad,
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
 *removes the player from the sceneplayers list
 */
function logout() {
    alert("logging out");
}
/**
 *sends a request to the server
 */
function sendRequest(url,params,returnFunction){
    var request = new XMLHttpRequest();
    request.open("POST",url);
    request.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    request.setRequestHeader("Content-length", params.length);
    request.setRequestHeader("Connection", "close");
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
    request.send(params);
}
/**
 *sets the error message.
 */
function setErrorMessage(message){
    document.getElementById("error").innerHTML = message;
    document.getElementById("errorPoint").style.visibility = "visible";
}
/**
 *clears the error message
 */
function clearErrorMessage(args) {
    document.getElementById("error").innerHTML = "";
    document.getElementById("errorPoint").style.visibility = "hidden";
}