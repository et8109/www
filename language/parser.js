
var pos=0;
var words;
//used for errors
var error="";
var maxpos=0;

function parse(string) {
    error="";
    maxpos=-1;
    pos=0;
    words = string.split(" ");
    if(Paragraph()){
        return string;
    }
    return error;
}

function Paragraph(){
    return check([Scentence,Paragraph]) ||
           check([Scentence, EOF]);
}

function Scentence() {
    return check([Verb]) ||
           check([Trigger]);
}

function Trigger() {
    return check(["when",TriggerTime]);
}

function TriggerTime() {
    return check(["time","is",TIME]);
}

function TIME(){
    var time = words[pos];
    return time.length == 5 &&
            isNumber(time.charAt(0)) &&
            isNumber(time.charAt(1)) &&
            time.charAt(2) == ':' &&
            isNumber(time.charAt(3)) &&
            isNumber(time.charAt(4)) && pos++;
}

/*function Noun() {
    return check(["the",Noun1]) ||
           check([Noun1]);
}
function Noun1(){
    return check([VisableObject]) ||
           check(["time"]);
}*/

function Verb() {
    return check(["say",Speech]) ||
           check(["tell", Person, Speech]) ||
           check(["look","at",VisibleObject]);
}

function VisibleObject() {
    return check(["the"]) &&
        ( check(["ground"]) || check(["sky"]) );
}

function Speech() {
    return check(["word"]);
}

function Person() {
    return check([Npc]) ||
           check([PlayerName]);
}

function Npc() {
    return check(["npc"]);
}

function PlayerName(){
    return false;
}

function EOF() {
    return pos == words.length || addError("EOF");
}

/////////////////////////////////////////////////////////

function isNumber(n) {
  return !isNaN(parseFloat(n)) && isFinite(n);
}

function check(funcs) {
    var p = pos;
    for (i in funcs){
        var type = typeof funcs[i];
        var name = funcs[i].name;
        if (type == "string"){
            if(!eat(funcs[i])){
                pos = p;
                return false;
            }
        } else if(type == "function"){
            if(!funcs[i]()) {
                addError("["+name+"]");
                pos = p;
                return false;
            }
        }
    }
    return true;
}

function eat(str) {
    if (words[pos] == str) {
        pos++;
        return true;
    } else{
        //addError(str);
        return false;
    }
}

/*function append(str,type) {
    out[pos]="<span class=t"+type+" >"+str+"</span>";
}*/

function addError(funcName){
    if (pos > maxpos) {
        error = "Parsing: "+words[pos]+". Does not match: "+funcName;
        maxpos = pos;
    }
    return false;
}