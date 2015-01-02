$(document).ready(function() {
	$('#termin a[href="#prihlaska"]').click(function() {
		$('#frm-application-trainingId').val($(this).data('id'));
	});
	$('#uskutecnene-terminy a[href="#uskutecnene-terminy"]').click(function() {
		$('#uskutecnene-terminy-container').fadeToggle('fast');
		return false;
	});
	$('#uskutecnene-terminy-container').hide();

	var Application = {};
	Application.loadDataControls = function() {
		$('#loadDataControls > span').hide();
		$('#loadDataAgain').show();
		$('#loadDataAgain a').click(Application.loadData);
	};
	Application.loadData = function(event) {
		$('#loadDataControls > span').hide();
		$('#loadDataWait').show();
		event.preventDefault();
		var load = $.ajax({
			url: $('#loadData').data('url'),
			data: {
				country: $('#frm-application-country').val(),
				companyId: $('#frm-application-companyId').val().replace(/ /g, ''),
			},
			timeout: 5000
		});
		load.done(function(data) {
			if (data.status == 200) {
				$.each(['companyId', 'companyTaxId', 'company', 'street', 'city', 'zip', 'country'], function(key, value) {
					$('#frm-application-' + value).val(data[value]);
				});
				Application.loadDataControls();
			} else if (data.status == 400) {
				Application.loadDataControls();
				$('#loadDataNotFound').show();
			} else {
				Application.loadDataControls();
				$('#loadDataError').show();
			}
		});
		load.fail(function() {
			Application.loadDataControls();
			$('#loadDataError').show();
		});
	};
	$('#loadData').click(Application.loadData);
	$('#loadData').show();
});
