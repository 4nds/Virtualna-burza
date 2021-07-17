

class GoogleStockChart {
	
	constructor(google_chart, symbol, range, chart_interval, chart_name) {
		this.chart = google_chart;
		this.symbol = symbol;
		this.range = range;
		this.chart_interval = chart_interval;
		this.chart_name = chart_name;
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
		this.options = {
			title: this.chart_name,
			legend: 'none',
		};			
	}

	async draw(refresh_data = false) {
		if (!this.data || refresh_data) {
			this.data = await this.getData();
		}
		this.chart.draw(this.data, this.options);
	}
	
	async getData() {
		let data;
		const data_array = await this.getIexPrices();
		this.data_array = data_array;
		if (data_array) {
			this.updateOptions(data_array);
			data = google.visualization.arrayToDataTable(data_array, true); // Treat the first row as data.
		}
		return data;
	}
	
	async getIexPrices() {
		const lower_range = this.range.toLowerCase();
		let data_array;
		if (['minute', 'date'].includes(this.filter[0])) {
			this.filter.shift();
		}
		if (lower_range === 'id') {
			this.filter.unshift('minute');
			data_array = await this.getIexIntradayPrices();
		} else {
			if (this.possible_options.includes(lower_range)) {
				this.filter.unshift('date');
				data_array = await this.getIexHistoricalPrices();
			} else {
				this.possible_options.push('id');
				console.log(`Range ${this.range} is not one of options(${this.possible_options}).`);
			}
		}
		this.aproximateNullValues(data_array);
		return data_array;
	}
	
