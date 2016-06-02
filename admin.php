<?php
require_once 'connect.php';

session_start();

if(isset($_SESSION['administration']) && $_SESSION['administration'] === TRUE && isset($_SESSION['last_activity'])
	&& (time() - $_SESSION['last_activity'] > $GLOBAL_CONFIG['max_session_length'])){
    session_unset();
    session_destroy();
    session_start();

    $_GET['msg'] = 3; //La sesión ha caducado.
}
$_SESSION['last_activity'] = time();

//If the user is logged in, generate a new valid session token
if(isset($_SESSION['administration']) && $_SESSION['administration'] === TRUE && isset($_SESSION['userid']) && isset($_SESSION['username'])){
	$_SESSION['token'] = md5(time() . $_SESSION['username']);
	$mysqli->query("UPDATE usuarios SET token = '".$_SESSION['token']."', token_time = '".time()."' WHERE id = ".$_SESSION['userid']);
}

//Si se ha iniciado sesión
if(isset($_POST['login'])){
	//Saneamos el input de usuario
	$username = $mysqli->real_escape_string(htmlentities($_POST['username']));

	//Comprobamos que el captcha se ha pasado
	if(isset($_SESSION['rC']) AND $_SESSION['rC']->verify()){
		$r = $mysqli->query("SELECT id, pass FROM usuarios WHERE usuario = '".$username."';")->fetch_assoc();
		$password_hash = $r['pass'];
		if(password_verify($_POST['password'], $password_hash)){
			$_SESSION['administration'] = TRUE;
			$_SESSION['username'] = $username;
			$_SESSION['userid'] = $r['id'];

			//password_hash() con el parámetro PASSWROD_DEFAULT está sujeto a cambios conforme nuevas versiones de PHP
			//son lanzadas. password_needs_rehash() puede determinar si existe un mejor algoritmo de hashing. Para
			//emplearlo necesitamos la contraseña en texto plano, por lo que esta comprobación solo se puede usar en
			//el momento del login.
			if(password_needs_rehash($password_hash, PASSWORD_DEFAULT)){
				$password_newHash = password_hash($_POST['password'], PASSWORD_DEFAULT);
				$mysqli->query("UPDATE usuarios SET `pass` = '".$password_newHash."' WHERE `usuario` = '".$username."'");
			}

			//Almacenar la sesión
			$_SESSION['token'] = md5(time() . $username);
			$mysqli->query("UPDATE usuarios SET token = '".$_SESSION['token']."', token_time = '".time()."' WHERE id = ".$r['id']);
		}else{
			$_GET['msg'] = 4; //Usuario o contraseña incorrectos

			//Store the failed login attempt
			$mysqli->query("INSERT INTO login_attempts (`ip`, `provided_user`, `time`) VALUES ('".$_SERVER['REMOTE_ADDR']."', '".$username."', '".time()."');");
		}
	}else{
		$_GET['msg'] = 5; //No se ha superado el captcha
	}
}

