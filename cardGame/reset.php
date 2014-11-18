<?php
require_once 'database.php';

$message="";
$reset="";

if(isset($_POST['reset'])){
    if($_POST['reset'] == "RESET"){
        try{
            $db = new Database(true);
            $message="done!";
        } catch(Exception $e){
            $message = $e->getMessage();
        }
    }
}
?>
<html>
  <body>
    <form action="reset.php" method="post">
      Type RESET: <input type=text name=reset maxlength=20><?php echo $reset ?></input>
      <input type=submit></input>
    </form>
    <?php echo $message ?>
  </body>
</html>