<?php
//TODO ability to edit the name of an exam
session_start();
require_once 'connect.php';

//Si se ha iniciado sesión
if(isset($_POST['login'])){
	//Saneamos el input de usuario
	$username = $mysqli->real_escape_string($_POST['username']);

	$password_hash = $mysqli->query("SELECT pass FROM usuarios WHERE usuario = '".$username."';")->fetch_assoc()['pass'];
	if(password_verify($_POST['password'], $password_hash)){
		$_SESSION['administration'] = TRUE;

		//password_hash() con el parámetro PASSWROD_DEFAULT está sujeto a cambios conforme nuevas versiones de PHP
		//son lanzadas. password_needs_rehash() puede determinar si existe un mejor algoritmo de hashing. Para
		//emplearlo necesitamos la contraseña en texto plano, por lo que esta comprobación solo se puede usar en
		//el momento del login.
		if(password_needs_rehash($password, PASSWORD_DEFAULT)){
			$password_newHash = password_hash($_POST['password'], PASSWORD_DEFAULT);
			$mysqli->query("UPDATE usuarios SET `pass` = '".$password_newHash."' WHERE `usuario` = '".$username."'");
		}
	}else{
		$error = TRUE;
	}
}

if(isset($_GET['activar']) && isset($_SESSION['administration']) AND $_SESSION['administration'] === TRUE){
	$mysqli->query("UPDATE examenes SET activa = 1 WHERE id = ".$_GET['activar']);
}
if(isset($_GET['desactivar']) && isset($_SESSION['administration']) AND $_SESSION['administration'] === TRUE){
	$mysqli->query("UPDATE examenes SET activa = 0 WHERE id = ".$_GET['desactivar']);
}

//Si se ha creado un usuario
if(isset($_SESSION['administration']) && isset($_POST['crear_usuario'])){
	//Comprobamos que las contraseñas coinciden
	if($_POST['password'] !== $_POST['password_confirm']) die('Las contraseñas no coinciden.<meta http-equiv="refresh" content="3; url=admin.php" />');

	//Saneamos el input de usuario. El de la contraseña no hace falta porque va a ser hasheada.
	$username = $mysqli->real_escape_string($_POST['username']);

	$password = password_hash($_POST['password'], PASSWORD_DEFAULT);
	$mysqli->query("INSERT INTO usuarios (`usuario`, `pass`) VALUES ('".$username."', '".$password."');");
}

//Si se ha borrado un usuario
if(isset($_SESSION['administration']) && $_SESSION['administration'] === TRUE && isset($_GET['borrarusr']) && is_numeric($_GET['borrarusr'])){
	//Solo borrar el usuario si existe más de un usuario registrado
	if($mysqli->query("SELECT COUNT(*) FROM usuarios;")->fetch_assoc()['COUNT(*)'] == 1) die('Solo queda un usuario registrado; no se puede borrar.<meta http-equiv="refresh" content="3; url=admin.php" />');
	$mysqli->query("DELETE FROM usuarios WHERE id = ".$_GET['borrarusr']);

	//Redireccionar a la misma pagina sin la variable &confirmar_borrar_usuario en la URL
	die('<meta http-equiv="refresh" content="0; url=admin.php" />');
}

//Si se ha creado un examen
if(isset($_POST['crear']) && isset($_SESSION['administration']) AND $_SESSION['administration'] === TRUE){
	$mysqli->query("INSERT INTO examenes (`nombre`) VALUES ('".htmlentities(mysqli_real_escape_string($mysqli, $_POST['titulo']))."');");
	die('<meta http-equiv="refresh" content="0; url=admin.php?editar='.$mysqli->insert_id.'&numOfQuestions='.$_POST['numero'].'" />');
}

