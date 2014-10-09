//By Elliot Essman
var sum=0;
for(i=0; i<1000; i++){
    if (multiple(i)) {
        sum+=i;
    }
}
document.write(sum);

function multiple(num){
    return (num%5 == 0 || num%3 == 0);
}