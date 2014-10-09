//By Elliot Essman
var num = 600851475143;
var primes=maxPrimeSieve(Math.floor((num/2)));
var i=primes.length;

while (num%primes[i] != 0 && i>0) {
    i--;
}
document.write(primes[i]);


function maxPrimeSieve(max){
    var result = [];
    var primes = new Array(max);
    alert("yo");
    for (i in primes){
        primes[i] = true;
    }
    var i=2;
    while (i<= max){
        if (primes[i]) {
            result[result.length]=i;
            var j=i*2;
            while (j < max) {
                primes[j] = false;
                j+=i;
            }
        }
    }
    return result;
}