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
	<?php require_once __DIR__ . '/left_sidebar.php'; ?>
	</aside>
	
	<aside id="right_sidebar">	
	<?php require_once __DIR__ . '/right_sidebar.php'; ?>
	</aside>
	

	<main>
		<div id="stock_chart_container">
		</div>
		<div id="transaction_container">
			<div id="price_container">
				<span>Cijena:</span>
				<span id="price_span"></span>
			</div>
			<div id="quantity_container">
				<span>Količina:</span>
				<input type="text" placeholder="npr. 3" id="quantity_input" value="">
			</div>
			<div id="buy_container">
				<button id="buy_button">Kupi</button>
			</div>
			<div id="sell_container">
				<button id="sell_button">Prodaj</button>
			</div>
		</div>
	</main>

	<footer>
	</footer>
</div>

<?php require_once __DIR__ . '/_footer.php'; ?>
