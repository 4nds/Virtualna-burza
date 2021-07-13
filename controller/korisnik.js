
google.charts.load('current', {
	'packages': ['corechart']
});

class StockChart {
	constructor(google_chart, symbol, range, chart_interval = 1) {
		this.google_chart = google_chart;
		this.symbol = symbol;
		this.range = range;
		this.chart_interval = chart_interval;
		this.range_map = new Map([
			['5y', 'five years'],
			['2y', 'two years'],
			['1y', 'year'],
			['6m', 'six months'],
			['3m', 'three months'],
			['1m', 'month'],
			['5d', 'five days'],
			['1d', 'day'],
			['id', 'day']
		]);
		this.possible_options = Array.from(this.range_map.keys());
		this.chart_name = this.getChartName();
		this.options = {
			title: this.chart_name,
			legend: 'none',
		};			
	}
	
	getChartName() {
		const range_name = this.range_map.get(this.range);	
		const chart_name = `${this.symbol.toUpperCase()}, last ${range_name}`;
		return chart_name;
	}
	
	async draw(refresh_data = false) {
		if (!this.data || refresh_data) {
			this.data = await this.getData();
		}
		this.google_chart.draw(this.data, this.options);
	}
	
	async getData() {
		let data;
		const data_array = await this.getIexPrices();
		if (data_array) {
			this.updateOptions(data_array);
			data = google.visualization.arrayToDataTable(data_array, true); // Treat the first row as data.
		}
		return data;
	}
	
	async getIexPrices() {
		const lower_range = this.range.toLowerCase();
		let data_array;
		if (lower_range === 'id') {
			this.filter.unshift('minute');
			data_array = await this.getIexIntradayPrices();
		} else {
			if (this.possible_options.includes(lower_range)) {
				this.filter.unshift('date');
				data_array = await this.getIexHistoricalPrices();
			} else {
				this.possible_options.push('id');
				console.log(`Range ${range} is not one of options(${this.possible_options}).`);
			}
		}
		this.aproximateNullValues(data_array);
		return data_array;
	}
	
	async getIexIntradayPrices() {
		const url = `https://sandbox.iexapis.com/stable/stock/${this.symbol}/intraday-prices?token=Tsk_846d0b9fb89741c583b142ee2f9bb434&filter=${this.filter.join(',')}&chartInterval=${this.chart_interval}`
		console.log(url);
		const data_array = await this.getIexPricesFromUrl(url);
		return data_array;
	}
	
	async getIexHistoricalPrices() {
		const url = `https://sandbox.iexapis.com/stable/stock/${this.symbol}/chart/${this.range}?token=Tsk_846d0b9fb89741c583b142ee2f9bb434&filter=${this.filter.join(',')}&chartInterval=${this.chart_interval}`;
		console.log(url);
		const data_array = await this.getIexPricesFromUrl(url);
		return data_array;
	}
	
	async getIexPricesFromUrl(api_url) {
		const response = await fetch(api_url);
		const raw_data = await response.json();
		const data_array = raw_data.map(datapoint =>
			this.filter.map(key => datapoint[key]))
		return data_array;
	}
	
	aproximateNullValues(data_array) {
		let i, j, start, end, range, k;
		for (let col = 1; col < data_array[0].length; col++) {
			i = 0;
			while (i < data_array.length && data_array[i][col] === null) {
				i++;
			}
			while (i < data_array.length) {
				if (data_array[i][col] === null) {
					start = i - 1;
					end = i + 1;
					while (end < data_array.length && data_array[end][col] === null) {
						end++;
					}
					if (end < data_array.length) {
						range = end - start;
						for (j = start + 1; j < end; j++) {
							k = (j - start) / range;
							data_array[j][col] =
								k * data_array[start][col] + (1 - k) * data_array[end][col];
						}
					}
					i = end;
				}
				i++
			}
		}
	}
	
	updateOptions(data_array) {
		const [vertical_minimum, vertical_maximum] = this.getChartMinAndMax(data_array);
		this.options.vAxis = {
			viewWindowMode: 'explicit',
			viewWindow: {
				min: vertical_minimum,
				max: vertical_maximum
			}
		}			
	}
	
	getChartMinAndMax(data_array) {
		const flatten_data_array = data_array.flatMap(datapoint =>
			datapoint.slice(1)).filter(price => price !== null);
		const minimum_price = Math.min(...flatten_data_array);
		const maximum_price = Math.max(...flatten_data_array);
		const price_range = maximum_price - minimum_price;
		const vertical_minimum = minimum_price - 0.2 * price_range;
		const vertical_maximum = maximum_price + 0.2 * price_range;
		return [vertical_minimum, vertical_maximum];
	}
							
}

