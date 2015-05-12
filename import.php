<?php

session_start();
require_once 'connect.php';

//Chech if the user has admin rights
if(!isset($_SESSION['administration']) OR !$_SESSION['administration']){
	die('You have no power here!');
}

//Get the file contents and decode them
$json = json_decode(file_get_contents($_FILES['jsonFile']['tmp_name']), true, 4);

//Chech if it's a valid file
if(!isset($json['generatedWithVersion'])){
	die('Error: el archivo subido no posee un formato correcto.');
}
?>