//Si se ha cerrado sesión
if(isset($_GET['logout'])){
	$_SESSION['administration'] = FALSE;
	unset($_SESSION['username']);

	//Redireccionar a la misma pagina sin la variable &logout en la URL
	die('<meta http-equiv="refresh" content="0; url=admin.php" />');
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

//Si se ha dado la orden de borrar un examen
if(isset($_SESSION['administration']) && $_SESSION['administration'] === TRUE && isset($_GET['borrarex']) && is_numeric($_GET['borrarex'])){
	//Borrar examen
	$mysqli->query("DELETE FROM examenes WHERE id = ".$_GET['borrarex']);
	$mysqli->query("DELETE FROM preguntas WHERE examen = ".$_GET['borrarex']);

	//Redireccionar a la misma pagina sin la variable &borrarex en la URL
	die('<meta http-equiv="refresh" content="0; url=admin.php" />');
}

function doWeNeedToShowACaptcha($mysqli, $config, $user, $ip = null){
	if($user !== '') $user = $mysqli->real_escape_string(htmlentities($user));
	if(is_null($ip)) $ip = $_SERVER['REMOTE_ADDR'];

	$r = $mysqli->query("SELECT * FROM login_attempts WHERE (provided_user = '".$user
		."' OR ip = '".$ip."') AND time > ".(time() - $config['failed_login_attempt_timeout']));

	return $r->num_rows >= $config['max_login_attempts'];
}
?>

<!DOCTYPE html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<title>L&#39;app du vocabulaire</title>
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link href="css/bootstrap.min.css" rel="stylesheet">
	<link href="css/style.css" rel="stylesheet">
</head>
<body> <!-- style="margin-top: 51px;" -->
	<?php $active = 2; include 'menu.php';
	if(isset($_SESSION['administration']) AND $_SESSION['administration'] === TRUE): ?>
		<?php if(isset($_GET['editar'])): ?>
			<?php
			//Si se ha dado la orden de añadir una pregunta
			if(isset($_GET['anyadir'])){
				//Insertar una fila vacía
				$mysqli->query("INSERT INTO preguntas (`examen`,`esp`,`fra`,`modo`) VALUES (".$_GET['editar'].", '', '');");
			}

			//If _GET['editar'] is an empty string, we need to create the exam instead of just editing it.
			if($_GET['editar'] === ''){
				$creating = TRUE;
			}else{
				$creating = FALSE;

				//Sanitize input first
				if(!is_numeric($_GET['editar'])) die('El examen que se desea editar no es válido<meta http-equiv="refresh" content="3; url=admin.php" />');

				$data = array();
				if($examen = $mysqli->query("SELECT * FROM examenes WHERE id = ".$_GET['editar'])->fetch_assoc()){
					$preguntas = $mysqli->query("SELECT esp, fra, modo FROM preguntas WHERE examen = ".$examen['id'])->fetch_all(MYSQLI_ASSOC);
					$data['id'] = $examen['id'];
					$data['name'] = $examen['nombre'];
					$data['questions'] = $preguntas;
				}else{
					die('El examen que se desea editar no es válido<meta http-equiv="refresh" content="3; url=admin.php" />');
				}
			}
			?>
			<script type="text/javascript">
				<?php echo ($creating) ? '' : 'var exam = JSON.parse(\''.json_encode($data).'\');' ?>
				var creating = <?php echo ($creating) ? 'true' : 'false'; ?>;
				var userid = <?php echo $_SESSION['userid'] ?>;
				var session_token = '<?php echo $_SESSION['token'] ?>';
			</script>
			<script src="examEditor.js"></script>
			<div class="container" style="margin-top: 65px;">
				<div class="panel panel-default">
					<div class="panel-heading">
						<h3 class="panel-title" id="titulo_examen">
							<?php echo $creating ? 'Creando un nuevo examen' : 'Editando <b>'.$examen['nombre'].'</b>'; ?>
						</h3>
					</div>
					<div class="panel-body">
						<p>Desde aqu&iacute; se pueden editar las palabras que se preguntar&aacute;n en el examen.</p>
						<form id="main_form">
							<div class="input-group" style="width: 50%; margin-bottom: 10px;">
								<div class="input-group-addon">Nombre del examen</div>
								<input type="text" id="exam_title" name="exam_title" class="form-control" data-required="true">
							</div>
							<table class="table table-bordered table-striped">
								<thead style="font-weight: bold">
									<tr>
										<td>En espa&ntilde;ol</td>
										<td>En franc&eacute;s</td>
										<td>Modo</td>
										<td></td>
									</tr>
								</thead>

								<tbody id="editor"></tbody>
							</table>
							<button type="button" class="btn btn-default" onclick="addRow()">
								<span class="glyphicon glyphicon-plus" aria-hidden="true"></span>
								A&ntilde;adir una pregunta
							</button>
							<center>
								<input type="button" class="btn btn-success" onclick="send()" value="Todos los cambios se han guardado" id="sendButton" data-loading-text="Guardando cambios..." disabled>
								<a href="admin.php" class="btn btn-default" role="button">
									<span class="glyphicon glyphicon-arrow-left" aria-hidden="true"></span>
									Volver atr&aacute;s
								</a>
							</center>
							<input style="display: none;" type="text" id="workaround" name="workaround">
						</form>
					</div>
				</div>
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
			<div class="container" id="principal" style="margin-top: 55px;">
				<h2>Administraci&oacute;n</h2>
				<div class="row">
					<div class="col-md-9">
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
								<a href="admin.php?editar" class="btn btn-default" role="button">
									<span class="glyphicon glyphicon-plus" aria-hidden="true"></span>
									Crear un nuevo examen
								</a>
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
					</div><!-- .col-md-9 -->
					<div class="col-md-3">
						<div class="well">
							<p>Sesi&oacute;n iniciada como <b><?php echo $_SESSION['username'] ?></b>.<br>
							<a href="admin.php?logout">Cerrar sesi&oacute;n</a>.</p>
						</div>
					</div><!-- .col-md-3 -->
				</div><!-- .row -->
			</div><!-- .container -->
		<?php endif; ?> 
	<?php else: ?>
		<?php
			$_SESSION['rC'] = new ReCaptcha(
				$GLOBAL_CONFIG['ReCaptcha']['enabled']
					AND doWeNeedToShowACaptcha($mysqli, $GLOBAL_CONFIG, isset($_POST['username']) ? $_POST['username'] : ''),
				$GLOBAL_CONFIG['ReCaptcha']['site_key'],
				$GLOBAL_CONFIG['ReCaptcha']['secret_key']
			);
			if($_SESSION['rC']->enabled) echo '<script src="'.$_SESSION['rC']->getApiURL().'"></script>';
		?>
		<div class="section container">
			<center>
			<h2 style="margin-top: 55px;">Acceso al panel de administraci&oacute;n</h2>
				<?php 
					if(isset($_GET['msg'])){
						switch($_GET['msg']){
							case '3':
								echo '<div class="alert alert-info alert-dismissible" role="alert">';
									echo '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>';
									echo '<strong>La sesi&oacute;n ha caducado. Por favor, inicie sesi&oacute;n de nuevo.</strong>';
								echo '</div>';
								break;
							case '4':
								echo '<div class="alert alert-danger alert-dismissible" role="alert">';
									echo '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>';
									echo '<strong>Nombre de usuario o contase&ntilde;a incorrectos.</strong>';
								echo '</div>';
								break;
							case '5':
								echo '<div class="alert alert-danger alert-dismissible" role="alert">';
									echo '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>';
									echo '<strong>No se ha superado la prueba del captcha.</strong>';
								echo '</div>';
								break;
						}
					}
				?>
				<form action="admin.php" method="POST" class="well form-inline">
					<div class="form-group">
						<label for="username" class="sr-only">Nombre de usuario</label>
						<input placeholder="Nombre de usuario" id="username" name="username" type="text" class="form-control"></input> 
					</div>
					<div class="form-group">
						<label for="password" class="sr-only">Contrase&ntilde;a</label>
						<input placeholder="Contrase&ntilde;a" id="password" name="password" type="password" class="form-control"></input>
					</div>
					<?php echo $_SESSION['rC']->getContainer(); ?>
					<button type="submit" name="login" class="btn btn-default">Enviar</button>
				</form>
			</center>
		</div>
	<?php endif; ?>
</body>
