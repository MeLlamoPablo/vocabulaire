<?php

//TODO ability to edit the name of an exam

session_start();
require_once 'connect.php';
if(isset($_POST['submit'])){
	//Generic pass for testing purposes
	if($_POST['pass'] === 'VocabAdmin' /*OR md5($_POST['pass']) === "insert a real, md5ed pass here" */){
		$_SESSION['administration'] = TRUE;
	}else{
		$error = TRUE;
	}

}

if(isset($_GET['activar']) && isset($_SESSION['administration'])){
	$mysqli->query("UPDATE examenes SET activa = 1 WHERE id = ".$_GET['activar']);
}
if(isset($_GET['desactivar']) && isset($_SESSION['administration'])){
	$mysqli->query("UPDATE examenes SET activa = 0 WHERE id = ".$_GET['desactivar']);
}

//Si se ha creado
if(isset($_POST['crear']) && isset($_SESSION['administration'])){
	$mysqli->query("INSERT INTO examenes (`nombre`,`preguntas`) VALUES ('".htmlentities(mysql_real_escape_string($_POST['titulo']))."', '".$_POST['numero']."');");
	die('<meta http-equiv="refresh" content="0; url=admin.php?editar='.$mysqli->insert_id.'" />');
}

//Si se ha editado un examen

if(isset($_POST['editar']) && isset($_SESSION['administration'])){
	$examen = $_SESSION['examen'];

	//Comprobar si el examen se está editando por primera vez o no
	$resultado = $mysqli->query("SELECT * FROM preguntas WHERE examen = ".$examen['id']." AND num = 0");
	$preguntas = $resultado->fetch_assoc();
	if(isset($preguntas['esp'])){
		$num = 0;
		while($num < $examen['preguntas']){
			$mysqli->query("UPDATE preguntas SET `examen` = ".$examen['id'].", `esp` = '".htmlentities(mysql_real_escape_string($_POST['esp'.$num]), ENT_QUOTES, "UTF-8")."', `fra` = '".htmlentities(mysql_real_escape_string($_POST['fra'.$num]), ENT_QUOTES, "UTF-8")."', `modo` = ".$_POST['modo'.$num]." WHERE num = ".$num);
			$num++;
		}
	}else{
		$num = 0;
		while($num < $examen['preguntas']){
			$mysqli->query("INSERT INTO preguntas (`examen`,`esp`,`fra`,`modo`,`num`) VALUES (".$examen['id'].", '".htmlentities(mysql_real_escape_string($_POST['esp'.$num]), ENT_QUOTES, "UTF-8")."', '".htmlentities(mysql_real_escape_string($_POST['fra'.$num]), ENT_QUOTES, "UTF-8")."', ".$_POST['modo'.$num].", ".$num.");");
			$num++;
		}
	}

	$_GET['msg'] = 1;
}

//Si se ha dado la orden de borrar una pregunta
if(isset($_GET['editar']) && isset($_GET['borrarpregunta']) && isset($_SESSION['administration'])){
	//Borrar pregunta
	$mysqli->query("DELETE FROM preguntas WHERE id = ".$_GET['borrarpregunta']);
	//Reducir en 1 el numero de preguntas
		//Ver cuantas preguntas hay ya
		$resultado = $mysqli->query("SELECT preguntas FROM examenes WHERE id = ".$_GET['editar']);
		$examen = $resultado->fetch_assoc();
		//Añadir una mas
		$preguntastotales = $examen['preguntas'] - 1;
		//Guardar la información
		$mysqli->query("UPDATE examenes SET preguntas = '".$preguntastotales."' WHERE id = ".$_GET['editar']);
	//Redireccionar a la misma pagina sin la variable &borrarpregunta en la URL
	die('<meta http-equiv="refresh" content="0; url=admin.php?editar='.$_GET['editar'].'" />');
}

unset($_SESSION['examen']);
?>

<!DOCTYPE html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<title>L&#39;app du vocabulaire</title>
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link href="css/bootstrap.min.css" rel="stylesheet">
	<link href="styles.css" rel="stylesheet">
