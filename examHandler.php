<?php

require_once 'connect.php';

//Check if inputs are passed
if(!isset($_POST['token']))  error('No token was provided.');
if(!isset($_POST['userid'])) error('No user id was provided.');
if(!isset($_POST['title']))  error('No exam title was provided.');

//Sanitize inputs
$provided_token = $mysqli->real_escape_string($_POST['token']);
$title = htmlentities($mysqli->real_escape_string($_POST['title']));

if(!is_numeric($_POST['userid']))
	error('Provided user id is not numeric.');
if(!isset($_POST['creating']) OR ($_POST['creating'] != 'true' && $_POST['creating'] != 'false'))
	error('Couldn\'t determine whether to create a new exam or edit an existing one.');

//Validate the session token
$r = $mysqli->query("SELECT token, token_time FROM usuarios WHERE id = ".$_POST['userid'])->fetch_assoc();
if($provided_token === $r['token'] && time() - $r['token_time'] < 60*60){
	//The token is valid. Do the thing.

	$query = '';

	if(filter_var($_POST['creating'], FILTER_VALIDATE_BOOLEAN)){ //Because PHP evaluates string "false" to boolean true.
		$mysqli->query("INSERT INTO examenes (`nombre`) VALUES ('".$title."');");
		$id = $mysqli->insert_id;
	}else{
		//Check and sanitize inputs
		if(!isset($_POST['examid'])) error('No exam id was provided. Set "creating" to TRUE in order to create a new exam.');
		if(!is_numeric($_POST['examid'])) error('Provided exam id is not numeric.');
		$id = $_POST['examid'];

		if($r = $mysqli->query("SELECT nombre FROM examenes WHERE id = ".$id)){
			if($r->fetch_assoc()['nombre'] !== $title) $query .= "UPDATE examenes SET nombre = '".$title."' WHERE id = ".$id.";";
		}else{
			error('Provided exam id is invalid'); //TODO TEST
		}

		//Delete everything we previously had from this exam, then save everything again.
		//Could this be optimized? Yeah. Do I want to optimize it? Nope.
		$query .= "DELETE FROM preguntas WHERE examen = ".$id.";";
	}

	foreach ($_POST['questions'] as $k => $v) {
		//Check if inputs are passed
		if(!isset($v['fra']))  error('No "fra" value was provided on question #'.$k.'.');
		if(!isset($v['esp']))  error('No "esp" value was provided on question #'.$k.'.');
		if(!isset($v['mode'])) error('No mode was provided on question #'.$k.'.');

		//Sanitize inputs
		$fra = htmlentities($mysqli->real_escape_string($v['fra']), ENT_QUOTES, "UTF-8");
		$esp = htmlentities($mysqli->real_escape_string($v['esp']), ENT_QUOTES, "UTF-8");
		if(!is_numeric($v['mode'])) error('Provided mode for question #'.$k.' is not numeric.');

		$query .= "INSERT INTO `preguntas` (`examen`,`esp`,`fra`,`modo`) VALUES (".$id.", '".$esp."', '".$fra."', ".$v['mode'].");";
	}
	

	if($mysqli->multi_query($query)){
		die(json_encode(array('success' => TRUE, 'examid' => $id)));
	}else{
		error('There was an SQL error when trying to write the information into the database: '.$mysqli->error);
	}
}else{
	//The token is not valid
	if($provided_token !== $r['token']){
		error('Provided token is not valid.');
	}else{
		error('Provided token has expired.');
	}
}

function error($msg = 'No error was specified.'){
	die(json_encode(array('success' => FALSE, 'error' => $msg)));
}

?>