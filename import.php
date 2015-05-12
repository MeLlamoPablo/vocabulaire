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

//Add the imported exam to the 'examenes' table
$mysqli->query("INSERT INTO examenes (`nombre`,`preguntas`) VALUES ('".mysql_real_escape_string($json['title'])."', '".$json['numberOfQuestions']."');");
$examId = $mysqli->insert_id;

//Add the questions to the 'preguntas' table
$num = 0;
while($num < $json['numberOfQuestions']){
	$mysqli->query("INSERT INTO preguntas (`examen`,`esp`,`fra`,`modo`,`num`) VALUES (".$examId.", '".mysql_real_escape_string($json['questions'][$num]['lang1'])."', '".mysql_real_escape_string($json['questions'][$num]['lang2'])."', ".$json['questions'][$num]['mode'].", ".$num.");");
	$num++;
}

//Redirect the user to the admin page
die('<meta http-equiv="refresh" content="0; url=admin.php?msg=2" />');

?>
