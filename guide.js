//set constants
function command(name, syntaxArray, desc){
    this.name = name;
    this.snytaxArray = syntaxArray;
    this.desc = desc;
    this.getText = function(){
        return (this.name+" : "+this.snytaxArray+"</br>"+this.desc);
    };
}
var commands = [
    new command("/look",[],"Shows where you are."),
    new command("/closelook",[],"Shows details about where you are. You can become a manager there if no one else is."),
    new command("/attack",["[targetname]"],"Attacks an enemy in the same location."),
    new command("/give",["[itemname]","to","[playername]"],"gives an item to another player."),
    new command("/help",[],"It's a link to this page."),
    new command("/put",["[item]","in","[container]"],"Puts an item into a container item."),
    new command("/take",["[item]","from","[container]"],"Removes an item from a container item."),
    new command("/manage",[],"Pulls up the options for managers. You must be in the location you manage."),
    new command("/quitjob",[],"Quit your job."),
    new command("/hire",["[playername]"],"Hire someone to the position below you. You must be in the correct location."),
    new command("/fire",["[playername]"],"Fires someone who works below you."),
    new command("/self",[],"Displays info about yourself."),
    new command("/destroy",["[itemname]"],"destroys an item you possess."),
    new command("/regen",[],"Completely heals you if you are in a sanctuary."),
    new command("/forget",[],"Remove a spell keyword you possess."),
    new command("/read",["[bookname]"],"Read a book in the current location."),
    new command("/cast",["[spellname]"],"Cast a spell you know. Spell names can be found in books.")
];
function keyword(word){
    this.word = word;
    this.getText = function(){
        return word;
    }
}
var keywords = [
    new keyword("simple"),
    new keyword("excellent"),
    new keyword("wooden"),
    new keyword("bag"),
    new keyword("necromancer"),
    new keyword("anvil"),
    new keyword("sanctuary")
];
function activity(name){
    this.name = name;
    this.getText = function(){
        return name;
    }
}
var activities = [
    new activity("talk to people"),
    new activity("craft items"),
    new activity("attack people"),
    new activity("explore the world"),
    new activity("manage a shop"),
    new activity("become a lord or a monarch"),
    new activity("fight some creatures")
];
//create page
//commands
document.write("<div id='commands'>");
for(c in commands){
    document.write(commands[c].getText());
    document.write("</br>");
}
document.write("</div>");
//keywords
document.write("<div id='keywords'>");
document.write("This is only a sample of the available keywords.</br> You'll have to find the rest on your own!</br>");
for(k in keywords){
    document.write(keywords[k].getText());
    document.write("</br>");
}
document.write("</div>");
//activities
document.write("<div id='activities'>");
for(a in activities){
    document.write(activities[a].getText());
    document.write("</br>");
}
document.write("</div>");
//display page
function hideAll() {
    document.getElementById("commands").style.display = "none";
    document.getElementById("keywords").style.display = "none";
    document.getElementById("activities").style.display = "none";
}
function displayCommands(){
    hideAll();
    document.getElementById("commands").style.display = "block";
}
function displayKeywords(){
    hideAll();
    document.getElementById("keywords").style.display = "block";
}
function displayActivities(){
    hideAll();
    document.getElementById("activities").style.display = "block";
}