<?php

if(! isset($_SESSION)) { 
	session_start(); 
}

ini_set('allow_url_fopen', 1);


require_once __DIR__ . '/../app/debug.php';
require_once __DIR__ . '/../model/korisnik.class.php';
require_once __DIR__ . '/../model/portfelj.class.php';
require_once __DIR__ . '/../model/transakcija.class.php';
require_once __DIR__ . '/../model/postavke.class.php';
require_once __DIR__ . '/stock_data.php';




class KorisnikController {
	
	public function index($transaction_info = []) {
		$username = $this->checkLogin();
		$rang_lista = $this->getRankList();
		$korisnik = Korisnik::where('korisnicko_ime', $username, ['limit' => 1])[0];
		$css_link = 'view/korisnik.css';
		$js_links = [
			'https://www.gstatic.com/charts/loader.js',
			'controller/chart.js',
			'controller/korisnik.js'
		];
		$neto_vrijednost = $this->getNetRevenue($korisnik);
		$ukupna_zarada = $neto_vrijednost - $korisnik->pocetni_kapital;
		$dnevna_zarada = $this->getDailyProfit($korisnik);
		$zarada_od_dividendi = $korisnik->zarada_od_dividendi;
		$transakcije = $this->getTransactions($korisnik);
		$lista_dionica = $this->getStockTicks($korisnik);
		if (isset($_SESSION['transaction'])) {
			$php_variables = json_encode(
				['transaction' => json_decode($_SESSION['transaction'])]);
			unset($_SESSION['transaction']);
		}
		require_once __DIR__ . '/../view/korisnik_index.php';
	}	
	
	protected function getUserStocks($korisnik) {
		$portfelji = Portfelj::where('korisnik_id', $korisnik->id);
		$user_stocks = [];
		foreach ($portfelji as $portfelj) {
			$user_stocks[$portfelj->oznaka_dionice] = $portfelj->kolicina;
		}
		return $user_stocks;
	}
	
	protected function getNetRevenue($korisnik) {
		$user_stocks = $this->getUserStocks($korisnik);
		$user_stock_ticks = [];
		foreach ($user_stocks as $stock_tick => $quantity) {
			$user_stock_ticks[] = $stock_tick;
		}
		$stock_prices = getStockData($user_stock_ticks, '1d');
		$stocks_value = 0;
		foreach ($user_stocks as $stock_tick => $quantity) {
			$price = end($stock_prices[$stock_tick])['close'];
			$stocks_value += $price * $quantity;	
		}
		$net_revenue = $korisnik->kapital + $stocks_value;
		return $net_revenue;
	}

	protected function getDailyTransactions($korisnik) {
		$transactions = Transakcija::where('korisnik_id', $korisnik->id);
		$daily_transactions = [];
		$today = new DateTime();
		$today_string = $today->format('Y-m-d');
		foreach ($transactions as $transaction) {
			if (substr($transaction->vrijeme, 0, 10) === $today_string) {
				$daily_transactions[] = [
					'tip' => $transaction->tip,
					'oznaka_dionice' => $transaction->oznaka_dionice,
					'kolicina' => $transaction->kolicina,
					'vrijednost' => $transaction->vrijednost	
				];
			}
		}
		return $daily_transactions;
	}
	
	protected function getDailyProfit($korisnik) {
		$daily_transactions = $this->getDailyTransactions($korisnik);
		$transaction_stock_ticks = [];
		foreach ($daily_transactions as $transaction) {
			$transaction_stock_ticks[] = $transaction['oznaka_dionice'];
		}
		$stock_prices = getStockData($transaction_stock_ticks, '1d');
		$daily_profit = 0;
		foreach($daily_transactions as $transaction) {
			$tick = $transaction['oznaka_dionice'];
			if ($transaction['tip'] === 'kupnja') {
				$price = end($stock_prices[$tick])['close'];
				$daily_profit += $transaction['kolicina'] * $price
					- $transaction['kolicina'] * $transaction['vrijednost'];
			} else if ($transaction['tip'] === 'prodaja') {
				$price = $stock_prices[$tick][0]['open'];
				$daily_profit += $transaction['kolicina'] * $transaction['vrijednost']
					- $transaction['kolicina'] * $price;
			}
		}
		return $daily_profit;
	}
	
