<?php
require_once("shared.php");

echo "page start";

//make sure they are not logged in
if(isset($_SESSION['playerID'])){
    echo "redirecting";
    header("Location: index.php");
}

if(isset($_POST['uname'])){
    echo "found uname";
//sanitize
$uname = $_POST['uname'];
$pass = $_POST['pass'];
if($uname == null || $uname == ""){
    throw new Exception("Enter a valid username");
}
if($pass == null || $pass == ""){
    throw new Exception("Enter a valid password");
}
//get username, password
$playerRow = query("select id,peerid,posx,posy,audioURL from playerinfo where uname=".prepVar($uname)." and pass=".prepVar($pass));
if($playerRow == false){
    throw new Exception("Incorrect username or password");
}
//set session
$_SESSION['playerID'] = $playerRow['id'];
$_SESSION['lastupdateTime'] = 0;
header("Location: index.php");
}
?>
<html>
 <body>
<form action="login.php" method="post">
  Username: <input type=text name=uname maxlength=20></input>
  Password: <input type=password name=pass maxlength=20></input>
  <input type=submit></input>
  </form>
 </body>
</html>