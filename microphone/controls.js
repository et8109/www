

var angle=Math.PI/2;
var compassConstant = (window.innerWidth/Math.PI);

var pressedW = false;
var pressedA = false;
var pressedS = false;
var pressedD = false;
//var lastMouseX=0;
//var lookSpeed = .0005;

var control_options = {
    WASD: 0,
    WASD_JL: 1,
    WASD_MOUSE: 2,
    MOUSE: 3
};
var controls = control_options.WASD;

/**
 *returns the angle at which the player is walking
 */
function getWalkAngle() {
    if (pressedW) {
        if (pressedA) {
            //a-w-
            return angle+(Math.PI/4);
        } else if (pressedD) {
            //-w-d
            return angle-(Math.PI/4);
        } else{
            //-w-
            return angle;
        }
    } else if (pressedS) {
        if (pressedA) {
            //a-s-
            return angle+Math.PI-(Math.PI/4);
        } else if (pressedD) {
            //-s-d
            return angle+Math.PI+(Math.PI/4);
        } else{
            //-s-
            return angle+Math.PI;
        }
    } else if (pressedA) {
        //a--
        return angle+(Math.PI/2);
    } else if (pressedD) {
        //--d
        return angle-(Math.PI/2);
    }
    log("walk angle not found");
    return false;
}

/**
 *changes the angle the player is facing
 */
/*function mouseMoved(e){
    var x = e.clientX - screen.width/2;
    var changeX = x - lastMouseX;
    lastMouseX = x;
    angle += changeX * lookSpeed;
    angle = angle%(Math.PI*2);
    var compass = document.getElementById("compass");
    var compassX = (screen.width/2) + (angle-Math.PI/2)*(screen.width/Math.PI)*10;
    compass.style.marginLeft = compassX + "px";
    //update audio context dir.
}*/

/**
 *tracks when the wasd keys are pressed
 */
function keyPressed(e){
    if (loading) {
        return;
    }
    //more control options, using mouse moved above^
    //movement
    //pressed w
    if(event.keyCode == 119 || event.keyCode == 87){
        pressedW = true;
    }
    //pressed w
    else if (event.keyCode == 65 || event.keyCode == 97) {
        pressedA = true;
    }
    //pressed s
    else if (event.keyCode == 83 || event.keyCode == 115) {
        pressedS = true;
    }
    //pressed d
    else if (event.keyCode == 68 || event.keyCode == 100) {
        pressedD = true;
    }
    if (controls == control_options.WASD_JL) {
        //turning
        //pressed j
        if (event.keyCode == 74 || event.keyCode == 106) {
            updateAngle(false/*to right*/);
        }
        //pressed l
        else if (event.keyCode == 76 || event.keyCode == 108) {
            updateAngle(true/*to right*/);
        }
    }
}

/**
 *updates the compass and audio diretion
 */
function updateAngle(toRight) {
    if (toRight) {
        angle -= .2;
    } else{
        angle += .2;
    }
    if (angle>Math.PI*2) {
        angle = Math.PI*2 - angle;
    } else if (angle<0) {
        angle = Math.PI*2 + angle;
    }
    document.getElementById("compass").style.marginLeft = ((window.innerWidth/2) + (angle-Math.PI/2)*compassConstant) + "px";
}

/**
 *tracks when the wasd keys are released
 */
function keyUp(e){
    if (loading) {
        return;
    }
    //pressed w
    if(event.keyCode == 119 || event.keyCode == 87){
        pressedW = false;
    }
    //pressed w
    else if (event.keyCode == 65 || event.keyCode == 97) {
        pressedA = false;
    }
    //pressed s
    else if (event.keyCode == 83 || event.keyCode == 115) {
        pressedS = false;
    }
    //pressed d
    else if (event.keyCode == 68 || event.keyCode == 100) {
        pressedD = false;
    }
}

function hideCompass(){
    document.getElementById('compass').style.visibility = "hidden";
}

function showCompass() {
    document.getElementById('compass').style.visibility = "visible";
}