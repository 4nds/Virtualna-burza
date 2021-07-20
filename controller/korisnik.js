	
if ('transaction' in PHP_VARIABLES) {
	const transaction_info = PHP_VARIABLES['transaction'];
	if (transaction_info[1]) {
		//
	} else {
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





//
async function addStockInfo(stock_info_div) {
	const daily_chart_div = stock_info_div.querySelector('.daily_chart');
	const stock_price_container = stock_info_div
		.querySelector('.stock_price_container');
	const stock_price_div = stock_price_container
		.querySelector('.stock_price');
	const form_price_input = stock_price_container.querySelector('input');
	const stock_percentage_div = stock_info_div
		.querySelector('.stock_percentage');
	const stock_chart = new StockChart(daily_chart_div, daily_chart_div.dataset.stock_tick,
		{range: 'lfd', interval: 10, type: 'area', ratio: 1.5, name: '',
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
}


const search_input = document.querySelector('#search_input');
const stocks_container = document.querySelector('#stocks_container');
const MAXIMUM_NUMBER_OF_STOCK_CONTAINERS = 14;
search_input.addEventListener('keyup', event => {
	if (event.key === 'Enter') {
		const stock_tick_string = search_input.value.toUpperCase();
		search_input.value = '';
		const stock_containers = stocks_container
			.querySelectorAll('.stock_outer_container');
		let new_stock_container = null;
		let new_stock_inner_container;
		for (const stock_container of stock_containers) {
			const stock_tick = stock_container.querySelector('.stock_tick');
			if (stock_tick.textContent.trim() === stock_tick_string) {
				new_stock_container = stock_container;
				new_stock_inner_container = stock_container
					.querySelector('.stock_inner_container');
				stocks_container.prepend(new_stock_container);
				break;
			}
		}
		if (new_stock_container === null) {
			if (stock_containers.length
					>= MAXIMUM_NUMBER_OF_STOCK_CONTAINERS) {
				new_stock_container = 
					stock_containers[stock_containers.length - 1];
				new_stock_container.remove();
				new_stock_inner_container = new_stock_container
					.querySelector('.stock_inner_container');
				while (new_stock_inner_container.firstChild) {
					new_stock_inner_container
						.removeChild(new_stock_inner_container.lastChild);
				}
			} else {
				new_stock_container = document.createElement('div');
				new_stock_container.classList.add('stock_outer_container');
				new_stock_inner_container = document.createElement('form');
				new_stock_inner_container
					.classList.add('stock_inner_container');
				new_stock_inner_container.action = 
					'index.php?rt=korisnik/transaction';
				new_stock_inner_container.method = 'post';
				new_stock_container.append(new_stock_inner_container);
			}
		
			const stock_info_container = document.createElement('div');
			stock_info_container.classList.add('stock_info_container');
			const stock_tick_container = document.createElement('div');
			stock_tick_container.classList.add('stock_tick_container');
			const stock_tick = document.createElement('div');
			stock_tick.classList.add('stock_tick');
			stock_tick.textContent = stock_tick_string;
			const stock_tick_input = document.createElement('input');
			stock_tick_input.type = 'hidden';
			stock_tick_input.name = 'oznaka_dionice';
			stock_tick_input.value = stock_tick_string;
			stock_tick_container.append(stock_tick);
			stock_tick_container.append(stock_tick_input);
			const daily_chart_container = document.createElement('div');
			daily_chart_container.classList.add('daily_chart_container');
			const daily_chart = document.createElement('div');
			daily_chart.classList.add('daily_chart');
			daily_chart.dataset.stock_tick = stock_tick_string;
			daily_chart_container.append(daily_chart);
			const stock_price_container = document.createElement('div');
			stock_price_container.classList.add('stock_price_container');
			const stock_price = document.createElement('div');
			stock_price.classList.add('stock_price');
			const stock_price_input = document.createElement('input');
			stock_price_input.type = 'hidden';
			stock_price_input.name = 'cijena';
			stock_price_input.value = '';
			stock_price_container.append(stock_price);
			stock_price_container.append(stock_price_input);
			const stock_percentage_container = document.createElement('div');
			stock_percentage_container
				.classList.add('stock_percentage_container');
			const stock_percentage = document.createElement('div');
			stock_percentage.classList.add('stock_percentage');
			stock_percentage_container.append(stock_percentage);
			stock_info_container.append(stock_tick_container);
			stock_info_container.append(daily_chart_container);
			stock_info_container.append(stock_price_container);
			stock_info_container.append(stock_percentage_container);
			
			const transaction_container = document.createElement('div');
			transaction_container.classList.add('transaction_container');
			const coquantity_text_container = document.createElement('div');
			coquantity_text_container
				.classList.add('coquantity_text_container');
			const quantity_text = document.createElement('div');
			quantity_text.classList.add('quantity_text');
			quantity_text.textContent = 'Količina:';
			coquantity_text_container.append(quantity_text);
			const quantity_input_container = document.createElement('div');
			quantity_input_container.classList.add('quantity_input_container');
			const quantity_input = document.createElement('input');
			quantity_input.classList.add('quantity_input');
			quantity_input.type = 'text';
			quantity_input.placeholder = 'npr. 3';
			quantity_input.name = 'kolicina';
			quantity_input.value = '';
			quantity_input_container.append(quantity_input);
			const buy_container = document.createElement('div');
			buy_container.classList.add('buy_container');
			const buy_button = document.createElement('button');
			buy_button.classList.add('buy_button');
			buy_button.type = 'submit';
			buy_button.name = 'kupi';
			buy_button.textContent = 'Kupi';
			buy_container.append(buy_button);
			const sell_container = document.createElement('div');
			sell_container.classList.add('sell_container');
			const sell_button = document.createElement('button');
			sell_button.classList.add('sell_button');
			sell_button.type = 'submit';
			sell_button.name = 'prodaj';
			sell_button.textContent = 'Prodaj';
			sell_container.append(sell_button);
			transaction_container.append(coquantity_text_container);
			transaction_container.append(quantity_input_container);
			transaction_container.append(buy_container);
			transaction_container.append(sell_container);
			
			addStockInfo(stock_info_container);
			new_stock_inner_container.append(stock_info_container);
			new_stock_inner_container.append(transaction_container);
			stocks_container.prepend(new_stock_container);
		}
	}
});
//



function sleep(ms) {
	return new Promise((resolve, reject) => setTimeout(resolve, ms));
}

async function main() {
	const stock_info_divs = document.querySelectorAll('.stock_info_container');
	for (const stock_info_container of stock_info_divs) {
		await addStockInfo(stock_info_container);
		await sleep(100);	
	}
}

main();



function showTransactionContainer(stock_outer_container) {
	const stock_inner_container = stock_outer_container
		.querySelector('.stock_inner_container');
	const transaction_container = stock_inner_container
		.querySelector('.transaction_container');
	stock_inner_container.classList.add('stock_inner_container_selected');
	transaction_container.style.display = 'grid';
	stock_outer_container.style.gridRowEnd = 'span 2';
}

function hideTransactionContainer(stock_outer_container) {
	const stock_inner_container = stock_outer_container
		.querySelector('.stock_inner_container');
	const transaction_container = stock_inner_container
		.querySelector('.transaction_container');
	stock_inner_container.classList.remove('stock_inner_container_selected');
	transaction_container.style.display = '';
	stock_outer_container.style.gridRowEnd = '';
}

stocks_container.addEventListener('click', event => {
	const stock_inner_container = event.target.closest('.stock_inner_container');
	if (stock_inner_container) {
		const stock_outer_container = stock_inner_container.parentNode;
		const transaction_container = stock_inner_container
			.querySelector('.transaction_container');
		if (stock_inner_container.classList
				.contains('stock_inner_container_selected')) {
			hideTransactionContainer(stock_outer_container)
		} else {
			stocks_container.querySelectorAll('.stock_outer_container')
					.forEach(hideTransactionContainer);
			showTransactionContainer(stock_outer_container)
		}
	}
});



//const stock_container = document.querySelector('.stock_inner_container');
//const transaction_container = stock_container.querySelector'.transaction_container');
//console.log(stock_container);
//stock_container.classList.add('stock_inner_container_selected')
//transaction_container.style.display = 'grid';





