///////////////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////
//Globals
var version = 1;

window.onerror = function(msg, url, line) {
    alert("Error: "+msg+" url: "+url+" line: "+line);
};

disableInput();
var frontLoadSceneText;
var frontLoadKeywords;
/**
 *Set up, needed
 */
(function(){
   sendRequest(
        "setup.php",
        "function=setUp$version="+version,
        function(response){
            response = response.split("<>");
            currentScene = parseInt(response[1]);
            frontLoadSceneText = parseInt(response[2]);
            frontLoadKeywords = parseInt(response[3]);
            enableInput();
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
var updater = setInterval("updateChat()", 3000);
/**
 *the class for all waits
 */
function listener(message, onInput, onCancel){
    /**
     *checks to make sure no other listeners are active
     *sets the wait message
     */
    this.start = function(){
        if (textLineListener != null || textAreaListener != null) {
            setErrorMessage("You're busy with something else.");
            return false;
        }
        setWaitMessage(message);
        return true;
    }
    /**
     *calls the function of this listener
     */
    this.onInput = function(input){
        onInput(input);
    }
    /**
     *called when cancelled
     */
    this.onCancel = function(){
        onCancel();
    }
}
var textLineListener = null;
var textAreaListener = null;
/**
 *sets a listener for the text line input
 */
function setTextLineListener(listener_){
    if (listener_.start()) {
        textLineListener = listener_;
    }
}
/**
 *sets a listener for the text area input
 *opens the text area
 */
function setTextAreaListener(listener_){
    if (listener_.start()) {
        openTextArea();
        textAreaListener = listener_;
    }
}
/**
 *closes the text area
 *clears the wait message
 *removes listeners
 */
function endListening() {
    closeTextArea();
    clearWaitMessage();
    if(textLineListener != null){
        textLineListener.onCancel();
        textLineListener = null;
    }
    if(textAreaListener != null){
        closeTextArea();
        textAreaListener.onCancel();
        textAreaListener = null;
    }
}
//text line listeners
var listener_item_name = new listener("Enter the name of the item you are crafting.",
                                            function(input){addCraftName(input);}, function(){}
                                          );
var listener_target_name = new listener("Enter a target name to attack.",
                                            function(input){attack(input);}, function(){}
                                          );
var listener_item_name_to_add_to_scene = new listener("Enter an item name to add.",
                                            function(input){addItemNoteToScenePrompt(input);}, function(){}
                                          );
var listener_item_name_to_remove_from_scene = new listener("Enter an item name to remove.",
                                            function(input){removeItemFromScene(input);}, function(){}
                                          );
var listener_item_name_to_change_note_of = new listener("Enter an item name to change the note of.",
                                            function(input){newNoteTextPromt(input);}, function(){}
                                          );
var listener_quit_job = new listener("Enter 'quit' to leave your job.",
                                            function(input){quitJob(input);}, function(){}
                                          );
//text area listeners
var listener_personal_desc = new listener("Enter your description below.",
                                            function(input){setNewDescription(input);}, function(){}
                                          );
var listener_item_desc = new listener("Enter the item description below.",
                                          function(input){addCraftDescription(input);}, function(){}
                                          );
var listener_new_items_note = new listener("Enter the item's note below.",
                                          function(input){addItemToScene(input);}, function(){}
                                          );
var listener_revised_item_note = new listener("Enter the item note below.",
                                          function(input){changeItemNote(input);},function(){}
                                          );
var listener_new_scene_desc = new listener("Enter the location description below.",
                                          function(input){editSceneDesc(input);},function(){}
                                          );

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
    ACTION: 3,
    KEYWORD: 4
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
    //listener check
    if (textLineListener != null) {
        textLineListener.onInput(inputText);
    }
    //command check
    else if(inputText.indexOf("/") == 0){
        closeTextArea();
        //find command
        inputText = inputText.split(" ");
        switch (inputText[0]) {
            case("/look"):
                deactivateActiveLinks();
                addDesc(spanTypes.SCENE, currentScene);
                break;
            case('/closelook'):
                closeLook();
                break;
            case("/attack"):
                setTextLineListener(listener_target_name);
                break;
            case("/help"):
                addText("<a href='guide.php' target='_newtab'>Guide</a>");
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
            case("/destroy"):
                inputText[0] = "";
                inputText = inputText.join(" ");
                inputText = inputText.trim();
                destroyItem(inputText);
                break;
            default:
                addText(inputText+"..unknown command");
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
                                var info = chatLine.split("<>");//1:target,2:text
                                addText(info[2]);
                                break;
                        }
                    }
                    //if a chat
                    else{
                        addText("<span class='name' onclick='addDesc("+spanTypes.PLAYER+","+text[i]+")'>"+text[i+1]+"</span>:"+chatLine);
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
*Sets the player's new description
*/
function setNewDescription(desc) {
    sendRequest("TextCombat.php","function=updateDescription&Description="+desc,
        function(response) {
            closeTextArea();
            endListening();
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
    endListening();
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
    endListening();
    setTextAreaListener(listener_personal_desc);
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
setTextLineListener(listener_item_name);
}
/**
 *When an item name is given, tells the player to give a description
 */
function addCraftName(name){
    itemName = name;
    //has a name, need a description
    sendRequest("crafting.php","function=getCraftInfo",
        function(response){
            endListening();
            addText("Your craftSkill is "+response+ ". enter the "+itemName+"'s description below. Your tags are: tags not done yet");
            setTextAreaListener(listener_item_desc);
        }
    );
}
/**
 *When an items description is given, and a name was already chosen
 */
function addCraftDescription(desc){
    if (itemName == "") {
        addText("[Something wierd happened. Woops! Please let me know what you did. Thanks.]");
        endListening();
        return;
    }
    alert(itemName);
    //input into database
    sendRequest("crafting.php","function=craftItem&Name="+itemName+"&Description="+desc,
        function(response){
            addText("You make a "+itemName);
            closeTextArea();
            endListening();
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
    setTextLineListener(listener_item_name_to_add_to_scene);
}

/**
 *adds an item to the current scene
 */
function addItemNoteToScenePrompt(name){
    itemName = name;
    endListening();
    setTextAreaListener(listener_new_items_note);
}
/**
 *prompts for what item to remove from the scene
 */
function removeItemFromScenePrompt() {
    setTextLineListener(listener_item_name_to_remove_from_scene);
}
/**
 *adds the item and its note to the scene
 */
function addItemToScene(note){
    endListening();
    sendRequest("manage.php","function=addItemToScene&Name="+itemName+"&Note="+note,
        function(response){
            addText("added "+itemName);
            return;
        }
    );
}
/**
 *removes the given item from the scene
 */
function removeItemFromScene(name){
    endListening();
    sendRequest("manage.php","function=removeItemFromScene&Name="+name,
        function(response){
            addText("you take the "+name);
            return;
        }
    );
}
/**
 *prompts the player for what note they want to change in this scene
 */
function changeItemNotePrompt() {
    addText("what item note would you like to change in this location?");
    endListening();
    setTextLineListener(listener_item_name_to_change_note_of);
}
/**
 *prompts for the new note text
 */
function newNoteTextPromt(name){
    itemName = name;
    endListening();
    setTextAreaListener(listener_revised_item_note);
}
/**
 *prompts for the new scene description
 */
function newSceneDescPrompt() {
    addText("Edit the description below.");
    endListening();
    //get scene desc
    sendRequest("TextCombat.php","function=getDesc&type="+spanTypes.SCENE,
        function(response){
            openTextArea();
            //first is name, second is desc
            response=response.split("<>");
            setTextAreaText(removeHtmlStyling(response[1]));
            setTextAreaListener(listener_new_scene_desc);
        }
    );
}
/**
 *gets the note text and changes the item note
 */
function changeItemNote(note){
    endListening();
    sendRequest("manage.php","function=changeItemNote&Name="+itemName+"&Note="+note,
        function(response){
            addText("changed note for "+itemName);
            return;
        }
    );
}  
/**
 *reuests to change the description of this scene
 */
function editSceneDesc(desc){
    endListening();
    sendRequest("manage.php","function=changeSceneDesc&desc="+desc,
        function(response){
           addText("changed scene description"); 
        }
    );
}
/**
*find who the player want to attack, after /attack
*/
function attack(name) {
    endListening();
    sendRequest("combat.php","function=attack&Name="+name,
        function(){}
    );
}
/**
 *clears the alerts which are not required
 */
function clearAlerts(){
    closeMenu();
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
    inside.innerHTML = "Loading..";
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
    menuInside.innerHTML = "Options:</br>";
    //front load scene text
    if (frontLoadSceneText) {
        menuInside.innerHTML +="<input type='checkbox' onclick='toggleFrontLoadSceneText()' checked='checked'>";
    }
    else{
        menuInside.innerHTML +="<input type='checkbox' onclick='toggleFrontLoadSceneText()'>";
    }
    menuInside.innerHTML +="Front load scene text. About 3 lines.</input></br>";
    //front load keywords
    if (frontLoadKeywords) {
        menuInside.innerHTML +="<input type='checkbox' onclick='toggleFrontLoadKeywords()' checked='checked'>";
    }
    else{
        menuInside.innerHTML +="<input type='checkbox' onclick='toggleFrontLoadKeywords()'>";
    }
    menuInside.innerHTML +="Front load keyword text. About 10 lines.</input>";
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
            response = response.split("<>");
            for(var i=0; i<response.length; i++){
                addText(response[i]);
            }
        }
    );
}

/**
 *makes sure the player really wants to quit thier job
 */
function quitJobPrompt(){
    endListening();
    setTextLineListener(listener_quit_job);
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

/**
 *removes an item from the player's inventory
 */
function destroyItem(itemName){
    sendRequest("TextCombat.php",
                "function=destroyItem&name="+itemName,
                function(response){
                    addText(itemName+" has been destroyed");
                }
    );
}

/**
 *gives additional info about the current scene
 */
function closeLook() {
    sendRequest(
        "TextCombat.php",
        "function=closeLook",
        function(response){
            response = response.split("<>");
            for(var i=0; i<response.length; i++){
                addText(response[i]);
            }
        }
    );
}

/**
 *lets the player become the manager of the scene
 *available from closelook
 */
function beManager(){
    sendRequest(
        "manage.php",
        "function=becomeManager",
        function(response){
            addText("Success, you are now the manager here!");
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
    var input = getTextAreaText();
    if (input == null){
        return;
    }
    clearErrorMessage();
    if (textAreaListener != null) {
        textAreaListener.onInput(input);
    }
}
/**
*Closes the text area.
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
 *returns true if the player is waiting for something,
 *  in the line or area
 */
function isWaiting() {
    return(textAreaListener != null || textLineListener != null);
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
    clearInterval(updater);
    disableInput();
    sendRequest("TextCombat.php",
                "function=logout",
                function(){}
    );
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
 *prevents the player from entering anything
 */
function disableInput() {
    document.getElementById("input").disabled = true;
    document.getElementById("textArea").disabled = true;
}
/**
 *allows input again
 */
function enableInput() {
    document.getElementById("input").disabled = false;
    document.getElementById("textArea").disabled = false;
}
/**
 *sets the wait message and pops up the image
 */
function setWaitMessage(message){
    var waitBox = document.getElementById("wait");
    waitBox.innerHTML = message;
    waitBox.style.visibility = "visible";
}
/**
 *removes the wait message and image
 */
function clearWaitMessage() {
    var waitBox = document.getElementById("wait");
    waitBox.innerHTML = "";
    waitBox.style.visibility = "hidden";
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