


//PHP_VARIABLES = {"capital":100000,"intial_capital":100000,"user_stocks":{"GOOG":2},"daily_transactions":[{"tip":"kupnja","oznaka_dionice":"GOOG","kolicina":2,"vrijednost":5123.23}]};
	
	
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
rank_list_button.focus();
transactions_div.style.display = 'none';

rank_list_button.addEventListener('click', event => {
	transactions_div.style.display = 'none';
	rank_list_div.style.display = '';
});

transactions_button.addEventListener('click', event => {
	rank_list_div.style.display = 'none';
	transactions_div.style.display = '';
});


const stocks_container = document.querySelector('#stocks_container');

stocks_container.addEventListener('click', event => {
	const stock_inner_container = event.target.closest('.stock_inner_container');
	if (stock_inner_container) {
		const stock_outer_container = stock_inner_container.parentNode;
		const transaction_container = stock_inner_container
			.querySelector('.transaction_container');
		if (stock_inner_container.classList
				.contains('stock_inner_container_selected')) {
			stock_inner_container.classList
				.remove('stock_inner_container_selected');
			transaction_container.style.display = '';
			stock_outer_container.style.gridRowEnd = '';
		} else {
			stocks_container.querySelectorAll('.stock_inner_container')
					.forEach(lstock_inner_container => {
				const lstock_outer_container = lstock_inner_container.parentNode;
				const ltransaction_container = lstock_inner_container
					.querySelector('.transaction_container');
				lstock_inner_container.classList
					.remove('stock_inner_container_selected');
				ltransaction_container.style.display = '';
				lstock_outer_container.style.gridRowEnd = '';
			});
			stock_inner_container.classList.add('stock_inner_container_selected');
			transaction_container.style.display = 'grid';
			stock_outer_container.style.gridRowEnd = 'span 2';
			console.log(stock_outer_container);
		}
	}
});




function sleep(ms) {
	return new Promise((resolve, reject) => setTimeout(resolve, ms));
}

async function main() {
	const stock_info_divs = document.querySelectorAll('.stock_info_container');
	for (const stock_info_div of stock_info_divs) {
		const daily_chart_div = stock_info_div.querySelector('.daily_chart');
		const stock_price_container = stock_info_div
			.querySelector('.stock_price_container');
		const stock_price_div = stock_price_container
			.querySelector('.stock_price');
		const form_price_input = stock_price_container.querySelector('input');
		const stock_percentage_div = stock_info_div
			.querySelector('.stock_percentage');
		const stock_chart = new StockChart(daily_chart_div, daily_chart_div.dataset.stock_tick,
			{range: '1d', interval: 10, type: 'area', ratio: 1.5, name: '',
			 hide_axis: true, hide_gridlines : true, color_by_percentage: true});
		const last_price = await stock_chart.getLastPrice();
		const percentage = await stock_chart.getPercentage();
		stock_price_div.textContent = `${last_price} kn`;
		form_price_input.value = last_price;
		stock_percentage_div.textContent = `${percentage.toFixed(2)} %`;
		stock_percentage_div.style.color = 
			//percentage >= 0 ? '#34E36F' : '#FF6341';
			//percentage >= 0 ? '#00BB00' : '#EE0000';
			percentage >= 0 ? '#00BB00' : '#FF0000';
		await sleep(100);	
	}
}

main();




const stock_container = document.querySelector('.stock_inner_container');
const transaction_container = stock_container.querySelector('.transaction_container');

//console.log(stock_container);
//stock_container.classList.add('stock_inner_container_selected')
//transaction_container.style.display = 'grid';





