

class GoogleStockChart {
	
	constructor(google_chart, symbol, range, chart_interval, chart_name) {
		this.chart = google_chart;
		this.symbol = symbol.toUpperCase();
		this.range = range;
		this.chart_interval = chart_interval;
		this.chart_name = chart_name;
		this.possible_options = ['5y', '2y', '1y', '6m', '3m', '1m', '5d', '1d', 'lfd', 'id'];
		this.options = {
			title: this.chart_name,
			legend: 'none'
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
		this.data_array = await this.getPrices();
		if (this.data_array) {
			this.updateOptions();
			data = google.visualization.arrayToDataTable(this.data_array, true); // Treat the first row as data.
		}
		return data;
	}
	
	async getPrices() {
		const lower_range = this.range.toLowerCase();
		if (! this.possible_options.includes(lower_range)) {
			this.possible_options.push('id');
			console.log(`Range ${this.range} is not one of options(${this.possible_options}).`);
			return
		}
		
		if (['minute', 'date'].includes(this.filter[0])) {
			this.filter.shift();
		}
		if (['1d', 'lfd', 'id'].includes(lower_range)) {
			this.filter.unshift('minute');
		} else {
			this.filter.unshift('date');
		}
		const url = '../Virtualna-burza/controller/stock_data.php?stock_ticks='
			+ this.symbol + '&range=' + this.range;
		console.log(url);
		const response = await fetch(url);
		const raw_data = await response.json();
		
		const symbol_data = raw_data[this.symbol];
		const data_array = symbol_data.map(datapoint =>
			this.filter.map(key => datapoint[key]))
		this.aproximateNullValues(data_array);
		const data_array_with_interval = [];
		for (let i = 0; i < data_array.length; i += this.chart_interval) {
			data_array_with_interval.push(data_array[i]);
		}
		data_array_with_interval.push(data_array[data_array.length - 1]);
		return data_array_with_interval;
	}	
	
	aproximateNullValues(data_array) {
		let i, j, start, end, range, k;
		for (let col = 1; col < data_array[0].length; col++) {
			i = 0;
			while (i < data_array.length && data_array[i][col]) {
				i++;
			}
			while (i < data_array.length) {
				if (! data_array[i][col]) {
					start = i - 1;
					end = i + 1;
					while (end < data_array.length && ! data_array[end][col]) {
						end++;
					}
					if (start < 0) {
						for (j = 0; j < end; j++) {
							data_array[j][col] = data_array[end][col];
						}
					} else if (end >= data_array.length) {
						for (j = start + 1; j < end; j++) {
							data_array[j][col] = data_array[start][col];
						}
					} else {
						range = end - start;
						for (j = start + 1; j < end; j++) {
							k = (j - start) / range;
							data_array[j][col] =
								k * data_array[start][col] + (1 - k) * data_array[end][col];
						}
					}
					i = end;
				}
				i++;
			}
		}
	}
	
	updateOptions() {
		const [vertical_minimum, vertical_maximum] = 
			this.getChartMinAndMax(this.data_array);
		this.options.vAxis = this.options.vAxis || {};			
		Object.assign(this.options.vAxis, {
			viewWindowMode: 'explicit',
			viewWindow: {
				min: vertical_minimum,
				max: vertical_maximum
			}
		});
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
	
	constructor(container, symbol, {range = 'id', interval = 1, type = 'area',
			ratio = null, name = '', hide_axis = false, hide_gridlines = false,
			color_by_percentage = false} = {}) {
		this.container = container;
		this.symbol = symbol.toUpperCase();
		this.range = range;
		this.chart_interval = interval;
		this.type = type.toLowerCase();
		this.ratio = ratio;
		this.name = name;
		this.hide_axis = hide_axis;
		this.hide_gridlines = hide_gridlines;
		this.color_by_percentage = color_by_percentage;
		this.data_initialized = false;
		this.setUpContainer();
		this.initialize();
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
	
	async waitGoogleChartsLoaded() {
		if (StockChart.google_charts_loaded) {
			return;
		} else {
			return new Promise((resolve, reject) => {
				google.charts.setOnLoadCallback(() => {
					StockChart.google_charts_loaded = true;
					resolve();
				});
			})
		}
	}
	
	async initialize() {
		await this.waitGoogleChartsLoaded();
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
			this.google_chart.options.candlestick =
				this.google_chart.options.candlestick || {};
			Object.assign(this.google_chart.options.candlestick, {
					fallingColor: {
						strokeWidth: 0,
						fill: '#a52714'
					}, // red
					risingColor: {
						strokeWidth: 0,
						fill: '#0f9d58'
					} // green
			});
			google.visualization.events.addListener(this.google_chart.chart,
				'ready', this.colorVerticalLines.bind(this));
		}
		if (this.hide_axis) {
			this.google_chart.options.vAxis = 
				this.google_chart.options.vAxis || {};
			Object.assign(this.google_chart.options.vAxis, {
				//textPosition: 'none',
				ticks: []
			});			
			this.google_chart.options.hAxis = 
				this.google_chart.options.hAxis || {};
			Object.assign(this.google_chart.options.hAxis, {
				textPosition: 'none'
			});
			this.google_chart.options.chartArea = 
				this.google_chart.options.chartArea || {};
			Object.assign(this.google_chart.options.chartArea, {
				top: 0,
				height: '80%'
			});
		}		
		if (this.hide_gridlines) {
			this.google_chart.options.vAxis = 
				this.google_chart.options.vAxis || {};
			Object.assign(this.google_chart.options.vAxis, {
				gridlines: {
					color: 'none'
				}
			});	
		}
		if (this.color_by_percentage) {
			this.google_chart.data = await this.google_chart.getData();
			this.onDataInitialized();
			const percentage = await this.getPercentage();
			if (percentage >= 0) {
				this.google_chart.options.colors = ['#00BB00'];
				//this.google_chart.options.colors = ['#34E36F'];
			} else {
				//this.google_chart.options.colors = ['#EE0000'];
				this.google_chart.options.colors = ['#FF3331'];
			}
			await this.google_chart.draw();
		} else {
			await this.google_chart.draw();
			this.onDataInitialized();
		}		
		StockChart.resize_observer.observe(this.container);
	}
	
	onDataInitialized() {
		this.data_initialized = true;
	}
	
	async waitDataInitialized() {
		if (this.data_initialized) {
			return;
		} else {
			return new Promise((resolve, reject) => {
				this.onDataInitialized = () => {
					this.data_initialized = true;
					resolve();
				};
			})
		}
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
			this.symbol = symbol.toUpperCase();
			this.google_chart.symbol = symbol.toUpperCase();
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
	
	async getFirstPrice() {
		await this.waitDataInitialized();
		const first_prices = this.google_chart.data_array[0];
		const price = first_prices[first_prices.length - 1];
		return price;
	}
	
	async getLastPrice() {
		await this.waitDataInitialized();
		const last = this.google_chart.data_array.length - 1;
		const last_prices = this.google_chart.data_array[last];
		const price = last_prices[last_prices.length - 1];
		return price;
	}
	
	async getPercentage() {
		const first_price = await this.getFirstPrice();
		const last_price = await this.getLastPrice();
		const percentage = (last_price - first_price) / first_price * 100;
		return percentage;
	}
	
}

google.charts.load('current', {
	'packages': ['corechart']
});


//google.charts.setOnLoadCallback(StockChart.onGoogleChartsLoaded);