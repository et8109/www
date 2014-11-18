<?php
require_once 'database.php';
session_start();

$message="";
$uname="";
$pass1="";
$pass2="";

//when a post is recieved
if(isset($_POST['uname'])){
  $uname=$_POST['uname'];
  $pass1=$_POST['pass1'];
  $pass2=$_POST['pass2'];
  if($pass1 != $pass2){
    $message = "Your passwords don't match";
    return;
  }
  try{
    $db = new Database();
    $db->insertUser($uname, $pass1);
    header("Location: login.php");
  } catch(Exception $e){
    $message = $e->getMessage();
  }
}
?>
<html>
  <body>
    <form action="register.php" method="post">
      Username: <input type=text name=uname maxlength=20 value="<?php echo $uname ?>"></input></br>
      Password: <input type=password name=pass1 maxlength=20 value="<?php echo $pass1 ?>"></input></br>
      Password again: <input type=password name=pass2 maxlength=20 value="<?php echo $pass2 ?>"></input>
      <input type=submit></input>
    </form>
Register an account</br><?php echo $message ?>
 </body>
</html>
