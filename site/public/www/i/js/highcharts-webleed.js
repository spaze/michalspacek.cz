$(document).ready(function() {
	var month = '';
	var data = [];
	var hosts = [];
	$('#points div').each(function () {
		p = $(this).data('point');
		port = $(this).data('port');
		if (typeof data[port] == 'undefined') {
			data[port] = [];
		}
		if (port == 'any') {
			hosts[Date.UTC(p[0], p[1], p[2])] = {
				y: p[3],
				p: p[4]
			};
		} else {
			data[port].push({
				x: Date.UTC(p[0], p[1], p[2]),
				y: p[3],
				p: p[4]
			});
		}
	});
	$('#chart').highcharts({
		credits: {
			text: 'heartbleed.michalspacek.cz',
			href: 'http://heartbleed.michalspacek.cz',
			position: {
				align: 'left',
				x: 100,
				y: -90
			}
		},
		chart: {
			backgroundColor: '#DDD',
			zoomType: 'x'
		},
		title: {
			text: 'Heart-bleeding CZ IP addresses'
		},
		subtitle: {
			text: 'Sudden drops indicate possible outages',
			floating: true,
			y: 55
		},
		tooltip: {
			crosshairs: {
				color: '#C0C0C0',
				dashStyle: 'shortdot'
			},
			shared: true,
			formatter: function() {
				s = 'Vulnerable IPs on ' + Highcharts.dateFormat('%Y-%m-%d, %A', this.x);
				$.each(this.points, function(i, point) {
					s += '<br/><span style="color: ' + point.series.color + '">●</span> ' + point.series.name + ': <strong>' + point.y + '</strong> (' + this.point.p + ' %)';
				});
				if (typeof hosts[this.x] != 'undefined') {
					s += '<br/>Any port (unique hosts): <strong>' + hosts[this.x].y + '</strong> (' + hosts[this.x].p + ' %)';
				}
				return s;
			}
		},
		xAxis: {
			type: 'datetime',
			dateTimeLabelFormats: {
				month: '%b %e',
				year: '%b',
				day: '%b %e'
			},
			labels: {
				formatter: function() {
					if (month != Highcharts.dateFormat('%b', this.value)) {
						month = Highcharts.dateFormat('%b', this.value);
						return Highcharts.dateFormat('%e<br>%b', this.value);
					} else {
						return Highcharts.dateFormat('%e', this.value);
					}
				}
			},
			tickInterval: 7 * 24 * 3600 * 1000
		},
		yAxis: {
			title: {
				text: 'Vulnerable IP addresses'
			},
			type: 'logarithmic'
		},
		plotOptions: {
			series: {
				animation: false,
				connectNulls: true
			}
		},
		legend: {
			margin: 0
		},
		series: [
			{
				type: 'area',
				name: 'Port 443 (HTTPS)',
				color: '#B90000',
				fillColor: {
					linearGradient: [0, 0, 0, 300],
					stops: [
						[0, '#B90000'],
						[1, Highcharts.Color('#B90000').setOpacity(0).get('rgba')]
					]
				},
				data: data[443],
				marker: {
					symbol: 'circle'
				}
			},
			{
				type: 'line',
				name: 'Port 993 (IMAPS)',
				color: '#F7F7F7',
				data: data[993],
				marker: {
					symbol: 'circle',
					lineColor: '#D7141A'
				}
			},
			{
				type: 'line',
				name: 'Port 995 (POP3S)',
				color: '#D7141A',
				data: data[995],
				marker: {
					symbol: 'circle'
				}
			},
			{
				type: 'line',
				name: 'Port 465 (SMTPS)',
				color: '#11457E',
				data: data[465],
				marker: {
					symbol: 'circle'
				}
			},
		]
   });
});
