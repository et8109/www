<?php
$username = "";
$password = "";
$password2 = "";
$errorMessage = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST'){
	$username = $_POST['username'];
	$password = $_POST['password'];
        $password2 = $_POST['password2'];
        $errorMessage = "";
        //check is passwords match
        if(strcmp($password, $password2)!=0){
            $errorMessage="Your passwords don't match";
        }else{
        //quote usename and password for db search
        $username=smart_quote($username);
	$password=smart_quote($password);
        }
$con = mysqli_connect("Localhost","root","","game");
//check connection
if (mysqli_connect_errno()){
    $chatFile = fopen("Chat.txt", "a");
    fwrite($chatFile,"Failed to Connect");
    fclose($chatFile);
    $errorMessage="Didn't connect to db";
}else{
    $SQL = "select * from playerinfo where Name=$username";
    $result=mysqli_query($con, $SQL);
}
//check result of search
if($result){
    if(mysqli_num_rows($result) > 0){
    $errorMessage="Someone already has that name";
    }
    else if(strcmp($errorMessage,"")==0){
        mysqli_query($con,"INSERT INTO playerinfo(Name, Password, Weapon) VALUES ($username,$password,'none')");
        $result2=mysqli_query($con, "Select * from playerinfo where Name=$username");
        $row = mysqli_fetch_array($result2);        
        session_start();
        $_SESSION['playerID']=$row['ID'];
        mysqli_free_result($result);
        mysqli_free_result($result2);
        $errorMessage="Welcome, ".$username."your id is: ".$row['ID']." <a href='index.php'>Begin!</a>";
    }
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
        <link rel="stylesheet" type="text/css" href="TextCombat.css" />
    </head>
    <body>
        <FORM NAME ="form1" METHOD ="POST" ACTION ="register.php">
Username: <INPUT TYPE = 'TEXT' Name ='username' maxlength="10" value="<?PHP print $username;?>" maxlength="20">
Password: <INPUT TYPE = 'TEXT' Name ='password' maxlength="20" value="<?PHP print $password;?>" maxlength="16">
Password: <INPUT TYPE = 'TEXT' Name ='password2' maxlength="20" value="<?PHP print $password2;?>" maxlength="16">
<P align = center>
<INPUT TYPE = "Submit" Name = "Submit1"  VALUE = "Register">
    <?php echo $errorMessage; ?>
    </body>
</html>