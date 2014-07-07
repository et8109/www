<?php
if(isset($_SESSION['playerID'])){
  header("Location: index.php");
}

//if logging in
if(isset($_POST['uname'])){
  $uname = $_POST['uname'];
  $pass = $_POST['pass'];
  if($uname == null || $uname == ""){
    throw new Exception("Enter a valid username");
  }
  if($pass == null || $pass == ""){
    throw new Exception("Enter a valid password");
  }
  //check username, password
  $playerRow = query("select id from playerinfo where uname=".prepVar($uname)." and pass=".prepVar($pass));
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
<head>
</head>
<script>
</script>
<style>
</style>
<body>
<form action="login.php">
  Username: <input type=text name=uname maxlength=20></input>
  Password: <input type=password name=pass maxlength=20></input>
  <input type=submit></input>
  </form>
 </body>
</html>
