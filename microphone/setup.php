<?php

require("sharedPhp.php");
connectToDb();
session_start();
$infoRow = query("select posx, posy, peerid, audioURL from playerinfo where id=".prepVar($_SESSION['playerID']));
sendJSON(array(
                    "spriteaudioURL" => "Lowlife.mp3,Dead.mp3",
                    "playerID" => $_SESSION['playerID'],
                    "playeraudioURL" => $infoRow['audioURL'],
                    "peerID" => $infoRow['peerid'],
                    "posX" => $infoRow['posx'],
                    "posY" => $infoRow['posy']
                ));
?>