class StockCandlestickChart extends StockChart {

	constructor(container, symbol, range, chart_interval = 1) {
		const chart = new google.visualization.CandlestickChart(container);
		super(chart, symbol, range, chart_interval);
		this.filter = ['low', 'open', 'close', 'high'];
		this.options.candlestick = {
			fallingColor: {
				strokeWidth: 0,
				fill: '#a52714'
			}, // red
			risingColor: {
				strokeWidth: 0,
				fill: '#0f9d58'
			} // green
		};
		google.visualization.events.addListener(this.google_chart, 'ready',
			this.colorVerticalLines.bind(this));
	}
	
	colorVerticalLines() {
		const falling_candle_rect_selector = `svg > g > g > g > g >
			rect[fill='${this.options.candlestick.fallingColor.fill}']`;
		const rising_candle_rect_selector = `svg > g > g > g > g >
			rect[fill='${this.options.candlestick.risingColor.fill}']`;
		const falling_line_rects = Array.from(this.google_chart.container.querySelectorAll(
			falling_candle_rect_selector)).map(rect => rect.previousSibling);
		const rising_line_rects = Array.from(this.google_chart.container.querySelectorAll(
			rising_candle_rect_selector)).map(rect => rect.previousSibling);
		falling_line_rects.forEach(rect =>
			rect.style.fill = this.options.candlestick.fallingColor.fill);
		rising_line_rects.forEach(rect =>
			rect.style.fill = this.options.candlestick.risingColor.fill);
	}
	
}

class StockAreaChart extends StockChart {

	constructor(container, symbol, range, chart_interval = 1) {
		const chart = new google.visualization.AreaChart(container);
		super(chart, symbol, range, chart_interval);
		this.filter = ['close'];
	}
	
}

function createCharts(stock_tick) {
	const stock_section = document.createElement('section');
	stock_section.classList.add('stock');
	const charts_div = document.createElement('div');
	charts_div.classList.add('charts');
	
	const year_chart_container = document.createElement('div');
	year_chart_container.classList.add('year_chart_container');
	year_chart_container.classList.add('chart_container');
	const year_image = new Image();
	year_image.src = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAIAAAABCAQAAABeK7cBAAAAC0lEQVR42mNkAAIAAAoAAv/lxKUAAAAASUVORK5CYII=';
	const year_chart_div = document.createElement('div');
	year_chart_div.classList.add('year_chart');
	year_chart_div.classList.add('chart');
	year_chart_container.append(year_image);
	year_chart_container.append(year_chart_div);
	
	const month_chart_container = document.createElement('div');
	month_chart_container.classList.add('month_chart_container');
	month_chart_container.classList.add('chart_container');
	const month_image = new Image();
	month_image.src = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAIAAAABCAQAAABeK7cBAAAAC0lEQVR42mNkAAIAAAoAAv/lxKUAAAAASUVORK5CYII=';
	const month_chart_div = document.createElement('div');
	month_chart_div.classList.add('month_chart');
	month_chart_div.classList.add('chart');
	month_chart_container.append(month_image);
	month_chart_container.append(month_chart_div);
	
	const day_chart_container = document.createElement('div');
	day_chart_container.classList.add('day_chart_container');
	day_chart_container.classList.add('chart_container');
	const day_image = new Image();
	day_image.src = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAIAAAABCAQAAABeK7cBAAAAC0lEQVR42mNkAAIAAAoAAv/lxKUAAAAASUVORK5CYII=';
	const day_chart_div = document.createElement('div');
	day_chart_div.classList.add('day_chart');
	day_chart_div.classList.add('chart');
	day_chart_container.append(day_image);
	day_chart_container.append(day_chart_div);
	
	charts_div.append(year_chart_container);
	charts_div.append(month_chart_container);
	charts_div.append(day_chart_container);
	stock_section.append(charts_div);
	
	const year_chart = new StockAreaChart(year_chart_div, stock_tick, "1y", 2);
	const month_chart = new StockCandlestickChart(month_chart_div, stock_tick, "1m");
	const day_chart = new StockCandlestickChart(day_chart_div, stock_tick, "id", 10);
	
	return [stock_section, year_chart, month_chart, day_chart];
}

function currentTime() {
	return Math.floor((new Date()).getTime() / 1000);
}