	protected static function compareByRank($korisnik1, $korisnik2) {
		return $korisnik1->rang - $korisnik2->rang;
	}
	
	protected static function compareByNetValue($korisnik1, $korisnik2) {
		return $korisnik1[1] - $korisnik2[1];
	}
	
	protected function getRankList() {
		$postavke = Postavke::all(['limit' => 1])[0];
		$today = new DateTime();
		$today_string = $today->format('Y-m-d');
		if (substr($postavke->vrijeme_rang_liste, 0, 10) !== $today_string) {
			$this->setRankList();
		}
		$korisnici = Korisnik::all();
		usort($korisnici, array('KorisnikController', 'compareByRank'));
		$rank_list = [];
		foreach ($korisnici as $korisnik) {
			$rank_list[] = $korisnik->korisnicko_ime;
		}
		return $rank_list;
	}
	
	protected function getStockPrice($stock_tick) {
		$data_url = 'https://sandbox.iexapis.com/stable/stock/' . $stock_tick . '/intraday-prices?token=Tsk_846d0b9fb89741c583b142ee2f9bb434&filter=minute,close&chartInterval=10';
		$data_json = file_get_contents($data_url);
		$stock_data = json_decode($data_json);
		$price = $stock_data[count($stock_data) - 1]->close;
		return $price;
	}
	
	protected function getNetValue($korisnik, $stock_prices, $portfelji) {
		$korisnikovi_portfelji = [];
		foreach ($portfelji as $portfelj) {
			if ($portfelj->korisnik_id === $korisnik->id) {
				$korisnikovi_portfelji[] = $portfelj;
			}
		}
		$portafolio_value = 0;
		foreach ($korisnikovi_portfelji as $portfelj) {
			$portafolio_value += $portfelj->kolicina * 
				$stock_prices[$portfelj->oznaka_dionice];
		}
		$net_value = $korisnik->kapital + $portafolio_value;
		return $net_value;
	}
	
	protected function setRankList() {
		$korisnici = Korisnik::all();
		$portfelji = Portfelj::all();
		$stock_prices = [];
		foreach ($portfelji as $portfelj) {
			if (! array_key_exists($portfelj->oznaka_dionice, $stock_prices)) {
				$stock_prices[$portfelj->oznaka_dionice] = 
					$this->getStockPrice($portfelj->oznaka_dionice);
			}
		}
		$net_values = [];
		foreach ($korisnici as $korisnik) {
			$net_values[] = [$korisnik,
				$this->getNetValue($korisnik, $stock_prices, $portfelji)];
		}
		usort($net_values, array('KorisnikController', 'compareByNetValue'));
		$i = 1;
		foreach ($net_values as $net_value_element) {
			list ($korisnik, $net_value) = $net_value_element;
			$korisnik->rang = $i;
			$korisnik->save();
			$i++;
		}
	}
	
	protected function getTransactions($korisnik) {
		$transactions = Transakcija::where('korisnik_id', $korisnik->id);
		return $transactions;
	}
	
	protected function getStockTicks($korisnik) {
		$user_stocks = $this->getUserStocks($korisnik);
		$user_stock_ticks = [];
		foreach ($user_stocks as $stock_tick => $quantity) {
			$user_stock_ticks[] = $stock_tick;
		}
		$stock_ticks = array_slice($user_stock_ticks, 0, 20);
		$default_stock_ticks = ['AAPL', 'MSFT', 'AMZN', 'FB', 'GOOG',
			'TSLA', 'NVDA', 'JPM', 'JNJ', 'V', 'UNH', 'PYPL', 'HD', 'MA',
			'DIS', 'BAC', 'ADBE', 'CMCSA', 'XOM', 'NFLX'];
		;
		for ($i = count($user_stock_ticks), $j = 0; $i < 20; $i++, $j++) {
			if (! in_array($default_stock_ticks[$j], $stock_ticks))
			$stock_ticks[] = $default_stock_ticks[$j];
		}
		$stock_ticks = array_slice($stock_ticks, 0, 2);
		return $stock_ticks;
	}
	
