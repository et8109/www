
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
    return check([Word]) ||
           check([Action]);
}

function Action() {
    return eat("say") && check([Speech]);
}

function Speech() {
    return eat("word");
}

function Word() {
    return eat("word");
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

function eat(str) {
    if (words[pos] == str) {
        pos++;
        return true;
    } else{
        addError(str);
        return false;
    }
}

function addError(funcName){
    if (pos > maxpos) {
        error = "At: "+words[pos]+". Could not find: "+funcName;
        maxpos = pos;
    } /*else if (pos == maxpos) {
        error += ", or "+funcName;
    }*/
}