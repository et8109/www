
<?php
$username = "";
$password = "";
include 'phpHelperFunctions.php';
if ($_SERVER['REQUEST_METHOD'] == 'POST'){
	$username = $_POST['username'];
	$password = $_POST['password'];
        $username=smart_quote($username);
        $password=smart_quote($password);
        
$con = mysqli_connect("Localhost","root","","game");
//check connection
if (mysqli_connect_errno()){
    //failed to connect
}else{
    $SQL = "select * from playerinfo where Name=$username and Password=$password";
    $result=mysqli_query($con, $SQL);
}
//check result of search
if($result){
    if(mysqli_num_rows($result) == 1){
    //get id of player
    $row = mysqli_fetch_array($result);
    //start a session
    session_start();
    $_SESSION['playerID'] = $row['ID'];
    $_SESSION['playerName'] = $row['Name'];
    $_SESSION['lastChatTime'] = date_timestamp_get(new DateTime());
    $_SESSION['currentScene'] = $row['Scene'];
    mysqli_free_result($result);
    header("Location: index.php");
    exit;
    }
}else{
    mysqli_free_result($result);
    //failed to log in
}
}
////////////////////////////////////////////////
////////////////////////////////////////////////
function smart_quote($value){
    if(!is_numeric($value)){
        $value="'" . $value . "'";
    }
    return $value;
}
?>

<html>
    <head>
        <link rel="stylesheet" type="text/css" href="login.css" />
    </head>
    <body>
        <FORM NAME ="form1" METHOD ="POST" ACTION ="login.php">
I am </br>
<INPUT TYPE = 'TEXT' Name ='username'  value="<?PHP print $username;?>" maxlength=20></br>
Password: </br>
<INPUT TYPE = 'TEXT' Name ='password'  value="<?PHP print $password;?>" maxlength=20><br/>
<INPUT TYPE = "Submit" Name = "Submit1"  VALUE = "Login">
    <a href="register.php">Need to register?</a>
        </FORM>
    </body>
</html>