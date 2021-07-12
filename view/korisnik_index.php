<?php require_once __DIR__ . '/_header.php'; ?>

<div class="box">
	<p> Ovo je stranica za korisnika</p>
	<br><br><br>
	<form action="index.php?rt=korisnik/logout" method="post">
		<input type="submit" id="logout-form" name="logout-form" value="Odjavi te se">
	<form>
</div>

<?php require_once __DIR__ . '/_footer.php'; ?>
