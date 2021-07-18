<?php


require_once __DIR__ . '/../app/database/db.class.php';
require_once __DIR__ . '/../model/transakcija.class.php';
require_once __DIR__ . '/../app/debug.php';
// require_once __DIR__ . '/../data';


function getApiStockDataUrl($stock_tick, $range) {
	$chart_interval = '1';
	$filter = $range === '1d' ? 'minute' : 'date';
	$data_url = 'https://sandbox.iexapis.com/stable/stock/' . $stock_tick
		. '/chart/' . $range . '?token=Tsk_846d0b9fb89741c583b142ee2f9bb434'
		. '&filter=' . $filter . ',low,open,close,high'
		. '&chartInterval=' . $chart_interval;
	return $data_url;
}

function getApiStockData($stock_tick, $range) {
	$data_url = getApiStockDataUrl($stock_tick, $range);
	$data_json = file_get_contents($data_url);
	$stock_data = json_decode($data_json, true);
	return $stock_data;
}

function getStockDataFromFile($stock_tick, $range) {
	$data_filepath = __DIR__  . '/../app/data/' . $stock_tick . '.data';
	$range_stock_data = False;
	if (file_exists($data_filepath)) {
		$stock_data_json = file_get_contents($data_filepath, LOCK_SH);
		if ($stock_data_json !== False) {
			$stock_data = json_decode($stock_data_json, true);
			if (array_key_exists($range, $stock_data)) {
				$range_stock_data = $stock_data[$range];
			}
		}
	}
	return $range_stock_data;
}

function saveStockDataToFile($stock_tick, $range_stock_data, $range) {
	$data_filepath = __DIR__  . '/../app/data/' . $stock_tick . '.data';
	$stock_data = [];
	if (file_exists($data_filepath)) {
		$stock_data_json = file_get_contents($data_filepath, LOCK_SH);
		if ($stock_data_json !== False) {
			$stock_data = json_decode($stock_data_json, true);
		}
	}
	$stock_data[$range] = $range_stock_data;
	$bytes = file_put_contents($data_filepath, json_encode($stock_data), LOCK_EX);
	if ($bytes !== False) {
		return true;
	}
	return False;
}

function getLastPrices() {
	$last_prices_filepath = __DIR__  . '/../app/data/last_prices.data';
	$last_prices = False;
	$last_prices_json = file_get_contents($last_prices_filepath, LOCK_SH);
	if ($last_prices_json !== False) {
		$last_prices = json_decode($last_prices_json, true);
	}
	return $last_prices;
}

function updateLastPrices($last_prices) {
	$today = new DateTime();
	$today_string = $today->format('Y-m-d');
	$last_prices_filepath = __DIR__  . '/../app/data/last_prices.data';
	$bytes =  file_put_contents($last_prices_filepath, json_encode($last_prices), LOCK_EX);
	if ($bytes !== False) {
		return true;
	}
	return False;
}

function getStockData($stock_ticks, $range) {
	if (empty($stock_ticks)) {
		return [];
	}
	$today = new DateTime();
	$today_string = $today->format('Y-m-d');
	$last_prices = getLastPrices();
	$database_stock_prices = [];
	$used_stock_ticks = [];
	foreach($stock_ticks as $stock_tick) {
		if ($last_prices !== False && array_key_exists($stock_tick, $last_prices)
				&& $last_prices[$stock_tick]['updated'] == $today_string) {
			$stock_data = getStockDataFromFile($stock_tick, $range);
			if ($stock_data !== False) {
				$database_stock_prices[$stock_tick] = $stock_data;
				$used_stock_ticks[] = $stock_tick;
			}
			
		}
	}
	foreach($stock_ticks as $stock_tick) {
		if (! array_key_exists($stock_tick, $database_stock_prices)) {
			$stock_data = getApiStockData($stock_tick, $range);
			if ($stock_data !== False) {
				saveStockDataToFile($stock_tick, $stock_data, $range);
				$last_price = $stock_data[count($stock_data) - 1]['close'];
				$last_prices[$stock_tick] = [
					'last_price' => $last_price,
					'updated' => $today_string
				];
				$database_stock_prices[$stock_tick] = $stock_data;
				$used_stock_ticks[] = $stock_tick;
			} else {
				$closest_tick = '';
				$transactions = Transakcija::where('oznaka_dionice', $stock_tick,
					['order by' => 'vrijeme', 'limit' => 1]);
				if (! empty($transactions)) {
					$price = $transactions[0]->vrijednost;
					$closest_difference = INF;
					foreach($last_prices as $lstock_tick => $lstock_info) {
						if (! in_array($lstock_tick, $used_stock_ticks)) {
							if ($lstock_info['last_price'] - $price < $closest_difference) {
								$closest_tick = $lstock_tick;
								$closest_difference = $lstock_info['last_price'] - $price;
							}
						}
					}
				}
				if (empty($closest_tick)) {
					foreach($last_prices as $lstock_tick => $lstock_info) {
						if (! in_array($lstock_tick, $used_stock_ticks)) {
							$closest_tick = $lstock_tick;
							break;
						}
					}
				}
				$stock_data = getStockDataFromFile($closest_tick, $range);
				$database_stock_prices[$stock_tick] = $stock_data;
				$used_stock_ticks[] = $closest_tick;

			}
			
		}
	}
	updateLastPrices($last_prices);
	return $database_stock_prices;
}



//$stock_prices = getStockData(['amzn', 'msft'], '1d');
//pprint($stock_prices, ['name', 'color' => 'red', 'json']);

if (isset($_GET['stock_ticks']) && isset($_GET['range'])) {
	header('Content-type: application/json');
	$stock_ticks = explode(',', $_GET['stock_ticks']);
	$range = $_GET['range'];
	$stock_prices = getStockData($stock_ticks, $range);
	echo json_encode($stock_prices);
}






?>