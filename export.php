<?php

//Set the content type to JSON
header('Content-Type: application/json');

session_start();
require_once 'connect.php';

//Chech if the user has admin rights
if(!isset($_SESSION['administration']) OR !$_SESSION['administration']){
	die('You have no power here!');
}

//If the exam variable is set, single export mode. Else, export all mode.
if(isset($_GET['examen'])){
	//Single export

	//Version:
	$version = '2.2.1';

	//Get data from examenes
	$resultado = $mysqli->query("SELECT * FROM examenes WHERE id = ".$_GET['examen']);
	$examen = $resultado->fetch_assoc();

	//Get data from preguntas
	$questions = array();
	$resultado = $mysqli->query("SELECT * FROM preguntas WHERE examen = ".$_GET['examen']);
	while($preguntas = $resultado->fetch_assoc()){
		$questions[$preguntas['num']] = array(
			'lang1' => $preguntas['esp'],
			'lang2' => $preguntas['fra'],
			'mode' => $preguntas['modo']);
	}

	$export = array(
		'title' => $examen['nombre'],
		'active' => $examen['activa'],
		'generatedWithVersion' => $version,
		'numberOfQuestions' => $examen['preguntas'],
		'questions' => $questions);

	//Output the exam in json
	echo json_encode($export, JSON_FORCE_OBJECT);
}else{
	//TODO Export all
}

?>
