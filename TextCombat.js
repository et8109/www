///////////////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////
//Globals
/**
 *Name of the player logged in
 */
var playerName;
/**
 *Remember the amount of items being carried.
 *Store more in an array, with info?
 */
var numItems;
/**Set up
 * Sets up the page:
 * sets playerName to the player's name
 * sets numItems to the number of items the player has
 */
(function(){
    //remember the player's name
    request = new XMLHttpRequest();
    request.onreadystatechange = function(){
	if (this.readyState==4 && this.status==200) {
            var response = this.responseText.split("<>");
	    playerName = response[0];
            numItems = parseInt(response[1]);
            var adminLevel = parseInt(response[2]);
            switch(adminLevel){
                case(1):
                    document.getElementById("hub").innerHTML+="</br><a href='edit.php'>edit</a>";
                    break;
            }
            for(var i=3; i<response.length; i++){
                addAlert(response[i]);
            }
            //allow input
            document.getElementById("input").disabled=false;
	}
    }
    request.open("GET", "TextCombat.php?function=setUp", true);
    request.send();
}());

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
    ITEM_DESCRIPTION : 2
};
/**
 *The possible inputs from the main text line
 */
var textLineInputs = {
    NOTHING : 0,
    ITEM_NAME : 1,
};
var waitingForTextArea = textAreaInputs.NOTHING;
var waitingForTextLine = textLineInputs.NOTHING;
/**
 A bunch of types of random stuff.
 Each should have:
 id
 description
 referenced in sql when crafting items
 not player made means no db lookup required
 */
var types = {
    ITEM: 0,
    PLAYER: 1,
    SCENE: 2,
    KEYWORD: {
        materials: 3,
        craftQualities: 4
    }
}
/**
 *The descriptions for things which are not player-made.
 *place the longest words, which include others, first.
 */
var keywords = {
    materials: {
        0: {
            words: ["wooden","wood", "bark"],
            text: "Not very strong, it is usually used for the handles of things. The useful ends of object should have a stronger material.",
            getLinkText: function(wordNum){
                return "<span class='keyword' onclick='addDescKeyword("+types.KEYWORD.materials+", 0, "+wordNum+")'>"+this.words[wordNum]+"</span>";
            },
            
        },
        1: {
            words: ["metallic", "metal", "iron", "bronze"],
            text: "A strong material, but it must be mined and then smelted into a practical shape.",
            getLinkText: function(wordNum){
               return "<span class='keyword' onclick='addDescKeyword("+types.KEYWORD.materials+", 1, "+wordNum+")' >"+this.words[wordNum]+"</span>";
            }
        }
    },
    craftQualities: {
        //crafting level 0:
        0: {
            words: ["plain", "simple"],
            text: "It does what it's supposed to.",
            getLinkText: function(wordNum){
                return "<span class='keyword' onclick='addDescKeyword("+types.KEYWORD.craftQualities+", 0, "+wordNum+")'>"+this.words[wordNum]+"</span>";
            }
        },
        //crafting level 1:
        1: {
            words: ["excellent", "beautiful", "exquisite"],
            text: "Very fancy. Whoever made this is a skilled crafstman",
            getLinkText: function(wordNum){
                return "<span class='keyword' onclick='addDescKeyword("+types.KEYWORD.craftQualities+", 1, "+wordNum+")'>"+this.words[wordNum]+"</span>";
            }
        }
    }
}

var currentLine=0; //17 is max, arbitrary
var textBox="textBox1";
var OfftextBox="textBox2";

/**
 *current active alerts
 */
var alerts =[];
/**
 *number of current alerts
 */
var numAlerts = 0;
/**
 *all the possible alerts.
 *stored in db as numbers, so must be finable by number
 */
var alertTypes ={
    100: "Your new item has been added to your description. You should edit it soon."
}
/**
 *adds an alert to the list.
 *input is the number, not the alertType subvar
 */
function addAlert(alertType) {
    for(alertNum in alerts){
        if (alerts[alertNum] == alertType) {
            return;
        }
    }
    alerts[numAlerts] = alertType;
    numAlerts++;
    document.getElementById("alert").innerHTML = numAlerts+" alerts";
    //if the first alert, make button visible.
    if (numAlerts == 1) {
        document.getElementById("alert").style.color="gold";
    }
}
/**
*removes the given alert from the alerts list.
*does not remove from the database
*/
function removeAlert(alertType) {
    alert("remove alert called");
    for(var i=0; i<alerts.length; i++){
        if (alerts[i] == alertType) {
            alert("found");
            numAlerts--;
            document.getElementById("alert").innerHTML = numAlerts+" alerts";
            if (numAlerts == 0) {
                alert("hiding");
                document.getElementById("alert").style.color="gold";
            }
            while(i+1<alerts.length){
                alerts[i] = alerts[i+1];
            }
            alerts[alerts.length-1] = null;
            return;
        }
    }
}
/**
 *Holds all the crafting stuff
 */
