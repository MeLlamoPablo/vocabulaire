<?php
session_start();
require_once 'connect.php';

//Chech if the user has admin rights
if(!isset($_SESSION['administration']) OR !$_SESSION['administration']){
	die('You have no power here!');
}

//If the exam variable is set, single export mode. Else, export all mode.
if(isset($_GET['examen'])){
	//Single export

	//Get data from examenes

	$export = array('title' => , );
}else{
	//Export all
}

?>