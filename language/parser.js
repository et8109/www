
var pos=0;
var words;
//used for errors
var error="";
var maxpos=0;

function parse(string) {
    error="";
    maxpos=0;
    pos=0;
    words = string.split(" ");
    if(Paragraph()){
        return string;
    }
    return error;
}

function Paragraph(){
    return check(this.name,[Word,
                      Action]);
}

function Action() {
    return eat("say") && check(this.name,[Speech]);
}

function Speech() {
    return eat("word");
}

function Word() {
    return eat("word");
}

/////////////////////////////////////////////////////////

function check(name, funcs) {
    var p = pos;
    for (i in funcs){
        if (funcs[i]()) {
            return true;
        } else{
            pos = p;
        }
    }
    if (pos >= maxpos) {
        error = "At: "+name+". Could not find: ";
        for (i in funcs) {
            error +=" ["+funcs[i].name+"] ";
        }
        maxpos = pos;
    }
    return false;
}

function eat(str) {
    if (words[pos] == str) {
        pos++;
        return true;
    } else{
        return false;
    }
}