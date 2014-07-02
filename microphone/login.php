<html>
 <head>
  <?php
   if(isset($_SESSION['playerID'])){
    //redirect to index
   }
   
   if(isset($_POST["uname"]) && isset($_POST("pass"))){
    //check length, chars, ect.
    //set session
   }
  ?>
 </head>
 <script>
 </script>
 <style>
 </style>
 <body>
  <form action="login.php">
  <input type=text name=uname></input>
  <input type=pass name=pass></input>
  <input type=submit></input>
  </form>
 </body>
</html>
