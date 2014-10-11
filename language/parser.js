
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
    return ( check([Word]) ||
             check([Verb]) ) && check([EOF]);
}

function Verb() {
    return eat("say",1) && check([Speech]) ||
           eat("tell",1) && check([Speech]) ||
           eat("look",1) && eat("at",0) && check([VisibleObject]);
}

function VisibleObject() {
    return eat("the",0) &&
        ( eat("ground",1) || eat("sky",1) );
}

function Speech() {
    return eat("word",0);
}

function Word() {
    return eat("word",0);
}

function EOF() {
    return pos == words.length || addError("EOF");
}

/////////////////////////////////////////////////////////

function check(funcs) {
    var p = pos;
    for (i in funcs){
        if (!funcs[i]()) {
            addError("["+funcs[i].name+"]");
            pos = p;
            return false;
        }
    }
    return true;
}

function eat(str,type) {
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