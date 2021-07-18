<div id="tr_buttons">
	<button id="transactions_button">Transakcije</button>
	<button id="rank_list_button">Rang lista</button>
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
			<th>Oznaka dionice</th>
			<th>Tip</th>
			<th>Vrijednost</th>
			<th>Količina</th>
			<th>Vrijeme</th>
		</tr>
		<?php foreach($transakcije as $transakcija): ?>
		<tr>
			<td><?php echo $transakcija->oznaka_dionice ?></td>
			<td><?php echo $transakcija->tip ?></td>
			<td><?php echo $transakcija->vrijednost ?></td>
			<td><?php echo $transakcija->kolicina ?></td>
			<td><?php echo $transakcija->vrijeme ?></td>
		</tr>
		<?php endforeach; ?>
	</table>
</div>