</head>
<body> <!-- style="margin-top: 51px;" -->
	<?php $active = 2; include 'menu.php';
	if(isset($_SESSION['administration'])): ?>
		<?php if(isset($_GET['crear'])): ?>
			<div class="container jumbotron">
				<h2>Crear nuevo examen</h2>
				<p>Cuando se crea un nuevo examen, por defecto estar&aacute; inactivo, es decir, solo se podr&aacute; acceder desde la administraci&oacute;n. Cuando se haya terminado de modificar, se podr&aacute; marcar como activo para que pueda ser accesible por todo el mundo.</p>
				<form style="padding-left: 15px;" method="post" action="admin.php">
					<label for="titulo">T&iacute;tulo: </label>
					<input type="text" name="titulo" id="titulo" style="width: 300px" required><br>
					<label for="numero">N&uacute;mero de palabras preguntadas: </label>
					<input type="number" name="numero" id="numero" value="1" min="1" required>
					<input type="submit" value="Enviar" name="crear">
				</form>
			</div>
		<?php elseif(isset($_GET['editar'])): ?>
			<?php
			//Si se ha dado la orden de añadir una pregunta
			if(isset($_GET['anyadir'])){
				//Ver cuantas preguntas hay ya
				$resultado = $mysqli->query("SELECT preguntas FROM examenes WHERE id = ".$_GET['editar']);
				$examen = $resultado->fetch_assoc();
				//Añadir una mas
				$preguntastotales = $examen['preguntas'] + 1;
				//Guardar la información
				$mysqli->query("UPDATE examenes SET preguntas = '".$preguntastotales."' WHERE id = ".$_GET['editar']);

				//Insertar una fila vacía. num = $examen['preguntas'] porque la cuenta de ids empieza en 0.
				$mysqli->query("INSERT INTO preguntas (`examen`,`esp`,`fra`,`modo`,`num`) VALUES (".$_GET['editar'].", '', '', '', '".$examen['preguntas']."');");
			}

			$resultado = $mysqli->query("SELECT * FROM examenes WHERE id = ".$_GET['editar']);
			$examen = $resultado->fetch_assoc();
			$_SESSION['examen'] = $examen;
			?>
			<div class="container jumbotron">
				<h2>Editar <i><?php echo $examen['nombre'] ?></i></h2>
				<p>Desde aqu&iacute; se pueden editar las palabras que se preguntar&aacute;n en el examen.</p>
				<form style="padding-left: 15px;" method="post" action="admin.php">
					<?php
					$num = 0;
					echo '<table style="width: 75%;">';
						echo '<tr>';
							echo '<td><center>En franc&eacute;s</center></td>';
							echo '<td><center>En espa&ntilde;ol</center></td>';
							echo '<td><center>Modo</center></td>';
							echo '<td></td>';
						echo '</tr>';
						while($num < $examen['preguntas']){
							$resultado = $mysqli->query("SELECT * FROM preguntas WHERE examen = ".$_GET['editar']." AND num = ".$num);
							$preguntas = $resultado->fetch_assoc();
							echo '<tr>';
								echo '<td><center><input required type="text" name="esp'.$num.'" style="width: 95%;" value="'.$preguntas['esp'].'"></center</td>';
								echo '<td><center><input required type="text" name="fra'.$num.'" style="width: 95%;" value="'.$preguntas['fra'].'"></center</td>';
								echo '<td><center><select name="modo'.$num.'" style="width: 95%;">
									  <option value="1"'; if($preguntas['modo'] == 1){echo 'selected="selected"';} echo '>Se puede pedir en franc&eacute;s y en espa&ntilde;ol</option>
									  <option value="2"'; if($preguntas['modo'] == 2){echo 'selected="selected"';} echo '>Se pide siempre en espa&ntilde;ol</option>
									  <option value="3"'; if($preguntas['modo'] == 3){echo 'selected="selected"';} echo '>Se pide siempre en franc&eacute;s</option>
									</select></center></td>';
								echo '<td>';
									//Codigo del boton del modal
									echo '<button type="button" class="btn btn-primary btn-xs btn-danger" data-toggle="modal" data-target="#pregunta'.$preguntas['id'].'">
										  <span class="glyphicon glyphicon-trash" aria-hidden="true"></span>
										</button>';
									//Codigo del modal
									echo '<div class="modal fade" id="pregunta'.$preguntas['id'].'" tabindex="-1" role="dialog" aria-labelledby="pregunta'.$preguntas['id'].'" aria-hidden="true">
										  <div class="modal-dialog">
										    <div class="modal-content">
										      <div class="modal-header">
										        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
										        <h4 class="modal-title">&iquest;Seguro?</h4>
										      </div>
										      <div class="modal-body">
										        Se va a borrar esta pregunta de la base de datos.
										      </div>
										      <div class="modal-footer">
										        <button type="button" class="btn btn-default" data-dismiss="modal">No, volver</button>
										        <a href="admin.php?editar='.$_GET['editar'].'&borrarpregunta='.$preguntas['id'].'"><button type="button" class="btn btn-primary btn-danger">S&iacute, borrar</button></a>
										      </div>
										    </div>
										  </div>
										</div>';
								echo '</td>';
							echo '</tr>';
							$num++;
						}
					echo '</table><br>';
					echo '<center><input type="submit" value="Enviar" name="editar"></center>';
					?>
				</form>
				<p><a href="admin.php?editar=<?php echo $_GET['editar'] ?>&anyadir">Añadir una pregunta</a></p>
			</div>
		<?php else: ?>
			<script>
			function seguro(id) {
			    if (confirm('El examen se va a borrar. Esta acción no se puede deshacer')) {
					$.post("borrar.php",{ pass:'verificacion', borrar:id },function(data){
						window.location = "admin.php";
					});
				} else {
				}
			}
			</script>
			<div class="container jumbotron" id="principal">
				<h2>Administraci&oacute;n</h2>
				<?php if(isset($_GET['msg'])){
					switch($_GET['msg']){
						case '1':
							echo '<div class="alert alert-success alert-dismissible" role="alert">';
								echo '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>';
								echo '<strong>Los cambios se han guardado correctamente.</strong>';
							echo '</div>';
							break;

						case '2':
							echo '<div class="alert alert-success alert-dismissible" role="alert">';
								echo '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>';
								echo '<strong>Se ha importado un examen correctamente.</strong>';
							echo '</div>';
							break;
					}
				} ?>
				<p>Desede aqu&iacute; se pueden crear ex&aacute;menes o modificar los existentes. Los ex&aacute;menes activos son accesibles por todo el mundo, mientras que los inactivos solo son accesibles desde la administraci&oacute;n.</p>
				<ul>
					<?php
					$resultado = $mysqli->query("SELECT * FROM examenes ORDER BY activa DESC");
					while($fila = $resultado->fetch_assoc()){
						echo '<li>'.$fila['nombre'];
						if($fila['activa'] == 1){
							echo ' <i>(activo)</i> - <a href="admin.php?desactivar='.$fila['id'].'">Desactivar</a>';
						}else{
							echo ' - <a href="admin.php?activar='.$fila['id'].'">Activar</a>';
						}
						echo ' - <a href="admin.php?editar='.$fila['id'].'">Editar</a>';
						echo ' - <a href="export.php?examen='.$fila['id'].'" download="'.$fila['nombre'].'.json">Exportar</a>';
						echo ' - <a href="#" onclick="javascript:seguro('.$fila['id'].')">Borrar</a>';
						echo '</li>';
					}
					?>
				</ul>
				<p><a href="admin.php?crear">Crear un nuevo examen</a></p>
				<form action="import.php" method="post" enctype="multipart/form-data">
					<label for="exampleInputFile">Importar un archivo</label>
					<div>Los archivos que se pueden importar son los generados al hacer click en el bot&oacute;n "Exportar". Estos archivos terminan en <i>.json</i><div>
					<input type="file" id="jsonFile" name="jsonFile"><br>
					<input type="submit" value="Importar" name="submit">
				</form>
			</div>
		<?php endif; ?> 
	<?php else: ?>
		<div class="section">
			<center>
			<h2>Introduce la contrase&ntilde;a</h2>
				<?php if(isset($error)) echo '<p>Contase&ntilde;a incorrecta</p>'; ?>
				<form method="post" action="admin.php">
					<input type="password" name="pass" id="pass">
					<input type="submit" name="submit" id="submit" value="Enviar">
				</form>
			</center>
		</div>
	<?php endif; ?>
</body>
