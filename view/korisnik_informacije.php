
<div id="username_container">
	<span id="username"><?php echo $korisnik->korisnicko_ime; ?> <b>&#35;</b><?php echo $korisnik->rang; ?></span>
	<form id="logout_form" action="index.php?rt=korisnik/logout" method="post">
		<button id="logout_button">Odjavi se</button>
	</form>
</div>
<table id="user_info">
	<tr>
		<td>Kapital:</td>
		<td class="user_info_number"><?php echo number_format($korisnik->kapital, 2, '.', ''); ?> kn</td>
	</tr>
	<tr>
		<td>Neto vrijednost:</td>
		<td class="user_info_number"><?php echo $neto_vrijednost; ?> kn</td>
	</tr>
	<tr>
		<td>Ukupna zarada:</td>
		<td class="user_info_number"><?php echo $ukupna_zarada; ?> kn</td>
	</tr>
	<tr>
		<td>Dnevna zarada:</td>
		<td class="user_info_number"><?php echo $dnevna_zarada; ?> kn</td>
	</tr>
	<tr>
		<td>Zarada od dividendi:</td>
		<td class="user_info_number"><?php echo $zarada_od_dividendi; ?> kn</td>
	</tr>
</table>












