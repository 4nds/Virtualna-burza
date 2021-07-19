<?php require_once __DIR__ . '/_header.php'; ?>

<div class="box">
	<div class="login">
		<h1>Virtualna burza</h1>
		<form action="index.php?rt=login/checkForm" method="post">
			<?php if ($error_message === 'Korisničko ime ili lozinka nisu ispravni.'): ?> 
				<p><input type="text" name="korisnicko_ime" value="<?php echo $_POST['korisnicko_ime']; ?>" placeholder="Korisničko ime"></p>
				<p><input type="password" name="lozinka" value="" placeholder="Lozinka" autofocus></p>
			<?php else: ?>
				<p><input type="text" name="korisnicko_ime" value="" placeholder="Korisničko ime" autofocus></p>
				<p><input type="password" name="lozinka" value="" placeholder="Lozinka"></p>
			<?php endif; ?>
			<p id="error_message"><?php echo $error_message ; ?></p>
			<p class="submit"><input type="submit" id="login-form" name="login-form" value="Prijavi se"></p>
			<p class="submit"><input type="submit" id="signup-form" name="signup-form" value="Registriraj se"></p>
		</form>
	</div>
</div>

<?php require_once __DIR__ . '/_footer.php'; ?>
