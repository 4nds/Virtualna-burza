<div id="username_container">
	<span id="username_left_text">Korisnik:</span>
	<span id="username_right_text"><?php echo $korisnik->korisnicko_ime; ?></span>
</div>

<div id="capital_container">
	<span id="capital_left_text">Kapital:</span>
	<?php $korisnik->kapital = 9765046 ?>
	<span id="capital_right_text"><?php echo $korisnik->kapital / 100; ?> kn</span>
</div>

<div id="logout_container">
	<form action="index.php?rt=korisnik/logout" method="post">
		<button type="submit" id="logout_button">Odjavi se</button>
	</form>
</div>