	async getIexIntradayPrices() {
		const url = `https://sandbox.iexapis.com/stable/stock/${this.symbol}/intraday-prices?token=Tsk_846d0b9fb89741c583b142ee2f9bb434&filter=${this.filter.join(',')}&chartInterval=${this.chart_interval}`;
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
			while (i < data_array.length && ! data_array[i][col]) {
				i++;
			}
			while (i < data_array.length) {
				if (! data_array[i][col]) {
					start = i - 1;
					end = i + 1;
					while (end < data_array.length && ! data_array[end][col]) {
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
		const [vertical_minimum, vertical_maximum] = 
			this.getChartMinAndMax(data_array);
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


class StockChart {
	
	static uninitialized_charts = [];
	static google_charts_loaded = false;
	static resize_observer = new ResizeObserver(entries => {
		if (this.resize_timer) {
			window.clearTimeout(this.resize_timer);
		}
		this.resize_timer = setTimeout(() => {
			const evt = new CustomEvent('resizeend');
			entries.forEach(entry => entry.target.dispatchEvent(evt));
		}, 200);
		
	});
	
	constructor(container, symbol, {range = 'id', interval = 1,
			type = 'area', ratio = null, name = ''} = {}) {
		this.container = container;
		this.symbol = symbol
		this.range = range;
		this.chart_interval = interval;
		this.type = type.toLowerCase();
		this.ratio = ratio;
		this.name = name;
		this.setUpContainer();
		if (StockChart.google_charts_loaded) {
			this.initialize();
		} else {
			StockChart.uninitialized_charts.push(this);
		}
	}
	
	setUpContainer() {
		this.outer_container = document.createElement('div');
		this.outer_container.style.width = '100%';
		this.outer_container.style.height = '100%';
		this.outer_container.style.display = 'flex';
		this.outer_container.style.justifyContent = 'center';
		this.outer_container.style.alignItems = 'center';
		
		this.center_container = document.createElement('div');
		this.center_container.style.position = 'relative';
		this.center_container.style.display = 'inline-block';
		
		this.ratio_image = new Image();
		
		this.inner_container = document.createElement('div');
		this.inner_container.style.position = 'absolute';
		this.inner_container.style.top = '0';
		this.inner_container.style.bottom = '0';
		this.inner_container.style.left = '0';
		this.inner_container.style.right = '0';
		
		this.center_container.append(this.ratio_image);
		this.center_container.append(this.inner_container);
		this.outer_container.append(this.center_container);
		this.container.append(this.outer_container);
		
		this.setSize();

		this.container.addEventListener('resizeend', event => {
			this.resize();
		});
		
	}
	
	static onGoogleChartsLoaded() {
		StockChart.uninitialized_charts
			.forEach(stock_chart => stock_chart.initialize());
		StockChart.uninitialized_charts.length = 0;
		StockChart.google_charts_loaded = true;
	}
	
	async initialize() {
		if (this.type === 'area') {
			const google_area_chart =
				new google.visualization.AreaChart(this.inner_container);
			this.google_chart = new GoogleStockChart(google_area_chart, 
				this.symbol, this.range, this.chart_interval, this.name);
			this.google_chart.filter = ['close'];
		} else if (this.type === 'candlestick') {
			const google_candlestick_chart =
				new google.visualization.CandlestickChart(this.inner_container);
			this.google_chart = new GoogleStockChart(google_candlestick_chart,
				this.symbol, this.range, this.chart_interval, this.name);
			this.google_chart.filter = ['low', 'open', 'close', 'high'];
			this.google_chart.options.candlestick = {
				fallingColor: {
					strokeWidth: 0,
					fill: '#a52714'
				}, // red
				risingColor: {
					strokeWidth: 0,
					fill: '#0f9d58'
				} // green
			};
			google.visualization.events.addListener(this.google_chart.chart,
				'ready', this.colorVerticalLines.bind(this));
		}
		await this.google_chart.draw();
		StockChart.resize_observer.observe(this.container);
	}
	
	colorVerticalLines() {
		const falling_candle_rect_selector = `svg > g > g > g > g >
			rect[fill='${this.google_chart.options.candlestick.fallingColor.fill}']`;
		const rising_candle_rect_selector = `svg > g > g > g > g >
			rect[fill='${this.google_chart.options.candlestick.risingColor.fill}']`;
		const falling_line_rects = Array.from(this.google_chart.chart.container.querySelectorAll(
			falling_candle_rect_selector)).map(rect => rect.previousSibling);
		const rising_line_rects = Array.from(this.google_chart.chart.container.querySelectorAll(
			rising_candle_rect_selector)).map(rect => rect.previousSibling);
		falling_line_rects.forEach(rect => rect.style.fill =
			this.google_chart.options.candlestick.fallingColor.fill);
		rising_line_rects.forEach(rect => rect.style.fill =
			this.google_chart.options.candlestick.risingColor.fill);
	}
	
	setSize() {
		const container_rect = this.container.getBoundingClientRect();
		let ratio_image_src;
		if (this.ratio === null) {
			this.ratio = container_rect.height / container_rect.width;
		}
		if (container_rect.height / container_rect.width >= this.ratio) {
			this.center_container.style.height = '100%';
			ratio_image_src = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAIAAAABCAQAAABeK7cBAAAAC0lEQVR42mNkAAIAAAoAAv/lxKUAAAAASUVORK5CYII=';
			this.ratio_image.style.height =
				(this.ratio / 2 * 100).toFixed(4) + '%';
		} else {
			this.center_container.style.width = '100%';
			ratio_image_src = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAACCAQAAAAziH6sAAAADklEQVR42mNkYGBkYAAAAA8AA7qm8EcAAAAASUVORK5CYII='
			this.ratio_image.style.width =
				(1 / this.ratio / 2 * 100).toFixed(4) + '%';
		}
		if (this.ratio_image.src !== ratio_image_src) {
			this.ratio_image.src = ratio_image_src;
		}
	}
	
	async resize(ratio) {
		this.ratio = ratio || this.ratio
		this.setSize();
		if (StockChart.google_charts_loaded) {
			await this.google_chart.draw();
		}
	}
	
	async refresh({symbol, range , interval, name} = {}) {
		if (symbol) {
			this.symbol = symbol;
			this.google_chart.symbol = symbol;
		}
		if (range) {
			this.range = range;
			this.google_chart.range = range;
		}
		if (interval) {
			this.chart_interval = interval;
			this.google_chart.chart_interval = interval;
		}
		if (name) {
			this.name = name;
			this.google_chart.chart_name = name;
			this.google_chart.options.title = name;
		}
		if (StockChart.google_charts_loaded) {
			await this.google_chart.draw(true);
		}
	}
}

google.charts.load('current', {
	'packages': ['corechart']
});


google.charts.setOnLoadCallback(StockChart.onGoogleChartsLoaded);