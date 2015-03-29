<?php
require_once 'connect.php';
if(!isset($_POST['pass']) OR $_POST['pass'] !== 'verificacion') die('No est&aacute;s autorizado para ver esto');
$mysqli->query("DELETE FROM examenes WHERE id = ".$_POST['borrar']);
$mysqli->query("DELETE FROM preguntas WHERE examen = ".$_POST['borrar']);
die('Fine');
?>