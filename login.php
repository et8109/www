<?php
ob_start();
session_start();
if(isset($_SESSION['playerID'])){
    header("Location: index.php");
}

$username = "";
$password = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST'){
	$username = $_POST['username'];
	$password = $_POST['password'];
        $quotedUsername=smart_quote($username);
        $quotedPassword=smart_quote($password);
        
$con = mysqli_connect("Localhost","ignatymc_admin","1Gn4tym","ignatymc_game");
//check connection
if (mysqli_connect_errno()){
    //failed to connect
}else{
    $SQL = "select * from playerinfo where Name=$quotedUsername and Password=$quotedPassword";
    $result=mysqli_query($con, $SQL);
}

//check result of search
if($result){
    if(mysqli_num_rows($result) != 1){
        //this is where an error message would be
    }
    //get id of player
    $row = mysqli_fetch_array($result);
    $_SESSION['playerID'] = $row['ID'];
    $_SESSION['playerName'] = $row['Name'];
    $_SESSION['lastChatTime'] = date_timestamp_get(new DateTime());
    $_SESSION['currentScene'] = $row['Scene'];
    mysqli_free_result($result);
    header("Location: index.php");
    exit;
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
<INPUT TYPE = 'password' Name ='password'  value="<?PHP print $password;?>" maxlength=20><br/>
<INPUT TYPE = "Submit" Name = "Submit1"  VALUE = "Login">
    <a href="register.php">Need to register?</a>
        </FORM>
        <div id="info">
            <a>forums will be found soon</a></br></br>
            Updates:</br>
            -v1 :)</br>
            Welcome to the alpha!
        </div>
    </body>
</html>