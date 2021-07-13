<?php require_once __DIR__ . '/_header.php'; ?>

<div id="container">
	<header>
	
		<div id="stock_tabs">
			<div class="stock_tab">
				<button>
					<span class="stock_tab_text">AMZN</span>
					<span class="stock_tab_exit">×</span>
				</button>
			</div>

		</div>
		<div id="search_container">
			<input type="text" placeholder="oznaka (npr. AMZN)" id="search_input" value="">
		</div>
	</header>

	<aside id="left_sidebar">
	</aside>
	
	<aside id="right_sidebar">
	
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
			<form>
		</div>
	
	</aside>

	<main>
		<div id="stock_chart_container">
		</div>
	</main>

	<footer>
	</footer>
</div>


<?php require_once __DIR__ . '/_footer.php'; ?>
