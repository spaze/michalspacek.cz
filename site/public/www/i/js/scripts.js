$(document).ready(function() {
	$('#termin a[href="#prihlaska"]').click(function() {
		$('#frm-application-trainingId').val($(this).data('id'));
	});
	$('#pastDates-link').click(function() {
		$('#pastDates-container').fadeToggle('fast');
		return false;
	});
	$('#pastDates-container').hide();

	var APPLICATION = APPLICATION || {};
	APPLICATION.loadData = function(event) {
		event.preventDefault();
		if ($('#frm-application-country').val() == '' || $('#frm-application-companyId').val().replace(/ /g, '') == '') {
			alert($('#errorCountryCompanyMissing').text());
			return;
		}
		$('#loadDataControls > span').hide();
		$('#loadDataWait').show();
		var load = $.ajax({
			url: $('#loadData').data('url'),
			data: {
				country: $('#frm-application-country').val(),
				companyId: $('#frm-application-companyId').val().replace(/ /g, ''),
			},
			timeout: 5000
		});
		load.done(function(data) {
			$('#loadDataControls > span').hide();
			$('#loadDataAgain').show();
			if (data.status == 200) {
				$.each(['companyId', 'companyTaxId', 'company', 'street', 'city', 'zip', 'country'], function(key, value) {
					$('#frm-application-' + value).val(data[value]);
				});
			} else if (data.status == 400) {
				$('#loadDataNotFound').show();
			} else {
				$('#loadDataError').show();
			}
		});
		load.fail(function() {
			$('#loadDataControls > span').hide();
			$('#loadDataAgain').show();
			$('#loadDataError').show();
		});
	};
	$('#loadData a').click(APPLICATION.loadData);
	$('#loadDataAgain a').click(APPLICATION.loadData);
	$('#loadData').show();
});
