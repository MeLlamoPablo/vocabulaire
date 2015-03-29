<script src="js/jquery.js"></script>
<script src="js/bootstrap.min.js"></script>

<div class="navbar navbar-default navbar-fixed-top">
	<div class="container">
		<div class="navbar-brand">L&#39;app du vocabulaire<span style="vertical-align: sub; font-size:60%;">ßeta</span></div>
		<button class="navbar-toggle" data-toggle="collapse" data-target=".navHeaderCollapse">
			<span class="icon-bar"></span>
			<span class="icon-bar"></span>
			<span class="icon-bar"></span>
		</button>
		<div class="collapse navbar-collapse navHeaderCollapse">
			<ul class="nav navbar-nav navbar-right">
				<li<?php if($active===1){echo' class="active"';} ?>><a href="index.php">Accueil</a></li>
				<li<?php if($active===2){echo' class="active"';} ?>><a href="admin.php">Administrateur</a></li>
			</ul>
		</div>
	</div>
</div>