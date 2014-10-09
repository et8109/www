//By Elliot Essman
var i,j=1
var num=2;
var sum=0;
while (num<4000000) {
    if (num%2==0) {
        sum+=num;
    }
    i=j;
    j=num;
    num=i+j;
}
document.write(sum);