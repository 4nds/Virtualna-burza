<?php require_once __DIR__ . '/_header.php'; ?>

<div class="box">
	<div class="header">
		<h1>Virtualna<span>burza</span></h1>
	</div>
	<div class="login">
		<form action="index.php?rt=login/checkForm" method="post">
			<input type="text" placeholder="korisničko ime" name="korisnicko_ime"><br>
			<input type="password" placeholder="lozinka" name="lozinka"><br>
			<p><?php echo $failure_message ; ?></p>
			<input type="submit" id="login-form" name="login-form" value="Prijavi se">
			<input type="submit" id="signup-form" name="signup-form" value="Registriraj se">
		</form>
	</div>
</div>

<?php require_once __DIR__ . '/_footer.php'; ?>
