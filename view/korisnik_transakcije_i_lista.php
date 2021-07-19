<div id="tr_buttons">
	<button id="rank_list_button">Rang lista</button>
	<button id="transactions_button">Transakcije</button>
</div>

<div id="rank_list">
	<ol>
	<?php foreach($rang_lista as $korisnicko_ime): ?>
		<li><?php echo $korisnicko_ime ?></li> 
	<?php endforeach; ?>
	</ol>
</div>

<div id="transactions">
	<table>
		<tr>
			<th>
				<span>Oznaka</span>
				<br>
				<span>dionice</span>
			</th>
			<th>Tip</th>
			<th>Cijena</th>
			<th>Kol.</th>
			<th>Vrijeme</th>
		</tr>
		<?php foreach($transakcije as $transakcija): ?>
		<tr>
			<td><?php echo $transakcija['oznaka_dionice'] ?></td>
			<td><?php echo $transakcija['tip'] ?></td>
			<td class="price_column"><?php echo $transakcija['vrijednost'] ?></td>
			<td class="quantity_column"><?php echo $transakcija['kolicina'] ?></td>
			<td><?php echo $transakcija['vrijeme'] ?></td>
		</tr>
		<?php endforeach; ?>
	</table>
</div>

