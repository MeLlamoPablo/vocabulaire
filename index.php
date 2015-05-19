<?php

//BRANCH 'compatible'
//This version is compatible with PHP versions older than PHP 5.3, BUT it's slightly less efficient
//It may also be out of date, though it shouldn't.
//For PHP versions newer than 5.3, use 'master' branch

session_start();
require_once 'connect.php';

if(isset($_POST['nom'])){
	$_SESSION['nom'] = $_POST['nom'];
}

//Is failureMode enabled?
if(isset($_GET['failureMode'])){
	$_SESSION['failureMode'] = TRUE;
}else{
	$_SESSION['failureMode'] = FALSE;
}
?>
<!DOCTYPE html>
<html>
	<head>
		<title>L&#39;app du vocabulaire</title>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<link href="css/bootstrap.min.css" rel="stylesheet">
		<link href="style.css" rel="stylesheet">
	</head>
	<body>
		<?php $active = 1; include 'menu.php';
		if(!isset($_POST['envoyer'])):
		?>
			<?php
			if(isset($_GET['examen'])):
			?>
				<div class="container">
					<div class="jumbotron">
						<h2>Salut!</h2>
						<p>Tu dois remplir le tableau suivant. Pour que la r&eacute;ponse soit bonne, elle doit &ecirc;tre compl&egrave;tement identique &agrave; celle du serveur, donc fais attention &agrave; mettre les articles et les majuscules correctement!</p>
						<ul>
							<li>Le premier mot (Soit un article, soit un verbe, soit un adjectif, etc.) commence toujours par une  majuscule.</li>
							<li>Voici des caractères difficiles &agrave; &eacute;crire avec le clavier. Copie-les si tu en as besoin:<br> &aacute; &eacute; &iacute; &oacute; &uacute; &#124; &agrave; &egrave; &igrave; &ograve; &ugrave; &#124; &acirc; &ecirc; &icirc; &ocirc; &ucirc; &#124; &auml; &euml; &iuml; &ouml; &uuml; &#124; &#39; (apostrophe)<br>&Aacute; &Eacute; &Iacute; &Oacute; &Uacute; &#124; &Agrave; &Egrave; &Igrave; &Ograve; &Ugrave; &#124; &Acirc; &Ecirc; &Icirc; &Ocirc; &Ucirc; &#124; &Auml; &Euml; &Iuml; &Ouml; &Uuml;</li>
						</ul>
						<?php if($_SESSION['failureMode']){
							echo '<div class="alert alert-info" role="alert">Maintenant, on te demande seulement les questions que tu as rat&eacute; la derni&eacute;re fois. Si ti veux faire tout l\'examen, <a href="index.php?examen='.$_GET['examen'].'">fais click ici</a>.</div>';
						}
						?>
						<div class="section" style="margin-top: 0px;">
							<form action="index.php<?php if($_SESSION['failureMode']){ echo '?failureMode'; } ?>" method="post">
								<div class="form-group">
									<table class="table table-condensed">
										<thead>
											<tr>
												<td style="text-align: center;">En fran&ccedil;ais</td>
												<td style="text-align: center;">En espagnol</td>
											</tr>
										</thead>
										<tbody>
										<?php
											//If exam is not public, redirect to the main page
											$resultado = $mysqli->query("SELECT * FROM examenes WHERE id = ".$_GET['examen']);
											if($resultado->fetch_assoc()['activa'] == 0) die('<meta http-equiv="refresh" content="0; url=index.php" />');

											//Get exam data
											if(!$_SESSION['failureMode']){
												//If failureMode is off, get the exam data from the database

												$resultado = $mysqli->query("SELECT * FROM preguntas WHERE examen = ".$_GET['examen']);
												$num = 0;

												//Count the number of questions
												while($pregunta = $resultado->fetch_assoc()){
													$orden[] = $num;
													$num++;
												}
												
												shuffle($orden);

												$num = 0;
												$resultado = $mysqli->query("SELECT * FROM preguntas WHERE examen = ".$_GET['examen']);
												while($pregunta = $resultado->fetch_assoc()){
													$fra[$orden[$num]] = $pregunta['esp']; //Al reves para fixear un bug
													$esp[$orden[$num]] = $pregunta['fra'];

													//METODO:
													//1 - Se puede pedir en frances y en espanol
													//2 - Se pide siempre en espanol
													//3 - Se pide siempre en frances

													$metodo[$orden[$num]] = $pregunta['modo'];
													$num++;
												}
											}else{
												//If failureMode is on, get the exam data from _SESSION
												$esp = $_SESSION['failuresEsp'];
												$fra = $_SESSION['failuresFra'];
												$metodo = $_SESSION['failuresMetodo'];

												//Count how many questions
												$count = 0;
												while(isset($esp[$count])){
													$orden[] = $count;
													$count++;
												}
												
												shuffle($orden);
											}

											$_SESSION['esp'] = $esp;
											$_SESSION['fra'] = $fra;
											$_SESSION['metodo'] = $metodo;
											$_SESSION['numero'] = count($esp);
											$_SESSION['examen'] = $_GET['examen'];

											$num = 0;
											$pregunta = array();

											while(isset($fra[$num])){
												if($metodo[$num] == 1){
													if(rand(0,1) == 1){
														$pregunta[$num] = 'esp';
													}else{
														$pregunta[$num] = 'fra';
													}
												}elseif($metodo[$num] == 2){
													$pregunta[$num] = 'esp';
												}elseif($metodo[$num] == 3){
													$pregunta[$num] = 'fra';
												}else{
													die('Ha ocurrido un error. Error 001.');
												}
												$num++;
											}

											$num = 0;
											while(isset($fra[$num])){
												if($pregunta[$num] === 'esp'){
													//Se da en frances, se pide en espanol
													echo '<tr>';
													echo '<td style="text-align: center"><input class="form-control" type="text" required autocomplete="off" name="fra'.$num.'" value="'.$fra[$num].'" readonly="readonly"></td>';
													echo '<td style="text-align: center"><input class="form-control" type="text" required autocomplete="off" name="esp'.$num.'"></td>';
													echo '</tr>';
												}else{
													//Se da en espanol, se pide en frances
													echo '<tr>';
													echo '<td style="text-align: center"><input class="form-control" type="text" required autocomplete="off" name="fra'.$num.'"></td>';
													echo '<td style="text-align: center"><input class="form-control" type="text" required autocomplete="off" name="esp'.$num.'" value="'.$esp[$num].'" readonly="true"></td>';
													echo '</tr>';
												}
												$num++;
											}

											$_SESSION['pregunta'] = $pregunta;
										?>
									</tbody>
								</table>
								<input class="btn btn-default" type="submit" value="Envoyer!" name="envoyer" style="margin-top: 10px;">
							</div>
						</form>
					</div>
				</div>
			<?php
			else://isset($_SESSION['examen'])
			$footer_fixed = TRUE;
			?>
				<div class="container">
					<div class="jumbotron">
						<h2>Bienvenu &agrave; l'app du vocabulaire!</h2>
						<p>Choisis un examen:</p>
						<?php
						$resultado = $mysqli->query("SELECT * FROM examenes WHERE activa = 1");
						echo '<ul>';
						while ($examenes = $resultado->fetch_assoc()) {
							echo '<li><a href="index.php?examen='.$examenes['id'].'">'.$examenes['nombre'].'</a></li>';
						}
						echo '</ul>';
						?>
					</div>
				</div>
			<?php
			endif;//isset($_SESSION['examen'])
			?>
		<?php
		else: //!isset($_POST['envoyer'])
		?>
		<div class="container">
			<div class="jumbotron">
				<div class="section">
					<h2>Correction</h2>
					<table class="table table-condensed">
						<thead>
							<tr>
								<td style="text-align: center"><b>Mot</b></td>
								<td style="text-align: center"><b>Ta r&eacute;ponse</b></td>
								<td style="text-align: center"><b>La bonne r&eacute;ponse</b></td>
							</tr>
						</thead>
						<tbody>
							<?php
							$esp = $_SESSION['esp'];
							$fra = $_SESSION['fra'];
							$numero = $_SESSION['numero'];
							$pregunta = $_SESSION['pregunta'];
							$metodo = $_SESSION['metodo'];

							//Corregir las apostrofes y htmlspecialchars();
							$num = 0;
							while(isset($_POST['esp'.$num])){
								$_POST['esp'.$num] = str_replace("\'", "'", $_POST['esp'.$num]);
								$_POST['esp'.$num] = htmlentities($_POST['esp'.$num], ENT_QUOTES, "UTF-8");
								$num++;
							}
							$num = 0;
							while(isset($_POST['fra'.$num])){
								$_POST['fra'.$num] = str_replace("\'", "'", $_POST['fra'.$num]);
								$_POST['fra'.$num] = htmlentities($_POST['fra'.$num], ENT_QUOTES, "UTF-8");
								$num++;
							}

							//Initialize 'failures', we're gonna need it for the 'failure only' mode
							$failuresEsp = array();
							$failuresFra = array();
							$failuresMetodo = array();

							$num = 0;
							$good = 0;
							$bad = 0;
							while($num < $numero){
								if(($_POST['fra'.$num] === $fra[$num]) && ($_POST['esp'.$num] === $esp[$num])){
									echo '<tr class="success">';
									$good++;
								}else{
									echo '<tr class="danger">';

									//Add the mistaken words to the failures variables
									$failuresEsp[$bad] = $esp[$num];
									$failuresFra[$bad] = $fra[$num];
									$failuresMetodo[$bad] = $metodo[$num];

									$bad++;
								}
								//Mot
								echo '<td style="text-align: center">';
									if($pregunta[$num] === 'fra'){
										echo $esp[$num];
									}else{
										echo $fra[$num];
									}
								echo '</td>';

								//Ta reponse
								echo '<td style="text-align: center">';
									if($pregunta[$num] === 'fra'){
										echo $_POST['fra'.$num];
									}else{
										echo $_POST['esp'.$num];
									}
								echo '</td>';

								//La bonne reponse
								echo '<td style="text-align: center">';
									if($pregunta[$num] === 'fra'){
										echo $fra[$num];
									}else{
										echo $esp[$num];
									}
								echo '</td></tr>';
								$num++;
							}
							
							//Add the failures variables into the _SESSION so that we can carry them into the failure only mode
							$_SESSION['failuresEsp'] = $failuresEsp;
							$_SESSION['failuresFra'] = $failuresFra;
							$_SESSION['failuresMetodo'] = $failuresMetodo;
							?>
						</tbody>
					</table>
				</div class="section">
				<div>
					<?php //We only give the user a mark if the failureMode is disabled
					if($_SESSION['failureMode'] == FALSE): ?>
						<p style="font-size:7em; text-align: center;">Tu as eu un <?php
						$nota = (10*$good)/$num;
						$nota = round($nota, 2);
						if($nota >= 5){
							echo '<b style="color: #008b0a;">'.$nota.'</b>';
						}else{
							echo '<b style="color: #b20000;">'.$nota.'</b>';
						}
						?>!</p>
					<?php endif; ?>
					<div class="text-center">
						<a class="btn btn-default" href="index.php?examen=<?php echo $_SESSION['examen'] ?>">Recommen&ccedil;er de z&eacute;ro</a>
						<?php
						//Offer the failure only mode if there is at least 1 mistake
						if($bad != 0){
							echo '<a class="btn btn-default" href="index.php?examen='.$_SESSION['examen'].'&failureMode" data-toggle="tooltip" data-placement="bottom" title="Si tu cliques sur ce bouton, on te demandera seulement les questions que tu as rat&eacute;. On ignorera les succ&egrave;s.">Recommen&ccedil;er seulement avec les erreurs</a>';
						}
						?>
					</div>
				</div>
			</div>
		</div>
		<?php
		endif;
		?>
		<div class="navbar navbar-default <?php if(isset($footer_fixed) AND $footer_fixed){ echo 'navbar-fixed-bottom'; } ?>">
			<div class="container">
				<p class="navbar-text">Application cr&eacute;&eacute;e par Pablo Rodr&iacute;guez avec l&#39;aide de <a href="http://php.net" target="_blank"><label class="label label-default">PHP</label></a>, <a href="http://jquery.com/" target="_blank"><label class="label label-default">Jquery</label></a> et <a href="http://getbootstrap.com" target="_blank"><label class="label label-default">Bootstrap</label></a>. <a data-toggle="modal" data-target="#changelogModal">v2.2.1ß</a>. <a href="https://github.com/MeLlamoPablo/vocabulaire" target="_blank">Code source</a>.</p>
			</div>
		</div>
		<!-- Changelog -->
		<div class="modal fade" id="changelogModal" tabindex="-1" role="dialog" aria-labelledby="changelogModal" aria-hidden="true">
			<div class="modal-dialog">
				<div class="modal-content">
					<div class="modal-header">
					<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
						<h4 class="modal-title" id="myModalLabel">Changelog</h4>
					</div>
					<div class="modal-body">
						<ul>
							<li>v2.2.1ß (18/05/15)
								<ul>
									<li>Eliminadas funciones declaradas obsoletas por PHP.</li>
									<li>Optimización de código</li>
									<li>Mejorada la función para exportar</li>
									<li>El modo "recomenzar con fallos" ya no dice la nota.</li>
								</ul>
							</li>
							<li>v2.2ß (12/05/15)
								<ul>
									<li>Añadida funcionalidad para importar y exportar en el backend.</li>
								</ul>
							</li>
							<li>v2.1.1ß (11/05/15)
								<ul>
									<li>Arreglo de bugs y mejoras en el backend.</li>
								</ul>
							</li>
							<li>v2.1ß (05/4/15)
								<ul>
									<li>A&ntilde;adida la funci&oacute;n "recomenzar con fallos".</li>
								</ul>
							</li>
							<li>v2.0.1ß (29/3/15)
								<ul>
									<li>Mejorada la codificaci&oacute;n de caracteres.</li>
									<li>Arreglado un bug de dise&ntilde;o que hac&iacute;a que el footer tapara contenido en la pantalla de correcci&oacute;n.</li>
									<li>Cambios est&eacute;ticos menores.</li>
								</ul>
							</li>
							<li>v2.0ß
								<ul>
									<li>A&ntilde;adido un modo administrador.</li>
									<li>C&oacute;digo optimizado.</li>
									<li>Primera versi&oacute;n beta.</li>
								</ul>
							</li>
						</ul>
					</div>
					<div class="modal-footer">
						<button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
					</div>
				</div>
			</div>
		</div>
		<script type="text/javascript">
			//Initialize bootstrap tooltips
			$(function () {
			  $('[data-toggle="tooltip"]').tooltip()
			})
		</script>
	</body>
</html>