	protected function checkLogin() {
		if (isset($_SESSION['login_hash'])) {
			$login_hash = $_SESSION['login_hash'];
			list($username, $cookie_hash) = explode(',', $_SESSION['login_hash']);
			$SECRET_WORD = 'Računarski praktikum 2 - Virtualna burza';
			if (md5($username . $SECRET_WORD) === $cookie_hash) {
				return $username;
			}
		}
		$this->gotoLogin();
		return false;
	}
	
	public function gotoLogin() {
		header('Location: index.php?rt=login');
	}
	
	public function logout() {
		session_unset();
		session_destroy();
		$this->gotoLogin();
	}
	
	protected function buy($stock_tick, $quantity, $price) {
		$username = $this->checkLogin();
		$korisnik = Korisnik::where('korisnicko_ime', $username, ['limit' => 1])[0];
		if ($korisnik->kapital > $quantity * $price) {
			$today = new DateTime();
			$today_string = $today->format('Y-m-d');
			$transakcija = new Transakcija([
				'korisnik_id' => $korisnik->id,
				'tip' => 'kupi',
				'oznaka_dionice' => $stock_tick,
				'kolicina' => $quantity,
				'vrijednost' => $price,
				'vrijeme' =>$today_string
			]);
			$transakcija->save();
			$portfelji = Portfelj::where(['korisnik_id', 'oznaka_dionice'],
				[$korisnik->id, $stock_tick], ['limit' => 1]);
			if (! empty($portfelji)) {
				$portfelj = $portfelji[0];
				$portfelj->kolicina += $quantity;
			} else {
				$portfelj = new Portfelj([
					'korisnik_id' => $korisnik->id,
					'oznaka_dionice' => $stock_tick,
					'kolicina' => $quantity
				]);
			}
			$portfelj->save();
			$korisnik->kapital -= $quantity * $price;
			$korisnik->save();
			return true;
		} else {
			return false;
		}
	}
	
	protected function sell($stock_tick, $quantity, $price) {
		$username = $this->checkLogin();
		$korisnik = Korisnik::where('korisnicko_ime', $username, ['limit' => 1])[0];
		$portfelji = Portfelj::where(['korisnik_id', 'oznaka_dionice'],
			[$korisnik->id, $stock_tick], ['limit' => 1]);
		if (! empty($portfelji)) {
			$portfelj = $portfelji[0];
			if ($portfelj->kolicina > $quantity) {
				$today = new DateTime();
				$today_string = $today->format('Y-m-d');
				$transakcija = new Transakcija([
					'korisnik_id' => $korisnik->id,
					'tip' => 'prodaj',
					'oznaka_dionice' => $stock_tick,
					'kolicina' => $quantity,
					'vrijednost' => $price,
					'vrijeme' =>$today_string
				]);
				$transakcija->save();
				$portfelj->kolicina -= $quantity;
				$portfelj->save();
				$korisnik->kapital += $quantity * $price;
				$korisnik->save();
				return true;
			}
		}
		return false;
	}
	
	public function transaction() {
		if(isset($_POST['kupi'])) {
			
			$bought = $this->buy($_POST['oznaka_dionice'],
				$_POST['kolicina'], $_POST['cijena']);
			pprint(['transaction' => ['buy', $bought]], ['name', 'json']);
			$_SESSION['transaction'] = json_encode(['buy', $bought]);
			header('Location: index.php?rt=korisnik');
		} else if(isset($_POST['prodaj'])) {
			$sold = $this->sell($_POST['oznaka_dionice'],
				$_POST['kolicina'], $_POST['cijena']);
			$_SESSION['transaction'] = json_encode(['sell', $sold]);
			header('Location: index.php?rt=korisnik');
		}
	}
	
}

?>