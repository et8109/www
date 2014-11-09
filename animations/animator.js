document.write("animating");
function SpriteAnim(options) {
    var timer;
    var i=0;
    var element = document.getElementById(options.elementId);
    element.style.width=options.width+"px";
    element.style.height = options.height + "px";
    element.style.backgroundRepeat = "no-repeat";
    element.style.backgroundImage = "url(" + options.sprite + ")";
    timer = setInterval(function(){
        if (i >= options.frames) {
            i=0;
        }
        element.style.backgroundPosition = "-"+i*options.width+"px 0px";
        i++;
    },200);
    
    this.stop = function(){
        clearInterval(timer);
    };
}
alert("done func");
var cloaked = new SpriteAnim({
    width: 410,
    height: 525,
    frames: 40,
    sprite: "pacing.png",
    elementId : "anim1"
    });
alert("done loading");