function setCharts(stock_tick, stock_sections, stock_chart_container, REFRESH_INTERVAL) {
	stock_sections.forEach(lstock_section => 
		lstock_section.style.display = 'none');
	if (stock_sections.has(stock_tick)) {
		if (Number(stock_sections.get(stock_tick).dataset.created) < 
				currentTime() - REFRESH_INTERVAL) {		
			const stock_section = stock_sections.get(stock_tick);
			const stock_section_style_width = stock_section.style.width;
			stock_section.style.width = '0px';
			stock_section.display = '';
			stock_section.style.width = stock_section_style_width;	
			return
		} else {
			stock_sections.get(stock_tick).remove();
			stock_sections.delete(stock_tick);
		}
	}
	const [stock_section, year_chart, month_chart, day_chart] =
		createCharts("twtr");
	year_chart.draw();
	month_chart.draw();
	day_chart.draw();			
	window.addEventListener('resizeend', event => {
		year_chart.draw();
		month_chart.draw();
		day_chart.draw();
	});
	stock_chart_container.append(stock_section);
	stock_section.dataset.created = currentTime();
	stock_sections.set(stock_tick, stock_section);
}


function main() {

	window.addEventListener('resize', (event) => {
		if (this.resize_timer) {
			window.clearTimeout(this.resize_timer);
		}
		this.resize_timer = setTimeout(() => {
			const evt = new CustomEvent('resizeend');
			window.dispatchEvent(evt);
		}, 200);
	});
	
	const search_input = document.querySelector('#search_input');
	const stock_tabs_div = document.querySelector('#stock_tabs');
	const stock_chart_container = document.querySelector('#stock_chart_container');
	const stock_sections = new Map();
	const REFRESH_INTERVAL = 60;
	search_input.disabled = false;
	
	const stock_tab = stock_tabs_div.querySelector('.stock_tab');
	if (stock_tab) {
		const stock_tab_text_span = stock_tab
			.querySelector('.stock_tab_text');
		const stock_tick = stock_tab_text_span.textContent.toLowerCase();
		
		setCharts(stock_tick, stock_sections, stock_chart_container, REFRESH_INTERVAL);
	}


	search_input.addEventListener('keyup', event => {
		if (event.key === 'Enter') {
			const stock_tick = search_input.value;
			search_input.value = '';
			const stock_tabs_div_length = stock_tabs_div.getBoundingClientRect().width;
			const stock_tabs = stock_tabs_div.querySelectorAll('.stock_tab');
			const stock_tab_div_length = stock_tabs[0].getBoundingClientRect().width;
			const stock_tabs_length = stock_tabs.length * stock_tab_div_length;
			if (stock_tabs_div_length - stock_tabs_length < stock_tab_div_length) {
				stock_tabs[0].remove();
			}
			
			const new_stock_tab_div = document.createElement('div');
			new_stock_tab_div.classList.add('stock_tab')
			const new_stock_tab_button = document.createElement('button');
			const new_stock_tab_text_span = document.createElement('span');
			new_stock_tab_text_span.classList.add('stock_tab_text');
			new_stock_tab_text_span.textContent = stock_tick;
			new_stock_tab_button.append(new_stock_tab_text_span);
			const new_stock_tab_exit_span = document.createElement('span');
			new_stock_tab_exit_span.classList.add('stock_tab_exit');
			new_stock_tab_exit_span.textContent = '×';
			new_stock_tab_button.append(new_stock_tab_exit_span);
			new_stock_tab_div.append(new_stock_tab_button);
			stock_tabs_div.append(new_stock_tab_div);
			
			setCharts(stock_tick, stock_sections, stock_chart_container, REFRESH_INTERVAL);		
		}
	});


	stock_tabs_div.addEventListener('click', event => {
		const stock_tab_exit_span = event.target.closest('.stock_tab_exit');
		if (stock_tab_exit_span) {
			const stock_tab_div = stock_tab_exit_span.parentNode.parentNode;
			stock_tab_div.remove();
		} else {
			const stock_tab_button = event.target.closest('.stock_tab button');
			if (stock_tab_button) {
				const stock_tab_text_span = stock_tab_button
					.querySelector('.stock_tab_text');
				const stock_tick = stock_tab_text_span.textContent.toLowerCase();
				
				setCharts(stock_tick, stock_sections, stock_chart_container, REFRESH_INTERVAL);
			}
		}
		
	});
	
}

document.querySelector('#search_input').disabled = true;
google.charts.setOnLoadCallback(main);