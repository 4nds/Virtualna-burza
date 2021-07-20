
<div id="search_container">
	<input type="text" id="search_input" placeholder="oznaka (npr. AMZN)" value="">
</div>

<div id="stocks_container">
	<?php foreach($lista_dionica as $oznaka_dionice): ?>
	<div class="stock_outer_container">
		<form class="stock_inner_container" action="index.php?rt=korisnik/transaction" method="post">
			<div class="stock_info_container">
				<div class="stock_tick_container">
					<div class="stock_tick"><?php echo $oznaka_dionice; ?></div>
					<input type="hidden" name="oznaka_dionice" value="<?php echo $oznaka_dionice; ?>">
				</div>
				<div class="daily_chart_container">
					<div class="daily_chart", data-stock_tick="<?php echo $oznaka_dionice; ?>"></div>
				</div>
				<div class="stock_price_container">
					<div class="stock_price"></div>
					<input type="hidden" name="cijena" value="">
				</div>
				<div class="stock_percentage_container">
					<div class="stock_percentage"></div>
				</div>
			</div>
			<div class="transaction_container">
				<div class="quantity_text_container">
					<div class="quantity_text">Količina:</div>
				</div>
				<div class="quantity_input_container">
					<input type="text" placeholder="npr. 3" class="quantity_input" name="kolicina" value="">
				</div>
				<div class="buy_container">
					<button type="submit" class="buy_button" name="kupi">Kupi</button>
				</div>
				<div class="sell_container">
					<button type="submit" class="sell_button" name="prodaj">Prodaj</button>
				</div>
			</div>
		</form>
	</div>
	<?php endforeach; ?>
</div>