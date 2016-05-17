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
	$version = '2.4';

	//Get data from examenes
	$resultado = $mysqli->query("SELECT * FROM examenes WHERE id = ".$_GET['examen']);
	$examen = $resultado->fetch_assoc();

	//Get data from preguntas
	$questions = array();
	$resultado = $mysqli->query("SELECT * FROM preguntas WHERE examen = ".$_GET['examen']);
	$num = 0;
	while($preguntas = $resultado->fetch_assoc()){
		$questions[$num] = array(
			'lang1' => $preguntas['esp'],
			'lang2' => $preguntas['fra'],
			'mode' => $preguntas['modo']);
		$num++;
	}

	$export = array(
		'title' => $examen['nombre'],
		'active' => $examen['activa'],
		'generatedWithVersion' => $version,
		'questions' => $questions);

	//Output the exam in json
	echo _format_json(json_encode($export, JSON_FORCE_OBJECT));
}else{
	//TODO Export all
}

/**
 * Formats a JSON string for pretty printing
 * pretty-json.php: https://gist.github.com/GloryFish/1045396
 * 
 * @param string $json The JSON to make pretty
 * @param bool $html Insert nonbreaking spaces and <br />s for tabs and linebreaks
 * @return string The prettified output
 * @author Jay Roberts
 */
     function _format_json($json, $html = false) {
	$tabcount = 0; 
	$result = ''; 
	$inquote = false; 
	$ignorenext = false; 

	if ($html) { 
	    $tab = "&nbsp;&nbsp;&nbsp;"; 
	    $newline = "<br/>"; 
	} else { 
	    $tab = "\t"; 
	    $newline = "\n"; 
	} 

	for($i = 0; $i < strlen($json); $i++) { 
	    $char = $json[$i]; 

	    if ($ignorenext) { 
	        $result .= $char; 
	        $ignorenext = false; 
	    } else { 
	        switch($char) { 
	            case '{': 
	                $tabcount++; 
	                $result .= $char . $newline . str_repeat($tab, $tabcount); 
	                break; 
	            case '}': 
	                $tabcount--; 
	                $result = trim($result) . $newline . str_repeat($tab, $tabcount) . $char; 
	                break; 
	            case ',': 
	                $result .= $char . $newline . str_repeat($tab, $tabcount); 
	                break; 
	            case '"': 
	                $inquote = !$inquote; 
	                $result .= $char; 
	                break; 
	            case '\\': 
	                if ($inquote) $ignorenext = true; 
	                $result .= $char; 
	                break; 
	            default: 
	                $result .= $char; 
	        } 
	    } 
	} 

	return $result; 
}

?>
