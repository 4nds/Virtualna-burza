


//PHP_VARIABLES = {"capital":100000,"intial_capital":100000,"user_stocks":{"GOOG":2},"daily_transactions":[{"tip":"kupnja","oznaka_dionice":"GOOG","kolicina":2,"vrijednost":5123.23}]};
	

console.log(PHP_VARIABLES);
if ('transaction' in PHP_VARIABLES) {
	const transaction_info = PHP_VARIABLES['transaction'];
	if (transaction_info[1]) {
		//
	} else {
		console.log(transaction_info[0]);
		if (transaction_info[0] === 'buy') {
			window.alert('Transakcija neuspješna:\nNemate dovoljno kapitala za kupnju odabranih dionica.')
		} else if (transaction_info[0] === 'sell') {
			window.alert('Transakcija neuspješna:\nNemate dovoljno odabranih dionica za prodaju.')
		}
	}
}

const rank_list_div = document.querySelector('#rank_list');
const transactions_div = document.querySelector('#transactions'); 
const rank_list_button = document.querySelector('#rank_list_button');
const transactions_button = document.querySelector('#transactions_button');
transactions_div.style.display = 'none';

rank_list_button.addEventListener('click', event => {
	transactions_div.style.display = 'none';
	rank_list_div.style.display = '';
});

transactions_button.addEventListener('click', event => {
	rank_list_div.style.display = 'none';
	transactions_div.style.display = '';
});


const chart_divs = document.querySelectorAll('.monthly_chart');
let t = 1000;
chart_divs.forEach(chart_div => {
	window.setTimeout(() => {
		const stock_chart = new StockChart(chart_div, chart_div.dataset.stock_tick,
			{range: '1d', interval: 10, type: 'area', ratio: 1.5, name: ''});
		t += 1000;
	}, t);
	
	
});

const form_price = document.querySelector('.form_price');
form_price.value = 3450.23;





