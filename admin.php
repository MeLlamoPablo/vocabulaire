<?php
require_once 'connect.php';

session_start();
//Max session length: 1 hour.
if(isset($_SESSION['administration']) && $_SESSION['administration'] === TRUE && isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 60*60)){
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
		$_GET['msg'] = 4; //Error al iniciar sesión
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
				var used_ids = [];
				var deleted_rows = [];
				var modes = {};
				<?php echo ($creating) ? '' : 'var exam = JSON.parse(\''.json_encode($data).'\');' ?>
				var creating = <?php echo ($creating) ? 'true' : 'false'; ?>;
				var userid = <?php echo $_SESSION['userid'] ?>;
				var session_token = '<?php echo $_SESSION['token'] ?>';
				var examid;

				function addRow(fra = '', esp = '', mode = 1, id = used_ids.length) {
					if(used_ids.indexOf(id) === -1){
						//The id used is not duplicate
						$('#editor').append(`
							<tr id="row${id}">
								<td><input class="form-control" required type="text" name="fra${id}" style="width: 95%;" data-required="true" value="${fra}"></td>
								<td><input class="form-control" required type="text" name="esp${id}" style="width: 95%;" data-required="true" value="${esp}"></td>
								<td>
									<div class="dropdown" id="dropdown${id}">
										<button class="btn btn-default dropdown-toggle" type="button" id="dropdown${id}_button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">
											<span id="dropdown${id}_title">Se puede pedir en franc&eacute;s y en espa&ntilde;ol</span>
											<span class="caret"></span>
										</button>
										<ul class="dropdown-menu" aria-labelledby="dropdown${id}_button">
											<li><a href="#" class="dropdownOption" id="dropdown${id}_mode1">Se puede pedir en franc&eacute;s y en espa&ntilde;ol</a></li>
											<li><a href="#" class="dropdownOption" id="dropdown${id}_mode2">Se pide siempre en espa&ntilde;ol</a></li>
											<li><a href="#" class="dropdownOption" id="dropdown${id}_mode3">Se pide siempre en franc&eacute;s</a></li>
										</ul>
									</div>
								</td>
								<td>
									<button type="button" class="btn btn-primary btn-xs btn-danger" id="delete${id}" onclick="deleteRow(${id})"
										    data-toggle="tooltip" data-placement="right" title="Borrar palabra">
										<span class="glyphicon glyphicon-trash" aria-hidden="true"></span>
									</button>
									<button style="display: none;" type="button" class="btn btn-primary btn-xs btn-success" id="undelete${id}" onclick="undeleteRow(${id})"
										    data-toggle="tooltip" data-placement="right" title="Conservar palabra">
										<span class="glyphicon glyphicon-ok" aria-hidden="true"></span>
									</button>
								</td>
							</tr>
						`);

						//We have to do this every time we add a new dropdownOption.
						$('.dropdownOption').click(event, onDropdownOptionClick);
						$('#dropdown' + id + '_mode' + mode).click(); //Set default mode
						$('[data-toggle="tooltip"]').tooltip();

						used_ids.push(id);

						return true;
					}else{
						console.error('Could not add row with id ' + id + '. That id is duplicate.');
						return false;
					}
						
				}

				function deleteRow(id){
					if($.inArray(id, used_ids) !== -1){
						if($('[name="fra' + id +'"]').val() !== ""
						   || $('[name="esp' + id +'"]').val() !== ""
						   || modes[id] !== 1){
							//If the row is not empty, it won't be deleted
							//Instead, it will be marked in red. Upon saving changes, the rows will be permanently deleted.

							$('#row' + id).addClass('danger');
							$('[name="fra' + id +'"], [name="esp' + id +'"]').attr('disabled', true);
							$('#dropdown' + id + ' > button').addClass('disabled');
							$('#delete' + id).css('display', 'none'); $('#undelete' + id).css('display', 'block');

							deleted_rows.push(id);
						}else{
							//If the row is empty, just delete it
							$('#row' + id).remove();

							//Also delete the default value from modes
							delete modes[id];
						}

						$('#main_form').trigger('rescan.areYouSure');
						return true;
					}else{
						console.error('Could not remove row with id ' + id + '. That row does not exist.');
						return false;
					}
				}

				function undeleteRow(id){
					var index = deleted_rows.indexOf(id);
					if(index !== -1){
						deleted_rows.splice(index, 1);

						$('#row' + id).removeClass('danger');
						$('[name="fra' + id +'"], [name="esp' + id +'"]').attr('disabled', false);
						$('#dropdown' + id + ' > button').removeClass('disabled');
						$('#delete' + id).css('display', 'block'); $('#undelete' + id).css('display', 'none');

						return true;
					}else{
						console.error('Could not undelete row with id ' + id + '. That row does not exist or is not marked as deleted.')
						return false;
					}
				}

				function onDropdownOptionClick(event){
					var regex = /dropdown([0-9]+)_mode([0-9]+)/;
					var matches = event.target.id.match(regex);
					modes[matches[1]] = +matches[2]; //The first match is the number of the row where the dropdown value was modified. 
													 //The second match is the dropdown value itself. We convert that from string to int.

					$('#dropdown' + matches[1] + '_title').html(event.target.text); //Change the dropdown title to the new option.

					//Workaround for jQuery.areYouSure not listening to dropdown changes.
					$('#workaround').val(JSON.stringify(modes));
					$('#main_form').trigger('rescan.areYouSure');
				}

				function send(){
					//Before sending, validate if every input has been filled
					var filled = true;
					$('[data-required="true"]').each(function(){
						if($(this).val() === '' && !$(this).prop('disabled')){
							filled = false;
							return false; //This only stpos the iteration, but doesn't make send() return false
						}
					});

					if(!filled){
						alert('Por favor, rellene todos los campos antes de guardar los cambios.')
						return false;
					}

					$('#sendButton').button('loading');

					var data = {};
					data['userid'] = userid;
					data['token'] = session_token;
					data['creating'] = creating.toString();
					data['title'] = $('#exam_title').val();
					if(!creating) data['examid'] = examid;

					data['questions'] = {}
					$.each(used_ids, function(k, v){
						if($.inArray(v, deleted_rows) === -1){
							data['questions'][v] = {
								fra: $('[name="fra' + v + '"]').val(),
								esp: $('[name="esp' + v + '"]').val(),
								mode: modes[v]
							}
						}
					});

					$.post('examHandler.php', data, function(response){
						r = JSON.parse(response)

						if(r['success']){
							$('#main_form').trigger('reinitialize.areYouSure');

							//If we were creating an exam, we now need to enter edit mode
							creating = false; 
							examid = r['examid'];
							$('#titulo_examen').html('Editando <b>' + data['title'] + '</b>');

							//We also need to permanently remove any rows marked as deleted
							for(var i = 0; i < deleted_rows.length; i++){
								$('#row' + deleted_rows[i]).remove();
							}

							return true;
						}else{
							console.error('The form was not saved. Error returned: ' + r['error']);

							localStorage.setItem('vocabapp_last_error', r['error']);
							localStorage.setItem('vocabapp_last_exam_data', JSON.stringify(data));
							console.log('In order to minimize damage, the exam data that was intended to be written into' +
								' the database was saved into the local storage along with the last error message.');
							console.log('Access it with localStorage.vocabapp_last_exam_data and localStorage.vocabapp_last_error');

							$('#sendButton').removeClass('btn-primary');
							$('#sendButton').addClass('btn-danger');
							$('#sendButton').attr('value', 'Ha ocurrido un error :(');

							return false;
						}
					});
				}

				$(document).ready(function(){
					if(creating){
						addRow();

						//Set default values for modes and put them in the workaround input. Then rescan.
						modes[0] = 1; $('#workaround').val(JSON.stringify(modes)); $('#main_form').trigger('rescan.areYouSure');
					}else{
						//Insert all data
						examid = exam.id;
						$('#exam_title').val(exam.name);
						for(var i = 0; i < exam.questions.length; i++){
							addRow(exam.questions[i]['esp'], exam.questions[i]['fra'], exam.questions[i]['modo']);
						}
					}

					$('#main_form').areYouSure({
						'message': '¡Atención! Hay cambios no guardados. Si abandona esta página se perderán definitivamente.'
					});

					$('#main_form').on('dirty.areYouSure', function(){
						$('#sendButton').removeAttr('disabled');
						$('#sendButton').attr('value', 'Guardar cambios');
						$('#sendButton').addClass('btn-primary');
						$('#sendButton').removeClass('btn-success');

						$('#sendButton').button('reset');
					});
				    $('#main_form').on('clean.areYouSure', function(){
				    	$('#sendButton').attr('disabled', true);
						$('#sendButton').attr('value', 'Todos los cambios se han guardado');
						$('#sendButton').removeClass('btn-primary');
						$('#sendButton').addClass('btn-success');
				    });

				});
			</script>
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
										<td>En franc&eacute;s</center>
										<td>En espa&ntilde;ol</center>
										<td>Modo</center>
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
									echo '<strong>Nomre de usuario o contase&ntilde;a incorrectos.</strong>';
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
					<button type="submit" name="login" class="btn btn-default">Enviar</button>
				</form>
			</center>
		</div>
	<?php endif; ?>
</body>