var Crafter ={
    itemName : "",
    craftSkill: 0
}

var muted = false;
/////////////////////////////////////////////////////
//////////////////////////////////////////////////////
//////////////////////////////////////////////////////
//////////////////////////////////////////////////////
/**
*Returns if key was not enter.
*Checks for waiting, commands with /, and talking
*/
function textTyped(e){
    //if enter button was not pressed, do nothing
    if(event.keyCode != 13){
        return;
    }
    var inputText = getInputText();
    //command check
    if(inputText.indexOf("/") == 0){
        //cancel waiting stuff?
        switch (inputText) {
            case("/look"):
                deactivateActiveLinks();
                addDesc(types.SCENE, -1);
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
        }
    }
    
    //not waiting, and not command
    else{
        if (inputText == "") {
            return;
        }
        request = new XMLHttpRequest();
        request.open("GET", "FilesBack.php?function=speak&inputText="+inputText, true);
        request.send();
    }
    
    //always: clear input
    document.getElementById("input").value="";
}

/**
*gets the lines of chat not yet seen.
*adds the lines to the text box
*/
function updateChat(){
    request = new XMLHttpRequest();
    request.onreadystatechange = function(){
	if (this.readyState==4 && this.status==200) {
	    response = this.responseText;
	    response = response.split("\r\n");
	    if (response.length>1) {
		for(var i=0; i<response.length; i+=3){
		    addText("<span class='name' onclick='addDesc("+types.PLAYER+","+response[i]+")'>"+response[i+1]+"</span>: "+response[i+2]);
		}
	    }
	}
    }
    request.open("GET", "FilesBack.php?function=updateChat", true);
    request.send();
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
 *prints the description into the text box.
 */
function addDesc(type, id) {
    var table;
    //find the table the description is in
    switch(type){
        case(types.ITEM):
            table="items";
            break;
        case(types.PLAYER):
            table="playerInfo";
            break;
        case(types.SCENE):
            table="scenes";
            break;
        default:
            addText("type "+type+" not found");
            return;
    }
    
    request = new XMLHttpRequest();
    request.onreadystatechange = function(){
        if (this.readyState==4 && this.status==200) {
            response = this.responseText.split("<>");
            switch (table) {
                case('playerInfo'):
                    addText("<span class='name' onclick='addDesc("+types.PLAYER+","+id+")'>"+response[0]+"</span>");
                    addText(response[1]);
                    addText("<span class='fight' onclick='attack("+id+")'>~Attack~</span>");
                break;
            
                case('scenes'):
                    addText("<span class='sceneName'>"+response[0]+"</span>");
                    addText(response[1]);
                break;
            
                case('items'):
                    addText("<span class='item' onclick='addDesc("+types.ITEM+","+id+")'>"+response[0]+"</span>");
                    addText(response[1]);
                break;
            }
        }
    }
    request.open("GET", "TextCombat.php?function=getDesc&table="+table+"&ID="+id, true);
    request.send();
    }

/**
 *prints the description into the text box
 */
function addDescKeyword(type, id, wordNum){
    switch (type) {
        case(types.KEYWORD.materials):
            addText(keywords.materials[id].getLinkText(wordNum));
            addText(keywords.materials[id].text);
            break;
        case(types.KEYWORD.craftQualities):
            addText(keywords.craftQualities[id].getLinkText(wordNum));
            addText(keywords.craftQualities[id].text);
            break;
    }
}
/**
*Deactivates all spans with class active
*/
function deactivateActiveLinks(){
    var previous = document.getElementsByClassName("active");
    for(var i=0; i<previous.length; i++){
        previous[i].setAttribute("onclick", null);
        previous[i].setAttribute("class", "inactive");
    }
    //sometimes the last is skipped
    skipped = document.getElementsByClassName("active");
    if (skipped.length>0){
        skipped[0].setAttribute("onclick", null);
        skipped[0].setAttribute("class", "inactive");
    }
}
    
////////////////////////////////////////////////////
////////////////////////////////////////////////////
////////////////////////////////////////////////////
////////////////////////////////////////////////////
    
/**
*Sets the player's new description after checking for inventory items, no < or >.
*Do not call directly! call check description first!
*/
function setNewDescription(newDescription, itemList) {
    //edit description so items stand out
    closeTextArea();
    //new item, cange description alert
    removeAlert(100);
    waitingForTextArea=textAreaInputs.NOTHING;
    for (var i=0; i<itemList.length-1; i+=2) {
        newDescription=newDescription.replace(itemList[i],"<span class='item' onclick='addDesc("+types.ITEM+","+itemList[i+1]+")' >"+itemList[i]+"</span>");
    }
    //add description to database
    request = new XMLHttpRequest();
    request.open("GET", "TextCombat.php?function=updateDescription&Description="+newDescription, true);
    request.send();
}

/**
 *Check to make sure everything needed is in the player description.
 *Calls set new description if success
 */
function checkNewDescription(){
    alert("check new description");
    var newDescription = getTextAreaText();
    setTextAreaMessage("");
    //no < or > found
    //get the list of item names
    request = new XMLHttpRequest();
    request.onreadystatechange = function(){
        if (this.readyState==4 && this.status==200) {
            response = this.responseText;
            alert(response);
            response = response.split("<>");
            //make sure the description contains all the items
            for (var i=0; i<response.length-1; i+=2) {
                if(newDescription.indexOf(response[i]) == -1){
                    //if the item is not in the description
                    setTextAreaMessage("please use all your visible items in your description. "+response[i]+" was not found.");
                    return;
                }
            }
            //if the description is ok. response is the item list
            setNewDescription(newDescription, response);
        }
    }
    request.open("GET", "TextCombat.php?function=getVisibleItems", true);
    request.send();
}

/**
* Move scene, then print new scene description.
* End crafting.
*/
function walk(newSceneId) {
deactivateActiveLinks();
request = new XMLHttpRequest();
request.onreadystatechange = function(){
        if (this.readyState==4 && this.status==200) {
            //-1 means current scene
            addDesc(types.SCENE, newSceneId);
        }
}
request.open("GET", "TextCombat.php?function=moveScenes&newScene="+newSceneId, true);
request.send();
closeTextArea();
}

/**
 *replaces all instances of the keyword subtype with it's linktext
 */
function replaceKeywords(keywordSubtype, description){
    for(k in keywordSubtype){
        for(w in keywordSubtype[k].words){
            var word = keywordSubtype[k].words[w];
            if (description.indexOf(word) > -1) {
                description = description.replace(word, keywordSubtype[k].getLinkText(w));
                break;
            }
        }
    }
    return description;
}
/**
 *
 */
function replaceCraftQuality(description, craftSkill) {
    var i=0;
    for(q in keywords.craftQualities){
        if (i>craftSkill) {
            return description;
        }
        for(w in keywords.craftQualities[q].words){
            var word = keywords.craftQualities[q].words[w];
            if (description.indexOf(word) > -1) {
                description = description.replace(word, keywords.craftQualities[q].getLinkText(w));
                break;
            }
        }
        i++;
    }
    return description;
}
/**
 *opens the alert box.
 *called by span on page
 */
function openAlerts(){
    document.getElementById("alertMainInside").innerHTML = "";
    document.getElementById("alertMain").style.visibility="visible";
    for(var i=0; i<alerts.length; i++){
        document.getElementById("alertMainInside").innerHTML +="</br>"+alertTypes[alerts[i]];
    }
}
/**
 *closes the alert box.
 *called by the close button on the page
 */
function closeAlerts(){
document.getElementById("alertMain").style.visibility="hidden";
}
/**
 *returns the text int the input field
 */
function getInputText(){
    return document.getElementById("input").value;
}
////////////////////////////////////////////////////
////////////////////////////////////////////////////
////////////////////////////////////////////////////
////////////////////////////////////////////////////

/**
    *open text area and display player description.
    *wait for a new description input
    */
function displayMyDesc() {
    openTextArea("enter a new description");
    request = new XMLHttpRequest();
    request.onreadystatechange = function(){
        if (this.readyState==4 && this.status==200) {
            response = this.responseText;
            //remove styling, not visible in text area
            document.getElementById("textArea").value=response.replace(/(<([^>]+)>)/ig,"");
        }
    }
    request.open("GET", "TextCombat.php?function=getPlayerDescription", true);
    request.send();
    waitingForTextArea = textAreaInputs.PERSONAL_DESCRIPTION;
}
/*
    * Opens the bottom text area, sets the value and error to blank
    */
function openTextArea(message) {
    document.getElementById("descriptionError").innerHTML = message ? message : "";
    document.getElementById("textArea").value="";
    document.getElementById("extra").style.display="block";
}

/**
 *sets the message of the text area. does not open it.
 */
function setTextAreaMessage(message){
    document.getElementById("descriptionError").innerHTML = message;
}

/**
 *Returns the text in the text area.
 *also sanitizes
 */
 function getTextAreaText(){
    var text = document.getElementById("textArea").value;
    if (text.indexOf("<") != -1 || text.indexOf(">") != -1) {
        setTextAreaMessage("please don't use < or >");
    }
    return text;
}

/**
*Closes the text area.
*Ends crafting.
*Ends waiting for text area.
*/
function closeTextArea() {
    document.getElementById("extra").style.display="none";
    endCrafting();
    waitingForTextArea = textAreaInputs.NOTHING;
}

/**
    *Called when the text area done button is clicked
    *looks at waiting stuff
    */
function textAreaSumbit() {
    switch (waitingForTextArea) {
        case(textAreaInputs.PERSONAL_DESCRIPTION):
            checkNewDescription();
            break;
        case(textAreaInputs.ITEM_DESCRIPTION):
            addCraftDescription();
            break;
    }
}

////////////////////////////////////////////////////
////////////////////////////////////////////////////
////////////////////////////////////////////////////
////////////////////////////////////////////////////

/**
 *When the anvil is clicked. checks if the player can start crafting.
 *asks for item name.
 */
function startCraft(){
//make sure they only have 9 or less items
if (numItems > 9) {
    addText("It seems your can't carry any more items..");
    return;
}
//if waiting for something.
if (waitingForTextLine != textLineInputs.NOTHING || waitingForTextArea != textAreaInputs.NOTHING) {
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
    Crafter.itemName = getInputText();
    var itemName = Crafter.itemName;
    openTextArea(itemName+"'s description");
    //has a name, need a description
    request = new XMLHttpRequest();
    request.onreadystatechange = function(){
        if (this.readyState==4 && this.status==200) {
            Crafter.craftSkill = parseInt(this.responseText);
            addText("Your craftSkill is "+this.responseText + ". enter the "+itemName+"'s description below. Your tags are: tags not done yet");
            waitingForTextArea = textAreaInputs.ITEM_DESCRIPTION;
            waitingForTextLine = textLineInputs.NOTHING;
        }
    }
    request.open("GET", "TextCombat.php?function=getCraftInfo", true);
    request.send();
    //sound
    playSound("anvil");
}

/**
 *When and items description is given, and a name was already chosen
 */
function addCraftDescription(){
    itemName = Crafter.itemName;
    if (itemName == "") {
        addText("[Something wierd happened. Woops! Please let me know what you did. Thanks.]");
        return;
    }
    itemDescription = getTextAreaText();
    itemDescription = replaceKeywords(keywords.materials, itemDescription);
    alert(itemDescription);
    //make sure the description was changed
    if (itemDescription.indexOf("<") == -1) {
        setTextAreaMessage("please include a material in your description");
        return;
    }
    //make sure a quality was used
    var lastDesc = itemDescription;
    itemDescription = replaceCraftQuality(itemDescription, Crafter.craftSkill);
    if (lastDesc == itemDescription) {
        setTextAreaMessage("please include a quality equal or lower than your craft skill");
        return;
    }
    //input into database
    numItems++;
    addText("You make a "+itemName);
    request = new XMLHttpRequest();
    request.open("GET", "TextCombat.php?function=craftItem&Name="+itemName+"&Description="+itemDescription, true);
    request.send();
    waitingForTextArea = textAreaInputs.NOTHING;
    closeTextArea();
    //new item in inventory alert
    //also in db
    addAlert(100);
}

/**Called by closeTextArea!!!
 *  does not close hub
 *sets item name and craft skill to null.
 *resets waiting if waiting for crafting things.
 */
function endCrafting(){
    if (waitingForTextArea == textAreaInputs.ITEM_DESCRIPTION) {
        addText("you decide not to make the "+Crafter.itemName);
        waitingForTextArea = textAreaInputs.NOTHING;
    }
    if (waitingForTextLine == textLineInputs.ITEM_NAME){
        addText("you decide not to make anything");
        waitingForTextLine = textLineInputs.NOTHING;
    }
    Crafter.itemName = null;
    Crafter.craftSkill = null;
}

////////////////////////////////////////////////////
////////////////////////////////////////////////////
////////////////////////////////////////////////////
////////////////////////////////////////////////////
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
 *toggles if the sound is muted
 */
function toggleMute(){
    if (muted) {
        muted = false;
        document.getElementById("alertMainMute").innerHTML = "Mute";
    }
    else{
        muted = true;
        document.getElementById("alertMainMute").innerHTML = "Unmute";
    }
}

////////////////////////////////////////////////////
////////////////////////////////////////////////////
////////////////////////////////////////////////////
////////////////////////////////////////////////////

/**
 *instanciates two (or more) people in combat
 *info which both clients need:
 *combat stats from items
 *combat descision tree
 */
function attack(enemyID){
    addText("[your skill level], [enemy skill level], you [lunge with your [weapon]]");
    addText("[enemyname] is hit for [this much damage]");
}