//Si se ha editado un examen
if(isset($_POST['editar']) && isset($_SESSION['administration']) AND $_SESSION['administration'] === TRUE){
	$examen = $_SESSION['examen'];

	//Comprobar si el examen se está editando por primera vez o no
	$resultado = $mysqli->query("SELECT id FROM preguntas WHERE examen = ".$examen['id']." LIMIT 1");
	$preguntas = $resultado->fetch_assoc();
	if(isset($preguntas['id'])){
		$num = 0;
		while($num < $_SESSION['num_rows']){
			$mysqli->query("UPDATE preguntas SET `examen` = ".$examen['id'].", `esp` = '".htmlentities(mysqli_real_escape_string($mysqli, $_POST['esp'.$num]), ENT_QUOTES, "UTF-8")."', `fra` = '".htmlentities(mysqli_real_escape_string($mysqli, $_POST['fra'.$num]), ENT_QUOTES, "UTF-8")."', `modo` = ".$_POST['modo'.$num]." WHERE id = ".$_SESSION['rowId'][$num]);
			$num++;
		}
	}else{
		$num = 0;
		while($num < $_SESSION['numOfQuestions']){
			$mysqli->query("INSERT INTO preguntas (`examen`,`esp`,`fra`,`modo`) VALUES (".$examen['id'].", '".htmlentities(mysqli_real_escape_string($mysqli, $_POST['esp'.$num]), ENT_QUOTES, "UTF-8")."', '".htmlentities(mysqli_real_escape_string($mysqli, $_POST['fra'.$num]), ENT_QUOTES, "UTF-8")."', ".$_POST['modo'.$num].");");
			$num++;
		}
	}

	$_GET['msg'] = 1;
}

//Si se ha dado la orden de borrar una pregunta
if(isset($_GET['editar']) && isset($_GET['borrarpregunta']) && isset($_SESSION['administration']) AND $_SESSION['administration'] === TRUE){
	//Borrar pregunta
	$mysqli->query("DELETE FROM preguntas WHERE id = ".$_GET['borrarpregunta']);

	//Redireccionar a la misma pagina sin la variable &borrarpregunta en la URL
	die('<meta http-equiv="refresh" content="0; url=admin.php?editar='.$_GET['editar'].'" />');
}

//Si se ha dado la orden de borrar un examen
if(isset($_SESSION['administration']) && $_SESSION['administration'] === TRUE && isset($_GET['borrarex']) && is_numeric($_GET['borrarex'])){
	//Borrar examen
	$mysqli->query("DELETE FROM examenes WHERE id = ".$_GET['borrarex']);
	$mysqli->query("DELETE FROM preguntas WHERE examen = ".$_GET['borrarex']);

	//Redireccionar a la misma pagina sin la variable &borrarex en la URL
	die('<meta http-equiv="refresh" content="0; url=admin.php" />');
}

