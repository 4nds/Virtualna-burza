
<div id="search_container">
	<input type="text" placeholder="oznaka (npr. AMZN)" id="search_input" value="">
</div>

<div id="stocks_container">
	<?php foreach($lista_dionica as $oznaka_dionice): ?>
	<form action="index.php?rt=korisnik/transaction" method="post">
		<div class="stock_container">
			<div class="stock_tick"><?php echo $oznaka_dionice; ?></div>
			<div class="monthly_chart", data-stock_tick="<?php echo $oznaka_dionice; ?>">
			</div>
			<div class="transaction_container">
			
			<input name="oznaka_dionice" value="<?php echo $oznaka_dionice; ?>" style="display:none">
			<input type="hidden" class="form_price" name="cijena" value="">
			<!--
			<div id="price_container">
				<span>Cijena:</span>
				<span id="price_span"></span>
			</div>
			-->
			<div class="quantity_container">
				<span>Količina:</span>
				<input type="text" placeholder="npr. 3" class="quantity_input" name="kolicina" value="">
			</div>
			<div class="buy_container">
				<button type="submit" class="buy_button" name="kupi">Kupi</button>
			</div>
			<div class="sell_container">
				<button type="submit" class="sell_button" name="prodaj">Prodaj</button>
			</div>
			</div>
		</div>
	</form>
	<?php endforeach; ?>
</div>