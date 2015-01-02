$(document).ready(function() {
	$('#termin a[href="#prihlaska"]').click(function() {
		$('#frm-application-trainingId').val($(this).data('id'));
	});
	$('#uskutecnene-terminy a[href="#uskutecnene-terminy"]').click(function() {
		$('#uskutecnene-terminy-container').fadeToggle('fast');
		return false;
	});
	$('#uskutecnene-terminy-container').hide();

	var APPLICATION = APPLICATION || {};
	APPLICATION.loadDataControls = function() {
		$('#loadDataControls > span').hide();
		$('#loadDataAgain').show();
		$('#loadDataAgain a').click(APPLICATION.loadData);
	};
	APPLICATION.loadData = function(event) {
		APPLICATION.loaded = false;
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
			APPLICATION.loaded = true;
			if (data.status == 200) {
				$.each(['companyId', 'companyTaxId', 'company', 'street', 'city', 'zip', 'country'], function(key, value) {
					$('#frm-application-' + value).val(data[value]);
				});
				APPLICATION.loadDataControls();
			} else if (data.status == 400) {
				APPLICATION.loadDataControls();
				$('#loadDataNotFound').show();
			} else {
				APPLICATION.loadDataControls();
				$('#loadDataError').show();
			}
		});
		load.fail(function() {
			if (!APPLICATION.loaded) {
				APPLICATION.loadDataControls();
				$('#loadDataError').show();
			}
		});
	};
	$('#loadData').click(APPLICATION.loadData);
	$('#loadData').show();
});
