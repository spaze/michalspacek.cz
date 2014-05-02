$(document).ready(function() {
	data = [];
	$('#points div').each(function () {
		p = $(this).data('point');
		data.push({
			x: Date.UTC(p[0], p[1], p[2]),
			y: p[3],
			p: p[4]
		});
	});
	$('#chart').highcharts({
		chart: {
			backgroundColor: '#DDD',
			zoomType: 'x'
		},
		title: {
			text: 'Heart-bleeding CZ IP addresses, port 443 (HTTPS)'
		},
		tooltip: {
			crosshairs: {
				color: '#C0C0C0',
				dashStyle: 'shortdot'
			},
			formatter: function() {
				return Highcharts.dateFormat('%Y-%m-%d, %A', this.x) + '<br/>Vulnerable IPs: <strong>' + this.y + '</strong> (' + this.point.p + ' %)';
			}
		},
		xAxis: {
			type: 'datetime',
				dateTimeLabelFormats: {
					month: '%b %e',
					year: '%b',
					day: '%b %e'
				},
			tickInterval: 24 * 3600 * 1000
		},
		yAxis: {
			title: {
				text: 'IP addresses'
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
			enabled: false
		},
		series: [
			{
				type: 'area',
				name: 'Vulnerable IP addresses',
				color: '#B90000',
				fillColor: {
					linearGradient: [0, 0, 0, 300],
					stops: [
						[0, '#B90000'],
						[1, Highcharts.Color('#B90000').setOpacity(0).get('rgba')]
					]
				},
				data: data
			}
		]
   });
});