unset($_SESSION['examen']);
unset($_SESSION['numOfQuestions']);
unset($_SESSION['num_rows']);
unset($_SESSION['rowId']);
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
	if(isset($_SESSION['administration']) AND $_SESSION['administration'] === TRUE): ?>
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
				//Insertar una fila vacía
				$mysqli->query("INSERT INTO preguntas (`examen`,`esp`,`fra`,`modo`) VALUES (".$_GET['editar'].", '', '');");
			}

			$resultado = $mysqli->query("SELECT * FROM examenes WHERE id = ".$_GET['editar']);
			$examen = $resultado->fetch_assoc();
			$_SESSION['examen'] = $examen;
			?>
			<script type="text/javascript">
				function confirmar_volver_atras() {
				    if (confirm('Si vuelves atrás, se perderán todos los cambios no guardados. Usa el botón "Enviar" para guardar los cambios.'))
						window.location = "admin.php";
				}
			</script>
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

						//If the exam is just created, add empty rows
						if(isset($_GET['numOfQuestions'])){
							while($num < $_GET['numOfQuestions']){
								echo '<tr>';
									echo '<td><center><input required type="text" name="esp'.$num.'" style="width: 95%;"></center</td>';
									echo '<td><center><input required type="text" name="fra'.$num.'" style="width: 95%;"></center</td>';
									echo '<td><center><select name="modo'.$num.'" style="width: 95%;">
										  <option value="1" selected="selected">Se puede pedir en franc&eacute;s y en espa&ntilde;ol</option>
										  <option value="2">Se pide siempre en espa&ntilde;ol</option>
										  <option value="3">Se pide siempre en franc&eacute;s</option>
										</select></center></td>';
									echo '<td>';
									echo '</td>';
								echo '</tr>';
								$num++;
							}

							//Add numOfQuestions into the session because we need to tell the handler how many rows we want to add
							$_SESSION['numOfQuestions'] = $_GET['numOfQuestions'];
						}

						//Get all the exam rows
						$resultado = $mysqli->query("SELECT * FROM preguntas WHERE examen = ".$_GET['editar']);
						$preguntas = $resultado->fetch_all(MYSQLI_ASSOC);

						//Add num_rows into the session because we need to tell the handler how many rows we want to add
						if(isset($resultado->num_rows)) $_SESSION['num_rows'] = $resultado->num_rows;

						//Add real rows
						while($num < $resultado->num_rows){
							echo '<tr>';
								echo '<td><center><input required type="text" name="esp'.$num.'" style="width: 95%;" value="'.$preguntas[$num]['esp'].'"></center</td>';
								echo '<td><center><input required type="text" name="fra'.$num.'" style="width: 95%;" value="'.$preguntas[$num]['fra'].'"></center</td>';
								echo '<td><center><select name="modo'.$num.'" style="width: 95%;">
									  <option value="1"'; if($preguntas[$num]['modo'] == 1){echo 'selected="selected"';} echo '>Se puede pedir en franc&eacute;s y en espa&ntilde;ol</option>
									  <option value="2"'; if($preguntas[$num]['modo'] == 2){echo 'selected="selected"';} echo '>Se pide siempre en espa&ntilde;ol</option>
									  <option value="3"'; if($preguntas[$num]['modo'] == 3){echo 'selected="selected"';} echo '>Se pide siempre en franc&eacute;s</option>
									</select></center></td>';
								echo '<td>';
									//Codigo del boton del modal
									echo '<button type="button" class="btn btn-primary btn-xs btn-danger" data-toggle="modal" data-target="#pregunta'.$preguntas[$num]['id'].'">
										  <span class="glyphicon glyphicon-trash" aria-hidden="true"></span>
										</button>';
									//Codigo del modal
									echo '<div class="modal fade" id="pregunta'.$preguntas[$num]['id'].'" tabindex="-1" role="dialog" aria-labelledby="pregunta'.$preguntas[$num]['id'].'" aria-hidden="true">
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
										        <a href="admin.php?editar='.$_GET['editar'].'&borrarpregunta='.$preguntas[$num]['id'].'"><button type="button" class="btn btn-primary btn-danger">S&iacute, borrar</button></a>
										      </div>
										    </div>
										  </div>
										</div>';
								echo '</td>';
							echo '</tr>';

							//Pass the row id on the database to the hanlder, so it knows wich row to update
							$_SESSION['rowId'][$num] = $preguntas[$num]['id'];

							$num++;
						}

					echo '</table><br>';
					echo '<center><input type="submit" value="Enviar" name="editar"></center>';
					?>
				</form>
				<p><a href="admin.php?editar=<?php echo $_GET['editar'] ?>&anyadir">Añadir una pregunta</a> | <a href="#" onclick="javascript:confirmar_volver_atras()">Volver atr&aacute;s</a></p>
			</div>
		<?php else: ?>
			<script>
			function confirmar_borrar_examen(id) {
			    if (confirm('El examen se va a borrar. Esta acción no se puede deshacer'))
					window.location = "admin.php?borrarex=" + id;
			}
			function confirmar_borrar_usuario(id) {
			    if (confirm('El usuario se va a borrar. Esta acción no se puede deshacer'))
					window.location = "admin.php?borrarusr=" + id;
			}
			</script>
			<div class="container" id="principal">
				<h2 style="margin-top: 55px;">Administraci&oacute;n</h2>
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
				<div class="panel panel-default">
					<div class="panel-heading">
						<h3 class="panel-title">
							<span class="glyphicon glyphicon-file" aria-hidden="true"></span>
							Gestor de ex&aacute;menes
						</h3>
					</div>
					<div class="panel-body">
						<p>Desede aqu&iacute; se pueden crear ex&aacute;menes o modificar los existentes. Los ex&aacute;menes activos son accesibles por todo el mundo, mientras que los inactivos solo son accesibles desde la administraci&oacute;n.</p>
		
						<table class="table table-hover">
							<thead>
								<tr>
									<td><b>#</b></td>
									<td><b>Nombre</b></td>
									<td><b>Opciones</b></td>
								</tr>
							</thead>
							<tbody>
								<?php
								$r = $mysqli->query("SELECT * FROM examenes ORDER BY activa DESC");
								while($f = $r->fetch_assoc()){
									echo '<tr>';
										echo '<td>'.$f['id'].'</td>';
										echo '<td>'.$f['nombre'].(($f['activa'] == 1) ? ' <i>(activo)</i>' : '').'</td>';
										echo '<td>';
											echo ($f['activa'] == 1) ? '<a href="admin.php?desactivar='.$f['id'].'">Desactivar</a>' : '<a href="admin.php?activar='.$f['id'].'">Activar</a>';
											echo ' - <a href="admin.php?editar='.$f['id'].'">Editar</a>';
											echo ' - <a href="export.php?examen='.$f['id'].'" download="'.$f['nombre'].'.json">Exportar</a>';
											echo ' - <a href="#" onclick="javascript:confirmar_borrar_examen('.$f['id'].')">Borrar</a>';
										echo '</td>';
									echo '</tr>';
								}
								?>
								<?php
								$r = $mysqli->query("SELECT * FROM usuarios");
								while($f = $r->fetch_assoc()){
									
								}
								?>
							</tbody>
						</table>
						<p><a href="admin.php?crear">Crear un nuevo examen</a></p>
					</div>
				</div>
				<div class="panel panel-default">
					<div class="panel-heading">
						<h3 class="panel-title">
							<span class="glyphicon glyphicon-upload" aria-hidden="true"></span>
							Importador de ex&aacute;menes
						</h3>
					</div>
					<div class="panel-body">
						<form action="import.php" method="post" enctype="multipart/form-data">
							<p>Los archivos que se pueden importar son los generados al hacer click en el bot&oacute;n "Exportar". Estos archivos terminan en <i>.json</i></p>
							<input type="file" id="jsonFile" name="jsonFile"><br>
							<input type="submit" value="Importar" name="submit">
						</form>
					</div>
				</div>
				<div class="panel panel-default">
					<div class="panel-heading">
						<h3 class="panel-title">
							<span class="glyphicon glyphicon-user" aria-hidden="true"></span>
							Gestor de usuarios
						</h3>
					</div>
					<div class="panel-body">
						<p>Desde aqu&iacute; se pueden crear y eliminar usuarios con acceso de administrador.</p>

						<table class="table table-hover">
							<thead>
								<tr>
									<td><b>#</b></td>
									<td><b>Nombre de usuario</b></td>
									<td><b>Opciones</b></td>
								</tr>
							</thead>
							<tbody>
								<?php
								$r = $mysqli->query("SELECT * FROM usuarios");
								while($f = $r->fetch_assoc()){
									echo '<tr>';
										echo '<td>'.$f['id'].'</td>';
										echo '<td>'.$f['usuario'].'</td>';
										echo '<td><a href="#" onclick="javascript:confirmar_borrar_usuario('.$f['id'].')">Borrar usuario</a></td>';
									echo '</tr>';
								}
								?>
							</tbody>
						</table>

						<form action="admin.php" method="POST" class="well form-inline">
							<h4>A&ntilde;adir nuevo usuario</h4>
							<p>Los usuarios que se a&ntilde;adan tendr&aacute;n permisos para crear y borrar otros usuarios, y crear, borrar, importar y exportar ex&aacute;menes.</p>
							<div class="form-group">
								<label for="username" class="sr-only">Nombre de usuario</label>
								<input placeholder="Nombre de usuario" id="username" name="username" type="text" class="form-control"></input> 
							</div>
							<div class="form-group">
								<label for="password" class="sr-only">Contrase&ntilde;a</label>
								<input placeholder="Contrase&ntilde;a" id="password" name="password" type="password" class="form-control"></input>
							</div>
							<div class="form-group">
								<label for="password_confirm" class="sr-only">Confirmar contrase&ntilde;a</label>
								<input placeholder="Confirmar contrase&ntilde;a" id="password_confirm" name="password_confirm" type="password" class="form-control"></input>
							</div>
							<button type="submit" name="crear_usuario" class="btn btn-default">Enviar</button>
						</form>
					</div>
				</div>
			</div>
		<?php endif; ?> 
	<?php else: ?>
		<div class="section">
			<center>
			<h2 style="margin-top: 55px;">Acceso al panel de administraci&oacute;n</h2>
				<?php if(isset($error)) echo '<p>Nomre de usuario o contase&ntilde;a incorrectos</p>'; ?>
				<form action="admin.php" method="POST" class="well form-inline">
					<div class="form-group">
						<label for="username" class="sr-only">Nombre de usuario</label>
						<input placeholder="Nombre de usuario" id="username" name="username" type="text" class="form-control"></input> 
					</div>
					<div class="form-group">
						<label for="password" class="sr-only">Contrase&ntilde;a</label>
						<input placeholder="Contrase&ntilde;a" id="password" name="password" type="password" class="form-control"></input>
					</div>
					<button type="submit" name="login" class="btn btn-default">Enviar</button>
				</form>
			</center>
		</div>
	<?php endif; ?>
</body>
