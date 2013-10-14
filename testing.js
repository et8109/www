//add to index if you want to test
//start at scene 100

if(!confirm("testing is about to start. This might screw up your account, please login as tester to test")){
    alert("canceled");
    exit();
}
alert("starting");

//helper functions
var numTests = 0;
/**
 *makes sure the message is not null, then tell the tester
 */
function tell(msg){
    if (msg == undefined) {
        alert("test "+numTests+" failed and there was no message");
    }
    else{
        alert(msg);
    }
}

function assertNull(item, msg){
    numTests++;
    if (item != null) {
        tell(msg);
    }
}
function assertNotUndefined(item, msg) {
    numTests++;
    if (item == undefined) {
        tell(msg);
    }
}
function assertTrue(stmt, msg){
    numTests++;
    if (!stmt) {
        tell(msg);
    }
}
function assertFalse(stmt, msg) {
    numTests++;
    if (stmt) {
        tell(msg);
    }
}
function assertEqual(item1, item2, msg){
    numTests++;
    if (item1 != item2) {
        tell(msg);
    }
}
function assertNotEqual(item1, item2, msg){
    numTests++;
    if (item1 == item2) {
        tell(msg);
    }
}

function typeAndSubmit(msg) {
    var e = document.createEvent("KeyboardEvent");
    e.keyCode = 13;
    document.getElementById("input").value = msg;
    textTyped(e);
}
function resetTextBox() {
    document.getElementById(textBox).innerHTML = "";
    currentLine = 0;
}
function getTextBoxText(){
    return document.getElementById(textBox).innerHTML;
}

function assertRequest(options){
    if (options.dbFunction == null) {
        alert("a db fnction was not chosen");
        return;
    }
    request = new XMLHttpRequest();
    request.onreadystatechange = function(){
        if (this.readyState==4 && this.status==200) {
            /*if (options.checkForNotEmptyTextBox) {
                    assertTrue(getTextBoxText() != "", options.message+": check for empty text box");
            }*/
            if (options.toCompareResultTo != null) {
                assertEqual(options.toCompareResultTo, this.responseText, options.message+": compare result to");
            }
        }
    }
    request.open("GET", "testingBack.php?function="+options.dbFunction, true);
    request.send();
}

function requestOptions (){
    this.checkForNotEmptyTextBox = false;
    this.toCompareResultTo = null;
    this.dbFunction = null;
    this.message = "options message was not set";
}

var dbFunctions = {
    getCurrentDbScene : "getCurrentScene"
}


//global variables
assertNull(playerName, "playerName is not null");
assertNull(numItems, "numItems is not null at start");
//assertNotUndefined(updater, "updater has not been set");
assertNotUndefined(textAreaInputs, "textAreaInputs is undefined");
assertNotUndefined(textLineInputs,"textLineInputs is undefined");
assertEqual(waitingForTextArea, textAreaInputs.NOTHING, "waitingForTextArea is not nothing");
assertEqual(waitingForTextLine, textLineInputs.NOTHING, "waitingForTextLine is not nothing");
assertNotUndefined(types, "types is undefined");
assertNotUndefined(keywords, "keywords is undefined");
assertEqual(currentLine, 0, "currentLine is not 0");
assertEqual(textBox, "textBox1", "textBox is not textBox1");
assertEqual(OfftextBox, "textBox2", "textBox is not textBox2");
assertEqual(alerts[0], null, "alerts is set to something");
assertEqual(numAlerts, 0, "numAlerts is not 0");
assertEqual(Crafter.itemName, "", "crafter itemName is not emptystring");
assertEqual(Crafter.craftSkill, 0, "crafter craftSkill is not 0");
assertEqual(muted, false, "mute is true");

//that is, html is loaded
window.onload = function(){
    //textTyped
    /*resetTextBox();
    typeAndSubmit("testing");*/
    //updateChat
    //addText
    resetTextBox();
    addText("text to add");
    assertEqual(getTextBoxText(), "<br>text to add", "addText did not add the correct text");
    assertEqual(currentLine, 1, "currentLine was not incremented after addText");
    //doesn't look at opacity
    
    //addDesc
    /*resetTextBox();
    addDesc(types.PLAYER,0);
    setTimeout(assertEqual(getTextAreaText(), "testers description."), 500);*/
    //addDescKeyword
    resetTextBox();
    addDescKeyword(types.KEYWORD.materials,0,0);
    assertEqual(getTextBoxText(), ("<br>"+keywords.materials[0].getLinkText(0)+"<br>"+keywords.materials[0].text).replace(/'/g,'"') ,"wood desc was not added correctly");
    
    //deactivateActiveLinks
    
    //checkNewDescription, setNewDescription
        
    //replaceKeywords, replaceCraftQuality
    //addAlert, removeAlert
    
    //db add alert, remove alert
    
    //openAlerts, closeAlerts
    //getInputText
    document.getElementById("input").value="inputted";
    assertEqual(getInputText(), "inputted", "getInputText got the wrong value");
    
    //displayMyDesc
    //openTextArea
    //setTextAreaMessage
    //getTextAreaText
    //closeTextArea
    //textAreaSumbit
    //startCraft
    //everything combat related
    //addCraftName
    //addCraftDescription
    //endCrafting
    //playSound
    //toggleMute
    muted = false;
    toggleMute();
    assertTrue(muted, "toggleMute did not toggle to true");
    toggleMute();
    assertFalse(muted, "toggleMute did not toggle to false");
    
    
    alert(numTests+" non-db tests done. db tests started.");
    //////////////////////////////////////////////////////////
    //////////////////////////////////////////////////////////
    //////////////////db tests////////////////////////////////
    //////////////////////////////////////////////////////////
    
    
    //walk
    resetTextBox();
    walk(101);
    var options = new requestOptions();
    //options.checkForNotEmptyTextBox = true;
    options.toCompareResultTo = 101;
    options.dbFunction = dbFunctions.getCurrentDbScene;
    options.message = "walk did not work";
    assertRequest(options);

}
