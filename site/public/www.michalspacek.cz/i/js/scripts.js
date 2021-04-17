$(document).ready(function() {
	$('#termin a[href="#prihlaska"]').on('click', function() {
		$('#frm-application-trainingId').val($(this).data('id'));
	});

	var APPLICATION = APPLICATION || {};
	APPLICATION.hideLoadControls = function() {
		$('#loadDataControls span').addClass('hidden');
	};
	APPLICATION.showLoadControls = function(selector) {
		$(selector).removeClass('hidden');
	};
	APPLICATION.loadData = function(event) {
		event.preventDefault();
		if ($('#frm-application-country').val() == '' || $('#frm-application-companyId').val().replace(/ /g, '') == '') {
			alert($('#errorCountryCompanyMissing').text());
			return;
		}
		APPLICATION.hideLoadControls();
		APPLICATION.showLoadControls('#loadDataWait');
		var load = $.ajax({
			url: $('#loadData').data('url'),
			data: {
				country: $('#frm-application-country').val(),
				companyId: $('#frm-application-companyId').val().replace(/ /g, ''),
			},
			timeout: 10000
		});
		load.done(function(data) {
			APPLICATION.hideLoadControls();
			APPLICATION.showLoadControls('#loadDataAgain');
			if (data.status == 200) {
				$.each(['companyId', 'companyTaxId', 'company', 'street', 'city', 'zip', 'country'], function(key, value) {
					$('#company').find('#frm-application-' + value).val(data[value]);
				});
			} else if (data.status == 400) {
				APPLICATION.showLoadControls('#loadDataNotFound');
			} else {
				APPLICATION.showLoadControls('#loadDataError');
			}
		});
		load.fail(function() {
			APPLICATION.hideLoadControls();
			APPLICATION.showLoadControls('#loadDataAgain, #loadDataError');
		});
	};
	$('#loadData a, #loadDataAgain a').on('click', APPLICATION.loadData);
	$('#frm-application-companyId').on('keypress', function(e) {
		if (e.which === 13) {
			APPLICATION.loadData(e);
		}
	});
	$('#loadDataDisabled').addClass('hidden');
	APPLICATION.hideLoadControls();
	APPLICATION.showLoadControls('#loadDataControls, #loadData');
	APPLICATION.changeLabels = function() {
		$('#frm-application').find('label').each(function() {
			var label = $(this).data('label-' + $('#frm-application-country').val());
			if (label) {
				$(this).text(label);
			}
		});
	};
	$('#frm-application-country').change(APPLICATION.changeLabels);
	APPLICATION.changeLabels();

	if ($('#slides-container .highlight').length) {
		$('html, body').animate({scrollTop: $('#slides-container .highlight').offset().top - 10});
	}

	var para = $('.column-content').find(window.location.hash)
	if (para.length) {
		para.addClass('highlight');
		$('html, body').animate({scrollTop: para.offset().top - 10});
	}
});
