<?php
require_once("shared.php");

$arrayJSON = array();
$infoRow = query("select posx, posy, peerid, audioURL from playerinfo where id=".prepVar($_SESSION['playerID']));
$arrayJSON[] = (array(
                    "spriteaudioURL" => "Lowlife.mp3,Dead.mp3",
                    "playerID" => $_SESSION['playerID'],
                    "playeraudioURL" => $infoRow['audioURL'],
                    "peerID" => $infoRow['peerid'],
                    "posX" => $infoRow['posx'],
                    "posY" => $infoRow['posy']
                ));
sendJSON($arrayJSON);
?>