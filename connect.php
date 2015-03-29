<?php

//Database config
$db_host		= 'localhost';
$db_user		= 'root';
$db_pass		= '';
$db_database	= 'vocabulaire'; 

$mysqli = new mysqli($db_host,$db_user,$db_pass,$db_database);
if ($mysqli->connect_errno) {
    die("No se ha podido conectar con la base de datos. Por favor, avisa a Pablo.<br>Error: " . $mysqli->connect_error);
}
$mysqli->query("SET names ISO-8859-1");

?>
