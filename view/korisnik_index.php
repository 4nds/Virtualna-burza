<?php require_once __DIR__ . '/_header.php'; ?>

<div id="container">
	<div id="user_and_transactions_container">
		<div id="user_container">
			<?php require_once __DIR__ . '/korisnik_informacije.php'; ?>
		</div>
		
		<div id="transactions_container">
			<?php require_once __DIR__ . '/korisnik_transakcije_i_lista.php'; ?>
		</div>
	</div>
	
	<div id="trading_container">
		<?php require_once __DIR__ . '/korisnik_dionice.php'; ?>
	</div>

</div>

<?php require_once __DIR__ . '/_footer.php'; ?>
